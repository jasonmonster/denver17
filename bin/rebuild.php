<?php
/**
 * Denver Elks Lodge #17 — Site Rebuild Script
 *
 * DESTRUCTIVE: deletes all pages except Home and all nav menus,
 * then rebuilds everything from scratch with the complete menu structure,
 * including structural Custom Link items (mega-label, mega-col-break, etc.)
 * that the nav walker uses to render grouped mega menus.
 *
 * Run via WP-CLI:
 *   wp eval-file bin/rebuild.php
 *
 * Unlike setup.php, this script does NOT skip existing items — it always
 * wipes and rebuilds, giving you a guaranteed clean state.
 *
 * Update the CONFIG section below before running if URLs have changed.
 */

// =============================================================================
// CONFIG
// =============================================================================

$config = [
    'facebook'  => 'https://www.facebook.com/DenverElksLodge17',
    'instagram' => 'https://www.instagram.com/denverelks17',
    'phone'     => '',
    'address'   => '',
];

// =============================================================================
// STEP 1 — Preserve the Home page
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 1: Locate Home Page ===' );

// Try the Reading Settings option first; fall back to slug lookup
$home_id = (int) get_option( 'page_on_front' );

if ( ! $home_id ) {
    $home_page = get_page_by_path( 'home' );
    $home_id   = $home_page ? (int) $home_page->ID : 0;
}

if ( $home_id ) {
    WP_CLI::success( "Keeping home page: [{$home_id}] \"" . get_the_title( $home_id ) . '"' );
} else {
    WP_CLI::log( '  No existing home page found — will create one.' );
}

// =============================================================================
// STEP 2 — Delete all non-Home pages
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 2: Delete Non-Home Pages ===' );

$all_pages = get_posts( [
    'post_type'      => 'page',
    'post_status'    => [ 'publish', 'draft', 'private', 'pending', 'trash', 'future' ],
    'posts_per_page' => -1,
    'fields'         => 'ids',
] );

$deleted = 0;
foreach ( $all_pages as $pid ) {
    if ( $home_id && (int) $pid === $home_id ) {
        continue;
    }
    wp_delete_post( (int) $pid, true ); // true = force delete (bypass trash)
    WP_CLI::log( "  Deleted: [{$pid}]" );
    $deleted++;
}

WP_CLI::success( "Deleted {$deleted} pages." );

// =============================================================================
// STEP 3 — Delete all nav menus
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 3: Delete Nav Menus ===' );

$existing_menus = wp_get_nav_menus();
foreach ( $existing_menus as $menu ) {
    wp_delete_nav_menu( $menu->term_id );
    WP_CLI::log( "  Deleted menu: \"{$menu->name}\" [{$menu->term_id}]" );
}

WP_CLI::success( 'All nav menus cleared.' );

// =============================================================================
// STEP 4 — Create pages
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 4: Create Pages ===' );

/**
 * Create a page and return its ID.
 * If slug === 'home' and a home page already exists, returns its ID unchanged.
 */
function d17_rb_create_page( $title, $slug, $parent_id = 0 ) {
    global $home_id;

    if ( $slug === 'home' && $home_id ) {
        WP_CLI::log( "  Keeping: home [{$home_id}]" );
        return $home_id;
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
        WP_CLI::warning( "  Failed to create: {$slug} — " . $id->get_error_message() );
        return 0;
    }

    WP_CLI::success( "  Created: [{$id}] {$slug}" );
    return $id;
}

// Page tree — matches sitemap exactly
$page_tree = [
    [ 'title' => 'Home',        'slug' => 'home',        'children' => [] ],
    [ 'title' => 'Visit',       'slug' => 'visit',       'children' => [
        [ 'title' => 'When & Where',         'slug' => 'when-and-where' ],
        [ 'title' => 'The Jolly Corks Bar',  'slug' => 'the-jolly-corks-bar' ],
        [ 'title' => 'Facilities',           'slug' => 'facilities' ],
        [ 'title' => 'Facility Rentals',     'slug' => 'facility-rentals' ],
    ]],
    [ 'title' => 'Learn',       'slug' => 'learn',       'children' => [
        [ 'title' => 'Our Lodge',                       'slug' => 'our-lodge' ],
        [ 'title' => 'Diversity & Inclusion Statement', 'slug' => 'diversity-and-inclusion' ],
        [ 'title' => "Who's Who",                       'slug' => 'whos-who' ],
        [ 'title' => 'FAQ',                             'slug' => 'faq' ],
        [ 'title' => 'History of the Elks',             'slug' => 'history-of-the-elks' ],
        [ 'title' => 'How to Become a Member',          'slug' => 'how-to-become-a-member' ],
        [ 'title' => 'Benefits of Membership',          'slug' => 'benefits-of-membership' ],
        [ 'title' => 'Volunteer',                       'slug' => 'volunteer' ],
    ]],
    [ 'title' => 'Community',   'slug' => 'community',   'children' => [
        [ 'title' => 'Charitable Giving',   'slug' => 'charitable-giving' ],
        [ 'title' => 'CASA',                'slug' => 'casa' ],
        [ 'title' => 'Hoop Shoot',          'slug' => 'hoop-shoot' ],
        [ 'title' => 'Soccer Shoot',        'slug' => 'soccer-shoot' ],
        [ 'title' => 'Military & Veterans', 'slug' => 'military-and-veterans' ],
        [ 'title' => 'Scholarships',        'slug' => 'scholarships' ],
        [ 'title' => 'Scouts',              'slug' => 'scouts' ],
    ]],
    [ 'title' => 'Events',      'slug' => 'events',      'children' => [] ],
    [ 'title' => 'Contact',     'slug' => 'contact',     'children' => [] ],
    [ 'title' => 'Member Area', 'slug' => 'member-area', 'children' => [] ],
];

