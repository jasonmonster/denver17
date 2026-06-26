<?php
/**
 * Beer Feed
 *
 * Fetches the tap list from a published Google Spreadsheet and caches it
 * with WP transients. Same pattern as inc/hours-feed.php.
 *
 * ── Spreadsheet setup ────────────────────────────────────────────────────────
 * Publish the sheet: File → Share → Publish to web → Entire document → CSV
 *
 * ── Tab: "Beers" (name configurable via DENVER17_BEER_SHEET_NAME) ────────────
 * Columns (row 1 = header, ignored):
 *   A  ID       (numeric, ignored)
 *   B  Name     Dry Dock - Apricot Blonde
 *   C  Style    Fruited Ale
 *   D  ABV      5.0
 *   E  Status   On Tap | Coming Soon | Not In Stock
 *   F  Order    (integer; controls display sort order within each section)
 */

// ── Configuration ─────────────────────────────────────────────────────────────

if ( ! defined( 'DENVER17_BEER_SHEET_ID' ) ) {
    define( 'DENVER17_BEER_SHEET_ID', '12nAaigkObox5quG1pFPFCI1Y3IMBYP2qvwBQf0MiPFc' ); // Regular sheet ID from the URL
}

// The 2PACX-... string from File → Share → Publish to web (preferred).
if ( ! defined( 'DENVER17_BEER_PUBLISH_ID' ) ) {
    define( 'DENVER17_BEER_PUBLISH_ID', '2PACX-1vQEbb-eBkkatv-uDxUE8i1UNQp5LJROdi5siffD697HQdTLZtVdgmGJxJYteDVI08Le9WqAEYos2nPu' ); // TODO: paste your 2PACX-... ID here
}

// Tab name exactly as it appears in Google Sheets.
if ( ! defined( 'DENVER17_BEER_SHEET_NAME' ) ) {
    define( 'DENVER17_BEER_SHEET_NAME', 'Beers' );
}

if ( ! defined( 'DENVER17_BEER_CACHE_TTL' ) ) {
    define( 'DENVER17_BEER_CACHE_TTL', 5 * MINUTE_IN_SECONDS );
}


// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Returns the current tap list as two arrays.
 *
 * Return shape:
 * [
 *   'on_tap'      => [ ['name'=>'...', 'style'=>'...', 'abv'=>'...'], ... ],
 *   'coming_soon' => [ ['name'=>'...', 'style'=>'...', 'abv'=>'...'], ... ],
 * ]
 *
 * Both arrays are empty if the sheet is unreachable and no cache exists.
 *
 * @return array{on_tap: array, coming_soon: array}
 */
function denver17_get_beer_data() {
    $cached = get_transient( 'denver17_beer_data' );
    if ( false !== $cached ) return $cached;

    $publish_id = DENVER17_BEER_PUBLISH_ID;
    $sheet_id   = DENVER17_BEER_SHEET_ID;
    $sheet_name = DENVER17_BEER_SHEET_NAME;

    if ( ! empty( $publish_id ) ) {
        $url = 'https://docs.google.com/spreadsheets/d/e/' . $publish_id
             . '/pub?output=csv&sheet=' . rawurlencode( $sheet_name );
    } elseif ( ! empty( $sheet_id ) ) {
        $url = 'https://docs.google.com/spreadsheets/d/' . $sheet_id . '/gviz/tq'
             . '?tqx=out:csv&sheet=' . rawurlencode( $sheet_name );
    } else {
        return [ 'on_tap' => [], 'coming_soon' => [] ];
    }

    $response = wp_remote_get( $url, [ 'timeout' => 8, 'sslverify' => true ] );

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return [ 'on_tap' => [], 'coming_soon' => [] ];
    }

    $csv  = wp_remote_retrieve_body( $response );
    $rows = array_map( 'str_getcsv', array_filter( explode( "\n", trim( $csv ) ) ) );
    $rows = array_slice( $rows, 1 ); // drop header

    $on_tap      = [];
    $coming_soon = [];

    foreach ( $rows as $row ) {
        $name   = trim( $row[1] ?? '' ); // B: Name
        $style  = trim( $row[2] ?? '' ); // C: Style
        $abv    = trim( $row[3] ?? '' ); // D: ABV
        $status = trim( $row[4] ?? '' ); // E: Status
        $order  = trim( $row[5] ?? '' ); // F: Order (display sort)

        if ( '' === $name ) continue;

        $beer = [ 'name' => $name, 'style' => $style, 'abv' => $abv, 'order' => (int) $order ];

        if ( 'On Tap' === $status ) {
            $on_tap[] = $beer;
        } elseif ( 'Coming Soon' === $status ) {
            $coming_soon[] = $beer;
        }
        // 'Not In Stock' and anything else: silently ignored
    }

    // Sort by Order column (0 = no order set, sorts last)
    $sort = function ( $a, $b ) {
        $ao = $a['order'] ?: PHP_INT_MAX;
        $bo = $b['order'] ?: PHP_INT_MAX;
        return $ao <=> $bo;
    };
    usort( $on_tap, $sort );
    usort( $coming_soon, $sort );

    $data = compact( 'on_tap', 'coming_soon' );
    set_transient( 'denver17_beer_data', $data, DENVER17_BEER_CACHE_TTL );
    return $data;
}
