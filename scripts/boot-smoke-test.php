<?php
/**
 * Minimal WordPress stub to smoke-test plugin bootstrap without fatals.
 */

define( 'ABSPATH', __DIR__ . '/../' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'HKDEV_ELEMENTS_SAFE_MODE', true );

$GLOBALS['wp_filter'] = [];
$GLOBALS['wp_actions'] = [];
$GLOBALS['shortcode_tags'] = [];
$GLOBALS['registered_styles'] = [];
$GLOBALS['registered_scripts'] = [];
$GLOBALS['enqueued_styles'] = [];
$GLOBALS['enqueued_scripts'] = [];

if ( ! function_exists( 'add_action' ) ) {
function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wp_filter'][ $tag ][] = [ $callback, $priority, $accepted_args ];
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	add_action( $tag, $callback, $priority, $accepted_args );
}

function remove_action( $tag, $callback, $priority = 10 ) {
	// no-op for smoke test.
}

function remove_filter( $tag, $callback, $priority = 10 ) {
	// no-op for smoke test.
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['shortcode_tags'][ $tag ] = $callback;
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function plugin_dir_url( $file ) {
	return 'http://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function trailingslashit( $string ) {
	return rtrim( $string, '/\\' ) . '/';
}

function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}

function wp_get_upload_dir() {
	return [ 'basedir' => WP_CONTENT_DIR . '/uploads' ];
}

function apply_filters( $tag, $value ) {
	return $value;
}

function get_option( $name, $default = false ) {
	return $default;
}

function admin_url( $path = '' ) {
	return 'http://example.test/wp-admin/' . ltrim( $path, '/' );
}

function wp_create_nonce( $action ) {
	return 'nonce-' . $action;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function esc_attr__( $text, $domain = 'default' ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return $url;
}

function __() {
	return func_get_args()[0] ?? '';
}

function wp_style_is( $handle, $status = 'registered' ) {
	if ( 'registered' === $status ) {
		return isset( $GLOBALS['registered_styles'][ $handle ] );
	}
	if ( 'enqueued' === $status ) {
		return isset( $GLOBALS['enqueued_styles'][ $handle ] );
	}
	return false;
}

function wp_script_is( $handle, $status = 'registered' ) {
	if ( 'registered' === $status ) {
		return isset( $GLOBALS['registered_scripts'][ $handle ] );
	}
	if ( 'enqueued' === $status ) {
		return isset( $GLOBALS['enqueued_scripts'][ $handle ] );
	}
	return false;
}

function wp_register_style( $handle, $src, $deps = [], $ver = false ) {
	$GLOBALS['registered_styles'][ $handle ] = compact( 'handle', 'src', 'deps', 'ver' );
}

function wp_register_script( $handle, $src, $deps = [], $ver = false, $in_footer = false ) {
	$GLOBALS['registered_scripts'][ $handle ] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
}

function wp_enqueue_style( $handle ) {
	$GLOBALS['enqueued_styles'][ $handle ] = true;
}

function wp_enqueue_script( $handle ) {
	$GLOBALS['enqueued_scripts'][ $handle ] = true;
}

function wp_dequeue_style( $handle ) {
	unset( $GLOBALS['enqueued_styles'][ $handle ] );
}

function wp_localize_script( $handle, $name, $data ) {
	// no-op.
}

function wc_get_checkout_url() {
	return 'http://example.test/checkout/';
}

function is_admin() {
	return false;
}

function is_user_logged_in() {
	return false;
}

function has_shortcode( $content, $tag ) {
	return false !== strpos( (string) $content, '[' . $tag );
}

function get_post_meta( $post_id, $key, $single = false ) {
	return '';
}

} // end function_exists guard block.

class WooCommerce {}

require __DIR__ . '/../hkdev-shop-elements.php';

\HkdevShopElements\hkdev_elements_boot();

if ( class_exists( '\HkdevShopElements\Includes\Widget_Options' ) ) {
	$states   = \HkdevShopElements\Includes\Widget_Options::instance()->get_widget_states();
	$registry = \HkdevShopElements\Includes\Widget_Manager::get_widget_registry();
	echo 'Boot OK. Widgets: ' . count( $states ) . ' registry groups: ' . count( $registry ) . PHP_EOL;
} else {
	echo 'Boot aborted safely (incomplete install simulation).' . PHP_EOL;
}
