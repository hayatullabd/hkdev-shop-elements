<?php
/**
 * Plugin Name:       HKDEV Shop Elements
 * Description:       Standalone Elementor + WooCommerce widgets (Shop Grid / Carousel, Cart, Checkout, Single Product, Header, Footer, Contact Form). Works with any WordPress theme.
 * Version:           0.4.9
 * Author:            FitForLife
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

define( 'HKDEV_ELEMENTS_VERSION', '0.4.9' );
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

	require_once HKDEV_ELEMENTS_PATH . 'includes/shop-engine.php';
	Includes\Shop_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/single-product-engine.php';
	Includes\Single_Product_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/header-engine.php';
	Includes\Header_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/footer-engine.php';
	Includes\Footer_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/cart-engine.php';
	Includes\Cart_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/checkout-engine.php';
	Includes\Checkout_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/contact-form-engine.php';
	Includes\Contact_Form_Engine::instance();

	require_once HKDEV_ELEMENTS_PATH . 'includes/catalog-engine.php';
	Includes\Catalog_Engine::instance();

	// Wishlist Engine (saved products: card / cart / single product + page).
	require_once HKDEV_ELEMENTS_PATH . 'includes/wishlist-engine.php';
	Includes\Wishlist_Engine::instance();

	// Admin settings (Checkout Fields on/off). Only hooks admin_menu, safe to
	// init unconditionally.
	require_once HKDEV_ELEMENTS_PATH . 'includes/checkout-options.php';
	Includes\Checkout_Options::instance()->init();

	// Admin settings for the site-wide header.
	require_once HKDEV_ELEMENTS_PATH . 'includes/header-options.php';
	Includes\Header_Options::instance()->init();

	// Admin settings for the site-wide footer.
	require_once HKDEV_ELEMENTS_PATH . 'includes/footer-options.php';
	Includes\Footer_Options::instance()->init();

	// Admin settings for the contact form + the submissions inbox submenu.
	require_once HKDEV_ELEMENTS_PATH . 'includes/contact-form-options.php';
	Includes\Contact_Form_Options::instance()->init();

	// Widget manager hooks elementor/* actions. When Elementor is not active
	// those actions never fire, so calling init() unconditionally is safe.
	require_once HKDEV_ELEMENTS_PATH . 'includes/widget-manager.php';
	Includes\Widget_Manager::instance()->init();

	// Auth Engine (Login/Signup modal).
	require_once HKDEV_ELEMENTS_PATH . 'includes/auth-engine.php';
	Includes\Auth_Engine::instance();

	// Account Engine (My Account page).
	require_once HKDEV_ELEMENTS_PATH . 'includes/account-engine.php';
	Includes\Account_Engine::instance();

	// Tracking Engine (Order tracking).
	require_once HKDEV_ELEMENTS_PATH . 'includes/tracking-engine.php';
	Includes\Tracking_Engine::instance();

	// 404 Engine (Custom 404 page).
	require_once HKDEV_ELEMENTS_PATH . 'includes/404-engine.php';
	Includes\Page404_Engine::instance();

	// Register shortcodes for the new systems.
	add_shortcode( 'hkdev_login', [ Includes\Auth_Engine::instance(), 'render_auth_modal' ] );
	add_shortcode( 'hkdev_my_account', [ Includes\Account_Engine::instance(), 'account_shortcode' ] );
	add_shortcode( 'hkdev_track_order', [ Includes\Tracking_Engine::instance(), 'tracking_shortcode' ] );
	add_shortcode( 'hkdev_404', [ Includes\Page404_Engine::instance(), 'page404_shortcode' ] );
	add_shortcode( 'hkdev_wishlist', [ Includes\Wishlist_Engine::instance(), 'wishlist_shortcode' ] );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\hkdev_elements_boot' );

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

	require_once HKDEV_ELEMENTS_PATH . 'includes/github-updater.php';

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
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
		[],
		'6.5.1'
	);
	wp_register_style(
		'hkdev-elements-swiper-css',
		'https://cdn.jsdelivr.net/npm/swiper@11.0.0/swiper-bundle.min.css',
		[],
		'11.0.0'
	);
	wp_register_script(
		'hkdev-elements-swiper-js',
		'https://cdn.jsdelivr.net/npm/swiper@11.0.0/swiper-bundle.min.js',
		[],
		'11.0.0',
		true
	);
	wp_register_style(
		'hkdev-elements-shop-style',
		hkdev_elements_asset_url( 'assets/css/shop.css' ),
		[],
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
		[],
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
		[],
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
		[],
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
			// One nonce per operation so a nonce minted for a low-impact action
			// (e.g. product filtering) can never satisfy a higher-impact one
			// (e.g. placing an order).
			'nonces'   => [
				'shop_filter'      => wp_create_nonce( 'hkdev_elements_shop_filter' ),
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

	// Wishlist button + wishlist page. Depends on the shop card styles.
	wp_register_style(
		'hkdev-elements-wishlist-style',
		hkdev_elements_asset_url( 'assets/css/wishlist.css' ),
		[ 'hkdev-elements-shop-style', 'hkdev-elements-fontawesome' ],
		hkdev_elements_asset_ver( 'assets/css/wishlist.css' )
	);
	wp_register_script(
		'hkdev-elements-wishlist-js',
		hkdev_elements_asset_url( 'assets/js/wishlist.js' ),
		[ 'jquery' ],
		hkdev_elements_asset_ver( 'assets/js/wishlist.js' ),
		true
	);
	wp_localize_script(
		'hkdev-elements-wishlist-js',
		'hkdevWishlistL10n',
		[
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'action'       => 'hkdev_elements_wishlist_toggle',
			'moveAction'   => 'hkdev_elements_wishlist_move',
			'addAllAction' => 'hkdev_elements_wishlist_add_all',
			'nonce'        => wp_create_nonce( 'hkdev_elements_wishlist' ),
			'error'        => esc_html__( 'Something went wrong. Please try again.', 'hkdev-shop-elements' ),
		]
	);

	wp_localize_script(
		'jquery',
		'hkdevElementsAjax',
		[
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		]
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
		'hkdev-elements-wishlist-style',
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
 * Enqueue tracking and 404 assets on pages that use the shortcodes.
 * Loads on all pages since shortcodes can be used anywhere.
 */
