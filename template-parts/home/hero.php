<?php
/**
 * Template Part: Homepage Hero
 *
 * Full-bleed hero with background image, headline, and hours card.
 * Background image is set via Customizer → Homepage Images → Hero Background Image.
 *
 * Note: hero-bg is an <img> for now. Swap for <video autoplay muted loop playsinline>
 * with class="hero-bg" once footage is ready — the overlay treatment works for both.
 */

$hero_bg = get_theme_mod( 'denver17_hero_bg', '' );
?>

<section class="hero">

    <?php if ( $hero_bg ) : ?>
        <img class="hero-bg"
             src="<?php echo esc_url( $hero_bg ); ?>"
             alt=""
             aria-hidden="true">
    <?php endif; ?>

    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="hero-content">
        <div class="hero-eyebrow">Denver, Colorado &middot; Est. 1882</div>
        <h1 class="hero-h">
            A private bar, <em>144 years</em> of giving back.
        </h1>
        <p class="hero-sub">
            Lodge #17 &mdash; Mother Lodge of the Rockies. A full bar, beer garden, golf simulators, and a downtown Denver view that does all the talking.
        </p>
        <a class="hero-btn" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            See upcoming events
        </a>
    </div>

    <?php get_template_part( 'template-parts/home/hours-card' ); ?>

</section>
