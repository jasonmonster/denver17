<?php
/**
 * Theme Options — Customizer
 *
 * Exposes global design tokens only: colors, contact info, social links.
 * Layout and design decisions are not configurable by design.
 * For content changes, use the WordPress editor.
 */

function denver17_customizer( $wp_customize ) {

    // -------------------------------------------------------------------------
    // Section: Brand Colors
    // -------------------------------------------------------------------------
    $wp_customize->add_section( 'denver17_colors', [
        'title'    => __( 'Brand Colors', 'denver17' ),
        'priority' => 30,
    ] );

    // Primary color (elk purple/maroon)
    $wp_customize->add_setting( 'denver17_color_primary', [
        'default'           => '#5B1A1A',
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

    // Accent color
    $wp_customize->add_setting( 'denver17_color_accent', [
        'default'           => '#C9A84C',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'denver17_color_accent',
        [
            'label'   => __( 'Accent Color', 'denver17' ),
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

    $social_fields = [
        'denver17_facebook'  => 'Facebook URL',
        'denver17_instagram' => 'Instagram URL',
        'denver17_phone'     => 'Phone Number',
        'denver17_address'   => 'Address',
    ];

    foreach ( $social_fields as $key => $label ) {
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
}
add_action( 'customize_register', 'denver17_customizer' );


/**
 * Output CSS custom properties from Customizer values.
 * This makes colors available as --color-primary etc. in CSS.
 */
function denver17_customizer_css() {
    $primary = get_theme_mod( 'denver17_color_primary', '#5B1A1A' );
    $accent  = get_theme_mod( 'denver17_color_accent', '#C9A84C' );
    ?>
    <style>
        :root {
            --color-primary: <?php echo esc_attr( $primary ); ?>;
            --color-accent:  <?php echo esc_attr( $accent ); ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'denver17_customizer_css' );
