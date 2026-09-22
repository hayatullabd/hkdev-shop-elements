<?php
/**
 * HKDEV Header Options (HKDEV Shop Elements plugin).
 *
 * WP Admin settings page that stores the site-wide header configuration in the
 * hkdev_elements_header_config option. When "Enable site-wide header" is on the
 * Header_Engine prints the header on every front-end page (wp_body_open), so no
 * Elementor Pro / Theme Builder is required.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Header_Options
 */
final class Header_Options {

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
	const SETTINGS_SLUG = 'hkdev-shop-elements-header';

	/**
	 * Nonce action used by the settings form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'hkdev_elements_header_save';

	/**
	 * @var ?Header_Options
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Header_Options
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
			esc_html__( 'Header', 'hkdev-shop-elements' ),
			esc_html__( 'Header', 'hkdev-shop-elements' ),
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

		$engine = Header_Engine::instance();
		$saved_notice = false;

		if ( isset( $_POST['hkdev_header_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

			$choice = static function ( $key, $allowed, $default ) {
				$value = isset( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return in_array( $value, $allowed, true ) ? $value : $default;
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
				'enabled'          => $yes_no( 'hkdev_hd_enabled' ),
				'logo'             => $url( 'hkdev_hd_logo' ),
				'logo_width'       => isset( $_POST['hkdev_hd_logo_width'] ) ? absint( wp_unslash( $_POST['hkdev_hd_logo_width'] ) ) : 150, // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'menu'             => $text( 'hkdev_hd_menu' ),
				'sticky'           => $yes_no( 'hkdev_hd_sticky' ),
				'hide_desk'        => $choice( 'hkdev_hd_hide_desk', [ 'full', 'top', 'none' ], 'full' ),
				'hide_mobile'      => $choice( 'hkdev_hd_hide_mobile', [ 'full', 'top', 'none' ], 'full' ),
				'show_topbar'      => $yes_no( 'hkdev_hd_show_topbar' ),
				'announcement'     => $text( 'hkdev_hd_announcement' ),
				'phone'            => $text( 'hkdev_hd_phone' ),
				'email'            => $text( 'hkdev_hd_email' ),
				'facebook'         => $url( 'hkdev_hd_facebook' ),
				'instagram'        => $url( 'hkdev_hd_instagram' ),
				'youtube'          => $url( 'hkdev_hd_youtube' ),
				'track_url'        => $url( 'hkdev_hd_track_url' ),
				'show_menu'        => $yes_no( 'hkdev_hd_show_menu' ),
				'show_search'      => $yes_no( 'hkdev_hd_show_search' ),
				'show_account'     => $yes_no( 'hkdev_hd_show_account' ),
				'show_cart'        => $yes_no( 'hkdev_hd_show_cart' ),
				'mini_cart'        => $yes_no( 'hkdev_hd_mini_cart' ),
				'float_cart'       => $yes_no( 'hkdev_hd_float_cart' ),
				'float_cart_empty' => $yes_no( 'hkdev_hd_float_cart_empty' ),
				'show_categories'  => $yes_no( 'hkdev_hd_show_categories' ),
				'categories_label' => $text( 'hkdev_hd_categories_label' ),
				'categories_limit' => isset( $_POST['hkdev_hd_categories_limit'] ) ? absint( wp_unslash( $_POST['hkdev_hd_categories_limit'] ) ) : 8, // phpcs:ignore WordPress.Security.NonceVerification.Missing

				// ---- Appearance ----
				'st_font'          => $font_stack( 'hkdev_hd_st_font' ),
				'st_font_size'     => $num( 'hkdev_hd_st_font_size' ),
				'st_font_size_t'   => $num( 'hkdev_hd_st_font_size_t' ),
				'st_font_size_m'   => $num( 'hkdev_hd_st_font_size_m' ),
				'st_density_preset' => $choice( 'hkdev_hd_st_density_preset', [ 'normal', 'compact', 'ultra' ], 'normal' ),
				'st_style_preset'   => $choice( 'hkdev_hd_st_style_preset', [ 'classic', 'modern-clean', 'minimal', 'bold-ecommerce' ], 'classic' ),
				'st_mobile_preset'  => $choice( 'hkdev_hd_st_mobile_preset', [ 'logo-first', 'search-first', 'icons-only', 'minimal' ], 'logo-first' ),
				'st_container'     => $num( 'hkdev_hd_st_container' ),
				'st_container_t'   => $num( 'hkdev_hd_st_container_t' ),
				'st_container_m'   => $num( 'hkdev_hd_st_container_m' ),
				'st_radius'        => $num( 'hkdev_hd_st_radius' ),
				'st_radius_t'      => $num( 'hkdev_hd_st_radius_t' ),
				'st_radius_m'      => $num( 'hkdev_hd_st_radius_m' ),
				'st_primary'       => $css_color( 'hkdev_hd_st_primary' ),
				'st_secondary'     => $css_color( 'hkdev_hd_st_secondary' ),
				'st_text'          => $css_color( 'hkdev_hd_st_text' ),
				'st_muted'         => $css_color( 'hkdev_hd_st_muted' ),
				'st_soft'          => $css_color( 'hkdev_hd_st_soft' ),
				'st_border'        => $css_color( 'hkdev_hd_st_border' ),
				'st_topbar_bg'     => $css_color( 'hkdev_hd_st_topbar_bg' ),
				'st_topbar_color'  => $css_color( 'hkdev_hd_st_topbar_color' ),
				'st_topbar_h'      => $num( 'hkdev_hd_st_topbar_h' ),
				'st_topbar_h_t'    => $num( 'hkdev_hd_st_topbar_h_t' ),
				'st_topbar_h_m'    => $num( 'hkdev_hd_st_topbar_h_m' ),
				'st_topbar_fs'     => $num( 'hkdev_hd_st_topbar_fs' ),
				'st_topbar_fs_t'   => $num( 'hkdev_hd_st_topbar_fs_t' ),
				'st_topbar_fs_m'   => $num( 'hkdev_hd_st_topbar_fs_m' ),
				'st_topbar_preset' => $choice( 'hkdev_hd_st_topbar_preset', [ 'inherit', 'normal', 'compact', 'ultra' ], 'inherit' ),
				'st_topbar_icon'   => $num( 'hkdev_hd_st_topbar_icon' ),
				'st_topbar_icon_t' => $num( 'hkdev_hd_st_topbar_icon_t' ),
				'st_topbar_icon_m' => $num( 'hkdev_hd_st_topbar_icon_m' ),
				'st_social_size'   => $num( 'hkdev_hd_st_social_size' ),
				'st_social_size_t' => $num( 'hkdev_hd_st_social_size_t' ),
				'st_social_size_m' => $num( 'hkdev_hd_st_social_size_m' ),
				'st_social_icon'   => $num( 'hkdev_hd_st_social_icon' ),
				'st_social_icon_t' => $num( 'hkdev_hd_st_social_icon_t' ),
				'st_social_icon_m' => $num( 'hkdev_hd_st_social_icon_m' ),
				'st_main_bg'       => $css_color( 'hkdev_hd_st_main_bg' ),
				'st_main_h'        => $num( 'hkdev_hd_st_main_h' ),
				'st_main_h_t'      => $num( 'hkdev_hd_st_main_h_t' ),
				'st_main_h_m'      => $num( 'hkdev_hd_st_main_h_m' ),
				'st_main_preset'   => $choice( 'hkdev_hd_st_main_preset', [ 'inherit', 'normal', 'compact', 'ultra' ], 'inherit' ),
				'st_navbar_bg'     => $css_color( 'hkdev_hd_st_navbar_bg' ),
				'st_navbar_color'  => $css_color( 'hkdev_hd_st_navbar_color' ),
				'st_navbar_h'      => $num( 'hkdev_hd_st_navbar_h' ),
				'st_navbar_h_t'    => $num( 'hkdev_hd_st_navbar_h_t' ),
				'st_navbar_h_m'    => $num( 'hkdev_hd_st_navbar_h_m' ),
				'st_navbar_preset' => $choice( 'hkdev_hd_st_navbar_preset', [ 'inherit', 'normal', 'compact', 'ultra' ], 'inherit' ),
				'st_navbar_fs'     => $num( 'hkdev_hd_st_navbar_fs' ),
				'st_navbar_fs_t'   => $num( 'hkdev_hd_st_navbar_fs_t' ),
				'st_navbar_fs_m'   => $num( 'hkdev_hd_st_navbar_fs_m' ),
				'st_search_h'      => $num( 'hkdev_hd_st_search_h' ),
				'st_search_h_t'    => $num( 'hkdev_hd_st_search_h_t' ),
				'st_search_h_m'    => $num( 'hkdev_hd_st_search_h_m' ),
				'st_logo_maxh'     => $num( 'hkdev_hd_st_logo_maxh' ),
				'st_logo_maxh_t'   => $num( 'hkdev_hd_st_logo_maxh_t' ),
				'st_logo_maxh_m'   => $num( 'hkdev_hd_st_logo_maxh_m' ),
			];

			if ( '' === $config['categories_label'] ) {
				$config['categories_label'] = esc_html__( 'All Categories', 'hkdev-shop-elements' );
			}
			if ( $config['logo_width'] < 1 ) {
				$config['logo_width'] = 150;
			}
			if ( $config['categories_limit'] < 1 ) {
				$config['categories_limit'] = 8;
			}

			update_option( Header_Engine::CONFIG_OPTION, $config );
			$saved_notice = true;
		}

		$config = $engine->get_config();
		$menus  = wp_get_nav_menus();

		$nav_items = [
			'general' => [ 'dashicons-admin-generic', esc_html__( 'General', 'hkdev-shop-elements' ) ],
			'topbar'  => [ 'dashicons-welcome-widgets-menus', esc_html__( 'Top Bar', 'hkdev-shop-elements' ) ],
			'contact' => [ 'dashicons-phone', esc_html__( 'Contact', 'hkdev-shop-elements' ) ],
			'elements' => [ 'dashicons-screenoptions', esc_html__( 'Elements', 'hkdev-shop-elements' ) ],
			'cart'    => [ 'dashicons-cart', esc_html__( 'Floating Cart', 'hkdev-shop-elements' ) ],
			'appearance' => [ 'dashicons-art', esc_html__( 'Appearance', 'hkdev-shop-elements' ) ],
		];
		?>
		<div class="wrap hkdev-hd-wrap">

			<div class="hd-hero">
				<div class="hd-hero-main">
					<span class="hd-hero-badge"><?php esc_html_e( 'HKDEV Shop Elements', 'hkdev-shop-elements' ); ?></span>
					<h1><?php esc_html_e( 'Header Builder', 'hkdev-shop-elements' ); ?></h1>
					<p><?php esc_html_e( 'Design the site header section by section. Everything here applies site-wide — no Elementor Pro needed.', 'hkdev-shop-elements' ); ?></p>
				</div>
				<div class="hd-hero-status<?php echo 'yes' === $config['enabled'] ? ' is-on' : ''; ?>">
					<span class="hd-status-dot" aria-hidden="true"></span>
					<div>
						<div class="hd-status-label"><?php esc_html_e( 'Header Status', 'hkdev-shop-elements' ); ?></div>
						<div class="hd-status-value"><?php echo 'yes' === $config['enabled'] ? esc_html__( 'Active', 'hkdev-shop-elements' ) : esc_html__( 'Inactive', 'hkdev-shop-elements' ); ?></div>
					</div>
				</div>
			</div>

			<?php if ( $saved_notice ) : ?>
				<div class="notice notice-success is-dismissible hd-notice"><p><?php esc_html_e( 'Header settings saved.', 'hkdev-shop-elements' ); ?></p></div>
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
									<p><?php esc_html_e( 'Turn the header on and choose the logo, menu and sticky behaviour.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Site-wide Header', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Print this header on every page. The theme header is hidden automatically while it is on.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_enabled" value="yes" <?php checked( 'yes', $config['enabled'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-logo"><?php esc_html_e( 'Logo', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Leave empty to use the site logo or site name.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<div class="hd-input-group">
											<input type="text" id="hkdev-hd-logo" name="hkdev_hd_logo" value="<?php echo esc_attr( $config['logo'] ); ?>" placeholder="https://...">
											<button type="button" class="button hd-btn-soft" id="hkdev-hd-logo-pick"><?php esc_html_e( 'Select', 'hkdev-shop-elements' ); ?></button>
										</div>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-logo-width"><?php esc_html_e( 'Logo Width', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Logo size in pixels (40 – 400).', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="number" class="hd-compact" id="hkdev-hd-logo-width" name="hkdev_hd_logo_width" value="<?php echo esc_attr( $config['logo_width'] ); ?>" min="40" max="400">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-menu"><?php esc_html_e( 'Menu', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Navigation menu shown in the header bar.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<select id="hkdev-hd-menu" name="hkdev_hd_menu">
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
										<span class="hd-field-title"><?php esc_html_e( 'Sticky Header', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Keep the header fixed at the top while scrolling.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_sticky" value="yes" <?php checked( 'yes', $config['sticky'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-hide-desk"><?php esc_html_e( 'Hide on Scroll Down — Desktop', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'What happens to the header while scrolling down on desktop. It always returns on scroll up. Needs Sticky Header turned on.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<select id="hkdev-hd-hide-desk" name="hkdev_hd_hide_desk">
											<option value="full" <?php selected( 'full', $config['hide_desk'] ); ?>><?php esc_html_e( 'Entire Header', 'hkdev-shop-elements' ); ?></option>
											<option value="top" <?php selected( 'top', $config['hide_desk'] ); ?>><?php esc_html_e( 'Top Bar Only', 'hkdev-shop-elements' ); ?></option>
											<option value="none" <?php selected( 'none', $config['hide_desk'] ); ?>><?php esc_html_e( 'Keep Visible', 'hkdev-shop-elements' ); ?></option>
										</select>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-hide-mobile"><?php esc_html_e( 'Hide on Scroll Down — Mobile', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'What happens to the header while scrolling down on mobile. It always returns on scroll up. Needs Sticky Header turned on.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<select id="hkdev-hd-hide-mobile" name="hkdev_hd_hide_mobile">
											<option value="full" <?php selected( 'full', $config['hide_mobile'] ); ?>><?php esc_html_e( 'Entire Header', 'hkdev-shop-elements' ); ?></option>
											<option value="top" <?php selected( 'top', $config['hide_mobile'] ); ?>><?php esc_html_e( 'Top Bar Only', 'hkdev-shop-elements' ); ?></option>
											<option value="none" <?php selected( 'none', $config['hide_mobile'] ); ?>><?php esc_html_e( 'Keep Visible', 'hkdev-shop-elements' ); ?></option>
										</select>
									</div>
								</div>

							</div>
						</section>

						<!-- ============ TOP BAR ============ -->
						<section class="hd-card" id="hd-sec-topbar">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-welcome-widgets-menus"></span></span>
								<div>
									<h2><?php esc_html_e( 'Top Bar', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'The slim utility strip above the main header — announcement and social links.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Show Top Bar', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Display the announcement / contact bar above the header.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_show_topbar" value="yes" <?php checked( 'yes', $config['show_topbar'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-announcement"><?php esc_html_e( 'Announcement', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Short message shown on the left of the top bar.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-hd-announcement" name="hkdev_hd_announcement" value="<?php echo esc_attr( $config['announcement'] ); ?>" placeholder="<?php esc_attr_e( 'Free delivery inside Dhaka on orders over 1000', 'hkdev-shop-elements' ); ?>">
									</div>
								</div>

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Social Links', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Icons appear on the right of the top bar. Leave a field empty to hide that icon.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input hd-input-stack">
										<input type="url" name="hkdev_hd_facebook" value="<?php echo esc_attr( $config['facebook'] ); ?>" placeholder="Facebook URL">
										<input type="url" name="hkdev_hd_instagram" value="<?php echo esc_attr( $config['instagram'] ); ?>" placeholder="Instagram URL">
										<input type="url" name="hkdev_hd_youtube" value="<?php echo esc_attr( $config['youtube'] ); ?>" placeholder="YouTube URL">
									</div>
								</div>

							</div>
						</section>

						<!-- ============ CONTACT ============ -->
						<section class="hd-card" id="hd-sec-contact">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-phone"></span></span>
								<div>
									<h2><?php esc_html_e( 'Contact', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Phone and email shown in the top bar and the mobile panel.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-phone"><?php esc_html_e( 'Phone Number', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Used for the call button on mobile.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-hd-phone" name="hkdev_hd_phone" value="<?php echo esc_attr( $config['phone'] ); ?>" placeholder="01XXXXXXXXX">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-email"><?php esc_html_e( 'Email Address', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Support email shown in the top bar.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="text" id="hkdev-hd-email" name="hkdev_hd_email" value="<?php echo esc_attr( $config['email'] ); ?>" placeholder="info@example.com">
									</div>
								</div>

							</div>
						</section>

						<!-- ============ ELEMENTS ============ -->
						<section class="hd-card" id="hd-sec-elements">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-screenoptions"></span></span>
								<div>
									<h2><?php esc_html_e( 'Elements', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Choose what appears in the header and where the utility links point.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field is-wide">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Show on Header', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Toggle each element independently.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<div class="hd-check-grid">
											<label class="hd-check"><input type="checkbox" name="hkdev_hd_show_menu" value="yes" <?php checked( 'yes', $config['show_menu'] ); ?>><span class="hd-check-box" aria-hidden="true"></span><span class="hd-check-label"><?php esc_html_e( 'Menu', 'hkdev-shop-elements' ); ?></span></label>
											<label class="hd-check"><input type="checkbox" name="hkdev_hd_show_search" value="yes" <?php checked( 'yes', $config['show_search'] ); ?>><span class="hd-check-box" aria-hidden="true"></span><span class="hd-check-label"><?php esc_html_e( 'Product Search', 'hkdev-shop-elements' ); ?></span></label>
											<label class="hd-check"><input type="checkbox" name="hkdev_hd_show_account" value="yes" <?php checked( 'yes', $config['show_account'] ); ?>><span class="hd-check-box" aria-hidden="true"></span><span class="hd-check-label"><?php esc_html_e( 'Log In / Register', 'hkdev-shop-elements' ); ?></span></label>
											<label class="hd-check"><input type="checkbox" name="hkdev_hd_show_cart" value="yes" <?php checked( 'yes', $config['show_cart'] ); ?>><span class="hd-check-box" aria-hidden="true"></span><span class="hd-check-label"><?php esc_html_e( 'Cart Icon', 'hkdev-shop-elements' ); ?></span></label>
											<label class="hd-check"><input type="checkbox" name="hkdev_hd_mini_cart" value="yes" <?php checked( 'yes', $config['mini_cart'] ); ?>><span class="hd-check-box" aria-hidden="true"></span><span class="hd-check-label"><?php esc_html_e( 'Mini Cart Drawer', 'hkdev-shop-elements' ); ?></span></label>
										</div>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<label class="hd-field-title" for="hkdev-hd-track-url"><?php esc_html_e( 'Track Order URL', 'hkdev-shop-elements' ); ?></label>
										<p class="hd-field-help"><?php esc_html_e( 'Leave empty to auto-detect a page with the slug track-order / order-tracking.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<input type="url" id="hkdev-hd-track-url" name="hkdev_hd_track_url" value="<?php echo esc_attr( $config['track_url'] ); ?>" placeholder="https://example.com/track-order/">
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Categories Dropdown', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Show the WooCommerce product categories dropdown.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_show_categories" value="yes" <?php checked( 'yes', $config['show_categories'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Dropdown Label & Limit', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Button label and how many categories to list.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<div class="hd-input-group">
											<input type="text" name="hkdev_hd_categories_label" value="<?php echo esc_attr( $config['categories_label'] ); ?>" placeholder="<?php esc_attr_e( 'All Categories', 'hkdev-shop-elements' ); ?>">
											<input type="number" class="hd-compact" name="hkdev_hd_categories_limit" value="<?php echo esc_attr( $config['categories_limit'] ); ?>" min="1" max="30">
										</div>
									</div>
								</div>

							</div>
						</section>

						<!-- ============ FLOATING CART ============ -->
						<section class="hd-card" id="hd-sec-cart">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-cart"></span></span>
								<div>
									<h2><?php esc_html_e( 'Floating Cart', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'The cart button pinned to the right edge of every page.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Floating Cart Button', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Clicking it opens the mini cart drawer (or the cart page when the drawer is off).', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_float_cart" value="yes" <?php checked( 'yes', $config['float_cart'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

								<div class="hd-field">
									<div class="hd-field-info">
										<span class="hd-field-title"><?php esc_html_e( 'Keep Visible When Cart Is Empty', 'hkdev-shop-elements' ); ?></span>
										<p class="hd-field-help"><?php esc_html_e( 'Leave off to hide the button until the cart has items.', 'hkdev-shop-elements' ); ?></p>
									</div>
									<div class="hd-field-input">
										<label class="hd-switch">
											<input type="checkbox" name="hkdev_hd_float_cart_empty" value="yes" <?php checked( 'yes', $config['float_cart_empty'] ); ?>>
											<span class="hd-switch-track" aria-hidden="true"></span>
										</label>
									</div>
								</div>

							</div>
						</section>

						<!-- ============ APPEARANCE ============ -->
						<section class="hd-card" id="hd-sec-appearance">
							<header class="hd-card-head">
								<span class="hd-card-icon"><span class="dashicons dashicons-art"></span></span>
								<div>
									<h2><?php esc_html_e( 'Appearance', 'hkdev-shop-elements' ); ?></h2>
									<p><?php esc_html_e( 'Pixel-perfect control of the header — bar heights, colours, fonts and sizes. Leave a field empty to keep the plugin default.', 'hkdev-shop-elements' ); ?></p>
								</div>
							</header>
							<div class="hd-card-body">
								<?php
								$preset_options = [
									'normal'  => __( 'Normal', 'hkdev-shop-elements' ),
									'compact' => __( 'Compact', 'hkdev-shop-elements' ),
									'ultra'   => __( 'Ultra Compact', 'hkdev-shop-elements' ),
								];
								$section_preset_options = [
									'inherit' => __( 'Inherit Global', 'hkdev-shop-elements' ),
									'normal'  => __( 'Normal', 'hkdev-shop-elements' ),
									'compact' => __( 'Compact', 'hkdev-shop-elements' ),
									'ultra'   => __( 'Ultra Compact', 'hkdev-shop-elements' ),
								];
								$style_preset_options = [
									'classic'        => __( 'Classic', 'hkdev-shop-elements' ),
									'modern-clean'   => __( 'Modern Clean', 'hkdev-shop-elements' ),
									'minimal'        => __( 'Minimal', 'hkdev-shop-elements' ),
									'bold-ecommerce' => __( 'Bold Ecommerce', 'hkdev-shop-elements' ),
								];
								$mobile_preset_options = [
									'logo-first'   => __( 'Logo First', 'hkdev-shop-elements' ),
									'search-first' => __( 'Search First', 'hkdev-shop-elements' ),
									'icons-only'   => __( 'Icons Only', 'hkdev-shop-elements' ),
									'minimal'      => __( 'Minimal', 'hkdev-shop-elements' ),
								];
								$app_groups = [
									[
										'title'  => __( 'Preset Controls', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_density_preset', 'type' => 'select', 'label' => __( 'Global Density', 'hkdev-shop-elements' ), 'help' => __( 'Set Normal / Compact / Ultra for the whole header.', 'hkdev-shop-elements' ), 'options' => $preset_options ],
											[ 'key' => 'st_style_preset', 'type' => 'select', 'label' => __( 'Header Style Pack', 'hkdev-shop-elements' ), 'help' => __( 'Apply curated color/style combinations quickly.', 'hkdev-shop-elements' ), 'options' => $style_preset_options ],
											[ 'key' => 'st_mobile_preset', 'type' => 'select', 'label' => __( 'Mobile Header Preset', 'hkdev-shop-elements' ), 'help' => __( 'Control mobile header layout behavior without custom CSS.', 'hkdev-shop-elements' ), 'options' => $mobile_preset_options ],
										],
									],
									[
										'title'  => __( 'Typography & Layout', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_font', 'type' => 'text', 'label' => __( 'Font Family', 'hkdev-shop-elements' ), 'ph' => "'Hind Siliguri', sans-serif", 'help' => __( 'Any font stack, e.g. "Poppins", sans-serif.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_font_size', 'type' => 'number', 'label' => __( 'Base Font Size (px)', 'hkdev-shop-elements' ), 'ph' => '15', 'help' => __( 'Header base text size.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_container', 'type' => 'number', 'label' => __( 'Container Width (px)', 'hkdev-shop-elements' ), 'ph' => '1280', 'help' => __( 'Max width of the header content.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_radius', 'type' => 'number', 'label' => __( 'Corner Radius (px)', 'hkdev-shop-elements' ), 'ph' => '12', 'help' => __( 'Radius of buttons, icon boxes and dropdowns.', 'hkdev-shop-elements' ) ],
										],
									],
									[
										'title'  => __( 'Colours', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_primary', 'type' => 'color', 'label' => __( 'Primary', 'hkdev-shop-elements' ), 'ph' => '#03a550', 'help' => __( 'Buttons, links and highlights.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_secondary', 'type' => 'color', 'label' => __( 'Secondary / Accent', 'hkdev-shop-elements' ), 'ph' => '#f06724', 'help' => __( 'Nav bar and badges.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_text', 'type' => 'color', 'label' => __( 'Body Text', 'hkdev-shop-elements' ), 'ph' => '#141a14', 'help' => '' ],
											[ 'key' => 'st_muted', 'type' => 'color', 'label' => __( 'Muted Text', 'hkdev-shop-elements' ), 'ph' => '#5f6e66', 'help' => '' ],
											[ 'key' => 'st_soft', 'type' => 'color', 'label' => __( 'Soft Background', 'hkdev-shop-elements' ), 'ph' => '#f1f8f3', 'help' => '' ],
											[ 'key' => 'st_border', 'type' => 'color', 'label' => __( 'Border', 'hkdev-shop-elements' ), 'ph' => 'rgba(0,0,0,0.08)', 'help' => '' ],
										],
									],
									[
										'title'  => __( 'Top Bar (Upper)', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_topbar_preset', 'type' => 'select', 'label' => __( 'Density Preset', 'hkdev-shop-elements' ), 'help' => __( 'Use Global by default or override per section.', 'hkdev-shop-elements' ), 'options' => $section_preset_options ],
											[ 'key' => 'st_topbar_bg', 'type' => 'color', 'label' => __( 'Background', 'hkdev-shop-elements' ), 'ph' => '#03a550', 'help' => '' ],
											[ 'key' => 'st_topbar_color', 'type' => 'color', 'label' => __( 'Text Colour', 'hkdev-shop-elements' ), 'ph' => '#ffffff', 'help' => '' ],
											[ 'key' => 'st_topbar_h', 'type' => 'number', 'label' => __( 'Height (px)', 'hkdev-shop-elements' ), 'ph' => '42', 'help' => '' ],
											[ 'key' => 'st_topbar_fs', 'type' => 'number', 'label' => __( 'Font Size (px)', 'hkdev-shop-elements' ), 'ph' => '13', 'help' => '' ],
											[ 'key' => 'st_topbar_icon', 'type' => 'number', 'label' => __( 'Icon Size (px)', 'hkdev-shop-elements' ), 'ph' => '13', 'help' => __( 'Announcement and contact icon size.', 'hkdev-shop-elements' ) ],
											[ 'key' => 'st_social_size', 'type' => 'number', 'label' => __( 'Social Button Size (px)', 'hkdev-shop-elements' ), 'ph' => '27', 'help' => '' ],
											[ 'key' => 'st_social_icon', 'type' => 'number', 'label' => __( 'Social Icon Size (px)', 'hkdev-shop-elements' ), 'ph' => '12', 'help' => '' ],
										],
									],
									[
										'title'  => __( 'Main Bar (Middle)', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_main_preset', 'type' => 'select', 'label' => __( 'Density Preset', 'hkdev-shop-elements' ), 'help' => __( 'Use Global by default or override per section.', 'hkdev-shop-elements' ), 'options' => $section_preset_options ],
											[ 'key' => 'st_main_bg', 'type' => 'color', 'label' => __( 'Background', 'hkdev-shop-elements' ), 'ph' => '#ffffff', 'help' => '' ],
											[ 'key' => 'st_main_h', 'type' => 'number', 'label' => __( 'Height (px)', 'hkdev-shop-elements' ), 'ph' => '88', 'help' => '' ],
										],
									],
									[
										'title'  => __( 'Nav Bar (Bottom)', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_navbar_preset', 'type' => 'select', 'label' => __( 'Density Preset', 'hkdev-shop-elements' ), 'help' => __( 'Use Global by default or override per section.', 'hkdev-shop-elements' ), 'options' => $section_preset_options ],
											[ 'key' => 'st_navbar_bg', 'type' => 'color', 'label' => __( 'Background', 'hkdev-shop-elements' ), 'ph' => '#f06724', 'help' => '' ],
											[ 'key' => 'st_navbar_color', 'type' => 'color', 'label' => __( 'Menu Text Colour', 'hkdev-shop-elements' ), 'ph' => '#ffffff', 'help' => '' ],
											[ 'key' => 'st_navbar_h', 'type' => 'number', 'label' => __( 'Height (px)', 'hkdev-shop-elements' ), 'ph' => '54', 'help' => '' ],
											[ 'key' => 'st_navbar_fs', 'type' => 'number', 'label' => __( 'Menu Font Size (px)', 'hkdev-shop-elements' ), 'ph' => '14', 'help' => '' ],
										],
									],
									[
										'title'  => __( 'Elements', 'hkdev-shop-elements' ),
										'fields' => [
											[ 'key' => 'st_search_h', 'type' => 'number', 'label' => __( 'Search Height (px)', 'hkdev-shop-elements' ), 'ph' => '52', 'help' => '' ],
											[ 'key' => 'st_logo_maxh', 'type' => 'number', 'label' => __( 'Logo Max Height (px)', 'hkdev-shop-elements' ), 'ph' => '62', 'help' => '' ],
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
											$fid   = 'hkdev-hd-' . str_replace( '_', '-', $key );
											$fname = 'hkdev_hd_' . $key;
											?>
											<div class="hd-field">
												<div class="hd-field-info">
													<label class="hd-field-title" for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
													<?php if ( ! empty( $field['help'] ) ) : ?>
														<p class="hd-field-help"><?php echo esc_html( $field['help'] ); ?></p>
													<?php endif; ?>
												</div>
												<div class="hd-field-input">
													<?php if ( 'select' === $field['type'] ) : ?>
														<select id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fname ); ?>">
															<?php
															$field_options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : $preset_options;
															foreach ( $field_options as $opt_key => $opt_label ) :
																?>
																<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( (string) $val, (string) $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
															<?php endforeach; ?>
														</select>
													<?php elseif ( 'color' === $field['type'] ) : ?>
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
																	<input type="number" min="0" id="<?php echo esc_attr( $did ); ?>" name="<?php echo esc_attr( 'hkdev_hd_' . $dkey ); ?>" value="<?php echo esc_attr( $dval ? $dval : '' ); ?>" placeholder="<?php echo esc_attr( $field['ph'] ); ?>">
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
					<?php submit_button( esc_html__( 'Save Changes', 'hkdev-shop-elements' ), 'primary', 'hkdev_header_submit', false ); ?>
				</div>
			</form>
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
			.hkdev-hd-wrap input[type="text"], .hkdev-hd-wrap input[type="url"], .hkdev-hd-wrap input[type="number"], .hkdev-hd-wrap select { width: 100%; max-width: 100%; min-height: 38px; padding: 7px 12px; border: 1px solid #d0d5d9; border-radius: 9px; background: #fff; color: #1d2327; font-size: 13px; line-height: 1.4; box-shadow: none; transition: border-color 0.18s ease, box-shadow 0.18s ease; }
			.hkdev-hd-wrap input[type="text"]:focus, .hkdev-hd-wrap input[type="url"]:focus, .hkdev-hd-wrap input[type="number"]:focus, .hkdev-hd-wrap select:focus { border-color: #03a550; box-shadow: 0 0 0 3px rgba(3, 165, 80, 0.15); outline: none; }
			.hkdev-hd-wrap input.hd-compact { flex: 0 0 96px; width: 96px; }
			.hd-input-group { display: flex; align-items: center; gap: 8px; width: 100%; }
			.hd-input-group input[type="text"] { flex: 1 1 auto; }
			.hd-btn-soft { border-radius: 9px !important; border-color: #d0d5d9 !important; color: #03a550 !important; font-weight: 600; box-shadow: none !important; }
			.hd-btn-soft:hover { border-color: #03a550 !important; background: #f1f8f3 !important; }

			/* ---- Toggle switch ---- */
			.hd-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
			.hd-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
			.hd-switch-track { position: relative; width: 46px; height: 26px; border-radius: 999px; background: #cbd2d6; transition: background 0.22s ease; flex: 0 0 auto; }
			.hd-switch-track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28); transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1); }
			.hd-switch input:checked + .hd-switch-track { background: #03a550; }
			.hd-switch input:checked + .hd-switch-track::after { transform: translateX(20px); }
			.hd-switch input:focus-visible + .hd-switch-track { box-shadow: 0 0 0 3px rgba(3, 165, 80, 0.3); }

			/* ---- Checkbox cards ---- */
			.hd-check-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; width: 100%; }
			.hd-check { position: relative; display: flex; align-items: center; gap: 10px; padding: 11px 13px; border: 1px solid #e3e6e8; border-radius: 10px; background: #fff; font-size: 13px; font-weight: 500; color: #3c434a; cursor: pointer; transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease; }
			.hd-check:hover { border-color: #bfe6cf; background: #f7fdf9; }
			.hd-check input { position: absolute; opacity: 0; width: 0; height: 0; }
			.hd-check-box { display: grid; place-items: center; width: 18px; height: 18px; border: 1.5px solid #c3cbd1; border-radius: 5px; flex: 0 0 auto; transition: background 0.18s ease, border-color 0.18s ease; }
			.hd-check-box::after { content: ""; width: 4px; height: 8px; margin-top: -2px; border: 2px solid #fff; border-top: 0; border-left: 0; transform: rotate(45deg) scale(0); transition: transform 0.18s ease; }
			.hd-check input:checked + .hd-check-box { background: #03a550; border-color: #03a550; }
			.hd-check input:checked + .hd-check-box::after { transform: rotate(45deg) scale(1); }
			.hd-check input:checked ~ .hd-check-label { color: #026b33; font-weight: 600; }
			.hd-check input:focus-visible + .hd-check-box { box-shadow: 0 0 0 3px rgba(3, 165, 80, 0.25); }

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
				.hd-check-grid { grid-template-columns: 1fr; }
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

			/* ---- Logo media picker ---- */
			var frame = null;

			$( '#hkdev-hd-logo-pick' ).on( 'click', function ( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: <?php echo wp_json_encode( esc_html__( 'Select Logo', 'hkdev-shop-elements' ) ); ?>,
					button: { text: <?php echo wp_json_encode( esc_html__( 'Use this image', 'hkdev-shop-elements' ) ); ?> },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					$( '#hkdev-hd-logo' ).val( attachment.url );
				} );

				frame.open();
			} );

			/* ---- Section nav: smooth scroll + scroll spy ---- */
			var $navItems = $( '.hkdev-hd-wrap .hd-nav-item' );
			var $sections = $( '.hkdev-hd-wrap .hd-card' );

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
			var $enable = $( 'input[name="hkdev_hd_enabled"]' );
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