<?php
/**
 * Denver Elks Lodge #17 — One-Time Site Setup Script
 *
 * Run via WP-CLI on staging after the theme is active:
 *   wp eval-file bin/setup.php
 *
 * What this does:
 *   1. Creates all sitemap pages as blank drafts with correct parent/child hierarchy
 *   2. Creates primary and footer nav menus
 *   3. Assigns pages to menus in correct order (top-level + children)
 *   4. Assigns menus to registered theme locations
 *   5. Sets Customizer defaults (colors, contact info, social URLs)
 *   6. Sets static front page and posts page in Reading Settings
 *
 * Safe to re-run — checks for existing pages/menus before creating.
 * Update the CONFIGURATION section below before running.
 */

// =============================================================================
// CONFIGURATION — fill these in before running
// =============================================================================

$config = [
    'facebook'  => 'https://www.facebook.com/DenverElksLodge17',
    'instagram' => 'https://www.instagram.com/denverelks17',
    'phone'     => '',   // e.g. '(303) 555-0100'
    'address'   => '',   // e.g. '1023 14th St, Denver, CO 80202'
];

// =============================================================================
// PAGE STRUCTURE
//
// Format: [ 'title' => string, 'slug' => string, 'children' => [...] ]
// Children inherit parent slug automatically.
// Pages created as 'draft' — publish manually when content is ready.
//
// NOTE: Update this list once the sitemap is finalized.
// =============================================================================

$page_tree = [
    [
        'title'    => 'Home',
        'slug'     => 'home',
        'children' => [],
    ],
    [
        'title'    => 'Visit',
        'slug'     => 'visit',
        'children' => [
            [ 'title' => 'When & Where',     'slug' => 'when-and-where' ],
            [ 'title' => 'The Jolly Corks Bar', 'slug' => 'the-jolly-corks-bar' ],
            [ 'title' => 'Facilities',        'slug' => 'facilities' ],
            [ 'title' => 'Facility Rentals',  'slug' => 'facility-rentals' ],
        ],
    ],
    [
        'title'    => 'Learn',
        'slug'     => 'learn',
        'children' => [
            [ 'title' => 'Our Lodge',                   'slug' => 'our-lodge' ],
            [ 'title' => 'Diversity & Inclusion Statement', 'slug' => 'diversity-and-inclusion' ],
            [ 'title' => "Who's Who",                   'slug' => 'whos-who' ],
            [ 'title' => 'FAQ',                         'slug' => 'faq' ],
            [ 'title' => 'History of the Elks',         'slug' => 'history-of-the-elks' ],
            [ 'title' => 'How to Become a Member',      'slug' => 'how-to-become-a-member' ],
            [ 'title' => 'Benefits of Membership',      'slug' => 'benefits-of-membership' ],
            [ 'title' => 'Volunteer',                   'slug' => 'volunteer' ],
        ],
    ],
    [
        'title'    => 'Community',
        'slug'     => 'community',
        'children' => [
            [ 'title' => 'Charitable Giving',    'slug' => 'charitable-giving' ],
            [ 'title' => 'CASA',                 'slug' => 'casa' ],
            [ 'title' => 'Hoop Shoot',           'slug' => 'hoop-shoot' ],
            [ 'title' => 'Soccer Shoot',         'slug' => 'soccer-shoot' ],
            [ 'title' => 'Military & Veterans',  'slug' => 'military-and-veterans' ],
            [ 'title' => 'Scholarships',         'slug' => 'scholarships' ],
            [ 'title' => 'Scouts',               'slug' => 'scouts' ],
        ],
    ],
    [
        'title'    => 'Events',
        'slug'     => 'events',
        'children' => [],
    ],
    [
        'title'    => 'Contact',
        'slug'     => 'contact',
        'children' => [],
    ],
    [
        'title'    => 'Member Area',
        'slug'     => 'member-area',
        'children' => [],
    ],
];

// Pages that go in the footer nav (slugs only — must match above)
$footer_nav_slugs = [ 'contact', 'member-area' ];

// =============================================================================
// HELPERS
// =============================================================================

/**
 * Get a page ID by slug, regardless of parent. get_page_by_path() walks a
 * page's ancestor chain and compares the reconstructed path against what
 * you pass in — a bare slug like 'facilities' only matches if that page
 * has no parent, which silently broke this check for every nested page
 * and caused duplicate pages on rerun. Returns 0 if not found.
 */
function d17_get_page_id( $slug ) {
    $posts = get_posts( [
        'post_type'   => 'page',
        'post_status' => 'any',
        'name'        => $slug,
        'numberposts' => 1,
    ] );
    return $posts ? $posts[0]->ID : 0;
}

/**
 * Create a page if it doesn't already exist. Returns the page ID.
 */
function d17_create_page( $title, $slug, $parent_id = 0 ) {
    $existing = d17_get_page_id( $slug );
    if ( $existing ) {
        WP_CLI::log( "  Exists: [{$existing}] {$slug}" );
        return $existing;
    }

    $id = wp_insert_post( [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'draft',
        'post_type'    => 'page',
        'post_parent'  => $parent_id,
        'post_content' => '',
    ] );

    if ( is_wp_error( $id ) ) {
        WP_CLI::warning( "  Failed to create page: {$slug} — " . $id->get_error_message() );
        return 0;
    }

    WP_CLI::success( "  Created: [{$id}] {$slug}" );
    return $id;
}

/**
 * Get or create a nav menu. Returns the menu term ID.
 */
