<?php
/**
 * Block Render: denver17/hero
 */
function denver17_render_block_hero( $attributes ) {
    ob_start();
    get_template_part( 'template-parts/home/hero', null, [
        'bg_image'      => $attributes['backgroundImage']['url'] ?? '',
        'bg_image_alt'  => $attributes['backgroundImage']['alt'] ?? '',
        'eyebrow'       => $attributes['eyebrow']      ?? '',
        'heading_line1' => $attributes['headingLine1'] ?? '',
        'heading_line2' => $attributes['headingLine2'] ?? '',
        'subtext'       => $attributes['subtext']      ?? '',
        'cta_text'      => $attributes['ctaText']      ?? '',
        'cta_url'       => $attributes['ctaUrl']       ?? home_url( '/events/' ),
    ] );
    return ob_get_clean();
}
