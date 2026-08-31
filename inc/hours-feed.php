<?php
/**
 * Hours Feed
 *
 * Fetches lodge hours from a published Google Spreadsheet,
 * caches the result with WP transients, and provides a single
 * function the rest of the theme calls for hours data.
 *
 * ── Spreadsheet setup ────────────────────────────────────────────────────────
 * Publish the sheet: File → Share → Publish to web → Entire document → CSV
 * Then paste the 2PACX-... publish ID into DENVER17_HOURS_PUBLISH_ID below.
 *
 * IMPORTANT: a CSV export can only ever return ONE tab, so every request must
 * carry a `gid` (and `single=true`). A gid-less `pub?output=csv` request does
 * not fall back to the first sheet — Google answers with an HTML "Sorry, the
 * file you have requested does not exist" page, HTTP 200. Passing `&sheet=Name`
 * does nothing on the /d/e/.../pub endpoint; that parameter only works on
 * gviz/tq, which this project does not use. Each tab's gid is hardcoded below;
 * find one by opening the tab in Sheets and reading `#gid=` off the URL.
 *
 * ── Tab: "Schedule" ──────────────────────────────────────────────────────────
 * Columns (row 1 = header, ignored):
 *   A  Date         M/D/YYYY  — the specific date this row applies to
 *   B  Open Time    5:30 PM   — leave BLANK if closed that day
 *   C  Close Time   Close     — "Close" or a specific time like "11:00 PM"
 *   D  Special Notice         — optional; shown on the hours card that day
 *
 * Add a row any time a date deviates from the base schedule OR has a notice.
 * Dates not listed here fall back to the base weekly schedule in Tab 2.
 *
 * ── Tab: "Base Hours" ────────────────────────────────────────────────────────
 * Key/Value format (row 1 = header, ignored):
 *   open_days      Tue,Wed,Thu,Fri,Sat  (abbreviated, comma-separated)
 *   open_time      17:30                (24h format)
 *   display_line_1 Tue–Sat · 5:30PM–Close
 *   display_line_2 Open Sundays for NFL Football  (leave blank to hide)
 */

// ── Configuration ─────────────────────────────────────────────────────────────

if ( ! defined( 'DENVER17_HOURS_SHEET_ID' ) ) {
    define( 'DENVER17_HOURS_SHEET_ID', '1nzzm33T7WYOG0Z--vBg2v0VvbuXhog_RGoik2a2eFhQ' );
}

// Published document ID — the 2PACX-... string from File → Share → Publish to web.
// More reliable than the sheet ID for server-side fetches.
if ( ! defined( 'DENVER17_HOURS_PUBLISH_ID' ) ) {
    define( 'DENVER17_HOURS_PUBLISH_ID', '2PACX-1vTpsdpLGObr0ZJ7gpqZeGibCG44OlE6KLdzIDVvgQk68JFjDz6uS291xY-WyU_CwA5HAXZO298emlvX' );
}

// Per-tab gids. Read off the Sheets URL (#gid=...) when a tab is added or
// replaced. "Schedule" is the first sheet in the document, hence gid 0.
if ( ! defined( 'DENVER17_HOURS_GID_SCHEDULE' ) ) {
    define( 'DENVER17_HOURS_GID_SCHEDULE', '0' );
}
if ( ! defined( 'DENVER17_HOURS_GID_BASE' ) ) {
    define( 'DENVER17_HOURS_GID_BASE', '1261713056' );
}

if ( ! defined( 'DENVER17_HOURS_CACHE_TTL' ) ) {
    define( 'DENVER17_HOURS_CACHE_TTL', 5 * MINUTE_IN_SECONDS );
}

// How long a last-known-good copy stays servable after the feed starts failing.
// Stored in an option, not a transient, so an object-cache flush can't wipe the
// only good data we have.
if ( ! defined( 'DENVER17_HOURS_STALE_MAX_AGE' ) ) {
    define( 'DENVER17_HOURS_STALE_MAX_AGE', 14 * DAY_IN_SECONDS );
}


