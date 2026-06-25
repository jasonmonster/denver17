/**
 * Denver Elks Lodge #17 — Main JS
 * Loaded in footer via wp_enqueue_script (no defer needed).
 */

( function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Hours Card
  //
  // Client-side open/closed logic against a hardcoded schedule.
  // TODO (post-launch): Replace with a real data source so staff can update
  // hours without a code deploy.
  // ---------------------------------------------------------------------------

  var OPEN_DAY_START = 2;   // Tuesday
  var OPEN_DAY_END   = 6;   // Saturday
  var OPEN_HOUR      = 17.5; // 5:30 PM

  var DAY_NAMES   = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
  var MONTH_NAMES = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

  function initHoursCard() {
    var row     = document.getElementById( 'hoursStatusRow' );
    var statusEl = document.getElementById( 'hoursStatus' );
    var dateEl   = document.getElementById( 'hoursDate' );
    var rangeEl  = document.getElementById( 'hoursRange' );

    if ( ! row ) return;

    var now        = new Date();
    var day        = now.getDay();
    var hour       = now.getHours() + now.getMinutes() / 60;
    var isOpenDay  = day >= OPEN_DAY_START && day <= OPEN_DAY_END;
    var isOpenNow  = isOpenDay && hour >= OPEN_HOUR;

    dateEl.textContent = DAY_NAMES[ day ] + ', ' + MONTH_NAMES[ now.getMonth() ] + ' ' + now.getDate();

    if ( isOpenNow ) {
      row.classList.add( 'is-open' );
      statusEl.textContent = 'Open now';
      rangeEl.textContent  = 'Open until close';
    } else if ( isOpenDay ) {
      statusEl.textContent = 'Opens at 5:30 PM';
      rangeEl.textContent  = '5:30 PM \u2013 Close';
    } else {
      statusEl.textContent = 'Closed today';
      rangeEl.textContent  = 'Next open Tue \u00b7 5:30 PM';
    }
  }

  // ---------------------------------------------------------------------------
  // Mobile Nav Drawer
  // ---------------------------------------------------------------------------

  function initMobileMenu() {
    var hamburger = document.getElementById( 'navHamburgerBtn' );
    var closeBtn  = document.getElementById( 'mobileMenuClose' );
    var menu      = document.getElementById( 'mobileMenu' );
    var backdrop  = document.getElementById( 'mobileMenuBackdrop' );

    if ( ! hamburger || ! menu || ! backdrop ) return;

    function openMenu() {
      menu.classList.add( 'is-open' );
      backdrop.classList.add( 'is-open' );
      document.body.classList.add( 'menu-open' );
      hamburger.setAttribute( 'aria-expanded', 'true' );
    }

    function closeMenu() {
      menu.classList.remove( 'is-open' );
      backdrop.classList.remove( 'is-open' );
      document.body.classList.remove( 'menu-open' );
      hamburger.setAttribute( 'aria-expanded', 'false' );
    }

    hamburger.addEventListener( 'click', openMenu );
    closeBtn.addEventListener( 'click', closeMenu );
    backdrop.addEventListener( 'click', closeMenu );

    // Close on Escape
    document.addEventListener( 'keydown', function ( e ) {
      if ( e.key === 'Escape' && menu.classList.contains( 'is-open' ) ) {
        closeMenu();
        hamburger.focus();
      }
    } );
  }

  // ---------------------------------------------------------------------------
  // Mobile Accordion Toggles
  // ---------------------------------------------------------------------------

  function initMobileAccordions() {
    var triggers = document.querySelectorAll( '.m-acc-trigger' );

    triggers.forEach( function ( trigger ) {
      trigger.addEventListener( 'click', function () {
        var acc        = this.closest( '.m-acc' );
        var isOpen     = acc.classList.contains( 'is-open' );
        var expanded   = isOpen ? 'false' : 'true';

        acc.classList.toggle( 'is-open' );
        this.setAttribute( 'aria-expanded', expanded );
      } );
    } );
  }

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------

  initHoursCard();
  initMobileMenu();
  initMobileAccordions();

} )();
