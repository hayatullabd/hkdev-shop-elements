<?php
/**
 * HKDEV Contact Form Engine (HKDEV Shop Elements plugin).
 *
 * Self-contained contact form widget renderer: info sidebar, social icons and
 * a full contact form. Submissions are saved to a private CPT (inbox) and an
 * email is sent to the receiver. Settings are read from the same option the
 * hkdev-shop theme uses (hkdev_cf_settings), with sensible defaults.
 *
 * Works with ANY WordPress theme + Elementor. No external CSS/JS required
 * (styling is inlined by the renderer, matching the theme design).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Contact_Form_Engine
 */
class Contact_Form_Engine {

	const OPTION_NAME = 'hkdev_cf_settings';

	/**
	 * @var ?Contact_Form_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Contact_Form_Engine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_message_post_type' ] );
	}

	/**
	 * Register the private CPT used to store contact submissions.
	 *
	 * @return void
	 */
	public function register_message_post_type() {
		register_post_type(
			'hkdev_cf_msg',
			[
				'labels'       => [
					'name'          => __( 'Contact Submissions', 'hkdev-shop-elements' ),
					'singular_name' => __( 'Message', 'hkdev-shop-elements' ),
					'all_items'     => __( 'Submissions', 'hkdev-shop-elements' ),
				],
				'public'       => false,
				'show_ui'      => true,
				// Linked as a submenu of the HKDEV Shop plugin menu
				// (see ContactFormOptions::register_menu()) instead of
				// creating its own top-level menu.
				'show_in_menu' => false,
				'menu_icon'    => 'dashicons-email-alt',
				'supports'     => [ 'title', 'editor' ],
				'capabilities' => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap' => true,
			]
		);
	}

