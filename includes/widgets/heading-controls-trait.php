<?php
/**
 * Shared "Section Heading" controls for the heading widgets.
 *
 * Gives every product widget an optional heading bar (accent bar + heading +
 * optional subtitle + "View All" link) with a full set of pixel-perfect
 * controls: show/hide, box chrome, accent bar, typography for heading /
 * subtitle / link, link hover colour and hover background.
 *
 * The markup is rendered by Shop_Engine::shop_heading_html() so the Shop Grid,
 * Trending and Section Heading widgets all stay visually identical.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Trait Heading_Controls
 */
trait Heading_Controls {

	/**
	 * Register the heading content + style control sections.
	 *
	 * @return void
	 */
	protected function register_heading_controls() {
		$this->register_heading_content_controls();
		$this->register_heading_box_controls();
		$this->register_heading_text_controls();
		$this->register_heading_link_controls();
	}

	/**
	 * Content tab – heading text and link.
	 *
	 * @return void
	 */
	protected function register_heading_content_controls() {
		$this->start_controls_section(
			'section_heading',
			[
				'label' => esc_html__( 'Heading', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_heading',
			[
				'label'        => esc_html__( 'Show Heading', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'heading_text',
			[
				'label'       => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'e.g. Best Selling', 'hkdev-shop-elements' ),
				'label_block' => true,
				'description' => esc_html__( 'Leave empty to hide the heading.', 'hkdev-shop-elements' ),
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_tag',
			[
				'label'     => esc_html__( 'Heading HTML Tag', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_sub',
			[
				'label'       => esc_html__( 'Subtitle', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Optional small line under the heading', 'hkdev-shop-elements' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_link_text',
			[
				'label'       => esc_html__( 'Link Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'e.g. View All', 'hkdev-shop-elements' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
				'separator'   => 'before',
				'condition'   => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_link',
			[
				'label'       => esc_html__( 'Link', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://example.com/shop/', 'hkdev-shop-elements' ),
				'options'     => [ 'url', 'is_external', 'nofollow' ],
				'default'     => [ 'url' => '' ],
				'dynamic'     => [ 'active' => true ],
				'condition'   => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_link_arrow',
			[
				'label'        => esc_html__( 'Show Arrow Icon', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [ 'show_heading' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab – heading box chrome + accent bar.
	 *
	 * @return void
	 */
	protected function register_heading_box_controls() {
		$this->start_controls_section(
			'section_heading_box',
			[
				'label'     => esc_html__( 'Heading Box', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_boxed',
			[
				'label'        => esc_html__( 'Boxed Style', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'heading_bg',
			[
				'label'     => esc_html__( 'Background', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => [ 'heading_boxed' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_border',
			[
				'label'     => esc_html__( 'Border Color', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0, 0, 0, 0.09)',
				'condition' => [ 'heading_boxed' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_border_width',
			[
				'label'      => esc_html__( 'Border Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 8,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 1,
				],
				'condition'  => [ 'heading_boxed' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 8,
				],
				'condition'  => [ 'heading_boxed' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_padding',
			[
				'label'      => esc_html__( 'Padding', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default'    => [
					'top'    => 11,
					'right'  => 18,
					'bottom' => 11,
					'left'   => 18,
					'unit'   => 'px',
				],
				'condition'  => [ 'heading_boxed' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_gap',
			[
				'label'      => esc_html__( 'Spacing Below', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 18,
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'heading_accent',
			[
				'label'        => esc_html__( 'Accent Bar', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'heading_accent_color',
			[
				'label'     => esc_html__( 'Accent Color', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#03a550',
				'condition' => [ 'heading_accent' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_accent_width',
			[
				'label'      => esc_html__( 'Accent Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 1,
						'max' => 12,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 5,
				],
				'condition'  => [ 'heading_accent' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_accent_height',
			[
				'label'      => esc_html__( 'Accent Height', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 60,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 18,
				],
				'condition'  => [ 'heading_accent' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_accent_radius',
			[
				'label'      => esc_html__( 'Accent Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 20,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 999,
				],
				'condition'  => [ 'heading_accent' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab – heading + subtitle typography.
	 *
	 * @return void
	 */
	protected function register_heading_text_controls() {
		$this->start_controls_section(
			'section_heading_text',
			[
				'label'     => esc_html__( 'Heading Text', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_color',
			[
				'label'   => esc_html__( 'Heading Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#f06724',
			]
		);

		$this->add_control(
			'heading_size',
			[
				'label'      => esc_html__( 'Heading Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 10,
						'max' => 60,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 18,
				],
			]
		);

		$this->add_control(
			'heading_weight',
			[
				'label'   => esc_html__( 'Font Weight', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '700',
				'options' => [
					'400' => esc_html__( 'Normal (400)', 'hkdev-shop-elements' ),
					'500' => '500',
					'600' => '600',
					'700' => esc_html__( 'Bold (700)', 'hkdev-shop-elements' ),
					'800' => '800',
					'900' => '900',
				],
			]
		);

		$this->add_control(
			'heading_line_height',
			[
				'label'      => esc_html__( 'Line Height', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min'  => 0.8,
						'max'  => 3,
						'step' => 0.05,
					],
				],
				'default'    => [
					'size' => 1.3,
				],
			]
		);

		$this->add_control(
			'heading_letter_spacing',
			[
				'label'      => esc_html__( 'Letter Spacing (px)', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => -2,
						'max'  => 10,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$this->add_control(
			'heading_transform',
			[
				'label'   => esc_html__( 'Text Transform', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''           => esc_html__( 'Default', 'hkdev-shop-elements' ),
					'none'       => esc_html__( 'None', 'hkdev-shop-elements' ),
					'uppercase'  => esc_html__( 'Uppercase', 'hkdev-shop-elements' ),
					'lowercase'  => esc_html__( 'Lowercase', 'hkdev-shop-elements' ),
					'capitalize' => esc_html__( 'Capitalize', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'heading_sub_heading',
			[
				'label'     => esc_html__( 'Subtitle', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'heading_sub_color',
			[
				'label'   => esc_html__( 'Subtitle Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#6b7280',
			]
		);

		$this->add_control(
			'heading_sub_size',
			[
				'label'      => esc_html__( 'Subtitle Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 30,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 13,
				],
			]
		);

		$this->add_control(
			'heading_sub_weight',
			[
				'label'   => esc_html__( 'Subtitle Weight', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '500',
				'options' => [
					'400' => esc_html__( 'Normal (400)', 'hkdev-shop-elements' ),
					'500' => '500',
					'600' => '600',
					'700' => esc_html__( 'Bold (700)', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'heading_sub_line_height',
			[
				'label'   => esc_html__( 'Subtitle Line Height', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [
					'px' => [
						'min'  => 0.8,
						'max'  => 3,
						'step' => 0.05,
					],
				],
				'default' => [
					'size' => 1.35,
				],
			]
		);

		$this->add_control(
			'heading_sub_letter_spacing',
			[
				'label'      => esc_html__( 'Subtitle Letter Spacing (px)', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => -2,
						'max'  => 10,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$this->add_control(
			'heading_sub_transform',
			[
				'label'   => esc_html__( 'Subtitle Text Transform', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''           => esc_html__( 'Default', 'hkdev-shop-elements' ),
					'none'       => esc_html__( 'None', 'hkdev-shop-elements' ),
					'uppercase'  => esc_html__( 'Uppercase', 'hkdev-shop-elements' ),
					'lowercase'  => esc_html__( 'Lowercase', 'hkdev-shop-elements' ),
					'capitalize' => esc_html__( 'Capitalize', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab – "View All" link styling including hover states.
	 *
	 * @return void
	 */
	protected function register_heading_link_controls() {
		$this->start_controls_section(
			'section_heading_link',
			[
				'label'     => esc_html__( 'View All Link', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'heading_link_color',
			[
				'label'   => esc_html__( 'Link Color', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '#f06724',
			]
		);

		$this->add_control(
			'heading_link_hover_color',
			[
				'label'       => esc_html__( 'Link Hover Color', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to keep the link color on hover.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'heading_link_hover',
			[
				'label'   => esc_html__( 'Link Hover Background', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::COLOR,
				'default' => 'rgba(240, 103, 36, 0.10)',
			]
		);

		$this->add_control(
			'heading_link_size',
			[
				'label'      => esc_html__( 'Link Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 30,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 13,
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'heading_link_weight',
			[
				'label'   => esc_html__( 'Link Font Weight', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '600',
				'options' => [
					'400' => esc_html__( 'Normal (400)', 'hkdev-shop-elements' ),
					'500' => '500',
					'600' => '600',
					'700' => esc_html__( 'Bold (700)', 'hkdev-shop-elements' ),
					'800' => '800',
				],
			]
		);

		$this->add_control(
			'heading_link_letter_spacing',
			[
				'label'      => esc_html__( 'Link Letter Spacing (px)', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => -2,
						'max'  => 10,
						'step' => 0.1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$this->add_control(
			'heading_link_transform',
			[
				'label'   => esc_html__( 'Link Text Transform', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''           => esc_html__( 'Default', 'hkdev-shop-elements' ),
					'none'       => esc_html__( 'None', 'hkdev-shop-elements' ),
					'uppercase'  => esc_html__( 'Uppercase', 'hkdev-shop-elements' ),
					'lowercase'  => esc_html__( 'Lowercase', 'hkdev-shop-elements' ),
					'capitalize' => esc_html__( 'Capitalize', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'heading_link_decoration',
			[
				'label'   => esc_html__( 'Link Underline', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'            => esc_html__( 'None', 'hkdev-shop-elements' ),
					'underline'       => esc_html__( 'Underline', 'hkdev-shop-elements' ),
					'underline-hover' => esc_html__( 'Underline on Hover', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'heading_link_padding',
			[
				'label'      => esc_html__( 'Link Padding', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default'    => [
					'top'    => 6,
					'right'  => 10,
					'bottom' => 6,
					'left'   => 10,
					'unit'   => 'px',
				],
				'separator'  => 'before',
			]
		);

		$this->add_control(
			'heading_link_radius',
			[
				'label'      => esc_html__( 'Link Radius', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 7,
				],
			]
		);

		$this->add_control(
			'heading_link_icon',
			[
				'label'      => esc_html__( 'Arrow Icon Size', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 6,
						'max' => 30,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 11,
				],
			]
		);

		$this->add_control(
			'heading_link_gap',
			[
				'label'      => esc_html__( 'Arrow Spacing', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 24,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 6,
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Build the heading config passed to Shop_Engine::shop_heading_html().
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	protected function get_heading_config( $settings ) {
		$is_yes = static function ( $key, $default = 'no' ) use ( $settings ) {
			if ( ! isset( $settings[ $key ] ) ) {
				return $default;
			}
			return ( 'yes' === $settings[ $key ] ) ? 'yes' : 'no';
		};

		// Slider value with a unit (e.g. 18px).
		$px = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return round( (float) $settings[ $key ]['size'], 2 ) . 'px';
			}
			return $fallback;
		};

		// Slider value without a unit (e.g. line-height).
		$num = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return (string) round( (float) $settings[ $key ]['size'], 2 );
			}
			return (string) $fallback;
		};

		$color = static function ( $key, $fallback ) use ( $settings ) {
			return ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? $settings[ $key ] : $fallback;
		};

		$choice = static function ( $key, $allowed, $fallback ) use ( $settings ) {
			$value = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			return ( '' !== $value && in_array( $value, $allowed, true ) ) ? $value : $fallback;
		};

		// Dimensions control -> CSS shorthand (optionally negated for margins).
		$dim = static function ( $key, $fallback, $negative = false ) use ( $settings ) {
			$value = ( isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) ? $settings[ $key ] : [];
			$out   = [];
			foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
				if ( isset( $value[ $side ] ) && '' !== $value[ $side ] ) {
					$px    = round( (float) $value[ $side ], 2 );
					$out[] = $negative ? '-' . $px . 'px' : $px . 'px';
				} else {
					$out[] = $fallback[ $side ];
				}
			}
			return implode( ' ', $out );
		};

		$rel = [];
		if ( ! empty( $settings['heading_link']['is_external'] ) ) {
			$rel[] = 'noopener';
			$rel[] = 'noreferrer';
		}
		if ( ! empty( $settings['heading_link']['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}

		$decoration   = $choice( 'heading_link_decoration', [ 'none', 'underline', 'underline-hover' ], 'none' );
		$link_padding = $dim(
			'heading_link_padding',
			[
				'top'    => '6px',
				'right'  => '10px',
				'bottom' => '6px',
				'left'   => '10px',
			]
		);
		$link_margin  = $dim(
			'heading_link_padding',
			[
				'top'    => '-6px',
				'right'  => '-10px',
				'bottom' => '-6px',
				'left'   => '-10px',
			],
			true
		);

		return [
			'show'             => $is_yes( 'show_heading', 'yes' ),
			'text'             => isset( $settings['heading_text'] ) ? $settings['heading_text'] : '',
			'tag'              => isset( $settings['heading_tag'] ) ? $settings['heading_tag'] : 'h2',
			'sub'              => isset( $settings['heading_sub'] ) ? $settings['heading_sub'] : '',
			'link_text'        => isset( $settings['heading_link_text'] ) ? $settings['heading_link_text'] : '',
			'link'             => isset( $settings['heading_link']['url'] ) ? (string) $settings['heading_link']['url'] : '',
			'link_target'      => ! empty( $settings['heading_link']['is_external'] ) ? '_blank' : '',
			'link_rel'         => implode( ' ', $rel ),
			'link_arrow'       => $is_yes( 'heading_link_arrow' ),

			'boxed'            => $is_yes( 'heading_boxed', 'yes' ),
			'bg'               => $color( 'heading_bg', '#ffffff' ),
			'border'           => $color( 'heading_border', 'rgba(0, 0, 0, 0.09)' ),
			'border_w'         => $px( 'heading_border_width', '1px' ),
			'radius'           => $px( 'heading_radius', '8px' ),
			'pad'              => $dim(
				'heading_padding',
				[
					'top'    => '11px',
					'right'  => '18px',
					'bottom' => '11px',
					'left'   => '18px',
				]
			),
			'gap'              => $px( 'heading_gap', '18px' ),

			'accent'           => $is_yes( 'heading_accent', 'yes' ),
			'accent_color'     => $color( 'heading_accent_color', '#03a550' ),
			'accent_w'         => $px( 'heading_accent_width', '5px' ),
			'accent_h'         => $px( 'heading_accent_height', '18px' ),
			'accent_r'         => $px( 'heading_accent_radius', '999px' ),

			'color'            => $color( 'heading_color', '#f06724' ),
			'size'             => $px( 'heading_size', '18px' ),
			'weight'           => $choice( 'heading_weight', [ '400', '500', '600', '700', '800', '900' ], '700' ),
			'lh'               => $num( 'heading_line_height', 1.3 ),
			'ls'               => $px( 'heading_letter_spacing', 'normal' ),
			'tt'               => $choice( 'heading_transform', [ 'none', 'uppercase', 'lowercase', 'capitalize' ], 'none' ),

			'sub_color'        => $color( 'heading_sub_color', '#6b7280' ),
			'sub_size'         => $px( 'heading_sub_size', '13px' ),
			'sub_weight'       => $choice( 'heading_sub_weight', [ '400', '500', '600', '700' ], '500' ),
			'sub_lh'           => $num( 'heading_sub_line_height', 1.35 ),
			'sub_ls'           => $px( 'heading_sub_letter_spacing', 'normal' ),
			'sub_tt'           => $choice( 'heading_sub_transform', [ 'none', 'uppercase', 'lowercase', 'capitalize' ], 'none' ),

			'link_color'       => $color( 'heading_link_color', '#f06724' ),
			'link_hover_color' => ( isset( $settings['heading_link_hover_color'] ) && '' !== $settings['heading_link_hover_color'] ) ? $settings['heading_link_hover_color'] : '',
			'link_hover'       => $color( 'heading_link_hover', 'rgba(240, 103, 36, 0.10)' ),
			'link_size'        => $px( 'heading_link_size', '13px' ),
			'link_weight'      => $choice( 'heading_link_weight', [ '400', '500', '600', '700', '800' ], '600' ),
			'link_ls'          => $px( 'heading_link_letter_spacing', 'normal' ),
			'link_tt'          => $choice( 'heading_link_transform', [ 'none', 'uppercase', 'lowercase', 'capitalize' ], 'none' ),
			'link_deco'        => ( 'underline-hover' === $decoration ) ? 'none' : $decoration,
			'link_deco_hover'  => ( 'underline-hover' === $decoration ) ? 'yes' : 'no',
			'link_radius'      => $px( 'heading_link_radius', '7px' ),
			'link_pad'         => $link_padding,
			'link_margin'      => $link_margin,
			'link_icon'        => $px( 'heading_link_icon', '11px' ),
			'link_gap'         => $px( 'heading_link_gap', '6px' ),
		];
	}
}
