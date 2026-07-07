<?php
/**
 * Denver Elks Lodge #17 — Inner Page Content Import
 *
 * Writes final, rewritten copy (block markup) into every inner page created
 * by bin/setup.php / bin/rebuild.php. This is the rewrite pass and the page
 * build merged into one step — pages come out published, ready to open in
 * the block editor and finish (photos, links, review).
 *
 * Run via WP-CLI on staging, after setup.php has created the page tree:
 *   wp eval-file bin/populate-inner-pages.php
 *
 * Safe to re-run — looks up each page by slug and overwrites post_content.
 * Publishes every page it touches. Does not touch Home (bin/import-homepage.php)
 * or Events (that page is Session 6.3 — the calendar plugin owns it).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * STILL NEEDS YOUR INPUT — pages publish immediately, but this script
 * deliberately does NOT fill these in, since guessing would be worse than
 * leaving them blank:
 *
 *   Who's Who        — no names. Officers are elected annually; add this
 *                       year's Exalted Ruler, trustees, and chairs yourself.
 *   Contact           — currently lists Leo Bartolotto as Secretary per your
 *                       last note. Re-check this every year alongside Who's
 *                       Who — the old site had two different names listed
 *                       across two pages, so it clearly goes stale.
 *   Member Area       — Slack invite link and how-to docs are placeholders.
 *   Hoop Shoot        — no old-site content existed to pull from. Filled with
 *                       general program info only; add local date/contact.
 *   Soccer Shoot      — same as Hoop Shoot.
 *   Military & Veterans — same; expanded from the Charitable Giving page,
 *                       but has no local contact/date info of its own yet.
 *   Scouts            — old site had 2021 troop numbers and a personal email
 *                       for a volunteer contact. Deliberately left out —
 *                       verify current numbers/contact before publishing.
 *   Facility Rentals  — whole section is a placeholder. Rentals are no
 *                       longer members-only and the terms are being
 *                       rewritten from scratch — don't reuse the old
 *                       deposit/booking numbers, they're gone.
 *   Customizer        — denver17_phone and denver17_address are still blank
 *                       in bin/setup.php's $config. Set Appearance → Customize
 *                       → Contact & Social once, and header/footer pick it up
 *                       automatically.
 *   Photos            — every page below gets at least one denver17_placeholder()
 *                       slot. Swap real photos in via the block editor image
 *                       block / Feature Split sidebar as they come in.
 *
 * Diversity & Inclusion Statement is paraphrased from the live site, not
 * copied verbatim — verify it against the actual adopted bylaws language
 * before publishing, since exact wording matters for adopted policy text.
 * ─────────────────────────────────────────────────────────────────────────
 */

// =============================================================================
// BLOCK BUILDERS
//
// All static markup fragments are single-quoted so neither PHP variable
// interpolation nor HTML/JSON double-quote characters need escaping.
// Dynamic blocks (denver17/*) go through wp_json_encode, which handles
// escaping correctly on its own.
// =============================================================================

function d17ip_p( $text ) {
    return '<!-- wp:paragraph -->' . "\n" . '<p>' . $text . '</p>' . "\n" . '<!-- /wp:paragraph -->';
}

function d17ip_h( $text, $level = 2 ) {
    return '<!-- wp:heading {"level":' . $level . '} -->' . "\n"
        . '<h' . $level . '>' . $text . '</h' . $level . '>' . "\n"
        . '<!-- /wp:heading -->';
}

function d17ip_list( $items, $ordered = false ) {
    $tag  = $ordered ? 'ol' : 'ul';
    $body = '';
    foreach ( $items as $item ) {
        $body .= '<li>' . $item . '</li>';
    }
    $comment_attrs = $ordered ? ' {"ordered":true}' : '';
    return '<!-- wp:list' . $comment_attrs . ' -->' . "\n"
        . '<' . $tag . ' class="wp-block-list">' . $body . '</' . $tag . '>' . "\n"
        . '<!-- /wp:list -->';
}

function d17ip_img( $url, $alt ) {
    return '<!-- wp:image {"sizeSlug":"large"} -->' . "\n"
        . '<figure class="wp-block-image size-large"><img src="' . $url . '" alt="' . $alt . '"/></figure>' . "\n"
        . '<!-- /wp:image -->';
}

function d17ip_html( $html ) {
    return '<!-- wp:html -->' . "\n" . $html . "\n" . '<!-- /wp:html -->';
}

