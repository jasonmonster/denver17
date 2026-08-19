<?php
/**
 * Contact form — storage, submission handling, notification email.
 *
 * Every submission is written to the elks_contact_msg CPT FIRST, then emailed.
 * If wp_mail() fails the message is still recorded in wp-admin under
 * "Contact Messages" and flagged as undelivered, so nothing is ever lost to a
 * mail problem (a known risk on this host).
 *
 * No third-party service, no JS, no captcha. Spam is handled by a honeypot
 * field, a hash-signed minimum-fill-time trap, a per-IP rate limit, and
 * content scoring.
 *
 * There is deliberately no nonce. This form is unauthenticated and takes no
 * action on behalf of a logged-in user, so CSRF buys an attacker nothing —
 * while a nonce on a page served from SpinupWP's FastCGI cache goes stale and
 * fails real submissions. The signed timestamp still prevents a forged POST
 * from outside the form.
 *
 * @package denver17
 */

defined( 'ABSPATH' ) || exit;

define( 'DENVER17_CONTACT_CPT', 'elks_contact_msg' );
define( 'DENVER17_CONTACT_MIN_SECONDS', 3 );      // Faster than this is a bot.
define( 'DENVER17_CONTACT_MAX_PER_HOUR', 5 );     // Per IP.
define( 'DENVER17_CONTACT_SPAM_THRESHOLD', 3 );   // Score at or above this = spam.

/**
 * Topic list. Keys are stored; values are shown.
 */
function denver17_contact_topics() {
	return apply_filters(
		'denver17_contact_topics',
		array(
			'general'    => __( 'General question', 'denver17' ),
			'membership' => __( 'Membership', 'denver17' ),
			'rentals'    => __( 'Facility rental', 'denver17' ),
			'events'     => __( 'Events & tickets', 'denver17' ),
			'website'    => __( 'Website issue', 'denver17' ),
		)
	);
}

/**
 * Sanitise one address or a comma-separated list. Invalid entries are dropped
 * rather than failing the whole field.
 */
function denver17_contact_sanitize_emails( $value ) {
	$out = array();

	foreach ( explode( ',', (string) $value ) as $address ) {
		$address = sanitize_email( trim( $address ) );
		if ( is_email( $address ) ) {
			$out[] = $address;
		}
	}

	return implode( ', ', array_unique( $out ) );
}

/**
 * Where notifications go. Customizer field first, admin email as fallback.
 * Accepts a comma-separated list — wp_mail() sends to all of them, and they
 * see each other in the To line, which is fine for lodge officers.
 *
 * Filterable so a topic can be routed elsewhere later without touching this file.
 */
function denver17_contact_recipient( $topic = '' ) {
	$to = denver17_contact_sanitize_emails( get_theme_mod( 'denver17_contact_email' ) );

	if ( '' === $to ) {
		$to = get_option( 'admin_email' );
	}

	return apply_filters( 'denver17_contact_recipient', $to, $topic );
}

/* -------------------------------------------------------------------------
 * CPT
 * ---------------------------------------------------------------------- */

