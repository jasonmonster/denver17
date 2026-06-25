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
}
add_action( 'wp_enqueue_scripts', 'denver17_enqueue_assets' );
