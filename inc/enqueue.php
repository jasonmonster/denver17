<?php
/**
 * Enqueue Styles and Scripts
 *
 * All front-end assets for the theme.
 * Plugin assets are enqueued by their respective plugins.
 */

function denver17_enqueue_assets() {
    $version = wp_get_theme()->get( 'Version' );

    // Main stylesheet
    wp_enqueue_style(
        'denver17-style',
        get_template_directory_uri() . '/assets/css/main.css',
        [],
        $version
    );

    // Main JavaScript
    wp_enqueue_script(
        'denver17-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $version,
        true // Load in footer
    );

    // Pass hours data from Google Sheets (via hours-feed.php) to main.js.
    // JS reads window.denver17Hours to drive the hours card UI.
    // open_time / close_time are 24h strings ("17:30") or empty ("" = closed).
    wp_localize_script( 'denver17-main', 'denver17Hours', denver17_get_hours_data() );
}
add_action( 'wp_enqueue_scripts', 'denver17_enqueue_assets' );


/**
 * Remove WP block library stylesheets from the front end.
 *
 * WP 6.x auto-loads wp-block-library, wp-block-library-theme, and
 * global-styles on every page. These inject default link colors,
 * button styles, and layout utilities that override custom theme CSS.
 * Since all block styles for this theme live in main.css, none of
 * these sheets are needed on the front end.
 *
 * Priority 100 runs after WP registers them at the default priority.
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'global-styles' );
}, 100 );
