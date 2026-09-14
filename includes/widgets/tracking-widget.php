<?php
/**
 * HKDEV Tracking Widget (HKDEV Shop Elements plugin).
 *
 * Elementor widget for order tracking.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Tracking_Widget extends Widget_Base {

	use Style_Controls;

	public function get_name() {
		return 'hkdev_tracking';
	}

	public function get_title() {
		return esc_html__( 'HKDEV Order Tracking', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	public function get_keywords() {
		return [ 'tracking', 'order', 'track', 'woocommerce' ];
	}

	public function get_style_depends() {
		return [ 'hkdev-elements-tracking-style', 'hkdev-elements-fontawesome' ];
	}

	public function get_script_depends() {
		return [ 'hkdev-elements-tracking-js' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_tracking',
			[
				'label' => esc_html__( 'Tracking Settings', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Title', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Track Your Order', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __( 'Enter your order ID and phone number to track your order.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_status_legend',
			[
				'label'        => esc_html__( 'Show Status Legend', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->register_tr_style_sections();
	}

	/**
	 * Style tab – container, header, form and status legend.
	 *
	 * @return void
	 */
	protected function register_tr_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-tracking-wrap';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'tr_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'tr_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_dimensions( 'tr_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'tr_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_dimensions( 'tr_radius', esc_html__( 'Block Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Header & form ---------------- */
		$this->start_controls_section(
			'tr_style_form',
			[
				'label' => esc_html__( 'Header & Form', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'tr_title', esc_html__( 'Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-header h3' );
		$this->hkdev_typography( 'tr_desc', esc_html__( 'Description', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-header p' );
		$this->hkdev_typography( 'tr_label', esc_html__( 'Field Label', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-field label' );
		$this->hkdev_color( 'tr_input_bg', esc_html__( 'Input Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-field input', 'background-color' );
		$this->hkdev_color( 'tr_input_border', esc_html__( 'Input Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-field input', 'border-color' );
		$this->hkdev_color( 'tr_input_color', esc_html__( 'Input Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-field input', 'color' );
		$this->hkdev_dimensions( 'tr_input_radius', esc_html__( 'Input Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-field input', 'border-radius' );
		$this->hkdev_typography( 'tr_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-submit' );
		$this->hkdev_color( 'tr_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-submit', 'background-color' );
		$this->hkdev_color( 'tr_btn_color', esc_html__( 'Button Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-submit', 'color' );
		$this->hkdev_dimensions( 'tr_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-submit', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Legend ---------------- */
		$this->start_controls_section(
			'tr_style_legend',
			[
				'label' => esc_html__( 'Status Legend', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'tr_legend_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-legend', 'background-color' );
		$this->hkdev_color( 'tr_legend_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-legend', 'border-color' );
		$this->hkdev_dimensions( 'tr_legend_radius', esc_html__( 'Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-legend', 'border-radius' );
		$this->hkdev_typography( 'tr_legend_title', esc_html__( 'Legend Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-tracking-legend h4' );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$atts = [
			'title'              => isset( $settings['title'] ) ? $settings['title'] : '',
			'description'        => isset( $settings['description'] ) ? $settings['description'] : '',
			'show_status_legend' => isset( $settings['show_status_legend'] ) && 'yes' === $settings['show_status_legend'] ? 'yes' : 'no',
		];

		echo \HkdevShopElements\Includes\Tracking_Engine::instance()->tracking_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}