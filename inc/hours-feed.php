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
 * Then paste the Sheet ID into DENVER17_HOURS_SHEET_ID below.
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
    define( 'DENVER17_HOURS_PUBLISH_ID', '2PACX-1vTpsdpLGObr0ZJ7gpqZeGibCG44OlE6KLdzIDVvgQk68JFjDz6uS291xY-WyU_CwA5HAXZO298emlvX' ); // TODO: paste your 2PACX-... ID here
}

if ( ! defined( 'DENVER17_HOURS_CACHE_TTL' ) ) {
    define( 'DENVER17_HOURS_CACHE_TTL', 5 * MINUTE_IN_SECONDS );
}


// ── Internal helpers ──────────────────────────────────────────────────────────

/**
 * Fetch one tab from the Google Sheet as an array of data rows (header stripped).
 *
 * @param  string      $sheet_name  Exact tab name as it appears in Google Sheets.
 * @return array[]|false            Array of string arrays, or false on failure.
 */
function denver17_fetch_sheet_tab( $sheet_name ) {
    $publish_id = DENVER17_HOURS_PUBLISH_ID;
    $sheet_id   = DENVER17_HOURS_SHEET_ID;

    if ( ! empty( $publish_id ) ) {
        // Published URL — most reliable, no sharing configuration required
        $url = 'https://docs.google.com/spreadsheets/d/e/' . $publish_id
             . '/pub?output=csv&sheet=' . rawurlencode( $sheet_name );
    } elseif ( ! empty( $sheet_id ) ) {
        // Fall back to gviz/tq with regular sheet ID (requires "Anyone with link")
        $url = 'https://docs.google.com/spreadsheets/d/' . $sheet_id . '/gviz/tq'
             . '?tqx=out:csv&sheet=' . rawurlencode( $sheet_name );
    } else {
        return false;
    }

    $response = wp_remote_get( $url, [
        'timeout'   => 8,
        'sslverify' => true,
    ] );

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return false;
    }

    $csv  = wp_remote_retrieve_body( $response );
    $rows = array_map( 'str_getcsv', array_filter( explode( "\n", trim( $csv ) ) ) );

    // Drop the header row; return empty array rather than false if sheet is empty
    return count( $rows ) > 1 ? array_slice( $rows, 1 ) : [];
}

/**
 * Normalise any human-readable time string to "HH:MM" (24h) for JS consumption.
 * Returns an empty string if the input is empty or the literal word "close".
 *
 * @param  string $str  e.g. "5:30 PM", "17:30", "close", ""
 * @return string       e.g. "17:30" | ""
 */
function denver17_normalise_time( $str ) {
    $str = trim( (string) $str );
    if ( '' === $str || 'close' === strtolower( $str ) ) return '';

    $ts = strtotime( $str );
    return $ts ? date( 'H:i', $ts ) : '';
}


// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Returns structured hours data for the current day.
 *
 * Data is fetched from Google Sheets and cached for DENVER17_HOURS_CACHE_TTL
 * seconds. Falls back to hardcoded defaults if the sheet is unreachable.
 *
 * Return shape (all values are strings):
 * [
 *   'open_time'  => '17:30' | ''          24h; empty string = closed today
 *   'close_time' => '23:00' | ''          24h; empty string = no fixed close
 *   'special'    => 'Kitchen open!'       today's notice, or empty
 *   'display_1'  => 'Tue–Sat · 5:30PM–Close'
 *   'display_2'  => ''                    second line, or empty to hide
 * ]
 *
 * @return array<string,string>
 */
function denver17_get_hours_data() {
    $cached = get_transient( 'denver17_hours_data' );
    if ( false !== $cached ) return $cached;

    // ── Parse "Base Hours" tab ───────────────────────────────────────────────
    $base      = [];
    $base_rows = denver17_fetch_sheet_tab( 'Base Hours' );

    if ( $base_rows ) {
        foreach ( $base_rows as $row ) {
            $key = strtolower( trim( $row[0] ?? '' ) );
            $val = trim( $row[1] ?? '' );
            if ( '' !== $key ) $base[ $key ] = $val;
        }
    }

    // Map abbreviated day names to PHP date('w') integers (0 = Sunday)
    $day_map = [
        'sun' => 0, 'mon' => 1, 'tue' => 2,
        'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
    ];

    $open_days_raw = $base['open_days'] ?? 'Tue,Wed,Thu,Fri,Sat';
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

    $base_open_24   = denver17_normalise_time( $base['open_time'] ?? '17:30' );
    $display_line_1 = $base['display_line_1'] ?? "Tue\u{2013}Sat \u{00B7} 5:30PM\u{2013}Close";
    $display_line_2 = $base['display_line_2'] ?? '';

    // ── Check "Schedule" tab for a date-specific override ────────────────────
    $today_ymd = current_time( 'Y-m-d' );
    $today_dow = (int) date( 'w', strtotime( $today_ymd ) );

    $has_override  = false;
    $today_open    = null; // null = not overridden
    $today_close   = '';
    $today_special = '';

    $schedule_rows = denver17_fetch_sheet_tab( 'Schedule' );
    if ( $schedule_rows ) {
        foreach ( $schedule_rows as $row ) {
            $date_raw = trim( $row[0] ?? '' );
            if ( '' === $date_raw ) continue;

            // Strip day-of-week prefix if present ("Friday, June 26" -> "June 26")
            $date_clean = trim( preg_replace( '/^[A-Za-z]+,\s*/', '', $date_raw ) );
            // Append current year if missing so strtotime is unambiguous
            if ( ! preg_match( '/\d{4}/', $date_clean ) ) {
                $date_clean .= ', ' . date( 'Y' );
            }
            $ts = strtotime( $date_clean );
            if ( $ts && date( 'Y-m-d', $ts ) === $today_ymd ) {
                $has_override  = true;
                $today_open    = denver17_normalise_time( $row[1] ?? '' );
                $today_close   = denver17_normalise_time( $row[2] ?? '' );
                $today_special = trim( $row[3] ?? '' );
                break;
            }
        }
    }

    // ── Resolve effective hours ───────────────────────────────────────────────
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

    $data = [
        'open_time'  => (string) $effective_open,
        'close_time' => (string) $effective_close,
        'special'    => $today_special,
        'display_1'  => $display_line_1,
        'display_2'  => $display_line_2,
    ];

    set_transient( 'denver17_hours_data', $data, DENVER17_HOURS_CACHE_TTL );
    return $data;
}
