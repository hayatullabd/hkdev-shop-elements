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
 * Class Cart_Widget
 */
class Cart_Widget extends Widget_Base {

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
		echo \HkdevShopElements\Includes\Cart_Engine::instance()->custom_cart_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
