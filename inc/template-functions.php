<?php
/**
 * Template Functions
 *
 * Helper/utility functions used across templates.
 * Keep these presentational — no business logic, no plugin concerns.
 */

/**
 * Returns a placehold.co URL for staging use when no real image has been uploaded.
 * Uses the brand purple palette so placeholders are clearly on-theme and
 * distinguishable from real photos during client review.
 *
 * @param int    $width   Image width in pixels.
 * @param int    $height  Image height in pixels.
 * @param string $text    Human-readable label describing the expected photo.
 * @return string
 */
function denver17_placeholder( $width, $height, $text = '' ) {
    $base = 'https://placehold.co/' . (int) $width . 'x' . (int) $height . '/3c3489/CECBF6';
    return $text ? $base . '?text=' . rawurlencode( $text ) : $base;
}

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
 * Outputs social icon links.
 *
 * @param string $context  'nav' or 'footer' — drives CSS class and icon size.
 */
function denver17_social_links( $context = 'footer' ) {
    $facebook  = denver17_get_facebook();
    $instagram = denver17_get_instagram();

    if ( ! $facebook && ! $instagram ) {
        return;
    }

    $size = ( $context === 'nav' ) ? '17' : '18';

    $fb_icon = '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33V22c4.78-.79 8.44-4.94 8.44-9.94z"/></svg>';

    $ig_icon = '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>';

    echo '<div class="social-links social-links--' . esc_attr( $context ) . '">';

    if ( $facebook ) {
        echo '<a href="' . esc_url( $facebook ) . '" target="_blank" rel="noopener noreferrer" aria-label="Facebook">' . $fb_icon . '</a>';
    }

    if ( $instagram ) {
        echo '<a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener noreferrer" aria-label="Instagram">' . $ig_icon . '</a>';
    }

    echo '</div>';
}
