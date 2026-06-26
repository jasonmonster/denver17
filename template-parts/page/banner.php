<?php
/**
 * Template Part: Inner Page Banner
 *
 * Rendered at the top of all inner pages (page.php, single.php, archive.php).
 * Outputs the dark purple title section with optional eyebrow and subtitle.
 *
 * @param array $args {
 *   @type string $eyebrow   Small label above the title. Defaults to parent page
 *                           title (identifies the section, e.g. "Visit", "Learn").
 *   @type string $title     Overrides the_title() — use for archive titles or any
 *                           case where the post title isn't right.
 *   @type string $subtitle  Optional paragraph below the title. Defaults to the
 *                           post excerpt if one is set.
 *   @type string $meta      Optional meta line (date, author) for single posts.
 *                           Passed as pre-escaped HTML.
 * }
 */

$eyebrow  = $args['eyebrow']  ?? '';
$title    = $args['title']    ?? '';
$subtitle = $args['subtitle'] ?? '';
$meta     = $args['meta']     ?? '';

// Default eyebrow: parent page name — e.g. "Learn" when on "Our Lodge"
if ( ! $eyebrow && in_the_loop() ) {
    $parent_id = wp_get_post_parent_id( get_the_ID() );
    if ( $parent_id ) {
        $eyebrow = get_the_title( $parent_id );
    }
}

// Default subtitle: post excerpt if one is set
if ( ! $subtitle && in_the_loop() ) {
    $raw_excerpt = get_the_excerpt();
    // Only use auto-generated excerpts if they're intentionally short;
    // editors who want a subtitle can set it explicitly in the excerpt field.
    if ( has_excerpt() ) {
        $subtitle = $raw_excerpt;
    }
}
?>

<div class="page-banner">
    <div class="page-banner-inner">

        <?php if ( $eyebrow ) : ?>
            <div class="page-banner-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
        <?php endif; ?>

        <h1 class="page-banner-title">
            <?php echo $title ? esc_html( $title ) : get_the_title(); ?>
        </h1>

        <?php if ( $subtitle ) : ?>
            <p class="page-banner-subtitle"><?php echo esc_html( $subtitle ); ?></p>
        <?php endif; ?>

        <?php if ( $meta ) : ?>
            <div class="page-banner-meta"><?php echo wp_kses_post( $meta ); ?></div>
        <?php endif; ?>

    </div>
</div>
