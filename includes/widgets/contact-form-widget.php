<?php
/**
 * HKDEV Contact Form Widget (HKDEV Shop Elements plugin).
 *
 * Renders the custom contact form (info sidebar + social icons + form).
 * Self-contained – does not require the hkdev-shop theme.
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
 * Class Contact_Form_Widget
 */
class Contact_Form_Widget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_contact_form';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Contact Form', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		return [ 'contact', 'form', 'message', 'support' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-fontawesome' ];
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
				'raw'             => esc_html__( 'Contact information (phone, email, address, social links) is managed from the HKDEV Contact Form settings page (Contact Submissions → Settings) or the hkdev_cf_settings option.', 'hkdev-shop-elements' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();

		$this->register_cf_style_sections();
	}

	/**
	 * Style tab – panel, info list, form fields and submit button.
	 *
	 * @return void
	 */
	protected function register_cf_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-contact-container';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'cf_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'cf_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'cf_radius', esc_html__( 'Block Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );
		$this->hkdev_dimensions( 'cf_info_padding', esc_html__( 'Info Panel Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-contact-info', 'padding' );
		$this->hkdev_dimensions( 'cf_form_padding', esc_html__( 'Form Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-wrap', 'padding' );

		$this->end_controls_section();

		/* ---------------- Info panel ---------------- */
		$this->start_controls_section(
			'cf_style_panel',
			[
				'label' => esc_html__( 'Info Panel', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'cf_info_bg', esc_html__( 'Panel Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-contact-info', 'background-color' );
		$this->hkdev_typography( 'cf_info_title', esc_html__( 'Panel Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-info-header h3' );
		$this->hkdev_typography( 'cf_info_text', esc_html__( 'Info Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-info-item .info-text' );
		$this->hkdev_color( 'cf_icon_bg', esc_html__( 'Info Icon Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-info-item i', 'background-color' );
		$this->hkdev_color( 'cf_icon_color', esc_html__( 'Info Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-info-item i', 'color' );
		$this->hkdev_color( 'cf_social_bg', esc_html__( 'Social Icon Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-social-icons a', 'background-color' );
		$this->hkdev_color( 'cf_social_color', esc_html__( 'Social Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-social-icons a', 'color' );

		$this->end_controls_section();

		/* ---------------- Form ---------------- */
		$this->start_controls_section(
			'cf_style_form',
			[
				'label' => esc_html__( 'Form', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'cf_form_title', esc_html__( 'Form Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-wrap h3' );
		$this->hkdev_typography( 'cf_label', esc_html__( 'Field Label', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group label' );
		$this->hkdev_color( 'cf_input_bg', esc_html__( 'Input Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group input', 'background-color' );
		$this->hkdev_color( 'cf_input_border', esc_html__( 'Input Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group input', 'border-color' );
		$this->hkdev_color( 'cf_input_color', esc_html__( 'Input Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group input', 'color' );
		$this->hkdev_dimensions( 'cf_input_radius', esc_html__( 'Input Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group input', 'border-radius' );
		$this->hkdev_dimensions( 'cf_input_padding', esc_html__( 'Input Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-group input', 'padding' );
		$this->hkdev_typography( 'cf_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-btn' );
		$this->hkdev_color( 'cf_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-btn', 'background-color' );
		$this->hkdev_color( 'cf_btn_color', esc_html__( 'Button Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-btn', 'color' );
		$this->hkdev_color( 'cf_btn_hover', esc_html__( 'Button Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-btn:hover', 'background-color' );
		$this->hkdev_dimensions( 'cf_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-cf-btn', 'border-radius' );

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

		echo \HkdevShopElements\Includes\Contact_Form_Engine::instance()->contact_form_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
