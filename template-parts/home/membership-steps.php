<?php
/**
 * Template Part: Membership Steps
 *
 * Called by the denver17/membership-steps block render callback with $args.
 *
 * $args:
 *   section_tag     (string)
 *   section_heading (string)
 *   steps           (array) — each item: num, title, body, image_url, image_alt
 */

$section_tag     = $args['section_tag']     ?? 'Membership';
$section_heading = $args['section_heading'] ?? 'From guest to lodge family';
$steps           = $args['steps']           ?? [];
?>

<section class="steps-wrap">

    <div class="steps-tag"><?php echo esc_html( $section_tag ); ?></div>
    <h2 class="steps-h"><?php echo esc_html( $section_heading ); ?></h2>

    <div class="steps-grid">
        <?php foreach ( $steps as $step ) : ?>
            <div class="step">
                <?php
                $step_img = $step['image_url'] ?: denver17_placeholder( 600, 450, ( $step['title'] ?? 'Step' ) . ' Photo' );
                ?>
                <img class="step-photo"
                     src="<?php echo esc_url( $step_img ); ?>"
                     alt="<?php echo esc_attr( $step['image_alt'] ?? '' ); ?>">
                <div class="step-num"><?php echo esc_html( $step['num'] ); ?></div>
                <h3 class="step-title"><?php echo esc_html( $step['title'] ); ?></h3>
                <p class="step-body"><?php echo nl2br( esc_html( $step['body'] ) ); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

</section>