// ── Internal helpers ──────────────────────────────────────────────────────────

/**
 * Health flags for the current request.
 *
 * 'stale'  — at least one tab was served from the last-known-good copy.
 * 'failed' — at least one tab had no data at all, live or cached.
 *
 * @param  string|null $flag  Flag to raise, or null to read the current state.
 * @return array{stale:bool,failed:bool}
 */
function denver17_hours_health( $flag = null ) {
    static $state = [ 'stale' => false, 'failed' => false ];
    if ( null !== $flag && isset( $state[ $flag ] ) ) {
        $state[ $flag ] = true;
    }
    return $state;
}

/** Map a tab name to its gid. Accepts a raw numeric gid and passes it through. */
function denver17_hours_gid_for( $tab ) {
    if ( is_numeric( $tab ) ) {
        return (string) $tab;
    }
    $key = strtolower( trim( (string) $tab ) );
    $map = [
        'schedule'   => DENVER17_HOURS_GID_SCHEDULE,
        'base hours' => DENVER17_HOURS_GID_BASE,
        'base'       => DENVER17_HOURS_GID_BASE,
    ];
    return $map[ $key ] ?? '';
}

/**
 * Parse a CSV string into rows, header stripped.
 *
 * Google exports CRLF line endings and quotes any field containing a comma, so
 * the whole string goes through str_getcsv line-by-line via a stream rather
 * than explode("\n") — a naive split corrupts quoted fields with embedded
 * newlines and leaves a stray \r on the last column of every row.
 *
 * @param  string $csv
 * @return array[]
 */
function denver17_parse_csv( $csv ) {
    $rows   = [];
    $handle = fopen( 'php://temp', 'r+' );
    if ( ! $handle ) {
        return $rows;
    }
    fwrite( $handle, $csv );
    rewind( $handle );
    while ( false !== ( $row = fgetcsv( $handle ) ) ) {
        if ( [ null ] === $row ) {
            continue; // blank line
        }
        $rows[] = $row;
    }
    fclose( $handle );

    return count( $rows ) > 1 ? array_slice( $rows, 1 ) : [];
}

/**
 * Turn the Base Hours tab's rows into a key => value map, and verify it really
 * is the Base Hours tab.
 *
 * This guard exists because of how this feed actually broke: the URL builder
 * omitted `gid`, so BOTH tabs resolved to the first sheet in the document.
 * "Schedule" rows parsed happily as key/value pairs ("friday, june 26 " =>
 * "5:30 PM"), no error surfaced, and the missing open_days/display_line_1 keys
 * quietly fell through to hardcoded Tue–Sat defaults. Requiring at least one
 * known key means a wrong-tab fetch fails loudly instead.
 *
 * @param  array[]|false $rows
 * @return array<string,string>  Empty array if the rows aren't Base Hours.
 */
function denver17_parse_base_rows( $rows ) {
    if ( ! is_array( $rows ) ) {
        return [];
    }

    $base = [];
    foreach ( $rows as $row ) {
        $key = strtolower( preg_replace( '/^\s+|\s+$/u', '', $row[0] ?? '' ) );
        $val = preg_replace( '/^\s+|\s+$/u', '', $row[1] ?? '' );
        if ( '' !== $key ) {
            $base[ $key ] = $val;
        }
    }

    $known = [ 'open_days', 'open_time', 'display_line_1', 'display_line_2' ];
    if ( ! array_intersect( $known, array_keys( $base ) ) ) {
        error_log( sprintf(
            '[denver17 hours] Base Hours tab (gid %s) has none of the expected keys (%s) — wrong tab or changed layout. Got: %s',
            DENVER17_HOURS_GID_BASE,
            implode( ', ', $known ),
            implode( ', ', array_slice( array_keys( $base ), 0, 5 ) ) ?: '(nothing)'
        ) );
        return [];
    }

    return $base;
}

