<?php
/**
 * Front Page Template
 *
 * Homepage of Denver Elks Lodge #17.
 * Set via Settings → Reading → "A static page" → Front page.
 *
 * Sections:
 *   1. Hero + Hours Card
 *   2. Feature: Jolly Corks Bar
 *   3. Feature: Lodge Community
 *   4. Membership Steps
 *   5. Events Band (placeholder — replace with plugin output post-launch)
 *   6. CTA Band
 */

get_header();
?>

<main id="main" class="site-main">

    <?php get_template_part( 'template-parts/home/hero' ); ?>

    <?php
    get_template_part( 'template-parts/home/feature-split', null, [
        'tag'       => 'The Jolly Corks Bar',
        'heading'   => 'Original stained glass.<br>Member pricing.<br>No tourists.',
        'body'      => 'The back bar&rsquo;s stained glass elk has been watching over Jolly Corks since long before anyone reading this was born. Members drink at near-wholesale. It&rsquo;s one of the better deals in Denver &mdash; if you know about it.',
        'link_href' => home_url( '/visit/the-jolly-corks-bar/' ),
        'link_text' => 'See the bar &rarr;',
        'image_url' => get_theme_mod( 'denver17_feature_bar_img', '' ),
        'image_alt' => 'Stained glass elk window behind the bar',
        'variant'   => 'dark',
        'layout'    => 'image-left',
    ] );
    ?>

    <?php
    get_template_part( 'template-parts/home/feature-split', null, [
        'tag'       => 'Lodge family',
        'heading'   => 'Strangers at the bar.<br>Family by the second round.',
        'body'      => 'Walk in once and somebody will know your name before you leave. Walk in twice and you&rsquo;ll know theirs. It&rsquo;s a lodge full of people who actually want you there.',
        'link_href' => home_url( '/learn/our-lodge/' ),
        'link_text' => 'Meet the lodge &rarr;',
        'image_url' => get_theme_mod( 'denver17_feature_community_img', '' ),
        'image_alt' => 'Members celebrating together at the lodge',
        'variant'   => 'mid',
        'layout'    => 'text-left',
    ] );
    ?>

    <?php get_template_part( 'template-parts/home/membership-steps' ); ?>

    <?php get_template_part( 'template-parts/home/events-band' ); ?>

    <?php get_template_part( 'template-parts/home/cta-band' ); ?>

</main>

<?php get_footer(); ?>
