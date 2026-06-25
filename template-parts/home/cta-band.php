<?php
/**
 * Template Part: CTA Band
 *
 * Called by the denver17/cta-band block render callback with $args.
 *
 * $args:
 *   eyebrow     (string)
 *   heading     (string)
 *   button_text (string)
 *   button_url  (string)
 */

$eyebrow     = $args['eyebrow']     ?? 'Mother Lodge of the Rockies';
$heading     = $args['heading']     ?? 'Come see what #17 is about.';
$button_text = $args['button_text'] ?? 'Plan your visit';
$button_url  = $args['button_url']  ?? home_url( '/visit/' );
?>

<section class="cta-band">
    <div class="cta-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
    <h2 class="cta-h"><?php echo esc_html( $heading ); ?></h2>
    <a class="cta-btn" href="<?php echo esc_url( $button_url ); ?>">
        <?php echo esc_html( $button_text ); ?>
    </a>
</section>