add_action( 'init', 'denver17_contact_register_cpt' );
function denver17_contact_register_cpt() {
	register_post_type(
		DENVER17_CONTACT_CPT,
		array(
			'labels'              => array(
				'name'          => __( 'Contact Messages', 'denver17' ),
				'singular_name' => __( 'Contact Message', 'denver17' ),
				'menu_name'     => __( 'Contact Messages', 'denver17' ),
				'search_items'  => __( 'Search messages', 'denver17' ),
				'not_found'     => __( 'No messages yet.', 'denver17' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			// Messages arrive from the form only — no "Add New".
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		)
	);
}

/**
 * Suspected spam gets its own status rather than being thrown away — a false
 * positive is recoverable, a deleted message from a real member isn't.
 */
add_action( 'init', 'denver17_contact_register_spam_status' );
function denver17_contact_register_spam_status() {
	register_post_status(
		'elks_spam',
		array(
			'label'                     => _x( 'Spam', 'post status', 'denver17' ),
			'public'                    => false,
			'internal'                  => false,
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of messages */
			'label_count'               => _n_noop( 'Spam <span class="count">(%s)</span>', 'Spam <span class="count">(%s)</span>', 'denver17' ),
		)
	);
}

/**
 * Does this text contain a web link?
 *
 * Nobody contacting a lodge about a rental needs to send a URL, and every
 * marketing bot does. Catches schemes, bare www., markdown/BBCode, and bare
 * domains like cutt.ly — with email addresses and the lodge's own domains
 * removed first, since people legitimately type both.
 */
function denver17_contact_has_link( $text ) {

	$allowed = apply_filters(
		'denver17_contact_allowed_domains',
		array( 'denverelks.org', 'elks.org', 'elks.torreys.brighthosted.com' )
	);

	// Email addresses are not links.
	$check = preg_replace( '/[\w.+-]+@[\w-]+\.[\w.-]+/u', ' ', (string) $text );

	foreach ( $allowed as $domain ) {
		$check = str_ireplace( $domain, ' ', $check );
	}

	// Explicit schemes and www.
	if ( preg_match( '#(https?:)?//|www\.|ftp://#i', $check ) ) {
		return true;
	}

	// Markdown / BBCode link syntax.
	if ( preg_match( '#\[url|\]\(\s*http|<a\s+href#i', (string) $text ) ) {
		return true;
	}

	// Bare domains: cutt.ly, tinyurl.com, example.shop and friends.
	$tlds = 'com|net|org|io|co|ly|me|us|uk|de|ru|cn|in|info|biz|xyz|top|site|online|shop|store|club|link|live|app|dev|ai|pro|vip|cc|tv|gg|sh|to|be|space|website|agency|digital|marketing';

	if ( preg_match( '#\b[a-z0-9][a-z0-9-]{1,63}\.(' . $tlds . ')\b#i', $check ) ) {
		return true;
	}

	return false;
}

/**
 * Hard blocklist. Any match is filed as spam regardless of score.
 * Filter it with a list of lowercase substrings matched against the email
 * address, its domain, and the IP.
 */
function denver17_contact_blocked( $input, $ip ) {

	$list = apply_filters( 'denver17_contact_blocklist', array() );

	if ( ! $list ) {
		return false;
	}

	$haystack = strtolower( $input['email'] . ' ' . $ip );

	foreach ( $list as $needle ) {
		$needle = strtolower( trim( $needle ) );
		if ( '' !== $needle && false !== strpos( $haystack, $needle ) ) {
			return true;
		}
	}

	return false;
}

/* -------------------------------------------------------------------------
 * Spam scoring
 * ---------------------------------------------------------------------- */

/**
 * Score a submission. Higher is worse. 0 for anything a member would send.
 *
 * Deliberately cheap and local — no API call, no shared blocklist, nothing
 * that stops working when a third-party key expires in two years.
 *
 * @return array{score:int,reasons:string[]}
 */
function denver17_contact_spam_score( $input ) {

	$score   = 0;
	$reasons = array();
	$message = $input['message'];
	$name    = $input['name'];

	// Links. A member asking about a rental doesn't paste four URLs.
	$links = preg_match_all( '#(https?://|www\.)#i', $message );
	if ( $links >= 4 ) {
		$score  += 4;
		$reasons[] = 'links:' . $links;
	} elseif ( $links >= 2 ) {
		$score  += 2;
		$reasons[] = 'links:' . $links;
	}

	// Raw HTML/BBCode markup survived sanitising as literal text.
	if ( preg_match( '#\[url=|\[/url\]|<a\s+href#i', $message ) ) {
		$score    += 4;
		$reasons[] = 'markup';
	}

	// A URL or an email address in the name field is never a person.
	if ( preg_match( '#(https?://|www\.|@)#i', $name ) ) {
		$score    += 4;
		$reasons[] = 'name-url';
	}

	// Scripts nobody contacting this lodge writes in.
	if ( preg_match( '/[\p{Cyrillic}\p{Han}\p{Hangul}\p{Hiragana}\p{Katakana}\p{Arabic}\p{Thai}]/u', $name . ' ' . $message ) ) {
		$score    += 4;
		$reasons[] = 'script';
	}

	// Payload vocabulary. Cheap, and it catches the SEO outreach flood.
	$terms = apply_filters(
		'denver17_contact_spam_terms',
		array(
			'seo', 'backlink', 'link building', 'guest post', 'domain authority',
			'first page of google', 'search engine ranking', 'search rankings',
			'increase traffic', 'more traffic', 'website traffic', 'your traffic',
			'web design services', 'digital marketing', 'marketing agency',
			'lead generation', 'more leads', 'leads and sales', 'grow your business',
			'boost your', 'scale your', 'skyrocket',
			'free trial', 'no contracts', 'cancel anytime', 'no obligation',
			'crypto', 'bitcoin', 'forex', 'casino', 'viagra', 'cialis',
			'payday loan', 'escort', 'porn', 'nude', 'earn money',
			'work from home', 'binary option', 'nft', 'unsubscribe',
			'i came across your website', 'i noticed your website',
			'visitors to your site', 'to your site',
		)
	);

	$haystack = strtolower( $name . ' ' . $message );
	$hits     = 0;

	foreach ( $terms as $term ) {
		if ( false !== strpos( $haystack, $term ) ) {
			$hits++;
			$reasons[] = 'term:' . $term;
		}
	}

	if ( $hits ) {
		$score += min( 5, $hits * 2 );
	}

	// Cold sales pitch shape: selling AT the lodge rather than asking it
	// something. Any two of these together is a pitch, not a question.
	$pitch = 0;

	foreach ( array( 'your website', 'your site', 'your business', 'your customers', 'your company' ) as $p ) {
		if ( false !== strpos( $haystack, $p ) ) {
			$pitch++;
			$reasons[] = 'pitch:' . $p;
			break;
		}
	}

	foreach ( array( 'we can', 'we help', 'we offer', 'we provide', 'our team', 'our service', 'our platform', 'our ai', 'let me know if you', 'interested?', 'reply to this', 'book a call', 'schedule a call', 'quick question about' ) as $p ) {
		if ( false !== strpos( $haystack, $p ) ) {
			$pitch++;
			$reasons[] = 'pitch:' . $p;
			break;
		}
	}

	foreach ( array( 'clients see', 'most clients', 'guaranteed', 'roi', 'conversion', 'pricing starts', 'per month', '/mo', 'discount', 'special offer' ) as $p ) {
		if ( false !== strpos( $haystack, $p ) ) {
			$pitch++;
			$reasons[] = 'pitch:' . $p;
			break;
		}
	}

	if ( $pitch >= 2 ) {
		$score += 4;
	} elseif ( $pitch ) {
		$score += 2;
	}

	// Domain that can't receive mail is a made-up address.
	$parts  = explode( '@', $input['email'] );
	$domain = isset( $parts[1] ) ? $parts[1] : '';

	if ( $domain && function_exists( 'checkdnsrr' ) ) {
		if ( ! checkdnsrr( $domain, 'MX' ) && ! checkdnsrr( $domain, 'A' ) ) {
			$score    += 4;
			$reasons[] = 'nomx';
		}
	}

	// Same body already submitted in the last 24 hours (blast across the form).
	$fingerprint = 'd17_contact_fp_' . md5( $haystack );
	if ( get_transient( $fingerprint ) ) {
		$score    += 3;
		$reasons[] = 'duplicate';
	}
	set_transient( $fingerprint, 1, DAY_IN_SECONDS );

	// Wall of text with no whitespace variety, or a one-word "message".
	if ( mb_strlen( $message ) > 1500 && substr_count( $message, ' ' ) < 40 ) {
		$score    += 3;
		$reasons[] = 'blob';
	}

	/**
	 * Final say on the score. Return 0 to force-allow, a big number to block.
	 */
	$score = (int) apply_filters( 'denver17_contact_spam_score', $score, $input, $reasons );

	return array(
		'score'   => $score,
		'reasons' => $reasons,
	);
}

/* -------------------------------------------------------------------------
 * Submission handling
 * ---------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_denver17_contact', 'denver17_contact_handle' );
add_action( 'admin_post_denver17_contact', 'denver17_contact_handle' );

function denver17_contact_handle() {

	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}

	$input = array(
		'name'    => isset( $_POST['d17_name'] ) ? sanitize_text_field( wp_unslash( $_POST['d17_name'] ) ) : '',
		'email'   => isset( $_POST['d17_email'] ) ? sanitize_email( wp_unslash( $_POST['d17_email'] ) ) : '',
		'phone'   => isset( $_POST['d17_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['d17_phone'] ) ) : '',
		'topic'   => isset( $_POST['d17_topic'] ) ? sanitize_key( wp_unslash( $_POST['d17_topic'] ) ) : 'general',
		'message' => isset( $_POST['d17_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['d17_message'] ) ) : '',
	);

	// --- Honeypot: real people never fill this in ------------------------
	if ( ! empty( $_POST['d17_website'] ) ) {
		// Pretend it worked. Bots shouldn't learn they were caught.
		denver17_contact_done( $redirect );
	}

	// --- Time trap -------------------------------------------------------
	$ts   = isset( $_POST['d17_ts'] ) ? absint( $_POST['d17_ts'] ) : 0;
	$hash = isset( $_POST['d17_th'] ) ? wp_unslash( $_POST['d17_th'] ) : '';

	if ( ! $ts || ! hash_equals( wp_hash( $ts . '|d17contact' ), $hash ) ) {
		denver17_contact_bail( $redirect, array( 'unsigned' ), $input );
	}

	$elapsed = time() - $ts;

	// Submitted in under three seconds — nobody types that fast.
	if ( $elapsed < DENVER17_CONTACT_MIN_SECONDS ) {
		denver17_contact_bail( $redirect, array( 'toofast' ), $input );
	}

	// Page sat open (or was served from cache) for more than a week.
	if ( $elapsed > WEEK_IN_SECONDS ) {
		denver17_contact_bail( $redirect, array( 'stale' ), $input );
	}

	// --- Rate limit ------------------------------------------------------
	$ip  = denver17_contact_ip();
	$key = 'd17_contact_ip_' . md5( $ip );
	$hits = (int) get_transient( $key );

	if ( $hits >= DENVER17_CONTACT_MAX_PER_HOUR ) {
		denver17_contact_bail( $redirect, array( 'throttled' ), $input );
	}

	// --- Validate --------------------------------------------------------
	$errors = array();
	$topics = denver17_contact_topics();

	if ( '' === $input['name'] ) {
		$errors[] = 'name';
	}
	if ( ! is_email( $input['email'] ) ) {
		$errors[] = 'email';
	}
	if ( mb_strlen( $input['message'] ) < 5 ) {
		$errors[] = 'message';
	}
	if ( denver17_contact_has_link( $input['message'] ) || denver17_contact_has_link( $input['name'] ) || denver17_contact_has_link( $input['phone'] ) ) {
		$errors[] = 'nolinks';
	}
	if ( ! isset( $topics[ $input['topic'] ] ) ) {
		$input['topic'] = 'general';
	}

	if ( $errors ) {
		denver17_contact_bail( $redirect, $errors, $input );
	}

	$input['name']    = mb_substr( $input['name'], 0, 120 );
	$input['phone']   = mb_substr( $input['phone'], 0, 40 );
	$input['message'] = mb_substr( $input['message'], 0, 5000 );

	set_transient( $key, $hits + 1, HOUR_IN_SECONDS );

	// --- Score, then store first, mail second ----------------------------
	$topic_label = $topics[ $input['topic'] ];
	$spam        = denver17_contact_spam_score( $input );
	$is_spam     = $spam['score'] >= DENVER17_CONTACT_SPAM_THRESHOLD;

	if ( denver17_contact_blocked( $input, $ip ) ) {
		$is_spam           = true;
		$spam['reasons'][] = 'blocklist';
	}

	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => DENVER17_CONTACT_CPT,
				'post_status'  => $is_spam ? 'elks_spam' : 'publish',
				'post_title'   => sprintf( '%s — %s', $input['name'], $topic_label ),
				'post_content' => $input['message'],
			)
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		denver17_contact_bail( $redirect, array( 'server' ), $input );
	}

	update_post_meta( $post_id, '_contact_name', $input['name'] );
	update_post_meta( $post_id, '_contact_email', $input['email'] );
	update_post_meta( $post_id, '_contact_phone', $input['phone'] );
	update_post_meta( $post_id, '_contact_topic', $input['topic'] );
	update_post_meta( $post_id, '_contact_ip', $ip );
	update_post_meta( $post_id, '_contact_ua', mb_substr( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '', 0, 255 ) );
	update_post_meta( $post_id, '_contact_spam_score', $spam['score'] );
	update_post_meta( $post_id, '_contact_spam_reasons', implode( ', ', $spam['reasons'] ) );

	// --- Notify ----------------------------------------------------------
	// Spam is filed, never emailed. The sender sees the same success screen so
	// a bot can't tell it was caught (and a false positive isn't insulted).
	if ( $is_spam ) {
		update_post_meta( $post_id, '_contact_mail_sent', 0 );
	} else {
		$sent = denver17_contact_notify( $post_id, $input, $topic_label );
		update_post_meta( $post_id, '_contact_mail_sent', $sent ? 1 : 0 );
	}

	denver17_contact_done( $redirect );
}

/**
 * Send the notification. Returns bool.
 */
function denver17_contact_notify( $post_id, $input, $topic_label ) {

	$to = denver17_contact_recipient( $input['topic'] );
	if ( '' === trim( (string) $to ) ) {
		return false;
	}

	$subject = sprintf(
		/* translators: 1: topic, 2: sender name */
		__( '[Denver Elks #17] %1$s — %2$s', 'denver17' ),
		$topic_label,
		$input['name']
	);

	$when = wp_date( 'F j, Y \a\t g:i a', null, wp_timezone() );

	$lines = array(
		sprintf( 'From:    %s', $input['name'] ),
		sprintf( 'Email:   %s', $input['email'] ),
		sprintf( 'Phone:   %s', $input['phone'] ? $input['phone'] : '—' ),
		sprintf( 'Topic:   %s', $topic_label ),
		sprintf( 'Sent:    %s', $when ),
		'',
		'---',
		'',
		$input['message'],
		'',
		'---',
		'',
		'Reply directly to this email to answer them.',
		'A copy is saved on the website: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
	);

	$body = implode( "\n", $lines );

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		sprintf( 'Reply-To: %s <%s>', $input['name'], $input['email'] ),
	);

	/**
	 * Last chance to change recipient/subject/body/headers.
	 */
	$mail = apply_filters(
		'denver17_contact_mail',
		compact( 'to', 'subject', 'body', 'headers' ),
		$post_id,
		$input
	);

	// Capture the reason if PHPMailer throws, so it lands on the record.
	$capture = function ( $wp_error ) use ( $post_id ) {
		update_post_meta( $post_id, '_contact_mail_error', $wp_error->get_error_message() );
	};
	add_action( 'wp_mail_failed', $capture );

	$sent = wp_mail( $mail['to'], $mail['subject'], $mail['body'], $mail['headers'] );

	remove_action( 'wp_mail_failed', $capture );

	return (bool) $sent;
}

/**
 * Redirect helpers.
 */
function denver17_contact_done( $redirect ) {
	wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) . '#lodge-contact' );
	exit;
}

