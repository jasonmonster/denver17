<?php
/**
 * Post Visibility — Public / Members Only / Both
 *
 * Adds a three-way visibility field to standard Posts (News feed). Values,
 * stored in post meta key `_denver17_visibility`:
 *
 *   public        Visible everywhere. Default.
 *   members_only  Signed-in members only. Members see it in the News list
 *                 and archives with a "Members" tag and can open it. Everyone
 *                 else: absent from the list, archives, search, feeds, the
 *                 REST API and the sitemap; the URL sends them to sign in.
 *   both          Visible to everyone, tagged "Members".
 *
 * "Is this viewer a member?" is a question the theme asks and the members
 * plugin answers (denver17_viewer_is_member filter), so the theme still works
 * with that plugin off: no answer means nobody but editors counts as a member.
 * Any signed-in member counts, lapsed or not — news isn't a paid benefit
 * (staff guide fix plan, issue 3, 2026-10-01).
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
 * Can the current viewer read Members Only posts? Anyone who can edit posts
 * (administrators, Communications staff), or a signed-in member as answered
 * by the members plugin.
 *
 * @return bool
 */
function denver17_viewer_is_member() {
	// Not cached: a REST request can change the current user after the first
	// call (cookie auth without a nonce drops to logged-out).
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( current_user_can( 'edit_posts' ) ) {
		return true;
	}
	return (bool) apply_filters( 'denver17_viewer_is_member', false );
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
		'members_only' => 'Members Only — only signed-in members can see or open it. Hidden from the public, search engines and feeds.',
		'both'         => 'Both — visible to everyone, tagged "Members".',
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

/** The meta_query clause that leaves Members Only posts out. */
function denver17_members_only_exclusion() {
	return [
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
}

/**
 * Leaves Members Only posts out of front-end listings for non-members, and
 * out of feeds for everyone (feed readers never carry a member's session).
 * Admin queries are untouched so editors always find every post in wp-admin.
 */
function denver17_hide_members_only_from_public_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$is_listing = $query->is_home() || $query->is_category() || $query->is_tag() || $query->is_search()
		|| $query->is_date() || $query->is_author();

	if ( ! $query->is_feed() && ! ( $is_listing && ! denver17_viewer_is_member() ) ) {
		return;
	}

	$meta_query   = (array) $query->get( 'meta_query' );
	$meta_query[] = denver17_members_only_exclusion();
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'denver17_hide_members_only_from_public_queries' );

/**
 * A non-member opening a Members Only post is sent to sign in, and comes back
 * to the post afterwards. The members plugin supplies its sign-in URL through
 * elks17_member_login_url; without it, the homepage.
 */
function denver17_block_members_only_single() {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}
	if ( ! denver17_is_members_only( get_queried_object_id() ) || denver17_viewer_is_member() ) {
		return;
	}

	$login = apply_filters( 'elks17_member_login_url', '' );
	if ( $login ) {
		$path  = wp_parse_url( get_permalink( get_queried_object_id() ), PHP_URL_PATH );
		$login = add_query_arg( 'redirect_to', rawurlencode( $path ), $login );
	}
	nocache_headers();
	wp_safe_redirect( $login ? $login : home_url( '/' ) );
	exit;
}
add_action( 'template_redirect', 'denver17_block_members_only_single' );

/**
 * REST: /wp/v2/posts would otherwise hand any visitor the full text. A REST
 * request authenticated by cookie and nonce (the block editor) runs as the
 * signed-in user, so editors are unaffected.
 */
add_filter( 'rest_post_query', function ( $args ) {
	if ( denver17_viewer_is_member() ) {
		return $args;
	}
	$args['meta_query']   = isset( $args['meta_query'] ) ? (array) $args['meta_query'] : [];
	$args['meta_query'][] = denver17_members_only_exclusion();
	return $args;
} );

// A single post is refused before the controller runs. (Returning a WP_Error
// from rest_prepare_post instead is a fatal: get_item() calls link_header() on
// whatever comes back.)
add_filter( 'rest_request_before_callbacks', function ( $response, $handler, $request ) {
	if ( is_wp_error( $response ) || ! preg_match( '#^/wp/v2/posts/(\d+)#', $request->get_route(), $m ) ) {
		return $response;
	}
	if ( denver17_is_members_only( (int) $m[1] ) && ! denver17_viewer_is_member() ) {
		return new WP_Error( 'rest_post_invalid_id', 'Invalid post ID.', [ 'status' => 404 ] );
	}
	return $response;
}, 10, 3 );

/** Keep Members Only posts out of search engines and Rank Math's sitemap. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_singular( 'post' ) && denver17_is_members_only( get_queried_object_id() ) ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( is_singular( 'post' ) && denver17_is_members_only( get_queried_object_id() ) ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'nofollow';
	}
	return $robots;
} );

add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $object ) {
	// Rank Math passes a raw database row here, not a WP_Post.
	if ( 'post' === $type && is_object( $object ) && ! empty( $object->ID ) && denver17_is_members_only( (int) $object->ID ) ) {
		return false;
	}
	return $url;
}, 10, 3 );

/**
 * "Members" tag shown in the News list's meta line for Members Only and Both
 * posts. Echoes nothing for public posts.
 */
function denver17_members_tag( $post_id = 0 ) {
	if ( 'public' === denver17_get_post_visibility( $post_id ) ) {
		return;
	}
	echo '<span aria-hidden="true">&middot;</span> <span class="archive-item-members">Members</span>';
}