/**
 * Fetch one tab from the Google Sheet as an array of data rows (header stripped).
 *
 * Every request carries an explicit gid + single=true. On failure the last
 * known-good copy of that tab is returned (up to DENVER17_HOURS_STALE_MAX_AGE
 * old) and the 'stale' health flag is raised; if there is no cached copy the
 * 'failed' flag is raised and this returns false. Callers must not substitute
 * hardcoded hours for a false return — wrong hours are worse than no hours.
 *
 * @param  string      $tab  Tab name ("Schedule" / "Base Hours") or a raw gid.
 * @return array[]|false     Array of string arrays, or false when unavailable.
 */
function denver17_fetch_sheet_tab( $tab ) {
    $publish_id = DENVER17_HOURS_PUBLISH_ID;
    $gid        = denver17_hours_gid_for( $tab );

    if ( '' === $publish_id || '' === $gid ) {
        denver17_hours_health( 'failed' );
        error_log( sprintf(
            '[denver17 hours] No publish ID or unknown tab "%s" — cannot build feed URL.',
            is_scalar( $tab ) ? (string) $tab : gettype( $tab )
        ) );
        return false;
    }

    // gid + single=true are both required. Without gid, Google returns an HTML
    // error page with a 200 status, which is exactly how this failed silently.
    $url = 'https://docs.google.com/spreadsheets/d/e/' . $publish_id . '/pub?'
         . http_build_query( [
             'gid'    => $gid,
             'single' => 'true',
             'output' => 'csv',
         ] );

    $option_key = 'denver17_hours_lastgood_' . $gid;

    $response = wp_remote_get( $url, [
        'timeout'   => 8,
        'sslverify' => true,
    ] );

    $error = null;

    if ( is_wp_error( $response ) ) {
        $error = $response->get_error_message();
    } else {
        $code         = (int) wp_remote_retrieve_response_code( $response );
        $content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
        $body         = (string) wp_remote_retrieve_body( $response );
        $trimmed      = ltrim( $body );

        if ( 200 !== $code ) {
            $error = 'HTTP ' . $code;
        } elseif ( '' === $trimmed ) {
            $error = 'empty response body';
        } elseif ( '<' === substr( $trimmed, 0, 1 ) || false !== stripos( $content_type, 'text/html' ) ) {
            // Google's "file does not exist" page arrives as HTML with a 200.
            $error = 'HTML response, expected CSV (content-type: ' . $content_type . ')';
        }
    }

    if ( null === $error ) {
        $rows = denver17_parse_csv( wp_remote_retrieve_body( $response ) );
        update_option( $option_key, [ 'rows' => $rows, 'time' => time() ], false );
        return $rows;
    }

    error_log( sprintf( '[denver17 hours] Fetch failed for gid %s: %s (%s)', $gid, $error, $url ) );

    $last_good = get_option( $option_key );
    if ( is_array( $last_good ) && isset( $last_good['rows'], $last_good['time'] )
        && ( time() - (int) $last_good['time'] ) < DENVER17_HOURS_STALE_MAX_AGE ) {
        denver17_hours_health( 'stale' );
        return $last_good['rows'];
    }

    denver17_hours_health( 'failed' );
    return false;
}

/**
 * Normalise any human-readable time string to "HH:MM" (24h) for JS consumption.
 * Returns an empty string if the input is empty or the literal word "close".
 *
 * @param  string $str  e.g. "5:30 PM", "17:30", "close", ""
 * @return string       e.g. "17:30" | ""
 */
