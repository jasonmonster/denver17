<?php
/**
 * Template Functions
 *
 * Helper functions used across templates.
 * Keep these presentational — no business logic, no plugin concerns.
 */

/**
 * Returns the phone number from Customizer.
 */
function denver17_get_phone() {
    return get_theme_mod( 'denver17_phone', '' );
}

/**
 * Returns the address from Customizer.
 */
function denver17_get_address() {
    return get_theme_mod( 'denver17_address', '' );
}

/**
 * Returns the Facebook URL from Customizer.
 */
function denver17_get_facebook() {
    return get_theme_mod( 'denver17_facebook', '' );
}

/**
 * Returns the Instagram URL from Customizer.
 */
function denver17_get_instagram() {
    return get_theme_mod( 'denver17_instagram', '' );
}

/**
 * Outputs social links as an <ul> if URLs are set.
 * Pass a $context string ('header' or 'footer') for BEM-style classes.
 */
function denver17_social_links( $context = 'footer' ) {
    $facebook  = denver17_get_facebook();
    $instagram = denver17_get_instagram();

    if ( ! $facebook && ! $instagram ) return;

    echo '<ul class="social-links social-links--' . esc_attr( $context ) . '">';
    if ( $facebook ) {
        echo '<li><a href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener noreferrer" aria-label="Facebook">Facebook</a></li>';
    }
    if ( $instagram ) {
        echo '<li><a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener noreferrer" aria-label="Instagram">Instagram</a></li>';
    }
    echo '</ul>';
}
