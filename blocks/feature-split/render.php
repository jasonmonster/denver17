<?php
/**
 * Block Render: denver17/feature-split
 */
function denver17_render_block_feature_split( $attributes ) {
    ob_start();
    get_template_part( 'template-parts/home/feature-split', null, [
        'tag'       => $attributes['tag']      ?? '',
        'heading'   => $attributes['heading']  ?? '',
        'body'      => $attributes['body']     ?? '',
        'link_text' => $attributes['linkText'] ?? '',
        'link_href' => $attributes['linkUrl']  ?? '#',
        'image_url' => $attributes['image']['url'] ?? '',
        'image_alt' => $attributes['image']['alt'] ?? '',
        'variant'   => $attributes['variant']  ?? 'dark',
        'layout'    => $attributes['layout']   ?? 'image-left',
    ] );
    return ob_get_clean();
}
