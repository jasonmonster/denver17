<?php
/**
 * Block Render: denver17/beer-list
 *
 * Pulls tap list from denver17_get_beer_data() (inc/beer-feed.php) and
 * renders On Tap and Coming Soon sections. All display decisions are
 * controlled by block attributes so the design can be shaped in CSS and
 * the editor without touching this file.
 */
function denver17_render_block_beer_list( $attributes ) {
    $data        = denver17_get_beer_data();
    $on_tap      = $data['on_tap']      ?? [];
    $coming_soon = $data['coming_soon'] ?? [];

    $heading          = trim( $attributes['heading']        ?? 'On Tap' );
    $show_style       = (bool) ( $attributes['showStyle']      ?? true );
    $show_abv         = (bool) ( $attributes['showAbv']        ?? true );
    $show_coming_soon = (bool) ( $attributes['showComingSoon'] ?? true );

    ob_start();
    ?>
    <div class="beer-list">

        <?php if ( $heading ) : ?>
            <h2 class="beer-list__heading"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <?php if ( $on_tap ) : ?>
            <ul class="beer-list__items" role="list">
                <?php foreach ( $on_tap as $beer ) : ?>
                    <li class="beer-list__item">
                        <span class="beer-list__name"><?php echo esc_html( $beer['name'] ); ?></span>
                        <?php if ( $show_style || $show_abv ) : ?>
                            <span class="beer-list__meta">
                                <?php if ( $show_style && $beer['style'] ) : ?>
                                    <span class="beer-list__style"><?php echo esc_html( $beer['style'] ); ?></span>
                                <?php endif; ?>
                                <?php if ( $show_style && $show_abv && $beer['style'] && $beer['abv'] ) : ?>
                                    <span class="beer-list__sep" aria-hidden="true">&nbsp;&middot;&nbsp;</span>
                                <?php endif; ?>
                                <?php if ( $show_abv && $beer['abv'] ) : ?>
                                    <span class="beer-list__abv"><?php echo esc_html( $beer['abv'] ); ?>%</span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="beer-list__empty">Tap list unavailable &mdash; check back soon.</p>
        <?php endif; ?>

        <?php if ( $show_coming_soon && $coming_soon ) : ?>
            <div class="beer-list__coming-soon">
                <div class="beer-list__coming-soon-label">Coming Soon</div>
                <ul class="beer-list__items beer-list__items--coming-soon" role="list">
                    <?php foreach ( $coming_soon as $beer ) : ?>
                        <li class="beer-list__item beer-list__item--coming-soon">
                            <span class="beer-list__name"><?php echo esc_html( $beer['name'] ); ?></span>
                            <?php if ( $show_style && $beer['style'] ) : ?>
                                <span class="beer-list__meta">
                                    <span class="beer-list__style"><?php echo esc_html( $beer['style'] ); ?></span>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
