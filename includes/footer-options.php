<?php
/**
 * HKDEV Footer Options (HKDEV Shop Elements plugin).
 *
 * WP Admin settings page that stores the site-wide footer configuration in the
 * hkdev_elements_footer_config option. When "Enable site-wide footer" is on the
 * Footer_Engine prints the footer on every front-end page (wp_footer), so no
 * Elementor Pro / Theme Builder is required.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Footer_Options
 */
final class Footer_Options {

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
	const SETTINGS_SLUG = 'hkdev-shop-elements-footer';

	/**
	 * Nonce action used by the settings form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'hkdev_elements_footer_save';

	/**
	 * Nonce action used by the "clear subscribers" button.
	 *
	 * @var string
	 */
	const CLEAR_NONCE_ACTION = 'hkdev_elements_footer_clear_subs';

	/**
	 * @var ?Footer_Options
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Footer_Options
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
	 * Load the WP media frame only on this settings screen.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::SETTINGS_SLUG !== $page ) {
			return;
		}
		wp_enqueue_media();
	}

	/**
	 * Register the submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Footer', 'hkdev-shop-elements' ),
			esc_html__( 'Footer', 'hkdev-shop-elements' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);
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

		$engine       = Footer_Engine::instance();
		$saved_notice = false;
		$cleared      = false;

		if ( isset( $_POST['hkdev_footer_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( self::NONCE_ACTION );

			$yes_no = static function ( $key ) {
				return ( isset( $_POST[ $key ] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$text = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$url = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? esc_url_raw( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$rich = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? wp_kses_post( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$num = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? absint( wp_unslash( $_POST[ $key ] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$css_color = static function ( $key ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_POST[ $key ] ) ? \HkdevShopElements\hkdev_elements_sanitize_css_color( wp_unslash( $_POST[ $key ] ) ) : '';
			};

			$font_stack = static function ( $key ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_POST[ $key ] ) ? \HkdevShopElements\hkdev_elements_sanitize_font_stack( wp_unslash( $_POST[ $key ] ) ) : '';
			};

			$config = [
				'enabled'           => $yes_no( 'hkdev_ft_enabled' ),
				'logo'              => $url( 'hkdev_ft_logo' ),
				'logo_width'        => isset( $_POST['hkdev_ft_logo_width'] ) ? absint( wp_unslash( $_POST['hkdev_ft_logo_width'] ) ) : 150, // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'about'             => $rich( 'hkdev_ft_about' ),
				'address'           => $text( 'hkdev_ft_address' ),
				'phone'             => $text( 'hkdev_ft_phone' ),
				'email'             => $text( 'hkdev_ft_email' ),
				'hours'             => $text( 'hkdev_ft_hours' ),
				'menu'              => $text( 'hkdev_ft_menu' ),
				'menu_title'        => $text( 'hkdev_ft_menu_title' ),
				'show_categories'   => $yes_no( 'hkdev_ft_show_categories' ),
				'categories_title'  => $text( 'hkdev_ft_categories_title' ),
				'categories_menu'   => $text( 'hkdev_ft_categories_menu' ),
				'show_newsletter'   => $yes_no( 'hkdev_ft_show_newsletter' ),
				'newsletter_title'  => $text( 'hkdev_ft_newsletter_title' ),
				'newsletter_text'   => $rich( 'hkdev_ft_newsletter_text' ),
				'newsletter_action' => $url( 'hkdev_ft_newsletter_action' ),
				'newsletter_btn'    => $text( 'hkdev_ft_newsletter_btn' ),
				'show_social'       => $yes_no( 'hkdev_ft_show_social' ),
				'facebook'          => $url( 'hkdev_ft_facebook' ),
				'instagram'         => $url( 'hkdev_ft_instagram' ),
				'youtube'           => $url( 'hkdev_ft_youtube' ),
				'whatsapp'          => $url( 'hkdev_ft_whatsapp' ),
				'show_payments'       => $yes_no( 'hkdev_ft_show_payments' ),
				'payment_banner'      => $url( 'hkdev_ft_payment_banner' ),
				'payment_banner_link' => $url( 'hkdev_ft_payment_banner_link' ),
				'show_backtotop'      => $yes_no( 'hkdev_ft_show_backtotop' ),
				'copyright'         => $text( 'hkdev_ft_copyright' ),

				// ---- Appearance ----
				'st_font'           => $font_stack( 'hkdev_ft_st_font' ),
				'st_font_size'      => $num( 'hkdev_ft_st_font_size' ),
				'st_font_size_t'    => $num( 'hkdev_ft_st_font_size_t' ),
				'st_font_size_m'    => $num( 'hkdev_ft_st_font_size_m' ),
				'st_container'      => $num( 'hkdev_ft_st_container' ),
				'st_container_t'    => $num( 'hkdev_ft_st_container_t' ),
				'st_container_m'    => $num( 'hkdev_ft_st_container_m' ),
				'st_bg'             => $css_color( 'hkdev_ft_st_bg' ),
				'st_bg2'            => $css_color( 'hkdev_ft_st_bg2' ),
				'st_text'           => $css_color( 'hkdev_ft_st_text' ),
				'st_heading'        => $css_color( 'hkdev_ft_st_heading' ),
				'st_muted'          => $css_color( 'hkdev_ft_st_muted' ),
				'st_green'          => $css_color( 'hkdev_ft_st_green' ),
				'st_orange'         => $css_color( 'hkdev_ft_st_orange' ),
				'st_border'         => $css_color( 'hkdev_ft_st_border' ),
				'st_soft'           => $css_color( 'hkdev_ft_st_soft' ),
				'st_main_pad_y'     => $num( 'hkdev_ft_st_main_pad_y' ),
				'st_main_pad_y_t'   => $num( 'hkdev_ft_st_main_pad_y_t' ),
				'st_main_pad_y_m'   => $num( 'hkdev_ft_st_main_pad_y_m' ),
				'st_grid_gap'       => $num( 'hkdev_ft_st_grid_gap' ),
				'st_grid_gap_t'     => $num( 'hkdev_ft_st_grid_gap_t' ),
				'st_grid_gap_m'     => $num( 'hkdev_ft_st_grid_gap_m' ),
				'st_bottom_pad_y'   => $num( 'hkdev_ft_st_bottom_pad_y' ),
				'st_bottom_pad_y_t' => $num( 'hkdev_ft_st_bottom_pad_y_t' ),
				'st_bottom_pad_y_m' => $num( 'hkdev_ft_st_bottom_pad_y_m' ),
				'st_title_fs'       => $num( 'hkdev_ft_st_title_fs' ),
				'st_title_fs_t'     => $num( 'hkdev_ft_st_title_fs_t' ),
				'st_title_fs_m'     => $num( 'hkdev_ft_st_title_fs_m' ),
				'st_link_fs'        => $num( 'hkdev_ft_st_link_fs' ),
				'st_link_fs_t'      => $num( 'hkdev_ft_st_link_fs_t' ),
				'st_link_fs_m'      => $num( 'hkdev_ft_st_link_fs_m' ),
			];

			if ( '' === $config['menu_title'] ) {
				$config['menu_title'] = esc_html__( 'Quick Links', 'hkdev-shop-elements' );
			}
			if ( '' === $config['categories_title'] ) {
				$config['categories_title'] = esc_html__( 'Categories', 'hkdev-shop-elements' );
			}
			if ( $config['logo_width'] < 1 ) {
				$config['logo_width'] = 150;
			}
			update_option( Footer_Engine::CONFIG_OPTION, $config );
			$saved_notice = true;
		}

		if ( isset( $_POST['hkdev_footer_clear_subs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( self::CLEAR_NONCE_ACTION );
			delete_option( Footer_Engine::SUBSCRIBERS_OPTION );
			$cleared = true;
		}

		$config   = $engine->get_config();
		$menus    = wp_get_nav_menus();
		$subs     = $engine->get_subscribers();
		$subs_cnt = count( $subs );

		$nav_items = [
			'general'    => [ 'dashicons-admin-generic', esc_html__( 'General', 'hkdev-shop-elements' ) ],
			'brand'      => [ 'dashicons-store', esc_html__( 'Brand & Contact', 'hkdev-shop-elements' ) ],
			'columns'    => [ 'dashicons-list-view', esc_html__( 'Link Columns', 'hkdev-shop-elements' ) ],
			'newsletter' => [ 'dashicons-email-alt', esc_html__( 'Newsletter & Social', 'hkdev-shop-elements' ) ],
			'bottom'     => [ 'dashicons-money-alt', esc_html__( 'Payments & Copyright', 'hkdev-shop-elements' ) ],
			'appearance' => [ 'dashicons-art', esc_html__( 'Appearance', 'hkdev-shop-elements' ) ],
		];
		$nav_items = Admin_Menu::instance()->filter_footer_builder_nav( $nav_items, $config );
		?>
		<div class="wrap hkdev-hd-wrap">

			<div class="hd-hero">
				<div class="hd-hero-main">
					<span class="hd-hero-badge"><?php esc_html_e( 'HKDEV Shop Elements', 'hkdev-shop-elements' ); ?></span>
					<h1><?php esc_html_e( 'Footer Builder', 'hkdev-shop-elements' ); ?></h1>
					<p><?php esc_html_e( 'Design the site footer section by section. Everything here applies site-wide — no Elementor Pro needed.', 'hkdev-shop-elements' ); ?></p>
				</div>
				<div class="hd-hero-status<?php echo 'yes' === $config['enabled'] ? ' is-on' : ''; ?>">
					<span class="hd-status-dot" aria-hidden="true"></span>
					<div>
						<div class="hd-status-label"><?php esc_html_e( 'Footer Status', 'hkdev-shop-elements' ); ?></div>
						<div class="hd-status-value"><?php echo 'yes' === $config['enabled'] ? esc_html__( 'Active', 'hkdev-shop-elements' ) : esc_html__( 'Inactive', 'hkdev-shop-elements' ); ?></div>
					</div>
				</div>
			</div>
			<?php Admin_Menu::instance()->render_module_nav( self::SETTINGS_SLUG, 'builder' ); ?>

			<?php if ( $saved_notice ) : ?>
				<div class="notice notice-success is-dismissible hd-notice"><p><?php esc_html_e( 'Footer settings saved.', 'hkdev-shop-elements' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $cleared ) : ?>
				<div class="notice notice-success is-dismissible hd-notice"><p><?php esc_html_e( 'Newsletter subscribers cleared.', 'hkdev-shop-elements' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<div class="hd-layout">

					<aside class="hd-nav">
						<?php foreach ( $nav_items as $slug => $item ) : ?>
							<a class="hd-nav-item<?php echo 'general' === $slug ? ' is-active' : ''; ?>" href="#hd-sec-<?php echo esc_attr( $slug ); ?>">
								<span class="dashicons <?php echo esc_attr( $item[0] ); ?>"></span>
								<span><?php echo esc_html( $item[1] ); ?></span>
							</a>
						<?php endforeach; ?>
					</aside>

					<div class="hd-content">

						<!-- ============ GENERAL ============ -->
						<section class="hd-card" id="hd-sec-general">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-admin-generic"></span></span>
								<div>
									<h2><?php esc_html_e( 'General', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Turn the footer on and choose the logo.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Site-wide Footer', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Print this footer on every page. The theme footer is hidden automatically while it is on.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_enabled" value="yes" <?php checked( 'yes', $config['enabled'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-logo"><?php esc_html_e( 'Logo', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Leave empty to use the site logo or site name.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<div class="hd-input-group">
											<input type="text" id="hkdev-ft-logo" name="hkdev_ft_logo" value="<?php echo esc_attr( $config['logo'] ); ?>" placeholder="https://...">
											<button type="button" class="button hd-btn-soft" id="hkdev-ft-logo-pick"><?php esc_html_e( 'Select', 'hkdev-shop-elements' ); ?></button>
										</div>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-logo-width"><?php esc_html_e( 'Logo Width', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Logo size in pixels (40 – 400).', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="number" class="hd-compact" id="hkdev-ft-logo-width" name="hkdev_ft_logo_width" value="<?php echo esc_attr( $config['logo_width'] ); ?>" min="40" max="400">
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-about"><?php esc_html_e( 'About Text', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Short blurb under the logo. Basic HTML is allowed.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<textarea id="hkdev-ft-about" name="hkdev_ft_about" rows="3" placeholder="<?php esc_attr_e( 'Your one-stop shop for everyday essentials...', 'hkdev-shop-elements' ); ?>"><?php echo esc_textarea( $config['about'] ); ?></textarea>
									</div>
								</div>

							</div>
						</section>

						<!-- ============ BRAND & CONTACT ============ -->
						<section class="hd-card" id="hd-sec-brand">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-store"></span></span>
								<div>
									<h2><?php esc_html_e( 'Brand & Contact', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Shop address, phone, email and opening hours shown in the first column.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-address"><?php esc_html_e( 'Address', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Leave empty to hide the address line.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-address" name="hkdev_ft_address" value="<?php echo esc_attr( $config['address'] ); ?>" placeholder="<?php esc_attr_e( '123 Market Road, Dhaka 1200', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-phone"><?php esc_html_e( 'Phone Number', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Turns into a tappable call link.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-phone" name="hkdev_ft_phone" value="<?php echo esc_attr( $config['phone'] ); ?>" placeholder="01XXXXXXXXX">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-email"><?php esc_html_e( 'Email Address', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Support email shown as a mailto link.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-email" name="hkdev_ft_email" value="<?php echo esc_attr( $config['email'] ); ?>" placeholder="info@example.com">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-hours"><?php esc_html_e( 'Opening Hours', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'For example: Sat – Thu, 9am – 9pm.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-hours" name="hkdev_ft_hours" value="<?php echo esc_attr( $config['hours'] ); ?>" placeholder="<?php esc_attr_e( 'Sat – Thu, 9am – 9pm', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

							</div>
						</section>

						<!-- ============ LINK COLUMNS ============ -->
						<section class="hd-card" id="hd-sec-columns">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-list-view"></span></span>
								<div>
									<h2><?php esc_html_e( 'Link Columns', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Choose WordPress menus for each footer link column. Build menus under Appearance → Menus.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-menu"><?php esc_html_e( 'Quick Links Menu', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Leave on Default to use your first navigation menu.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<select id="hkdev-ft-menu" name="hkdev_ft_menu">
											<option value=""><?php esc_html_e( '— Default —', 'hkdev-shop-elements' ); ?></option>
											<?php foreach ( $menus as $menu ) : ?>
												<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( (string) $config['menu'], (string) $menu->term_id ); ?>><?php echo esc_html( $menu->name ); ?></option>
											<?php endforeach; ?>
										</select>
										<?php if ( empty( $menus ) ) : ?>
											<p class="hd-field-help hd-field-help-inline"><?php esc_html_e( 'No menu found. Create one under Appearance → Menus first.', 'hkdev-shop-elements' ); ?></p>
										<?php endif; ?>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-menu-title"><?php esc_html_e( 'Menu Column Title', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Heading shown above the links.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-menu-title" name="hkdev_ft_menu_title" value="<?php echo esc_attr( $config['menu_title'] ); ?>" placeholder="<?php esc_attr_e( 'Quick Links', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Second Link Column', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Show another menu column (for example Categories). Turn off if you only need Quick Links.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_show_categories" value="yes" <?php checked( 'yes', $config['show_categories'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-categories-menu"><?php esc_html_e( 'Categories Menu', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Create your list under Appearance → Menus, then select that menu here (same as Quick Links).', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<select id="hkdev-ft-categories-menu" name="hkdev_ft_categories_menu">
											<option value=""><?php esc_html_e( '— Select a menu —', 'hkdev-shop-elements' ); ?></option>
											<?php foreach ( $menus as $menu ) : ?>
												<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( (string) ( $config['categories_menu'] ?? '' ), (string) $menu->term_id ); ?>><?php echo esc_html( $menu->name ); ?></option>
											<?php endforeach; ?>
										</select>
										<?php if ( empty( $menus ) ) : ?>
											<p class="hd-field-help hd-field-help-inline"><?php esc_html_e( 'No menu found. Create one under Appearance → Menus first.', 'hkdev-shop-elements' ); ?></p>
										<?php endif; ?>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-categories-title"><?php esc_html_e( 'Column Title', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Heading above the second menu (e.g. Categories).', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-categories-title" name="hkdev_ft_categories_title" value="<?php echo esc_attr( $config['categories_title'] ); ?>" placeholder="<?php esc_attr_e( 'Categories', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

							</div>
						</section>

						<!-- ============ NEWSLETTER & SOCIAL ============ -->
						<?php if ( isset( $nav_items['newsletter'] ) ) : ?>
						<section class="hd-card" id="hd-sec-newsletter">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-email-alt"></span></span>
								<div>
									<h2><?php esc_html_e( 'Newsletter & Social', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Email signup form and the social profile icons.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Newsletter Form', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Show the email signup form. Subscribers are stored and emailed to the site admin.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_show_newsletter" value="yes" <?php checked( 'yes', $config['show_newsletter'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-newsletter-title"><?php esc_html_e( 'Newsletter Title', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Heading above the signup form.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-newsletter-title" name="hkdev_ft_newsletter_title" value="<?php echo esc_attr( $config['newsletter_title'] ); ?>" placeholder="<?php esc_attr_e( 'Newsletter', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-newsletter-text"><?php esc_html_e( 'Newsletter Text', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Short line inviting visitors to subscribe. Basic HTML is allowed.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<textarea id="hkdev-ft-newsletter-text" name="hkdev_ft_newsletter_text" rows="2"><?php echo esc_textarea( $config['newsletter_text'] ); ?></textarea>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-newsletter-action"><?php esc_html_e( 'Form Action URL (optional)', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Point to Mailchimp / your mailing provider. Leave empty to store signups inside WordPress.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="url" id="hkdev-ft-newsletter-action" name="hkdev_ft_newsletter_action" value="<?php echo esc_attr( $config['newsletter_action'] ); ?>" placeholder="https://...">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-newsletter-btn"><?php esc_html_e( 'Button Label', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Text on the subscribe button.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-newsletter-btn" name="hkdev_ft_newsletter_btn" value="<?php echo esc_attr( $config['newsletter_btn'] ); ?>" placeholder="<?php esc_attr_e( 'Subscribe', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Social Icons', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Leave a field empty to hide that icon.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_show_social" value="yes" <?php checked( 'yes', $config['show_social'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Social URLs', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Facebook, Instagram, YouTube and WhatsApp links.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input hd-input-stack">
										<input type="url" name="hkdev_ft_facebook" value="<?php echo esc_attr( $config['facebook'] ); ?>" placeholder="Facebook URL">
										<input type="url" name="hkdev_ft_instagram" value="<?php echo esc_attr( $config['instagram'] ); ?>" placeholder="Instagram URL">
										<input type="url" name="hkdev_ft_youtube" value="<?php echo esc_attr( $config['youtube'] ); ?>" placeholder="YouTube URL">
										<input type="url" name="hkdev_ft_whatsapp" value="<?php echo esc_attr( $config['whatsapp'] ); ?>" placeholder="WhatsApp URL (https://wa.me/8801...)">
									</div>
								</div>

							</div>
						</section>
						<?php endif; ?>

						<!-- ============ PAYMENTS & COPYRIGHT ============ -->
						<?php if ( isset( $nav_items['bottom'] ) ) : ?>
						<section class="hd-card" id="hd-sec-bottom">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-money-alt"></span></span>
								<div>
									<h2><?php esc_html_e( 'Payments & Copyright', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'The bottom bar — payment badges and the copyright line.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Payment Banner', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Show the accepted payment methods / SSL provider banner.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_show_payments" value="yes" <?php checked( 'yes', $config['show_payments'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-payment-banner"><?php esc_html_e( 'Banner Image', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Upload the payment / SSL provider banner. Leave empty to hide it.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<div class="hd-media-field">
											<div class="hd-input-group">
												<input type="text" id="hkdev-ft-payment-banner" name="hkdev_ft_payment_banner" value="<?php echo esc_attr( $config['payment_banner'] ); ?>" placeholder="https://...">
												<button type="button" class="button hd-btn-soft" id="hkdev-ft-pay-pick"><?php esc_html_e( 'Select', 'hkdev-shop-elements' ); ?></button>
											</div>
											<img class="hd-media-preview<?php echo $config['payment_banner'] ? ' is-visible' : ''; ?>" id="hkdev-ft-pay-preview" src="<?php echo esc_url( $config['payment_banner'] ); ?>" alt="">
										</div>
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-payment-banner-link"><?php esc_html_e( 'Banner Link (optional)', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Where the banner points when clicked. Leave empty for no link.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="url" id="hkdev-ft-payment-banner-link" name="hkdev_ft_payment_banner_link" value="<?php echo esc_attr( $config['payment_banner_link'] ); ?>" placeholder="https://...">
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-ft-copyright"><?php esc_html_e( 'Copyright Text', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Use {year} and {site} as placeholders. Leave empty for the default notice.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-ft-copyright" name="hkdev_ft_copyright" value="<?php echo esc_attr( $config['copyright'] ); ?>" placeholder="© {year} {site}. All rights reserved.">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Back to Top Button', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Floating button that scrolls back to the top.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_ft_show_backtotop" value="yes" <?php checked( 'yes', $config['show_backtotop'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

							</div>
						</section>
						<?php endif; ?>

						<!-- ============ APPEARANCE ============ -->
						<section class="hd-card" id="hd-sec-appearance">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-art"></span></span>
								<div>
									<h2><?php esc_html_e( 'Appearance', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Pixel-perfect control of the footer — colours, fonts, sizes and spacing. Leave a field empty to keep the plugin default.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">
								<?php
								$app_groups = [
									[
										'title'  => __( 'Typography & Layout', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_font', 'type' => 'text', 'label' => __( 'Font Family', 'hkdev-shop-elements' ), 'ph' => "'Hind Siliguri', sans-serif", 'help' => __( 'Any font stack, e.g. "Poppins", sans-serif.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_font_size', 'type' => 'number', 'label' => __( 'Base Font Size (px)', 'hkdev-shop-elements' ), 'ph' => '15', 'help' => '' ],
											[ 'key' => 'st_container', 'type' => 'number', 'label' => __( 'Container Width (px)', 'hkdev-shop-elements' ), 'ph' => '1280', 'help' => __( 'Max width of the footer content.', 'hkdev-shop-elements' ) ],
										],
									],
									[
										'title'  => __( 'Colours', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_bg', 'type' => 'color', 'label' => __( 'Footer Background', 'hkdev-shop-elements' ), 'ph' => '#0b1f15', 'help' => '' ],
											[ 'key' => 'st_bg2', 'type' => 'color', 'label' => __( 'Bottom Bar Background', 'hkdev-shop-elements' ), 'ph' => '#071710', 'help' => '' ],
											[ 'key' => 'st_text', 'type' => 'color', 'label' => __( 'Body Text', 'hkdev-shop-elements' ), 'ph' => '#c6d5cb', 'help' => '' ],
											[ 'key' => 'st_heading', 'type' => 'color', 'label' => __( 'Headings & Titles', 'hkdev-shop-elements' ), 'ph' => '#ffffff', 'help' => '' ],
											[ 'key' => 'st_muted', 'type' => 'color', 'label' => __( 'Muted Text', 'hkdev-shop-elements' ), 'ph' => '#8ba498', 'help' => '' ],
											[ 'key' => 'st_green', 'type' => 'color', 'label' => __( 'Accent Green', 'hkdev-shop-elements' ), 'ph' => '#03a550', 'help' => '' ],
											[ 'key' => 'st_orange', 'type' => 'color', 'label' => __( 'Accent Orange', 'hkdev-shop-elements' ), 'ph' => '#f06724', 'help' => '' ],
											[ 'key' => 'st_border', 'type' => 'color', 'label' => __( 'Border', 'hkdev-shop-elements' ), 'ph' => 'rgba(255,255,255,0.1)', 'help' => '' ],
											[ 'key' => 'st_soft', 'type' => 'color', 'label' => __( 'Soft Fill', 'hkdev-shop-elements' ), 'ph' => 'rgba(255,255,255,0.05)', 'help' => '' ],
										],
									],
									[
										'title'  => __( 'Spacing & Sizes', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_main_pad_y', 'type' => 'number', 'label' => __( 'Main Padding (px)', 'hkdev-shop-elements' ), 'ph' => '58', 'help' => __( 'Top and bottom padding of the main footer area.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_grid_gap', 'type' => 'number', 'label' => __( 'Column Gap (px)', 'hkdev-shop-elements' ), 'ph' => '38', 'help' => '' ],
											[ 'key' => 'st_bottom_pad_y', 'type' => 'number', 'label' => __( 'Bottom Bar Padding (px)', 'hkdev-shop-elements' ), 'ph' => '26', 'help' => '' ],
											[ 'key' => 'st_title_fs', 'type' => 'number', 'label' => __( 'Column Title Size (px)', 'hkdev-shop-elements' ), 'ph' => '16', 'help' => '' ],
											[ 'key' => 'st_link_fs', 'type' => 'number', 'label' => __( 'Link Font Size (px)', 'hkdev-shop-elements' ), 'ph' => '14', 'help' => '' ],
										],
									],
								];

								foreach ( $app_groups as $group ) :
									?>
									<div class="hd-app-group">
										<h3 class="hd-app-group-title"><?php echo esc_html( $group['title'] ); ?></h3>
										<?php foreach ( $group['fields'] as $field ) : ?>
											<?php
											$key   = $field['key'];
											$val   = isset( $config[ $key ] ) ? $config[ $key ] : '';
											$fid   = 'hkdev-ft-' . str_replace( '_', '-', $key );
											$fname = 'hkdev_ft_' . $key;
											?>
											<div class="hd-field">
												<div class="hd-field-info">
													<label class="hd-field-title" for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
													<?php if ( ! empty( $field['help'] ) ) : ?>
														<p class="hd-field-help"><?php echo esc_html( $field['help'] ); ?></p>
													<?php endif; ?>
												</div>
												<div class="hd-field-input">
													<?php if ( 'color' === $field['type'] ) : ?>
														<div class="hd-color-wrap">
															<input type="text" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fname ); ?>" class="hd-color-text" value="<?php echo esc_attr( $val ); ?>" placeholder="<?php echo esc_attr( $field['ph'] ); ?>">
															<input type="color" class="hd-color-pick" value="<?php echo esc_attr( preg_match( '/^#[0-9a-f]{6}$/i', (string) $val ) ? $val : ( preg_match( '/^#[0-9a-f]{6}$/i', $field['ph'] ) ? $field['ph'] : '#000000' ) ); ?>" tabindex="-1" aria-hidden="true">
														</div>
													<?php elseif ( 'number' === $field['type'] ) : ?>
														<div class="hd-devices">
															<?php
															$devices = [
																''   => __( 'Desktop', 'hkdev-shop-elements' ),
																'_t' => __( 'Tablet', 'hkdev-shop-elements' ),
																'_m' => __( 'Mobile', 'hkdev-shop-elements' ),
															];
															foreach ( $devices as $sfx => $dlabel ) :
																$dkey = $key . $sfx;
																$did  = $fid . ( '' === $sfx ? '' : '-' . trim( $sfx, '_' ) );
																$dval = isset( $config[ $dkey ] ) ? $config[ $dkey ] : '';
																?>
																<label class="hd-device" for="<?php echo esc_attr( $did ); ?>">
																	<input type="number" min="0" id="<?php echo esc_attr( $did ); ?>" name="<?php echo esc_attr( 'hkdev_ft_' . $dkey ); ?>" value="<?php echo esc_attr( $dval ? $dval : '' ); ?>" placeholder="<?php echo esc_attr( $field['ph'] ); ?>">
																	<span><?php echo esc_html( $dlabel ); ?></span>
																</label>
															<?php endforeach; ?>
														</div>
													<?php else : ?>
														<input type="text" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fname ); ?>" value="<?php echo esc_attr( $val ); ?>" placeholder="<?php echo esc_attr( $field['ph'] ); ?>">
													<?php endif; ?>
												</div>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</section>

					</div><!-- /.hd-content -->
				</div><!-- /.hd-layout -->

				<div class="hd-savebar">
					<span class="hd-savebar-hint"><?php esc_html_e( 'Changes apply site-wide as soon as you save.', 'hkdev-shop-elements' ); ?></span>
					<?php submit_button( esc_html__( 'Save Changes', 'hkdev-shop-elements' ), 'primary', 'hkdev_footer_submit', false ); ?>
				</div>
			</form>

			<!-- ============ NEWSLETTER SUBSCRIBERS ============ -->
			<section class="hd-card hd-card-standalone">
				<header class="hd-card-head">
					<span class="hd-card-icon"><span class="dashicons dashicons-groups"></span></span>
					<div>
						<h2><?php esc_html_e( 'Newsletter Subscribers', 'hkdev-shop-elements' ); ?></h2>
						<p>
							<?php
							printf(
								/* translators: %d: number of subscribers */
								esc_html__( '%d email address(es) collected from the footer form.', 'hkdev-shop-elements' ),
								(int) $subs_cnt
							);
							?>
						</p>
					</div>
				</header>
				<div class="hd-card-body">
					<?php if ( empty( $subs ) ) : ?>
						<p class="hd-empty"><?php esc_html_e( 'No subscribers yet.', 'hkdev-shop-elements' ); ?></p>
					<?php else : ?>
						<?php $subs = array_reverse( $subs, true ); ?>
						<ul class="hd-subs">
							<?php $shown = 0; ?>
							<?php foreach ( $subs as $sub_email => $sub_time ) : ?>
								<?php
								if ( $shown >= 50 ) {
									break;
								}
								++$shown;
								?>
								<li>
									<span class="hd-subs-mail"><?php echo esc_html( $sub_email ); ?></span>
									<span class="hd-subs-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $sub_time ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php if ( $subs_cnt > 50 ) : ?>
							<p class="hd-empty"><?php esc_html_e( 'Showing the latest 50 subscribers.', 'hkdev-shop-elements' ); ?></p>
						<?php endif; ?>

						<form method="post" action="" class="hd-subs-clear">
							<?php wp_nonce_field( self::CLEAR_NONCE_ACTION ); ?>
							<button type="submit" name="hkdev_footer_clear_subs" value="1" class="button hd-btn-danger"><?php esc_html_e( 'Clear all subscribers', 'hkdev-shop-elements' ); ?></button>
						</form>
					<?php endif; ?>
				</div>
			</section>

		</div>

		<style>
			.hkdev-hd-wrap { max-width: 1180px; margin: 16px 0 60px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif; }
			.hkdev-hd-wrap *, .hkdev-hd-wrap *::before, .hkdev-hd-wrap *::after { box-sizing: border-box; }

			/* ---- Hero ---- */
			.hd-hero { position: relative; display: flex; align-items: center; gap: 24px; flex-wrap: wrap; padding: 26px 30px; border-radius: 16px; background: linear-gradient(135deg, #03a550 0%, #026b33 100%); color: #fff; overflow: hidden; box-shadow: 0 18px 38px -18px rgba(3, 165, 80, 0.65); }
			.hd-hero::after { content: ""; position: absolute; right: -70px; top: -70px; width: 230px; height: 230px; border-radius: 50%; background: rgba(255, 255, 255, 0.09); }
			.hd-hero-main { position: relative; z-index: 1; flex: 1 1 420px; }
			.hd-hero-badge { display: inline-block; font-size: 10.5px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; padding: 4px 11px; border-radius: 999px; background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.22); }
			.hd-hero h1 { margin: 12px 0 6px; padding: 0; font-size: 26px; line-height: 1.2; font-weight: 700; color: #fff; }
			.hd-hero p { margin: 0; max-width: 640px; font-size: 13px; line-height: 1.6; color: rgba(255, 255, 255, 0.88); }
			.hd-hero-status { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-radius: 13px; background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.26); }
			.hd-status-dot { width: 10px; height: 10px; border-radius: 50%; background: #ffd166; box-shadow: 0 0 0 5px rgba(255, 209, 102, 0.22); flex: 0 0 auto; }
			.hd-hero-status.is-on .hd-status-dot { background: #8ff0b5; box-shadow: 0 0 0 5px rgba(143, 240, 181, 0.22); }
			.hd-status-label { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(255, 255, 255, 0.8); }
			.hd-status-value { font-size: 14px; font-weight: 700; color: #fff; }

			.hd-notice { margin: 16px 0 0 !important; border-radius: 10px; }

			/* ---- Layout ---- */
			.hd-layout { display: flex; align-items: flex-start; gap: 24px; margin-top: 24px; }
			.hd-nav { position: sticky; top: 46px; flex: 0 0 212px; display: flex; flex-direction: column; gap: 4px; padding: 10px; border: 1px solid #e3e6e8; border-radius: 14px; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); }
			.hd-nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 9px; font-size: 13px; font-weight: 600; color: #3c434a; text-decoration: none; transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease; }
			.hd-nav-item .dashicons { font-size: 18px; width: 18px; height: 18px; }
			.hd-nav-item:hover { background: #f1f8f3; color: #03a550; }
			.hd-nav-item.is-active { background: #03a550; color: #fff; box-shadow: 0 10px 20px -10px rgba(3, 165, 80, 0.85); }
			.hd-content { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 20px; }

			/* ---- Cards ---- */
			.hd-card { border: 1px solid #e3e6e8; border-radius: 14px; background: #fff; overflow: hidden; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04); scroll-margin-top: 40px; }
			.hd-card-standalone { margin-top: 24px; }
			.hd-card-head { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-bottom: 1px solid #eef0f2; background: #fbfcfc; }
			.hd-card-icon { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 11px; background: #eef9f2; color: #03a550; flex: 0 0 auto; }
			.hd-card-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }
			.hd-card-head h2 { margin: 0; padding: 0; font-size: 15px; font-weight: 700; color: #1d2327; }
			.hd-card-head p { margin: 3px 0 0; font-size: 12.5px; line-height: 1.5; color: #646970; }
			.hd-card-body { padding: 4px 22px; }

			/* ---- Fields ---- */
			.hd-field { display: flex; align-items: flex-start; gap: 24px; padding: 17px 0; border-bottom: 1px solid #f0f2f4; }
			.hd-field:last-child { border-bottom: 0; }
			.hd-field-info { flex: 1 1 auto; min-width: 0; }
			.hd-field-title { display: block; margin-bottom: 4px; font-size: 13.5px; font-weight: 600; color: #1d2327; }
			.hd-field-help { margin: 0; font-size: 12.5px; line-height: 1.55; color: #646970; }
			.hd-field-help-inline { margin-top: 6px; }
			.hd-field-input { flex: 0 0 420px; max-width: 420px; display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
			.hd-field.is-wide .hd-field-input { align-items: flex-start; }
			.hd-input-stack { flex-direction: column; align-items: stretch; }
			.hd-input-stack input { width: 100%; }

			/* ---- Inputs ---- */
			.hkdev-hd-wrap input[type="text"], .hkdev-hd-wrap input[type="url"], .hkdev-hd-wrap input[type="number"], .hkdev-hd-wrap select, .hkdev-hd-wrap textarea { width: 100%; max-width: 100%; min-height: 38px; padding: 7px 12px; border: 1px solid #d0d5d9; border-radius: 9px; background: #fff; color: #1d2327; font-size: 13px; line-height: 1.4; box-shadow: none; transition: border-color 0.18s ease, box-shadow 0.18s ease; }
			.hkdev-hd-wrap textarea { resize: vertical; }
			.hkdev-hd-wrap input[type="text"]:focus, .hkdev-hd-wrap input[type="url"]:focus, .hkdev-hd-wrap input[type="number"]:focus, .hkdev-hd-wrap select:focus, .hkdev-hd-wrap textarea:focus { border-color: #03a550; box-shadow: 0 0 0 3px rgba(3, 165, 80, 0.15); outline: none; }
			.hkdev-hd-wrap input.hd-compact { flex: 0 0 96px; width: 96px; }
			.hd-input-group { display: flex; align-items: center; gap: 8px; width: 100%; }
			.hd-input-group input[type="text"] { flex: 1 1 auto; }
			.hd-btn-soft { border-radius: 9px !important; border-color: #d0d5d9 !important; color: #03a550 !important; font-weight: 600; box-shadow: none !important; }
			.hd-btn-soft:hover { border-color: #03a550 !important; background: #f1f8f3 !important; }
			.hd-btn-danger { border-radius: 9px !important; border-color: #e6b3b3 !important; color: #b32d2e !important; font-weight: 600; box-shadow: none !important; }
			.hd-btn-danger:hover { border-color: #b32d2e !important; background: #fdf2f2 !important; }
			.hd-media-field { width: 100%; }
			.hd-media-preview { display: none; width: auto; max-width: 100%; max-height: 54px; margin-top: 10px; padding: 6px; border: 1px solid #e3e6e8; border-radius: 9px; background: #fbfcfc; object-fit: contain; }
			.hd-media-preview.is-visible { display: block; }

			/* ---- Toggle switch ---- */
			.hd-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
			.hd-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
			.hd-switch-track { position: relative; width: 46px; height: 26px; border-radius: 999px; background: #cbd2d6; transition: background 0.22s ease; flex: 0 0 auto; }
			.hd-switch-track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28); transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1); }
			.hd-switch input:checked + .hd-switch-track { background: #03a550; }
			.hd-switch input:checked + .hd-switch-track::after { transform: translateX(20px); }
			.hd-switch input:focus-visible + .hd-switch-track { box-shadow: 0 0 0 3px rgba(3, 165, 80, 0.3); }

			/* ---- Subscribers ---- */
			.hd-empty { padding: 6px 0 14px; margin: 0; font-size: 13px; color: #646970; }
			.hd-subs { margin: 6px 0 12px; padding: 0; list-style: none; }
			.hd-subs li { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #f0f2f4; font-size: 13px; }
			.hd-subs li:last-child { border-bottom: 0; }
			.hd-subs-mail { font-weight: 600; color: #1d2327; word-break: break-all; }
			.hd-subs-date { color: #646970; font-size: 12px; flex: 0 0 auto; }
			.hd-subs-clear { padding: 6px 0 12px; }

			/* ---- Save bar ---- */
			.hd-savebar { position: sticky; bottom: 12px; z-index: 6; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-top: 22px; padding: 14px 22px; border: 1px solid #e3e6e8; border-radius: 14px; background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(8px); box-shadow: 0 -8px 28px -16px rgba(0, 0, 0, 0.3); }
			.hd-savebar-hint { font-size: 12.5px; color: #646970; }
			.hd-savebar .button-primary { height: auto; padding: 8px 26px; border: 1px solid #03a550 !important; border-radius: 9px; background: #03a550 !important; color: #fff !important; font-size: 13px; font-weight: 600; text-shadow: none !important; box-shadow: none !important; transition: background 0.18s ease, border-color 0.18s ease; }
			.hd-savebar .button-primary:hover, .hd-savebar .button-primary:focus { background: #028a40 !important; border-color: #028a40 !important; }

			/* ---- Responsive ---- */
			@media (max-width: 960px) {
				.hd-layout { flex-direction: column; }
				.hd-nav { position: sticky; top: 32px; z-index: 7; flex-direction: row; width: 100%; overflow-x: auto; gap: 6px; padding: 8px; }
				.hd-nav-item { white-space: nowrap; padding: 8px 12px; }
				.hd-field { flex-direction: column; gap: 10px; }
				.hd-field-input { flex: 1 1 auto; max-width: 100%; width: 100%; justify-content: flex-start; }
				.hd-hero { padding: 22px; }
				.hd-hero h1 { font-size: 22px; }
				.hd-hero-status { margin-left: 0; }
			}

			/* ---- Appearance colour fields ---- */
			.hd-app-group { border-top: 1px solid #f0f2f4; }
			.hd-app-group:first-child { border-top: 0; }
			.hd-app-group-title { margin: 0; padding: 18px 0 2px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; color: #03a550; }
			.hd-color-wrap { display: flex; align-items: center; gap: 8px; width: 100%; }
			.hd-color-wrap .hd-color-text { flex: 1 1 auto; }
			.hd-color-pick { flex: 0 0 auto; width: 42px; height: 38px; padding: 2px; border: 1px solid #d0d5d9; border-radius: 9px; background: #fff; cursor: pointer; }
			.hd-devices { display: flex; gap: 8px; width: 100%; }
			.hd-device { flex: 1 1 0; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
			.hd-device input { width: 100%; min-width: 0; }
			.hd-device span { font-size: 10.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #646970; }
		</style>
		<script>
		jQuery( function ( $ ) {
			'use strict';

			/* ---- Media pickers (logo + payment banner) ---- */
			function bindMediaPicker( buttonSelector, inputSelector, previewSelector ) {
				var frame = null;

				$( buttonSelector ).on( 'click', function ( e ) {
					e.preventDefault();

					if ( frame ) {
						frame.open();
						return;
					}

					frame = wp.media( {
						title: <?php echo wp_json_encode( esc_html__( 'Select Image', 'hkdev-shop-elements' ) ); ?>,
						button: { text: <?php echo wp_json_encode( esc_html__( 'Use this image', 'hkdev-shop-elements' ) ); ?> },
						library: { type: 'image' },
						multiple: false
					} );

					frame.on( 'select', function () {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						$( inputSelector ).val( attachment.url );

						if ( previewSelector ) {
							$( previewSelector ).attr( 'src', attachment.url ).addClass( 'is-visible' );
						}
					} );

					frame.open();
				} );
			}

			bindMediaPicker( '#hkdev-ft-logo-pick', '#hkdev-ft-logo' );
			bindMediaPicker( '#hkdev-ft-pay-pick', '#hkdev-ft-payment-banner', '#hkdev-ft-pay-preview' );

			/* ---- Section nav: smooth scroll + scroll spy ---- */
			var $navItems = $( '.hkdev-hd-wrap .hd-nav-item' );
			var $sections = $( '.hkdev-hd-wrap .hd-card[id]' );

			$navItems.on( 'click', function ( e ) {
				var target = $( this ).attr( 'href' );
				if ( ! target || '#' !== target.charAt( 0 ) ) {
					return;
				}
				var $target = $( target );
				if ( ! $target.length ) {
					return;
				}
				e.preventDefault();
				$( 'html, body' ).animate( { scrollTop: $target.offset().top - 40 }, 420 );
			} );

			function spy() {
				if ( ! $sections.length ) {
					return;
				}
				var pos = $( window ).scrollTop() + 90;
				var current = $sections.first().attr( 'id' );
				$sections.each( function () {
					if ( $( this ).offset().top <= pos ) {
						current = this.id;
					}
				} );
				$navItems.removeClass( 'is-active' ).filter( '[href="#' + current + '"]' ).addClass( 'is-active' );
			}

			if ( $sections.length ) {
				$( window ).on( 'scroll', spy );
				spy();
			}

			/* ---- Keep the hero status pill in sync with the toggle ---- */
			var $enable = $( 'input[name="hkdev_ft_enabled"]' );
			var $status = $( '.hd-hero-status' );

			function syncStatus() {
				var on = $enable.is( ':checked' );
				$status.toggleClass( 'is-on', on );
				$status.find( '.hd-status-value' ).text( on ? <?php echo wp_json_encode( esc_html__( 'Active', 'hkdev-shop-elements' ) ); ?> : <?php echo wp_json_encode( esc_html__( 'Inactive', 'hkdev-shop-elements' ) ); ?> );
			}

			$enable.on( 'change', syncStatus );
			syncStatus();

			/* ---- Appearance: keep each colour picker and its text field in sync ---- */
			$( document ).on( 'input change', '.hd-color-pick', function () {
				$( this ).closest( '.hd-color-wrap' ).find( '.hd-color-text' ).val( $( this ).val() );
			} );
			$( document ).on( 'input', '.hd-color-text', function () {
				var v = $.trim( $( this ).val() );
				if ( /^#[0-9a-f]{6}$/i.test( v ) ) {
					$( this ).closest( '.hd-color-wrap' ).find( '.hd-color-pick' ).val( v );
				}
			} );
		} );
		</script>
		<?php
	}
}
