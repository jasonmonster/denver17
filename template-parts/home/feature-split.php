<?php
/**
 * Template Part: Feature Split
 *
 * Reusable two-column feature section: image on one side, text on the other.
 * Used twice on the homepage (bar, community). Called via get_template_part()
 * with $args for all variable content.
 *
 * Expected $args keys:
 *   tag        (string) — small eyebrow label
 *   heading    (string) — main heading, may contain <br> tags
 *   body       (string) — body copy
 *   link_href  (string) — CTA link URL
 *   link_text  (string) — CTA link text
 *   image_url  (string) — image src from media library
 *   image_alt  (string) — image alt text
 *   variant    (string) — 'dark' (default, --p800 bg) or 'mid' (--p600 bg)
 *   layout     (string) — 'image-left' (default) or 'text-left'
 */

$tag       = $args['tag']       ?? '';
$heading   = $args['heading']   ?? '';
$body      = $args['body']      ?? '';
$link_href = $args['link_href'] ?? '#';
$link_text = $args['link_text'] ?? 'Learn more &rarr;';
$image_url = $args['image_url'] ?? '';
$image_alt = $args['image_alt'] ?? '';
$variant   = $args['variant']   ?? 'dark';
$layout    = $args['layout']    ?? 'image-left';

$text_class = 'feature-text' . ( $variant === 'mid' ? ' mid' : '' );
?>

<section class="feature-split">

    <?php if ( $layout === 'image-left' ) : ?>

        <div class="feature-img">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>"
                     alt="<?php echo esc_attr( $image_alt ); ?>">
            <?php endif; ?>
        </div>

        <div class="<?php echo esc_attr( $text_class ); ?>">
            <?php if ( $tag ) : ?>
                <div class="feature-tag"><?php echo esc_html( $tag ); ?></div>
            <?php endif; ?>
            <h2 class="feature-h"><?php echo wp_kses( $heading, [ 'br' => [] ] ); ?></h2>
            <p class="feature-body"><?php echo wp_kses_post( $body ); ?></p>
            <a class="feature-link" href="<?php echo esc_url( $link_href ); ?>">
                <?php echo wp_kses( $link_text, [ 'span' => [ 'class' => [] ] ] ); ?>
            </a>
        </div>

    <?php else : ?>

        <div class="<?php echo esc_attr( $text_class ); ?>">
            <?php if ( $tag ) : ?>
                <div class="feature-tag"><?php echo esc_html( $tag ); ?></div>
            <?php endif; ?>
            <h2 class="feature-h"><?php echo wp_kses( $heading, [ 'br' => [] ] ); ?></h2>
            <p class="feature-body"><?php echo wp_kses_post( $body ); ?></p>
            <a class="feature-link" href="<?php echo esc_url( $link_href ); ?>">
                <?php echo wp_kses( $link_text, [ 'span' => [ 'class' => [] ] ] ); ?>
            </a>
        </div>

        <div class="feature-img">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>"
                     alt="<?php echo esc_attr( $image_alt ); ?>">
            <?php endif; ?>
        </div>

    <?php endif; ?>

</section>
