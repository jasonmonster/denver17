<?php
/**
 * Template Part: Pay Dues (PayPal)
 *
 * Called by the denver17/pay-dues block render callback with $args.
 *
 * $args:
 *   business   (string)  PayPal account email
 *   heading    (string)
 *   intro      (string)
 *   tiers      (array)   each: label, amount, amount_display, item_name, item_number
 *   note       (string)
 *   return_url (string)
 */

$business   = $args['business']   ?? '';
$heading    = $args['heading']    ?? '';
$intro      = $args['intro']      ?? '';
$tiers      = $args['tiers']      ?? [];
$note       = $args['note']       ?? '';
$return_url = $args['return_url'] ?? home_url( '/member-area/' );

if ( '' === $business || empty( $tiers ) ) {
    return;
}
?>

<div class="dues-pay">
    <?php if ( '' !== $heading ) : ?>
        <h3 class="dues-pay-h"><?php echo esc_html( $heading ); ?></h3>
    <?php endif; ?>

    <?php if ( '' !== $intro ) : ?>
        <p class="dues-pay-intro"><?php echo esc_html( $intro ); ?></p>
    <?php endif; ?>

    <div class="dues-pay-buttons">
        <?php foreach ( $tiers as $tier ) : ?>
            <form class="dues-pay-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" rel="noopener">
                <input type="hidden" name="cmd"           value="_xclick">
                <input type="hidden" name="business"      value="<?php echo esc_attr( $business ); ?>">
                <input type="hidden" name="lc"            value="US">
                <input type="hidden" name="currency_code" value="USD">
                <input type="hidden" name="item_name"     value="<?php echo esc_attr( $tier['item_name'] ); ?>">
                <?php if ( '' !== $tier['item_number'] ) : ?>
                    <input type="hidden" name="item_number" value="<?php echo esc_attr( $tier['item_number'] ); ?>">
                <?php endif; ?>
                <input type="hidden" name="amount"      value="<?php echo esc_attr( $tier['amount'] ); ?>">
                <input type="hidden" name="quantity"    value="1">
                <input type="hidden" name="shipping"    value="0">
                <input type="hidden" name="tax_rate"    value="0">
                <input type="hidden" name="no_shipping" value="1">
                <input type="hidden" name="rm"          value="1">
                <input type="hidden" name="return"      value="<?php echo esc_url( $return_url ); ?>">

                <button type="submit" class="dues-pay-btn">
                    <span class="dues-pay-btn-label"><?php echo esc_html( $tier['label'] ); ?></span>
                    <span class="dues-pay-btn-amount">$<?php echo esc_html( $tier['amount_display'] ); ?></span>
                </button>
            </form>
        <?php endforeach; ?>
    </div>

    <?php if ( '' !== $note ) : ?>
        <p class="dues-pay-note"><?php echo esc_html( $note ); ?></p>
    <?php endif; ?>
</div>
