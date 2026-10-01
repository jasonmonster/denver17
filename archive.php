<?php
/**
 * Archive Template
 *
 * Used for category, tag, author, and date archives.
 * Text-only list — no featured images by design (see home.php, same list
 * markup/classes). Each item shows date, title, excerpt, and a linked
 * category eyebrow (skipped when it would just link back to the category
 * archive you're already on).
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

            <div class="archive-list">
                <?php while ( have_posts() ) : the_post(); ?>

                    <?php
                    $cats        = get_the_category();
                    $cat         = $cats ? $cats[0] : null;
                    $on_this_cat = $cat && is_category() && (int) $cat->term_id === get_queried_object_id();
                    ?>

                    <article <?php post_class( 'archive-item' ); ?>>

                        <div class="archive-item-meta">
                            <?php if ( $cat && ! $on_this_cat ) : ?>
                                <a class="archive-item-cat" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
                                <span aria-hidden="true">&middot;</span>
                            <?php endif; ?>
                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                            <?php denver17_members_tag(); ?>
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

            <p class="archive-empty">Nothing here yet.</p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
