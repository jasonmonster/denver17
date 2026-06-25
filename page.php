<?php
/**
 * Static Page Template
 *
 * Handles all standard pages (About, Visit, Contact, etc.).
 * Interior page templates in template-parts/page/ can extend this.
 */

get_header();
?>

<main id="main" class="site-main page-main">

    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-article' ); ?>>
                <div class="page-content">
                    <?php the_content(); ?>
                </div>
            </article>

        <?php endwhile;
    endif;
    ?>

</main>

<?php get_footer(); ?>
