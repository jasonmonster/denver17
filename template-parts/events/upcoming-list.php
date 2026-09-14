<?php
/**
 * Template Part: Events — Upcoming list (homepage events band)
 *
 * Rendered by the denver17-events/upcoming-list block's render callback, which
 * passes redaction-safe rows in $args. Presentation lives here (theme owns
 * rendering; the plugin owns the query + redaction). Replaces the old static
 * mosaic (template-parts/home/events-band.php) with a horizontal card row that
 * becomes a scroll/arrow carousel once the cards overflow their track.
 *
 * Cards are light (WOW-style): a 16:9 feature image on top, then title, date,
 * location, an optional blurb, and a "Learn more" button when the event has a
 * public page. Private Bookings arrive already redacted (no link, no image,
 * no blurb) — they render as a plain, non-linked card.
 *
 * $args:
 *   section_heading (string)
 *   events (array of rows: title, date_label, time_label, location, link,
 *           image, public, ticketed, excerpt)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_heading = $args['section_heading'] ?? 'Upcoming at the lodge';
$events          = $args['events'] ?? [];
// Three opt-out flags, all defaulting to the original homepage behavior, so
// this template part can also render as the "next 10" row nested inside the
// featured-events block (which supplies its own band background + single
// heading and would otherwise get a duplicated purple band + duplicated
// heading). See blocks/featured-events. (punch list, added 2026-09-08)
$show_heading  = $args['show_heading'] ?? true;
$show_view_all = $args['show_view_all'] ?? true;
$wrap_section  = $args['wrap_section'] ?? true;
$tag           = $wrap_section ? 'section' : 'div';
?>
<<?php echo $tag; ?><?php echo $wrap_section ? ' id="upcoming-events" class="events-band elks17-upcoming"' : ' class="elks17-upcoming"'; ?>>

	<?php if ( $show_heading ) : ?>
	<div class="eb-head">
		<div class="eb-tag">What&rsquo;s happening</div>
		<h2 class="eb-h"><?php echo esc_html( $section_heading ); ?></h2>
	</div>
	<?php endif; ?>

	<?php if ( empty( $events ) ) : ?>

		<p class="eb-empty">No upcoming events right now &mdash; check back soon.</p>

	<?php else : ?>

		<div class="eb-carousel" data-eb-carousel>

			<button type="button" class="eb-nav eb-nav--prev" data-eb-prev aria-label="Show previous events" hidden>
				<span aria-hidden="true">&lsaquo;</span>
			</button>

			<ul class="eb-track" data-eb-track>
				<?php
				foreach ( $events as $ev ) :
					$has_link = ! empty( $ev['link'] );
					$img_src  = ! empty( $ev['image'] )
						? $ev['image']
						: denver17_placeholder( 800, 450, $ev['title'] );
					$when = trim( ( $ev['date_label'] ?? '' ) . ' · ' . ( $ev['time_label'] ?? '' ), ' ·' );
					?>
					<li class="ev-card">

						<div class="ev-media">
							<img src="<?php echo esc_url( $img_src ); ?>"
								alt="<?php echo esc_attr( $ev['title'] ); ?>" loading="lazy">
							<?php if ( ! empty( $ev['public'] ) || ! empty( $ev['ticketed'] ) ) : ?>
								<div class="ev-badges">
									<?php if ( ! empty( $ev['public'] ) ) : ?>
										<span class="ev-badge ev-badge--public">Open to the Public</span>
									<?php endif; ?>
									<?php if ( ! empty( $ev['ticketed'] ) ) : ?>
										<span class="ev-badge ev-badge--ticketed">Tickets</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<div class="ev-body">
							<?php if ( '' !== $when ) : ?>
								<div class="ev-date"><?php echo esc_html( $when ); ?></div>
							<?php endif; ?>

							<h3 class="ev-title">
								<?php if ( $has_link ) : ?>
									<a href="<?php echo esc_url( $ev['link'] ); ?>"><?php echo esc_html( $ev['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $ev['title'] ); ?>
								<?php endif; ?>
							</h3>

							<?php if ( ! empty( $ev['location'] ) ) : ?>
								<div class="ev-loc"><?php echo esc_html( $ev['location'] ); ?></div>
							<?php endif; ?>

							<?php if ( ! empty( $ev['excerpt'] ) ) : ?>
								<p class="ev-desc"><?php echo esc_html( $ev['excerpt'] ); ?></p>
							<?php endif; ?>

							<?php if ( $has_link ) : ?>
								<a class="ev-btn" href="<?php echo esc_url( $ev['link'] ); ?>">
									Learn more<span class="ev-btn-arrow" aria-hidden="true">&rarr;</span>
								</a>
							<?php endif; ?>
						</div>

					</li>
				<?php endforeach; ?>
			</ul>

			<button type="button" class="eb-nav eb-nav--next" data-eb-next aria-label="Show more events" hidden>
				<span aria-hidden="true">&rsaquo;</span>
			</button>

		</div>

	<?php endif; ?>

	<?php if ( $show_view_all ) : ?>
	<div class="eb-view-all">
		<a class="eb-view-all-btn" href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
			View All Events<span class="eb-view-all-arrow" aria-hidden="true">&rarr;</span>
		</a>
	</div>
	<?php endif; ?>

</<?php echo $tag; ?>>
