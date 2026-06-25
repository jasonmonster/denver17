<?php
/**
 * Theme Setup
 *
 * Core WordPress theme support and navigation registration.
 * No plugin functionality here (events, members, payments).
 */

function denver17_setup() {
    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Featured images
    add_theme_support( 'post-thumbnails' );

    // HTML5 markup for core elements
    add_theme_support( 'html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ] );

    // Block editor alignment options
    add_theme_support( 'align-wide' );

    // Editor styles so the block editor matches the front end
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // Custom logo support (used in header)
    add_theme_support( 'custom-logo', [
        'height'      => 100,
        'width'       => 100,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    // Navigation menus
    register_nav_menus( [
        'primary' => __( 'Primary Navigation', 'denver17' ),
        'footer'  => __( 'Footer Navigation', 'denver17' ),
    ] );
}
add_action( 'after_setup_theme', 'denver17_setup' );
