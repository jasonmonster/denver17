<?php
/**
 * Template Part: Mobile Menu Drawer
 */

$facebook  = get_theme_mod( 'denver17_facebook', '' );
$instagram = get_theme_mod( 'denver17_instagram', '' );
?>

<div class="mobile-menu-backdrop" id="mobileMenuBackdrop" aria-hidden="true"></div>

<div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Navigation menu">

    <div class="mobile-menu-head">
        <?php if ( has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <img class="mobile-menu-logo"
                     src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>"
                     alt="<?php bloginfo( 'name' ); ?>">
            </a>
        <?php endif; ?>
        <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">&#x2715;</button>
    </div>

    <div class="mobile-menu-links">
        <?php
        wp_nav_menu( [
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'walker'         => new Denver17_Mobile_Nav_Walker(),
            'fallback_cb'    => false,
        ] );
        ?>
    </div>

    <a class="mobile-menu-cta" href="<?php echo esc_url( home_url( '/member-area/' ) ); ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"/></svg>
        Member Area
    </a>

    <?php if ( $facebook || $instagram ) : ?>
        <div class="mobile-menu-social">
            <?php if ( $facebook ) : ?>
                <a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33V22c4.78-.79 8.44-4.94 8.44-9.94z"/></svg>
                </a>
            <?php endif; ?>
            <?php if ( $instagram ) : ?>
                <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
