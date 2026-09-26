<?php
/**
 * HKDEV Cart Widget (HKDEV Shop Elements plugin).
 *
 * Renders the full custom cart page (items list, totals, custom checkout
 * button). Self-contained – does not require the hkdev-shop theme.
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
 * Class CartWidget
 */
class CartWidget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_cart';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Cart', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-cart';
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
		return [ 'cart', 'woocommerce', 'basket' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-cart-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-cart-js' ];
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
				'label' => esc_html__( 'Settings', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'This widget renders the full cart page. Place it on the WooCommerce Cart page (or a page assigned as cart) and keep only this widget on that page.', 'hkdev-shop-elements' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_items_style',
			[
				'label' => esc_html__( 'Cart Items', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'item_title_heading',
			[
				'label' => esc_html__( 'Product Name', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'item_title_color',
			[
				'label'   => esc_html__( 'Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'item_title_size',
			[
				'label'      => esc_html__( 'Font Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 11,
						'max' => 30,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 16,
				],
			]
		);

		$this->add_control(
			'item_title_weight',
			[
				'label'   => esc_html__( 'Font Weight', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '800',
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
			'item_unit_heading',
			[
				'label'     => esc_html__( 'Unit Price', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'item_unit_color',
			[
				'label'   => esc_html__( 'Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'item_unit_size',
			[
				'label'      => esc_html__( 'Font Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 10,
						'max' => 24,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 14,
				],
			]
		);

		$this->add_control(
			'item_total_heading',
			[
				'label'     => esc_html__( 'Item Total', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'item_total_color',
			[
				'label'   => esc_html__( 'Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'item_total_size',
			[
				'label'      => esc_html__( 'Font Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 12,
						'max' => 32,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 18,
				],
			]
		);

		$this->add_control(
			'item_row_heading',
			[
				'label'     => esc_html__( 'Row', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'item_row_padding',
			[
				'label'      => esc_html__( 'Vertical Padding', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 20,
				],
			]
		);

		$this->add_control(
			'item_row_border',
			[
				'label'   => esc_html__( 'Divider Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'item_thumb_size',
			[
				'label'      => esc_html__( 'Image Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 40,
						'max' => 140,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 90,
				],
			]
		);

		$this->add_control(
			'item_thumb_radius',
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
					'size' => 12,
				],
			]
		);

		$this->add_control(
			'item_remove_heading',
			[
				'label'     => esc_html__( 'Remove Button', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'item_remove_color',
			[
				'label'   => esc_html__( 'Icon Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->add_control(
			'item_remove_hover',
			[
				'label'   => esc_html__( 'Icon Hover Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->register_cart_style_sections();
	}

	/**
	 * Style tab – container, header, summary, coupon and buttons.
	 *
	 * @return void
	 */
	protected function register_cart_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-cart-scope';

		/* ---------------- Container ---------------- */
		$this->start_controls_section(
			'ct_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'ct_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-container', 'margin' );
		$this->hkdev_dimensions( 'ct_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-container', 'padding' );
		$this->hkdev_slider( 'ct_grid_gap', esc_html__( 'Column Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-grid', 'gap', 0, 80 );

		$this->end_controls_section();

		/* ---------------- Cards ---------------- */
		$this->start_controls_section(
			'ct_style_card',
			[
				'label' => esc_html__( 'Cards', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'ct_card_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card', 'background-color' );
		$this->hkdev_color( 'ct_card_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card', 'border-color' );
		$this->hkdev_slider( 'ct_card_border_w', esc_html__( 'Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card', 'border-width', 0, 6 );
		$this->hkdev_dimensions( 'ct_card_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card', 'border-radius' );
		$this->hkdev_dimensions( 'ct_card_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card', 'padding' );
		$this->hkdev_shadow( 'ct_card_shadow', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-card' );

		$this->end_controls_section();

		/* ---------------- Header ---------------- */
		$this->start_controls_section(
			'ct_style_header',
			[
				'label' => esc_html__( 'Page Header', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'ct_head', esc_html__( 'Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-header h2' );
		$this->hkdev_typography( 'ct_head_sub', esc_html__( 'Subtitle', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-header p' );

		$this->end_controls_section();

		/* ---------------- Summary ---------------- */
		$this->start_controls_section(
			'ct_style_summary',
			[
				'label' => esc_html__( 'Cart Summary', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'ct_calc', esc_html__( 'Total Lines', 'hkdev-shop-elements' ), $scope . ' .calc-line' );
		$this->hkdev_slider( 'ct_calc_pad', esc_html__( 'Line Padding', 'hkdev-shop-elements' ), $scope . ' .calc-line', 'padding-top', 0, 40 );
		$this->hkdev_typography( 'ct_grand', esc_html__( 'Grand Total', 'hkdev-shop-elements' ), $scope . ' .grand-total-line strong' );

		$this->end_controls_section();

		/* ---------------- Coupon ---------------- */
		$this->start_controls_section(
			'ct_style_coupon',
			[
				'label' => esc_html__( 'Coupon', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'ct_cp_bg', esc_html__( 'Field Background', 'hkdev-shop-elements' ), $scope . ' .coupon-box', 'background-color' );
		$this->hkdev_color( 'ct_cp_border', esc_html__( 'Field Border', 'hkdev-shop-elements' ), $scope . ' .coupon-box', 'border-color' );
		$this->hkdev_dimensions( 'ct_cp_radius', esc_html__( 'Field Radius', 'hkdev-shop-elements' ), $scope . ' .coupon-box', 'border-radius' );
		$this->hkdev_color( 'ct_cp_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope . ' .coupon-box button', 'background-color' );
		$this->hkdev_color( 'ct_cp_btn_color', esc_html__( 'Button Text Colour', 'hkdev-shop-elements' ), $scope . ' .coupon-box button', 'color' );
		$this->hkdev_color( 'ct_cp_btn_bg_hover', esc_html__( 'Button Hover Background', 'hkdev-shop-elements' ), $scope . ' .coupon-box button:hover', 'background-color' );

		$this->end_controls_section();

		/* ---------------- Buttons ---------------- */
		$this->start_controls_section(
			'ct_style_buttons',
			[
				'label' => esc_html__( 'Buttons', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'ct_btn', esc_html__( 'Primary Button', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-primary-btn' );
		$this->hkdev_color( 'ct_btn_bg', esc_html__( 'Primary Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-primary-btn', 'background-color' );
		$this->hkdev_color( 'ct_btn_color', esc_html__( 'Primary Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-primary-btn', 'color' );
		$this->hkdev_color( 'ct_btn_bg_hover', esc_html__( 'Primary Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-primary-btn:hover', 'background-color' );
		$this->hkdev_dimensions( 'ct_btn_radius', esc_html__( 'Primary Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-primary-btn', 'border-radius' );
		$this->hkdev_color( 'ct_sec_bg', esc_html__( 'Secondary Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-secondary-btn', 'background-color' );
		$this->hkdev_color( 'ct_sec_color', esc_html__( 'Secondary Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-secondary-btn', 'color' );
		$this->hkdev_color( 'ct_sec_border', esc_html__( 'Secondary Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-secondary-btn', 'border-color' );
		$this->hkdev_dimensions( 'ct_sec_radius', esc_html__( 'Secondary Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-cart-secondary-btn', 'border-radius' );

		$this->end_controls_section();
	}

	/**
	 * Build the CSS custom properties consumed by cart.css.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function get_style_vars( $settings ) {
		$px = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return round( (float) $settings[ $key ]['size'], 2 ) . 'px';
			}
			return $fallback . 'px';
		};

		$weight = static function ( $key, $fallback ) use ( $settings ) {
			$value = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			return in_array( $value, [ '400', '500', '600', '700', '800' ], true ) ? $value : $fallback;
		};

		$vars = [
			'--hkdev-cart-item-title-size'   => $px( 'item_title_size', 16 ),
			'--hkdev-cart-item-title-weight' => $weight( 'item_title_weight', '800' ),
			'--hkdev-cart-item-unit-size'    => $px( 'item_unit_size', 14 ),
			'--hkdev-cart-item-total-size'   => $px( 'item_total_size', 18 ),
			'--hkdev-cart-row-padding'       => $px( 'item_row_padding', 20 ),
			'--hkdev-cart-thumb-size'        => $px( 'item_thumb_size', 90 ),
			'--hkdev-cart-thumb-radius'      => $px( 'item_thumb_radius', 12 ),
		];

		$colors = [
			'item_title_color'  => '--hkdev-cart-item-title-color',
			'item_unit_color'   => '--hkdev-cart-item-unit-color',
			'item_total_color'  => '--hkdev-cart-item-total-color',
			'item_row_border'   => '--hkdev-cart-row-border',
			'item_remove_color' => '--hkdev-cart-remove-color',
			'item_remove_hover' => '--hkdev-cart-remove-hover',
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
	 * Render the widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$style    = $this->get_style_vars( $settings );

		echo '<div class="hkdev-cart-scope"' . ( '' !== $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
		echo \HkdevShopElements\Includes\Core\CartEngine::instance()->custom_cart_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
