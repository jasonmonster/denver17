<?php
/**
 * Block Render: denver17/pay-dues
 *
 * Temporary PayPal dues buttons, carried over from the old site. Uses PayPal's
 * legacy _xclick form so it works without any PayPal-side button setup.
 * Retire this block when Stripe Checkout ships.
 */

/**
 * PayPal account dues payments land in.
 *
 * Filterable so it can move to the Customizer later without touching this file.
 */
function denver17_paypal_business_email() {
    return (string) apply_filters( 'denver17_paypal_business_email', 'info@denverelks.org' );
}

/**
 * Normalize one tier's attributes into a template-ready row.
 * Returns null when the tier has no usable amount, so empty tiers drop out.
 */
function denver17_pay_dues_tier( $label, $amount, $item_name, $item_number ) {
    $clean = preg_replace( '/[^0-9.]/', '', (string) $amount );

    if ( '' === $clean || (float) $clean <= 0 ) {
        return null;
    }

    $value = (float) $clean;

    return [
        'label'          => (string) $label,
        'amount'         => number_format( $value, 2, '.', '' ),
        'amount_display' => number_format( $value, ( fmod( $value, 1 ) === 0.0 ) ? 0 : 2, '.', ',' ),
        'item_name'      => (string) $item_name,
        'item_number'    => (string) $item_number,
    ];
}

function denver17_render_block_pay_dues( $attributes ) {
    $tiers = array_values( array_filter( [
        denver17_pay_dues_tier(
            $attributes['tier1Label']      ?? '',
            $attributes['tier1Amount']     ?? '',
            $attributes['tier1ItemName']   ?? '',
            $attributes['tier1ItemNumber'] ?? ''
        ),
        denver17_pay_dues_tier(
            $attributes['tier2Label']      ?? '',
            $attributes['tier2Amount']     ?? '',
            $attributes['tier2ItemName']   ?? '',
            $attributes['tier2ItemNumber'] ?? ''
        ),
    ] ) );

    ob_start();
    get_template_part( 'template-parts/page/pay-dues', null, [
        'business'   => denver17_paypal_business_email(),
        'heading'    => $attributes['heading'] ?? '',
        'intro'      => $attributes['intro']   ?? '',
        'tiers'      => $tiers,
        'note'       => $attributes['note']    ?? '',
        'return_url' => home_url( '/member-area/' ),
    ] );
    return ob_get_clean();
}