/**
 * Sanitise the state token from the URL. Lowercase alphanumerics only, which
 * is exactly what denver17_contact_bail() generates — do not swap this for
 * sanitize_key(), which lowercases and would silently miss a mixed-case token.
 */
function denver17_contact_clean_token( $raw ) {
	return substr( preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $raw ) ), 0, 32 );
}

function denver17_contact_bail( $redirect, $errors, $input ) {
	$token = strtolower( wp_generate_password( 20, false, false ) );

	set_transient(
		'd17_contact_' . $token,
		array(
			'errors' => $errors,
			'input'  => $input,
		),
		15 * MINUTE_IN_SECONDS
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'contact' => 'error',
				'ct'      => $token,
			),
			$redirect
		) . '#lodge-contact'
	);
	exit;
}

/**
 * Client IP. If Cloudflare is ever put in front of this site, add
 * HTTP_CF_CONNECTING_IP here or the rate limit will see one shared address.
 */
function denver17_contact_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '0.0.0.0';
}

/* -------------------------------------------------------------------------
 * Rendering: block render callback + shortcode
 * ---------------------------------------------------------------------- */

function denver17_contact_form_render( $attributes = array(), $content = '', $block = null ) {
	ob_start();
	get_template_part(
		'template-parts/blocks/contact-form',
		null,
		array(
			'heading' => isset( $attributes['heading'] ) ? $attributes['heading'] : '',
			'intro'   => isset( $attributes['intro'] ) ? $attributes['intro'] : '',
		)
	);
	return ob_get_clean();
}

