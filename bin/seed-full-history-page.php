<?php
/**
 * Denver Elks #17 — seed the "Full History of the Elks & Denver #17" page.
 *
 * Idempotent content seeder, run under WordPress (WP-CLI):
 *
 *     wp eval-file seed-full-history-page.php
 *
 * Re-running updates the existing page in place rather than creating a
 * duplicate — it's matched by the _elks_seed_key post meta below, falling
 * back to the slug. Content is core Gutenberg blocks so the denver17 theme
 * owns all styling; the handful of custom layout pieces carry className hooks
 * (elks-* ) the theme stylesheet can target — see the note printed at the end.
 *
 * This creates the page only. It does NOT add it to any nav menu — the page is
 * reached from a link in the body of the short "History of the Elks" page, by
 * design. It also does NOT add that link; drop it into the parent page content
 * (a ready-to-paste block is printed at the end of this run).
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "This script must run inside WordPress, e.g. `wp eval-file seed-full-history-page.php`.\n" );
	exit( 1 );
}

// ── Config ───────────────────────────────────────────────────────────────────
$seed_key      = 'full-history-page';                 // idempotency key (post meta)
$child_slug    = 'history-of-the-elks-full';          // this page's slug
$child_title   = 'The Full History of the Elks & Denver #17';
$parent_slug   = 'history-of-the-elks';               // short page this hangs under
$author_id     = 1;                                   // adjust if the content author differs

// ── Page content: core Gutenberg blocks ──────────────────────────────────────
// Kept as block markup (not raw HTML) so the theme styles it. Custom layout
// pieces use core/columns + core/group with an `elks-*` className the theme
// stylesheet targets.
$content = <<<'BLOCKS'
<!-- wp:paragraph {"className":"elks-lede"} -->
<p class="elks-lede">This is the companion to our short <a href="/our-lodge/history-of-the-elks/">History of the Elks</a> page — the fuller record for anyone who wants the whole story. It covers the Order's beginnings in New York, how a bar game called the Jolly Corks became a national fraternal order, and the complete account of race and gender at the Elks and here at Denver&nbsp;#17, including the members who broke ground for everyone who came after.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Denver #17: the Mother Lodge of the Rockies</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Denver Elks Lodge&nbsp;#17 was chartered in 1882 as the 17th lodge in the Order, and it became known as the "Mother Lodge of the Rockies" — the first lodge in the region, the one every Colorado lodge that followed traces back to.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The original stone building stood on 17th Street in downtown Denver and was built in 1911. In 1973 the lodge moved to its current home at 2475 W. 26th Avenue, onto land that had previously been a local granary. Original stained glass and chandeliers from the earlier building came along and are still in use, and the Jolly Corks Bar looks out over the downtown Denver skyline. The lodge's main-floor conference room holds the famous Buffalo Bill Table.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>The Order's name and symbol</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The Order was formally established on February 16, 1868, in New York City, under the full name the Benevolent and Protective Order of Elks of the United States of America. Its four cardinal virtues are Charity, Justice, Brotherly Love, and Fidelity.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The elk was chosen for what the founders saw as its distinctly American character. It lives in herds, is among the largest of quadrupeds, moves fleet of foot and graceful, and shows a keenness of perception. Gentle by nature, it is nonetheless strong and valiant in defense of its own. The elk's head with spreading antlers became the Order's first badge and is still its most recognizable symbol.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>The colors</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The Order's colors are Royal Purple and White. White stands for purity and absolute truth; Royal Purple signifies the favor of the people. Together they represent the love of truth and the highest degree of virtue.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>Origins: Charles Vivian and the Jolly Corks</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The story starts with Charles A. Vivian, an English comic singer who arrived in New York on November 15, 1867. At the Star Hotel on Lispenard Street, Vivian met piano player Richard R. Steirly and others, including William Bowron. Their informal social gatherings grew into something more organized.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>On November 23, 1867, Vivian introduced a game played with corks at Sandy Spencer's place near Broadway and Fulton Street. Three players would drop corks on the bar and snatch them back up; the last to grab their cork paid for the round. Henry Vandemark was the first man caught by the "cork trick."</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The game caught on, especially among entertainers looking to get around New York's strict Sunday liquor-enforcement laws. A regular group met at Mrs. Giesman's boarding house on Elm Street and called themselves the "Corks," with Vivian as the "Imperial Cork." After Mrs. Giesman put a stop to their Sunday gatherings, they moved to 17 Delancy Street, above Paul Sommers' saloon. Watching them at play, George McDonald renamed the group the "Jolly Corks." Membership was mostly professional and semi-professional entertainers and legitimate actors, among them Thomas Riggs, George McDonald, William Sheppard, and George Thompson.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>After the group attended the funeral of a friend, Ted Quinn, McDonald suggested the Jolly Corks become a protective and benevolent society. At the February 2, 1868 meeting, chaired by Vivian, McDonald proposed reorganizing the group along fraternal and benevolent lines, with a new name, a ritual, and governing rules.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>From Jolly Corks to the Order of the Elks</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A committee researched possible names at the Cooper Institute Library, where they found the elk described as "fleet of foot, timorous of doing wrong, but ever ready to combat in defense of self or of the female of the species" — a description that matched the values the founders wanted to stand for.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>On February 16, 1868, the committee recommended merging the Jolly Corks into the "Benevolent and Protective Order of Elks." It passed by a single vote, 8 to 7 — the choice was between the buffalo and the elk.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"elks-vote"} -->
<div class="wp-block-columns elks-vote"><!-- wp:column {"className":"elks-vote-col"} -->
<div class="wp-block-column elks-vote-col"><!-- wp:heading {"level":4} -->
<h4>Voted for "Buffalo"</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Charles A. Vivian, Richard Steirly, M.G. Ash, Henry Vandermark, Harry Bosworth, Frank Langhorne, E.W. Platt</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"className":"elks-vote-col elks-vote-win"} -->
<div class="wp-block-column elks-vote-col elks-vote-win"><!-- wp:heading {"level":4} -->
<h4>Voted for "Elk" — carried, 8 to 7</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>George McDonald, George Thompson, Thomas Riggs, William Carleton, William Sheppard, George Guy, Hugh Dougherty, William Bowron</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:heading {"level":3} -->
<h3>The death of Charles Vivian</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A split showed up early, between the legitimate actors pushing the benevolent and fraternal principles and the semi-professional entertainers who favored the original convivial spirit. Vivian led the latter group. During his second-degree initiation on June 14, 1868, he was rejected by the professionals then running the organization, and he and his associates were expelled.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Vivian moved to Leadville, Colorado, where he died of pneumonia on March 20, 1880. Twelve years later, on April 28, 1889, his remains were exhumed and taken to Boston by Boston Lodge&nbsp;#10 for burial in Mt. Hope Cemetery. Though Vivian is remembered as an organizer of the Elks, he never actually took the Order's degrees and never officially counted himself a member.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>A more honest history: race, gender, and the record</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The cardinal principles — Charity, Justice, Brotherly Love, and Fidelity — did not extend to men of color for the Order's first 100 years, or to women for its first 125. Denver Lodge&nbsp;#17 acknowledges its past injustices and commits to doing better: recruiting members who reflect the Greater Denver community, and backing the needs that community identifies for itself.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The BPOE was founded in 1868 as an all-white, all-male organization, descended from a social club of Vaudeville minstrels, many of whom performed in blackface. A charitable arm supporting widows and veterans had formed by 1870, but it too excluded people of color and women.</p>
<!-- /wp:paragraph -->

<!-- wp:quote {"className":"elks-statement"} -->
<blockquote class="wp-block-quote elks-statement"><!-- wp:paragraph -->
<p>Denver&nbsp;#17's Diversity Statement, adopted in 2019 and revised in 2021, reaffirms the lodge's commitment to inclusive practices and to reckoning honestly with this history.</p>
<!-- /wp:paragraph --><cite>Denver Lodge #17 Diversity Statement</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:heading {"level":3} -->
<h3>The Improved Benevolent and Protective Order of the Elks of the World</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>In 1898, two Black men who were denied entry to the BPOE founded an alternative order, open to qualified individuals "without regard to race, creed, or ethnicity." Finding that the Elks ritual had never been copyrighted, they secured the copyright themselves. The first IBPOEW meeting was held on November 17, 1898. The BPOE initially objected to the use of its seal and pin, but that opposition ended by 1918.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The IBPOEW set out to promote the welfare and happiness of its members, to cultivate nobleness of soul and goodness of heart, to instill charity, justice, brotherly and sisterly love, and fidelity, to assist and protect its members and their families, and to keep alive the spirit of patriotism.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Legal pressure built in the early 1970s, as the BPOE's exclusionary policies ran up against federal civil rights law. In <em>McGlotten v. Connally</em> (1972), a court established that government support of private clubs that discriminate violated the Civil Rights Act. A resolution repealing the discriminatory clauses passed in 1973. Denver&nbsp;#17 welcomes IBPOEW members and encourages shared learning and community work between the two organizations.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>IBPOEW leadership and growth</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Arthur James Riggs, a Pullman porter, and Benjamin Franklin Howard established Alpha Lodge No.&nbsp;1 in Cincinnati. The organization grew enormously under J. Finley Wilson, elected Grand Exalted Ruler in 1922. Wilson took national membership from 30,000 to 500,000, oversaw the founding of roughly 900 new lodges, raised about $700,000 in scholarships for African American college students, and founded the <em>Washington Eagle</em> newspaper.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Emma Virginia Kelly organized the Daughters of Elks on June 13, 1902, in Norfolk, Virginia, later adopted as an auxiliary body. The Daughters of Elks still present the annual Emma V. Kelley Achievement Award in her memory.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>Women and the Elks</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Rather than admit women as members, the BPOE steered them toward related organizations. Elks' ladies organized the Emblem Club in 1917 to support American troops in World War I. In 1921, women in Omaha, Nebraska created the Benevolent and Patriotic Order of the Does, for the wives, widows, mothers, daughters, and sisters of Elks. Canadian women had established the Royal Purple auxiliary in 1914.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The BPOE stayed all-male until 1995. The turning point was legal: the 1993 Utah Supreme Court decision in <em>Beynon v. St. George-Dixie Lodge 1743</em> held that while freedom of association allowed the Elks to remain a men-only organization, the Order could not hold a liquor license while violating the Utah State Civil Rights Act. Facing the loss of those licenses, Utah lodges voted to go "unisex" in June 1993. At the national convention in July 1995 — pushed by declining membership and by the women seeking to join — the Order voted to remove "male" from its membership requirements.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Women and Denver #17, before 1995</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Three women's organizations carried Denver&nbsp;#17 for decades before women could join as members:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul><!-- wp:list-item -->
<li><strong>Elks Ladies</strong> — organized December 11, 1936, by Ann Sherman, its first president.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Elks Widow's Club</strong> — organized February 9, 1949, by Mrs. William Erz, Mrs. Nicholas Coninillo, Mrs. Mat Haesch, and Mrs. Al Zable. Coninillo served as first president.</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><strong>Elkettes</strong> — formed September 1976, chartered 1977, with Bernice Barlock as first president.</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>The first Charity Ball and Charity Queen coronation was held in December 1950, honoring these women's charitable contributions to the lodge. In May 1992, the three organizations merged into the Denver&nbsp;#17 Elkettes.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Denver #17 Elkettes charter members</h4>
<!-- /wp:heading -->

<!-- wp:list {"className":"elks-names"} -->
<ul class="elks-names"><!-- wp:list-item --><li>Bernice Barlock</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Juanita Carlson</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Iva Coomes</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Margaret Davis</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Louise Debeii</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Madlyn Dezzutti</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Betty Dillman</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Wilma Faulkner</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Arlene Fosnight</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Arlene Gustafson</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Donna Gustafson</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Barbara Matlock</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Edith McGillivray</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>June Moss</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Mickey Ress</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Lauretta Rullo</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Verona Runyan</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Ester Salina</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>D. Schaughnessy</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Pat Schmidt</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Roseann Sievin</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Dona Smidt</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Beverly Suntum</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":4} -->
<h4>The first women initiated at Denver #17</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>On April 25, 1996 — a year after the national settlement opened membership to women — Denver&nbsp;#17 initiated its first female members: Janie Iacino (deceased), Bernadine (Berni) Penrose, and Joan (Joanie) Verhey. Penrose and Verhey remain active honorary life members.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":2} -->
<h2>Honorary Life Members — recognized February 6, 2019</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Three pioneering members received Honorary Life Member recognition for distinguished service to Denver&nbsp;#17.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Bernadine (Berni) Penrose</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Initiated April 25, 1996, in the first women's class, Berni was an active Elkette both before and after. She has served as Lecturing Knight, Loyal Knight, Esquire, Chaplain, and Charity Queen, and has been a regular Bingo volunteer — more than 24 years of combined service.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Joan (Joanie) Verhey</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Also initiated April 25, 1996 alongside Penrose, Joanie has served as Treasurer for 15-plus of her 24 years, along with time as Elkettes Society President, Charity Queen, and Bingo Games Manager, and weekly volunteer work across multiple committees.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Valeri Fitzgibbons</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Initiated November 20, 1997, after years as an Elkette and joining her husband Cliff (an Elk since 1993), Valeri has given 23-plus years of service. Highlights include Denver Elk of the Year (2004), two terms as Trustee, Elkettes Society President, Charity Queen, weekly Bingo volunteer, and multiple committee leadership roles.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Sources &amp; a note on research</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This account draws on primary historical sources including <em>An Authentic History of the Benevolent and Protective Order of Elks</em> by Charles Edward Ellis, a Bath Lodge&nbsp;#1547 publication (1986), and official Order documents and civil-rights-era legal cases. The history of the Elks around race and gender is an ongoing research project. Anyone with historical information to add can contact Jim Wolf.</p>
<!-- /wp:paragraph -->
BLOCKS;

// ── Locate the parent page ───────────────────────────────────────────────────
$parent    = get_page_by_path( $parent_slug );
$parent_id = $parent ? (int) $parent->ID : 0;
if ( ! $parent_id ) {
	WP_CLI::warning( sprintf(
		"Parent page '%s' not found. Creating this page at top level — set its parent manually once the short History page exists.",
		$parent_slug
	) );
}

// ── Find existing seeded page (idempotency) ──────────────────────────────────
$existing = get_posts( array(
	'post_type'   => 'page',
	'post_status' => 'any',
	'numberposts' => 1,
	'meta_key'    => '_elks_seed_key',
	'meta_value'  => $seed_key,
	'fields'      => 'ids',
) );
$existing_id = $existing ? (int) $existing[0] : 0;

// Fall back to slug match if the meta isn't there (e.g. page made by hand first).
if ( ! $existing_id ) {
	$by_slug = get_page_by_path( $child_slug );
	if ( $by_slug ) {
		$existing_id = (int) $by_slug->ID;
	}
}

// ── Build post array ─────────────────────────────────────────────────────────
$postarr = array(
	'post_type'    => 'page',
	'post_title'   => $child_title,
	'post_name'    => $child_slug,
	'post_status'  => 'publish',
	'post_author'  => $author_id,
	'post_parent'  => $parent_id,
	'post_content' => $content,
);

// wp_insert_post expects slashed data.
$postarr = wp_slash( $postarr );

if ( $existing_id ) {
	$postarr['ID'] = $existing_id;
	$result = wp_update_post( $postarr, true );
	$verb   = 'Updated';
} else {
	$result = wp_insert_post( $postarr, true );
	$verb   = 'Created';
}

if ( is_wp_error( $result ) ) {
	WP_CLI::error( 'Failed to save page: ' . $result->get_error_message() );
}

$page_id = (int) $result;
update_post_meta( $page_id, '_elks_seed_key', $seed_key );

WP_CLI::success( sprintf( '%s page #%d — %s', $verb, $page_id, get_permalink( $page_id ) ) );

// ── Print the link block to paste into the short History page ────────────────
$child_url = get_permalink( $page_id );
$link_block = <<<LINK
<!-- wp:paragraph -->
<p><a href="{$child_url}">Read the full history of the Elks &amp; Denver #17 &rarr;</a></p>
<!-- /wp:paragraph -->
LINK;

WP_CLI::log( '' );
WP_CLI::log( 'Next steps (manual, by design — this page is NOT in the nav):' );
WP_CLI::log( '  1. Add this link block to the body of the short "History of the Elks" page:' );
WP_CLI::log( '' );
WP_CLI::log( $link_block );
WP_CLI::log( '' );
WP_CLI::log( '  2. Theme CSS: the custom pieces carry className hooks the denver17 stylesheet' );
WP_CLI::log( '     should target — .elks-lede, .elks-vote / .elks-vote-win, .elks-statement,' );
WP_CLI::log( '     .elks-names (2-column name list). Without theme rules they degrade to plain' );
WP_CLI::log( '     core-block styling, which is still readable.' );
