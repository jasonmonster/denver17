<?php
/**
 * Disable Comments — permanently, site-wide
 *
 * No template in this theme calls comments_template(), comment_form(), or
 * wp_list_comments() — comments were already invisible on the front end.
 * This file makes that a deliberate, enforced decision rather than an
 * accident of what nobody got around to building: comments are closed at
 * the query level (not just hidden by omission), the admin UI for managing
 * them is removed, and the underlying HTML5 theme support for comment
 * markup is dropped in theme-setup.php since nothing will ever render it.
 *
 * If comments are ever wanted in the future, this whole file is the one
 * place to delete/revert — nothing else in the theme depends on comments
 * being off.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force comments and pings closed everywhere, regardless of each post's own
// "Discussion" setting in wp-admin — editors can't accidentally turn this on
// per-post.
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );

// Hide the comments feed link from wp_head().
add_filter( 'feed_links_show_comments_feed', '__return_false' );

// Never enqueue the core comment-reply script (it only loads when comments
// are open on a singular request, so this is belt-and-suspenders on top of
// the filter above).
add_action( 'wp_enqueue_scripts', function () {
	wp_deregister_script( 'comment-reply' );
} );

// Remove the Comments menu from wp-admin — there's nothing to moderate.
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
} );

// Remove the comment bubble from the admin bar.
add_action( 'wp_before_admin_bar_render', function () {
	global $wp_admin_bar;
	if ( $wp_admin_bar ) {
		$wp_admin_bar->remove_menu( 'comments' );
	}
} );

// Redirect anyone who navigates straight to wp-admin/edit-comments.php.
add_action( 'admin_init', function () {
	global $pagenow;
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
} );
