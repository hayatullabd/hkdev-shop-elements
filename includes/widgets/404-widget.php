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

class Page404_Widget extends Widget_Base {

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

		echo \HkdevShopElements\Includes\Page404_Engine::instance()->page404_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}