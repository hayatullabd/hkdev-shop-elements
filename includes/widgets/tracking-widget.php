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