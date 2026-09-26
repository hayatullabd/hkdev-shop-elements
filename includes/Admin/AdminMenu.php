<?php
/**
 * HKDEV Shop Elements — shared WP Admin menu + page header navigation.
 *
 * Hides admin screens for widgets disabled in Widget Manager and prints a
 * consistent cross-page nav under each plugin admin header.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminMenu
 */
final class AdminMenu {

	const PARENT_SLUG = 'hkdev-shop-elements';

	/**
	 * @var ?AdminMenu
	 */
	private static $instance = null;

	/**
	 * @return AdminMenu
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_shared_assets' ], 5 );
		add_action( 'admin_menu', [ $this, 'prune_disabled_submenus' ], 999 );
	}

	/**
	 * Whether an Elementor widget slug is enabled in Widget Manager.
	 *
	 * @param string $widget_slug Widget registry slug.
	 * @return bool
	 */
	public function is_widget_enabled( $widget_slug ) {
		if ( ! class_exists( '\HkdevShopElements\Includes\Admin\WidgetOptions' ) ) {
			return true;
		}

		return \HkdevShopElements\Includes\Admin\WidgetOptions::instance()->is_widget_enabled( $widget_slug );
	}

	/**
	 * All HKDEV admin screens (page= query arg).
	 *
	 * @return string[]
	 */
	public function get_admin_page_slugs() {
		return [
			\HkdevShopElements\Includes\Admin\CheckoutOptions::MENU_SLUG,
			\HkdevShopElements\Includes\Admin\HeaderOptions::SETTINGS_SLUG,
			\HkdevShopElements\Includes\Admin\FooterOptions::SETTINGS_SLUG,
			\HkdevShopElements\Includes\Admin\ContactFormOptions::SETTINGS_SLUG,
			\HkdevShopElements\Includes\Admin\ReviewOptions::SETTINGS_SLUG,
			\HkdevShopElements\Includes\Admin\WidgetOptions::SETTINGS_SLUG,
		];
	}

