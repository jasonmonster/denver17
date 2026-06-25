<?php
/**
 * Block Render: denver17/cta-band
 */
function denver17_render_block_cta_band( $attributes ) {
    ob_start();
    get_template_part( 'template-parts/home/cta-band', null, [
        'eyebrow'     => $attributes['eyebrow']    ?? '',
        'heading'     => $attributes['heading']    ?? '',
        'button_text' => $attributes['buttonText'] ?? '',
        'button_url'  => $attributes['buttonUrl']  ?? home_url( '/visit/' ),
    ] );
    return ob_get_clean();
}
