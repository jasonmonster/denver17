<?php
/**
 * Block Render: denver17/beer-list
 *
 * Pulls the tap list from denver17_get_beer_data() (inc/beer-feed.php) and
 * renders On Tap and Coming Soon sections. All display decisions are
 * controlled by block attributes so the design can be shaped in CSS and the
 * editor without touching this file.
 *
 * Two variants:
 *   band   — full-bleed dark section, optional photo behind at low opacity.
 *            The default. Echoes the in-lodge TV menu without copying it.
 *   plain  — light, inline list for pages where a dark band would be too loud.
 *
 * Each item is a three-part row (name / leader rule / ABV) rather than a
 * justified flex pair. The leader is the standard printed-menu device: it
 * carries the eye across the gap, so a two-column layout stays legible where
 * a plain space-between row falls apart.
 *
 * Deliberately no photo of the taps themselves — the list is live from a
 * Google Sheet and any tap photo goes stale the moment a keg is swapped.
 * The back-bar stained glass doesn't have that problem.
 */
function denver17_render_block_beer_list( $attributes ) {
    $data        = denver17_get_beer_data();
    $on_tap      = $data['on_tap']      ?? [];
    $coming_soon = $data['coming_soon'] ?? [];

    $variant          = ( $attributes['variant'] ?? 'band' ) === 'plain' ? 'plain' : 'band';
    $eyebrow          = trim( $attributes['eyebrow'] ?? '' );
    $heading          = trim( $attributes['heading'] ?? 'On Tap' );
    $note             = trim( $attributes['note']    ?? '' );
    $show_style       = (bool) ( $attributes['showStyle']      ?? true );
    $show_abv         = (bool) ( $attributes['showAbv']        ?? true );
    $show_coming_soon = (bool) ( $attributes['showComingSoon'] ?? true );

    $bg     = $attributes['backgroundImage'] ?? [];
    $bg_url = ( $variant === 'band' && ! empty( $bg['url'] ) ) ? $bg['url'] : '';

    // get_block_wrapper_attributes() picks up align + anchor from block supports.
    $wrapper = get_block_wrapper_attributes( array(
        'class' => 'beer-list beer-list--' . $variant . ( $bg_url ? ' beer-list--has-bg' : '' ),
    ) );

    ob_start();
    ?>
    <div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

        <?php if ( $bg_url ) : ?>
            <div class="beer-list__bg" aria-hidden="true">
                <img src="<?php echo esc_url( $bg_url ); ?>" alt="" loading="lazy" decoding="async">
            </div>
            <div class="beer-list__scrim" aria-hidden="true"></div>
        <?php endif; ?>

        <div class="beer-list__inner">

            <?php if ( $eyebrow || $heading || $note ) : ?>
                <div class="beer-list__head">
                    <?php if ( $eyebrow ) : ?>
                        <div class="beer-list__eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
                    <?php endif; ?>
                    <?php if ( $heading ) : ?>
                        <h2 class="beer-list__heading"><?php echo esc_html( $heading ); ?></h2>
                    <?php endif; ?>
                    <?php if ( $note ) : ?>
                        <div class="beer-list__note">
                            <span class="beer-list__pulse" aria-hidden="true"></span>
                            <?php echo esc_html( $note ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( $on_tap ) : ?>
                <ul class="beer-list__items" role="list">
                    <?php foreach ( $on_tap as $beer ) : ?>
                        <li class="beer-list__item">
                            <div class="beer-list__row">
                                <span class="beer-list__name"><?php echo esc_html( $beer['name'] ); ?></span>
                                <span class="beer-list__leader" aria-hidden="true"></span>
                                <?php if ( $show_abv && $beer['abv'] ) : ?>
                                    <span class="beer-list__abv"><?php echo esc_html( $beer['abv'] ); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <?php if ( $show_style && $beer['style'] ) : ?>
                                <div class="beer-list__style"><?php echo esc_html( $beer['style'] ); ?></div>
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
                    <ul class="beer-list__coming-soon-items" role="list">
                        <?php foreach ( $coming_soon as $beer ) : ?>
                            <li class="beer-list__coming-soon-item">
                                <span class="beer-list__name"><?php echo esc_html( $beer['name'] ); ?></span>
                                <?php if ( $show_style && $beer['style'] ) : ?>
                                    <span class="beer-list__style"><?php echo esc_html( $beer['style'] ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

    </div>
    <?php
    return ob_get_clean();
}