function denver17_normalise_time( $str ) {
    // trim() leaves the non-breaking space Google Sheets appends to cells.
    $str = preg_replace( '/^\s+|\s+$/u', '', (string) $str );
    if ( '' === $str ) {
        return '';
    }

    // Words the sheet uses for "no time here": "Close" in the close column,
    // "Closed" in the open column. Both mean empty.
    if ( in_array( strtolower( $str ), [ 'close', 'closed', 'n/a', '-' ], true ) ) {
        return '';
    }

    // Parse against WP's timezone rather than strtotime()/date(), which read the
    // server timezone (UTC on this host).
    $tz    = wp_timezone();
    $value = strtoupper( $str );

    foreach ( [ 'g:i A', 'g:iA', 'g A', 'gA', 'G:i' ] as $format ) {
        $dt     = DateTime::createFromFormat( '!' . $format, $value, $tz );
        $errors = DateTime::getLastErrors();
        // createFromFormat returns an object even with trailing junk, so treat
        // any warning (e.g. "Trailing data") as a failed match.
        $clean  = ( false === $errors )
            || ( empty( $errors['warning_count'] ) && empty( $errors['error_count'] ) );

        if ( $dt && $clean ) {
            return $dt->format( 'H:i' );
        }
    }

    return '';
}


// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Returns structured hours data for the current day.
 *
 * Data is fetched from Google Sheets and cached for DENVER17_HOURS_CACHE_TTL
 * seconds. If the sheet is unreachable it serves the last known-good copy; if
 * there isn't one, it reports 'degraded' so the card can say so. It never
 * substitutes a hardcoded schedule.
 *
 * Return shape (all values are strings):
 * [
 *   'open_time'  => '17:30' | ''          24h; empty string = closed today
 *   'close_time' => '23:00' | ''          24h; empty string = no fixed close
 *   'special'    => 'Kitchen open!'       today's notice, or empty
 *   'display_1'  => 'Mon–Sat · 5:30PM–Close'
 *   'display_2'  => ''                    second line, or empty to hide
 *   'degraded'   => '1' | ''              no live feed and no cached copy
 *   'stale'      => '1' | ''              served from the last known-good copy
 * ]
 *
 * @return array<string,string>
 */
