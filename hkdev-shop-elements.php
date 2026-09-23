<?php
/**
 * Plugin Name:       HKDEV Shop Elements
 * Plugin URI:        https://github.com/hayatullabd/hkdev-shop-elements
 * Description:       Standalone Elementor + WooCommerce widgets (Shop Grid / Carousel, Cart, Checkout, Single Product, Header, Footer, Contact Form). Works with any WordPress theme.
 * Version:           0.5.113
 * Author:            Md Hayatulla Kha
 * Author URI:        https://github.com/hayatullabd
 * Text Domain:       hkdev-shop-elements
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * Update URI:        https://github.com/hayatullabd/hkdev-shop-elements
 */

namespace HkdevShopElements;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HKDEV_ELEMENTS_VERSION', '0.5.113' );
define( 'HKDEV_ELEMENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'HKDEV_ELEMENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'HKDEV_ELEMENTS_ASSETS_URL', HKDEV_ELEMENTS_URL . 'assets/' );

/**
 * GitHub repository ("owner/repo") used for one-click plugin updates.
 *
 * WP Admin → Plugins shows an update as soon as this repository has a release
 * (or tag) whose name is a higher version, e.g. "v0.2.1". Set this to your own
 * repository; it must be public (or define HKDEV_ELEMENTS_GITHUB_TOKEN for a
 * private one).
 */
if ( ! defined( 'HKDEV_ELEMENTS_GITHUB_REPO' ) ) {
	define( 'HKDEV_ELEMENTS_GITHUB_REPO', 'hayatullabd/hkdev-shop-elements' );
}

/**
 * Safe mode. Define HKDEV_ELEMENTS_SAFE_MODE as true in wp-config.php to stop
 * the plugin from registering its Elementor widgets — useful to check whether a
 * broken panel/editor comes from this plugin.
 */
if ( ! defined( 'HKDEV_ELEMENTS_SAFE_MODE' ) ) {
	define( 'HKDEV_ELEMENTS_SAFE_MODE', false );
}

// Fatal-error log (diagnostics). Loaded first so it also captures a crash in the
// engine / widget bootstrap below. Written to
// wp-content/uploads/hkdev-elements-error.log
require_once HKDEV_ELEMENTS_PATH . 'includes/error-logger.php';


/**
 * Resolve a plugin asset to its minified build when one exists next to the
 * source file (e.g. assets/css/shop.css -> assets/css/shop.min.css). Falls back
 * to the source path so editing a file without rebuilding still works.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string Resolved path relative to the plugin root.
 */
function hkdev_elements_asset_path( $relative_path ) {
	$relative_path = ltrim( $relative_path, '/' );
	$min_path      = preg_replace( '/\.(css|js)$/', '.min.$1', $relative_path );
	if ( $min_path !== $relative_path && file_exists( HKDEV_ELEMENTS_PATH . $min_path ) ) {
		return $min_path;
	}
	return $relative_path;
}

/**
 * Resolve a plugin asset URL, preferring the minified build.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string
 */
function hkdev_elements_asset_url( $relative_path ) {
	return HKDEV_ELEMENTS_URL . hkdev_elements_asset_path( $relative_path );
}

/**
 * Cache-busting asset version (filemtime based) for the resolved asset.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string
 */
function hkdev_elements_asset_ver( $relative_path ) {
	$file  = HKDEV_ELEMENTS_PATH . hkdev_elements_asset_path( $relative_path );
	$mtime = file_exists( $file ) ? filemtime( $file ) : false;
	return $mtime ? (string) $mtime : HKDEV_ELEMENTS_VERSION;
}

/**
 * Sanitize a CSS colour value (hex, rgb()/hsl(), or a named colour).
 *
 * The value is printed into a <style> block on the front end, so anything that
 * is not obviously a colour is rejected outright.
 *
 * @param string $value Raw value.
 * @return string Safe colour, or '' when invalid.
 */
function hkdev_elements_sanitize_css_color( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value ) ) {
		return $value;
	}
	if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $value ) ) {
		return $value;
	}
	if ( preg_match( '/^[a-z]{3,20}$/i', $value ) ) {
		return $value;
	}
	return '';
}

/**
 * Sanitize a CSS font stack (e.g. "'Hind Siliguri', sans-serif").
 *
 * @param string $value Raw value.
 * @return string Safe font stack, or '' when invalid.
 */