	/**
	 * Cross-page nav items (filtered by Widget Manager).
	 *
	 * @return array<string,array{label:string,icon:string,page:string}>
	 */
	public function get_module_nav_items() {
		$modules = [
			'checkout' => [
				'label' => esc_html__( 'Checkout Fields', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-cart',
				'page'  => \HkdevShopElements\Includes\Admin\CheckoutOptions::MENU_SLUG,
				'gate'  => [],
			],
			'header'   => [
				'label' => esc_html__( 'Header', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-admin-home',
				'page'  => \HkdevShopElements\Includes\Admin\HeaderOptions::SETTINGS_SLUG,
				'gate'  => [],
			],
			'footer'   => [
				'label' => esc_html__( 'Footer', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-admin-multisite',
				'page'  => \HkdevShopElements\Includes\Admin\FooterOptions::SETTINGS_SLUG,
				'gate'  => [],
			],
			'contact'  => [
				'label' => esc_html__( 'Contact Form', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-email-alt',
				'page'  => \HkdevShopElements\Includes\Admin\ContactFormOptions::SETTINGS_SLUG,
				'gate'  => [ 'hkdev_contact_form' ],
			],
			'reviews'  => [
				'label' => esc_html__( 'Customer Reviews', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-star-filled',
				'page'  => \HkdevShopElements\Includes\Admin\ReviewOptions::SETTINGS_SLUG,
				'gate'  => [ 'hkdev_customer_reviews' ],
			],
			'widgets'  => [
				'label' => esc_html__( 'Widget Manager', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-screenoptions',
				'page'  => \HkdevShopElements\Includes\Admin\WidgetOptions::SETTINGS_SLUG,
				'gate'  => [],
			],
		];

		$visible = [];
		foreach ( $modules as $key => $module ) {
			if ( ! $this->is_module_visible( $module['gate'] ) ) {
				continue;
			}
			$visible[ $key ] = $module;
		}

		return $visible;
	}

	/**
	 * @param string[] $widget_slugs Empty = always visible.
	 * @return bool
	 */
	private function is_module_visible( array $widget_slugs ) {
		if ( empty( $widget_slugs ) ) {
			return true;
		}

		foreach ( $widget_slugs as $slug ) {
			if ( $this->is_widget_enabled( $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove submenu entries for disabled modules.
	 *
	 * @return void
	 */
	public function prune_disabled_submenus() {
		if ( ! $this->is_module_visible( [ 'hkdev_contact_form' ] ) ) {
			remove_submenu_page( self::PARENT_SLUG, \HkdevShopElements\Includes\Admin\ContactFormOptions::SETTINGS_SLUG );
			remove_submenu_page( self::PARENT_SLUG, 'edit.php?post_type=' . \HkdevShopElements\Includes\Admin\ContactFormOptions::POST_TYPE );
		}
	}

	/**
	 * Enqueue shared admin styles on every HKDEV settings screen.
	 *
	 * @param string $hook_suffix Current admin hook suffix.
	 * @return void
	 */
	public function enqueue_shared_assets( $hook_suffix ) {
		unset( $hook_suffix );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $page, $this->get_admin_page_slugs(), true ) ) {
			return;
		}

		wp_enqueue_style(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'css/admin.css',
			[],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin.css' )
		);
	}

	/**
	 * Print horizontal navigation between HKDEV admin screens.
	 *
	 * @param string $current_page Current page slug (admin.php?page=…).
	 * @param string $variant      "light" (below green card header) or "builder" (on builder hero pages).
	 * @return void
	 */
	public function render_module_nav( $current_page, $variant = 'light' ) {
		$items = $this->get_module_nav_items();
		if ( count( $items ) < 2 ) {
			return;
		}

		$class = 'hkdev-admin-module-nav';
		if ( 'builder' === $variant ) {
			$class .= ' is-builder';
		}

		echo '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'HKDEV Shop settings', 'hkdev-shop-elements' ) . '">';
		foreach ( $items as $module ) {
			$url      = admin_url( 'admin.php?page=' . $module['page'] );
			$is_active = ( $current_page === $module['page'] );
			printf(
				'<a class="hkdev-admin-module-link%s" href="%s"><span class="dashicons %s" aria-hidden="true"></span><span>%s</span></a>',
				$is_active ? ' is-active' : '',
				esc_url( $url ),
				esc_attr( $module['icon'] ),
				esc_html( $module['label'] )
			);
		}
		echo '</nav>';
	}

	/**
	 * Filter Header Builder sidebar sections to match enabled header features.
	 *
	 * @param array<string,array{0:string,1:string}> $nav_items Section slug => [ dashicon, label ].
	 * @param array                                  $config    Header config.
	 * @return array<string,array{0:string,1:string}>
	 */
	public function filter_header_builder_nav( array $nav_items, array $config ) {
		if ( 'yes' !== ( $config['show_topbar'] ?? 'no' ) ) {
			unset( $nav_items['topbar'] );
		}

		if ( 'yes' !== ( $config['float_cart'] ?? 'no' ) && 'yes' !== ( $config['show_cart'] ?? 'no' ) ) {
			unset( $nav_items['cart'] );
		}

		return $nav_items;
	}

	/**
	 * Filter Footer Builder sidebar sections.
	 *
	 * @param array<string,array{0:string,1:string}> $nav_items Section slug => [ dashicon, label ].
	 * @param array                                  $config    Footer config.
	 * @return array<string,array{0:string,1:string}>
	 */
	public function filter_footer_builder_nav( array $nav_items, array $config ) {
		if ( 'yes' !== ( $config['show_newsletter'] ?? 'no' ) && 'yes' !== ( $config['show_social'] ?? 'no' ) ) {
			unset( $nav_items['newsletter'] );
		}

		if ( 'yes' !== ( $config['show_payments'] ?? 'no' ) && 'yes' !== ( $config['show_backtotop'] ?? 'no' ) ) {
			unset( $nav_items['bottom'] );
		}

		return $nav_items;
	}
}