function denver17_get_hours_data() {
    $cached = get_transient( 'denver17_hours_data' );
    if ( false !== $cached ) return $cached;

    // ── Parse "Base Hours" tab ───────────────────────────────────────────────
    $base = denver17_parse_base_rows( denver17_fetch_sheet_tab( 'Base Hours' ) );

    // Map abbreviated day names to PHP date('w') integers (0 = Sunday)
    $day_map = [
        'sun' => 0, 'mon' => 1, 'tue' => 2,
        'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
    ];

    // No hardcoded weekly fallback. If Base Hours is unavailable the card
    // degrades to a "call the lodge" state rather than inventing a schedule —
    // this feed shipped Tue–Sat defaults for weeks while the fetch was broken
    // and the lodge was actually open Mondays.
    $have_base     = ! empty( $base );
    $open_days_raw = $base['open_days'] ?? '';
    $open_days     = array_values( array_filter(
        array_map(
            function ( $d ) use ( $day_map ) {
                $abbr = strtolower( trim( substr( trim( $d ), 0, 3 ) ) );
                return $day_map[ $abbr ] ?? null;
            },
            explode( ',', $open_days_raw )
        ),
        fn( $v ) => null !== $v
    ) );

    $base_open_24   = denver17_normalise_time( $base['open_time'] ?? '' );
    $display_line_1 = $base['display_line_1'] ?? '';
    $display_line_2 = $base['display_line_2'] ?? '';

    // ── Check "Schedule" tab for a date-specific override ────────────────────
    // Use WP's configured timezone (America/Denver) for all date comparisons.
    // PHP's date() uses the server timezone (UTC on this host), which causes
    // the date to drift by up to 6 hours and match the wrong schedule row.
    $wp_tz     = wp_timezone();
    $now_local = new DateTime( 'now', $wp_tz );
    $today_ymd = $now_local->format( 'Y-m-d' );
    $today_dow = (int) $now_local->format( 'w' );

    $has_override  = false;
    $today_open    = null; // null = not overridden
    $today_close   = '';
    $today_special = '';

    $schedule_rows = denver17_fetch_sheet_tab( 'Schedule' );
    if ( $schedule_rows ) {
        foreach ( $schedule_rows as $row ) {
            $date_raw = trim( $row[0] ?? '' );
            if ( '' === $date_raw ) continue;

            // Strip day-of-week prefix if present ("Friday, June 26" -> "June 26").
            // Use /u flag so \s matches Unicode whitespace incl. non-breaking spaces
            // that Google Sheets embeds in CSV exports.
            $date_clean = preg_replace( '/^[A-Za-z]+,\s*/u', '', $date_raw );
            $date_clean = preg_replace( '/^\s+|\s+$/u', '', $date_clean );
            // Append current year if missing so strtotime is unambiguous.
            // Use the local year, not date('Y') — the server runs UTC, so on
            // Dec 31 after 5pm Denver time date('Y') is already next year.
            if ( ! preg_match( '/\d{4}/', $date_clean ) ) {
                $date_clean .= ', ' . $now_local->format( 'Y' );
            }
            // Parse as a date-only value anchored to WP's local timezone so
            // midnight-UTC offsets don't shift the date.
            $dt = DateTime::createFromFormat( 'F j, Y', $date_clean, $wp_tz );
            if ( ! $dt ) {
                $dt = date_create( $date_clean, $wp_tz );
            }
            if ( $dt && $dt->format( 'Y-m-d' ) === $today_ymd ) {
                $has_override  = true;
                $today_open    = denver17_normalise_time( $row[1] ?? '' );
                $today_close   = denver17_normalise_time( $row[2] ?? '' );
                $today_special = trim( $row[3] ?? '' );
                break;
            }
        }
    }

    // ── Resolve effective hours ───────────────────────────────────────────────
    // "Degraded" = we have no trustworthy answer for today. That happens when
    // the Base Hours tab is missing AND today has no Schedule override. In that
    // case the card must say so; it must not render a guess as fact.
    $degraded = ( ! $have_base && ! $has_override );

    if ( $has_override ) {
        $effective_open  = $today_open;   // '' = closed; 'HH:MM' = open
        $effective_close = $today_close;
    } elseif ( in_array( $today_dow, $open_days, true ) ) {
        $effective_open  = $base_open_24;
        $effective_close = '';            // no fixed close time
    } else {
        $effective_open  = '';            // closed on this day of week
        $effective_close = '';
    }

    $health = denver17_hours_health();

    $data = [
        'open_time'  => (string) $effective_open,
        'close_time' => (string) $effective_close,
        'special'    => $today_special,
        'display_1'  => $display_line_1,
        'display_2'  => $display_line_2,
        // '1' / '' so the values survive wp_localize_script's string casting.
        'degraded'   => $degraded ? '1' : '',
        'stale'      => ( $health['stale'] && ! $degraded ) ? '1' : '',
    ];

    if ( $degraded ) {
        error_log( '[denver17 hours] Serving degraded hours card — no live feed and no cached copy.' );
    }

    // Retry sooner when the answer is untrustworthy so the card recovers fast.
    set_transient(
        'denver17_hours_data',
        $data,
        $degraded ? MINUTE_IN_SECONDS : DENVER17_HOURS_CACHE_TTL
    );
    return $data;
}


// ── Per-date hours API (consumed by the events calendar day cells) ────────────
//
// denver17_get_hours_data() above only resolves TODAY (for the hours card). The
// calendar needs the effective hours for ANY date: the base weekly schedule by
// day-of-week, unless the Schedule tab has an override row for that exact date.
// The parsed sheet tabs are cached once so a month view (~35 dates) costs a
// single transient read, not 35 external fetches.

/**
 * Fetch + parse both sheet tabs once, cached. Returns:
 * [
 *   'base'     => [ key => value ]                       (Base Hours tab)
 *   'schedule' => [ 'Y-m-d' => ['open','close','special'] ]  (Schedule overrides)
 * ]
 *
 * @return array{base:array,schedule:array}
 */
