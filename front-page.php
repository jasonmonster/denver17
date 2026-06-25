<?php
/**
 * Front Page Template
 *
 * Homepage of Denver Elks Lodge #17.
 * Set via Settings → Reading → "A static page" → Front page.
 *
 * Content is managed entirely through the block editor. Add blocks in this order:
 *   1. Hero (denver17/hero)
 *   2. Feature Split — bar (denver17/feature-split, variant: dark, layout: image-left)
 *   3. Feature Split — community (denver17/feature-split, variant: mid, layout: text-left)
 *   4. Membership Steps (denver17/membership-steps)
 *   5. Events Band (denver17/events-band)
 *   6. CTA Band (denver17/cta-band)
 *
 * All Denver17 blocks appear under the "Denver Elks #17" category in the inserter.
 */

get_header();
?>

<main id="main" class="site-main">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            the_content();
        endwhile;
    endif;
    ?>
</main>

<?php get_footer(); ?>
