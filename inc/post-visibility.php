<?php
/**
 * Post Visibility — Public / Members Only / Both
 *
 * Adds a three-way visibility field to standard Posts (News feed) so editors
 * can flag content for a future members-only feed without any login system
 * existing yet. Values, stored in post meta key `_denver17_visibility`:
 *
 *   public        Visible everywhere. Default.
 *   members_only  Hidden from the public News feed, category/tag/search
 *                 archives, and the single post URL, until member login
 *                 ships. Still fully visible/editable in wp-admin.
 *   both          Visible everywhere Public is, and will also appear in the
 *                 members-only feed once that exists. Front end shows a
 *                 small "Members" badge to signal the crossover.
 *
 * No login system exists yet (see claude/working-rules.md — Member Area is
 * nav-only, not gated, by deliberate choice). This file only prepares the
 * data model and the public-side hiding; the future members feed is a
 * separate, not-yet-scheduled piece of work.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DENVER17_VISIBILITY_META_KEY', '_denver17_visibility' );


// =============================================================================
// Helpers
// =============================================================================

/**
 * A post's visibility setting, always one of the three allowed values.
 * Falls back to 'public' for posts created before this field existed.
 *
 * @param int $post_id Defaults to the current post in the loop.
 * @return string 'public' | 'members_only' | 'both'
 */
function denver17_get_post_visibility( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, DENVER17_VISIBILITY_META_KEY, true );
	$allowed = [ 'public', 'members_only', 'both' ];
	return in_array( $value, $allowed, true ) ? $value : 'public';
}

/**
 * True when a post is Members Only (not Both — Both is still public).
 *
 * @param int $post_id
 * @return bool
 */
function denver17_is_members_only( $post_id = 0 ) {
	return 'members_only' === denver17_get_post_visibility( $post_id );
}

/**
 * Human-readable labels, shared by the meta box and the admin list column.
 *
 * @return array
 */
function denver17_visibility_labels() {
	return [
		'public'       => 'Public',
		'members_only' => 'Members Only',
		'both'         => 'Both',
	];
}


// =============================================================================
// Meta box (post editor sidebar)
// =============================================================================

function denver17_add_visibility_meta_box() {
	add_meta_box(
		'denver17_visibility',
		__( 'Visibility', 'denver17' ),
		'denver17_render_visibility_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'denver17_add_visibility_meta_box' );

function denver17_render_visibility_meta_box( $post ) {
	wp_nonce_field( 'denver17_save_visibility', 'denver17_visibility_nonce' );

	$current = denver17_get_post_visibility( $post->ID );

	$options = [
		'public'       => 'Public — visible to everyone.',
		'members_only' => 'Members Only — hidden from the public News feed and archives, and the post URL is blocked, until member login exists.',
		'both'         => 'Both — visible to everyone now, and will also show in the members feed once that exists.',
	];

	echo '<p style="margin-top:0;">';
	foreach ( $options as $key => $label ) {
		printf(
			'<label style="display:block;margin-bottom:10px;line-height:1.4;"><input type="radio" name="denver17_visibility" value="%1$s" %2$s style="margin-top:2px;"> %3$s</label>',
			esc_attr( $key ),
			checked( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</p>';
}

function denver17_save_visibility_meta( $post_id ) {
	if ( ! isset( $_POST['denver17_visibility_nonce'] )
		|| ! wp_verify_nonce( wp_unslash( $_POST['denver17_visibility_nonce'] ), 'denver17_save_visibility' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['denver17_visibility'] ) ) {
		return;
	}

	$value   = sanitize_key( wp_unslash( $_POST['denver17_visibility'] ) );
	$allowed = [ 'public', 'members_only', 'both' ];
	if ( ! in_array( $value, $allowed, true ) ) {
		$value = 'public';
	}

	update_post_meta( $post_id, DENVER17_VISIBILITY_META_KEY, $value );
}
add_action( 'save_post_post', 'denver17_save_visibility_meta' );


// =============================================================================
// Admin list column — Posts → All Posts
// =============================================================================

add_filter( 'manage_post_posts_columns', function ( $columns ) {
	// Insert right after the title column so it's easy to scan.
	$new = [];
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['denver17_visibility'] = __( 'Visibility', 'denver17' );
		}
	}
	return $new;
} );

add_action( 'manage_post_posts_custom_column', function ( $column, $post_id ) {
	if ( 'denver17_visibility' !== $column ) {
		return;
	}
	$labels = denver17_visibility_labels();
	$value  = denver17_get_post_visibility( $post_id );
	echo esc_html( $labels[ $value ] ?? 'Public' );
}, 10, 2 );


// =============================================================================
// Front end — hide Members Only from public feeds, archives, and search
// =============================================================================

/**
 * Excludes Members Only posts from every public-facing post listing.
 * "Both" posts are left in (they're public too). Admin queries are untouched
 * so editors can always find every post in wp-admin regardless of visibility.
 */
function denver17_hide_members_only_from_public_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! ( is_home() || is_category() || is_tag() || is_search() || is_date() || is_author() ) ) {
		return;
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = [
		'relation' => 'OR',
		[
			'key'     => DENVER17_VISIBILITY_META_KEY,
			'compare' => 'NOT EXISTS',
		],
		[
			'key'     => DENVER17_VISIBILITY_META_KEY,
			'value'   => 'members_only',
			'compare' => '!=',
		],
	];
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'denver17_hide_members_only_from_public_queries' );

/**
 * Blocks direct access to a Members Only post's URL. No login system exists
 * to check against yet, so this is a hard block for everyone (including admins
 * viewing the front end — they can still open/edit the post from wp-admin).
 * Redirects to the home page rather than 404ing, since a 404 for a post that
 * *does* exist reads as a broken link rather than a deliberate restriction.
 */
function denver17_block_members_only_single() {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}

	if ( denver17_is_members_only( get_queried_object_id() ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'denver17_block_members_only_single' );
