<?php
/**
 * Block Registration
 *
 * Registers all Denver17 custom blocks as dynamic blocks (PHP render callbacks).
 * No build step — editor UI is registered in assets/js/blocks-editor.js via
 * wp.blocks.registerBlockType using global wp.* dependencies.
 *
 * Each block:
 *   - block.json      defines name, attributes, and metadata
 *   - render.php      PHP render callback (calls the relevant template part)
 *   - blocks-editor.js  client-side edit component (sidebar inspector UI)
 */

// Load render callbacks
require_once get_template_directory() . '/blocks/hero/render.php';
require_once get_template_directory() . '/blocks/feature-split/render.php';
require_once get_template_directory() . '/blocks/membership-steps/render.php';
require_once get_template_directory() . '/blocks/events-band/render.php';
require_once get_template_directory() . '/blocks/cta-band/render.php';
require_once get_template_directory() . '/blocks/hours-display/render.php';
require_once get_template_directory() . '/blocks/beer-list/render.php';


/**
 * Register blocks on init.
 */
function denver17_register_blocks() {
    $blocks = [
        'hero'             => 'denver17_render_block_hero',
        'feature-split'    => 'denver17_render_block_feature_split',
        'membership-steps' => 'denver17_render_block_membership_steps',
        'events-band'      => 'denver17_render_block_events_band',
        'cta-band'         => 'denver17_render_block_cta_band',
        'hours-display'    => 'denver17_render_block_hours_display',
        'beer-list'        => 'denver17_render_block_beer_list',
    ];

    foreach ( $blocks as $block => $callback ) {
        register_block_type(
            get_template_directory() . '/blocks/' . $block . '/block.json',
            [ 'render_callback' => $callback ]
        );
    }
}
add_action( 'init', 'denver17_register_blocks' );


/**
 * Add a custom block category so all Denver17 blocks are grouped together
 * at the top of the block inserter.
 */
add_filter( 'block_categories_all', function ( $categories ) {
    return array_merge(
        [
            [
                'slug'  => 'denver17',
                'title' => 'Denver Elks #17',
                'icon'  => null,
            ],
        ],
        $categories
    );
} );


/**
 * Enqueue the block editor script.
 * Loads blocks-editor.js only in the block editor context.
 * Dependencies cover everything used via wp.* globals in that file.
 */
function denver17_enqueue_block_editor_assets() {
    wp_enqueue_script(
        'denver17-blocks-editor',
        get_template_directory_uri() . '/assets/js/blocks-editor.js',
        [
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-i18n',
        ],
        wp_get_theme()->get( 'Version' ),
        false // must be in <head> for block editor registration
    );
}
add_action( 'enqueue_block_editor_assets', 'denver17_enqueue_block_editor_assets' );
