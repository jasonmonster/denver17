<?php
/**
 * Denver Elks #17 — rewrite plain-permalink internal links to pretty ones.
 *
 * Finds `?page_id=N` / `?p=N` style links embedded in post content (from any
 * host, or host-relative) and replaces them with the target's ROOT-RELATIVE
 * pretty permalink, e.g. `/our-lodge/history-of-the-elks/`.
 *
 * Root-relative (not absolute) on purpose: these survive the staging→production
 * domain cutover with no second search-replace.
 *
 * Run under WP-CLI. Dry run by default — pass `apply` to actually write:
 *
 *     wp eval-file bin/update-internal-permalinks.php          # preview only
 *     wp eval-file bin/update-internal-permalinks.php apply    # write changes
 *
 * Idempotent: a rewritten link contains no `?page_id=`, so re-running is a no-op.
 * Naturally safe to run again after new content is added.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress, e.g. `wp eval-file bin/update-internal-permalinks.php`.\n" );
	exit( 1 );
}

global $wpdb;

// `apply` as a positional arg flips this from preview to write.
$apply = isset( $args ) && is_array( $args ) && in_array( 'apply', $args, true );

// Match http(s)://host/?page_id=N, //host/?p=N, or bare /?page_id=N, keeping any
// trailing #fragment. Deliberately narrow: only the leading ? param, which is the
// shape WordPress emits for these links.
$pattern = '#(?:https?:)?(?://[^/"\'\s<>]+)?/?\?(?:page_id|p)=(\d+)(\#[^"\'\s<>]*)?#';

$unresolved = array(); // id => count  (page missing/trashed)
$drafts     = array(); // id => count  (target not published — permalink still ugly)
$touched    = 0;       // posts changed
$replaced   = 0;       // individual links rewritten
$preview    = array(); // sample of old => new for the dry-run report

// Direct query (WP-CLI runs with no user; WP_Query post_status => any drops drafts).
// Skip revisions and menu items — menu links live in postmeta, handled separately.
$rows = $wpdb->get_results(
	"SELECT ID, post_content
	   FROM {$wpdb->posts}
	  WHERE post_type NOT IN ('revision','nav_menu_item')
	    AND post_status NOT IN ('trash','auto-draft')
	    AND ( post_content LIKE '%page_id=%' OR post_content LIKE '%?p=%' )"
);

foreach ( $rows as $row ) {
	$post_id  = (int) $row->ID;
	$original = $row->post_content;

	$new = preg_replace_callback(
		$pattern,
		function ( $m ) use ( &$unresolved, &$drafts, &$replaced, &$preview ) {
			$target_id = (int) $m[1];
			$fragment  = isset( $m[2] ) ? $m[2] : '';
			$permalink = get_permalink( $target_id );

			// Page gone / trashed — leave the link untouched, report it.
			if ( ! $permalink ) {
				$unresolved[ $target_id ] = ( $unresolved[ $target_id ] ?? 0 ) + 1;
				return $m[0];
			}

			// Draft/pending targets still resolve to ?page_id= — no pretty slug yet.
			// Rewriting would be a lateral no-op, so skip and report.
			if ( preg_match( '/[?&](?:page_id|p)=/', $permalink ) ) {
				$drafts[ $target_id ] = ( $drafts[ $target_id ] ?? 0 ) + 1;
				return $m[0];
			}

			$relative = wp_make_link_relative( $permalink ) . $fragment;
			$replaced++;
			if ( count( $preview ) < 25 ) {
				$preview[] = array( 'from' => $m[0], 'to' => $relative );
			}
			return $relative;
		},
		$original
	);

	if ( $new === null ) {
		WP_CLI::warning( "preg_replace_callback failed on post #{$post_id} — skipped." );
		continue;
	}

	if ( $new !== $original ) {
		$touched++;
		if ( $apply ) {
			$res = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $new ),
				),
				true
			);
			if ( is_wp_error( $res ) ) {
				WP_CLI::warning( "Failed to update post #{$post_id}: " . $res->get_error_message() );
				$touched--;
			}
		}
	}
}

// ── Report ───────────────────────────────────────────────────────────────────
WP_CLI::log( '' );
WP_CLI::log( $apply ? '=== APPLIED ===' : '=== DRY RUN (no changes written) ===' );
WP_CLI::log( sprintf( 'Links rewritten:   %d', $replaced ) );
WP_CLI::log( sprintf( 'Posts %s: %d', $apply ? 'updated' : 'would change', $touched ) );

if ( $preview ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Sample of rewrites:' );
	foreach ( $preview as $p ) {
		WP_CLI::log( sprintf( '  %s  ->  %s', $p['from'], $p['to'] ) );
	}
	if ( $replaced > count( $preview ) ) {
		WP_CLI::log( sprintf( '  … and %d more.', $replaced - count( $preview ) ) );
	}
}

if ( $drafts ) {
	WP_CLI::log( '' );
	WP_CLI::warning( 'Left alone — target not published yet (still resolves to ?page_id=):' );
	foreach ( $drafts as $id => $n ) {
		WP_CLI::log( sprintf( '  page_id=%d  (%d link%s)', $id, $n, $n === 1 ? '' : 's' ) );
	}
}

if ( $unresolved ) {
	WP_CLI::log( '' );
	WP_CLI::warning( 'Left alone — no such page (trashed/deleted?):' );
	foreach ( $unresolved as $id => $n ) {
		WP_CLI::log( sprintf( '  page_id=%d  (%d link%s)', $id, $n, $n === 1 ? '' : 's' ) );
	}
}

WP_CLI::log( '' );
if ( ! $apply && $touched > 0 ) {
	WP_CLI::log( 'Re-run with `apply` to write these changes:' );
	WP_CLI::log( '  wp eval-file bin/update-internal-permalinks.php apply' );
} else {
	WP_CLI::success( $apply ? 'Done.' : 'Nothing to change.' );
}