function d17_get_or_create_menu( $name ) {
    $menu = wp_get_nav_menu_object( $name );
    if ( $menu ) {
        WP_CLI::log( "  Menu exists: {$name}" );
        return $menu->term_id;
    }
    $menu_id = wp_create_nav_menu( $name );
    if ( is_wp_error( $menu_id ) ) {
        WP_CLI::warning( "  Failed to create menu: {$name}" );
        return 0;
    }
    WP_CLI::success( "  Created menu: {$name}" );
    return $menu_id;
}

/**
 * Add a page to a nav menu if not already present.
 * Returns the menu item ID.
 */
function d17_add_menu_item( $menu_id, $page_id, $parent_menu_item_id = 0 ) {
    // Check if this page is already in this menu
    $existing_items = wp_get_nav_menu_items( $menu_id );
    if ( $existing_items ) {
        foreach ( $existing_items as $item ) {
            if ( (int) $item->object_id === (int) $page_id ) {
                return $item->ID;
            }
        }
    }

    return wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-object-id'   => $page_id,
        'menu-item-object'      => 'page',
        'menu-item-type'        => 'post_type',
        'menu-item-status'      => 'publish',
        'menu-item-parent-id'   => $parent_menu_item_id,
    ] );
}

// =============================================================================
// 1. CREATE PAGES
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 1: Pages ===' );

$page_ids = []; // slug => ID

foreach ( $page_tree as $page ) {
    $parent_id = d17_create_page( $page['title'], $page['slug'] );
    $page_ids[ $page['slug'] ] = $parent_id;

    foreach ( $page['children'] as $child ) {
        $child_id = d17_create_page( $child['title'], $child['slug'], $parent_id );
        $page_ids[ $child['slug'] ] = $child_id;
    }
}

// =============================================================================
// 2. CREATE NAV MENUS
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 2: Nav Menus ===' );

$primary_menu_id = d17_get_or_create_menu( 'Primary Navigation' );
$footer_menu_id  = d17_get_or_create_menu( 'Footer Navigation' );

// =============================================================================
// 3. POPULATE PRIMARY MENU
// Top-level items + their children, in sitemap order.
// Skip Home (front page) and Member Area (handled by nav-cta button, not menu).
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 3: Populate Primary Menu ===' );

$primary_top_level = [ 'visit', 'learn', 'community', 'events', 'contact' ];

foreach ( $primary_top_level as $slug ) {
    if ( empty( $page_ids[ $slug ] ) ) continue;

    $top_item_id = d17_add_menu_item( $primary_menu_id, $page_ids[ $slug ] );
    WP_CLI::log( "  Added top-level: {$slug} (menu item {$top_item_id})" );

    // Find children of this page and add them
    foreach ( $page_tree as $page ) {
        if ( $page['slug'] !== $slug ) continue;
        foreach ( $page['children'] as $child ) {
            if ( empty( $page_ids[ $child['slug'] ] ) ) continue;
            $child_item_id = d17_add_menu_item( $primary_menu_id, $page_ids[ $child['slug'] ], $top_item_id );
            WP_CLI::log( "    Added child: {$child['slug']} (menu item {$child_item_id})" );
        }
    }
}

// =============================================================================
// 4. POPULATE FOOTER MENU
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 4: Populate Footer Menu ===' );

foreach ( $footer_nav_slugs as $slug ) {
    if ( empty( $page_ids[ $slug ] ) ) continue;
    $item_id = d17_add_menu_item( $footer_menu_id, $page_ids[ $slug ] );
    WP_CLI::log( "  Added: {$slug} (menu item {$item_id})" );
}

// =============================================================================
// 5. ASSIGN MENUS TO THEME LOCATIONS
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 5: Assign Menus to Theme Locations ===' );

$locations = get_theme_mod( 'nav_menu_locations', [] );
$locations['primary'] = $primary_menu_id;
$locations['footer']  = $footer_menu_id;
set_theme_mod( 'nav_menu_locations', $locations );
WP_CLI::success( 'Menu locations assigned.' );

// =============================================================================
// 6. CUSTOMIZER DEFAULTS
// Only sets values that aren't already set (won't overwrite manual changes).
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 6: Customizer Defaults ===' );

$customizer_defaults = [
    'denver17_color_primary' => '#26215C',
    'denver17_color_accent'  => '#C59B3A',
    'denver17_facebook'      => $config['facebook'],
    'denver17_instagram'     => $config['instagram'],
    'denver17_phone'         => $config['phone'],
    'denver17_address'       => $config['address'],
];

foreach ( $customizer_defaults as $key => $value ) {
    if ( ! $value ) continue;
    $existing = get_theme_mod( $key, '__not_set__' );
    if ( $existing !== '__not_set__' && $existing !== '' ) {
        WP_CLI::log( "  Skipped (already set): {$key}" );
        continue;
    }
    set_theme_mod( $key, $value );
    WP_CLI::success( "  Set: {$key}" );
}

// =============================================================================
// 7. READING SETTINGS — static front page
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 7: Reading Settings ===' );

$front_page_id = $page_ids['home'] ?? 0;

if ( $front_page_id ) {
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $front_page_id );
    WP_CLI::success( "Front page set to: home [{$front_page_id}]" );
} else {
    WP_CLI::warning( 'Could not set front page — home page ID not found.' );
}

// =============================================================================
// DONE
// =============================================================================

WP_CLI::log( '' );
WP_CLI::success( 'Setup complete. Review drafts in WP Admin → Pages, then publish when ready.' );
WP_CLI::log( 'Next: upload the logo via Appearance → Customize → Site Identity.' );
WP_CLI::log( 'Next: set homepage images via Appearance → Customize → Homepage Images.' );
