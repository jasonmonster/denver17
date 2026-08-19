<?php
/**
 * Contact form markup.
 *
 * @package denver17
 *
 * @var array $args heading, intro
 */

defined( 'ABSPATH' ) || exit;

$heading = ! empty( $args['heading'] ) ? $args['heading'] : __( 'Get in touch', 'denver17' );
$intro   = ! empty( $args['intro'] ) ? $args['intro'] : '';

$topics = denver17_contact_topics();

// Recover state after a failed submission.
$errors = array();
$old    = array(
	'name'    => '',
	'email'   => '',
	'phone'   => '',
	'topic'   => 'general',
	'message' => '',
);

$sent = isset( $_GET['contact'] ) && 'sent' === $_GET['contact'];
$bad  = isset( $_GET['contact'] ) && 'error' === $_GET['contact'];

if ( isset( $_GET['ct'] ) && function_exists( 'denver17_contact_clean_token' ) ) {
	$state = get_transient( 'd17_contact_' . denver17_contact_clean_token( wp_unslash( $_GET['ct'] ) ) );
	if ( is_array( $state ) ) {
		$errors = isset( $state['errors'] ) ? (array) $state['errors'] : array();
		$old    = wp_parse_args( isset( $state['input'] ) ? (array) $state['input'] : array(), $old );
	}
}

// Something went wrong but the saved state has aged out — say so rather than
// re-rendering an apparently untouched form with no explanation.
if ( $bad && ! $errors ) {
	$errors = array( 'unknown' );
}

$messages = array(
	'name'      => __( 'Please add your name.', 'denver17' ),
	'email'     => __( 'That email address doesn\'t look right — check for a typo.', 'denver17' ),
	'message'   => __( 'Please write a message before sending.', 'denver17' ),
	'nolinks'   => __( 'Please remove any web links — this form doesn\'t accept them. Describe what you need and we\'ll follow up by email or phone.', 'denver17' ),
	'toofast'   => __( 'That came through faster than a person can type, so it was blocked as automated. Please send it again.', 'denver17' ),
	'stale'     => __( 'This page had been open a while. Please reload it and send your message again.', 'denver17' ),
	'unsigned'  => __( 'The form didn\'t submit properly. Please reload the page and try again.', 'denver17' ),
	'throttled' => __( 'That\'s several messages in a short time. Please try again in an hour, or call the lodge at 303-455-3557.', 'denver17' ),
	'server'    => __( 'Something went wrong saving your message. Please email or call the lodge instead.', 'denver17' ),
	'unknown'   => __( 'Your message didn\'t go through. Please reload the page and try again, or call the lodge at 303-455-3557.', 'denver17' ),
);

// Which specific inputs to flag.
$field_errors = array_intersect( $errors, array( 'name', 'email', 'message' ) );

// A rejected link is a problem with the message box.
if ( in_array( 'nolinks', $errors, true ) && ! in_array( 'message', $field_errors, true ) ) {
	$field_errors[] = 'message';
}

$first_bad = $field_errors ? reset( $field_errors ) : '';

/**
 * Print the class and ARIA attributes for a field.
 */
$field_attrs = function ( $field ) use ( $field_errors, $first_bad ) {
	$out = '';
	if ( in_array( $field, $field_errors, true ) ) {
		$out .= ' aria-invalid="true" aria-describedby="d17_' . $field . '_error"';
	}
	if ( $field === $first_bad ) {
		$out .= ' autofocus';
	}
	return $out;
};

$ts = time();
?>

