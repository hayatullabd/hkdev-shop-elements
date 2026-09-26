<?php
/**
 * HKDEV Checkout Widget (HKDEV Shop Elements plugin).
 *
 * Renders the full custom single-page checkout. Self-contained – does not
 * require the hkdev-shop theme.
 *
 * The widget also ships a few ready-made checkout styles ("Classic", "Modern",
 * "Compact"). Picking one restyles the whole checkout page – no per-element
 * configuration needed.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Core\CheckoutEngine;

/**
 * Class Checkout_Widget
 */
class Checkout_Widget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_checkout';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Checkout', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-checkout';
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
		return [ 'checkout', 'woocommerce', 'order' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-checkout-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-checkout-js' ];
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
			'style_preset',
			[
				'label'   => esc_html__( 'Checkout Style', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'classic',
				'options' => [
					'classic' => esc_html__( 'Classic (Default)', 'hkdev-shop-elements' ),
					'modern'  => esc_html__( 'Modern (Green Gradient)', 'hkdev-shop-elements' ),
					'compact' => esc_html__( 'Compact (Tight Spacing)', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'This widget renders the full checkout page. Place it on the WooCommerce Checkout page and keep only this widget on that page. Manage which fields are shown (on/off) from WP Admin → HKDEV Shop → Checkout Fields.', 'hkdev-shop-elements' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();

		$this->register_co_style_sections();
	}

	/**
	 * Style tab – container, cards, fields, options, totals, coupon and button.
	 *
	 * @return void
	 */
	protected function register_co_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-co-container';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'co_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'co_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'co_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_color( 'co_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_slider( 'co_grid_gap', esc_html__( 'Column Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-checkout-grid', 'gap', 0, 80 );

		$this->end_controls_section();

		/* ---------------- Section cards ---------------- */
		$this->start_controls_section(
			'co_style_cards',
			[
				'label' => esc_html__( 'Section Cards', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'co_card_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card', 'background-color' );
		$this->hkdev_color( 'co_card_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card', 'border-color' );
		$this->hkdev_slider( 'co_card_border_w', esc_html__( 'Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card', 'border-width', 0, 6 );
		$this->hkdev_dimensions( 'co_card_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card', 'border-radius' );
		$this->hkdev_dimensions( 'co_card_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card', 'padding' );
		$this->hkdev_shadow( 'co_card_shadow', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-section-card' );
		$this->hkdev_typography( 'co_card_title', esc_html__( 'Card Heading', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-card-header h3' );
		$this->hkdev_color( 'co_icon_bg', esc_html__( 'Step Icon Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-step-icon', 'background-color' );
		$this->hkdev_color( 'co_icon_color', esc_html__( 'Step Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-step-icon', 'color' );
		$this->hkdev_dimensions( 'co_icon_radius', esc_html__( 'Step Icon Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-step-icon', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Form fields ---------------- */
		$this->start_controls_section(
			'co_style_fields',
			[
				'label' => esc_html__( 'Form Fields', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'co_label', esc_html__( 'Field Label', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group label' );
		$this->hkdev_color( 'co_input_bg', esc_html__( 'Input Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group input', 'background-color' );
		$this->hkdev_color( 'co_input_border', esc_html__( 'Input Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group input', 'border-color' );
		$this->hkdev_dimensions( 'co_input_radius', esc_html__( 'Input Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group input', 'border-radius' );
		$this->hkdev_color( 'co_input_color', esc_html__( 'Input Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group input', 'color' );
		$this->hkdev_dimensions( 'co_input_padding', esc_html__( 'Input Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-form-group input', 'padding' );

		$this->end_controls_section();

		/* ---------------- Payment & shipping options ---------------- */
		$this->start_controls_section(
			'co_style_options',
			[
				'label' => esc_html__( 'Payment & Shipping Options', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'co_opt_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row, ' . $scope . ' .hkdev-co-pay-pill-box', 'background-color' );
		$this->hkdev_color( 'co_opt_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row, ' . $scope . ' .hkdev-co-pay-pill-box', 'border-color' );
		$this->hkdev_dimensions( 'co_opt_radius', esc_html__( 'Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row, ' . $scope . ' .hkdev-co-pay-pill-box', 'border-radius' );
		$this->hkdev_dimensions( 'co_opt_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row, ' . $scope . ' .hkdev-co-pay-pill-box', 'padding' );
		$this->hkdev_color( 'co_opt_active_border', esc_html__( 'Selected Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row.active, ' . $scope . ' .hkdev-co-pay-pill-box.active', 'border-color' );
		$this->hkdev_color( 'co_opt_active_bg', esc_html__( 'Selected Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-radio-row.active, ' . $scope . ' .hkdev-co-pay-pill-box.active', 'background-color' );

		$this->end_controls_section();

		/* ---------------- Totals ---------------- */
		$this->start_controls_section(
			'co_style_totals',
			[
				'label' => esc_html__( 'Order Totals', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'co_calc', esc_html__( 'Total Lines', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-calc-line' );
		$this->hkdev_slider( 'co_calc_pad', esc_html__( 'Line Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-calc-line', 'padding-top', 0, 40 );
		$this->hkdev_typography( 'co_grand', esc_html__( 'Grand Total', 'hkdev-shop-elements' ), $scope . ' .grand-total-line .total-amt' );

		$this->end_controls_section();

		/* ---------------- Coupon ---------------- */
		$this->start_controls_section(
			'co_style_coupon',
			[
				'label' => esc_html__( 'Coupon', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'co_cp_bg', esc_html__( 'Field Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box', 'background-color' );
		$this->hkdev_color( 'co_cp_border', esc_html__( 'Field Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box', 'border-color' );
		$this->hkdev_dimensions( 'co_cp_radius', esc_html__( 'Field Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box', 'border-radius' );
		$this->hkdev_color( 'co_cp_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box button', 'background-color' );
		$this->hkdev_color( 'co_cp_btn_color', esc_html__( 'Button Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box button', 'color' );
		$this->hkdev_color( 'co_cp_btn_bg_hover', esc_html__( 'Button Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-coupon-box button:hover', 'background-color' );

		$this->end_controls_section();

		/* ---------------- Confirm button ---------------- */
		$this->start_controls_section(
			'co_style_button',
			[
				'label' => esc_html__( 'Confirm Order Button', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'co_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn' );
		$this->hkdev_color( 'co_btn_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn', 'background-color' );
		$this->hkdev_color( 'co_btn_color', esc_html__( 'Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn', 'color' );
		$this->hkdev_color( 'co_btn_bg_hover', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn:hover', 'background-color' );
		$this->hkdev_dimensions( 'co_btn_radius', esc_html__( 'Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn', 'border-radius' );
		$this->hkdev_dimensions( 'co_btn_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-confirm-btn', 'padding' );

		$this->end_controls_section();

		/* ---------------- Terms ---------------- */
		$this->start_controls_section(
			'co_style_terms',
			[
				'label' => esc_html__( 'Terms Text', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'co_terms', esc_html__( 'Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-terms-label' );
		$this->hkdev_color( 'co_terms_link', esc_html__( 'Link Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-co-terms-label a', 'color' );

		$this->end_controls_section();
	}

	/**
	 * Preset CSS for the selected checkout style.
	 *
	 * @param string $preset Preset key.
	 * @return string
	 */
	private function preset_css( $preset ) {
		$scope = '.hkdev-co-container';

		if ( 'modern' === $preset ) {
			return '/* Modern */
			' . $scope . ' .hkdev-co-section-card{border-radius:28px!important;border-color:rgba(3,165,80,.16)!important;box-shadow:0 18px 45px rgba(3,165,80,.10)!important}
			' . $scope . ' .hkdev-co-card-header{border-bottom:2px solid rgba(3,165,80,.14)!important}
			' . $scope . ' .hkdev-co-step-icon{background:linear-gradient(135deg,#03a550,#04c463)!important;color:#fff!important}
			' . $scope . ' .hkdev-co-form-group input,' . $scope . ' .hkdev-co-form-group textarea,' . $scope . ' .hkdev-co-form-group select{border-radius:14px!important}
			' . $scope . ' .hkdev-co-radio-row,' . $scope . ' .hkdev-co-pay-pill-box{border-radius:14px!important}
			' . $scope . ' .hkdev-co-confirm-btn{background:linear-gradient(135deg,#03a550,#04c463)!important;border-radius:16px!important;box-shadow:0 12px 28px rgba(3,165,80,.30)!important}
			' . $scope . ' .grand-total-line .total-amt{background:linear-gradient(135deg,#03a550,#04c463);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}';
		}

		if ( 'compact' === $preset ) {
			return '/* Compact */
			' . $scope . ' .hkdev-co-checkout-grid{gap:18px!important}
			' . $scope . ' .hkdev-co-section-card{padding:20px!important;border-radius:16px!important;box-shadow:0 4px 14px rgba(0,0,0,.04)!important}
			' . $scope . ' .hkdev-co-card-header{margin-bottom:16px!important;padding-bottom:10px!important}
			' . $scope . ' .hkdev-co-form-group.form-row{margin-bottom:12px!important}
			' . $scope . ' .hkdev-co-form-group input,' . $scope . ' .hkdev-co-form-group textarea,' . $scope . ' .hkdev-co-form-group select{padding:11px 13px!important;font-size:14px!important;border-radius:10px!important}
			' . $scope . ' .hkdev-co-radio-row,' . $scope . ' .hkdev-co-pay-pill-box{padding:12px 14px!important;margin-bottom:8px!important;border-radius:10px!important}
			' . $scope . ' .hkdev-co-calc-line{padding:7px 0!important;font-size:14px!important}
			' . $scope . ' .grand-total-line .total-amt{font-size:22px!important}
			' . $scope . ' .hkdev-co-confirm-btn{padding:14px!important;font-size:15px!important;border-radius:12px!important}';
		}

		return '';
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

		$preset = (string) $this->get_settings_for_display( 'style_preset' );
		$css    = $this->preset_css( $preset );

		if ( '' !== $css ) {
			echo '<style id="hkdev-co-style-preset">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo CheckoutEngine::instance()->custom_checkout_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
