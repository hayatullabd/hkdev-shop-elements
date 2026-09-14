<?php
/**
 * HKDEV Auth Engine (HKDEV Shop Elements plugin).
 *
 * Login / Registration modal with AJAX handlers. Self-contained.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Auth_Engine {

	const AJAX_LOGIN    = 'hkdev_elements_auth_login';
	const AJAX_REGISTER = 'hkdev_elements_auth_register';
	const AJAX_RESET    = 'hkdev_elements_auth_reset';
	const NONCE_LOGIN   = 'hkdev_elements_auth_login';
	const NONCE_REGISTER = 'hkdev_elements_auth_register';
	const NONCE_RESET   = 'hkdev_elements_auth_reset';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_LOGIN, [ $this, 'ajax_login' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_LOGIN, [ $this, 'ajax_login' ] );
		add_action( 'wp_ajax_' . self::AJAX_REGISTER, [ $this, 'ajax_register' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_REGISTER, [ $this, 'ajax_register' ] );
		add_action( 'wp_ajax_' . self::AJAX_RESET, [ $this, 'ajax_reset' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_RESET, [ $this, 'ajax_reset' ] );
		add_action( 'wp_footer', [ $this, 'render_auth_modal' ], 999 );
	}

	public function render_auth_modal() {
		if ( is_user_logged_in() ) {
			return;
		}
		?>
		<div class="hkdev-auth-modal" id="hkdev-auth-modal" aria-hidden="true">
			<div class="hkdev-auth-modal-overlay"></div>
			<div class="hkdev-auth-modal-container">
				<button type="button" class="hkdev-auth-modal-close" aria-label="<?php esc_attr_e( 'Close', 'hkdev-shop-elements' ); ?>">
					<i class="fa-solid fa-xmark"></i>
				</button>
				<div class="hkdev-auth-modal-header">
					<div class="hkdev-auth-logo">
						<?php
						$logo_id = get_theme_mod( 'custom_logo' );
						if ( $logo_id ) {
							echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'alt' => get_bloginfo( 'name' ) ] );
						} else {
							echo '<span class="hkdev-auth-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
						}
						?>
					</div>
				</div>
				<div class="hkdev-auth-modal-body">
					<!-- Login Form -->
					<div class="hkdev-auth-form hkdev-auth-login-form" data-form="login">
						<h3 class="hkdev-auth-title"><?php esc_html_e( 'Welcome Back', 'hkdev-shop-elements' ); ?></h3>
						<p class="hkdev-auth-subtitle"><?php esc_html_e( 'Login to your account', 'hkdev-shop-elements' ); ?></p>
						<div class="hkdev-auth-message" style="display:none;"></div>
						<form id="hkdev-login-form" method="post">
							<?php wp_nonce_field( self::NONCE_LOGIN, 'hkdev_login_nonce' ); ?>
							<input type="hidden" name="action" value="<?php echo esc_attr( self::AJAX_LOGIN ); ?>">
							<div class="hkdev-auth-field">
								<label for="hkdev-login-username"><?php esc_html_e( 'Username or Email', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="text" id="hkdev-login-username" name="username" required placeholder="<?php esc_attr_e( 'Enter username or email', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-login-password"><?php esc_html_e( 'Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<div class="hkdev-auth-password-wrap">
									<input type="password" id="hkdev-login-password" name="password" required placeholder="<?php esc_attr_e( 'Enter password', 'hkdev-shop-elements' ); ?>">
									<button type="button" class="hkdev-auth-toggle-password" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'hkdev-shop-elements' ); ?>">
										<i class="fa-regular fa-eye"></i>
									</button>
								</div>
							</div>
							<div class="hkdev-auth-field hkdev-auth-remember">
								<label>
									<input type="checkbox" name="remember" value="1">
									<?php esc_html_e( 'Remember me', 'hkdev-shop-elements' ); ?>
								</label>
								<a href="#" class="hkdev-auth-switch" data-switch="reset"><?php esc_html_e( 'Forgot password?', 'hkdev-shop-elements' ); ?></a>
							</div>
							<button type="submit" class="hkdev-auth-submit">
								<span class="hkdev-auth-btn-text"><?php esc_html_e( 'Login', 'hkdev-shop-elements' ); ?></span>
								<span class="hkdev-auth-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></span>
							</button>
						</form>
						<div class="hkdev-auth-footer">
							<p><?php esc_html_e( "Don't have an account?", 'hkdev-shop-elements' ); ?> <a href="#" class="hkdev-auth-switch" data-switch="register"><?php esc_html_e( 'Register', 'hkdev-shop-elements' ); ?></a></p>
						</div>
					</div>

					<!-- Register Form -->
					<div class="hkdev-auth-form hkdev-auth-register-form" data-form="register" style="display:none;">
						<h3 class="hkdev-auth-title"><?php esc_html_e( 'Create Account', 'hkdev-shop-elements' ); ?></h3>
						<p class="hkdev-auth-subtitle"><?php esc_html_e( 'Register a new account', 'hkdev-shop-elements' ); ?></p>
						<div class="hkdev-auth-message" style="display:none;"></div>
						<form id="hkdev-register-form" method="post">
							<?php wp_nonce_field( self::NONCE_REGISTER, 'hkdev_register_nonce' ); ?>
							<input type="hidden" name="action" value="<?php echo esc_attr( self::AJAX_REGISTER ); ?>">
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-firstname"><?php esc_html_e( 'First Name', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="text" id="hkdev-reg-firstname" name="first_name" required placeholder="<?php esc_attr_e( 'Enter first name', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-lastname"><?php esc_html_e( 'Last Name', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="text" id="hkdev-reg-lastname" name="last_name" required placeholder="<?php esc_attr_e( 'Enter last name', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-phone"><?php esc_html_e( 'Phone Number', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="tel" id="hkdev-reg-phone" name="phone" required placeholder="<?php esc_attr_e( '01XXXXXXXXX', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-email"><?php esc_html_e( 'Email Address', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="email" id="hkdev-reg-email" name="email" required placeholder="<?php esc_attr_e( 'Enter email address', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-password"><?php esc_html_e( 'Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<div class="hkdev-auth-password-wrap">
									<input type="password" id="hkdev-reg-password" name="password" required placeholder="<?php esc_attr_e( 'Create password', 'hkdev-shop-elements' ); ?>" minlength="6">
									<button type="button" class="hkdev-auth-toggle-password" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'hkdev-shop-elements' ); ?>">
										<i class="fa-regular fa-eye"></i>
									</button>
								</div>
							</div>
							<div class="hkdev-auth-field">
								<label for="hkdev-reg-password2"><?php esc_html_e( 'Confirm Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="password" id="hkdev-reg-password2" name="password2" required placeholder="<?php esc_attr_e( 'Confirm password', 'hkdev-shop-elements' ); ?>">
							</div>
							<div class="hkdev-auth-field hkdev-auth-terms">
								<label>
									<input type="checkbox" name="terms" value="1" required>
									<?php
									$terms_page_id = (int) get_option( 'woocommerce_terms_page_id' );
									$terms_url     = $terms_page_id ? get_permalink( $terms_page_id ) : '';
									printf(
										esc_html__( 'I agree to the %s', 'hkdev-shop-elements' ),
										'<a href="' . esc_url( $terms_url ? $terms_url : '#' ) . '" target="_blank">' . esc_html__( 'Terms & Conditions', 'hkdev-shop-elements' ) . '</a>'
									);
									?>
								</label>
							</div>
							<button type="submit" class="hkdev-auth-submit">
								<span class="hkdev-auth-btn-text"><?php esc_html_e( 'Create Account', 'hkdev-shop-elements' ); ?></span>
								<span class="hkdev-auth-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></span>
							</button>
						</form>
						<div class="hkdev-auth-footer">
							<p><?php esc_html_e( 'Already have an account?', 'hkdev-shop-elements' ); ?> <a href="#" class="hkdev-auth-switch" data-switch="login"><?php esc_html_e( 'Login', 'hkdev-shop-elements' ); ?></a></p>
						</div>
					</div>

					<!-- Reset Password Form -->
					<div class="hkdev-auth-form hkdev-auth-reset-form" data-form="reset" style="display:none;">
						<h3 class="hkdev-auth-title"><?php esc_html_e( 'Reset Password', 'hkdev-shop-elements' ); ?></h3>
						<p class="hkdev-auth-subtitle"><?php esc_html_e( 'Enter your email to receive a reset link', 'hkdev-shop-elements' ); ?></p>
						<div class="hkdev-auth-message" style="display:none;"></div>
						<form id="hkdev-reset-form" method="post">
							<?php wp_nonce_field( self::NONCE_RESET, 'hkdev_reset_nonce' ); ?>
							<input type="hidden" name="action" value="<?php echo esc_attr( self::AJAX_RESET ); ?>">
							<div class="hkdev-auth-field">
								<label for="hkdev-reset-email"><?php esc_html_e( 'Email Address', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
								<input type="email" id="hkdev-reset-email" name="email" required placeholder="<?php esc_attr_e( 'Enter your email', 'hkdev-shop-elements' ); ?>">
							</div>
							<button type="submit" class="hkdev-auth-submit">
								<span class="hkdev-auth-btn-text"><?php esc_html_e( 'Send Reset Link', 'hkdev-shop-elements' ); ?></span>
								<span class="hkdev-auth-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></span>
							</button>
						</form>
						<div class="hkdev-auth-footer">
							<p><a href="#" class="hkdev-auth-switch" data-switch="login"><?php esc_html_e( 'Back to Login', 'hkdev-shop-elements' ); ?></a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_login() {
		check_ajax_referer( self::NONCE_LOGIN, 'hkdev_login_nonce' );
		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
		$remember = isset( $_POST['remember'] ) ? true : false;

		if ( empty( $username ) || empty( $password ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please fill in all fields.', 'hkdev-shop-elements' ) ] );
		}

		$credentials = [
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		];

		$user = wp_signon( $credentials, is_ssl() );

		if ( is_wp_error( $user ) ) {
			$message = $user->get_error_message();
			if ( empty( $message ) ) {
				$message = esc_html__( 'Invalid username or password.', 'hkdev-shop-elements' );
			}
			wp_send_json_error( [ 'message' => $message ] );
		}

		wp_set_current_user( $user->ID );
		$redirect = wc_get_page_permalink( 'myaccount' );
		if ( empty( $redirect ) ) {
			$redirect = home_url();
		}

		wp_send_json_success( [
			'message'  => esc_html__( 'Login successful! Redirecting...', 'hkdev-shop-elements' ),
			'redirect' => apply_filters( 'hkdev_elements_login_redirect', $redirect, $user ),
		] );
	}

	public function ajax_register() {
		check_ajax_referer( self::NONCE_REGISTER, 'hkdev_register_nonce' );

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password   = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
		$password2  = isset( $_POST['password2'] ) ? wp_unslash( $_POST['password2'] ) : '';

		$errors = new \WP_Error();

		if ( empty( $first_name ) ) {
			$errors->add( 'first_name', esc_html__( 'First name is required.', 'hkdev-shop-elements' ) );
		}
		if ( empty( $last_name ) ) {
			$errors->add( 'last_name', esc_html__( 'Last name is required.', 'hkdev-shop-elements' ) );
		}
		if ( empty( $phone ) ) {
			$errors->add( 'phone', esc_html__( 'Phone number is required.', 'hkdev-shop-elements' ) );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors->add( 'email', esc_html__( 'A valid email is required.', 'hkdev-shop-elements' ) );
		}
		if ( empty( $password ) || strlen( $password ) < 6 ) {
			$errors->add( 'password', esc_html__( 'Password must be at least 6 characters.', 'hkdev-shop-elements' ) );
		}
		if ( $password !== $password2 ) {
			$errors->add( 'password2', esc_html__( 'Passwords do not match.', 'hkdev-shop-elements' ) );
		}
		if ( email_exists( $email ) ) {
			$errors->add( 'email_exists', esc_html__( 'An account with this email already exists.', 'hkdev-shop-elements' ) );
		}

		if ( $errors->has_errors() ) {
			wp_send_json_error( [ 'message' => implode( '<br>', $errors->get_error_messages() ) ] );
		}

		$username = $this->generate_username( $first_name, $last_name, $email );

		$user_id = wp_insert_user( [
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'role'       => 'customer',
		] );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
		}

		update_user_meta( $user_id, 'billing_phone', $phone );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		wp_new_user_notification( $user_id, null, 'both' );

		$redirect = wc_get_page_permalink( 'myaccount' );
		if ( empty( $redirect ) ) {
			$redirect = home_url();
		}

		wp_send_json_success( [
			'message'  => esc_html__( 'Account created successfully! Redirecting...', 'hkdev-shop-elements' ),
			'redirect' => apply_filters( 'hkdev_elements_register_redirect', $redirect, $user_id ),
		] );
	}

	public function ajax_reset() {
		check_ajax_referer( self::NONCE_RESET, 'hkdev_reset_nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid email address.', 'hkdev-shop-elements' ) ] );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			wp_send_json_success( [ 'message' => esc_html__( 'If an account with that email exists, a reset link has been sent.', 'hkdev-shop-elements' ) ] );
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			wp_send_json_error( [ 'message' => $key->get_error_message() ] );
		}

		$reset_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );

		$subject = sprintf(
			esc_html__( '[%s] Password Reset', 'hkdev-shop-elements' ),
			wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES )
		);

		$message  = esc_html__( 'Someone has requested a password reset for the following account:', 'hkdev-shop-elements' ) . "\r\n\r\n";
		$message .= sprintf( esc_html__( 'Site: %s', 'hkdev-shop-elements' ), home_url() ) . "\r\n\r\n";
		$message .= sprintf( esc_html__( 'Username: %s', 'hkdev-shop-elements' ), $user->user_login ) . "\r\n\r\n";
		$message .= esc_html__( 'If this was a mistake, ignore this email and nothing will happen.', 'hkdev-shop-elements' ) . "\r\n\r\n";
		$message .= esc_html__( 'To reset your password, visit the following address:', 'hkdev-shop-elements' ) . "\r\n\r\n";
		$message .= $reset_link . "\r\n";

		wp_mail( $email, $subject, $message );

		wp_send_json_success( [ 'message' => esc_html__( 'A password reset link has been sent to your email.', 'hkdev-shop-elements' ) ] );
	}

	private function generate_username( $first_name, $last_name, $email ) {
		$base = sanitize_user( strtolower( $first_name . '.' . $last_name ), true );
		$base = preg_replace( '/[^a-z0-9.]/', '', $base );

		if ( empty( $base ) ) {
			$base = current( explode( '@', $email ) );
		}

		$username = $base;
		$suffix   = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $suffix;
			$suffix++;
		}

		return $username;
	}
}