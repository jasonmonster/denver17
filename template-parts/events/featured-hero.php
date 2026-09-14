<?php
/**
 * Template Part: Events — Featured hero card
 *
 * Rendered by the denver17-events/featured-events block's render callback.
 * A single large event card (image one side, details the other) meant to sit
 * above the "next 10" carousel (template-parts/events/upcoming-list.php) on
 * the events/calendar page. Reuses the homepage carousel's badge and button
 * classes so the two components read as one system.
 *
 * $args:
 *   hero (array|null): title, date_label, time_label, location, link, image,
 *                       public, ticketed, excerpt — same row shape as the
 *                       upcoming-list carousel's $events entries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero = $args['hero'] ?? null;

if ( ! $hero ) {
	return;
}

$has_link = ! empty( $hero['link'] );
$img_src  = ! empty( $hero['image'] )
	? $hero['image']
	: denver17_placeholder( 1200, 750, $hero['title'] );
$when = trim( ( $hero['date_label'] ?? '' ) . ' · ' . ( $hero['time_label'] ?? '' ), ' ·' );
?>
<div class="fe-hero">

	<div class="fe-hero-media">
		<img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $hero['title'] ); ?>" loading="lazy">
		<?php if ( ! empty( $hero['public'] ) || ! empty( $hero['ticketed'] ) ) : ?>
			<div class="ev-badges">
				<?php if ( ! empty( $hero['public'] ) ) : ?>
					<span class="ev-badge ev-badge--public">Open to the Public</span>
				<?php endif; ?>
				<?php if ( ! empty( $hero['ticketed'] ) ) : ?>
					<span class="ev-badge ev-badge--ticketed">Tickets</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="fe-hero-body">
		<div class="fe-hero-eyebrow">Featured event</div>

		<h3 class="fe-hero-title">
			<?php if ( $has_link ) : ?>
				<a href="<?php echo esc_url( $hero['link'] ); ?>"><?php echo esc_html( $hero['title'] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $hero['title'] ); ?>
			<?php endif; ?>
		</h3>

		<?php if ( '' !== $when ) : ?>
			<div class="fe-hero-date"><?php echo esc_html( $when ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $hero['location'] ) ) : ?>
			<div class="fe-hero-loc"><?php echo esc_html( $hero['location'] ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $hero['excerpt'] ) ) : ?>
			<p class="fe-hero-desc"><?php echo esc_html( $hero['excerpt'] ); ?></p>
		<?php endif; ?>

		<?php if ( $has_link ) : ?>
			<a class="eb-view-all-btn fe-hero-btn" href="<?php echo esc_url( $hero['link'] ); ?>">
				Learn more<span class="eb-view-all-arrow" aria-hidden="true">&rarr;</span>
			</a>
		<?php endif; ?>
	</div>

</div>
