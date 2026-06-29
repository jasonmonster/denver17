<?php
/**
 * Block Render: denver17/hours-display
 *
 * Pulls live hours from denver17_get_hours_data() (inc/hours-feed.php)
 * and computes open/closed status server-side. No JS required on inner pages
 * since page caching is disabled for authenticated/dynamic pages.
 *
 * Status modifier classes on the wrapper:
 *   .hours-display--open       currently open
 *   .hours-display--opens-at   open today but not yet
 *   .hours-display--closed     closed today
 */
function denver17_render_block_hours_display( $attributes ) {
    $data = denver17_get_hours_data();

    $open_time  = $data['open_time']  ?? '';
    $close_time = $data['close_time'] ?? '';
    $special    = $data['special']    ?? '';
    $display_1  = $data['display_1']  ?? '';
    $display_2  = $data['display_2']  ?? '';

    // ── Compute status in WP's local timezone (server runs UTC) ──────────────
    $wp_tz     = wp_timezone();
    $now_local = new DateTime( 'now', $wp_tz );
    $now_decimal = (float) $now_local->format( 'G' ) + (float) $now_local->format( 'i' ) / 60;

    $open_decimal = null;
    if ( $open_time ) {
        $parts        = explode( ':', $open_time );
        $open_decimal = (int) $parts[0] + (int) ( $parts[1] ?? 0 ) / 60;
    }

    $is_open_today = $open_decimal !== null;
    $is_open_now   = $is_open_today && $now_decimal >= $open_decimal;

    // Format open time for display (e.g. "17:30" → "5:30 PM")
    $open_fmt  = '';
    $close_fmt = 'Close';
    if ( $open_time ) {
        $open_ts  = strtotime( $open_time );
        $open_fmt = $open_ts ? date( 'g:i A', $open_ts ) : $open_time;
    }
    if ( $close_time ) {
        $close_ts  = strtotime( $close_time );
        $close_fmt = $close_ts ? date( 'g:i A', $close_ts ) : $close_time;
    }

    if ( ! $is_open_today ) {
        $status      = 'closed';
        $status_text = 'Closed today';
        $range       = '';
    } elseif ( $is_open_now ) {
        $status      = 'open';
        $status_text = 'We&rsquo;re open';
        // When no fixed close time, "Open until close" is awkward — show open time instead.
        $range = $close_time
            ? 'Open until ' . esc_html( $close_fmt )
            : 'Open at ' . esc_html( $open_fmt );
    } else {
        $status      = 'opens-at';
        $status_text = 'Opens at ' . esc_html( $open_fmt );
        $range       = esc_html( $open_fmt ) . '&ndash;' . esc_html( $close_fmt );
    }

    // ── Today's date label ────────────────────────────────────────────────────
    $date_label = $now_local->format( 'l, F j' );

    // ── Attribute flags ───────────────────────────────────────────────────────
    $show_status     = (bool) ( $attributes['showStatus']    ?? true );
    $show_special    = (bool) ( $attributes['showSpecial']   ?? true );
    $show_base_hours = (bool) ( $attributes['showBaseHours'] ?? true );
    $show_note       = (bool) ( $attributes['showNote']      ?? true );
    $heading         = trim( $attributes['heading'] ?? '' );

    ob_start();
    ?>
    <div class="hours-display hours-display--<?php echo esc_attr( $status ); ?>">

        <?php if ( $heading ) : ?>
            <h2 class="hours-display__heading"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <?php if ( $show_status ) : ?>
            <div class="hours-display__status-row">
                <span class="hours-display__dot" aria-hidden="true"></span>
                <span class="hours-display__status-text"><?php echo $status_text; ?></span>
            </div>
        <?php endif; ?>

        <div class="hours-display__date"><?php echo esc_html( $date_label ); ?></div>

        <?php if ( $range ) : ?>
            <div class="hours-display__range"><?php echo $range; ?></div>
        <?php endif; ?>

        <?php if ( $show_special && $special ) : ?>
            <div class="hours-display__special"><?php echo esc_html( $special ); ?></div>
        <?php endif; ?>

        <?php if ( $show_base_hours && ( $display_1 || $display_2 ) ) : ?>
            <div class="hours-display__base-hours">
                <?php if ( $display_1 ) : ?>
                    <div class="hours-display__base-line"><?php echo esc_html( $display_1 ); ?></div>
                <?php endif; ?>
                <?php if ( $display_2 ) : ?>
                    <div class="hours-display__base-line"><?php echo esc_html( $display_2 ); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $show_note ) : ?>
            <p class="hours-display__note">Hours subject to change for special events.</p>
            <p class="hours-display__note">Closing time is at bartender&rsquo;s discretion.</p>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
