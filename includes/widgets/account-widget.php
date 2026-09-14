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

	use Style_Controls;

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

		$this->register_ac_style_sections();
	}

	/**
	 * Style tab – panels, navigation, forms and tables.
	 *
	 * @return void
	 */
	protected function register_ac_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-account-wrap';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'ac_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'ac_gap', esc_html__( 'Column Gap', 'hkdev-shop-elements' ), $scope, 'gap', 0, 80 );
		$this->hkdev_color( 'ac_side_bg', esc_html__( 'Sidebar Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-sidebar', 'background-color' );
		$this->hkdev_dimensions( 'ac_side_radius', esc_html__( 'Sidebar Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-sidebar', 'border-radius' );
		$this->hkdev_dimensions( 'ac_side_padding', esc_html__( 'Sidebar Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-sidebar', 'padding' );
		$this->hkdev_color( 'ac_content_bg', esc_html__( 'Content Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-content', 'background-color' );
		$this->hkdev_dimensions( 'ac_content_radius', esc_html__( 'Content Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-content', 'border-radius' );
		$this->hkdev_dimensions( 'ac_content_padding', esc_html__( 'Content Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-content', 'padding' );

		$this->end_controls_section();

		/* ---------------- Navigation ---------------- */
		$this->start_controls_section(
			'ac_style_nav',
			[
				'label' => esc_html__( 'Sidebar Navigation', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'ac_nav', esc_html__( 'Nav Link', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-nav a' );
		$this->hkdev_color( 'ac_nav_active_color', esc_html__( 'Active Link Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-nav li.active a', 'color' );
		$this->hkdev_color( 'ac_nav_active_bg', esc_html__( 'Active Link Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-nav li.active a', 'background-color' );
		$this->hkdev_dimensions( 'ac_nav_radius', esc_html__( 'Link Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-nav a', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Forms & tables ---------------- */
		$this->start_controls_section(
			'ac_style_forms',
			[
				'label' => esc_html__( 'Forms & Tables', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'ac_label', esc_html__( 'Field Label', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-field label' );
		$this->hkdev_color( 'ac_input_bg', esc_html__( 'Input Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-field input', 'background-color' );
		$this->hkdev_color( 'ac_input_border', esc_html__( 'Input Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-field input', 'border-color' );
		$this->hkdev_color( 'ac_input_color', esc_html__( 'Input Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-field input', 'color' );
		$this->hkdev_dimensions( 'ac_input_radius', esc_html__( 'Input Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-field input', 'border-radius' );
		$this->hkdev_typography( 'ac_submit', esc_html__( 'Submit Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-submit' );
		$this->hkdev_color( 'ac_submit_bg', esc_html__( 'Submit Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-submit', 'background-color' );
		$this->hkdev_color( 'ac_submit_color', esc_html__( 'Submit Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-submit', 'color' );
		$this->hkdev_dimensions( 'ac_submit_radius', esc_html__( 'Submit Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-submit', 'border-radius' );
		$this->hkdev_typography( 'ac_th', esc_html__( 'Table Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-orders-table th' );
		$this->hkdev_typography( 'ac_td', esc_html__( 'Table Cell', 'hkdev-shop-elements' ), $scope . ' .hkdev-account-orders-table td' );

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