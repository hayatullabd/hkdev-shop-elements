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
						'title'       => esc_html__( 'Shop Grid', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Product grid with category tabs, filters and buy now.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-products',
						'file'        => 'shop-widget.php',
						'class'       => Widgets\ShopWidget::class,
					],
					'hkdev_shop_carousel' => [
						'title'       => esc_html__( 'Shop Carousel', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Product carousel/slider with category tabs and controls.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-slider-push',
						'file'        => 'shop-carousel-widget.php',
						'class'       => Widgets\ShopCarouselWidget::class,
					],
					'hkdev_single_product' => [
						'title'       => esc_html__( 'Single Product', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Full product page layout with gallery and buy buttons.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-single-product',
						'file'        => 'single-product-widget.php',
						'class'       => Widgets\SingleProductWidget::class,
					],
					'hkdev_related_products' => [
						'title'       => esc_html__( 'Related Products', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Related / upsell product carousel.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-product-related',
						'file'        => 'related-widget.php',
						'class'       => Widgets\RelatedWidget::class,
					],
					'hkdev_category_grid' => [
						'title'       => esc_html__( 'Category Grid', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'WooCommerce product category grid.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-gallery-grid',
						'file'        => 'category-grid-widget.php',
						'class'       => Widgets\CategoryGridWidget::class,
					],
					'hkdev_category_carousel' => [
						'title'       => esc_html__( 'Category Carousel', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'WooCommerce product category carousel / slider.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-gallery-grid',
						'file'        => 'category-carousel-widget.php',
						'class'       => Widgets\CategoryCarouselWidget::class,
					],
					'hkdev_catalog' => [
						'title'       => esc_html__( 'Catalog (Search + Filter)', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'AJAX catalog with search, sort and filters.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-filter',
						'file'        => 'catalog-widget.php',
						'class'       => Widgets\CatalogWidget::class,
					],
					'hkdev_cart' => [
						'title'       => esc_html__( 'Cart', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom AJAX shopping cart page.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-cart',
						'file'        => 'cart-widget.php',
						'class'       => Widgets\CartWidget::class,
					],
					'hkdev_checkout' => [
						'title'       => esc_html__( 'Checkout', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom one-page checkout form.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-checkout',
						'file'        => 'checkout-widget.php',
						'class'       => Widgets\CheckoutWidget::class,
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
						'class'       => Widgets\SectionHeadingWidget::class,
					],
					'hkdev_hero_slider' => [
						'title'       => esc_html__( 'Hero Slider', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Promotional hero banner slider.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-slider-push',
						'file'        => 'hero-slider-widget.php',
						'class'       => Widgets\HeroSliderWidget::class,
					],
					'hkdev_customer_reviews' => [
						'title'       => esc_html__( 'Customer Reviews', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Customer review carousel / grid.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-testimonial',
						'file'        => 'reviews-widget.php',
						'class'       => Widgets\ReviewsWidget::class,
					],
					'hkdev_video_embed' => [
						'title'       => esc_html__( 'Video Embed', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'YouTube / Vimeo / direct video embed.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-youtube',
						'file'        => 'video-widget.php',
						'class'       => Widgets\VideoWidget::class,
					],
					'hkdev_faq' => [
						'title'       => esc_html__( 'FAQ', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Accordion FAQ block for any page.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-help-o',
						'file'        => 'faq-widget.php',
						'class'       => Widgets\FaqWidget::class,
					],
					'hkdev_blog' => [
						'title'       => esc_html__( 'Blog', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Blog grid / carousel with filters.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-post-list',
						'file'        => 'blog-widget.php',
						'class'       => Widgets\BlogWidget::class,
					],
				],
			],
			'site_builder' => [
				'label' => esc_html__( 'Site Builder', 'hkdev-shop-elements' ),
				'icon'  => 'dashicons-admin-home',
				'widgets' => [
					'hkdev_header' => [
						'title'       => esc_html__( 'Header (Full)', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Full WooCommerce header with upper, main and bottom rows.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-header',
						'file'        => 'header-widget.php',
						'class'       => Widgets\HeaderWidget::class,
					],
					'hkdev_header_upper' => [
						'title'       => esc_html__( 'Header Upper', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Top announcement / contact / social bar only.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-site-identity',
						'file'        => 'header-upper-widget.php',
						'class'       => Widgets\HeaderUpperWidget::class,
					],
					'hkdev_header_main' => [
						'title'       => esc_html__( 'Header Main', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Logo, search, cart and account row only.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-header',
						'file'        => 'header-main-widget.php',
						'class'       => Widgets\HeaderMainWidget::class,
					],
					'hkdev_header_bottom' => [
						'title'       => esc_html__( 'Header Bottom', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Navigation / category menu bar only.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-nav-menu',
						'file'        => 'header-bottom-widget.php',
						'class'       => Widgets\HeaderBottomWidget::class,
					],
					'hkdev_footer' => [
						'title'       => esc_html__( 'Footer', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Site footer with newsletter and links.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-footer',
						'file'        => 'footer-widget.php',
						'class'       => Widgets\FooterWidget::class,
					],
					'hkdev_contact_form' => [
						'title'       => esc_html__( 'Contact Form', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Contact form with info sidebar.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-form-horizontal',
						'file'        => 'contact-form-widget.php',
						'class'       => Widgets\ContactFormWidget::class,
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
						'class'       => Widgets\AccountWidget::class,
					],
					'hkdev_tracking' => [
						'title'       => esc_html__( 'Order Tracking', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Track order by ID and phone.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-search',
						'file'        => 'tracking-widget.php',
						'class'       => Widgets\TrackingWidget::class,
					],
					'hkdev_404_page' => [
						'title'       => esc_html__( '404 Page', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Custom 404 page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-warning',
						'file'        => '404-widget.php',
						'class'       => Widgets\Page404Widget::class,
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
						'class'       => Widgets\PrivacyPolicyWidget::class,
						'policy_base' => true,
					],
					'hkdev_terms_conditions_link' => [
						'title'       => esc_html__( 'Terms & Conditions', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made terms page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'terms-conditions-widget.php',
						'class'       => Widgets\TermsConditionsWidget::class,
						'policy_base' => true,
					],
					'hkdev_careers_link' => [
						'title'       => esc_html__( 'Careers', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made careers page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'careers-widget.php',
						'class'       => Widgets\CareersWidget::class,
						'policy_base' => true,
					],
					'hkdev_company_information_link' => [
						'title'       => esc_html__( 'Company Information', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made company info page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'company-information-widget.php',
						'class'       => Widgets\CompanyInformationWidget::class,
						'policy_base' => true,
					],
					'hkdev_about_us_link' => [
						'title'       => esc_html__( 'About Us', 'hkdev-shop-elements' ),
						'description' => esc_html__( 'Ready-made about us page block.', 'hkdev-shop-elements' ),
						'icon'        => 'eicon-document-file',
						'file'        => 'about-us-widget.php',
						'class'       => Widgets\AboutUsWidget::class,
						'policy_base' => true,
					],
				],
			],
		];
	}

	/**
	 * Load the shared policy page widget base class once.
	 *
	 * @return void
	 */
	private static function require_policy_link_base_widget() {
		if ( class_exists( Widgets\PolicyLinkBaseWidget::class ) || class_exists( Widgets\Policy_Link_Base_Widget::class ) ) {
			return;
		}

		require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/policy-link-base-widget.php';
		self::register_canonical_widget_alias( Widgets\Policy_Link_Base_Widget::class );
	}

	/**
	 * Canonical-to-legacy widget class alias map.
	 *
	 * @return array<string,string>
	 */
	private static function get_widget_class_alias_map() {
		return [
			Widgets\ShopWidget::class => Widgets\Shop_Widget::class,
			Widgets\ShopCarouselWidget::class => Widgets\Shop_Carousel_Widget::class,
			Widgets\SingleProductWidget::class => Widgets\Single_Product_Widget::class,
			Widgets\RelatedWidget::class => Widgets\Related_Widget::class,
			Widgets\CategoryGridWidget::class => Widgets\Category_Grid_Widget::class,
			Widgets\CategoryCarouselWidget::class => Widgets\Category_Carousel_Widget::class,
			Widgets\CatalogWidget::class => Widgets\Catalog_Widget::class,
			Widgets\CartWidget::class => Widgets\Cart_Widget::class,
			Widgets\CheckoutWidget::class => Widgets\Checkout_Widget::class,
			Widgets\SectionHeadingWidget::class => Widgets\Section_Heading_Widget::class,
			Widgets\HeroSliderWidget::class => Widgets\Hero_Slider_Widget::class,
			Widgets\ReviewsWidget::class => Widgets\Reviews_Widget::class,
			Widgets\VideoWidget::class => Widgets\Video_Widget::class,
			Widgets\FaqWidget::class => Widgets\FAQ_Widget::class,
			Widgets\BlogWidget::class => Widgets\Blog_Widget::class,
			Widgets\HeaderWidget::class => Widgets\Header_Widget::class,
			Widgets\HeaderUpperWidget::class => Widgets\Header_Upper_Widget::class,
			Widgets\HeaderMainWidget::class => Widgets\Header_Main_Widget::class,
			Widgets\HeaderBottomWidget::class => Widgets\Header_Bottom_Widget::class,
			Widgets\FooterWidget::class => Widgets\Footer_Widget::class,
			Widgets\ContactFormWidget::class => Widgets\Contact_Form_Widget::class,
			Widgets\AccountWidget::class => Widgets\Account_Widget::class,
			Widgets\TrackingWidget::class => Widgets\Tracking_Widget::class,
			Widgets\Page404Widget::class => Widgets\Page404_Widget::class,
			Widgets\PrivacyPolicyWidget::class => Widgets\Privacy_Policy_Widget::class,
			Widgets\TermsConditionsWidget::class => Widgets\Terms_Conditions_Widget::class,
			Widgets\CareersWidget::class => Widgets\Careers_Widget::class,
			Widgets\CompanyInformationWidget::class => Widgets\Company_Information_Widget::class,
			Widgets\AboutUsWidget::class => Widgets\About_Us_Widget::class,
			Widgets\PolicyLinkBaseWidget::class => Widgets\Policy_Link_Base_Widget::class,
		];
	}

	/**
	 * Resolve canonical/legacy pair for any widget class name.
	 *
	 * @param string $class_name Canonical or legacy fully qualified class name.
	 * @return array{canonical:string,legacy:string}
	 */
	private static function resolve_widget_alias_pair( $class_name ) {
		$map = self::get_widget_class_alias_map();

		if ( isset( $map[ $class_name ] ) ) {
			return [
				'canonical' => $class_name,
				'legacy'    => $map[ $class_name ],
			];
		}

		$reverse = array_flip( $map );
		if ( isset( $reverse[ $class_name ] ) ) {
			return [
				'canonical' => $reverse[ $class_name ],
				'legacy'    => $class_name,
			];
		}

		$canonical = self::canonical_widget_class( $class_name );
		$legacy    = self::legacy_widget_class( $class_name );

		return [
			'canonical' => $canonical,
			'legacy'    => $legacy,
		];
	}

	/**
	 * Convert a legacy widget class (underscored) to canonical class naming.
	 *
	 * Example:
	 * - Widgets\Shop_Widget => Widgets\ShopWidget
	 * - Widgets\Policy_Link_Base_Widget => Widgets\PolicyLinkBaseWidget
	 *
	 * @param string $legacy_class Legacy fully qualified class name.
	 * @return string Canonical fully qualified class name.
	 */
	private static function canonical_widget_class( $legacy_class ) {
		$pos = strrpos( $legacy_class, '\\' );
		if ( false === $pos ) {
			return $legacy_class;
		}

		$namespace  = substr( $legacy_class, 0, $pos + 1 );
		$short_name = substr( $legacy_class, $pos + 1 );
		if ( false === strpos( $short_name, '_' ) ) {
			return $legacy_class;
		}

		$parts = explode( '_', $short_name );
		$parts = array_map(
			static function ( $part ) {
				if ( '' === $part ) {
					return '';
				}

				if ( ctype_upper( $part ) ) {
					return ucfirst( strtolower( $part ) );
				}

				return ucfirst( $part );
			},
			$parts
		);

		return $namespace . implode( '', $parts );
	}

	/**
	 * Convert a canonical widget class to legacy underscored naming.
	 *
	 * Example:
	 * - Widgets\ShopWidget => Widgets\Shop_Widget
	 * - Widgets\PolicyLinkBaseWidget => Widgets\Policy_Link_Base_Widget
	 *
	 * @param string $canonical_class Canonical fully qualified class name.
	 * @return string Legacy fully qualified class name.
	 */
	private static function legacy_widget_class( $canonical_class ) {
		$pos = strrpos( $canonical_class, '\\' );
		if ( false === $pos ) {
			return $canonical_class;
		}

		$namespace  = substr( $canonical_class, 0, $pos + 1 );
		$short_name = substr( $canonical_class, $pos + 1 );
		if ( false !== strpos( $short_name, '_' ) ) {
			return $canonical_class;
		}

		$legacy_short = preg_replace( '/(?<=[a-z0-9])([A-Z])/', '_$1', $short_name );

		return $namespace . $legacy_short;
	}

	/**
	 * Register class alias from legacy to canonical widget class.
	 *
	 * @param string $legacy_class Legacy fully qualified class name.
	 * @return string Class to use for registration.
	 */
	private static function register_canonical_widget_alias( $legacy_class ) {
		$pair      = self::resolve_widget_alias_pair( $legacy_class );
		$canonical = $pair['canonical'];
		$legacy    = $pair['legacy'];

		if ( class_exists( $canonical, false ) ) {
			return $canonical;
		}
		if ( class_exists( $legacy ) && ! class_exists( $canonical, false ) ) {
			class_alias( $legacy, $canonical );
		}

		if ( class_exists( $canonical, false ) ) {
			return $canonical;
		}

		return class_exists( $legacy, false ) ? $legacy : $legacy_class;
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

		$options  = \HkdevShopElements\Includes\Admin\WidgetOptions::instance();
		$registry = self::get_widget_registry();
		$widgets  = [];

		foreach ( self::get_shared_includes() as $include_file ) {
			require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/' . $include_file;
		}

		foreach ( $registry as $group ) {
			foreach ( $group['widgets'] as $slug => $meta ) {
				if ( ! $options->is_widget_enabled( $slug ) ) {
					continue;
				}

				// Policy page widgets extend Policy_Link_Base_Widget, so the base
				// file must load before any of their class files are parsed.
				if ( ! empty( $meta['policy_base'] ) ) {
					self::require_policy_link_base_widget();
				}

				require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/' . $meta['file'];
				$widgets[] = self::register_canonical_widget_alias( $meta['class'] );
			}
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

		$element->add_control(
			'hkdev_ft_hover_heading',
			[
				'label'     => esc_html__( 'Hover State', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$element->add_control(
			'hkdev_ft_transition_duration',
			[
				'label'     => esc_html__( 'Hover Transition (s)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [ 'min' => 0, 'max' => 1.5, 'step' => 0.05 ],
				],
				'default'   => [ 'size' => 0.3 ],
				'selectors' => [
					'{{WRAPPER}} .elementor-widget-container' => 'transition: all {{SIZE}}s ease !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_hover_bg',
			[
				'label'     => esc_html__( 'Hover Background', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}:hover .elementor-widget-container' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_hover_border_color',
			[
				'label'     => esc_html__( 'Hover Border Color', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}:hover .elementor-widget-container' => 'border-color: {{VALUE}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_hover_shadow',
			[
				'label'       => esc_html__( 'Hover Shadow', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '0 16px 36px rgba(0,0,0,.18)',
				'selectors'   => [
					'{{WRAPPER}}:hover .elementor-widget-container' => 'box-shadow: {{VALUE}} !important;',
				],
			]
		);

		$element->add_control(
			'hkdev_ft_hover_lift',
			[
				'label'     => esc_html__( 'Hover Lift (px)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [ 'min' => -30, 'max' => 30, 'step' => 1 ],
				],
				'default'   => [ 'size' => 0 ],
				'selectors' => [
					'{{WRAPPER}}:hover .elementor-widget-container' => 'transform: translateY({{SIZE}}px) !important;',
				],
			]
		);

		$element->end_controls_section();
	}
}
