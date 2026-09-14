<?php
/**
 * HKDEV Shop Grid Widget (HKDEV Shop Elements plugin).
 *
 * Renders the master product grid with optional category tabs, sorting and
 * AJAX filtering. Self-contained – does not require the hkdev-shop theme.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Shop_Engine;

/**
 * Class Shop_Widget
 */
class Shop_Widget extends Widget_Base {

	use Heading_Controls;
	use Product_Controls;
	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_shop_grid';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Shop Grid / Carousel', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-woocommerce';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'shop', 'grid', 'carousel', 'slider', 'products', 'product slider', 'product carousel', 'category', 'tabs', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-shop-style', 'hkdev-elements-swiper-css', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-shop-js', 'hkdev-elements-swiper-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Query', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => esc_html__( 'Number of Products', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'       => esc_html__( 'Image Size', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'woocommerce_thumbnail',
				'options'     => $this->hkdev_image_size_options(),
				'description' => esc_html__( 'Which product image file is loaded in the card. The visual crop is controlled from Style → Image.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'type',
			[
				'label'   => esc_html__( 'Product Type', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'recent',
				'options' => [
					'recent'       => esc_html__( 'All Products', 'hkdev-shop-elements' ),
					'best_selling' => esc_html__( 'Best Selling', 'hkdev-shop-elements' ),
					'trending'     => esc_html__( 'Trending', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'category',
			[
				'label'          => esc_html__( 'Categories', 'hkdev-shop-elements' ),
				'type'           => Controls_Manager::SELECT2,
				'multiple'       => true,
				'label_block'    => true,
				'default'        => [],
				'options'        => Shop_Engine::term_options( 'product_cat' ),
				'description'    => esc_html__( 'Start typing to search. Leave empty for every category.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'exclude',
			[
				'label'          => esc_html__( 'Exclude Categories', 'hkdev-shop-elements' ),
				'type'           => Controls_Manager::SELECT2,
				'multiple'       => true,
				'label_block'    => true,
				'default'        => [],
				'options'        => Shop_Engine::term_options( 'product_cat' ),
				'description'    => esc_html__( 'Products inside these categories are hidden.', 'hkdev-shop-elements' ),
			]
		);

		$brand_options = Shop_Engine::term_options( 'product_brand' );
		if ( ! empty( $brand_options ) ) {
			$this->add_control(
				'brands',
				[
					'label'       => esc_html__( 'Brands', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'default'     => [],
					'options'     => $brand_options,
					'description' => esc_html__( 'Start typing to search. Leave empty for every brand.', 'hkdev-shop-elements' ),
				]
			);
		}

		$tag_options = Shop_Engine::term_options( 'product_tag' );
		if ( ! empty( $tag_options ) ) {
			$this->add_control(
				'tags',
				[
					'label'       => esc_html__( 'Tags', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'label_block' => true,
					'default'     => [],
					'options'     => $tag_options,
					'description' => esc_html__( 'Show only products tagged with any of the selected tags.', 'hkdev-shop-elements' ),
				]
			);
		}

		$this->add_control(
			'on_sale',
			[
				'label'        => esc_html__( 'Only On Sale', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'default'      => '',
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'featured',
			[
				'label'        => esc_html__( 'Only Featured', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'default'      => '',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'stock_status',
			[
				'label'   => esc_html__( 'Stock Status', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''            => esc_html__( 'Any', 'hkdev-shop-elements' ),
					'instock'     => esc_html__( 'In Stock', 'hkdev-shop-elements' ),
					'outofstock'  => esc_html__( 'Out of Stock', 'hkdev-shop-elements' ),
					'onbackorder' => esc_html__( 'On Backorder', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'days',
			[
				'label'       => esc_html__( 'Trending Days', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 365,
				'description' => esc_html__( 'Trending looks only at products published within this many days (0 = last 30 days).', 'hkdev-shop-elements' ),
				'condition'   => [ 'type' => 'trending' ],
			]
		);

		$this->add_control(
			'order_by',
			[
				'label'   => esc_html__( 'Order', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC' => esc_html__( 'Newest First', 'hkdev-shop-elements' ),
					'ASC'  => esc_html__( 'Oldest First', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'show_tabs',
			[
				'label'        => esc_html__( 'Show Category Tabs', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'include_children',
			[
				'label'        => esc_html__( 'Include Sub-Categories', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'style',
			[
				'label'       => esc_html__( 'Layout Style', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'grid',
				'options'     => [
					'grid'     => esc_html__( 'Grid', 'hkdev-shop-elements' ),
					'carousel' => esc_html__( 'Carousel / Slider', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Carousel shows the slides, autoplay and arrow options in the Carousel section below.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'load_more',
			[
				'label'        => esc_html__( 'Load More Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Appends the next batch of products without reloading the page. Only appears when there are more products than the Item Limit.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'load_more_text',
			[
				'label'     => esc_html__( 'Load More Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load More', 'hkdev-shop-elements' ),
				'condition' => [ 'load_more' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->register_product_controls();
		$this->register_heading_controls();
		$this->register_style_sections();
	}

	/**
	 * Style tab – every visual part of the grid / carousel card.
	 *
	 * @return void
	 */
	protected function register_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-shop-wrapper';

		/* ---------------- Layout & spacing ---------------- */
		$this->start_controls_section(
			'hkdev_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'sk_wrapper_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'sk_wrapper_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_slider( 'sk_grid_gap', esc_html__( 'Gap Between Cards', 'hkdev-shop-elements' ), $scope . ' .hkdev-shop-grid', 'gap', 0, 60 );
		$this->hkdev_color( 'sk_page_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );

		$this->end_controls_section();

		/* ---------------- Card ---------------- */
		$this->start_controls_section(
			'hkdev_style_card',
			[
				'label' => esc_html__( 'Product Card', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'sk_card_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'background-color' );
		$this->hkdev_color( 'sk_card_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-color' );
		$this->hkdev_slider( 'sk_card_border_w', esc_html__( 'Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-width', 0, 8 );
		$this->hkdev_dimensions( 'sk_card_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-radius' );
		$this->hkdev_shadow( 'sk_card_shadow', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card' );
		$this->hkdev_dimensions( 'sk_card_content_pad', esc_html__( 'Content Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-content-box', 'padding' );

		$this->end_controls_section();

		/* ---------------- Image ---------------- */
		$this->start_controls_section(
			'hkdev_style_image',
			[
				'label' => esc_html__( 'Product Image', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_select(
			'sk_img_ratio',
			esc_html__( 'Image Shape', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-img-box img',
			'aspect-ratio',
			[
				''       => esc_html__( 'Default (square)', 'hkdev-shop-elements' ),
				'1 / 1'  => esc_html__( 'Square (1:1)', 'hkdev-shop-elements' ),
				'4 / 3'  => esc_html__( 'Landscape (4:3)', 'hkdev-shop-elements' ),
				'3 / 2'  => esc_html__( 'Landscape (3:2)', 'hkdev-shop-elements' ),
				'16 / 9' => esc_html__( 'Wide (16:9)', 'hkdev-shop-elements' ),
				'3 / 4'  => esc_html__( 'Portrait (3:4)', 'hkdev-shop-elements' ),
				'4 / 5'  => esc_html__( 'Portrait (4:5)', 'hkdev-shop-elements' ),
				'auto'   => esc_html__( 'Original', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_select(
			'sk_img_fit',
			esc_html__( 'Image Fit', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-img-box img',
			'object-fit',
			[
				'cover'   => esc_html__( 'Cover (crop)', 'hkdev-shop-elements' ),
				'contain' => esc_html__( 'Contain (whole image)', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_slider( 'sk_img_zoom', esc_html__( 'Hover Zoom', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card:hover .hkdev-img-box img', 'transform', 1, 1.3 );
		$this->hkdev_dimensions( 'sk_img_radius', esc_html__( 'Image Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-img-box img', 'border-radius' );
		$this->hkdev_color( 'sk_img_box_bg', esc_html__( 'Image Area Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-img-box', 'background-color' );

		$this->end_controls_section();

		/* ---------------- Text ---------------- */
		$this->start_controls_section(
			'hkdev_style_text',
			[
				'label' => esc_html__( 'Card Text', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_cat', esc_html__( 'Category / Brand', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-label' );
		$this->hkdev_typography( 'sk_title', esc_html__( 'Product Title', 'hkdev-shop-elements' ), $scope . ' .hkdev-title' );
		$this->hkdev_typography( 'sk_price', esc_html__( 'Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-price-container .price .amount' );
		$this->hkdev_color( 'sk_price_old', esc_html__( 'Old (Struck) Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-price-container .price del .amount', 'color' );

		$this->end_controls_section();

		/* ---------------- Button ---------------- */
		$this->start_controls_section(
			'hkdev_style_button',
			[
				'label' => esc_html__( 'Buy Now Button', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn' );
		$this->hkdev_color( 'sk_btn_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'background-color' );
		$this->hkdev_color( 'sk_btn_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'border-color' );
		$this->hkdev_color( 'sk_btn_bg_hover', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover', 'background-color' );
		$this->hkdev_color( 'sk_btn_color_hover', esc_html__( 'Hover Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover', 'color' );
		$this->hkdev_slider( 'sk_btn_height', esc_html__( 'Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'height', 30, 70 );
		$this->hkdev_dimensions( 'sk_btn_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'border-radius' );
		$this->hkdev_dimensions( 'sk_btn_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'padding' );

		$this->end_controls_section();

		/* ---------------- Badges ---------------- */
		$this->start_controls_section(
			'hkdev_style_badges',
			[
				'label' => esc_html__( 'Badges', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'sk_sale_bg', esc_html__( 'Sale Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sale-badge', 'background-color' );
		$this->hkdev_color( 'sk_sale_color', esc_html__( 'Sale Badge Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-sale-badge', 'color' );
		$this->hkdev_color( 'sk_trend_bg', esc_html__( 'Trending Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-trending-badge', 'background-color' );
		$this->hkdev_color( 'sk_best_bg', esc_html__( 'Best Seller Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-best-seller-badge', 'background-color' );

		$this->end_controls_section();
	}

	/**
	 * Render the widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$yes_no   = static function ( $key ) use ( $settings ) {
			return ( isset( $settings[ $key ] ) && 'yes' === $settings[ $key ] ) ? 'yes' : 'no';
		};
		// Multi-select controls save an array; the shortcode expects a comma list.
		$csv      = static function ( $key ) use ( $settings ) {
			if ( empty( $settings[ $key ] ) ) {
				return '';
			}
			$value = $settings[ $key ];
			return sanitize_text_field( is_array( $value ) ? implode( ',', $value ) : (string) $value );
		};

		$atts = [
			'limit'            => isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 12,
			'columns'          => isset( $settings['columns'] ) ? $settings['columns'] : '4',
			'image_size'       => isset( $settings['image_size'] ) ? sanitize_key( $settings['image_size'] ) : 'woocommerce_thumbnail',
			'category'         => $csv( 'category' ),
			'exclude'          => $csv( 'exclude' ),
			'tags'             => $csv( 'tags' ),
			'brands'           => $csv( 'brands' ),
			'on_sale'          => $yes_no( 'on_sale' ),
			'featured'         => $yes_no( 'featured' ),
			'stock_status'     => isset( $settings['stock_status'] ) ? sanitize_key( $settings['stock_status'] ) : '',
			'type'             => isset( $settings['type'] ) ? $settings['type'] : 'recent',
			'days'             => isset( $settings['days'] ) ? absint( $settings['days'] ) : 0,
			'order_by'         => isset( $settings['order_by'] ) ? $settings['order_by'] : 'DESC',
			'show_tabs'        => $yes_no( 'show_tabs' ),
			'include_children' => $yes_no( 'include_children' ),
			'style'            => isset( $settings['style'] ) ? $settings['style'] : 'grid',
			'load_more'        => isset( $settings['load_more'] ) ? $yes_no( 'load_more' ) : 'yes',
			'load_more_text'   => isset( $settings['load_more_text'] ) && '' !== $settings['load_more_text'] ? sanitize_text_field( $settings['load_more_text'] ) : __( 'Load More', 'hkdev-shop-elements' ),
			'heading'          => $this->get_heading_config( $settings ),
			'carousel'         => $this->get_carousel_config( $settings ),
			'title_lines'      => $this->get_title_lines( $settings ),
		];

		echo \HkdevShopElements\Includes\Shop_Engine::instance()->master_shop_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
