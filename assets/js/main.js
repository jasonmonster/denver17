/**
 * Denver Elks Lodge #17 — Main JS
 * Loaded in footer via wp_enqueue_script (no defer needed).
 */

( function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Hours Card
  //
  // Data comes from window.denver17Hours, which is populated by
  // inc/hours-feed.php via wp_localize_script. The sheet is the single
  // source of truth; this JS only computes the live open/closed state
  // (time-of-day logic must be client-side) and renders the UI.
  //
  // window.denver17Hours shape:
  //   open_time  '17:30' | ''   24h; empty string = closed today
  //   close_time '23:00' | ''   24h; empty string = no fixed close time
  //   special    string          notice text, or empty
  //   display_1  string          base hours line 1
  //   display_2  string          base hours line 2, or empty to hide
  //
  // Dev preview: add ?hours=open, ?hours=opens_at, or ?hours=closed
  // to the URL to force a specific state without waiting for the clock.
  // ---------------------------------------------------------------------------

  var DAY_NAMES   = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
  var MONTH_NAMES = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];

  /**
   * Parse a "HH:MM" (24h) string to decimal hours.
   * Returns null if the string is empty or unparseable.
   */
  function parseTime24( str ) {
    if ( ! str ) return null;
    var parts = str.split( ':' );
    if ( parts.length !== 2 ) return null;
    var h = parseInt( parts[ 0 ], 10 );
    var m = parseInt( parts[ 1 ], 10 );
    if ( isNaN( h ) || isNaN( m ) ) return null;
    return h + m / 60;
  }

  /**
   * Format a "HH:MM" (24h) string to "h:mm AM/PM" for display.
   * Returns empty string on bad input.
   */
  function formatTime12( str ) {
    if ( ! str ) return '';
    var parts = str.split( ':' );
    var h = parseInt( parts[ 0 ], 10 );
    var m = parseInt( parts[ 1 ], 10 );
    if ( isNaN( h ) || isNaN( m ) ) return '';
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    var mStr = m > 0 ? ':' + ( m < 10 ? '0' + m : m ) : '';
    return h + mStr + '\u202f' + ampm; // narrow no-break space before AM/PM
  }

  function initHoursCard() {
    var row       = document.getElementById( 'hoursStatusRow' );
    var statusEl  = document.getElementById( 'hoursStatus' );
    var dateEl    = document.getElementById( 'hoursDate' );
    var rangeEl   = document.getElementById( 'hoursRange' );
    var specialEl = document.getElementById( 'hoursSpecial' );
    var baseEl    = document.getElementById( 'hoursBase' );
    var base2El   = document.getElementById( 'hoursBase2' );

    if ( ! row ) return;

    // Date display
    var now = new Date();
    dateEl.textContent = DAY_NAMES[ now.getDay() ] + ', '
      + MONTH_NAMES[ now.getMonth() ] + '\u00a0' + now.getDate();

    // Hours data from Sheets via wp_localize_script, with hardcoded fallback
    var h = ( typeof window.denver17Hours !== 'undefined' && window.denver17Hours )
      ? window.denver17Hours
      : {
          open_time:  ( now.getDay() >= 2 && now.getDay() <= 6 ) ? '17:30' : '',
          close_time: '',
          special:    '',
          display_1:  'Tue\u2013Sat \u00b7 5:30PM\u2013Close',
          display_2:  '',
        };

    // Dev preview: ?hours=open | opens_at | closed
    // Forces a specific state without waiting for real time to match.
    var previewState = new URLSearchParams( window.location.search ).get( 'hours' );
    if ( previewState === 'open' ) {
      h = Object.assign( {}, h, { open_time: '00:00' } );
    } else if ( previewState === 'opens_at' ) {
      h = Object.assign( {}, h, { open_time: '23:59' } );
    } else if ( previewState === 'closed' ) {
      h = Object.assign( {}, h, { open_time: '' } );
    }

    // Compute live open/closed status
    var openDecimal  = parseTime24( h.open_time );
    var nowDecimal   = now.getHours() + now.getMinutes() / 60;
    var isOpenToday  = openDecimal !== null;
    var isOpenNow    = isOpenToday && nowDecimal >= openDecimal;

    var openFormatted  = formatTime12( h.open_time );
    var closeFormatted = h.close_time ? formatTime12( h.close_time ) : 'Close';

    if ( ! isOpenToday ) {
      row.classList.add( 'is-closed' );
      statusEl.textContent = 'Closed today';
      rangeEl.textContent  = '\u2014';

    } else if ( isOpenNow ) {
      row.classList.add( 'is-open' );
      statusEl.textContent = 'We\u2019re open';
      // When there's no fixed close time, "Open until close" is awkward.
      // Show the open time instead so the card stays informative.
      rangeEl.textContent  = h.close_time
        ? 'Open until ' + closeFormatted
        : 'Open at ' + openFormatted;

    } else {
      row.classList.add( 'is-opens-at' );
      statusEl.textContent = 'Opens at ' + openFormatted;
      rangeEl.textContent  = openFormatted + '\u2013' + closeFormatted;
    }

    // Special notice (hidden when empty)
    if ( specialEl && h.special ) {
      specialEl.textContent = h.special;
      specialEl.hidden = false;
    }

    // Base hours display lines
    if ( baseEl ) {
      baseEl.textContent = h.display_1 || '';
    }
    if ( base2El && h.display_2 ) {
      base2El.textContent = h.display_2;
      base2El.hidden = false;
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
        var acc      = this.closest( '.m-acc' );
        var isOpen   = acc.classList.contains( 'is-open' );
        var expanded = isOpen ? 'false' : 'true';

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
