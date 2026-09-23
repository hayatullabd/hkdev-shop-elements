<?php
/**
 * HKDEV Contact Form Options (HKDEV Shop Elements plugin).
 *
 * WP Admin settings page for the contact form: receiver email, the contact
 * details shown in the info sidebar, social links and the accent colour.
 * Values are stored in the hkdev_cf_settings option (shared with the
 * hkdev-shop theme) which Contact_Form_Engine reads when rendering the form.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Contact_Form_Options
 */
final class Contact_Form_Options {

	/**
	 * Parent admin menu slug (registered by Checkout_Options).
	 *
	 * @var string
	 */
	const MENU_SLUG = 'hkdev-shop-elements';

	/**
	 * This settings page slug.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'hkdev-shop-elements-contact-form';

	/**
	 * Nonce action used by the settings form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'hkdev_cf_settings_save';

	/**
	 * Submissions post type.
	 *
	 * @var string
	 */
	const POST_TYPE = 'hkdev_cf_msg';

	/**
	 * @var ?Contact_Form_Options
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Contact_Form_Options
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Load the shared admin CSS/JS + colour picker on this screen only.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $page, [ self::SETTINGS_SLUG, self::MENU_SLUG ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'css/admin.css',
			[],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin.css' )
		);

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'js/admin.js',
			[ 'jquery', 'wp-color-picker' ],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/admin.js' ),
			true
		);
	}

	/**
	 * Register the submenu pages.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Contact Form', 'hkdev-shop-elements' ),
			esc_html__( 'Contact Form', 'hkdev-shop-elements' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);

		// The contact form inbox (private CPT) lives under the same plugin menu.
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Submissions', 'hkdev-shop-elements' ),
			esc_html__( 'Submissions', 'hkdev-shop-elements' ),
			'manage_options',
			'edit.php?post_type=' . self::POST_TYPE
		);
	}

	/**
	 * Default values (used as field placeholders when nothing is stored yet).
	 *
	 * @return array<string, string>
	 */
	private function defaults() {
		return [
			'receiver_email' => get_option( 'admin_email' ),
			'display_email'  => '',
			'phone'          => '',
			'address'        => '',
			'info_text'      => __( 'Contact us for any questions, opinions or information.', 'hkdev-shop-elements' ),
			'form_title'     => __( 'Send us a message', 'hkdev-shop-elements' ),
			'facebook'       => '',
			'whatsapp'       => '',
			'instagram'      => '',
			'youtube'        => '',
			'primary_color'  => '#03a550',
		];
	}

	/**
	 * Read the stored settings.
	 *
	 * @return array<string, string>
	 */
	private function stored() {
		$stored = get_option( Contact_Form_Engine::OPTION_NAME, [] );

		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Render the settings page and handle the save.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hkdev-shop-elements' ) );
		}

		$saved_notice = false;