function d17ip_details( $summary, $answer ) {
    return '<!-- wp:details -->' . "\n"
        . '<details class="wp-block-details"><summary>' . $summary . '</summary>' . "\n"
        . d17ip_p( $answer ) . "\n"
        . '</details>' . "\n"
        . '<!-- /wp:details -->';
}

function d17ip_block( $name, $attrs = [] ) {
    if ( empty( $attrs ) ) {
        return '<!-- wp:' . $name . ' /-->';
    }
    $json = wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    return '<!-- wp:' . $name . ' ' . $json . ' /-->';
}

/** Finds a page by its own slug, regardless of parent or status. Two prior
 * bugs lived here: get_page_by_path() only matches pages with no parent,
 * and get_posts( [ 'post_status' => 'any' ] ) silently excludes draft/private
 * posts when there's no logged-in user with permission to see them — which
 * is WP-CLI's default context. Direct query sidesteps both. */
function d17ip_find( $slug ) {
    global $wpdb;
    $id = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' AND post_status != 'trash' LIMIT 1",
        $slug
    ) );
    return $id ? get_post( (int) $id ) : null;
}

/** Resolves a real permalink for a page slug created by bin/setup.php. */
function d17ip_url( $slug ) {
    $page = d17ip_find( $slug );
    return $page ? get_permalink( $page->ID ) : '#';
}

/** Internal link — HTML anchor pointing at another inner page by slug. */
function d17ip_link( $slug, $label ) {
    return '<a href="' . d17ip_url( $slug ) . '">' . $label . '</a>';
}

/** Shorthand for the placeholder helper already defined in inc/template-functions.php. */
function d17ip_ph( $w, $h, $label ) {
    return denver17_placeholder( $w, $h, $label );
}

// =============================================================================
// PAGE CONTENT
// =============================================================================

$pages = [];