$ids = []; // slug => page ID

foreach ( $page_tree as $page ) {
    $pid               = d17_rb_create_page( $page['title'], $page['slug'] );
    $ids[ $page['slug'] ] = $pid;
    foreach ( $page['children'] as $child ) {
        $cid                    = d17_rb_create_page( $child['title'], $child['slug'], $pid );
        $ids[ $child['slug'] ] = $cid;
    }
}

// =============================================================================
// STEP 5 — Create menus
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 5: Create Nav Menus ===' );

$primary_id = wp_create_nav_menu( 'Primary Navigation' );
$footer_id  = wp_create_nav_menu( 'Footer Navigation' );

if ( is_wp_error( $primary_id ) || is_wp_error( $footer_id ) ) {
    WP_CLI::error( 'Failed to create one or both nav menus. Aborting.' );
    return;
}

WP_CLI::success( "Primary Navigation created: [{$primary_id}]" );
WP_CLI::success( "Footer Navigation created:  [{$footer_id}]" );

// =============================================================================
// HELPERS
//
// NOTE: wp_update_nav_menu_item() passes menu-item-classes directly into
// explode(), so it must be a space-separated string — NOT a PHP array.
// =============================================================================

/**
 * Add a page-type menu item.
 *
 * @param int    $menu_id        Menu term ID
 * @param int    $page_id        Page post ID
 * @param int    $parent_item_id Parent menu item ID (0 = top-level)
 * @param array  $classes        CSS classes for the walker (e.g. ['mega-col-break'])
 * @return int   New menu item ID
 */
function d17_rb_menu_page( $menu_id, $page_id, $parent_item_id = 0, $classes = [] ) {
    return wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-object-id' => $page_id,
        'menu-item-object'    => 'page',
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parent_item_id,
        'menu-item-classes'   => implode( ' ', $classes ), // must be a string
    ] );
}

/**
 * Add a custom-link menu item (structural — for mega-label / col / group breaks).
 *
 * @param int    $menu_id        Menu term ID
 * @param string $title          Display title (e.g. 'About 17', 'Membership')
 * @param int    $parent_item_id Parent menu item ID
 * @param array  $classes        Walker CSS classes
 * @return int   New menu item ID
 */
function d17_rb_menu_custom( $menu_id, $title, $parent_item_id = 0, $classes = [] ) {
    return wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-title'     => $title,
        'menu-item-url'       => '#',
        'menu-item-type'      => 'custom',
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parent_item_id,
        'menu-item-classes'   => implode( ' ', $classes ), // must be a string
    ] );
}

// =============================================================================
// STEP 6 — Populate Primary Menu
//
// The Denver17_Nav_Walker reads three CSS classes to control mega menu layout:
//
//   mega-label      — Renders as a section header (mega-group-label), not a link.
//                     Opens a new group in the current column.
//
//   mega-col-break  — Closes the current group/column and opens a new one.
//                     If combined with mega-label, title becomes the col header.
//                     Without mega-label, item renders as a normal link.
//
//   mega-group-break — Closes the current group and opens a new one in the same
//                      column. The item itself renders as a normal link.
//
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 6: Populate Primary Menu ===' );

// ---- Visit ----
// Single column, no section headers.
//
// [ When & Where ]
// [ The Jolly Corks Bar ]
// [ Facilities ]
// [ Facility Rentals ]
//
$visit = d17_rb_menu_page( $primary_id, $ids['visit'] );
d17_rb_menu_page( $primary_id, $ids['when-and-where'],      $visit );
d17_rb_menu_page( $primary_id, $ids['the-jolly-corks-bar'], $visit );
d17_rb_menu_page( $primary_id, $ids['facilities'],          $visit );
d17_rb_menu_page( $primary_id, $ids['facility-rentals'],    $visit );
WP_CLI::success( "  Visit + 4 children [{$visit}]" );

