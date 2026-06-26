<?php
/**
 * Template Part: Events Band
 *
 * Static placeholder until the events plugin is live (Session 6).
 * When the plugin is ready, swap the $events array for a real query.
 *
 * $args:
 *   section_heading (string)
 */

$section_heading = $args['section_heading'] ?? 'Upcoming at the lodge';

$events = [
    [
        'date'      => 'Jun 19 &middot; Annual',
        'name'      => 'Elkstock Music Festival',
        'image_url' => '',
        'image_alt' => 'Elkstock Music Festival',
        'size'      => 'large',
    ],
    [
        'date'      => 'Fridays',
        'name'      => 'Beer garden nights',
        'image_url' => '',
        'image_alt' => 'Beer Garden at Night',
        'size'      => 'small',
    ],
    [
        'date'      => 'Daily',
        'name'      => 'Happy hour',
        'image_url' => '',
        'image_alt' => 'Happy Hour Cocktails',
        'size'      => 'small',
    ],
    [
        'date'      => 'Jul 4 &middot; Holiday',
        'name'      => 'Fourth of July Rooftop Party',
        'image_url' => '',
        'image_alt' => 'Denver Skyline at Dusk',
        'size'      => 'large',
    ],
];
?>

<section class="events-band">

    <div class="eb-head">
        <div class="eb-tag">What&rsquo;s happening</div>
        <h2 class="eb-h"><?php echo esc_html( $section_heading ); ?></h2>
    </div>

    <div class="eb-grid">

        <?php
        $card    = $events[0];
        $img_src = $card['image_url'] ?: denver17_placeholder( 800, 600, $card['image_alt'] );
        ?>
        <div class="eb-card">
            <img src="<?php echo esc_url( $img_src ); ?>"
                 alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
            <div class="eb-card-overlay">
                <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
            </div>
        </div>

        <div class="eb-col">
            <?php foreach ( [ $events[1], $events[2] ] as $card ) :
                $img_src = $card['image_url'] ?: denver17_placeholder( 800, 400, $card['image_alt'] );
            ?>
                <div class="eb-card small">
                    <img src="<?php echo esc_url( $img_src ); ?>"
                         alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
                    <div class="eb-card-overlay">
                        <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                        <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php
        $card    = $events[3];
        $img_src = $card['image_url'] ?: denver17_placeholder( 800, 600, $card['image_alt'] );
        ?>
        <div class="eb-card">
            <img src="<?php echo esc_url( $img_src ); ?>"
                 alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
            <div class="eb-card-overlay">
                <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
            </div>
        </div>

    </div>

</section>
