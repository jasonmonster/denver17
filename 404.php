<?php
/**
 * 404 Template
 *
 * Kept intentionally simple: same banner as every other inner page, then a
 * single centered block below with the elk photo, a short line of copy, and
 * two ways back into the site. No search form — nothing on the site styles
 * WordPress's default search markup yet, so this leans on the two links
 * (Home, Events) that are guaranteed to exist instead of guessing at others.
 */

get_header();
?>

<main id="main" class="site-main">

    <?php
    get_template_part( 'template-parts/page/banner', null, [
        'eyebrow' => '404',
        'title'   => 'This Page Has Left the Herd',
    ] );
    ?>

    <div class="page-entry-content error-404-content">

        <div class="error-404">

            <img
                class="error-404-photo"
                src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/404-elk.jpg' ); ?>"
                alt="A bull elk looking directly at the camera, antlers up, appearing thoroughly confused"
                width="900"
                height="644"
                loading="eager"
            >

            <p class="error-404-body">
                Even he doesn't know where this page went. It may have been renamed, moved
                during the rebuild, or never existed in the first place &mdash; but the
                lodge itself is right where you left it.
            </p>

            <div class="error-404-actions">
                <a class="hero-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to Home</a>
                <a class="error-404-link" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">See What's Happening &rarr;</a>
            </div>

        </div>

    </div>

</main>

<?php get_footer(); ?>