function hkdev_elements_sanitize_font_stack( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( ! preg_match( '/^[a-z0-9 ,\'"-]+$/i', $value ) ) {
		return '';
	}
	return $value;
}

/**
 * Whether another provider (the hkdev-shop theme or a plugin) is already
 * shipping the hkdev-shop asset handles. When it is, this plugin defers so
 * CSS/JS are not enqueued twice and JS events are not double-bound.
 *
 * @return bool
 */
function hkdev_elements_theme_provides_assets() {
	return wp_style_is( 'hkdev-shop-style', 'registered' )
		|| wp_script_is( 'hkdev-shop-js', 'registered' )
		|| class_exists( '\HkdevShop\App\Controllers\Shop_Controller' );
}

/**
 * Print every WooCommerce price as a whole number ("100" instead of "100.00").
 *
 * Only the formatting is touched: `wc_price_args` is consumed by wc_price()
 * alone, so stored prices, taxes and rounding keep their full precision.
 * Change the number of decimals with the `hkdev_elements_price_decimals` filter.
 *
 * @param array $args wc_price() arguments.
 * @return array
 */
function hkdev_elements_price_args( $args ) {
	$args['decimals'] = (int) apply_filters( 'hkdev_elements_price_decimals', 0 );

	return $args;
}

/**
 * Require a plugin file when it exists.
 *
 * A failed GitHub update can leave the plugin folder incomplete while it
 * stays active in WordPress. A blind require_once on a missing file fatals
 * the whole site, so every bootstrap include goes through this helper.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return bool True when the file was loaded.
 */
function hkdev_elements_require_file( $relative_path ) {
	$file = HKDEV_ELEMENTS_PATH . ltrim( $relative_path, '/' );
	if ( ! file_exists( $file ) ) {
		return false;
	}

	require_once $file;

	return true;
}

/**
 * Surface an incomplete install without taking the site down.
 *
 * @param string $missing_path Missing path relative to the plugin root.
 * @return void
 */
function hkdev_elements_handle_incomplete_install( $missing_path ) {
	static $handled = false;

	if ( $handled ) {
		return;
	}
	$handled = true;

	hkdev_elements_log_message(
		sprintf(
			'Incomplete install: missing %s. Reinstall hkdev-shop-elements from the latest GitHub release zip.',
			$missing_path
		)
	);

	if ( is_admin() ) {
		add_action(
			'admin_notices',
			static function () use ( $missing_path ) {
				printf(
					'<div class="notice notice-error"><p><strong>%s</strong> %s <code>%s</code>. %s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p></div>',
					esc_html__( 'HKDEV Shop Elements is incomplete.', 'hkdev-shop-elements' ),
					esc_html__( 'A required file is missing:', 'hkdev-shop-elements' ),
					esc_html( $missing_path ),
					esc_html__( 'Please delete the plugin folder and reinstall from the latest release zip, then activate again.', 'hkdev-shop-elements' ),
					esc_url( 'https://github.com/hayatullabd/hkdev-shop-elements/releases/latest/download/hkdev-shop-elements.zip' ),
					esc_html__( 'Download hkdev-shop-elements.zip', 'hkdev-shop-elements' )
				);
			}
		);
	}
}

/**
 * Bootstrap.
 *
 * The engine only needs WooCommerce, so it loads at plugins_loaded. Elementor
 * widget classes extend Elementor\Widget_Base, so they are required lazily
 * inside the "elementor/widgets/register" callback (Elementor v4 initialises
 * its autoloader on plugins_loaded with negative priority, so parsing the
 * widget files earlier would fatal).
 */
