<?php
/**
 * Plugin Name:       HKDEV Shop Elements
 * Plugin URI:        https://github.com/hayatullabd/hkdev-shop-elements
 * Description:       Standalone Elementor + WooCommerce widgets (Shop Grid / Carousel, Cart, Checkout, Single Product, Header, Footer, Contact Form). Works with any WordPress theme.
 * Version:           1.1.2
 * Author:            Md Hayatulla Kha
 * Author URI:        https://github.com/hayatullabd
 * Text Domain:       hkdev-shop-elements
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce, elementor
 * WC requires at least: 7.0
 * Update URI:        https://github.com/hayatullabd/hkdev-shop-elements
 */

namespace HkdevShopElements;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HKDEV_ELEMENTS_VERSION', '1.1.2' );
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
require_once HKDEV_ELEMENTS_PATH . 'includes/Support/error-logger.php';


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
	$source_file   = HKDEV_ELEMENTS_PATH . $relative_path;
	$min_file      = HKDEV_ELEMENTS_PATH . $min_path;

	if ( $min_path !== $relative_path && file_exists( $min_file ) ) {
		// Prefer the source file when it was edited after the last minify.
		if ( file_exists( $source_file ) && filemtime( $source_file ) > filemtime( $min_file ) ) {
			return $relative_path;
		}
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
	$relative_path = ltrim( (string) $relative_path, '/' );
	$candidates    = [ $relative_path ];

	// Git on Windows can record includes/admin while bootstrap asks for
	// includes/Admin. Linux hosts are case-sensitive, so try both.
	if ( 0 === strpos( $relative_path, 'includes/Admin/' ) ) {
		$candidates[] = 'includes/admin/' . substr( $relative_path, strlen( 'includes/Admin/' ) );
	} elseif ( 0 === strpos( $relative_path, 'includes/admin/' ) ) {
		$candidates[] = 'includes/Admin/' . substr( $relative_path, strlen( 'includes/admin/' ) );
	}

	if ( preg_match( '#^includes/([^/]+\.php)$#', $relative_path, $matches ) ) {
		$candidates[] = 'includes/Support/' . $matches[1];
	}

	foreach ( $candidates as $candidate ) {
		$file = HKDEV_ELEMENTS_PATH . $candidate;
		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}
	}

	return false;
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
 * Load and initialize a list of modules.
 *
 * Each item must be [ 'relative/path.php', callable ].
 *
 * @param array<int,array{0:string,1:callable}> $modules Module map.
 * @return bool True when all modules are loaded.
 */
