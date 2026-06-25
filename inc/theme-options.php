<?php
/**
 * Theme Options — Customizer
 *
 * Exposes global design tokens only: colors, contact info, social links,
 * and homepage section images. Layout and design decisions are not
 * configurable by design. For content changes, use the WordPress editor.
 */

function denver17_customizer( $wp_customize ) {

    // -------------------------------------------------------------------------
    // Section: Brand Colors
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'denver17_colors', [
        'title'    => __( 'Brand Colors', 'denver17' ),
        'priority' => 30,
    ] );

    $wp_customize->add_setting( 'denver17_color_primary', [
        'default'           => '#26215C',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'denver17_color_primary',
        [
            'label'   => __( 'Primary Color', 'denver17' ),
            'section' => 'denver17_colors',
        ]
    ) );

    $wp_customize->add_setting( 'denver17_color_accent', [
        'default'           => '#C59B3A',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'denver17_color_accent',
        [
            'label'   => __( 'Accent / Gold Color', 'denver17' ),
            'section' => 'denver17_colors',
        ]
    ) );

    // -------------------------------------------------------------------------
    // Section: Contact & Social
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'denver17_contact', [
        'title'    => __( 'Contact & Social', 'denver17' ),
        'priority' => 40,
    ] );

    $contact_fields = [
        'denver17_facebook'  => 'Facebook URL',
        'denver17_instagram' => 'Instagram URL',
        'denver17_phone'     => 'Phone Number',
        'denver17_address'   => 'Address',
    ];

    foreach ( $contact_fields as $key => $label ) {
        $wp_customize->add_setting( $key, [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        $wp_customize->add_control( $key, [
            'label'   => __( $label, 'denver17' ),
            'section' => 'denver17_contact',
            'type'    => 'text',
        ] );
    }

    // -------------------------------------------------------------------------
    // Section: Homepage Images
    // All section images live in the media library and are set here.
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'denver17_homepage_images', [
        'title'    => __( 'Homepage Images', 'denver17' ),
        'priority' => 50,
    ] );

    $homepage_images = [
        'denver17_hero_bg'               => 'Hero — Background Image',
        'denver17_feature_bar_img'       => 'Feature: Bar — Image',
        'denver17_feature_community_img' => 'Feature: Community — Image',
        'denver17_step1_img'             => 'Membership Step 1 — Image',
        'denver17_step2_img'             => 'Membership Step 2 — Image',
        'denver17_step3_img'             => 'Membership Step 3 — Image',
    ];

    foreach ( $homepage_images as $key => $label ) {
        $wp_customize->add_setting( $key, [
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $wp_customize->add_control( new WP_Customize_Image_Control(
            $wp_customize,
            $key,
            [
                'label'   => __( $label, 'denver17' ),
                'section' => 'denver17_homepage_images',
            ]
        ) );
    }
}
add_action( 'customize_register', 'denver17_customizer' );


/**
 * Output CSS custom properties from Customizer values.
 * The purple token palette is hardcoded in main.css.
 * --color-accent is referenced there via var(--color-accent, #C59B3A).
 */
function denver17_customizer_css() {
    $primary = get_theme_mod( 'denver17_color_primary', '#26215C' );
    $accent  = get_theme_mod( 'denver17_color_accent',  '#C59B3A' );
    ?>
    <style id="denver17-customizer-css">
        :root {
            --color-primary: <?php echo esc_attr( $primary ); ?>;
            --color-accent:  <?php echo esc_attr( $accent );  ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'denver17_customizer_css' );