function hkdev_elements_boot() {
	if ( ! class_exists( '\WooCommerce' ) ) {
		return;
	}

	// Whole-number pricing across every WooCommerce price output.
	add_filter( 'wc_price_args', __NAMESPACE__ . '\\hkdev_elements_price_args' );

	$modules = [
		[ 'includes/shop-engine.php', static function () {
			Includes\Shop_Engine::instance();
		} ],
		[ 'includes/single-product-engine.php', static function () {
			Includes\Single_Product_Engine::instance();
		} ],
		[ 'includes/header-engine.php', static function () {
			Includes\Header_Engine::instance();
		} ],
		[ 'includes/footer-engine.php', static function () {
			Includes\Footer_Engine::instance();
		} ],
		[ 'includes/cart-engine.php', static function () {
			Includes\Cart_Engine::instance();
		} ],
		[ 'includes/checkout-engine.php', static function () {
			Includes\Checkout_Engine::instance();
		} ],
		[ 'includes/contact-form-engine.php', static function () {
			Includes\Contact_Form_Engine::instance();
		} ],
		[ 'includes/catalog-engine.php', static function () {
			Includes\Catalog_Engine::instance();
		} ],
		[ 'includes/review-engine.php', static function () {
			Includes\Review_Engine::instance()->register_shortcode();
		} ],
		[ 'includes/review-options.php', static function () {
			Includes\Review_Options::instance()->init();
		} ],
		[ 'includes/video-engine.php', static function () {
			Includes\Video_Engine::instance();
		} ],
		[ 'includes/checkout-options.php', static function () {
			Includes\Checkout_Options::instance()->init();
		} ],
		[ 'includes/header-options.php', static function () {
			Includes\Header_Options::instance()->init();
		} ],
		[ 'includes/footer-options.php', static function () {
			Includes\Footer_Options::instance()->init();
		} ],
		[ 'includes/contact-form-options.php', static function () {
			Includes\Contact_Form_Options::instance()->init();
		} ],
		[ 'includes/widget-options.php', static function () {
			Includes\Widget_Options::instance()->init();
		} ],
		[ 'includes/admin-menu.php', static function () {
			Includes\Admin_Menu::instance()->init();
		} ],
		[ 'includes/widget-manager.php', static function () {
			Includes\Widget_Manager::instance()->init();
		} ],
		[ 'includes/auth-engine.php', static function () {
			Includes\Auth_Engine::instance();
		} ],
		[ 'includes/account-engine.php', static function () {
			Includes\Account_Engine::instance();
		} ],
		[ 'includes/tracking-engine.php', static function () {
			Includes\Tracking_Engine::instance();
		} ],
		[ 'includes/404-engine.php', static function () {
			Includes\Page404_Engine::instance();
		} ],
		[ 'includes/blog-engine.php', static function () {
			Includes\Blog_Engine::instance();
		} ],
	];

	foreach ( $modules as $module ) {
		if ( ! hkdev_elements_require_file( $module[0] ) ) {
			hkdev_elements_handle_incomplete_install( $module[0] );
			return;
		}

		$module[1]();
	}

	// Register shortcodes for the new systems.
	add_shortcode( 'hkdev_login', [ Includes\Auth_Engine::instance(), 'render_auth_modal' ] );
	add_shortcode( 'hkdev_my_account', [ Includes\Account_Engine::instance(), 'account_shortcode' ] );
	add_shortcode( 'hkdev_track_order', [ Includes\Tracking_Engine::instance(), 'tracking_shortcode' ] );
	add_shortcode( 'hkdev_404', [ Includes\Page404_Engine::instance(), 'page404_shortcode' ] );
	add_shortcode( 'hkdev_contact_form', [ Includes\Contact_Form_Engine::instance(), 'contact_form_shortcode' ] );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\hkdev_elements_boot', 20 );

/**
 * GitHub update checker.
 *
 * Loaded on admin screens and WP-Cron only, and independently of WooCommerce,
 * so the plugin can always be updated from WP Admin → Plugins.
 *
 * @return void
 */
function hkdev_elements_github_updater() {
	if ( ! is_admin() && ! ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	if ( ! hkdev_elements_require_file( 'includes/github-updater.php' ) ) {
		hkdev_elements_handle_incomplete_install( 'includes/github-updater.php' );
		return;
	}

	new Includes\GitHub_Updater( __FILE__, HKDEV_ELEMENTS_GITHUB_REPO );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\hkdev_elements_github_updater' );

/**
 * Register plugin assets. Uses its own handle prefix (hkdev-elements-*) so it
 * never collides with other themes/plugins.
 */
function hkdev_elements_register_assets() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}

	wp_register_style(
		'hkdev-elements-fontawesome',
		hkdev_elements_asset_url( 'assets/css/fontawesome.min.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/fontawesome.min.css' )
	);

	// Google Fonts — Hind Siliguri brand font, now vendored locally
	// (assets/css/hind-siliguri.css + assets/fonts/*.woff2) so there are no
	// render-blocking @import font requests. Enqueued as a dependency of the
	// widget stylesheets that use the font.
	wp_register_style(
		'hkdev-elements-font',
		hkdev_elements_asset_url( 'assets/css/hind-siliguri.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/hind-siliguri.css' )
	);
	wp_register_style(
		'hkdev-elements-swiper-css',
		hkdev_elements_asset_url( 'assets/css/swiper-bundle.min.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/swiper-bundle.min.css' )
	);
	wp_register_script(
		'hkdev-elements-swiper-js',
		hkdev_elements_asset_url( 'assets/js/swiper-bundle.min.js' ),
		[],
		hkdev_elements_asset_ver( 'assets/js/swiper-bundle.min.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-shop-style',
		hkdev_elements_asset_url( 'assets/css/shop.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/shop.css' )
	);
	wp_register_script(
		'hkdev-elements-shop-js',
		hkdev_elements_asset_url( 'assets/js/shop.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/shop.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-single-product-style',
		hkdev_elements_asset_url( 'assets/css/single-product.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/single-product.css' )
	);
	wp_register_script(
		'hkdev-elements-single-product-js',
		hkdev_elements_asset_url( 'assets/js/single-product.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/single-product.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-cart-style',
		hkdev_elements_asset_url( 'assets/css/cart.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/cart.css' )
	);
	wp_register_script(
		'hkdev-elements-cart-js',
		hkdev_elements_asset_url( 'assets/js/cart.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/cart.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-checkout-style',
		hkdev_elements_asset_url( 'assets/css/checkout.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/checkout.css' )
	);
	wp_register_script(
		'hkdev-elements-checkout-js',
		hkdev_elements_asset_url( 'assets/js/checkout.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/checkout.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-header-style',
		hkdev_elements_asset_url( 'assets/css/header.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/header.css' )
	);
	wp_register_script(
		'hkdev-elements-header-js',
		hkdev_elements_asset_url( 'assets/js/header.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/header.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-footer-style',
		hkdev_elements_asset_url( 'assets/css/footer.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/footer.css' )
	);
	wp_register_script(
		'hkdev-elements-footer-js',
		hkdev_elements_asset_url( 'assets/js/footer.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/footer.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-catalog-style',
		hkdev_elements_asset_url( 'assets/css/catalog.css' ),
		[ 'hkdev-elements-shop-style', 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/catalog.css' )
	);
	wp_register_script(
		'hkdev-elements-catalog-js',
		hkdev_elements_asset_url( 'assets/js/catalog.js' ),
		[ 'jquery', 'hkdev-elements-shop-js' ],
		hkdev_elements_asset_ver( 'assets/js/catalog.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-reviews-style',
		hkdev_elements_asset_url( 'assets/css/reviews.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/reviews.css' )
	);
	wp_register_script(
		'hkdev-elements-reviews-js',
		hkdev_elements_asset_url( 'assets/js/reviews.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/reviews.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-video-style',
		hkdev_elements_asset_url( 'assets/css/video.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/video.css' )
	);
	wp_register_script(
		'hkdev-elements-video-js',
		hkdev_elements_asset_url( 'assets/js/video.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/video.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-hero-style',
		hkdev_elements_asset_url( 'assets/css/hero-slider.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/hero-slider.css' )
	);
	wp_register_style(
		'hkdev-elements-faq-style',
		hkdev_elements_asset_url( 'assets/css/faq.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/faq.css' )
	);
	wp_register_style(
		'hkdev-elements-policy-link-style',
		hkdev_elements_asset_url( 'assets/css/policy-link.css' ),
		[ 'hkdev-elements-font' ],
		hkdev_elements_asset_ver( 'assets/css/policy-link.css' )
	);
	wp_register_script(
		'hkdev-elements-hero-js',
		hkdev_elements_asset_url( 'assets/js/hero-slider.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/hero-slider.js' ),
		true
	);
	wp_localize_script(
		'hkdev-elements-header-js',
		'hkdevHeaderL10n',
		[
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'searchNonce' => wp_create_nonce( 'hkdev_elements_live_search' ),
			'cartNonce'   => wp_create_nonce( 'hkdev_elements_mini_cart' ),
			'action'      => 'hkdev_elements_live_search',
			'cartAction'  => 'hkdev_elements_header_mini_cart',
			'noResults'   => esc_html__( 'No products found for', 'hkdev-shop-elements' ),
			'viewAll'     => esc_html__( 'View all results', 'hkdev-shop-elements' ),
			'itemOne'     => esc_html__( '%d Item', 'hkdev-shop-elements' ),
			'itemMany'    => esc_html__( '%d Items', 'hkdev-shop-elements' ),
		]
	);

	wp_localize_script(
		'hkdev-elements-footer-js',
		'hkdevFooterL10n',
		[
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'subscribeNonce' => wp_create_nonce( 'hkdev_elements_footer_subscribe' ),
			'action'         => 'hkdev_elements_footer_subscribe',
			'invalid'        => esc_html__( 'Please enter a valid email address.', 'hkdev-shop-elements' ),
			'success'        => esc_html__( 'Thanks for subscribing!', 'hkdev-shop-elements' ),
			'error'          => esc_html__( 'Something went wrong. Please try again.', 'hkdev-shop-elements' ),
		]
	);

	wp_localize_script(
		'jquery',
		'hkdev_elements_ajax',
		[
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			// Where the Buy Now checkout modal opens: both, simple, variable
			// or none (redirect to the checkout page instead).
			'checkout_modal_scope' => get_option( 'hkdev_elements_checkout_modal_scope', 'both' ),
			'checkout_url'         => wc_get_checkout_url(),
			// One nonce per operation so a nonce minted for a low-impact action
			// (e.g. product filtering) can never satisfy a higher-impact one
			// (e.g. placing an order).
			'nonces'   => [
				'shop_filter'      => wp_create_nonce( 'hkdev_elements_shop_filter' ),
				'blog_filter'      => wp_create_nonce( 'hkdev_elements_blog_filter' ),
				'add_to_cart'      => wp_create_nonce( 'hkdev_elements_add_to_cart' ),
				'cart_update'      => wp_create_nonce( 'hkdev_elements_cart_update' ),
				'co_modal'         => wp_create_nonce( 'hkdev_elements_co_modal' ),
				'co_update_cart'   => wp_create_nonce( 'hkdev_elements_co_update_cart' ),
				'co_apply_coupon'  => wp_create_nonce( 'hkdev_elements_co_apply_coupon' ),
				'co_remove_coupon' => wp_create_nonce( 'hkdev_elements_co_remove_coupon' ),
			],
		]
	);

	// ---- Auth / Account / Tracking / 404 assets ----
	wp_register_style(
		'hkdev-elements-auth-style',
		hkdev_elements_asset_url( 'assets/css/auth.css' ),
		[ 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/auth.css' )
	);
	wp_register_script(
		'hkdev-elements-auth-js',
		hkdev_elements_asset_url( 'assets/js/auth.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/auth.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-account-style',
		hkdev_elements_asset_url( 'assets/css/account.css' ),
		[ 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/account.css' )
	);
	wp_register_script(
		'hkdev-elements-account-js',
		hkdev_elements_asset_url( 'assets/js/account.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/account.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-tracking-style',
		hkdev_elements_asset_url( 'assets/css/tracking.css' ),
		[ 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/tracking.css' )
	);
	wp_register_script(
		'hkdev-elements-tracking-js',
		hkdev_elements_asset_url( 'assets/js/tracking.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/tracking.js' ),
		true
	);
	wp_register_style(
		'hkdev-elements-404-style',
		hkdev_elements_asset_url( 'assets/css/404.css' ),
		[ 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/404.css' )
	);
	wp_register_style(
		'hkdev-elements-blog-style',
		hkdev_elements_asset_url( 'assets/css/blog.css' ),
		[ 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/blog.css' )
	);
	wp_register_script(
		'hkdev-elements-blog-js',
		hkdev_elements_asset_url( 'assets/js/blog.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/blog.js' ),
		true
	);

}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_register_assets', 5 );

/**
 * Re-queue the plugin stylesheets so they always print after the theme's and
 * Elementor's CSS. Widget styles are enqueued early (Elementor widget
 * dependencies), so a late dequeue + enqueue is the only reliable way to move
 * them to the end of the styles queue. Combined with the `!important` rules in
 * the plugin CSS this keeps theme/Elementor styles from overriding the widgets.
 */
function hkdev_elements_force_style_order() {
	if ( is_admin() ) {
		return;
	}

	$handles = [
		'hkdev-elements-shop-style',
		'hkdev-elements-single-product-style',
		'hkdev-elements-cart-style',
		'hkdev-elements-checkout-style',
		'hkdev-elements-catalog-style',
		'hkdev-elements-auth-style',
		'hkdev-elements-account-style',
		'hkdev-elements-tracking-style',
		'hkdev-elements-404-style',
		'hkdev-elements-header-style',
		'hkdev-elements-footer-style',
		'hkdev-elements-reviews-style',
		'hkdev-elements-video-style',
		'hkdev-elements-hero-style',
		'hkdev-elements-blog-style',
		'hkdev-elements-faq-style',
		'hkdev-elements-policy-link-style',
	];

	foreach ( $handles as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			wp_dequeue_style( $handle );
			wp_enqueue_style( $handle );
		}
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_force_style_order', 999 );

/**
 * Load Font Awesome asynchronously.
 *
 * Font Awesome is a large icon stylesheet (~100 KB) that does not define the
 * page layout, so it must not block the first paint. The stylesheet is turned
 * into a high-priority <link rel="preload"> that promotes itself to a real
 * stylesheet the moment it loads — keeping the icon flash as short as possible
 * — with a <noscript> copy for visitors without JavaScript. Layout-critical
 * stylesheets are deliberately left render-blocking.
 *
 * @param string $tag    Full <link> markup.
 * @param string $handle Style handle.
 * @return string
 */
function hkdev_elements_async_fontawesome( $tag, $handle ) {
	if ( is_admin() || 'hkdev-elements-fontawesome' !== $handle ) {
		return $tag;
	}
	if ( false === strpos( $tag, "rel='stylesheet'" ) ) {
		return $tag;
	}

	$async = str_replace(
		"rel='stylesheet'",
		"rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet';\"",
		$tag
	);

	return $async . '<noscript>' . $tag . '</noscript>';
}
add_filter( 'style_loader_tag', __NAMESPACE__ . '\\hkdev_elements_async_fontawesome', 10, 2 );

/**
 * Preload the primary body font.
 *
 * The brand font is requested from inside a render-blocking stylesheet, which
 * gives it a low fetch priority. Hinting it high lets the largest text paint
 * with the real font sooner. Only emitted when the font stylesheet is actually
 * enqueued, so pages that do not use it pay nothing.
 *
 * @return void
 */
function hkdev_elements_preload_primary_font() {
	if ( is_admin() || ! wp_style_is( 'hkdev-elements-font', 'enqueued' ) ) {
		return;
	}

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
		esc_url( hkdev_elements_asset_url( 'assets/fonts/hind-siliguri-400-latin.woff2' ) )
	);
}
add_action( 'wp_head', __NAMESPACE__ . '\\hkdev_elements_preload_primary_font', 1 );

/**
 * Enqueue plugin shop assets when no other provider (theme/plugin) already
 * ships the hkdev-shop grid assets. Runs late (priority 30) so handles
 * registered by the hkdev-shop theme at default priority are already known.
 */
function hkdev_elements_enqueue_assets() {
	if ( ! function_exists( 'is_woocommerce' ) || is_admin() ) {
		return;
	}

	// The catalog module (search / filter / sort) is plugin-only, so it always
	// loads on the WooCommerce Shop and product archives.
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_style( 'hkdev-elements-catalog-style' );
		wp_enqueue_script( 'hkdev-elements-catalog-js' );
	}

	// Auth modal loads everywhere (login/signup button can appear on any page).
	if ( ! is_user_logged_in() ) {
		wp_enqueue_style( 'hkdev-elements-auth-style' );
		wp_enqueue_script( 'hkdev-elements-auth-js' );
	}

	// Account page assets.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		wp_enqueue_style( 'hkdev-elements-account-style' );
		wp_enqueue_script( 'hkdev-elements-account-js' );
	}

	if ( hkdev_elements_theme_provides_assets() ) {
		return;
	}

	wp_enqueue_style( 'hkdev-elements-shop-style' );
	wp_enqueue_script( 'hkdev-elements-shop-js' );
	wp_enqueue_style( 'hkdev-elements-swiper-css' );
	wp_enqueue_script( 'hkdev-elements-swiper-js' );
	wp_enqueue_style( 'hkdev-elements-fontawesome' );

	// The shop widget's "Buy Now" opens the checkout form inside a modal, so
	// the checkout runtime must be available on shop pages too. Skip the JS
	// when another provider already ships the hkdev-shop checkout script.
	wp_enqueue_style( 'hkdev-elements-checkout-style' );

	// Select2 powers the searchable state / country dropdowns. WooCommerce
	// registers it as "wc-select2" (script) and "select2" (style).
	if ( wp_script_is( 'wc-select2', 'registered' ) ) {
		wp_enqueue_script( 'wc-select2' );
	} elseif ( wp_script_is( 'select2', 'registered' ) ) {
		wp_enqueue_script( 'select2' );
	}
	if ( wp_style_is( 'select2', 'registered' ) ) {
		wp_enqueue_style( 'select2' );
	}

	if ( ! wp_script_is( 'hkdev-checkout-js', 'registered' ) ) {
		wp_enqueue_script( 'hkdev-elements-checkout-js' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_assets', 30 );

/**
 * Whether a post contains a given shortcode in post_content or Elementor data.
 *
 * @param \WP_Post|null $post      Post object.
 * @param string        $shortcode Shortcode tag without brackets.
 * @return bool
 */
function hkdev_elements_page_has_shortcode( $post, $shortcode ) {
	if ( ! is_a( $post, 'WP_Post' ) ) {
		return false;
	}

	if ( has_shortcode( $post->post_content, $shortcode ) ) {
		return true;
	}

	$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
	if ( is_string( $elementor_data ) && '' !== $elementor_data ) {
		return false !== strpos( $elementor_data, '[' . $shortcode );
	}

	return false;
}

/**
 * Enqueue tracking and 404 assets on pages that use the shortcodes.
 * Loads on all pages since shortcodes can be used anywhere.
 */
function hkdev_elements_enqueue_tracking_404_assets() {
	if ( ! function_exists( 'is_woocommerce' ) || is_admin() ) {
		return;
	}

	global $post;
	if ( hkdev_elements_page_has_shortcode( $post, 'hkdev_track_order' ) ) {
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_style( 'hkdev-elements-tracking-style' );
		wp_enqueue_script( 'hkdev-elements-tracking-js' );
	}
	if ( hkdev_elements_page_has_shortcode( $post, 'hkdev_404' ) ) {
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_style( 'hkdev-elements-404-style' );
	}
	if ( hkdev_elements_page_has_shortcode( $post, 'hkdev_my_account' ) ) {
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		if ( is_user_logged_in() ) {
			wp_enqueue_style( 'hkdev-elements-account-style' );
			wp_enqueue_script( 'hkdev-elements-account-js' );
		} else {
			wp_enqueue_style( 'hkdev-elements-auth-style' );
			wp_enqueue_script( 'hkdev-elements-auth-js' );
		}
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_tracking_404_assets', 20 );

/**
 * Enqueue the plugin checkout assets on the WooCommerce checkout page.
 *
 * Makes the checkout self-sufficient on ANY theme: the plugin always loads
 * its own CSS (consistent field design + fallbacks) so the custom checkout
 * looks the same regardless of the active theme. The plugin JS (cart /
 * coupon / place-order AJAX) is only loaded when no other provider already
 * ships the hkdev-shop checkout JS, so DOM events are never bound twice.
 *
 * Runs late (priority 30) so handles registered by themes at default
 * priority are already known.
 */
function hkdev_elements_enqueue_checkout_assets() {
	if ( ! function_exists( 'is_checkout' ) || is_admin() ) {
		return;
	}

	$is_checkout = is_checkout() || ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) );
	if ( ! $is_checkout ) {
		return;
	}

	wp_enqueue_style( 'hkdev-elements-fontawesome' );
	wp_enqueue_style( 'hkdev-elements-checkout-style' );

	// Another provider already ships the checkout JS – skip ours to avoid
	// double-bound AJAX handlers.
	if ( wp_script_is( 'hkdev-checkout-js', 'registered' ) ) {
		return;
	}

	wp_enqueue_script( 'hkdev-elements-checkout-js' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_checkout_assets', 30 );
