<?php
/**
 * Static Page Template
 *
 * All standard pages (Visit, Learn, Community, Contact, etc.) use this.
 * Content is composed in the block editor — no hardcoded copy here.
 *
 * Content width defaults to 800px for prose blocks. Editors can break out
 * using Gutenberg's alignwide (1140px) or alignfull (edge-to-edge) on any
 * block — the theme declares add_theme_support( 'align-wide' ) in theme-setup.php.
 */

get_header();
?>

<main id="main" class="site-main">

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

        <?php get_template_part( 'template-parts/page/banner' ); ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-article' ); ?>>
            <div class="entry-content page-entry-content">
                <?php the_content(); ?>
            </div>
        </article>

    <?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>