function hkdev_elements_enqueue_tracking_404_assets() {
	if ( ! function_exists( 'is_woocommerce' ) || is_admin() ) {
		return;
	}

	global $post;
	if ( is_a( $post, 'WP_Post' ) ) {
		if ( has_shortcode( $post->post_content, 'hkdev_track_order' ) ) {
			wp_enqueue_style( 'hkdev-elements-tracking-style' );
			wp_enqueue_script( 'hkdev-elements-tracking-js' );
		}
		if ( has_shortcode( $post->post_content, 'hkdev_404' ) ) {
			wp_enqueue_style( 'hkdev-elements-404-style' );
		}
		if ( has_shortcode( $post->post_content, 'hkdev_my_account' ) ) {
			wp_enqueue_style( 'hkdev-elements-account-style' );
			wp_enqueue_script( 'hkdev-elements-account-js' );
		}
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_tracking_404_assets', 20 );

/**
 * Enqueue the wishlist assets on the pages that can show a wishlist button:
 * the cart (per-item button) and any page holding the wishlist / account
 * shortcode. Elementor widgets load them through their own dependencies.
 *
 * @return void
 */
function hkdev_elements_enqueue_wishlist_assets() {
	if ( ! function_exists( 'is_woocommerce' ) || is_admin() ) {
		return;
	}

	$needed = function_exists( 'is_cart' ) && is_cart();

	if ( ! $needed ) {
		global $post;
		if ( is_a( $post, 'WP_Post' ) ) {
			$needed = has_shortcode( $post->post_content, 'hkdev_wishlist' )
				|| has_shortcode( $post->post_content, 'hkdev_my_account' );
		}
	}

	if ( ! $needed ) {
		return;
	}

	wp_enqueue_style( 'hkdev-elements-wishlist-style' );
	wp_enqueue_script( 'hkdev-elements-wishlist-js' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\hkdev_elements_enqueue_wishlist_assets', 25 );

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
