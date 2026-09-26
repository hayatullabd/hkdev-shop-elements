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
	 * @param float  $step        Step.
	 * @param array  $condition   Elementor condition.
	 * @param string $description Optional help text under the control.
	 * @return void
	 */
	protected function hkdev_slider_raw( $id, $label, $selector, $property, $min, $max, $step = 0.1, $condition = [], $description = '' ) {
		$args = [
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
		];

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		$this->add_control( $id, $this->hkdev_args( $args, $condition ) );
	}

	/**
	 * Slider control producing a transform: scale(...) declaration.
	 *
	 * @param string $id          Control id.
	 * @param string $label       Label.
	 * @param string $selector    CSS selector.
	 * @param float  $min         Minimum scale.
	 * @param float  $max         Maximum scale.
	 * @param float  $step        Step.
	 * @param float  $default     Default scale.
	 * @param array  $condition   Elementor condition.
	 * @param string $description Optional help text.
	 * @return void
	 */
	protected function hkdev_scale( $id, $label, $selector, $min, $max, $step = 0.01, $default = 1, $condition = [], $description = '' ) {
		$args = [
			'label'     => $label,
			'type'      => Controls_Manager::SLIDER,
			'range'     => [
				'px' => [
					'min'  => $min,
					'max'  => $max,
					'step' => $step,
				],
			],
			'default'   => [ 'size' => $default ],
			'selectors' => [ $selector => 'transform: scale({{SIZE}}) !important;' ],
		];

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		$this->add_control( $id, $this->hkdev_args( $args, $condition ) );
	}

	/**
	 * Slider control producing a transform: translateY(...) declaration.
	 *
	 * @param string $id          Control id.
	 * @param string $label       Label.
	 * @param string $selector    CSS selector.
	 * @param int    $min         Minimum translate in px.
	 * @param int    $max         Maximum translate in px.
	 * @param int    $default     Default translate in px.
	 * @param array  $condition   Elementor condition.
	 * @param string $description Optional help text.
	 * @return void
	 */
	protected function hkdev_translate_y( $id, $label, $selector, $min, $max, $default = 0, $condition = [], $description = '' ) {
		$args = [
			'label'     => $label,
			'type'      => Controls_Manager::SLIDER,
			'range'     => [
				'px' => [
					'min'  => $min,
					'max'  => $max,
					'step' => 1,
				],
			],
			'default'   => [ 'size' => $default ],
			'selectors' => [ $selector => 'transform: translateY({{SIZE}}px) !important;' ],
		];

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		$this->add_control( $id, $this->hkdev_args( $args, $condition ) );
	}

	/**
	 * Slider control producing a transition-duration value in seconds.
	 *
	 * @param string $id          Control id.
	 * @param string $label       Label.
	 * @param string $selector    CSS selector.
	 * @param float  $min         Minimum seconds.
	 * @param float  $max         Maximum seconds.
	 * @param float  $step        Step.
	 * @param float  $default     Default seconds.
	 * @param array  $condition   Elementor condition.
	 * @param string $description Optional help text.
	 * @return void
	 */
	protected function hkdev_transition_seconds( $id, $label, $selector, $min, $max, $step = 0.05, $default = 0.3, $condition = [], $description = '' ) {
		$args = [
			'label'     => $label,
			'type'      => Controls_Manager::SLIDER,
			'range'     => [
				'px' => [
					'min'  => $min,
					'max'  => $max,
					'step' => $step,
				],
			],
			'default'   => [ 'size' => $default ],
			'selectors' => [ $selector => 'transition-duration: {{SIZE}}s !important;' ],
		];

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		$this->add_control( $id, $this->hkdev_args( $args, $condition ) );
	}

	/**
	 * Select control writing the chosen value directly.
	 *
	 * @param string $id        Control id.
	 * @param string $label     Label.
	 * @param string $selector  CSS selector.
	 * @param string $property  CSS property.
	 * @param array  $options     Value => label pairs.
	 * @param array  $condition   Elementor condition.
	 * @param string $description Optional help text under the control.
	 * @return void
	 */
	protected function hkdev_select( $id, $label, $selector, $property, $options, $condition = [], $description = '' ) {
		$args = [
			'label'     => $label,
			'type'      => Controls_Manager::SELECT,
			'options'   => $options,
			'selectors' => [ $selector => $property . ': {{VALUE}} !important;' ],
		];

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		$this->add_control( $id, $this->hkdev_args( $args, $condition ) );
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
		// A plain text field is used on purpose: the dedicated box-shadow control
		// type is only meant to be used through Elementor's group control, and
		// using it standalone can break the editor panel.
		$this->add_control(
			$id,
			$this->hkdev_args(
				[
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'placeholder' => '0 10px 30px rgba(0, 0, 0, 0.12)',
					'description' => esc_html__( 'Standard CSS box-shadow value. Leave empty for the default.', 'hkdev-shop-elements' ),
					'selectors'   => [ $selector => 'box-shadow: {{VALUE}} !important;' ],
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

	/**
	 * Style sections for a product grid: layout, card, image, text, button and
	 * badges. Shared by every widget that renders the product card.
	 *
	 * @param string $scope        CSS scope of the widget wrapper (must contain {{WRAPPER}}).
	 * @return void
	 */
	protected function register_style_sections( $scope = '{{WRAPPER}} .hkdev-shop-wrapper' ) {
		/* ---------------- Layout & spacing ---------------- */
		$this->start_controls_section(
			'hkdev_style_layout',
			[
				'label' => esc_html__( 'Layout Design', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'sk_wrapper_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'sk_wrapper_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_slider( 'sk_grid_gap', esc_html__( 'Gap Between Cards', 'hkdev-shop-elements' ), $scope . ' .hkdev-shop-grid', 'gap', 0, 60 );
		$this->hkdev_color( 'sk_page_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );

		$this->end_controls_section();

		/* ---------------- Card ---------------- */
		$this->start_controls_section(
			'hkdev_style_card',
			[
				'label' => esc_html__( 'Product Card', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'sk_card_state_tabs' );

		$this->start_controls_tab(
			'sk_card_tab_normal',
			[
				'label' => esc_html__( 'Normal', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_color( 'sk_card_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'background-color' );
		$this->hkdev_color( 'sk_card_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-color' );
		$this->hkdev_slider( 'sk_card_border_w', esc_html__( 'Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-width', 0, 8 );
		$this->hkdev_dimensions( 'sk_card_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 'border-radius' );
		$this->hkdev_shadow( 'sk_card_shadow', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card' );
		$this->end_controls_tab();

		$this->start_controls_tab(
			'sk_card_tab_hover',
			[
				'label' => esc_html__( 'Hover', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_transition_seconds( 'sk_card_transition', esc_html__( 'Transition (s)', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card', 0, 1.5, 0.05, 0.3 );
		$this->hkdev_color( 'sk_card_bg_hover', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card:hover', 'background-color' );
		$this->hkdev_color( 'sk_card_border_hover', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card:hover', 'border-color' );
		$this->hkdev_shadow( 'sk_card_shadow_hover', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-product-card:hover' );
		$this->hkdev_translate_y(
			'sk_card_hover_lift',
			esc_html__( 'Lift (px)', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-product-card:hover',
			-30,
			0,
			-6,
			[],
			esc_html__( 'Negative values move the card upward on hover.', 'hkdev-shop-elements' )
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->hkdev_dimensions( 'sk_card_content_pad', esc_html__( 'Content Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-content-box', 'padding' );

		$this->end_controls_section();

		/* ---------------- Image ---------------- */
		$this->start_controls_section(
			'hkdev_style_image',
			[
				'label' => esc_html__( 'Image Styles', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_select(
			'sk_img_ratio',
			esc_html__( 'Image Shape', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-img-box img',
			'aspect-ratio',
			[
				''       => esc_html__( 'Default (square)', 'hkdev-shop-elements' ),
				'1 / 1'  => esc_html__( 'Square (1:1)', 'hkdev-shop-elements' ),
				'4 / 3'  => esc_html__( 'Landscape (4:3)', 'hkdev-shop-elements' ),
				'3 / 2'  => esc_html__( 'Landscape (3:2)', 'hkdev-shop-elements' ),
				'16 / 9' => esc_html__( 'Wide (16:9)', 'hkdev-shop-elements' ),
				'3 / 4'  => esc_html__( 'Portrait (3:4)', 'hkdev-shop-elements' ),
				'4 / 5'  => esc_html__( 'Portrait (4:5)', 'hkdev-shop-elements' ),
				'auto'   => esc_html__( 'Original', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_select(
			'sk_img_fit',
			esc_html__( 'Image Fit', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-img-box img',
			'object-fit',
			[
				'cover'   => esc_html__( 'Cover (crop)', 'hkdev-shop-elements' ),
				'contain' => esc_html__( 'Contain (whole image)', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_scale(
			'sk_img_zoom',
			esc_html__( 'Hover Zoom', 'hkdev-shop-elements' ),
			$scope . ' .hkdev-product-card:hover .hkdev-img-box img',
			1,
			1.4,
			0.01,
			1.08,
			[],
			esc_html__( '1 = no zoom, 1.08-1.15 gives a smooth premium hover effect.', 'hkdev-shop-elements' )
		);
		$this->hkdev_transition_seconds( 'sk_img_zoom_speed', esc_html__( 'Zoom Transition (s)', 'hkdev-shop-elements' ), $scope . ' .hkdev-img-box img', 0, 1.5, 0.05, 0.35 );
		$this->hkdev_dimensions( 'sk_img_radius', esc_html__( 'Image Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-img-box img', 'border-radius' );
		$this->hkdev_color( 'sk_img_box_bg', esc_html__( 'Image Area Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-img-box', 'background-color' );

		$this->end_controls_section();

		/* ---------------- Product categories (meta line) ---------------- */
		$this->start_controls_section(
			'hkdev_style_categories',
			[
				'label' => esc_html__( 'Product Categories', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_cat', esc_html__( 'Category / Brand', 'hkdev-shop-elements' ), $scope . ' .hkdev-cat-label' );

		$this->end_controls_section();

		/* ---------------- Product title ---------------- */
		$this->start_controls_section(
			'hkdev_style_title',
			[
				'label' => esc_html__( 'Product Title', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_title', esc_html__( 'Title Typography', 'hkdev-shop-elements' ), $scope . ' .hkdev-title' );
		$this->hkdev_color( 'sk_title_hover', esc_html__( 'Title Hover Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-title:hover', 'color' );
		$this->hkdev_slider_raw( 'sk_title_gap', esc_html__( 'Title Bottom Spacing (px)', 'hkdev-shop-elements' ), $scope . ' .hkdev-title', 'margin-bottom', 0, 30, 1 );

		$this->end_controls_section();

		/* ---------------- Product price ---------------- */
		$this->start_controls_section(
			'hkdev_style_price',
			[
				'label' => esc_html__( 'Product Price', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_price', esc_html__( 'Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-price-container .price .amount' );
		$this->hkdev_color( 'sk_price_old', esc_html__( 'Old (Struck) Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-price-container .price del .amount', 'color' );

		$this->end_controls_section();

		/* ---------------- Button ---------------- */
		$this->start_controls_section(
			'hkdev_style_button',
			[
				'label' => esc_html__( 'Add to Cart Button', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sk_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn' );
		$this->start_controls_tabs( 'sk_btn_state_tabs' );

		$this->start_controls_tab(
			'sk_btn_tab_normal',
			[
				'label' => esc_html__( 'Normal', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_color( 'sk_btn_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'background-color' );
		$this->hkdev_color( 'sk_btn_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'border-color' );
		$this->end_controls_tab();

		$this->start_controls_tab(
			'sk_btn_tab_hover',
			[
				'label' => esc_html__( 'Hover', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_color( 'sk_btn_bg_hover', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover', 'background-color' );
		$this->hkdev_color( 'sk_btn_color_hover', esc_html__( 'Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover', 'color' );
		$this->hkdev_color( 'sk_btn_border_hover', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover', 'border-color' );
		$this->end_controls_tab();

		$this->end_controls_tabs();
		$this->hkdev_transition_seconds( 'sk_btn_transition', esc_html__( 'Transition (s)', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 0, 1.5, 0.05, 0.25 );
		$this->hkdev_shadow( 'sk_btn_shadow', esc_html__( 'Normal Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn' );
		$this->hkdev_shadow( 'sk_btn_shadow_hover', esc_html__( 'Hover Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn:hover' );
		$this->hkdev_slider( 'sk_btn_height', esc_html__( 'Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'height', 30, 70 );
		$this->hkdev_dimensions( 'sk_btn_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'border-radius' );
		$this->hkdev_dimensions( 'sk_btn_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-order-btn', 'padding' );

		$this->end_controls_section();

		/* ---------------- Badges ---------------- */
		$this->start_controls_section(
			'hkdev_style_badges',
			[
				'label' => esc_html__( 'Badges', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'sk_sale_bg', esc_html__( 'Sale Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sale-badge', 'background-color' );
		$this->hkdev_color( 'sk_sale_color', esc_html__( 'Sale Badge Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-sale-badge', 'color' );
		$this->hkdev_color( 'sk_trend_bg', esc_html__( 'Trending Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-trending-badge', 'background-color' );
		$this->hkdev_color( 'sk_best_bg', esc_html__( 'Best Seller Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-best-seller-badge', 'background-color' );

		$this->end_controls_section();
	}

	/**
	 * Style controls for carousel navigation and dots.
	 *
	 * @param string $scope CSS scope of the widget wrapper.
	 * @return void
	 */
	protected function register_carousel_style_controls( $scope = '{{WRAPPER}} .hkdev-shop-wrapper' ) {
		$this->start_controls_section(
			'hkdev_style_carousel_nav',
			[
				'label'     => esc_html__( 'Carousel Navigation', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'style' => 'carousel' ],
			]
		);

		$this->hkdev_slider( 'sk_car_nav_size', esc_html__( 'Arrow Button Size', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'width', 28, 72 );
		$this->hkdev_slider( 'sk_car_nav_height', esc_html__( 'Arrow Button Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'height', 28, 72 );
		$this->hkdev_slider( 'sk_car_nav_icon_size', esc_html__( 'Arrow Icon Size', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn svg', 'width', 10, 36 );
		$this->hkdev_dimensions( 'sk_car_nav_radius', esc_html__( 'Arrow Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'border-radius' );
		$this->hkdev_color( 'sk_car_nav_bg', esc_html__( 'Arrow Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'background-color' );
		$this->hkdev_color( 'sk_car_nav_color', esc_html__( 'Arrow Icon Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'color' );
		$this->hkdev_color( 'sk_car_nav_border', esc_html__( 'Arrow Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn', 'border-color' );
		$this->hkdev_shadow( 'sk_car_nav_shadow', esc_html__( 'Arrow Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn' );

		$this->add_control(
			'sk_car_nav_hover_heading',
			[
				'label'     => esc_html__( 'Arrow Hover', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->hkdev_color( 'sk_car_nav_bg_hover', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn:hover', 'background-color' );
		$this->hkdev_color( 'sk_car_nav_color_hover', esc_html__( 'Hover Icon Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn:hover', 'color' );
		$this->hkdev_color( 'sk_car_nav_border_hover', esc_html__( 'Hover Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-nav-btn:hover', 'border-color' );

		$this->add_control(
			'sk_car_dots_heading',
			[
				'label'     => esc_html__( 'Pagination Dots', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		$this->hkdev_color( 'sk_car_dots_wrap_bg', esc_html__( 'Dots Wrapper Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots', 'background-color' );
		$this->hkdev_shadow( 'sk_car_dots_wrap_shadow', esc_html__( 'Dots Wrapper Shadow', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots' );
		$this->hkdev_color( 'sk_car_dot_bg', esc_html__( 'Dot Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots .swiper-pagination-bullet', 'background-color' );
		$this->hkdev_color( 'sk_car_dot_active_bg', esc_html__( 'Active Dot Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots .swiper-pagination-bullet-active', 'background-color' );
		$this->hkdev_slider( 'sk_car_dot_size', esc_html__( 'Dot Size', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots .swiper-pagination-bullet', 'width', 4, 18 );
		$this->hkdev_slider( 'sk_car_dot_size_h', esc_html__( 'Dot Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots .swiper-pagination-bullet', 'height', 4, 18 );
		$this->hkdev_slider( 'sk_car_dot_active_w', esc_html__( 'Active Dot Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-carousel-dots .swiper-pagination-bullet-active', 'width', 8, 40 );

		$this->end_controls_section();
	}
}