	/**
	 * Read a single setting with a default fallback.
	 *
	 * @param string $key     Setting key.
	 * @param string $default Default value.
	 * @return string
	 */
	public function cf_setting( $key, $default = '' ) {
		$opts = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $opts ) ) {
			$opts = [];
		}
		return ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) ? $opts[ $key ] : $default;
	}

	/**
	 * Render the contact form (shortcode/widget).
	 *
	 * @return string
	 */
	public function contact_form_shortcode() {
		$receiver_email = $this->cf_setting( 'receiver_email', get_option( 'admin_email' ) );
		// No fake placeholder details: these are empty until the shop owner
		// fills them in under HKDEV Shop → Contact Form.
		$display_email  = $this->cf_setting( 'display_email', '' );
		$phone          = $this->cf_setting( 'phone', '' );
		$address        = $this->cf_setting( 'address', '' );
		$info_text      = $this->cf_setting( 'info_text', __( 'Contact us for any questions, opinions or information.', 'hkdev-shop-elements' ) );
		$form_title     = $this->cf_setting( 'form_title', __( 'Send us a message', 'hkdev-shop-elements' ) );

		$facebook  = $this->cf_setting( 'facebook', '' );
		$whatsapp  = $this->cf_setting( 'whatsapp', '' );
		$instagram = $this->cf_setting( 'instagram', '' );
		$youtube   = $this->cf_setting( 'youtube', '' );

		$primary_color = $this->cf_setting( 'primary_color', '#03a550' );

		$form_status = '';

		// Handle form submission.
		if ( isset( $_POST['hkdev_cf_submitted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['hkdev_cf_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hkdev_cf_nonce'] ), 'hkdev_cf_action' ) ) {
				$form_status = '<div class="hkdev-cf-error"><i class="fa-solid fa-triangle-exclamation"></i> ' . esc_html__( 'Security check failed. Please try again.', 'hkdev-shop-elements' ) . '</div>';
			} else {
				$name_raw = isset( $_POST['hkdev_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hkdev_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$name     = str_replace( [ "\r", "\n" ], '', $name_raw );
				$email    = isset( $_POST['hkdev_email'] ) ? sanitize_email( wp_unslash( $_POST['hkdev_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$subject  = isset( $_POST['hkdev_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['hkdev_subject'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$message  = isset( $_POST['hkdev_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hkdev_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
					$form_status = '<div class="hkdev-cf-error"><i class="fa-solid fa-circle-exclamation"></i> ' . esc_html__( 'Please fill out all required fields.', 'hkdev-shop-elements' ) . '</div>';
				} elseif ( ! is_email( $email ) ) {
					$form_status = '<div class="hkdev-cf-error"><i class="fa-solid fa-circle-exclamation"></i> ' . esc_html__( 'Please enter a valid email address.', 'hkdev-shop-elements' ) . '</div>';
				} else {
					$to        = $receiver_email;
					$mail_subj = 'New Contact Submission: ' . ( $subject ? $subject : __( 'No Subject', 'hkdev-shop-elements' ) );

					$body  = "You have received a new message from your website contact form.\n\n";
					$body .= "-------------------------------------------\n";
					$body .= "Name    : {$name}\n";
					$body .= "Email   : {$email}\n";
					$body .= "Subject : {$subject}\n";
					$body .= "-------------------------------------------\n\n";
					$body .= "Message:\n{$message}\n\n";
					$body .= "-------------------------------------------\n";
					$body .= 'Sent from: ' . site_url() . "\n";

					$headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];

					$mail_sent = wp_mail( $to, $mail_subj, $body, $headers );

					$post_id = wp_insert_post(
						[
							'post_title'   => sanitize_text_field( $subject ),
							'post_content' => sanitize_textarea_field( $message ),
							'post_type'    => 'hkdev_cf_msg',
							'post_status'  => 'publish',
						]
					);

					if ( $post_id ) {
						update_post_meta( $post_id, 'hkdev_cf_name', $name );
						update_post_meta( $post_id, 'hkdev_cf_email', $email );
						update_post_meta( $post_id, 'hkdev_cf_status', 'unread' );
					}

					if ( $mail_sent || $post_id ) {
						$form_status = '<div class="hkdev-cf-success"><i class="fa-solid fa-circle-check"></i> ' . esc_html__( 'Thank you! Your message has been sent successfully. We will contact you soon.', 'hkdev-shop-elements' ) . '</div>';
					} else {
						$form_status = '<div class="hkdev-cf-error"><i class="fa-solid fa-circle-xmark"></i> ' . esc_html__( 'Sorry, your message could not be sent due to a server issue. Please try again later.', 'hkdev-shop-elements' ) . '</div>';
					}
				}
			}
		}

		ob_start();
		?>
		<style>
			:root {
				--hk-cf-primary: <?php echo esc_attr( $primary_color ); ?>;
				--hk-cf-primary-dark: color-mix(in srgb, var(--hk-cf-primary) 80%, black);
				--hk-cf-primary-light: color-mix(in srgb, var(--hk-cf-primary) 10%, transparent);
			}
			.hkdev-contact-container{max-width:1000px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.08);font-family:'Poppins','Hind Siliguri',sans-serif;border:1px solid rgba(0,0,0,.05);display:flex;overflow:hidden;flex-direction:row}
			.hkdev-contact-info{background:var(--hk-cf-primary);color:#fff;padding:48px 40px;width:40%;display:flex;flex-direction:column;justify-content:space-between;position:relative}
			.hkdev-contact-info::before{content:'';position:absolute;inset:0;opacity:.05;background-image:radial-gradient(#fff 1px,transparent 1px);background-size:20px 20px;pointer-events:none}
			.hkdev-info-header{position:relative;z-index:1}
			.hkdev-info-header h3{color:#fff;font-size:26px;font-weight:700;margin-bottom:12px;margin-top:0;font-family:'Hind Siliguri','Poppins',sans-serif}
			.hkdev-info-header p{color:rgba(255,255,255,.85);font-size:15px;line-height:1.6;margin-bottom:40px}
			.hkdev-info-list{display:flex;flex-direction:column;gap:28px;margin-bottom:40px;position:relative;z-index:1}
			.hkdev-info-item{display:flex;align-items:center;gap:18px}
			.hkdev-info-item i{font-size:20px;color:var(--hk-cf-primary);background:#fff;width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;flex-shrink:0;box-shadow:0 4px 10px rgba(0,0,0,.1)}
			.hkdev-info-item div{display:flex;flex-direction:column}
			.hkdev-info-item span.info-label{font-size:12px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.7);margin-bottom:4px;font-weight:600}
			.hkdev-info-item span.info-text{font-size:16px;font-weight:600;color:#fff}
			.hkdev-social-icons{display:flex;gap:12px;position:relative;z-index:1}
			.hkdev-social-icons a{color:#fff;background:rgba(255,255,255,.15);width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;text-decoration:none;transition:all .3s ease;font-size:18px}
			.hkdev-social-icons a:hover{transform:translateY(-4px);background:#fff;color:var(--hk-cf-primary);box-shadow:0 8px 15px rgba(0,0,0,.2)}
			.hkdev-cf-wrap{padding:56px 48px;width:60%;background:#fff}
			.hkdev-cf-wrap h3{font-size:24px;color:#141a14;margin-top:0;margin-bottom:24px;font-weight:700}
			.hkdev-cf-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
			.hkdev-cf-group{margin-bottom:20px}
			.hkdev-cf-group.full-width{grid-column:1/-1}
			.hkdev-cf-group label{display:block;margin-bottom:8px;font-weight:600;color:#5f6e66;font-size:14px}
			.hkdev-cf-group input,.hkdev-cf-group textarea{width:100%;padding:14px 18px;border:1.5px solid #e6ebe6;border-radius:10px;font-size:15px;color:#141a14;background:#f7faf7;transition:all .3s ease;box-sizing:border-box;font-family:inherit}
			.hkdev-cf-group input:focus,.hkdev-cf-group textarea:focus{border-color:var(--hk-cf-primary);background:#fff;outline:none;box-shadow:0 0 0 4px var(--hk-cf-primary-light)}
			.hkdev-cf-btn{background:var(--hk-cf-primary);color:#fff;border:none;padding:16px 32px;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;transition:all .3s ease;display:inline-flex;justify-content:center;align-items:center;gap:10px;width:auto;margin-top:10px}
			.hkdev-cf-btn:hover{background:var(--hk-cf-primary-dark);transform:translateY(-2px);box-shadow:0 8px 20px var(--hk-cf-primary-light)}
			.hkdev-cf-error,.hkdev-cf-success{padding:16px 20px;border-radius:10px;margin-bottom:24px;font-weight:600;font-size:14px;display:flex;align-items:center;gap:12px}
			.hkdev-cf-error{background:#fdf1ee;border:1px solid #f5a69a;color:#b3402e}
			.hkdev-cf-error i{font-size:18px;color:#e5533d}
			.hkdev-cf-success{background:#f0f9f1;border:1px solid #c9f2d6;color:#026b33}
			.hkdev-cf-success i{font-size:18px;color:var(--hk-cf-primary)}
			@media (max-width:768px){.hkdev-contact-container{flex-direction:column}.hkdev-contact-info,.hkdev-cf-wrap{width:100%;padding:40px 24px}.hkdev-contact-info{border-radius:16px 16px 0 0}.hkdev-cf-grid{grid-template-columns:1fr;gap:0}}
		</style>

		<div class="hkdev-contact-container">
			<div class="hkdev-contact-info">
				<div>
					<div class="hkdev-info-header">
						<h3><?php esc_html_e( 'Contact Information', 'hkdev-shop-elements' ); ?></h3>
						<p><?php echo esc_html( $info_text ); ?></p>
					</div>
					<div class="hkdev-info-list">
						<?php if ( ! empty( $address ) ) : ?>
						<div class="hkdev-info-item">
							<i class="fa-solid fa-location-dot"></i>
							<div>
								<span class="info-label"><?php esc_html_e( 'Address', 'hkdev-shop-elements' ); ?></span>
								<span class="info-text"><?php echo esc_html( $address ); ?></span>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $phone ) ) : ?>
						<div class="hkdev-info-item">
							<i class="fa-solid fa-phone"></i>
							<div>
								<span class="info-label"><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></span>
								<span class="info-text"><?php echo esc_html( $phone ); ?></span>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( ! empty( $display_email ) ) : ?>
						<div class="hkdev-info-item">
							<i class="fa-solid fa-envelope"></i>
							<div>
								<span class="info-label"><?php esc_html_e( 'Email', 'hkdev-shop-elements' ); ?></span>
								<span class="info-text"><?php echo esc_html( $display_email ); ?></span>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="hkdev-social-icons">
					<?php if ( ! empty( $facebook ) ) : ?>
						<a href="<?php echo esc_url( $facebook ); ?>" target="_blank" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
					<?php endif; ?>
					<?php if ( ! empty( $whatsapp ) ) : ?>
						<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
					<?php endif; ?>
					<?php if ( ! empty( $instagram ) ) : ?>
						<a href="<?php echo esc_url( $instagram ); ?>" target="_blank" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
					<?php endif; ?>
					<?php if ( ! empty( $youtube ) ) : ?>
						<a href="<?php echo esc_url( $youtube ); ?>" target="_blank" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="hkdev-cf-wrap">
				<h3><?php echo esc_html( $form_title ); ?></h3>
				<?php echo wp_kses_post( $form_status ); ?>
				<form action="" method="post" id="hkdev-contact-form">
					<?php wp_nonce_field( 'hkdev_cf_action', 'hkdev_cf_nonce' ); ?>
					<input type="hidden" name="hkdev_cf_submitted" value="1">
					<div class="hkdev-cf-grid">
						<div class="hkdev-cf-group">
							<label for="hkdev_name"><?php esc_html_e( 'Your Name', 'hkdev-shop-elements' ); ?> *</label>
							<input type="text" name="hkdev_name" id="hkdev_name" required placeholder="John Doe">
						</div>
						<div class="hkdev-cf-group">
							<label for="hkdev_email"><?php esc_html_e( 'Email Address', 'hkdev-shop-elements' ); ?> *</label>
							<input type="email" name="hkdev_email" id="hkdev_email" required placeholder="your@email.com">
						</div>
					</div>
					<div class="hkdev-cf-group full-width">
						<label for="hkdev_subject"><?php esc_html_e( 'Subject', 'hkdev-shop-elements' ); ?> *</label>
						<input type="text" name="hkdev_subject" id="hkdev_subject" required>
					</div>
					<div class="hkdev-cf-group full-width">
						<label for="hkdev_message"><?php esc_html_e( 'Message', 'hkdev-shop-elements' ); ?> *</label>
						<textarea name="hkdev_message" id="hkdev_message" rows="5" required></textarea>
					</div>
					<button type="submit" class="hkdev-cf-btn">
						<i class="fa-regular fa-paper-plane"></i> <?php esc_html_e( 'Send Message', 'hkdev-shop-elements' ); ?>
					</button>
				</form>
			</div>
		</div>

		<script>
		if ( window.history.replaceState ) {
			window.history.replaceState( null, null, window.location.href );
		}
		</script>
		<?php
		return ob_get_clean();
	}
}