add_shortcode( 'denver17_contact_form', 'denver17_contact_form_shortcode' );
function denver17_contact_form_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'heading' => '',
			'intro'   => '',
		),
		$atts,
		'denver17_contact_form'
	);
	return denver17_contact_form_render( $atts );
}

/*
 * Block registration at priority 20, so if the theme already auto-registers
 * everything in blocks/ at the default priority its version wins and this is
 * a no-op. Nothing to comment out either way.
 */
add_action( 'init', 'denver17_contact_register_block', 20 );
function denver17_contact_register_block() {

	if ( class_exists( 'WP_Block_Type_Registry' )
		&& WP_Block_Type_Registry::get_instance()->is_registered( 'denver17/contact-form' ) ) {
		return;
	}

	$dir = get_theme_file_path( 'blocks/contact-form' );

	if ( file_exists( $dir . '/block.json' ) ) {
		register_block_type( $dir, array( 'render_callback' => 'denver17_contact_form_render' ) );
	}
}

/**
 * Stylesheet. Self-contained rather than appended to the theme's main CSS, so
 * dropping this feature in or out is a file operation, not a diff.
 */
add_action( 'wp_enqueue_scripts', 'denver17_contact_styles' );
function denver17_contact_styles() {
	$rel  = 'assets/css/contact-form.css';
	$path = get_theme_file_path( $rel );

	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_style(
		'denver17-contact-form',
		get_theme_file_uri( $rel ),
		array(),
		filemtime( $path )
	);
}