		if ( isset( $_POST['hkdev_cf_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( self::NONCE_ACTION );

			$text = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$textarea = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$email = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_email( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$url = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$color = sanitize_hex_color( $text( 'hkdev_cf_primary_color' ) );

			$settings = [
				'receiver_email' => $email( 'hkdev_cf_receiver_email' ),
				'display_email'  => $email( 'hkdev_cf_display_email' ),
				'phone'          => $text( 'hkdev_cf_phone' ),
				'address'        => $textarea( 'hkdev_cf_address' ),
				'info_text'      => $textarea( 'hkdev_cf_info_text' ),
				'form_title'     => $text( 'hkdev_cf_form_title' ),
				'facebook'       => $url( 'hkdev_cf_facebook' ),
				'whatsapp'       => $url( 'hkdev_cf_whatsapp' ),
				'instagram'      => $url( 'hkdev_cf_instagram' ),
				'youtube'        => $url( 'hkdev_cf_youtube' ),
				'primary_color'  => $color ? $color : '',
			];

			update_option( Contact_Form_Engine::OPTION_NAME, $settings );
			$saved_notice = true;
		}

		$defaults = $this->defaults();
		$stored   = $this->stored();
		$val      = static function ( $key ) use ( $stored, $defaults ) {
			return ( isset( $stored[ $key ] ) && '' !== $stored[ $key ] ) ? $stored[ $key ] : ( $defaults[ $key ] ?? '' );
		};

		?>
		<div class="wrap hkdev-admin-wrap">

			<!-- HEADER -->
			<div class="hkdev-admin-header">
				<div class="hkdev-admin-branding">
					<span class="hkdev-admin-logo dashicons dashicons-email-alt"></span>
					<div class="hkdev-admin-titles">
						<h1><?php esc_html_e( 'Contact Form', 'hkdev-shop-elements' ); ?></h1>
						<p><?php esc_html_e( 'Contact details, social links and colours used by the Contact Form widget.', 'hkdev-shop-elements' ); ?></p>
					</div>
				</div>
				<div class="hkdev-admin-actions">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-email"></span>
						<?php esc_html_e( 'Submissions', 'hkdev-shop-elements' ); ?>
					</a>
				</div>
			</div>
			<?php Admin_Menu::instance()->render_module_nav( self::SETTINGS_SLUG ); ?>

			<?php if ( $saved_notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Contact form settings saved.', 'hkdev-shop-elements' ); ?></p></div>
			<?php endif; ?>

			<form method="post" id="hkdev-contact-form-settings">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="hkdev_cf_submit" value="1">

				<!-- ============ FORM & DELIVERY ============ -->
				<div class="hkdev-admin-card">
					<div class="hkdev-admin-card-header">
						<span class="hkdev-admin-card-icon dashicons dashicons-email-alt"></span>
						<h2><?php esc_html_e( 'Form & Delivery', 'hkdev-shop-elements' ); ?></h2>
						<p class="hkdev-admin-card-hint"><?php esc_html_e( 'Where submissions are sent and what visitors see.', 'hkdev-shop-elements' ); ?></p>
					</div>
					<div class="hkdev-admin-card-body">
						<div class="hkdev-settings-grid">

							<div class="hkdev-field">
								<label for="hkdev_cf_receiver_email"><?php esc_html_e( 'Receiver Email', 'hkdev-shop-elements' ); ?> <span class="hkdev-req">*</span></label>
								<input type="email" id="hkdev_cf_receiver_email" name="hkdev_cf_receiver_email"
									value="<?php echo esc_attr( $val( 'receiver_email' ) ); ?>">
								<p class="description"><?php esc_html_e( 'New submissions are emailed to this address.', 'hkdev-shop-elements' ); ?></p>
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_display_email"><?php esc_html_e( 'Display Email', 'hkdev-shop-elements' ); ?></label>
								<input type="email" id="hkdev_cf_display_email" name="hkdev_cf_display_email"
									value="<?php echo esc_attr( $val( 'display_email' ) ); ?>"
									placeholder="info@example.com">
								<p class="description"><?php esc_html_e( 'Shown in the contact info panel. Leave empty to hide.', 'hkdev-shop-elements' ); ?></p>
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_phone"><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></label>
								<input type="text" id="hkdev_cf_phone" name="hkdev_cf_phone"
									value="<?php echo esc_attr( $val( 'phone' ) ); ?>"
									placeholder="01XXXXXXXXX">
								<p class="description"><?php esc_html_e( 'Shown in the contact info panel. Leave empty to hide.', 'hkdev-shop-elements' ); ?></p>
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_form_title"><?php esc_html_e( 'Form Title', 'hkdev-shop-elements' ); ?></label>
								<input type="text" id="hkdev_cf_form_title" name="hkdev_cf_form_title"
									value="<?php echo esc_attr( $val( 'form_title' ) ); ?>">
							</div>

							<div class="hkdev-field hkdev-field-wide">
								<label for="hkdev_cf_address"><?php esc_html_e( 'Address', 'hkdev-shop-elements' ); ?></label>
								<textarea id="hkdev_cf_address" name="hkdev_cf_address" rows="2"><?php echo esc_textarea( $val( 'address' ) ); ?></textarea>
							</div>

							<div class="hkdev-field hkdev-field-wide">
								<label for="hkdev_cf_info_text"><?php esc_html_e( 'Intro Text', 'hkdev-shop-elements' ); ?></label>
								<textarea id="hkdev_cf_info_text" name="hkdev_cf_info_text" rows="2"><?php echo esc_textarea( $val( 'info_text' ) ); ?></textarea>
							</div>

						</div>
					</div>
				</div>

				<!-- ============ SOCIAL LINKS ============ -->
				<div class="hkdev-admin-card">
					<div class="hkdev-admin-card-header">
						<span class="hkdev-admin-card-icon dashicons dashicons-share"></span>
						<h2><?php esc_html_e( 'Social Links', 'hkdev-shop-elements' ); ?></h2>
						<p class="hkdev-admin-card-hint"><?php esc_html_e( 'Empty fields are hidden from the icon list.', 'hkdev-shop-elements' ); ?></p>
					</div>
					<div class="hkdev-admin-card-body">
						<div class="hkdev-settings-grid">

							<div class="hkdev-field">
								<label for="hkdev_cf_facebook"><?php esc_html_e( 'Facebook URL', 'hkdev-shop-elements' ); ?></label>
								<input type="url" id="hkdev_cf_facebook" name="hkdev_cf_facebook"
									value="<?php echo esc_attr( $val( 'facebook' ) ); ?>"
									placeholder="https://facebook.com/yourpage">
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_whatsapp"><?php esc_html_e( 'WhatsApp URL', 'hkdev-shop-elements' ); ?></label>
								<input type="url" id="hkdev_cf_whatsapp" name="hkdev_cf_whatsapp"
									value="<?php echo esc_attr( $val( 'whatsapp' ) ); ?>"
									placeholder="https://wa.me/8801XXXXXXXXX">
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_instagram"><?php esc_html_e( 'Instagram URL', 'hkdev-shop-elements' ); ?></label>
								<input type="url" id="hkdev_cf_instagram" name="hkdev_cf_instagram"
									value="<?php echo esc_attr( $val( 'instagram' ) ); ?>"
									placeholder="https://instagram.com/yourpage">
							</div>

							<div class="hkdev-field">
								<label for="hkdev_cf_youtube"><?php esc_html_e( 'YouTube URL', 'hkdev-shop-elements' ); ?></label>
								<input type="url" id="hkdev_cf_youtube" name="hkdev_cf_youtube"
									value="<?php echo esc_attr( $val( 'youtube' ) ); ?>"
									placeholder="https://youtube.com/@yourchannel">
							</div>

						</div>
					</div>
				</div>

				<!-- ============ APPEARANCE ============ -->
				<div class="hkdev-admin-card">
					<div class="hkdev-admin-card-header">
						<span class="hkdev-admin-card-icon dashicons dashicons-art"></span>
						<h2><?php esc_html_e( 'Appearance', 'hkdev-shop-elements' ); ?></h2>
					</div>
					<div class="hkdev-admin-card-body">
						<div class="hkdev-settings-grid">
							<div class="hkdev-field">
								<label for="hkdev_cf_primary_color"><?php esc_html_e( 'Accent Colour', 'hkdev-shop-elements' ); ?></label>
								<input type="text" class="hkdev-color-input" id="hkdev_cf_primary_color" name="hkdev_cf_primary_color"
									value="<?php echo esc_attr( $val( 'primary_color' ) ); ?>"
									data-default-color="<?php echo esc_attr( $defaults['primary_color'] ); ?>">
								<p class="description"><?php esc_html_e( 'Used for the info panel and buttons.', 'hkdev-shop-elements' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<?php submit_button( __( 'Save Changes', 'hkdev-shop-elements' ), 'primary', 'submit', true ); ?>
			</form>
		</div>
		<?php
	}
}
