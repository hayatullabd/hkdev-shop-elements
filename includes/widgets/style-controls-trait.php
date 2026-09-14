<?php
/**
 * Shared Elementor style controls for the HKDEV widgets.
 *
 * Design note: the plugin stylesheet uses `!important` so the widgets keep
 * their look with any theme. Elementor's group controls (typography /
 * background / border / box-shadow) cannot emit `!important`, so they would
 * lose that fight. Every helper here therefore writes a plain control whose
 * `selectors` string ends in `!important`, which makes the Elementor value win
 * (highest specificity) while the plugin's default look stays intact.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Trait Style_Controls
 */
trait Style_Controls {

	/**
	 * Merge an optional condition into a control definition.
	 *
	 * @param array $args      Control definition.
	 * @param array $condition Elementor condition.
	 * @return array
	 */
	private function hkdev_args( $args, $condition = [] ) {
		if ( ! empty( $condition ) ) {
			$args['condition'] = $condition;
		}

		return $args;
	}

	/**
	 * Colour control writing one CSS declaration.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_color( $id, $label, $selector, $property = 'color', $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'     => $label,
					'type'      => Controls_Manager::COLOR,
					'selectors' => [ $selector => $property . ': {{VALUE}} !important;' ],
				],
				$condition
			)
		);
	}

	/**
	 * Slider control producing a value + unit declaration.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param int    $min       Minimum.
	 * @param int    $max       Maximum.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_slider( $id, $label, $selector, $property, $min, $max, $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'      => $label,
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ 'px', 'em', 'rem' ],
					'range'      => [
						'px'  => [
							'min' => $min,
							'max' => $max,
						],
						'em'  => [
							'min' => 0.1,
							'max' => 10,
						],
						'rem' => [
							'min' => 0.1,
							'max' => 10,
						],
					],
					'selectors'  => [ $selector => $property . ': {{SIZE}}{{UNIT}} !important;' ],
				],
				$condition
			)
		);
	}

	/**
	 * Slider control producing a unit-less declaration (e.g. line-height).
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param float  $min       Minimum.
	 * @param float  $max       Maximum.
	 * @param float  $step      Step.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_slider_raw( $id, $label, $selector, $property, $min, $max, $step = 0.1, $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'     => $label,
					'type'      => Controls_Manager::SLIDER,
					'range'     => [
						'px' => [
							'min'  => $min,
							'max'  => $max,
							'step' => $step,
						],
					],
					'selectors' => [ $selector => $property . ': {{SIZE}} !important;' ],
				],
				$condition
			)
		);
	}

	/**
	 * Select control writing the chosen value directly.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param array  $options   Value => label pairs.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_select( $id, $label, $selector, $property, $options, $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'     => $label,
					'type'      => Controls_Manager::SELECT,
					'options'   => $options,
					'selectors' => [ $selector => $property . ': {{VALUE}} !important;' ],
				],
				$condition
			)
		);
	}

	/**
	 * Dimensions control writing a padding / margin / radius shorthand.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_dimensions( $id, $label, $selector, $property = 'padding', $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'      => $label,
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors'  => [
						$selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
					],
				],
				$condition
			)
		);
	}

	/**
	 * Box-shadow control.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_shadow( $id, $label, $selector, $condition = [] ) {
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'     => $label,
					'type'      => Controls_Manager::BOX_SHADOW,
					'selectors' => [
						$selector => 'box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} !important;',
					],
				],
				$condition
			)
		);
	}

	/**
	 * Colour + font-size + weight + line-height + letter-spacing + transform.
	 *
	 * @param string $id        Base control id.
	 * @param string $label     Group label.
	 * @param string $selector  CSS selector.
	 * @param array  $condition Elementor condition.
	 * @return void
	 */
	protected function hkdev_typography( $id, $label, $selector, $condition = [] ) {
		$this->hkdev_color( $id . '_color', $label . ' — Colour', $selector, 'color', $condition );

		$this->add_control(
			$id . '_size',
			$this->hkdev_args(
				[
					'label'      => $label . ' — Font Size',
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ 'px', 'em', 'rem' ],
					'range'      => [
						'px'  => [
							'min' => 8,
							'max' => 80,
						],
						'em'  => [
							'min' => 0.5,
							'max' => 5,
						],
						'rem' => [
							'min' => 0.5,
							'max' => 5,
						],
					],
					'selectors'  => [ $selector => 'font-size: {{SIZE}}{{UNIT}} !important;' ],
				],
				$condition
			)
		);

		$this->add_control(
			$id . '_weight',
			$this->hkdev_args(
				[
					'label'     => $label . ' — Font Weight',
					'type'      => Controls_Manager::SELECT,
					'options'   => [
						''    => esc_html__( 'Default', 'hkdev-shop-elements' ),
						'300' => '300',
						'400' => '400',
						'500' => '500',
						'600' => '600',
						'700' => '700',
						'800' => '800',
						'900' => '900',
					],
					'selectors' => [ $selector => 'font-weight: {{VALUE}} !important;' ],
				],
				$condition
			)
		);

		$this->hkdev_slider_raw( $id . '_lh', $label . ' — Line Height', $selector, 'line-height', 0.8, 3, 0.05, $condition );
		$this->hkdev_slider( $id . '_ls', $label . ' — Letter Spacing', $selector, 'letter-spacing', -3, 12, $condition );

		$this->add_control(
			$id . '_tt',
			$this->hkdev_args(
				[
					'label'     => $label . ' — Text Transform',
					'type'      => Controls_Manager::SELECT,
					'options'   => [
						''           => esc_html__( 'Default', 'hkdev-shop-elements' ),
						'none'       => esc_html__( 'None', 'hkdev-shop-elements' ),
						'uppercase'  => esc_html__( 'Uppercase', 'hkdev-shop-elements' ),
						'lowercase'  => esc_html__( 'Lowercase', 'hkdev-shop-elements' ),
						'capitalize' => esc_html__( 'Capitalize', 'hkdev-shop-elements' ),
					],
					'selectors' => [ $selector => 'text-transform: {{VALUE}} !important;' ],
				],
				$condition
			)
		);
	}

	/**
	 * WooCommerce image size options.
	 *
	 * @return array
	 */
	protected function hkdev_image_size_options() {
		return [
			'woocommerce_thumbnail' => esc_html__( 'WooCommerce Thumbnail (300px)', 'hkdev-shop-elements' ),
			'woocommerce_single'    => esc_html__( 'WooCommerce Single (600px)', 'hkdev-shop-elements' ),
			'thumbnail'             => esc_html__( 'Thumbnail', 'hkdev-shop-elements' ),
			'medium'                => esc_html__( 'Medium', 'hkdev-shop-elements' ),
			'medium_large'          => esc_html__( 'Medium Large', 'hkdev-shop-elements' ),
			'large'                 => esc_html__( 'Large', 'hkdev-shop-elements' ),
			'full'                  => esc_html__( 'Full (original)', 'hkdev-shop-elements' ),
		];
	}
}
