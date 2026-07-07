<?php
/**
 * Template Part: Feature Split
 *
 * Reusable two-column section: image one side, text the other.
 * Called by the denver17/feature-split block render callback with $args.
 *
 * $args:
 *   tag        (string) — small eyebrow label
 *   heading    (string) — heading text; newlines rendered as <br>
 *   body       (string) — body copy
 *   link_href  (string) — CTA URL
 *   link_text  (string) — CTA label
 *   image_url  (string) — image src
 *   image_alt  (string) — image alt text
 *   variant    (string) — 'dark' (--p800 bg) or 'mid' (--p600 bg)
 *   layout     (string) — 'image-left' or 'text-left'
 */

$tag       = $args['tag']       ?? '';
$heading   = $args['heading']   ?? '';
$body      = $args['body']      ?? '';
$link_href = $args['link_href'] ?? '#';
$link_text = $args['link_text'] ?? 'Learn more';
$show_link = ! empty( $args['link_text'] );
$image_url = $args['image_url'] ?? '';
$image_alt = $args['image_alt'] ?? '';
$variant   = $args['variant']   ?? 'dark';
$layout    = $args['layout']    ?? 'image-left';

$text_class    = 'feature-text' . ( $variant === 'mid' ? ' mid' : '' );
$heading_html  = nl2br( esc_html( $heading ) );
$body_html     = nl2br( esc_html( $body ) );
?>

<section class="feature-split">

    <?php if ( $layout === 'image-left' ) : ?>

        <div class="feature-img">
            <?php
            $img_src = $image_url ?: denver17_placeholder( 800, 600, ( $tag ?: 'Section' ) . ' Photo' );
            ?>
            <img src="<?php echo esc_url( $img_src ); ?>"
                 alt="<?php echo esc_attr( $image_alt ); ?>">
        </div>

        <div class="<?php echo esc_attr( $text_class ); ?>">
            <?php if ( $tag ) : ?>
                <div class="feature-tag"><?php echo esc_html( $tag ); ?></div>
            <?php endif; ?>
            <h2 class="feature-h"><?php echo $heading_html; ?></h2>
            <p class="feature-body"><?php echo $body_html; ?></p>
            <?php if ( $show_link ) : ?>
            <a class="feature-link" href="<?php echo esc_url( $link_href ); ?>">
                <?php echo esc_html( $link_text ); ?>
            </a>
            <?php endif; ?>
        </div>

    <?php else : ?>

        <div class="<?php echo esc_attr( $text_class ); ?>">
            <?php if ( $tag ) : ?>
                <div class="feature-tag"><?php echo esc_html( $tag ); ?></div>
            <?php endif; ?>
            <h2 class="feature-h"><?php echo $heading_html; ?></h2>
            <p class="feature-body"><?php echo $body_html; ?></p>
            <?php if ( $show_link ) : ?>
            <a class="feature-link" href="<?php echo esc_url( $link_href ); ?>">
                <?php echo esc_html( $link_text ); ?>
            </a>
            <?php endif; ?>
        </div>

        <div class="feature-img">
            <?php
            $img_src = $image_url ?: denver17_placeholder( 800, 600, ( $tag ?: 'Section' ) . ' Photo' );
            ?>
            <img src="<?php echo esc_url( $img_src ); ?>"
                 alt="<?php echo esc_attr( $image_alt ); ?>">
        </div>

    <?php endif; ?>

</section>
