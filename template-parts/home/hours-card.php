<?php
/**
 * Template Part: Hours Card
 *
 * Renders the live-status hours card overlaid on the hero.
 * The open/closed state is computed client-side by main.js.
 *
 * TODO (post-launch): Replace hardcoded schedule with a real data source —
 * e.g. an ACF options field or a custom hours CPT — so staff can update
 * hours without a code deploy.
 */
?>

<div class="hours-card">
    <div class="hours-status-row" id="hoursStatusRow">
        <span class="hours-dot" aria-hidden="true"></span>
        <span class="hours-status" id="hoursStatus">Hours</span>
    </div>
    <div class="hours-date" id="hoursDate">Today</div>
    <div class="hours-today-range" id="hoursRange">Tue&ndash;Sat &middot; 5:30PM&ndash;Close</div>
    <div class="hours-divider" aria-hidden="true"></div>
    <div class="hours-week">Tue&ndash;Sat &middot; 5:30PM&ndash;Close</div>
    <p class="hours-note">Hours subject to change for special events.</p>
    <a class="hours-link" href="<?php echo esc_url( home_url( '/visit/' ) ); ?>">
        See full hours &rarr;
    </a>
</div>