// ---- Learn ----
// Two columns with section headers and group breaks.
//
// Col 1: ABOUT 17
//   [ Our Lodge ]
//   [ Diversity & Inclusion Statement ]
//   [ Who's Who ]
//   [ FAQ ]
//   --- new group ---
//   [ History of the Elks ]
//
// Col 2: MEMBERSHIP
//   [ How to Become a Member ]
//   [ Benefits of Membership ]
//   --- new group ---
//   [ Volunteer ]
//
$learn = d17_rb_menu_page( $primary_id, $ids['learn'] );
// Col 1
d17_rb_menu_custom( $primary_id, 'About 17',                                $learn, [ 'mega-label' ] );
d17_rb_menu_page(   $primary_id, $ids['our-lodge'],                          $learn );
d17_rb_menu_page(   $primary_id, $ids['diversity-and-inclusion'],           $learn );
d17_rb_menu_page(   $primary_id, $ids['whos-who'],                          $learn );
d17_rb_menu_page(   $primary_id, $ids['faq'],                               $learn );
d17_rb_menu_page(   $primary_id, $ids['history-of-the-elks'],               $learn, [ 'mega-group-break' ] );
// Col 2
d17_rb_menu_custom( $primary_id, 'Membership',                              $learn, [ 'mega-col-break', 'mega-label' ] );
d17_rb_menu_page(   $primary_id, $ids['how-to-become-a-member'],            $learn );
d17_rb_menu_page(   $primary_id, $ids['benefits-of-membership'],            $learn );
d17_rb_menu_page(   $primary_id, $ids['volunteer'],                         $learn, [ 'mega-group-break' ] );
WP_CLI::success( "  Learn + 10 children [{$learn}]" );

// ---- Community ----
// Two columns, no section headers.
//
// Col 1:
//   [ Charitable Giving ]
//   [ CASA ]
//   [ Hoop Shoot ]
//   [ Soccer Shoot ]
//
// Col 2:
//   [ Military & Veterans ]     <- mega-col-break starts new col
//   [ Scholarships ]
//   [ Scouts ]
//
$comm = d17_rb_menu_page( $primary_id, $ids['community'] );
d17_rb_menu_page( $primary_id, $ids['charitable-giving'],              $comm );
d17_rb_menu_page( $primary_id, $ids['casa'],                           $comm );
d17_rb_menu_page( $primary_id, $ids['hoop-shoot'],                    $comm );
d17_rb_menu_page( $primary_id, $ids['soccer-shoot'],                  $comm );
d17_rb_menu_page( $primary_id, $ids['military-and-veterans'],         $comm, [ 'mega-col-break' ] );
d17_rb_menu_page( $primary_id, $ids['scholarships'],                  $comm );
d17_rb_menu_page( $primary_id, $ids['scouts'],                        $comm );
WP_CLI::success( "  Community + 7 children [{$comm}]" );

// ---- Events & Contact — leaf items (no mega menu) ----
d17_rb_menu_page( $primary_id, $ids['events'] );
d17_rb_menu_page( $primary_id, $ids['contact'] );
WP_CLI::success( '  Events + Contact added.' );

// =============================================================================
// STEP 7 — Populate Footer Menu
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 7: Populate Footer Menu ===' );

d17_rb_menu_page( $footer_id, $ids['contact'] );
d17_rb_menu_page( $footer_id, $ids['member-area'] );
WP_CLI::success( 'Footer menu: Contact + Member Area.' );

// =============================================================================
// STEP 8 — Assign menus to theme locations
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 8: Assign Menu Locations ===' );

set_theme_mod( 'nav_menu_locations', [
    'primary' => $primary_id,
    'footer'  => $footer_id,
] );
WP_CLI::success( 'Locations assigned: primary + footer.' );

// =============================================================================
// STEP 9 — Customizer defaults (always sets, doesn't skip)
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 9: Customizer Defaults ===' );

$customizer = [
    'denver17_color_primary' => '#26215C',
    'denver17_color_accent'  => '#C59B3A',
    'denver17_facebook'      => $config['facebook'],
    'denver17_instagram'     => $config['instagram'],
    'denver17_phone'         => $config['phone'],
    'denver17_address'       => $config['address'],
];

foreach ( $customizer as $key => $value ) {
    if ( ! $value ) {
        continue;
    }
    set_theme_mod( $key, $value );
    WP_CLI::success( "  {$key}" );
}

// =============================================================================
// STEP 10 — Static front page
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Step 10: Front Page ===' );

$front_id = $ids['home'] ?? $home_id;

if ( $front_id ) {
    // Make sure the home page is published
    wp_update_post( [ 'ID' => $front_id, 'post_status' => 'publish' ] );
    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $front_id );
    WP_CLI::success( "Front page → home [{$front_id}]" );
} else {
    WP_CLI::warning( 'Could not find home page — set Reading Settings manually.' );
}

// =============================================================================
// DONE
// =============================================================================

WP_CLI::log( '' );
WP_CLI::success( '=== Rebuild complete ===' );
WP_CLI::log( 'All inner pages created as drafts. Publish when content is ready.' );
WP_CLI::log( 'Verify menu structure: Appearance → Menus.' );
