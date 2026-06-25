<?php
/**
 * Template Part: Events Band
 *
 * Homepage events section. Currently static placeholder markup.
 *
 * TODO (Session 6 / plugin): Replace with a real events query once the
 * custom events plugin (CPT: d17_event) is in place. The plugin will expose
 * a function (e.g. denver17_upcoming_events(4)) returning the next N events
 * with featured images, dates, and titles. Swap the static $events array below
 * for that output.
 *
 * Grid layout expects:
 *   Column 1 — 1 large card
 *   Column 2 — 2 stacked small cards
 *   Column 3 — 1 large card
 */

$events = [
    [
        'date'      => 'Jun 19 &middot; Annual',
        'name'      => 'Elkstock Music Festival',
        'image_url' => '',
        'image_alt' => 'Elkstock festival',
        'size'      => 'large',
    ],
    [
        'date'      => 'Fridays',
        'name'      => 'Beer garden nights',
        'image_url' => '',
        'image_alt' => 'Beer garden at night',
        'size'      => 'small',
    ],
    [
        'date'      => 'Daily',
        'name'      => 'Happy hour',
        'image_url' => '',
        'image_alt' => 'Cocktails at the bar',
        'size'      => 'small',
    ],
    [
        'date'      => 'Jul 4 &middot; Holiday',
        'name'      => 'Fourth of July rooftop party',
        'image_url' => '',
        'image_alt' => 'Denver skyline at dusk',
        'size'      => 'large',
    ],
];
?>

<section class="events-band">

    <div class="eb-head">
        <div class="eb-tag">What&rsquo;s happening</div>
        <h2 class="eb-h">Upcoming at the lodge</h2>
    </div>

    <div class="eb-grid">

        <?php $card = $events[0]; ?>
        <div class="eb-card">
            <?php if ( $card['image_url'] ) : ?>
                <img src="<?php echo esc_url( $card['image_url'] ); ?>"
                     alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
            <?php endif; ?>
            <div class="eb-card-overlay">
                <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
            </div>
        </div>

        <div class="eb-col">
            <?php foreach ( [ $events[1], $events[2] ] as $card ) : ?>
                <div class="eb-card small">
                    <?php if ( $card['image_url'] ) : ?>
                        <img src="<?php echo esc_url( $card['image_url'] ); ?>"
                             alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
                    <?php endif; ?>
                    <div class="eb-card-overlay">
                        <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                        <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php $card = $events[3]; ?>
        <div class="eb-card">
            <?php if ( $card['image_url'] ) : ?>
                <img src="<?php echo esc_url( $card['image_url'] ); ?>"
                     alt="<?php echo esc_attr( $card['image_alt'] ); ?>">
            <?php endif; ?>
            <div class="eb-card-overlay">
                <div class="eb-date"><?php echo wp_kses_post( $card['date'] ); ?></div>
                <div class="eb-name"><?php echo esc_html( $card['name'] ); ?></div>
            </div>
        </div>

    </div>

</section>
