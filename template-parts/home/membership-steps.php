<?php
/**
 * Template Part: Membership Steps
 *
 * Three-step membership funnel section.
 * Step images are set via Customizer → Homepage Images.
 */

$steps = [
    [
        'num'       => 'Step 1',
        'title'     => 'Stop in as a guest',
        'body'      => 'Visit the bar, meet some members, see what Jolly Corks is about. No pressure. Just cold drinks and real people.',
        'image_url' => get_theme_mod( 'denver17_step1_img', '' ),
        'image_alt' => 'Members at the bar',
    ],
    [
        'num'       => 'Step 2',
        'title'     => 'Apply for membership',
        'body'      => 'Open to all. A short application, a sponsor from inside the lodge, and you&rsquo;re on your way. Most people wonder why they waited.',
        'image_url' => get_theme_mod( 'denver17_step2_img', '' ),
        'image_alt' => 'Member celebrating at the lodge',
    ],
    [
        'num'       => 'Step 3',
        'title'     => 'Get your member number',
        'body'      => 'Member pricing on drinks, golf simulator access, invites to events, and a lodge that&rsquo;s had your back since 1882.',
        'image_url' => get_theme_mod( 'denver17_step3_img', '' ),
        'image_alt' => 'Members having fun at the lodge',
    ],
];
?>

<section class="steps-wrap">

    <div class="steps-tag">Membership</div>
    <h2 class="steps-h">From guest to lodge family</h2>

    <div class="steps-grid">
        <?php foreach ( $steps as $step ) : ?>
            <div class="step">
                <?php if ( $step['image_url'] ) : ?>
                    <img class="step-photo"
                         src="<?php echo esc_url( $step['image_url'] ); ?>"
                         alt="<?php echo esc_attr( $step['image_alt'] ); ?>">
                <?php endif; ?>
                <div class="step-num"><?php echo esc_html( $step['num'] ); ?></div>
                <h3 class="step-title"><?php echo esc_html( $step['title'] ); ?></h3>
                <p class="step-body"><?php echo wp_kses_post( $step['body'] ); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

</section>
