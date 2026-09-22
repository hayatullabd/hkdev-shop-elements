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
	 * Shared trait files always loaded before widgets.
	 *
	 * @return string[]
	 */
	public static function get_shared_includes() {
		return [
			'heading-controls-trait.php',
			'product-controls-trait.php',
			'style-controls-trait.php',
		];
	}

	/**
	 * Canonical registry of every HKDEV Elementor widget.
	 *
	 * @return array<string,array{label:string,icon:string,widgets:array<string,array{title:string,description:string,icon:string,file:string,class:string,policy_base?:bool}>}>
	 */
	public static function get_widget_registry() {
		return [
			'commerce' => [
				'label' => esc_html__( 'Commerce', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-cart',
				'widgets' => [
					'hkdev_shop_grid' => [
						'title'       => esc_html__( 'Shop Grid / Carousel', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Product grid, carousel, filters and buy now.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-products',
						'file'        => 'shop-widget.php',
						'class'       => Widgets\Shop_Widget::class,
					],
					'hkdev_single_product' => [
						'title'       => esc_html__( 'Single Product', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Full product page layout with gallery and buy buttons.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-single-product',
						'file'        => 'single-product-widget.php',
						'class'       => Widgets\Single_Product_Widget::class,
					],
					'hkdev_related_products' => [
						'title'       => esc_html__( 'Related Products', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Related / upsell product carousel.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-product-related',
						'file'        => 'related-widget.php',
						'class'       => Widgets\Related_Widget::class,
					],
					'hkdev_category_carousel' => [
						'title'       => esc_html__( 'Category Grid / Carousel', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'WooCommerce category carousel or grid.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-gallery-grid',
						'file'        => 'category-carousel-widget.php',
						'class'       => Widgets\Category_Carousel_Widget::class,
					],
					'hkdev_catalog' => [
						'title'       => esc_html__( 'Catalog (Search + Filter)', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'AJAX catalog with search, sort and filters.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-filter',
						'file'        => 'catalog-widget.php',
						'class'       => Widgets\Catalog_Widget::class,
					],
					'hkdev_cart' => [
						'title'       => esc_html__( 'Cart', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom AJAX shopping cart page.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-cart',
						'file'        => 'cart-widget.php',
						'class'       => Widgets\Cart_Widget::class,
					],
					'hkdev_checkout' => [
						'title'       => esc_html__( 'Checkout', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom one-page checkout form.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-checkout',
						'file'        => 'checkout-widget.php',
						'class'       => Widgets\Checkout_Widget::class,
					],
				],
			],
			'content' => [
				'label' => esc_html__( 'Content & Conversion', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-layout',
				'widgets' => [
					'hkdev_section_heading' => [
						'title'       => esc_html__( 'Section Heading', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Styled section title with subtitle.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-heading',
						'file'        => 'section-heading-widget.php',
						'class'       => Widgets\Section_Heading_Widget::class,
					],
					'hkdev_hero_slider' => [
						'title'       => esc_html__( 'Hero Slider', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Promotional hero banner slider.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-slider-push',
						'file'        => 'hero-slider-widget.php',
						'class'       => Widgets\Hero_Slider_Widget::class,
					],
					'hkdev_customer_reviews' => [
						'title'       => esc_html__( 'Customer Reviews', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Customer review carousel / grid.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-testimonial',
						'file'        => 'reviews-widget.php',
						'class'       => Widgets\Reviews_Widget::class,
					],
					'hkdev_video_embed' => [
						'title'       => esc_html__( 'Video Embed', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'YouTube / Vimeo / direct video embed.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-youtube',
						'file'        => 'video-widget.php',
						'class'       => Widgets\Video_Widget::class,
					],
					'hkdev_faq' => [
						'title'       => esc_html__( 'FAQ', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Accordion FAQ block for any page.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-help-o',
						'file'        => 'faq-widget.php',
						'class'       => Widgets\FAQ_Widget::class,
					],
					'hkdev_blog' => [
						'title'       => esc_html__( 'Blog', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Blog grid / carousel with filters.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-post-list',
						'file'        => 'blog-widget.php',
						'class'       => Widgets\Blog_Widget::class,
					],
				],
			],
			'site_builder' => [
				'label' => esc_html__( 'Site Builder', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-admin-home',
				'widgets' => [
					'hkdev_header' => [
						'title'       => esc_html__( 'Header', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Full WooCommerce header with search and cart.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-header',
						'file'        => 'header-widget.php',
						'class'       => Widgets\Header_Widget::class,
					],
					'hkdev_footer' => [
						'title'       => esc_html__( 'Footer', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Site footer with newsletter and links.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-footer',
						'file'        => 'footer-widget.php',
						'class'       => Widgets\Footer_Widget::class,
					],
					'hkdev_contact_form' => [
						'title'       => esc_html__( 'Contact Form', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Contact form with info sidebar.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-form-horizontal',
						'file'        => 'contact-form-widget.php',
						'class'       => Widgets\Contact_Form_Widget::class,
					],
				],
			],
			'utility' => [
				'label' => esc_html__( 'Utility', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-admin-tools',
				'widgets' => [
					'hkdev_account' => [
						'title'       => esc_html__( 'My Account', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Customer account dashboard.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-person',
						'file'        => 'account-widget.php',
						'class'       => Widgets\Account_Widget::class,
					],
					'hkdev_tracking' => [
						'title'       => esc_html__( 'Order Tracking', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Track order by ID and phone.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-search',
						'file'        => 'tracking-widget.php',
						'class'       => Widgets\Tracking_Widget::class,
					],
					'hkdev_404_page' => [
						'title'       => esc_html__( '404 Page', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom 404 page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-warning',
						'file'        => '404-widget.php',
						'class'       => Widgets\Page404_Widget::class,
					],
				],
			],
			'policy' => [
				'label' => esc_html__( 'Policy & Company Pages', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-media-text',
				'widgets' => [
					'hkdev_privacy_policy_link' => [
						'title'       => esc_html__( 'Privacy Policy', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made privacy policy page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'privacy-policy-widget.php',
						'class'       => Widgets\Privacy_Policy_Widget::class,
						'policy_base' => true,
					],
					'hkdev_terms_conditions_link' => [
						'title'       => esc_html__( 'Terms & Conditions', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made terms page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'terms-conditions-widget.php',
						'class'       => Widgets\Terms_Conditions_Widget::class,
						'policy_base' => true,
					],
					'hkdev_careers_link' => [
						'title'       => esc_html__( 'Careers', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made careers page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'careers-widget.php',
						'class'       => Widgets\Careers_Widget::class,
						'policy_base' => true,
					],
					'hkdev_company_information_link' => [
						'title'       => esc_html__( 'Company Information', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made company info page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'company-information-widget.php',
						'class'       => Widgets\Company_Information_Widget::class,
						'policy_base' => true,
					],
					'hkdev_about_us_link' => [
						'title'       => esc_html__( 'About Us', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made about us page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'about-us-widget.php',
						'class'       => Widgets\About_Us_Widget::class,
						'policy_base' => true,
					],
				],
			],
		];
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

		$options  = Widget_Options::instance();
		$registry = self::get_widget_registry();
		$widgets  = [];

		foreach ( self::get_shared_includes() as $include_file ) {
			require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/' . $include_file;
		}

		$needs_policy_base = false;

		foreach ( $registry as $group ) {
			foreach ( $group['widgets'] as $slug => $meta ) {
				if ( ! $options->is_widget_enabled( $slug ) ) {
					continue;
				}

				if ( ! empty( $meta['policy_base'] ) ) {
					$needs_policy_base = true;
				}

				require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/' . $meta['file'];
				$widgets[] = $meta['class'];
			}
		}

		if ( $needs_policy_base ) {
			require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/policy-link-base-widget.php';
		}

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

		if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
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