function denver17_hours_tables() {
    $cached = get_transient( 'denver17_hours_tables' );
    if ( false !== $cached ) {
        return $cached;
    }

    $base = denver17_parse_base_rows( denver17_fetch_sheet_tab( 'Base Hours' ) );

    $schedule = [];
    $wp_tz    = wp_timezone();
    $rows     = denver17_fetch_sheet_tab( 'Schedule' );
    if ( $rows ) {
        foreach ( $rows as $row ) {
            $date_raw = trim( $row[0] ?? '' );
            if ( '' === $date_raw ) {
                continue;
            }
            // Same date-cleaning as denver17_get_hours_data(): strip a weekday
            // prefix, strip the trailing non-breaking space Google Sheets emits,
            // append the current year if missing.
            $clean = preg_replace( '/^[A-Za-z]+,\s*/u', '', $date_raw );
            $clean = preg_replace( '/^\s+|\s+$/u', '', $clean );
            if ( ! preg_match( '/\d{4}/', $clean ) ) {
                $clean .= ', ' . ( new DateTime( 'now', $wp_tz ) )->format( 'Y' );
            }
            $dt = DateTime::createFromFormat( 'F j, Y', $clean, $wp_tz );
            if ( ! $dt ) {
                $dt = date_create( $clean, $wp_tz );
            }
            if ( $dt ) {
                $schedule[ $dt->format( 'Y-m-d' ) ] = [
                    'open'    => denver17_normalise_time( $row[1] ?? '' ),
                    'close'   => denver17_normalise_time( $row[2] ?? '' ),
                    'special' => trim( $row[3] ?? '' ),
                ];
            }
        }
    }

    $tables = [ 'base' => $base, 'schedule' => $schedule ];

    // Don't hold a broken read for the full TTL — retry on the next minute.
    $ttl = ( empty( $base ) && empty( $schedule ) )
        ? MINUTE_IN_SECONDS
        : DENVER17_HOURS_CACHE_TTL;

    set_transient( 'denver17_hours_tables', $tables, $ttl );
    return $tables;
}

/**
 * Format a "HH:MM" (24h) value with a PHP time format, or '' if empty.
 *
 * Default includes the meridiem: without it, the Sunday football rows render as
 * "Club Open 10:30 - Close", which reads as 10:30 at night.
 */
function denver17_normalise_display_time( $hm, $format = 'g:i A' ) {
    $hm = trim( (string) $hm );
    if ( '' === $hm ) {
        return '';
    }
    $dt = DateTime::createFromFormat( 'H:i', $hm, wp_timezone() );
    return $dt ? $dt->format( $format ) : $hm;
}

/**
 * Effective hours for a single local date.
 *
 * @param string     $ymd    'Y-m-d' local date.
 * @param array|null $tables Pre-fetched tables (avoids re-reading the transient
 *                           in a loop). Null = fetch here.
 * @return array{date:string,label:string,closed:bool,open_time:string,close_time:string,special:string}
 */
