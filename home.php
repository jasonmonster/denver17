<?php
/**
 * Blog / News Index Template
 *
 * WordPress's standard "posts page" template — used when Settings → Reading →
 * "Posts page" is set to the News page (see bin/setup.php, page_for_posts).
 * Text-only list, deliberately no featured images — the lodge will never use
 * them here, so there's no thumbnail markup to keep in sync. Each item shows
 * a linked category eyebrow, date, title, and excerpt.
 *
 * Members Only posts never reach this loop — inc/post-visibility.php strips
 * them out via pre_get_posts until member login exists.
 */

get_header();

$posts_page_id   = (int) get_option( 'page_for_posts' );
$banner_title     = $posts_page_id ? get_the_title( $posts_page_id ) : 'News';
$banner_subtitle  = $posts_page_id ? get_the_excerpt( $posts_page_id ) : '';
?>

<main id="main" class="site-main">

    <?php
    get_template_part( 'template-parts/page/banner', null, [
        'eyebrow'  => "What's happening",
        'title'    => $banner_title,
        'subtitle' => $banner_subtitle,
    ] );
    ?>

    <div class="archive-wrap page-entry-content">

        <?php if ( have_posts() ) : ?>

            <div class="archive-list">
                <?php while ( have_posts() ) : the_post(); ?>

                    <?php
                    $cats     = get_the_category();
                    $cat_name = $cats ? $cats[0]->name : '';
                    $cat_link = $cats ? get_category_link( $cats[0]->term_id ) : '';
                    ?>

                    <article <?php post_class( 'archive-item' ); ?>>

                        <div class="archive-item-meta">
                            <?php if ( $cat_name ) : ?>
                                <a class="archive-item-cat" href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $cat_name ); ?></a>
                                <span aria-hidden="true">&middot;</span>
                            <?php endif; ?>
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                        </div>

                        <h2 class="archive-item-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <p class="archive-item-excerpt"><?php the_excerpt(); ?></p>

                        <a class="archive-item-readmore" href="<?php the_permalink(); ?>">
                            Read More<span class="archive-item-readmore-arrow" aria-hidden="true">&rarr;</span>
                        </a>

                    </article>

                <?php endwhile; ?>
            </div>

            <div class="archive-pagination">
                <?php
                the_posts_pagination( [
                    'mid_size'  => 2,
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                ] );
                ?>
            </div>

        <?php else : ?>

            <p class="archive-empty">No news yet &mdash; check back soon.</p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