/**
 * Customizer field for the notification address. Skipped entirely if the
 * theme already registers a setting by this name.
 */
add_action( 'customize_register', 'denver17_contact_customizer', 20 );
function denver17_contact_customizer( $wp_customize ) {

	if ( $wp_customize->get_setting( 'denver17_contact_email' ) ) {
		return;
	}

	if ( ! $wp_customize->get_section( 'denver17_contact' ) ) {
		$wp_customize->add_section(
			'denver17_contact',
			array(
				'title'    => __( 'Contact', 'denver17' ),
				'priority' => 90,
			)
		);
	}

	$wp_customize->add_setting(
		'denver17_contact_email',
		array(
			'default'           => get_option( 'admin_email' ),
			'sanitize_callback' => 'denver17_contact_sanitize_emails',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'denver17_contact_email',
		array(
			'label'       => __( 'Contact form recipient', 'denver17' ),
			'description' => __( 'Where contact form messages are emailed. Separate multiple addresses with commas. Defaults to the site admin address.', 'denver17' ),
			'section'     => 'denver17_contact',
			'type'        => 'text',
		)
	);
}

/* -------------------------------------------------------------------------
 * Admin: columns, detail panel, delivery-failure notice
 * ---------------------------------------------------------------------- */

add_filter( 'manage_' . DENVER17_CONTACT_CPT . '_posts_columns', 'denver17_contact_columns' );
function denver17_contact_columns( $columns ) {
	return array(
		'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'       => __( 'From', 'denver17' ),
		'd17_email'   => __( 'Email', 'denver17' ),
		'd17_phone'   => __( 'Phone', 'denver17' ),
		'd17_excerpt' => __( 'Message', 'denver17' ),
		'd17_sent'    => __( 'Emailed', 'denver17' ),
		'date'        => __( 'Received', 'denver17' ),
	);
}

add_action( 'manage_' . DENVER17_CONTACT_CPT . '_posts_custom_column', 'denver17_contact_column_content', 10, 2 );
function denver17_contact_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'd17_email':
			$email = get_post_meta( $post_id, '_contact_email', true );
			if ( $email ) {
				printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
			}
			break;

		case 'd17_phone':
			echo esc_html( get_post_meta( $post_id, '_contact_phone', true ) );
			break;

		case 'd17_excerpt':
			echo esc_html( wp_trim_words( get_post_field( 'post_content', $post_id ), 14 ) );
			break;

		case 'd17_sent':
			if ( 'elks_spam' === get_post_status( $post_id ) ) {
				printf(
					'<span title="%s">%s</span>',
					esc_attr( get_post_meta( $post_id, '_contact_spam_reasons', true ) ),
					esc_html__( 'Held as spam', 'denver17' )
				);
			} elseif ( get_post_meta( $post_id, '_contact_mail_sent', true ) ) {
				echo '<span style="color:#2E8B4F">&#10003;</span>';
			} else {
				$err = get_post_meta( $post_id, '_contact_mail_error', true );
				printf(
					'<strong style="color:#b32d2e" title="%s">%s</strong>',
					esc_attr( $err ? $err : __( 'Delivery failed', 'denver17' ) ),
					esc_html__( 'Failed', 'denver17' )
				);
			}
			break;
	}
}