function hkdev_elements_bootstrap_modules( array $modules ) {
	foreach ( $modules as $module ) {
		if ( ! hkdev_elements_require_file( $module[0] ) ) {
			hkdev_elements_handle_incomplete_install( $module[0] );
			return false;
		}

		$module[1]();
	}

	return true;
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

	$core_modules = [
		[ 'includes/Core/ColorTheme.php', static function () {
			Includes\Core\ColorTheme::instance()->init();
		} ],
		[ 'includes/Core/ShopEngine.php', static function () {
			Includes\Core\ShopEngine::instance();
		} ],
		[ 'includes/Core/SingleProductEngine.php', static function () {
			Includes\Core\SingleProductEngine::instance();
		} ],
		[ 'includes/Core/HeaderEngine.php', static function () {
			Includes\Core\HeaderEngine::instance();
		} ],
		[ 'includes/Core/FooterEngine.php', static function () {
			Includes\Core\FooterEngine::instance();
		} ],
		[ 'includes/Core/CartEngine.php', static function () {
			Includes\Core\CartEngine::instance();
		} ],
		[ 'includes/Core/CheckoutEngine.php', static function () {
			Includes\Core\CheckoutEngine::instance();
		} ],
		[ 'includes/Core/ContactFormEngine.php', static function () {
			Includes\Core\ContactFormEngine::instance();
		} ],
		[ 'includes/Core/CatalogEngine.php', static function () {
			Includes\Core\CatalogEngine::instance();
		} ],
		[ 'includes/Core/ReviewEngine.php', static function () {
			Includes\Core\ReviewEngine::instance()->register_shortcode();
		} ],
		[ 'includes/Core/VideoEngine.php', static function () {
			Includes\Core\VideoEngine::instance();
		} ],
		[ 'includes/Core/AuthEngine.php', static function () {
			Includes\Core\AuthEngine::instance();
		} ],
		[ 'includes/Core/AccountEngine.php', static function () {
			Includes\Core\AccountEngine::instance();
		} ],
		[ 'includes/Core/TrackingEngine.php', static function () {
			Includes\Core\TrackingEngine::instance();
		} ],
		[ 'includes/Core/Page404Engine.php', static function () {
			Includes\Core\Page404Engine::instance();
		} ],
		[ 'includes/Core/BlogEngine.php', static function () {
			Includes\Core\BlogEngine::instance();
		} ],
	];

	$admin_modules = [
		[ 'includes/Admin/ReviewOptions.php', static function () {
			Includes\Admin\ReviewOptions::instance()->init();
		} ],
		[ 'includes/Admin/CheckoutOptions.php', static function () {
			Includes\Admin\CheckoutOptions::instance()->init();
		} ],
		[ 'includes/Admin/HeaderOptions.php', static function () {
			Includes\Admin\HeaderOptions::instance()->init();
		} ],
		[ 'includes/Admin/FooterOptions.php', static function () {
			Includes\Admin\FooterOptions::instance()->init();
		} ],
		[ 'includes/Admin/ContactFormOptions.php', static function () {
			Includes\Admin\ContactFormOptions::instance()->init();
		} ],
		[ 'includes/Admin/WidgetOptions.php', static function () {
			Includes\Admin\WidgetOptions::instance()->init();
		} ],
		[ 'includes/Admin/ColorThemeOptions.php', static function () {
			Includes\Admin\ColorThemeOptions::instance()->init();
		} ],
		[ 'includes/Admin/AdminMenu.php', static function () {
			Includes\Admin\AdminMenu::instance()->init();
		} ],
		[ 'includes/Admin/PluginRow.php', static function () {
			Includes\Admin\PluginRow::instance()->init();
		} ],
		[ 'includes/Admin/WidgetManager.php', static function () {
			Includes\Admin\WidgetManager::instance()->init();
		} ],
	];

	if ( ! hkdev_elements_bootstrap_modules( $core_modules ) ) {
		return;
	}
	if ( ! hkdev_elements_bootstrap_modules( $admin_modules ) ) {
		return;
	}

	// Register shortcodes for the new systems.
	add_shortcode( 'hkdev_login', [ Includes\Core\AuthEngine::instance(), 'render_auth_modal' ] );
	add_shortcode( 'hkdev_my_account', [ Includes\Core\AccountEngine::instance(), 'account_shortcode' ] );
	add_shortcode( 'hkdev_track_order', [ Includes\Core\TrackingEngine::instance(), 'tracking_shortcode' ] );
	add_shortcode( 'hkdev_404', [ Includes\Core\Page404Engine::instance(), 'page404_shortcode' ] );
	add_shortcode( 'hkdev_contact_form', [ Includes\Core\ContactFormEngine::instance(), 'contact_form_shortcode' ] );
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

	if ( ! hkdev_elements_require_file( 'includes/Support/github-updater.php' ) ) {
		hkdev_elements_handle_incomplete_install( 'includes/Support/github-updater.php' );
		return;
	}

	new Includes\GitHubUpdater( __FILE__, HKDEV_ELEMENTS_GITHUB_REPO );
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

	// Local Bangla font pack (Hind Siliguri, Noto Sans Bengali, Noto Serif
	// Bengali, Tiro Bangla). Enqueued as a dependency of widget styles so every
	// widget can switch fonts without external requests.
	wp_register_style(
		'hkdev-elements-font',
		hkdev_elements_asset_url( 'assets/css/hkdev-bangla-fonts.css' ),
		[],
		hkdev_elements_asset_ver( 'assets/css/hkdev-bangla-fonts.css' )
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
			'assets'   => [
				'checkoutCss' => hkdev_elements_registered_asset_src( 'hkdev-elements-checkout-style', 'style' ),
				'checkoutJs'  => hkdev_elements_registered_asset_src( 'hkdev-elements-checkout-js', 'script' ),
				'select2Css'  => hkdev_elements_registered_asset_src( 'select2', 'style' ),
				'select2Js'   => hkdev_elements_registered_asset_src( wp_script_is( 'wc-select2', 'registered' ) ? 'wc-select2' : 'select2', 'script' ),
				'authCss'     => hkdev_elements_registered_asset_src( 'hkdev-elements-auth-style', 'style' ),
				'authJs'      => hkdev_elements_registered_asset_src( 'hkdev-elements-auth-js', 'script' ),
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
 * Registered style/script URL with cache-busting ver, or empty.
 *
 * @param string $handle Asset handle.
 * @param string $type   style|script.
 * @return string
 */
function hkdev_elements_registered_asset_src( $handle, $type ) {
	$wp = ( 'script' === $type ) ? wp_scripts() : wp_styles();
	if ( ! $wp || empty( $wp->registered[ $handle ] ) ) {
		return '';
	}

	$src = (string) $wp->registered[ $handle ]->src;
	if ( '' === $src ) {
		return '';
	}

	if ( 0 === strpos( $src, '//' ) ) {
		$src = ( is_ssl() ? 'https:' : 'http:' ) . $src;
	} elseif ( 0 !== strpos( $src, 'http' ) ) {
		$src = site_url( $src );
	}

	$ver = $wp->registered[ $handle ]->ver;
	if ( $ver ) {
		$src = add_query_arg( 'ver', $ver, $src );
	}

	return $src;
}

/**
 * Load Font Awesome, the font pack, and below-the-fold widget CSS asynchronously.
 *
 * Header / hero / single-product CSS stay render-blocking because they paint
 * the LCP. Everything else becomes a <link rel="preload"> that promotes itself
 * to a stylesheet on load, with a <noscript> copy.
 *
 * @param string $tag    Full <link> markup.
 * @param string $handle Style handle.
 * @return string
 */
function hkdev_elements_async_fontawesome( $tag, $handle ) {
	$async_handles = [
		'hkdev-elements-fontawesome',
		'hkdev-elements-font',
		'hkdev-elements-footer-style',
		'hkdev-elements-reviews-style',
		'hkdev-elements-video-style',
		'hkdev-elements-faq-style',
		'hkdev-elements-policy-link-style',
		'hkdev-elements-blog-style',
		'hkdev-elements-auth-style',
	];

	if ( is_admin() || ! in_array( $handle, $async_handles, true ) ) {
		return $tag;
	}
	if ( false === strpos( $tag, 'rel=' ) || false === strpos( $tag, 'stylesheet' ) ) {
		return $tag;
	}

	$async = str_replace(
		[ "rel='stylesheet'", 'rel="stylesheet"' ],
		[
			"rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet';\"",
			'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\';"',
		],
		$tag
	);

	return $async . '<noscript>' . $tag . '</noscript>';
}
add_filter( 'style_loader_tag', __NAMESPACE__ . '\\hkdev_elements_async_fontawesome', 10, 2 );

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

	// Auth CSS/JS load on the account page or the first login-modal click.
	// Account page assets.
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		wp_enqueue_style( 'hkdev-elements-account-style' );
		wp_enqueue_script( 'hkdev-elements-account-js' );
	}

	if ( hkdev_elements_theme_provides_assets() ) {
		return;
	}

	// Shop / category / tag archives may render the catalog or a shop
	// shortcode without an Elementor widget. Product pages, home, and
	// content pages load assets only via widget `get_*_depends()`.
	$needs_shop_stack = function_exists( 'is_shop' )
		&& ( is_shop() || is_product_category() || is_product_tag() );

	if ( ! $needs_shop_stack ) {
		return;
	}

	wp_enqueue_style( 'hkdev-elements-shop-style' );
	wp_enqueue_script( 'hkdev-elements-shop-js' );
	wp_enqueue_style( 'hkdev-elements-swiper-css' );
	wp_enqueue_script( 'hkdev-elements-swiper-js' );
	wp_enqueue_style( 'hkdev-elements-fontawesome' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_assets', 30 );

/**
 * Checkout CSS/JS for the Buy Now modal (shop grid / carousel / catalog).
 *
 * @return void
 */
function hkdev_elements_enqueue_buy_now_checkout_assets() {
	wp_enqueue_style( 'hkdev-elements-checkout-style' );

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

/**
 * Load Buy Now checkout assets whenever a shop listing script is present.
 *
 * The checkout popup and its form JS need to be on the page before Buy Now
 * is clicked. Lazy-loading them after add-to-cart left the modal unstyled
 * and checkout.js never bound.
 *
 * @return void
 */
function hkdev_elements_enqueue_buy_now_when_shop_js() {
	if ( is_admin() || ! wp_script_is( 'hkdev-elements-shop-js', 'enqueued' ) ) {
		return;
	}

	hkdev_elements_enqueue_buy_now_checkout_assets();
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_buy_now_when_shop_js', 40 );

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
	if ( hkdev_elements_page_has_shortcode( $post, 'hkdev_login' ) ) {
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_style( 'hkdev-elements-auth-style' );
		wp_enqueue_script( 'hkdev-elements-auth-js' );
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