<section class="contact-form" id="lodge-contact">

	<?php if ( $heading ) : ?>
		<h2 class="contact-form__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( $intro ) : ?>
		<p class="contact-form__intro"><?php echo esc_html( $intro ); ?></p>
	<?php endif; ?>

	<?php if ( $sent ) : ?>

		<div class="contact-form__success" role="status">
			<p class="contact-form__success-head"><?php esc_html_e( 'Message sent.', 'denver17' ); ?></p>
			<p><?php esc_html_e( 'Thanks — it\'s on its way to the lodge secretary, and someone will get back to you. If it\'s urgent, call 303-455-3557.', 'denver17' ); ?></p>
		</div>

	<?php else : ?>

		<?php if ( $errors ) : ?>
			<div class="contact-form__errors" role="alert" tabindex="-1">
				<p class="contact-form__errors-head"><?php esc_html_e( 'Your message wasn\'t sent.', 'denver17' ); ?></p>
				<?php
				$shown = array();
				foreach ( $errors as $code ) :
					if ( ! isset( $messages[ $code ] ) || isset( $shown[ $code ] ) ) {
						continue;
					}
					$shown[ $code ] = true;
					?>
					<p><?php echo esc_html( $messages[ $code ] ); ?></p>
				<?php endforeach; ?>

				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					<p class="contact-form__debug"><?php echo esc_html( 'Debug: ' . implode( ', ', $errors ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form class="contact-form__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<input type="hidden" name="action" value="denver17_contact">
			<input type="hidden" name="d17_ts" value="<?php echo esc_attr( $ts ); ?>">
			<input type="hidden" name="d17_th" value="<?php echo esc_attr( wp_hash( $ts . '|d17contact' ) ); ?>">
			<?php // No nonce by design — see the note at the top of inc/contact-form.php. ?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wp_unslash( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) ); ?>">

			<p class="contact-form__field<?php echo in_array( 'name', $field_errors, true ) ? ' contact-form__field--error' : ''; ?>">
				<label for="d17_name"><?php esc_html_e( 'Your name', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<input type="text" id="d17_name" name="d17_name" required autocomplete="name"
					value="<?php echo esc_attr( $old['name'] ); ?>"<?php echo $field_attrs( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php if ( in_array( 'name', $field_errors, true ) ) : ?>
					<span class="contact-form__field-error" id="d17_name_error"><?php echo esc_html( $messages['name'] ); ?></span>
				<?php endif; ?>
			</p>

			<p class="contact-form__field<?php echo in_array( 'email', $field_errors, true ) ? ' contact-form__field--error' : ''; ?>">
				<label for="d17_email"><?php esc_html_e( 'Email', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<input type="email" id="d17_email" name="d17_email" required autocomplete="email"
					value="<?php echo esc_attr( $old['email'] ); ?>"<?php echo $field_attrs( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php if ( in_array( 'email', $field_errors, true ) ) : ?>
					<span class="contact-form__field-error" id="d17_email_error"><?php echo esc_html( $messages['email'] ); ?></span>
				<?php endif; ?>
			</p>

			<p class="contact-form__field">
				<label for="d17_phone"><?php esc_html_e( 'Phone', 'denver17' ); ?> <span class="contact-form__optional"><?php esc_html_e( '(optional)', 'denver17' ); ?></span></label>
				<input type="tel" id="d17_phone" name="d17_phone" autocomplete="tel"
					value="<?php echo esc_attr( $old['phone'] ); ?>">
			</p>

			<p class="contact-form__field">
				<label for="d17_topic"><?php esc_html_e( 'What\'s this about?', 'denver17' ); ?></label>
				<select id="d17_topic" name="d17_topic">
					<?php foreach ( $topics as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $old['topic'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="contact-form__field<?php echo in_array( 'message', $field_errors, true ) ? ' contact-form__field--error' : ''; ?>">
				<label for="d17_message"><?php esc_html_e( 'Message', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="d17_message" name="d17_message" rows="6" required<?php echo $field_attrs( 'message' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_textarea( $old['message'] ); ?></textarea>
				<?php if ( in_array( 'message', $field_errors, true ) ) : ?>
					<span class="contact-form__field-error" id="d17_message_error"><?php echo esc_html( in_array( 'nolinks', $errors, true ) ? $messages['nolinks'] : $messages['message'] ); ?></span>
				<?php endif; ?>
			</p>

			<?php // Honeypot. Hidden from people, irresistible to bots. ?>
			<div class="contact-form__hp" aria-hidden="true">
				<label for="d17_website"><?php esc_html_e( 'Leave this field empty', 'denver17' ); ?></label>
				<input type="text" id="d17_website" name="d17_website" tabindex="-1" autocomplete="off" value="">
			</div>

			<p class="contact-form__actions">
				<button type="submit" class="contact-form__submit"><?php esc_html_e( 'Send message', 'denver17' ); ?></button>
			</p>

		</form>

	<?php endif; ?>

</section>
