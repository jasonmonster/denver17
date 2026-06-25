<?php
/**
 * Block Render: denver17/events-band
 */
function denver17_render_block_events_band( $attributes ) {
    ob_start();
    get_template_part( 'template-parts/home/events-band', null, [
        'section_heading' => $attributes['sectionHeading'] ?? 'Upcoming at the lodge',
    ] );
    return ob_get_clean();
}
