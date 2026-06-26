<?php
/**
 * Import Homepage Content
 *
 * Writes the serialized Gutenberg block markup to the Home page, matching
 * the mockup copy exactly. Images still need to be set in the block editor
 * sidebar after running this — they can't be scripted since they depend on
 * media library IDs.
 *
 * Run once after deploy (requires setup.php to have run first):
 *   wp eval-file bin/import-homepage.php
 *
 * Safe to re-run — overwrites post_content each time, no side effects.
 */

// =============================================================================
// HELPERS
// =============================================================================

/**
 * Serializes a self-closing dynamic block (save returns null).
 */
function d17_block( $name, $attrs = [] ) {
    if ( empty( $attrs ) ) {
        return '<!-- wp:' . $name . ' /-->';
    }
    $json = json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    return '<!-- wp:' . $name . ' ' . $json . ' /-->';
}

// =============================================================================
// BLOCK CONTENT
// =============================================================================

$blocks = implode( "\n\n", [

    d17_block( 'denver17/hero', [
        'eyebrow'      => 'Denver, Colorado · Est. 1882',
        'headingLine1' => 'A private bar,',
        'headingLine2' => '144 years of giving back.',
        'subtext'      => 'Lodge #17 — Mother Lodge of the Rockies. A full bar, beer garden, golf simulators, and a downtown Denver view that does all the talking.',
        'ctaText'      => 'See upcoming events',
        'ctaUrl'       => '',
        // backgroundImage: set manually in block editor after uploading to media library
    ] ),

    d17_block( 'denver17/feature-split', [
        'tag'      => 'The Jolly Corks Bar',
        'heading'  => "Original stained glass.\nMember pricing.\nNo tourists.",
        'body'     => "The back bar's stained glass elk has been watching over Jolly Corks since long before anyone reading this was born. Members drink at near-wholesale. It's one of the better deals in Denver — if you know about it.",
        'linkText' => 'See the bar',
        'linkUrl'  => '',
        'variant'  => 'dark',
        'layout'   => 'image-left',
        // image: set manually
    ] ),

    d17_block( 'denver17/feature-split', [
        'tag'      => 'Lodge family',
        'heading'  => "Strangers at the bar.\nFamily by the second round.",
        'body'     => "Walk in once and somebody will know your name before you leave. Walk in twice and you'll know theirs. It's a lodge full of people who actually want you there.",
        'linkText' => 'Meet the lodge',
        'linkUrl'  => '',
        'variant'  => 'mid',
        'layout'   => 'text-left',
        // image: set manually
    ] ),

    d17_block( 'denver17/membership-steps', [
        'sectionTag'     => 'Membership',
        'sectionHeading' => 'From guest to lodge family',
        'step1Title'     => 'Stop in as a guest',
        'step1Body'      => 'Visit the bar, meet some members, see what Jolly Corks is about. No pressure. Just cold drinks and real people.',
        'step2Title'     => 'Apply for membership',
        'step2Body'      => "Open to all. A short application, a sponsor from inside the lodge, and you're on your way. Most people wonder why they waited.",
        'step3Title'     => 'Get your member number',
        'step3Body'      => "Member pricing on drinks, golf simulator access, invites to events, and a lodge that's had your back since 1882.",
        // step images: set manually
    ] ),

    d17_block( 'denver17/events-band', [
        'sectionHeading' => 'Upcoming at the lodge',
    ] ),

    d17_block( 'denver17/cta-band', [
        'eyebrow'    => 'Mother Lodge of the Rockies',
        'heading'    => 'Come see what #17 is about.',
        'buttonText' => 'Plan your visit',
        'buttonUrl'  => '',
    ] ),

] );

// =============================================================================
// WRITE TO HOME PAGE
// =============================================================================

$home = get_page_by_path( 'home' );

if ( ! $home ) {
    WP_CLI::error( 'Home page not found. Run bin/setup.php first.' );
    return;
}

$result = wp_update_post( [
    'ID'           => $home->ID,
    'post_content' => $blocks,
    'post_status'  => 'draft',
], true );

if ( is_wp_error( $result ) ) {
    WP_CLI::error( 'Failed: ' . $result->get_error_message() );
    return;
}

WP_CLI::success( "Content written to Home page (ID {$home->ID}). Status: draft." );
WP_CLI::log( '' );
WP_CLI::log( 'Still needed before publishing:' );
WP_CLI::log( '  1. Upload photos to media library' );
WP_CLI::log( '  2. Open Home page in block editor' );
WP_CLI::log( '  3. Set images in Hero, both Feature Split blocks, and each Membership Step' );
WP_CLI::log( '  4. Set CTA and feature link URLs once inner pages are published' );
WP_CLI::log( '  5. Publish' );
