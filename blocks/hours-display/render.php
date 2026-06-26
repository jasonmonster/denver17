<?php
/**
 * Block Render: denver17/hours-display
 *
 * Pulls live hours from denver17_get_hours_data() (inc/hours-feed.php)
 * and computes open/closed status server-side. No JS required on inner pages
 * since page caching is disabled for authenticated/dynamic pages.
 *
 * Attributes control which sections render — design can be refined later
 * by styling .hours-display and its children in main.css without touching
 * this file or the block attributes.
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

    // ── Compute status server-side ────────────────────────────────────────────
    $open_decimal = null;
    if ( $open_time ) {
        $parts        = explode( ':', $open_time );
        $open_decimal = (int) $parts[0] + (int) ( $parts[1] ?? 0 ) / 60;
    }

    $now_ts      = current_time( 'timestamp' );
    $now_decimal = (float) date( 'G', $now_ts ) + (float) date( 'i', $now_ts ) / 60;

    $is_open_today = $open_decimal !== null;
    $is_open_now   = $is_open_today && $now_decimal >= $open_decimal;

    if ( ! $is_open_today ) {
        $status      = 'closed';
        $status_text = 'Closed today';
    } elseif ( $is_open_now ) {
        $status      = 'open';
        $status_text = 'We&rsquo;re open';
    } else {
        $status      = 'opens-at';
        $open_ts     = strtotime( $open_time );
        $open_fmt    = $open_ts ? date( 'g:i A', $open_ts ) : $open_time;
        $status_text = 'Opens at ' . esc_html( $open_fmt );
    }

    // ── Format today's time range ─────────────────────────────────────────────
    $range = '';
    if ( $open_time ) {
        $open_ts  = strtotime( $open_time );
        $open_fmt = $open_ts ? date( 'g:i A', $open_ts ) : $open_time;

        if ( $close_time ) {
            $close_ts  = strtotime( $close_time );
            $close_fmt = $close_ts ? date( 'g:i A', $close_ts ) : $close_time;
        } else {
            $close_fmt = 'Close';
        }

        $range = esc_html( $open_fmt ) . '&ndash;' . esc_html( $close_fmt );
    }

    // ── Today's date label ────────────────────────────────────────────────────
    $date_label = date_i18n( 'l, F j', $now_ts );

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
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}
