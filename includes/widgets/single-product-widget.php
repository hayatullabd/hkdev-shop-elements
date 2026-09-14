<?php
/**
 * HKDEV Single Product Widget (HKDEV Shop Elements plugin).
 *
 * Renders the full custom single product layout (gallery, price, variants,
 * size chart, quantity, AJAX add-to-cart, tabs, meta). Self-contained – does
 * not require the hkdev-shop theme.
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
 * Class Single_Product_Widget
 */
class Single_Product_Widget extends Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_single_product';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Single Product', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-single-product';
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
		return [ 'product', 'single', 'detail', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-single-product-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-single-product-js' ];
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
				'label' => esc_html__( 'Product', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'id',
			[
				'label'       => esc_html__( 'Product ID (optional)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to use the product from the current page. On a single product page just drop this widget anywhere.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_contact',
			[
				'label' => esc_html__( 'Order Buttons & Info', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_whatsapp',
			[
				'label'        => esc_html__( 'Show WhatsApp Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'whatsapp',
			[
				'label'       => esc_html__( 'WhatsApp Number', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '88017XXXXXXXX',
				'description' => esc_html__( 'Leave empty to hide the WhatsApp button.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_whatsapp' => 'yes' ],
			]
		);

		$this->add_control(
			'show_call',
			[
				'label'        => esc_html__( 'Show Call Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'phone',
			[
				'label'       => esc_html__( 'Phone Number', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '01XXXXXXXXX',
				'description' => esc_html__( 'Leave empty to hide the Call button.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_call' => 'yes' ],
			]
		);

		$this->add_control(
			'show_brand',
			[
				'label'        => esc_html__( 'Show Brand', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_category',
			[
				'label'        => esc_html__( 'Show Category', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

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

		$atts = [
			'phone'         => isset( $settings['phone'] ) ? $settings['phone'] : '',
			'whatsapp'      => isset( $settings['whatsapp'] ) ? $settings['whatsapp'] : '',
			'show_whatsapp' => ( isset( $settings['show_whatsapp'] ) && 'yes' === $settings['show_whatsapp'] ) ? 'yes' : 'no',
			'show_call'     => ( isset( $settings['show_call'] ) && 'yes' === $settings['show_call'] ) ? 'yes' : 'no',
			'show_brand'    => ( isset( $settings['show_brand'] ) && 'yes' === $settings['show_brand'] ) ? 'yes' : 'no',
			'show_category' => ( isset( $settings['show_category'] ) && 'yes' === $settings['show_category'] ) ? 'yes' : 'no',
		];

		if ( isset( $settings['id'] ) && ! empty( $settings['id'] ) ) {
			$atts['id'] = absint( $settings['id'] );
		}

		echo \HkdevShopElements\Includes\Single_Product_Engine::instance()->custom_single_product_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