// ---------------------------------------------------------------------------
// VISIT (hub)
// ---------------------------------------------------------------------------
$pages['visit'] = implode( "\n\n", [
    d17ip_p( "Everything you need to plan a trip to Lodge #17 — where we are, what's on tap, what the place looks like, and how to book it for your own event." ),
    d17ip_list( [
        d17ip_link( 'when-and-where', 'When &amp; Where' ),
        d17ip_link( 'the-jolly-corks-bar', 'The Jolly Corks Bar' ),
        d17ip_link( 'facilities', 'Facilities' ),
        d17ip_link( 'facility-rentals', 'Facility Rentals' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// WHEN & WHERE — bigger build, per Jason: map + hours block + photo band
// ---------------------------------------------------------------------------
$pages['when-and-where'] = implode( "\n\n", [
    d17ip_p( "Find us in Jefferson Park, a few minutes west of downtown. Look for the elk over the door." ),
    d17ip_h( 'Hours' ),
    d17ip_block( 'denver17/hours-display', [
        'showStatus'    => true,
        'showSpecial'   => true,
        'showBaseHours' => true,
        'showNote'      => true,
    ] ),
    d17ip_h( 'Getting Here' ),
    d17ip_p( "2475 W 26th Ave, Denver, CO 80211. Street parking on 26th and the surrounding blocks is usually easy outside of event nights. Headed to a game or concert at Mile High? Show your member card from any Elks lodge and park with us for free." ),
    d17ip_html( '<iframe src="https://www.google.com/maps?q=2475+W+26th+Ave,+Denver,+CO+80211&output=embed" width="100%" height="360" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map to Denver Elks Lodge #17"></iframe>' ),
    d17ip_img( d17ip_ph( 1200, 600, 'Exterior photo of the lodge' ), 'Denver Elks Lodge #17 exterior' ),
] );

// ---------------------------------------------------------------------------
// THE JOLLY CORKS BAR — bigger build, photo-driven per Jason
// ---------------------------------------------------------------------------
$pages['the-jolly-corks-bar'] = implode( "\n\n", [
    d17ip_block( 'denver17/feature-split', [
        'tag'      => 'Since 1882',
        'heading'  => "Original stained glass.\nMember pricing.\nNo tourists.",
        'body'     => "The elk in the back-bar window has been keeping watch over Jolly Corks since before anyone currently pouring drinks here was born. It's survived a renovation, a few close calls, and more Broncos losses than anyone wants to count. Members drink at close to wholesale — genuinely one of the better deals in Denver, if you know where to look.",
        'linkText' => 'See membership benefits',
        'linkUrl'  => d17ip_url( 'benefits-of-membership' ),
        'image'    => [ 'url' => d17ip_ph( 700, 800, 'Stained glass elk window behind the bar' ), 'alt' => 'Stained glass elk window behind the bar' ],
        'variant'  => 'dark',
        'layout'   => 'image-left',
    ] ),
    d17ip_h( 'On Tap Now' ),
    d17ip_block( 'denver17/beer-list', [
        'heading'        => 'On Tap',
        'showStyle'      => true,
        'showAbv'        => true,
        'showComingSoon' => true,
    ] ),
    d17ip_block( 'denver17/feature-split', [
        'tag'      => 'Member Pricing',
        'heading'  => "Near-wholesale,\nevery round.",
        'body'     => "Members get bar pricing you won't find anywhere else downtown, plus the option to keep a personal liquor locker stocked at close to cost. See the full rundown on what membership gets you.",
        'linkText' => 'How to become a member',
        'linkUrl'  => d17ip_url( 'how-to-become-a-member' ),
        'image'    => [ 'url' => d17ip_ph( 700, 800, 'Bartender pouring a drink at Jolly Corks' ), 'alt' => 'Bartender pouring a drink at Jolly Corks' ],
        'variant'  => 'mid',
        'layout'   => 'text-left',
    ] ),
    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'The Jolly Corks Bar',
        'heading'    => 'Ready to make this your bar too?',
        'buttonText' => 'Become a Member',
        'buttonUrl'  => d17ip_url( 'how-to-become-a-member' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// FACILITIES
// ---------------------------------------------------------------------------
$pages['facilities'] = implode( "\n\n", [
    d17ip_p( "Three floors, a beer garden, and enough space to host your whole family reunion or your best friend's wedding reception. Here's what's inside." ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'Lodge Room',
        'body'    => "The main hall — high ceilings, a stage, and room for around 125 people seated at round tables or roughly 200 standing and seated combined. It's where the big stuff happens: weddings, banquets, holiday parties, and Lodge meetings. An optional small bar, the Blind Elk, connects right to the room.",
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Lodge Room set up for an event' ), 'alt' => 'The Lodge Room' ],
        'variant' => 'dark',
        'layout'  => 'image-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'The Club Room',
        'body'    => "Upstairs, with the best view in the building — downtown Denver skyline out the window and a fully stocked bar. This is the Jolly Corks Bar and dance floor, open to members during normal hours. Because it's never fully private, we rarely rent it out for events.",
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'The Club Room with downtown view' ), 'alt' => 'The Club Room' ],
        'variant' => 'mid',
        'layout'  => 'text-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'Game Room',
        'body'    => 'Right behind the Club Room. Pool, darts, and the golf simulators live one floor down — open to members and their guests.',
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Game Room with pool table' ), 'alt' => 'The Game Room' ],
        'variant' => 'dark',
        'layout'  => 'image-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'Beer Garden',
        'body'    => 'An open-air space in what used to be the parking lot, with string lights and downtown views. Runs through the warmer months and fills up fast on Friday nights.',
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Beer garden at night' ), 'alt' => 'The beer garden' ],
        'variant' => 'mid',
        'layout'  => 'text-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'Kitchen',
        'body'    => "A full commercial kitchen that feeds members and Lodge events, and doubles as a rented commissary kitchen. It's not available for event rentals or outside caterers.",
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Commercial kitchen' ), 'alt' => 'The kitchen' ],
        'variant' => 'dark',
        'layout'  => 'image-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'East Wing',
        'body'    => "Three floors: a large commercial space on top available for events or office use, rented office and commercial space in the middle, and three golf simulators on the garden level — run in partnership with North High School's girls' and boys' golf teams.",
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Golf simulator bay' ), 'alt' => 'Golf simulators, East Wing garden level' ],
        'variant' => 'mid',
        'layout'  => 'text-left',
    ] ),

    d17ip_block( 'denver17/feature-split', [
        'tag'     => 'Facilities',
        'heading' => 'Conference Room',
        'body'    => 'Seats 15. Used for committee meetings, Board of Directors sessions, and other Lodge business.',
        'image'   => [ 'url' => d17ip_ph( 700, 800, 'Conference room' ), 'alt' => 'The Conference Room' ],
        'variant' => 'dark',
        'layout'  => 'image-left',
    ] ),

    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Facilities',
        'heading'    => 'Want to host your event in one of these spaces?',
        'buttonText' => 'Facility Rentals',
        'buttonUrl'  => d17ip_url( 'facility-rentals' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// FACILITY RENTALS
// ---------------------------------------------------------------------------
$pages['facility-rentals'] = implode( "\n\n", [
    d17ip_p( 'We rent the Lodge for private events — weddings, receptions, memorials, and everything in between.' ),
    d17ip_p( "[Rental terms are being rewritten from scratch — the old site's members-only policy is being dropped, and the booking process, deposit terms, and what's included below all need new copy from Jason. Structure only past this point.]" ),

    d17ip_h( 'Booking' ),
    d17ip_p( '[New booking process and terms go here.]' ),

    d17ip_h( "What's Included" ),
    d17ip_p( '[New inclusions list goes here.]' ),

    d17ip_img( d17ip_ph( 1000, 650, 'Lodge Room set up for a rental event' ), 'Lodge Room set up for a rental' ),

    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Facility Rentals',
        'heading'    => 'Ready to book your event?',
        'buttonText' => 'Get in Touch',
        'buttonUrl'  => d17ip_url( 'contact' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// LEARN (hub)
// ---------------------------------------------------------------------------
$pages['learn'] = implode( "\n\n", [
    d17ip_p( 'The story behind Lodge #17, who runs it, and what membership actually gets you.' ),
    d17ip_list( [
        d17ip_link( 'our-lodge', 'Our Lodge' ),
        d17ip_link( 'diversity-and-inclusion', 'Diversity &amp; Inclusion Statement' ),
        d17ip_link( 'whos-who', "Who's Who" ),
        d17ip_link( 'faq', 'FAQ' ),
        d17ip_link( 'history-of-the-elks', 'History of the Elks' ),
        d17ip_link( 'how-to-become-a-member', 'How to Become a Member' ),
        d17ip_link( 'benefits-of-membership', 'Benefits of Membership' ),
        d17ip_link( 'volunteer', 'Volunteer' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// OUR LODGE
// ---------------------------------------------------------------------------
$pages['our-lodge'] = implode( "\n\n", [
    d17ip_p( "Lodge #17 has been part of this neighborhood since 1882 — the Mother Lodge of the Rockies, and still one of the more active Elks lodges in the state." ),
    d17ip_p( "We back Denver North High School's athletics and theater programs, sponsor Boy Scout and Girl Scout troops, and support local rugby and soccer clubs. Every year we put real money behind Colorado Special Olympics and Laradon Hall, along with scholarships, veterans' programs, and a handful of causes members care about personally." ),
    d17ip_img( d17ip_ph( 1000, 650, 'Members at the lodge' ), 'Members at Lodge #17' ),
    d17ip_h( "What We're About" ),
    d17ip_list( [
        'Charity, justice, brotherly love, and fidelity — the four founding virtues, still the mission statement',
        'A private bar and clubhouse that funds public good',
        'Open to any U.S. citizen over 21 who believes in a higher power, whatever that means to you',
    ] ),
] );

// ---------------------------------------------------------------------------
// DIVERSITY & INCLUSION STATEMENT
// NOTE: paraphrased from the live site, not a verbatim copy of the bylaws —
// verify wording against the actual adopted policy before publishing.
// ---------------------------------------------------------------------------
$pages['diversity-and-inclusion'] = implode( "\n\n", [
    d17ip_p( "Adopted by the Lodge's Board of Trustees, this statement is part of our bylaws." ),
    d17ip_p( "Inherent in the Benevolent and Protective Order of Elks' cardinal virtue of Brotherly Love, we support a culture of mutual respect regardless of gender, race, national origin, religion, sexual orientation, gender identity or expression, marital status, military service, disability, or age. We celebrate the diversity of our membership and put their talents and energy to work in the Order's charitable mission." ),
    d17ip_p( "We're committed to a safe environment, free of discrimination and harassment, and we require every member to act in a way consistent with that commitment. As members of the Order, we take an oath to uphold the Constitution and laws of the United States, and we hold ourselves to federal and state equal employment standards even though we're a private club — no sexual harassment and no discriminatory conduct, verbal, physical, or otherwise, tolerated on Lodge property." ),
    d17ip_p( 'This statement was first written in 2019 by a Lodge trustee working to update outdated assumptions about who the Elks are for, and it was updated to explicitly address harassment and discrimination in meetings and hiring. Denver #17 admitted its first women members in 1995. Read more about that history on our ' . d17ip_link( 'history-of-the-elks', 'History of the Elks' ) . ' page.' ),
] );

// ---------------------------------------------------------------------------
// WHO'S WHO — scaffold only, no names. Officers are elected annually.
// ---------------------------------------------------------------------------
$pages['whos-who'] = implode( "\n\n", [
    d17ip_p( "Lodge officers are elected annually. This page lists the current Exalted Ruler, trustees, and committee chairs — update it each year after elections." ),
    d17ip_h( 'Officers' ),
    d17ip_list( [
        'Exalted Ruler — ',
        'Leading Knight — ',
        'Loyal Knight — ',
        'Lecturing Knight — ',
        'Secretary — ',
        'Treasurer — ',
        'Tiler — ',
    ] ),
    d17ip_p( 'Committee chairs (House, Membership, Charities, Youth Activities, Americanism, and more) go here too — pull the current list from the Secretary.' ),
] );

// ---------------------------------------------------------------------------
// FAQ
// ---------------------------------------------------------------------------
$pages['faq'] = implode( "\n\n", [
    d17ip_details( 'Do I have to be religious to join?', "You need to believe in a higher power — however you define that. The Elks aren't tied to any particular religion or denomination." ),
    d17ip_details( 'I thought the Elks was a men\'s club.', 'Not since 1995. Women have been full members here for decades, including some of our current lodge leadership.' ),
    d17ip_details( 'Can I bring guests to the bar?', 'Yes — members can bring guests, and first-time visitors are welcome to stop in and see what the place is about before applying.' ),
    d17ip_details( 'What does membership cost?', 'Dues vary year to year — the fastest way to get a current number is to visit or ask a member. See ' . d17ip_link( 'how-to-become-a-member', 'How to Become a Member' ) . ' for the application process.' ),
    d17ip_details( "Do I need to know a member to join?", "You'll need a sponsor and two references from current members, but if you don't know anyone yet, stop by the bar. Most people leave with a sponsor lined up." ),
    d17ip_details( 'Is the Lodge open to the public?', 'The bar is members and guests only. Facility rentals for private events are currently members only as well — see ' . d17ip_link( 'facility-rentals', 'Facility Rentals' ) . ' for details.' ),
] );

// ---------------------------------------------------------------------------
// HISTORY OF THE ELKS
// ---------------------------------------------------------------------------
$pages['history-of-the-elks'] = implode( "\n\n", [
    d17ip_h( 'How the Elks Began' ),
    d17ip_p( 'The Benevolent and Protective Order of Elks started in New York City in 1868, founded by a group of actors and entertainers who first called themselves the Jolly Corks — a name meant to dodge blue laws that kept bars closed on Sundays. When one of the group died suddenly, the others decided their drinking club should do some good in the world too, and the Elks were born. The four founding virtues — charity, justice, brotherly love, and fidelity — still anchor everything the Order does.' ),

    d17ip_h( 'Denver #17' ),
    d17ip_p( "Lodge #17 was chartered in Denver in 1882, making it 144 years old and one of the older Elks lodges in the country. We're known as the Mother Lodge of the Rockies — the first lodge established in the region, with several other Colorado lodges tracing their roots back here." ),
    d17ip_img( d17ip_ph( 1000, 650, 'Historical photo of the lodge' ), 'Lodge #17, historical photo' ),

    d17ip_h( 'A More Honest History' ),
    d17ip_p( "For most of its history, the Elks — like most fraternal organizations of the era — was a white men's club. Denver #17 didn't admit its first women members until 1995, when three longtime volunteers, previously known as Elkettes, were finally allowed to join as full members. They're still active in the Lodge today. In 2019, a Lodge trustee wrote a formal diversity statement addressing that history directly and committing the Lodge to a different standard going forward — you can read the current version on our " . d17ip_link( 'diversity-and-inclusion', 'Diversity &amp; Inclusion Statement' ) . ' page.' ),
    d17ip_p( "We think that history is worth telling straight, not glossing over it. The Lodge today looks different than it did in 1882, and that's the point." ),
] );

// ---------------------------------------------------------------------------
// HOW TO BECOME A MEMBER
// ---------------------------------------------------------------------------
$pages['how-to-become-a-member'] = implode( "\n\n", [
    d17ip_p( 'Membership is open to any U.S. citizen over 21 who believes in a higher power — however you define that. No other qualifications required.' ),
    d17ip_h( 'How It Works' ),
    d17ip_list( [
        'Come by as a guest. Have a drink, meet some members, see if the place is for you.',
        "Find a sponsor. Any member in good standing — from this Lodge or any Elks lodge in the country — can sponsor you. You'll need two more members to serve as references.",
        "Fill out an application. Your sponsor will get you the form, either on paper or online.",
        "Wait for a vote. Your application gets read at a regular Lodge meeting, with at least 10 days' and up to two months' notice before members vote.",
        "You're in. If the vote doesn't go your way, you can reapply after six months.",
    ], true ),
    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Membership',
        'heading'    => 'Come see what #17 is about.',
        'buttonText' => 'Plan a Visit',
        'buttonUrl'  => d17ip_url( 'when-and-where' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// BENEFITS OF MEMBERSHIP — consolidates Club Bar, Liquor Lockers, Queen of
// Hearts, Golf Simulators, Fairmount Cemetery Plots from the old site.
// ---------------------------------------------------------------------------
$pages['benefits-of-membership'] = implode( "\n\n", [
    d17ip_p( "Member pricing is just the start. Here's what you actually get." ),
    d17ip_img( d17ip_ph( 1000, 650, 'Golf simulator bay' ), 'Golf simulators — a member benefit' ),
    d17ip_list( [
        'Private bar access — member and guest pricing you won\'t find anywhere else downtown',
        'Liquor lockers — keep a bottle at the Lodge at close to wholesale cost',
        'Golf simulators — three bays, open to members',
        "Free parking near downtown and Mile High Stadium — show your member card from any Elks lodge",
        'A shot at Elks Rest — member-exclusive burial plots at Fairmount Cemetery',
        "Queen of Hearts — the Lodge's recurring member raffle",
        'Volunteer opportunities across a dozen-plus committees, from Scholarships to Youth Activities',
        'Real community impact — the Lodge backs Special Olympics Colorado, Laradon Hall, local scholarships, and more, and members drive where that support goes',
    ] ),
    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Benefits of Membership',
        'heading'    => 'Ready to join?',
        'buttonText' => 'How to Become a Member',
        'buttonUrl'  => d17ip_url( 'how-to-become-a-member' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// VOLUNTEER — consolidates Become a Bartender and Volunteer Opportunities.
// Bingo intentionally left out per Jason — that program has been retired.
// ---------------------------------------------------------------------------
$pages['volunteer'] = implode( "\n\n", [
    d17ip_p( "A good Elk volunteers for the Lodge. There's a committee for almost anything you're into — here's where to start." ),

    d17ip_h( 'Become a Lodge Bartender' ),
    d17ip_p( "Members only, tip-based. You'll need to be 21, able to stand for a shift, and willing to take an online TIPS certification course (about $40 — the Lodge reimburses it once you've got the certificate). No bartending experience required; we'll train you. Pre-confirm with the bar manager before your first shift." ),

    d17ip_h( 'Committees' ),
    d17ip_list( [
        "Scholarships — help coordinate roughly \$20,000 in annual scholarship awards, or fundraise for the Lodge's own scholarship endowment",
        "Lodge Events — plan the parties. If you're the type who organizes group trips, this is your committee",
        'Membership — recruit and onboard new members, run new-member orientation',
        'Grants — apply for Elks National Foundation Community Investment Program grants, which put more than $40 million a year to work across Elks lodges nationally',
    ] ),

    d17ip_p( 'Reach out through ' . d17ip_link( 'contact', 'Contact' ) . ' to get connected with a committee chair.' ),
] );

// ---------------------------------------------------------------------------
// COMMUNITY (hub)
// ---------------------------------------------------------------------------
$pages['community'] = implode( "\n\n", [
    d17ip_p( "Scholarships, youth sports, veterans' support, and a handful of causes the Lodge has backed for decades. Here's where the money and the volunteer hours go." ),
    d17ip_list( [
        d17ip_link( 'charitable-giving', 'Charitable Giving' ),
        d17ip_link( 'casa', 'CASA' ),
        d17ip_link( 'hoop-shoot', 'Hoop Shoot' ),
        d17ip_link( 'soccer-shoot', 'Soccer Shoot' ),
        d17ip_link( 'military-and-veterans', 'Military &amp; Veterans' ),
        d17ip_link( 'scholarships', 'Scholarships' ),
        d17ip_link( 'scouts', 'Scouts' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// CHARITABLE GIVING
// ---------------------------------------------------------------------------
$pages['charitable-giving'] = implode( "\n\n", [
    d17ip_p( 'You can add a donation to your bar tab, give at the front office, or give online — and you can direct it toward a specific cause if you want.' ),
    d17ip_h( 'Where It Goes' ),
    d17ip_list( [
        'Elks Care — a fund for urgent needs, disaster relief, and members seeking help through Lodge-issued grocery gift cards',
        'Veterans Committee — the Lodge adopted the 193rd National Guard Troop and regularly hosts fundraisers supporting active and former service members',
        "Laradon Hall — services for children and adults with intellectual and developmental disabilities, one of the Lodge's longest-running partnerships",
        'The Denver Elks #17 Scholarship Fund — formerly the Bennett Fund, now run in partnership with Denver North High School',
        'The Clem &amp; Evelyn Audin Memorial Fund — established in 1975, supports education, health, and care for young people',
        'Youth Activities &amp; Drug Awareness — the Elks have run a national drug-prevention program since 1982; locally, that means athletic banquets, school fundraisers, and Lodge space for youth clubs',
        'Bienvenidos Food Bank — a food insecurity partner near the Lodge, plus regular blood drives',
    ] ),
    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Charitable Giving',
        'heading'    => 'Support the causes Lodge #17 backs.',
        'buttonText' => 'Get in Touch',
        'buttonUrl'  => d17ip_url( 'contact' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// CASA
// ---------------------------------------------------------------------------
$pages['casa'] = implode( "\n\n", [
    d17ip_p( 'CASA — Court Appointed Special Advocate — trains community members to represent the best interests of kids and youth in the court system. Lodge #17 partners with Denver CASA on a couple of recurring drives.' ),
    d17ip_list( [
        'A holiday shopping event where CASA volunteers shop for the children and families they represent, funded through Lodge donations and community partners',
        'An annual hat and glove drive — bring newly purchased items to the Lodge during drop-off dates',
    ] ),
    d17ip_p( 'Watch ' . d17ip_link( 'community', 'Community' ) . ' or ask at the bar for current drop-off dates.' ),
] );

// ---------------------------------------------------------------------------
// HOOP SHOOT — no old-site content existed. General program info only.
// ---------------------------------------------------------------------------
$pages['hoop-shoot'] = implode( "\n\n", [
    d17ip_p( 'The Elks Hoop Shoot is a national free-throw shooting contest for kids, run by Elks lodges across the country since the 1970s. Local winners advance through district, state, and eventually a shot at the national finals.' ),
    d17ip_p( "[Add current details for Lodge #17's Hoop Shoot: date, age groups, sign-up contact.]" ),
] );

// ---------------------------------------------------------------------------
// SOCCER SHOOT — same situation as Hoop Shoot.
// ---------------------------------------------------------------------------
$pages['soccer-shoot'] = implode( "\n\n", [
    d17ip_p( 'The Elks Soccer Shoot is a national penalty-kick accuracy contest for kids, run the same way as the Hoop Shoot — local competition feeding into district and state rounds.' ),
    d17ip_p( "[Add current details for Lodge #17's Soccer Shoot: date, age groups, sign-up contact.]" ),
] );

// ---------------------------------------------------------------------------
// MILITARY & VETERANS — expanded from the Charitable Giving page since the
// old standalone page was empty.
// ---------------------------------------------------------------------------
$pages['military-and-veterans'] = implode( "\n\n", [
    d17ip_p( 'Lodge #17 has adopted the 193rd National Guard Troop and works directly with their Soldier and Family Readiness Group, often alongside grants from the Elks\' Grand Lodge.' ),
    d17ip_p( 'We host fundraisers throughout the year supporting active-duty service members and veterans, and contribute to veterans in need through the Lodge\'s broader charitable giving.' ),
    d17ip_p( '[Add current point of contact for the Veterans Committee.]' ),
] );

// ---------------------------------------------------------------------------
// SCHOLARSHIPS
// ---------------------------------------------------------------------------
$pages['scholarships'] = implode( "\n\n", [
    d17ip_p( 'The Lodge awards close to $20,000 a year in scholarships, funded through member donations and Lodge fundraising.' ),
    d17ip_h( 'How Awards Are Decided' ),
    d17ip_p( 'Applications go through a scoring rubric weighted evenly across four areas: merit (unweighted GPA), financial need, relationship to the Elks, and essay response.' ),
    d17ip_h( 'Where the Money Comes From' ),
    d17ip_list( [
        'The Denver Elks #17 / North High School Scholarship — $2,000 a year to a graduating North High senior, matched at 75% by the Prosperity Denver Fund',
        'Legacy Awards — $4,000 scholarships for children and grandchildren of Lodge members',
        'Elks National Foundation scholarships — the national Order awards 500 four-year scholarships annually, ranging from $1,000 to $7,000 a year',
    ] ),
    d17ip_block( 'denver17/cta-band', [
        'eyebrow'    => 'Scholarships',
        'heading'    => 'Questions about applying?',
        'buttonText' => 'Get in Touch',
        'buttonUrl'  => d17ip_url( 'contact' ),
    ] ),
] );

// ---------------------------------------------------------------------------
// SCOUTS — old content is stale (2021 numbers, named personal contact).
// Deliberately generic. Do not add specifics without verifying first.
// ---------------------------------------------------------------------------
$pages['scouts'] = implode( "\n\n", [
    d17ip_p( 'Lodge #17 charters a Scouts BSA troop and a Girl Scout troop, both active in the building.' ),
    d17ip_p( 'Scouting here means the usual mix: regular campouts, a week-long summer camp, hiking and backpacking trips, service projects around the neighborhood, and the occasional non-outdoor night of games at the Lodge.' ),
    d17ip_p( '[This page needs a refresh before launch — confirm current troop numbers and leadership contact with the troop before publishing anything specific.]' ),
] );

// ---------------------------------------------------------------------------
// CONTACT
// ---------------------------------------------------------------------------
$pages['contact'] = implode( "\n\n", [
    d17ip_p( '2475 W 26th Ave, Denver, CO 80211' ),
    d17ip_p( '303-455-3557 &middot; info@denverelks.org' ),
    d17ip_p( 'Lodge Secretary: Leo Bartolotto — by appointment.' ),
    d17ip_p( 'For rentals and private events, reach out through events@denverelks.org instead — see ' . d17ip_link( 'facility-rentals', 'Facility Rentals' ) . ' for details.' ),
] );

// ---------------------------------------------------------------------------
// MEMBER AREA — no auth. Dues, comms, how-to docs.
// ---------------------------------------------------------------------------
$pages['member-area'] = implode( "\n\n", [
    d17ip_p( "This page is for current members — dues, communication channels, and the how-to stuff that doesn't need its own page. No login required." ),
    d17ip_h( 'Pay Dues' ),
    d17ip_p( "Simplest option: ask the bartender to add your dues to your tab next time you're in. You can also pay by appointment — contact Leo Bartolotto, Lodge Secretary, at secretary@denverelks.org." ),
    d17ip_h( 'Stay in the Loop' ),
    d17ip_p( '[Add Slack invite link once set up.]' ),
    d17ip_h( 'How-To Docs' ),
    d17ip_p( '[Add links to any member how-to guides once written — booking the Lodge Room, requesting a liquor locker, etc.]' ),
] );

// =============================================================================
// WRITE TO PAGES
// =============================================================================

WP_CLI::log( '' );
WP_CLI::log( '=== Writing Inner Page Content ===' );

$updated = 0;
$skipped = 0;

foreach ( $pages as $slug => $content ) {
    $page = d17ip_find( $slug );

    if ( ! $page ) {
        WP_CLI::warning( "  Not found, skipping: {$slug} (run bin/setup.php or bin/rebuild.php first)" );
        $skipped++;
        continue;
    }

    $result = wp_update_post( [
        'ID'           => $page->ID,
        'post_content' => $content,
        'post_status'  => 'publish',
    ], true );

    if ( is_wp_error( $result ) ) {
        WP_CLI::warning( "  Failed: {$slug} — " . $result->get_error_message() );
        $skipped++;
        continue;
    }

    WP_CLI::success( "  [{$page->ID}] {$slug}" );
    $updated++;
}

WP_CLI::log( '' );
WP_CLI::success( "Done. {$updated} pages updated, {$skipped} skipped." );
WP_CLI::log( 'Every page touched is now published — this is staging, review live rather than in a draft queue.' );
WP_CLI::log( 'See the doc comment at the top of this file for what still needs manual input.' );
