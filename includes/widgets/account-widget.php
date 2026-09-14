<?php
/**
 * HKDEV Account Widget (HKDEV Shop Elements plugin).
 *
 * Elementor widget for the My Account page.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Account_Widget extends Widget_Base {

	public function get_name() {
		return 'hkdev_account';
	}

	public function get_title() {
		return esc_html__( 'HKDEV My Account', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	public function get_keywords() {
		return [ 'account', 'my account', 'orders', 'profile', 'woocommerce' ];
	}

	public function get_style_depends() {
		return [ 'hkdev-elements-account-style', 'hkdev-elements-fontawesome' ];
	}

	public function get_script_depends() {
		return [ 'hkdev-elements-account-js' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_account',
			[
				'label' => esc_html__( 'Account Settings', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_orders',
			[
				'label'        => esc_html__( 'Show Orders', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_addresses',
			[
				'label'        => esc_html__( 'Show Addresses', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_profile',
			[
				'label'        => esc_html__( 'Show Profile', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_downloads',
			[
				'label'        => esc_html__( 'Show Downloads', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'orders_per_page',
			[
				'label'   => esc_html__( 'Orders Per Page', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
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
			'show_orders'     => isset( $settings['show_orders'] ) && 'yes' === $settings['show_orders'] ? 'yes' : 'no',
			'show_addresses'  => isset( $settings['show_addresses'] ) && 'yes' === $settings['show_addresses'] ? 'yes' : 'no',
			'show_profile'    => isset( $settings['show_profile'] ) && 'yes' === $settings['show_profile'] ? 'yes' : 'no',
			'show_downloads'  => isset( $settings['show_downloads'] ) && 'yes' === $settings['show_downloads'] ? 'yes' : 'no',
			'orders_per_page' => isset( $settings['orders_per_page'] ) ? absint( $settings['orders_per_page'] ) : 10,
		];

		echo \HkdevShopElements\Includes\Account_Engine::instance()->account_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}