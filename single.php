<?php
/**
 * Single Post Template
 *
 * Used for individual blog/news posts. Banner pulls the first category
 * as the eyebrow and shows date + author in the meta line.
 */

get_header();
?>

<main id="main" class="site-main">

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

        <?php
        // Eyebrow: first assigned category
        $cats    = get_the_category();
        $eyebrow = $cats ? esc_html( $cats[0]->name ) : '';

        // Meta line: date · author
        $meta = '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">'
              . esc_html( get_the_date() ) . '</time>';
        if ( get_the_author() ) {
            $meta .= ' &middot; ' . esc_html( get_the_author() );
        }

        get_template_part( 'template-parts/page/banner', null, [
            'eyebrow' => $eyebrow,
            'meta'    => $meta,
        ] );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-article' ); ?>>

            <div class="single-featured-img">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'full' ); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url( denver17_placeholder( 1200, 630, 'Post Featured Photo' ) ); ?>"
                         alt="">
                <?php endif; ?>
            </div>

            <div class="entry-content page-entry-content">
                <?php the_content(); ?>
            </div>

        </article>

        <nav class="post-navigation page-entry-content" aria-label="Post navigation">
            <?php
            the_post_navigation( [
                'prev_text' => '&larr; %title',
                'next_text' => '%title &rarr;',
            ] );
            ?>
        </nav>

    <?php endwhile; endif; ?>

</main>

<?php get_footer(); ?>
