<?php
/**
 * Block Render: denver17/membership-steps
 */
function denver17_render_block_membership_steps( $attributes ) {
    $steps = [
        [
            'num'       => 'Step 1',
            'title'     => $attributes['step1Title']        ?? '',
            'body'      => $attributes['step1Body']         ?? '',
            'image_url' => $attributes['step1Image']['url'] ?? '',
            'image_alt' => $attributes['step1Image']['alt'] ?? '',
        ],
        [
            'num'       => 'Step 2',
            'title'     => $attributes['step2Title']        ?? '',
            'body'      => $attributes['step2Body']         ?? '',
            'image_url' => $attributes['step2Image']['url'] ?? '',
            'image_alt' => $attributes['step2Image']['alt'] ?? '',
        ],
        [
            'num'       => 'Step 3',
            'title'     => $attributes['step3Title']        ?? '',
            'body'      => $attributes['step3Body']         ?? '',
            'image_url' => $attributes['step3Image']['url'] ?? '',
            'image_alt' => $attributes['step3Image']['alt'] ?? '',
        ],
    ];

    ob_start();
    get_template_part( 'template-parts/home/membership-steps', null, [
        'section_tag'     => $attributes['sectionTag']     ?? 'Membership',
        'section_heading' => $attributes['sectionHeading'] ?? 'From guest to lodge family',
        'steps'           => $steps,
    ] );
    return ob_get_clean();
}
