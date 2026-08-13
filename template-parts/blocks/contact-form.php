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

if ( isset( $_GET['ct'] ) ) {
	$state = get_transient( 'd17_contact_' . sanitize_key( wp_unslash( $_GET['ct'] ) ) );
	if ( is_array( $state ) ) {
		$errors = isset( $state['errors'] ) ? (array) $state['errors'] : array();
		$old    = wp_parse_args( isset( $state['input'] ) ? (array) $state['input'] : array(), $old );
	}
}

$messages = array(
	'name'      => __( 'Please add your name.', 'denver17' ),
	'email'     => __( 'That email address doesn\'t look right.', 'denver17' ),
	'message'   => __( 'Please write a message.', 'denver17' ),
	'expired'   => __( 'That form had been open a while and expired. Please send it again.', 'denver17' ),
	'throttled' => __( 'That\'s a few messages in a short time. Try again in an hour, or call the lodge.', 'denver17' ),
	'server'    => __( 'Something went wrong saving your message. Please call the lodge instead.', 'denver17' ),
);

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

		<p class="contact-form__success" role="status">
			<?php esc_html_e( 'Thanks — your message is on its way to the lodge. Someone will get back to you.', 'denver17' ); ?>
		</p>

	<?php else : ?>

		<?php if ( $errors ) : ?>
			<div class="contact-form__errors" role="alert">
				<?php foreach ( $errors as $code ) : ?>
					<?php if ( isset( $messages[ $code ] ) ) : ?>
						<p><?php echo esc_html( $messages[ $code ] ); ?></p>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<form class="contact-form__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<input type="hidden" name="action" value="denver17_contact">
			<input type="hidden" name="d17_ts" value="<?php echo esc_attr( $ts ); ?>">
			<input type="hidden" name="d17_th" value="<?php echo esc_attr( wp_hash( $ts . '|d17contact' ) ); ?>">
			<?php // No nonce by design — see the note at the top of inc/contact-form.php. ?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( wp_unslash( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) ); ?>">

			<p class="contact-form__field">
				<label for="d17_name"><?php esc_html_e( 'Your name', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<input type="text" id="d17_name" name="d17_name" required autocomplete="name"
					value="<?php echo esc_attr( $old['name'] ); ?>">
			</p>

			<p class="contact-form__field">
				<label for="d17_email"><?php esc_html_e( 'Email', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<input type="email" id="d17_email" name="d17_email" required autocomplete="email"
					value="<?php echo esc_attr( $old['email'] ); ?>">
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

			<p class="contact-form__field">
				<label for="d17_message"><?php esc_html_e( 'Message', 'denver17' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="d17_message" name="d17_message" rows="6" required><?php echo esc_textarea( $old['message'] ); ?></textarea>
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
