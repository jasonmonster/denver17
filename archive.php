<?php
/**
 * Archive Template
 *
 * Used for category, tag, author, and date archives.
 * Post cards show a featured image (or placeholder) + date, title, excerpt.
 */

get_header();

// Banner: clean eyebrow and title without WP's default "Category: X" prefix
$archive_eyebrow = '';
$archive_title   = '';

if ( is_category() ) {
    $archive_eyebrow = 'Category';
    $archive_title   = single_cat_title( '', false );
} elseif ( is_tag() ) {
    $archive_eyebrow = 'Tag';
    $archive_title   = single_tag_title( '', false );
} elseif ( is_author() ) {
    $archive_eyebrow = 'Author';
    $archive_title   = get_the_author();
} elseif ( is_date() ) {
    $archive_eyebrow = 'Archive';
    $archive_title   = get_the_date( 'F Y' );
} else {
    $archive_title = get_the_archive_title();
}
?>

<main id="main" class="site-main">

    <?php
    get_template_part( 'template-parts/page/banner', null, [
        'eyebrow'  => $archive_eyebrow,
        'title'    => $archive_title,
        'subtitle' => get_the_archive_description(),
    ] );
    ?>

    <div class="archive-wrap page-entry-content">

        <?php if ( have_posts() ) : ?>

            <div class="archive-grid">
                <?php while ( have_posts() ) : the_post(); ?>

                    <article <?php post_class( 'archive-card' ); ?>>

                        <a class="archive-card-img-link" href="<?php the_permalink(); ?>">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'large' ); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url( denver17_placeholder( 800, 450, get_the_title() ) ); ?>"
                                     alt="">
                            <?php endif; ?>
                        </a>

                        <div class="archive-card-body">
                            <div class="archive-card-meta">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                    <?php echo esc_html( get_the_date() ); ?>
                                </time>
                            </div>
                            <h2 class="archive-card-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <p class="archive-card-excerpt"><?php the_excerpt(); ?></p>
                        </div>

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

            <p class="archive-empty">Nothing here yet.</p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
