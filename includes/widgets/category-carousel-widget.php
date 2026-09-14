<?php
/**
 * HKDEV Category Carousel Widget (HKDEV Shop Elements plugin).
 *
 * Renders WooCommerce product categories as a responsive Swiper carousel:
 * image + name (+ product count) per card. Slides per view, spacing, autoplay,
 * arrows and dots are all configurable per device.
 *
 * The markup reuses the shop wrapper contract (.hkdev-shop-wrapper with
 * data-style="carousel") so the existing shop.js carousel engine drives it.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Class Category_Carousel_Widget
 */
class Category_Carousel_Widget extends Widget_Base {

	use Heading_Controls;
	use Product_Controls;
	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_category_carousel';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Category Carousel', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
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
		return [ 'category', 'categories', 'carousel', 'slider', 'product cat', 'responsive' ];
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
	 * Category slug => name pairs for the select fields.
	 *
	 * @return array
	 */
	protected function get_category_options() {
		$options = [];

		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $options;
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_query_controls();
		$this->register_product_controls( false );
		$this->register_heading_controls();
		$this->register_card_style_controls();

		// Category cards are lighter than product cards.
		$this->update_control(
			'carousel_gap',
			[
				'default' => [
					'unit' => 'px',
					'size' => 16,
				],
			]
		);

		$this->register_cc_extra_style_controls();
	}

