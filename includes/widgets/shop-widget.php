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
use HkdevShopElements\Includes\Core\ShopEngine;

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
		return esc_html__( 'HKDEV Shop Grid', 'hkdev-shop-elements' );
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
		return [ 'shop', 'grid', 'products', 'category', 'tabs', 'woocommerce' ];
	}

	/**
	 * Widget layout style used by the shortcode render.
	 *
	 * Child widgets can override this to force a different mode.
	 *
	 * @return string
	 */
	protected function get_widget_layout_style() {
		return 'grid';
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
		/* ---- Layout: Presentation ---- */
		$this->start_controls_section(
			'section_presentation',
			[
				'label' => esc_html__( 'Presentation', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'style',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => $this->get_widget_layout_style(),
			]
		);

		$this->register_cards_per_view_controls( '4' );

		$this->add_control(
			'limit',
			[
				'label'       => esc_html__( 'Products Per Page', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 12,
				'min'         => 1,
				'max'         => 100,
				'description' => esc_html__( 'How many products load at once (Load More adds the next batch).', 'hkdev-shop-elements' ),
			]
		);

		$this->register_design_controls();

		$this->end_controls_section();

		/* ---- Layout: Query ---- */
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Query', 'hkdev-shop-elements' ),
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
			'custom_products',
			[
				'label'       => esc_html__( 'Custom Products', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => ShopEngine::product_options(),
				'description' => esc_html__( 'Manual picks for Best Selling / Trending. Order matches selection. Empty = automatic ranking.', 'hkdev-shop-elements' ),
				'condition'   => [ 'type' => [ 'best_selling', 'trending' ] ],
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
				'description' => esc_html__( 'Only products published within this window (0 = 30 days). Ignored when Custom Products are set.', 'hkdev-shop-elements' ),
				'condition'   => [ 'type' => 'trending' ],
			]
		);

		$this->add_control(
			'order_by',
			[
				'label'     => esc_html__( 'Sort Order', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => [
					'DESC' => esc_html__( 'Newest First', 'hkdev-shop-elements' ),
					'ASC'  => esc_html__( 'Oldest First', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'type' => 'recent' ],
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
				'options'        => ShopEngine::term_options( 'product_cat' ),
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
				'options'        => ShopEngine::term_options( 'product_cat' ),
				'description'    => esc_html__( 'Products inside these categories are hidden.', 'hkdev-shop-elements' ),
			]
		);

		$brand_options = ShopEngine::term_options( 'product_brand' );
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

		$tag_options = ShopEngine::term_options( 'product_tag' );
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

		$this->end_controls_section();

		/* ---- Layout: AJAX tab filters ---- */
		$this->start_controls_section(
			'section_ajax_tabs',
			[
				'label' => esc_html__( 'AJAX Tab Filters', 'hkdev-shop-elements' ),
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
			'tabs',
			[
				'label'       => esc_html__( 'Category Tabs', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => [
					[
						'name'        => 'category',
						'label'       => esc_html__( 'Category', 'hkdev-shop-elements' ),
						'type'        => Controls_Manager::SELECT2,
						'options'     => ShopEngine::term_options( 'product_cat' ),
						'label_block' => true,
					],
				],
				'title_field' => '{{{ category }}}',
				'default'     => [],
				'condition'   => [ 'show_tabs' => 'yes' ],
				'description' => esc_html__( 'Add categories to set the tab order, then drag to rearrange. Leave empty to list categories automatically.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		/* ---- Layout: Pagination ---- */
		$this->start_controls_section(
			'section_pagination',
			[
				'label' => esc_html__( 'Pagination', 'hkdev-shop-elements' ),
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
				'description'  => esc_html__( 'Grid layout only: loads the next batch without reloading. Carousel ignores this setting.', 'hkdev-shop-elements' ),
				'condition'    => [ 'style' => 'grid' ],
			]
		);

		$this->add_control(
			'load_more_text',
			[
				'label'       => esc_html__( 'Load More Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Load More', 'hkdev-shop-elements' ),
				'condition'   => [
					'style'     => 'grid',
					'load_more' => 'yes',
				],
				'description' => esc_html__( 'Used when Layout Style is Grid and Load More is enabled.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->register_carousel_controls();

		$this->register_heading_controls();
		$this->register_product_title_controls();
		$this->register_product_image_controls( true );

		$this->register_style_sections( '{{WRAPPER}} .hkdev-shop-wrapper' );
		$this->register_tabs_style_controls();
		$this->register_carousel_style_controls();
	}

	/**
	 * Style controls for category tabs.
	 *
	 * @return void
	 */
	protected function register_tabs_style_controls() {
		$scope = '{{WRAPPER}} .hkdev-shop-wrapper';

		$this->start_controls_section(
			'hkdev_style_tabs',
			[
				'label'     => esc_html__( 'Category Tabs', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_tabs' => 'yes' ],
			]
		);

		$this->hkdev_typography( 'sk_tabs', esc_html__( 'Tab Typography', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item' );
		$this->hkdev_dimensions( 'sk_tabs_padding', esc_html__( 'Tab Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item', 'padding' );
		$this->hkdev_dimensions( 'sk_tabs_radius', esc_html__( 'Tab Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item', 'border-radius' );
		$this->hkdev_color( 'sk_tabs_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item', 'background-color' );
		$this->hkdev_color( 'sk_tabs_text', esc_html__( 'Text Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item', 'color' );
		$this->hkdev_color( 'sk_tabs_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item', 'border-color' );
		$this->hkdev_color( 'sk_tabs_bg_hover', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item:hover', 'background-color' );
		$this->hkdev_color( 'sk_tabs_border_hover', esc_html__( 'Hover Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item:hover', 'border-color' );

		$this->add_control(
			'sk_tabs_active_heading',
			[
				'label'     => esc_html__( 'Active Tab', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->hkdev_color( 'sk_tabs_active_bg', esc_html__( 'Active Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item.active', 'background-color' );
		$this->hkdev_color( 'sk_tabs_active_text', esc_html__( 'Active Text Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item.active', 'color' );
		$this->hkdev_color( 'sk_tabs_active_border', esc_html__( 'Active Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item.active', 'border-color' );

		$this->add_control(
			'sk_tabs_count_heading',
			[
				'label'     => esc_html__( 'Count Badge', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->hkdev_typography( 'sk_tabs_count', esc_html__( 'Badge Typography', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-count' );
		$this->hkdev_color( 'sk_tabs_count_bg', esc_html__( 'Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-count', 'background-color' );
		$this->hkdev_color( 'sk_tabs_count_text', esc_html__( 'Badge Text Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-count', 'color' );
		$this->hkdev_color( 'sk_tabs_count_active_bg', esc_html__( 'Active Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item.active .hkdev-tab-count', 'background-color' );
		$this->hkdev_color( 'sk_tabs_count_active_text', esc_html__( 'Active Badge Text Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-tab-item.active .hkdev-tab-count', 'color' );

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

		$tab_slugs = [];
		if ( ! empty( $settings['tabs'] ) && is_array( $settings['tabs'] ) ) {
			foreach ( $settings['tabs'] as $item ) {
				if ( ! empty( $item['category'] ) ) {
					$tab_slugs[] = sanitize_text_field( $item['category'] );
				}
			}
		}

		$custom_product_ids = [];
		if ( ! empty( $settings['custom_products'] ) && is_array( $settings['custom_products'] ) ) {
			$custom_product_ids = array_values( array_filter( array_map( 'absint', $settings['custom_products'] ) ) );
		}

		$cols = $this->get_cards_per_view( $settings );
		$layout_style = ( isset( $settings['style'] ) && 'carousel' === $settings['style'] ) ? 'carousel' : 'grid';

		$atts = array_merge(
			$this->get_design_atts( $settings ),
			$this->get_image_atts( $settings ),
			[
				'limit'            => isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 12,
				'columns'          => (string) $cols['desktop'],
				'columns_tablet'   => (string) $cols['tablet'],
				'columns_mobile'   => (string) $cols['mobile'],
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
				'tabs'             => implode( ',', $tab_slugs ),
				'include_children' => $yes_no( 'include_children' ),
				'style'            => $layout_style,
				'load_more'        => ( 'carousel' === $layout_style ) ? 'no' : ( isset( $settings['load_more'] ) ? $yes_no( 'load_more' ) : 'yes' ),
				'load_more_text'   => isset( $settings['load_more_text'] ) && '' !== $settings['load_more_text'] ? sanitize_text_field( $settings['load_more_text'] ) : __( 'Load More', 'hkdev-shop-elements' ),
				'hover_img'        => $this->get_hover_img( $settings ),
				'heading'          => $this->get_heading_config( $settings ),
				'carousel'         => $this->get_carousel_config( $settings ),
				'title_lines'      => $this->get_title_lines( $settings ),
			]
		);

		if ( ! empty( $custom_product_ids ) && in_array( $atts['type'], [ 'best_selling', 'trending' ], true ) ) {
			$atts['product_ids'] = implode( ',', $custom_product_ids );
		}

		echo ShopEngine::instance()->master_shop_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