function denver17_compute_hours_for_date( $ymd, $tables = null ) {
    if ( null === $tables ) {
        $tables = denver17_hours_tables();
    }
    $base     = $tables['base'];
    $schedule = $tables['schedule'];
    $wp_tz    = wp_timezone();

    $dt = DateTime::createFromFormat( 'Y-m-d', $ymd, $wp_tz );
    if ( ! $dt ) {
        return [ 'date' => $ymd, 'label' => '', 'closed' => true, 'degraded' => true, 'open_time' => '', 'close_time' => '', 'special' => '' ];
    }
    $dt->setTime( 0, 0, 0 );
    $dow = (int) $dt->format( 'w' );

    $day_map = [ 'sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6 ];
    // Same rule as denver17_get_hours_data(): no base data means no guess.
    $have_base     = ! empty( $base );
    $open_days_raw = $base['open_days'] ?? '';
    $open_days = array_values( array_filter(
        array_map(
            function ( $d ) use ( $day_map ) {
                $abbr = strtolower( trim( substr( trim( $d ), 0, 3 ) ) );
                return $day_map[ $abbr ] ?? null;
            },
            explode( ',', $open_days_raw )
        ),
        fn( $v ) => null !== $v
    ) );
    $base_open = denver17_normalise_time( $base['open_time'] ?? '' );

    if ( isset( $schedule[ $ymd ] ) ) {
        // Live-spreadsheet override wins.
        $open    = $schedule[ $ymd ]['open'];
        $close   = $schedule[ $ymd ]['close'];
        $special = $schedule[ $ymd ]['special'];
    } elseif ( in_array( $dow, $open_days, true ) ) {
        // Default weekly schedule.
        $open    = $base_open;
        $close   = '';
        $special = '';
    } else {
        $open    = '';
        $close   = '';
        $special = '';
    }

    // Unknown is not the same as closed. With no base schedule and no override
    // for this date we genuinely don't know, so say that instead of "Closed".
    $degraded = ( ! $have_base && ! isset( $schedule[ $ymd ] ) );
    $closed   = ( '' === $open );

    if ( $degraded ) {
        $label = 'Hours unavailable';
    } elseif ( $closed ) {
        $label = 'Closed';
    } else {
        $close_disp = ( '' !== $close ) ? denver17_normalise_display_time( $close ) : 'Close';
        $label = 'Club Open ' . denver17_normalise_display_time( $open ) . ' - ' . $close_disp;
    }

    return [
        'date'       => $ymd,
        'label'      => $label,
        // The calendar paints a "closed" style off this flag. Unknown must not
        // be painted as closed, so it reports false and leans on the label.
        'closed'     => ( $closed && ! $degraded ),
        'degraded'   => $degraded,
        'open_time'  => $open,
        'close_time' => $close,
        'special'    => $special,
    ];
}

/**
 * REST: GET /wp-json/denver17/v1/hours
 *   ?date=YYYY-MM-DD                 single date
 *   ?start=YYYY-MM-DD&end=YYYY-MM-DD inclusive range (what the calendar uses)
 * Always returns a map keyed by 'Y-m-d' so the client handles one shape.
 */
add_action( 'rest_api_init', function () {
    register_rest_route(
        'denver17/v1',
        '/hours',
        [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => 'denver17_rest_hours',
            'args'                => [
                'date'  => [ 'required' => false, 'type' => 'string' ],
                'start' => [ 'required' => false, 'type' => 'string' ],
                'end'   => [ 'required' => false, 'type' => 'string' ],
            ],
        ]
    );
} );

function denver17_rest_hours( $request ) {
    $tables = denver17_hours_tables();
    $wp_tz  = wp_timezone();
    $out    = [];

    $is_ymd = function ( $s ) {
        return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $s );
    };

    $date  = (string) $request->get_param( 'date' );
    $start = (string) $request->get_param( 'start' );
    $end   = (string) $request->get_param( 'end' );

    if ( $is_ymd( $date ) ) {
        $out[ $date ] = denver17_compute_hours_for_date( $date, $tables );
    } elseif ( $is_ymd( $start ) && $is_ymd( $end ) ) {
        $s = DateTime::createFromFormat( 'Y-m-d', $start, $wp_tz );
        $e = DateTime::createFromFormat( 'Y-m-d', $end, $wp_tz );
        if ( $s && $e ) {
            $s->setTime( 0, 0, 0 );
            $e->setTime( 0, 0, 0 );
            $cursor = clone $s;
            $guard  = 0;
            while ( $cursor <= $e && $guard < 400 ) {
                $d = $cursor->format( 'Y-m-d' );
                $out[ $d ] = denver17_compute_hours_for_date( $d, $tables );
                $cursor->modify( '+1 day' );
                $guard++;
            }
        }
    } else {
        $d = ( new DateTime( 'today', $wp_tz ) )->format( 'Y-m-d' );
        $out[ $d ] = denver17_compute_hours_for_date( $d, $tables );
    }

    return rest_ensure_response( $out );
}
