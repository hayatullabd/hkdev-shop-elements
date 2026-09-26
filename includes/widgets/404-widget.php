<?php
/**
 * HKDEV 404 Widget (HKDEV Shop Elements plugin).
 *
 * Elementor widget for the custom 404 page.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Page404Widget extends Widget_Base {

	use Style_Controls;

	public function get_name() {
		return 'hkdev_404_page';
	}

	public function get_title() {
		return esc_html__( 'HKDEV 404 Page', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-error-404';
	}

	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	public function get_keywords() {
		return [ '404', 'page not found', 'error' ];
	}

	public function get_style_depends() {
		return [ 'hkdev-elements-404-style', 'hkdev-elements-fontawesome' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_404',
			[
				'label' => esc_html__( '404 Page Settings', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Error Code', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '404',
			]
		);

		$this->add_control(
			'heading',
			[
				'label'   => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Oops! Page Not Found', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'message',
			[
				'label'   => esc_html__( 'Message', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => __( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_search',
			[
				'label'        => esc_html__( 'Show Search', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_products',
			[
				'label'        => esc_html__( 'Show Popular Products', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'products_count',
			[
				'label'   => esc_html__( 'Number of Products', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			]
		);

		$this->add_control(
			'show_home_btn',
			[
				'label'        => esc_html__( 'Show Home Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'accent_color',
			[
				'label'   => esc_html__( 'Accent Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#03a550',
			]
		);

		$this->end_controls_section();

		$this->register_p404_style_sections();
	}

	/**
	 * Style tab – container, text, search, buttons and product cards.
	 *
	 * @return void
	 */
	protected function register_p404_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-404-wrap';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'p4_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'p4_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_dimensions( 'p4_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'p4_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_dimensions( 'p4_radius', esc_html__( 'Block Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Text ---------------- */
		$this->start_controls_section(
			'p4_style_text',
			[
				'label' => esc_html__( 'Text', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'p4_code', esc_html__( 'Error Code', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-code' );
		$this->hkdev_typography( 'p4_heading', esc_html__( 'Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-heading' );
		$this->hkdev_typography( 'p4_message', esc_html__( 'Message', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-message' );

		$this->end_controls_section();

		/* ---------------- Search & buttons ---------------- */
		$this->start_controls_section(
			'p4_style_search',
			[
				'label' => esc_html__( 'Search & Buttons', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'p4_input_bg', esc_html__( 'Search Field Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields input', 'background-color' );
		$this->hkdev_color( 'p4_input_border', esc_html__( 'Search Field Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields input', 'border-color' );
		$this->hkdev_color( 'p4_input_color', esc_html__( 'Search Field Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields input', 'color' );
		$this->hkdev_dimensions( 'p4_input_radius', esc_html__( 'Search Field Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields input', 'border-radius' );
		$this->hkdev_color( 'p4_search_btn_bg', esc_html__( 'Search Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields button', 'background-color' );
		$this->hkdev_color( 'p4_search_btn_color', esc_html__( 'Search Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-search-fields button', 'color' );
		$this->hkdev_typography( 'p4_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn' );
		$this->hkdev_color( 'p4_btn_bg', esc_html__( 'Primary Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn-primary', 'background-color' );
		$this->hkdev_color( 'p4_btn_color', esc_html__( 'Primary Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn-primary', 'color' );
		$this->hkdev_color( 'p4_btn2_bg', esc_html__( 'Secondary Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn-secondary', 'background-color' );
		$this->hkdev_color( 'p4_btn2_color', esc_html__( 'Secondary Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn-secondary', 'color' );
		$this->hkdev_color( 'p4_btn2_border', esc_html__( 'Secondary Button Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn-secondary', 'border-color' );
		$this->hkdev_dimensions( 'p4_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-btn', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Product cards ---------------- */
		$this->start_controls_section(
			'p4_style_products',
			[
				'label' => esc_html__( 'Popular Products', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'p4_prod_title', esc_html__( 'Section Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-products h3' );
		$this->hkdev_color( 'p4_card_bg', esc_html__( 'Card Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-product-card', 'background-color' );
		$this->hkdev_color( 'p4_card_border', esc_html__( 'Card Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-product-card', 'border-color' );
		$this->hkdev_dimensions( 'p4_card_radius', esc_html__( 'Card Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-product-card', 'border-radius' );
		$this->hkdev_dimensions( 'p4_card_padding', esc_html__( 'Card Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-404-product-card', 'padding' );

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$atts = [
			'title'          => isset( $settings['title'] ) ? $settings['title'] : '404',
			'heading'        => isset( $settings['heading'] ) ? $settings['heading'] : '',
			'message'        => isset( $settings['message'] ) ? $settings['message'] : '',
			'show_search'    => isset( $settings['show_search'] ) && 'yes' === $settings['show_search'] ? 'yes' : 'no',
			'show_products'  => isset( $settings['show_products'] ) && 'yes' === $settings['show_products'] ? 'yes' : 'no',
			'products_count' => isset( $settings['products_count'] ) ? absint( $settings['products_count'] ) : 4,
			'show_home_btn'  => isset( $settings['show_home_btn'] ) && 'yes' === $settings['show_home_btn'] ? 'yes' : 'no',
			'accent_color'   => isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#03a550',
		];

		echo \HkdevShopElements\Includes\Core\Page404Engine::instance()->page404_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}