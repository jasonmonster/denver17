<?php
/**
 * Template Part: Hours Card
 *
 * All hours data is supplied by inc/hours-feed.php → wp_localize_script
 * as window.denver17Hours. The JS in main.js handles every dynamic
 * element: date display, open/closed state, time range, special notice,
 * and base hours lines. The PHP here is structure only.
 */
?>

<div class="hours-card">

    <!-- Status row: JS adds is-open / is-opens-at / is-closed to this element -->
    <div class="hours-status-row" id="hoursStatusRow">
        <span class="hours-dot" aria-hidden="true"></span>
        <span class="hours-status" id="hoursStatus">Hours</span>
    </div>

    <!-- Dynamic date e.g. "Friday, Jun 27" — written by JS -->
    <div class="hours-date" id="hoursDate">Today</div>

    <!-- Large time display: today's hours or "open until" text — written by JS -->
    <div class="hours-today-range" id="hoursRange">&nbsp;</div>

    <!-- Special notice from spreadsheet column D; hidden until JS populates it -->
    <div class="hours-special" id="hoursSpecial" hidden></div>

    <div class="hours-divider" aria-hidden="true"></div>

    <!-- Base schedule display lines from spreadsheet Tab 2 — written by JS -->
    <div class="hours-base" id="hoursBase"></div>
    <div class="hours-base hours-base--2" id="hoursBase2" hidden></div>

    <p class="hours-note">Hours subject to change for special events.</p>
    <p class="hours-note">Closing time is at bartender&rsquo;s discretion.</p>

    <a class="hours-link" href="<?php echo esc_url( home_url( '/visit/' ) ); ?>">
        See full hours &rarr;
    </a>

</div>