	/**
	 * Extra Style controls that the card sections did not cover yet.
	 *
	 * @return void
	 */
	protected function register_cc_extra_style_controls() {
		$scope = '{{WRAPPER}} .hkdev-cat-carousel';

		$this->start_controls_section(
			'cc_style_extra',
			[
				'label' => esc_html__( 'Card Details', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_shadow( 'cc_card_shadow', esc_html__( 'Card Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-card' );
		$this->hkdev_slider( 'cc_card_border_w', esc_html__( 'Card Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-card', 'border-width', 0, 6 );
		$this->hkdev_slider( 'cc_gap', esc_html__( 'Gap Between Cards', 'hkdev-shop-elements' ), $scope . ' .hkdev-shop-grid', 'gap', 0, 60 );

		$this->hkdev_select(
			'cc_img_fit',
			esc_html__( 'Image Fit', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-cat-thumb img',
			'object-fit',
			[
				'cover'   => esc_html__( 'Cover (crop)', 'hkdev-shop-elements' ),
				'contain' => esc_html__( 'Contain (whole image)', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_color( 'cc_icon_color', esc_html__( 'Placeholder Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-thumb i', 'color' );
		$this->hkdev_slider( 'cc_icon_size', esc_html__( 'Placeholder Icon Size', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-thumb i', 'font-size', 12, 70 );

		$this->hkdev_slider_raw( 'cc_name_lh', esc_html__( 'Name Line Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-name', 'line-height', 0.8, 3, 0.05 );
		$this->hkdev_slider( 'cc_name_ls', esc_html__( 'Name Letter Spacing', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-name', 'letter-spacing', -3, 12 );
		$this->hkdev_select(
			'cc_name_tt',
			esc_html__( 'Name Text Transform', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-cat-name',
			'text-transform',
			[
				''           => esc_html__( 'Default', 'hkdev-shop-elements' ),
				'none'       => esc_html__( 'None', 'hkdev-shop-elements' ),
				'uppercase'  => esc_html__( 'Uppercase', 'hkdev-shop-elements' ),
				'lowercase'  => esc_html__( 'Lowercase', 'hkdev-shop-elements' ),
				'capitalize' => esc_html__( 'Capitalize', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_select(
			'cc_count_weight',
			esc_html__( 'Count Font Weight', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-cat-count',
			'font-weight',
			[
				''    => esc_html__( 'Default', 'hkdev-shop-elements' ),
				'400' => '400',
				'500' => '500',
				'600' => '600',
				'700' => '700',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab – which categories to show.
	 *
	 * @return void
	 */
	protected function register_query_controls() {
		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Categories', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'source',
			[
				'label'   => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'top',
				'options' => [
					'top'      => esc_html__( 'Top Level Categories', 'hkdev-shop-elements' ),
					'all'      => esc_html__( 'All Categories', 'hkdev-shop-elements' ),
					'children' => esc_html__( 'Children of a Category', 'hkdev-shop-elements' ),
					'selected' => esc_html__( 'Selected Categories', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'parent_cat',
			[
				'label'     => esc_html__( 'Parent Category', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT2,
				'options'   => $this->get_category_options(),
				'multiple'  => false,
				'label_block' => true,
				'condition' => [ 'source' => 'children' ],
			]
		);

		$this->add_control(
			'include',
			[
				'label'       => esc_html__( 'Categories', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_category_options(),
				'multiple'    => true,
				'label_block' => true,
				'description' => esc_html__( 'Shown in the order you pick them.', 'hkdev-shop-elements' ),
				'condition'   => [ 'source' => 'selected' ],
			]
		);

		$this->add_control(
			'exclude',
			[
				'label'       => esc_html__( 'Exclude Categories', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_category_options(),
				'multiple'    => true,
				'label_block' => true,
				'condition'   => [ 'source!' => 'selected' ],
			]
		);

		$this->add_control(
			'hide_empty',
			[
				'label'        => esc_html__( 'Hide Empty Categories', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'     => esc_html__( 'Order By', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'name',
				'options'   => [
					'name'  => esc_html__( 'Name', 'hkdev-shop-elements' ),
					'count' => esc_html__( 'Product Count', 'hkdev-shop-elements' ),
					'slug'  => esc_html__( 'Slug', 'hkdev-shop-elements' ),
					'id'    => esc_html__( 'ID', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'source!' => 'selected' ],
			]
		);

		$this->add_control(
			'order',
			[
				'label'     => esc_html__( 'Order', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => [
					'ASC'  => esc_html__( 'Ascending', 'hkdev-shop-elements' ),
					'DESC' => esc_html__( 'Descending', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'source!' => 'selected' ],
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => esc_html__( 'Maximum Categories', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 50,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'       => esc_html__( 'Laptop Slides', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '6',
				'options'     => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
				],
				'description' => esc_html__( 'Cards visible at a time on laptops and larger screens.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_image',
			[
				'label'        => esc_html__( 'Show Image', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'     => esc_html__( 'Image Size', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'woocommerce_thumbnail',
				'options'   => [
					'thumbnail'            => esc_html__( 'Thumbnail', 'hkdev-shop-elements' ),
					'medium'               => esc_html__( 'Medium', 'hkdev-shop-elements' ),
					'medium_large'         => esc_html__( 'Medium Large', 'hkdev-shop-elements' ),
					'large'                => esc_html__( 'Large', 'hkdev-shop-elements' ),
					'woocommerce_thumbnail' => esc_html__( 'WooCommerce Thumbnail', 'hkdev-shop-elements' ),
					'full'                 => esc_html__( 'Full', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'show_image' => 'yes' ],
			]
		);

		$this->add_control(
			'show_count',
			[
				'label'        => esc_html__( 'Show Product Count', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'count_format',
			[
				'label'       => esc_html__( 'Count Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '%d Products', 'hkdev-shop-elements' ),
				'label_block' => true,
				'description' => esc_html__( 'Use %d where the number should appear.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_count' => 'yes' ],
			]
		);

		$this->add_control(
			'title_lines',
			[
				'label'   => esc_html__( 'Name Lines', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'0' => esc_html__( 'Unlimited', 'hkdev-shop-elements' ),
					'1' => esc_html__( '1 Line', 'hkdev-shop-elements' ),
					'2' => esc_html__( '2 Lines', 'hkdev-shop-elements' ),
					'3' => esc_html__( '3 Lines', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Long category names are trimmed with "..." so every card stays the same height.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab – card look and feel.
	 *
	 * @return void
	 */
	protected function register_card_style_controls() {
		$this->start_controls_section(
			'section_card_style',
			[
				'label' => esc_html__( 'Category Card', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'card_bg',
			[
				'label'   => esc_html__( 'Background', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#ffffff',
			]
		);

		$this->add_control(
			'card_border',
			[
				'label'   => esc_html__( 'Border Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'card_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 14,
				],
			]
		);

		$this->add_control(
			'card_padding',
			[
				'label'      => esc_html__( 'Padding', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 30,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 12,
				],
			]
		);

		$this->add_control(
			'img_radius',
			[
				'label'      => esc_html__( 'Image Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 10,
				],
			]
		);

		$this->add_control(
			'img_ratio',
			[
				'label'   => esc_html__( 'Image Shape', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1 / 1',
				'options' => [
					'1 / 1'  => esc_html__( 'Square', 'hkdev-shop-elements' ),
					'4 / 3'  => esc_html__( 'Landscape (4:3)', 'hkdev-shop-elements' ),
					'3 / 2'  => esc_html__( 'Landscape (3:2)', 'hkdev-shop-elements' ),
					'3 / 4'  => esc_html__( 'Portrait (3:4)', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'name_heading',
			[
				'label'     => esc_html__( 'Category Name', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'name_color',
			[
				'label'   => esc_html__( 'Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'name_hover_color',
			[
				'label'   => esc_html__( 'Hover Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'name_size',
			[
				'label'      => esc_html__( 'Font Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 11,
						'max' => 26,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 15,
				],
			]
		);

		$this->add_control(
			'name_weight',
			[
				'label'   => esc_html__( 'Font Weight', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '700',
				'options' => [
					'400' => esc_html__( 'Normal (400)', 'hkdev-shop-elements' ),
					'500' => '500',
					'600' => '600',
					'700' => esc_html__( 'Bold (700)', 'hkdev-shop-elements' ),
					'800' => '800',
				],
			]
		);

		$this->add_control(
			'count_heading',
			[
				'label'     => esc_html__( 'Product Count', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'count_color',
			[
				'label'   => esc_html__( 'Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'count_size',
			[
				'label'      => esc_html__( 'Font Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 9,
						'max' => 20,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 12.5,
				],
			]
		);

		$this->add_control(
			'hover_heading',
			[
				'label'     => esc_html__( 'Card Hover', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'hover_border',
			[
				'label'   => esc_html__( 'Border Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'hover_lift',
			[
				'label'      => esc_html__( 'Lift (px)', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 14,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 5,
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Build the CSS custom properties consumed by shop.css.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function get_card_style_vars( $settings ) {
		$px = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return round( (float) $settings[ $key ]['size'], 2 ) . 'px';
			}
			return $fallback;
		};

		$color = static function ( $key, $fallback ) use ( $settings ) {
			return ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? $settings[ $key ] : $fallback;
		};

		$weight = static function ( $key, $fallback ) use ( $settings ) {
			$value = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			return in_array( $value, [ '400', '500', '600', '700', '800' ], true ) ? $value : $fallback;
		};

		$ratio = isset( $settings['img_ratio'] ) ? (string) $settings['img_ratio'] : '1 / 1';
		if ( ! in_array( $ratio, [ '1 / 1', '4 / 3', '3 / 2', '3 / 4' ], true ) ) {
			$ratio = '1 / 1';
		}

		$vars = [
			'--hkdev-cat-bg'           => $color( 'card_bg', '#ffffff' ),
			'--hkdev-cat-radius'       => $px( 'card_radius', '14px' ),
			'--hkdev-cat-pad'          => $px( 'card_padding', '12px' ),
			'--hkdev-cat-img-radius'   => $px( 'img_radius', '10px' ),
			'--hkdev-cat-ratio'        => $ratio,
			'--hkdev-cat-name-size'    => $px( 'name_size', '15px' ),
			'--hkdev-cat-name-weight'  => $weight( 'name_weight', '700' ),
			'--hkdev-cat-count-size'   => $px( 'count_size', '12.5px' ),
			'--hkdev-cat-lift'         => $px( 'hover_lift', '5px' ),
		];

		$colors = [
			'card_border'      => '--hkdev-cat-border',
			'name_color'       => '--hkdev-cat-name-color',
			'name_hover_color' => '--hkdev-cat-name-hover',
			'count_color'      => '--hkdev-cat-count-color',
			'hover_border'     => '--hkdev-cat-hover-border',
		];

		foreach ( $colors as $setting => $prop ) {
			if ( ! empty( $settings[ $setting ] ) ) {
				$vars[ $prop ] = $settings[ $setting ];
			}
		}

		$style = '';
		foreach ( $vars as $prop => $value ) {
			$style .= $prop . ':' . sanitize_text_field( (string) $value ) . ';';
		}

		return $style;
	}

	/**
	 * Resolve the categories to render.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	protected function query_categories( $settings ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$limit  = isset( $settings['limit'] ) ? max( 1, absint( $settings['limit'] ) ) : 12;
		$source = isset( $settings['source'] ) ? $settings['source'] : 'top';

		$args = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => ( isset( $settings['hide_empty'] ) && 'yes' === $settings['hide_empty'] ),
			'number'     => $limit,
			'order'      => ( isset( $settings['order'] ) && 'DESC' === $settings['order'] ) ? 'DESC' : 'ASC',
		];

		$orderby = isset( $settings['orderby'] ) ? $settings['orderby'] : 'name';
		$args['orderby'] = in_array( $orderby, [ 'name', 'count', 'slug', 'id' ], true ) ? $orderby : 'name';

		if ( 'selected' === $source ) {
			$ids = $this->slugs_to_term_ids( isset( $settings['include'] ) ? (array) $settings['include'] : [] );

			if ( empty( $ids ) ) {
				return [];
			}

			// 'include' keeps the order the user picked in the widget.
			$args['include'] = $ids;
			$args['orderby'] = 'include';
			$args['number']  = count( $ids );
		} elseif ( 'children' === $source ) {
			$parent = isset( $settings['parent_cat'] ) ? sanitize_title( $settings['parent_cat'] ) : '';
			$term   = $parent ? get_term_by( 'slug', $parent, 'product_cat' ) : false;
			if ( ! $term ) {
				return [];
			}
			$args['parent'] = $term->term_id;
		} elseif ( 'top' === $source ) {
			$args['parent'] = 0;
		}

		if ( 'selected' !== $source && ! empty( $settings['exclude'] ) ) {
			$exclude_slugs = array_filter( array_map( 'sanitize_title', (array) $settings['exclude'] ) );
			if ( ! empty( $exclude_slugs ) ) {
				$exclude_slugs        = array_slice( $exclude_slugs, 0, 40 );
				$args['exclude']      = implode( ',', $exclude_slugs );
				$args['exclude_tree'] = '';
			}
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		return $terms;
	}

	/**
	 * Convert a list of category slugs to term IDs.
	 *
	 * Term queries only accept IDs for include/exclude, not slugs.
	 *
	 * @param array $slugs Category slugs.
	 * @return array
	 */
	protected function slugs_to_term_ids( $slugs ) {
		$ids = [];

		foreach ( array_filter( array_map( 'sanitize_title', $slugs ) ) as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return $ids;
	}

	/**
	 * Render a single category card.
	 *
	 * @param \WP_Term $term     Category term.
	 * @param array    $settings Widget settings.
	 * @return void
	 */
	protected function render_card( $term, $settings ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			return;
		}

		$show_image = ! isset( $settings['show_image'] ) || 'yes' === $settings['show_image'];
		$show_count = isset( $settings['show_count'] ) && 'yes' === $settings['show_count'];
		$size       = isset( $settings['image_size'] ) ? $settings['image_size'] : 'woocommerce_thumbnail';
		$thumb_id   = $show_image ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;

		$count_text = '';
		if ( $show_count ) {
			$format     = isset( $settings['count_format'] ) && '' !== $settings['count_format']
				? $settings['count_format']
				: '%d Products';
			$count_text = sprintf( $format, number_format_i18n( $term->count ) );
		}
		?>
		<a class="hkdev-cat-card swiper-slide" href="<?php echo esc_url( $link ); ?>">
			<?php if ( $show_image ) : ?>
				<span class="hkdev-cat-thumb">
					<?php
					if ( $thumb_id ) {
						echo wp_get_attachment_image( $thumb_id, $size, false, [ 'alt' => esc_attr( $term->name ), 'loading' => 'lazy' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo '<i class="fa-solid fa-layer-group" aria-hidden="true"></i>';
					}
					?>
				</span>
			<?php endif; ?>

			<span class="hkdev-cat-body">
				<span class="hkdev-cat-name"><?php echo esc_html( $term->name ); ?></span>
				<?php if ( '' !== $count_text ) : ?>
					<span class="hkdev-cat-count"><?php echo esc_html( $count_text ); ?></span>
				<?php endif; ?>
			</span>
		</a>
		<?php
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

		$settings   = $this->get_settings_for_display();
		$categories = $this->query_categories( $settings );

		if ( empty( $categories ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="hkdev-cat-empty">' . esc_html__( 'No product categories found for the current settings.', 'hkdev-shop-elements' ) . '</div>';
			}
			return;
		}

		$carousel  = $this->get_carousel_config( $settings );
		$columns   = isset( $settings['columns'] ) ? max( 1, absint( $settings['columns'] ) ) : 6;
		$unique_id = 'hkdev-cat-' . wp_rand( 1000, 9999 );
		?>
		<div class="hkdev-shop-wrapper hkdev-cat-carousel" id="<?php echo esc_attr( $unique_id ); ?>"
			 data-columns="<?php echo esc_attr( $columns ); ?>"
			 data-style="carousel"
			 data-carousel="<?php echo esc_attr( wp_json_encode( $carousel ) ); ?>"
			 data-car-arrows="<?php echo esc_attr( $carousel['arrows'] ? '1' : '0' ); ?>"
			 data-car-dots="<?php echo esc_attr( $carousel['dots'] ? '1' : '0' ); ?>"
			 data-title-lines="<?php echo absint( $this->get_title_lines( $settings ) ); ?>"
			 data-hkdev-elements="1">

			<?php echo \HkdevShopElements\Includes\Shop_Engine::instance()->shop_heading_html( $this->get_heading_config( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<div class="hkdev-cat-style" style="<?php echo esc_attr( $this->get_card_style_vars( $settings ) ); ?>">
				<div class="hkdev-grid-container">
					<div class="swiper hkdev-swiper-container hkdev-loading-carousel">
						<div class="swiper-wrapper hkdev-shop-grid">
							<?php foreach ( $categories as $term ) : ?>
								<?php $this->render_card( $term, $settings ); ?>
							<?php endforeach; ?>
						</div>
						<div class="hkdev-carousel-dots swiper-pagination"></div>
					</div>
					<div class="hkdev-nav-btn hkdev-prev-<?php echo esc_attr( $unique_id ); ?> kh-prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></div>
					<div class="hkdev-nav-btn hkdev-next-<?php echo esc_attr( $unique_id ); ?> kh-next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
				</div>
			</div>
		</div>
		<?php
	}
}