add_action( 'add_meta_boxes', 'denver17_contact_meta_box' );
function denver17_contact_meta_box() {
	add_meta_box(
		'denver17_contact_details',
		__( 'Sender details', 'denver17' ),
		'denver17_contact_meta_box_render',
		DENVER17_CONTACT_CPT,
		'side',
		'high'
	);
}

function denver17_contact_meta_box_render( $post ) {
	$topics = denver17_contact_topics();
	$topic  = get_post_meta( $post->ID, '_contact_topic', true );
	$email  = get_post_meta( $post->ID, '_contact_email', true );
	$phone  = get_post_meta( $post->ID, '_contact_phone', true );
	$sent   = get_post_meta( $post->ID, '_contact_mail_sent', true );
	$err    = get_post_meta( $post->ID, '_contact_mail_error', true );

	echo '<p><strong>' . esc_html__( 'Name', 'denver17' ) . ':</strong><br>' . esc_html( get_post_meta( $post->ID, '_contact_name', true ) ) . '</p>';

	if ( $email ) {
		printf(
			'<p><strong>%s:</strong><br><a href="mailto:%s">%s</a></p>',
			esc_html__( 'Email', 'denver17' ),
			esc_attr( $email ),
			esc_html( $email )
		);
	}

	if ( $phone ) {
		echo '<p><strong>' . esc_html__( 'Phone', 'denver17' ) . ':</strong><br>' . esc_html( $phone ) . '</p>';
	}

	if ( isset( $topics[ $topic ] ) ) {
		echo '<p><strong>' . esc_html__( 'Topic', 'denver17' ) . ':</strong><br>' . esc_html( $topics[ $topic ] ) . '</p>';
	}

	if ( 'elks_spam' === get_post_status( $post ) ) {
		printf(
			'<p style="padding:8px;background:#fdf0f0;border-left:3px solid #b32d2e"><strong>%s</strong> (%s %d)<br><em>%s</em><br><br><a class="button" href="%s">%s</a></p>',
			esc_html__( 'Held as spam', 'denver17' ),
			esc_html__( 'score', 'denver17' ),
			(int) get_post_meta( $post->ID, '_contact_spam_score', true ),
			esc_html( get_post_meta( $post->ID, '_contact_spam_reasons', true ) ),
			esc_url(
				wp_nonce_url(
					admin_url( 'admin-post.php?action=denver17_contact_release&post=' . $post->ID ),
					'denver17_contact_release_' . $post->ID
				)
			),
			esc_html__( 'Not spam — deliver it', 'denver17' )
		);
	}

	echo '<p><strong>' . esc_html__( 'Email notification', 'denver17' ) . ':</strong><br>';
	if ( 'elks_spam' === get_post_status( $post ) ) {
		echo esc_html__( 'Not sent', 'denver17' );
	} elseif ( $sent ) {
		echo esc_html__( 'Delivered', 'denver17' );
	} else {
		echo '<span style="color:#b32d2e">' . esc_html__( 'Not delivered', 'denver17' ) . '</span>';
		if ( $err ) {
			echo '<br><em>' . esc_html( $err ) . '</em>';
		}
	}
	echo '</p>';
}

