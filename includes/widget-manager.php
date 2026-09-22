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

use Elementor\Controls_Manager;

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
		add_action( 'elementor/element/after_section_end', [ $this, 'register_global_finetune_controls' ], 20, 3 );
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

		// Keep HKDEV widgets first in the Elementor panel by moving this
		// category to the top after all categories are registered.
		if ( method_exists( $elements_manager, 'get_categories' ) ) {
			$categories = $elements_manager->get_categories();
			if ( is_array( $categories ) && isset( $categories['hkdev-shop-elements'] ) ) {
				$ordered = [ 'hkdev-shop-elements' => $categories['hkdev-shop-elements'] ];
				foreach ( $categories as $key => $value ) {
					if ( 'hkdev-shop-elements' === $key ) {
						continue;
					}
					$ordered[ $key ] = $value;
				}

				try {
					$ref = new \ReflectionObject( $elements_manager );
					if ( $ref->hasProperty( 'categories' ) ) {
						$prop = $ref->getProperty( 'categories' );
						$prop->setAccessible( true );
						$prop->setValue( $elements_manager, $ordered );
					}
				} catch ( \Throwable $e ) {
					\HkdevShopElements\hkdev_elements_log_message(
						sprintf( 'Widget category reorder failed: %s (%s:%d)', $e->getMessage(), $e->getFile(), $e->getLine() )
					);
				}
			}
		}
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
		// Safe mode: skip every widget so a problem in the controls cannot take
		// the Elementor panel down while it is being diagnosed.
		if ( defined( 'HKDEV_ELEMENTS_SAFE_MODE' ) && HKDEV_ELEMENTS_SAFE_MODE ) {
			return;
		}

		$includes = [
			// Shared control helpers / base widgets.
			'heading-controls-trait.php',
			'product-controls-trait.php',
			'style-controls-trait.php',
			'policy-link-base-widget.php',

			// Commerce widgets.
			'shop-widget.php',
			'single-product-widget.php',
			'related-widget.php',
			'category-carousel-widget.php',
			'catalog-widget.php',
			'cart-widget.php',
			'checkout-widget.php',

			// Content widgets.
			'section-heading-widget.php',
			'hero-slider-widget.php',
			'reviews-widget.php',
			'video-widget.php',
			'faq-widget.php',
			'blog-widget.php',

			// Site builder widgets.
			'header-widget.php',
			'footer-widget.php',
			'contact-form-widget.php',

			// Utility widgets.
			'account-widget.php',
			'tracking-widget.php',
			'404-widget.php',

			// Policy / company page-ready widgets.
			'privacy-policy-widget.php',
			'terms-conditions-widget.php',
			'careers-widget.php',
			'company-information-widget.php',
			'about-us-widget.php',
		];
		foreach ( $includes as $include_file ) {
			require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/' . $include_file;
		}

		$widgets = [
			// Commerce.
			Widgets\Shop_Widget::class,
			Widgets\Single_Product_Widget::class,
			Widgets\Related_Widget::class,
			Widgets\Category_Carousel_Widget::class,
			Widgets\Catalog_Widget::class,
			Widgets\Cart_Widget::class,
			Widgets\Checkout_Widget::class,

			// Content / conversion.
			Widgets\Section_Heading_Widget::class,
			Widgets\Hero_Slider_Widget::class,
			Widgets\Reviews_Widget::class,
			Widgets\Video_Widget::class,
			Widgets\FAQ_Widget::class,
			Widgets\Blog_Widget::class,

			// Site-wide blocks.
			Widgets\Header_Widget::class,
			Widgets\Footer_Widget::class,
			Widgets\Contact_Form_Widget::class,

			// Utility.
			Widgets\Account_Widget::class,
			Widgets\Tracking_Widget::class,
			Widgets\Page404_Widget::class,

			// Policy / company (page-ready).
			Widgets\Privacy_Policy_Widget::class,
			Widgets\Terms_Conditions_Widget::class,
			Widgets\Careers_Widget::class,
			Widgets\Company_Information_Widget::class,
			Widgets\About_Us_Widget::class,
		];

		foreach ( $widgets as $widget_class ) {
			if ( ! class_exists( $widget_class ) ) {
				continue;
			}

			// A single broken widget must never take down the editor / admin.
			try {
				$widgets_manager->register( new $widget_class() );
			} catch ( \Throwable $e ) {
				\HkdevShopElements\hkdev_elements_log_message(
					sprintf( 'Widget %s failed to register: %s (%s:%d)', $widget_class, $e->getMessage(), $e->getFile(), $e->getLine() )
				);
			}
		}
	}

	/**
	 * Inject a global fine-tune style section into every HKDEV widget so old and
	 * new widgets can be pixel-perfect configured from Elementor.
	 *
	 * @param \Elementor\Element_Base $element Elementor element.
	 * @param string                  $section_id Ended section id.
	 * @param array                   $args Section args.
	 * @return void
	 */
	public function register_global_finetune_controls( $element, $section_id, $args ) {
		if ( 'section_advanced' !== $section_id ) {
			return;
		}

		if ( ! is_object( $element ) || ! method_exists( $element, 'get_categories' ) ) {
			return;
		}

		$cats = $element->get_categories();
		if ( ! is_array( $cats ) || ! in_array( 'hkdev-shop-elements', $cats, true ) ) {
			return;
		}

		$element->start_controls_section(
			'hkdev_global_finetune',
			[
				'label' => esc_html__( 'HKDEV Fine Tune', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'hkdev_ft_text_align',
			[
				'label'     => esc_html__( 'Text Align', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [ 'title' => esc_html__( 'Left', 'hkdev-shop-elements' ), 'icon' => 'eicon-text-align-left' ],
					'center' => [ 'title' => esc_html__( 'Center', 'hkdev-shop-elements' ), 'icon' => 'eicon-text-align-center' ],
					'right'  => [ 'title' => esc_html__( 'Right', 'hkdev-shop-elements' ), 'icon' => 'eicon-text-align-right' ],
				],
				'toggle'    => false,
				'selectors' => [
					'{{WRAPPER}} .elementor-widget-container' => 'text-align: {{VALUE}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_width',
			[
				'label'      => esc_html__( 'Widget Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'range'      => [
					'%'  => [ 'min' => 10, 'max' => 100 ],
					'px' => [ 'min' => 120, 'max' => 1920 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'width: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_max_width',
			[
				'label'      => esc_html__( 'Max Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [ 'min' => 120, 'max' => 2200 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'max-width: {{SIZE}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_bg',
			[
				'label'     => esc_html__( 'Background', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-widget-container' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_border_color',
			[
				'label'     => esc_html__( 'Border Color', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-widget-container' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'border-style: solid !important; border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_padding',
			[
				'label'      => esc_html__( 'Padding', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_responsive_control(
			'hkdev_ft_margin',
			[
				'label'      => esc_html__( 'Margin', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .elementor-widget-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_shadow',
			[
				'label'       => esc_html__( 'Box Shadow', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '0 10px 30px rgba(0,0,0,.12)',
				'selectors'   => [
					'{{WRAPPER}} .elementor-widget-container' => 'box-shadow: {{VALUE}} !important;',
				],
			]
		);

		$element->end_controls_section();
	}
}
