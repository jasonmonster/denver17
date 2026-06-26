<?php
/**
 * Rebuild Primary Navigation Menu
 *
 * Deletes and recreates the Primary Navigation menu with the correct
 * column/group structure for the desktop mega menu walker.
 *
 * Run after setup.php (pages must exist first):
 *   wp eval-file bin/rebuild-menus.php
 *
 * Safe to re-run — always deletes and rebuilds from scratch.
 *
 * CSS class conventions used by Denver17_Nav_Walker:
 *   mega-label       — Custom Link (URL: #). Renders as a group header, not a link.
 *   mega-col-break   — Opens a new column. Combine with mega-label for a labeled
 *                      column header, or use alone on a real page link to just
 *                      start a new column.
 *   mega-group-break — Opens a new group in the current column. Item renders as
 *                      a normal link.
 */

// =============================================================================
// HELPERS
// =============================================================================

function d17_page_id( $slug ) {
    $page = get_page_by_path( $slug );
    return $page ? $page->ID : 0;
}

/**
 * Add a real page link to a menu.
 */
function d17_menu_page( $menu_id, $page_id, $classes = '', $parent = 0 ) {
    if ( ! $page_id ) {
        WP_CLI::warning( "  Skipping: page ID {$page_id} not found" );
        return 0;
    }
    $item_id = wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-object-id'   => $page_id,
        'menu-item-object'      => 'page',
        'menu-item-type'        => 'post_type',
        'menu-item-status'      => 'publish',
        'menu-item-classes'     => $classes,
        'menu-item-parent-id'   => $parent,
    ] );
    WP_CLI::log( "  + page [{$page_id}] parent={$parent} classes='{$classes}'" );
    return $item_id;
}

/**
 * Add a Custom Link (structural marker — label, col break header, etc.)
 */
function d17_menu_custom( $menu_id, $title, $classes = '', $parent = 0 ) {
    $item_id = wp_update_nav_menu_item( $menu_id, 0, [
        'menu-item-title'       => $title,
        'menu-item-url'         => '#',
        'menu-item-type'        => 'custom',
        'menu-item-status'      => 'publish',
        'menu-item-classes'     => $classes,
        'menu-item-parent-id'   => $parent,
    ] );
    WP_CLI::log( "  + custom '{$title}' classes='{$classes}'" );
    return $item_id;
}

// =============================================================================
// REBUILD PRIMARY MENU
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Rebuilding Primary Navigation ===' );

// Delete existing and start fresh
$existing = wp_get_nav_menu_object( 'Primary Navigation' );
if ( $existing ) {
    wp_delete_nav_menu( $existing->term_id );
    WP_CLI::log( 'Deleted existing Primary Navigation menu.' );
}

$menu_id = wp_create_nav_menu( 'Primary Navigation' );
if ( is_wp_error( $menu_id ) ) {
    WP_CLI::error( 'Could not create menu: ' . $menu_id->get_error_message() );
    return;
}
WP_CLI::success( "Created Primary Navigation (ID: {$menu_id})" );

// ----- Visit -----
WP_CLI::log( '' );
WP_CLI::log( 'Visit:' );
$visit = d17_menu_page( $menu_id, d17_page_id( 'visit' ) );
    d17_menu_page( $menu_id, d17_page_id( 'when-and-where' ),       '', $visit );
    d17_menu_page( $menu_id, d17_page_id( 'the-jolly-corks-bar' ),  '', $visit );
    d17_menu_page( $menu_id, d17_page_id( 'facilities' ),           '', $visit );
    d17_menu_page( $menu_id, d17_page_id( 'facility-rentals' ),     '', $visit );

// ----- Learn -----
// Col 1: [label] About 17 → Our Lodge, D&I, Who's Who, FAQ | [group break] History of the Elks
// Col 2: [col break + label] Membership → How to..., Benefits | [group break] Volunteer
WP_CLI::log( '' );
WP_CLI::log( 'Learn:' );
$learn = d17_menu_page( $menu_id, d17_page_id( 'learn' ) );
    d17_menu_custom( $menu_id, 'About 17',                                  'mega-label',            $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'our-lodge' ),                  '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'diversity-and-inclusion' ),    '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'whos-who' ),                   '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'faq' ),                        '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'history-of-the-elks' ),        'mega-group-break',      $learn );
    d17_menu_custom( $menu_id, 'Membership',                                 'mega-col-break mega-label', $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'how-to-become-a-member' ),     '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'benefits-of-membership' ),     '',                      $learn );
    d17_menu_page(   $menu_id, d17_page_id( 'volunteer' ),                  'mega-group-break',      $learn );

// ----- Community -----
// Col 1: Charitable Giving, CASA, Hoop Shoot, Soccer Shoot
// Col 2: [col break] Military & Veterans, Scholarships, Scouts
WP_CLI::log( '' );
WP_CLI::log( 'Community:' );
$community = d17_menu_page( $menu_id, d17_page_id( 'community' ) );
    d17_menu_page( $menu_id, d17_page_id( 'charitable-giving' ),    '',               $community );
    d17_menu_page( $menu_id, d17_page_id( 'casa' ),                 '',               $community );
    d17_menu_page( $menu_id, d17_page_id( 'hoop-shoot' ),           '',               $community );
    d17_menu_page( $menu_id, d17_page_id( 'soccer-shoot' ),         '',               $community );
    d17_menu_page( $menu_id, d17_page_id( 'military-and-veterans' ),'mega-col-break', $community );
    d17_menu_page( $menu_id, d17_page_id( 'scholarships' ),         '',               $community );
    d17_menu_page( $menu_id, d17_page_id( 'scouts' ),               '',               $community );

// ----- Events & Contact (top-level, no children) -----
WP_CLI::log( '' );
WP_CLI::log( 'Top-level:' );
d17_menu_page( $menu_id, d17_page_id( 'events' ) );
d17_menu_page( $menu_id, d17_page_id( 'contact' ) );

// =============================================================================
// REBUILD FOOTER MENU
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Rebuilding Footer Navigation ===' );

$existing_footer = wp_get_nav_menu_object( 'Footer Navigation' );
if ( $existing_footer ) {
    wp_delete_nav_menu( $existing_footer->term_id );
}

$footer_menu_id = wp_create_nav_menu( 'Footer Navigation' );
d17_menu_page( $footer_menu_id, d17_page_id( 'contact' ) );
d17_menu_page( $footer_menu_id, d17_page_id( 'member-area' ) );
WP_CLI::success( "Footer Navigation rebuilt." );

// =============================================================================
// RE-ASSIGN THEME LOCATIONS
// =============================================================================

$locations             = get_theme_mod( 'nav_menu_locations', [] );
$locations['primary']  = $menu_id;
$locations['footer']   = $footer_menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

WP_CLI::log( '' );
WP_CLI::success( 'Done. Both menus rebuilt and assigned to theme locations.' );