/**
 * "Not spam — deliver it": publish the message and send the notification
 * that was withheld.
 */
add_action( 'admin_post_denver17_contact_release', 'denver17_contact_release' );
function denver17_contact_release() {

	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'denver17' ) );
	}

	check_admin_referer( 'denver17_contact_release_' . $post_id );

	$post = get_post( $post_id );

	if ( ! $post || DENVER17_CONTACT_CPT !== $post->post_type ) {
		wp_die( esc_html__( 'Message not found.', 'denver17' ) );
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		)
	);

	$topics = denver17_contact_topics();
	$topic  = get_post_meta( $post_id, '_contact_topic', true );

	$input = array(
		'name'    => get_post_meta( $post_id, '_contact_name', true ),
		'email'   => get_post_meta( $post_id, '_contact_email', true ),
		'phone'   => get_post_meta( $post_id, '_contact_phone', true ),
		'topic'   => $topic,
		'message' => $post->post_content,
	);

	$label = isset( $topics[ $topic ] ) ? $topics[ $topic ] : $topic;
	$sent  = denver17_contact_notify( $post_id, $input, $label );

	update_post_meta( $post_id, '_contact_mail_sent', $sent ? 1 : 0 );

	wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
	exit;
}

/**
 * Loud notice when anything failed to send in the last 30 days — the whole
 * point of storing these is that a mail outage gets noticed.
 */
