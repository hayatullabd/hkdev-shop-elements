<?php
/**
 * Elementor Widget Manager (HKDEV Shop Elements).
 *
 * Registers the widget category and all custom Elementor widgets exposed by
 * this plugin. Phase 1: Shop Grid + Trending product widgets.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Manager
 */
final class Widget_Manager {

	/**
	 * @var ?Widget_Manager
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Widget_Manager
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
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}

	/**
	 * Register the custom widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'hkdev-shop-elements',
			[
				'title' => esc_html__( 'HKDEV Shop Elements', 'hkdev-shop-elements' ),
				'icon'  => 'eicon-cart-solid',
			]
		);
	}

	/**
	 * Register all plugin widgets.
	 *
	 * Widget class files are required here, not at plugins_loaded: they extend
	 * Elementor\Widget_Base and Elementor v4 only has its autoloader ready by
	 * the time this action fires.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/heading-controls-trait.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/product-controls-trait.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/style-controls-trait.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/shop-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/single-product-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/related-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/section-heading-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/header-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/footer-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/cart-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/checkout-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/category-carousel-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/contact-form-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/catalog-widget.php';

		// Account, Tracking, 404 widgets.
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/account-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/tracking-widget.php';
		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/404-widget.php';

		$widgets = [
			Widgets\Shop_Widget::class,
			Widgets\Single_Product_Widget::class,
			Widgets\Related_Widget::class,
			Widgets\Category_Carousel_Widget::class,
			Widgets\Section_Heading_Widget::class,
			Widgets\Header_Widget::class,
			Widgets\Footer_Widget::class,
			Widgets\Cart_Widget::class,
			Widgets\Checkout_Widget::class,
			Widgets\Contact_Form_Widget::class,
			Widgets\Catalog_Widget::class,
			Widgets\Account_Widget::class,
			Widgets\Tracking_Widget::class,
			Widgets\Page404_Widget::class,
		];

		foreach ( $widgets as $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				$widgets_manager->register( new $widget_class() );
			}
		}
	}
}
