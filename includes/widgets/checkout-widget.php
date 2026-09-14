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
use HkdevShopElements\Includes\Checkout_Engine;

/**
 * Class Checkout_Widget
 */
class Checkout_Widget extends Widget_Base {

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

		echo Checkout_Engine::instance()->custom_checkout_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