add_action( 'admin_notices', 'denver17_contact_failure_notice' );
function denver17_contact_failure_notice() {
	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-' . DENVER17_CONTACT_CPT ), true ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$failed = get_posts(
		array(
			'post_type'      => DENVER17_CONTACT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'date_query'     => array( array( 'after' => '30 days ago' ) ),
			'meta_query'     => array(
				array(
					'key'   => '_contact_mail_sent',
					'value' => '0',
				),
			),
		)
	);

	if ( ! $failed ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
		esc_html__( 'Contact form:', 'denver17' ),
		esc_html(
			sprintf(
				/* translators: %d: number of messages */
				_n(
					'%d message could not be emailed in the last 30 days. It is saved here but nobody was notified.',
					'%d messages could not be emailed in the last 30 days. They are saved here but nobody was notified.',
					count( $failed ),
					'denver17'
				),
				count( $failed )
			)
		),
		esc_url( admin_url( 'edit.php?post_type=' . DENVER17_CONTACT_CPT ) ),
		esc_html__( 'Review them', 'denver17' )
	);
}

/* -------------------------------------------------------------------------
 * Optional retention pruning. Returns 0 by default = keep everything.
 * Filter to e.g. 365 to trash messages older than a year.
 * ---------------------------------------------------------------------- */

add_action( 'init', 'denver17_contact_schedule_prune' );
function denver17_contact_schedule_prune() {
	if ( ! wp_next_scheduled( 'denver17_contact_prune' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'denver17_contact_prune' );
	}
}

add_action( 'denver17_contact_prune', 'denver17_contact_prune_old' );
function denver17_contact_prune_old() {

	// Held spam is deleted after 30 days — long enough to spot a false
	// positive, short enough that the list doesn't fill up.
	$spam_days = (int) apply_filters( 'denver17_contact_spam_retention_days', 30 );

	if ( $spam_days > 0 ) {
		$spam = get_posts(
			array(
				'post_type'      => DENVER17_CONTACT_CPT,
				'post_status'    => 'elks_spam',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array( array( 'before' => $spam_days . ' days ago' ) ),
			)
		);

		foreach ( $spam as $id ) {
			wp_delete_post( $id, true );
		}
	}

	$days = (int) apply_filters( 'denver17_contact_retention_days', 0 );

	if ( $days < 1 ) {
		return;
	}

	$old = get_posts(
		array(
			'post_type'      => DENVER17_CONTACT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'date_query'     => array( array( 'before' => $days . ' days ago' ) ),
		)
	);

	foreach ( $old as $id ) {
		wp_trash_post( $id );
	}
}
