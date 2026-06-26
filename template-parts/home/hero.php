<?php
/**
 * Template Part: Homepage Hero
 *
 * Called by the denver17/hero block render callback with $args from block attributes.
 * All values fall back to sensible defaults if args aren't provided.
 *
 * Note: hero-bg is an <img> tag. Swap for <video autoplay muted loop playsinline
 * class="hero-bg"> once footage is ready — the overlay treatment works for both.
 */

$bg_image      = $args['bg_image']      ?? '';
$bg_image_alt  = $args['bg_image_alt']  ?? '';
$eyebrow       = $args['eyebrow']       ?? 'Denver, Colorado &middot; Est. 1882';
$heading_line1 = $args['heading_line1'] ?? 'A private bar,';
$heading_line2 = $args['heading_line2'] ?? '144 years of giving back.';
$subtext       = $args['subtext']       ?? 'Lodge #17 &mdash; Mother Lodge of the Rockies. A full bar, beer garden, golf simulators, and a downtown Denver view that does all the talking.';
$cta_text      = $args['cta_text']      ?? 'See upcoming events';
$cta_url       = $args['cta_url']       ?? home_url( '/events/' );
?>

<section class="hero">

    <?php
    $hero_bg_src = $bg_image ?: denver17_placeholder( 1600, 800, 'Hero Background — View from Bar' );
    ?>
    <img class="hero-bg"
         src="<?php echo esc_url( $hero_bg_src ); ?>"
         alt="<?php echo esc_attr( $bg_image_alt ); ?>"
         aria-hidden="true">

    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-content">
        <div class="hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
        <h1 class="hero-h">
            <?php echo esc_html( $heading_line1 ); ?>
            <em><?php echo esc_html( $heading_line2 ); ?></em>
        </h1>
        <p class="hero-sub"><?php echo esc_html( $subtext ); ?></p>
        <a class="hero-btn" href="<?php echo esc_url( $cta_url ); ?>">
            <?php echo esc_html( $cta_text ); ?>
        </a>
    </div>

    <?php get_template_part( 'template-parts/home/hours-card' ); ?>

</section>
