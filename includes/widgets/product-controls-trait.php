<?php
/**
 * Shared "Card" and "Carousel" controls for the product widgets.
 *
 * Card section  : how many lines of the product title are shown on a card.
 * Carousel section (only when Layout Style = Carousel): slides per device,
 * gap, autoplay, loop, transition speed, arrows and pagination dots.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

/**
 * Trait Product_Controls
 */
trait Product_Controls {

	/**
	 * Register the card + carousel control sections.
	 *
	 * @return void
	 */
	protected function register_product_controls() {
		$this->register_card_controls();
		$this->register_carousel_controls();
	}

	/**
	 * Content tab – product card options.
	 *
	 * @return void
	 */
	protected function register_card_controls() {
		$this->start_controls_section(
			'section_card',
			[
				'label' => esc_html__( 'Product Card', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'title_lines',
			[
				'label'       => esc_html__( 'Title Lines', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '0',
				'options'     => [
					'0' => esc_html__( 'Unlimited', 'hkdev-shop-elements' ),
					'1' => esc_html__( '1 Line', 'hkdev-shop-elements' ),
					'2' => esc_html__( '2 Lines', 'hkdev-shop-elements' ),
					'3' => esc_html__( '3 Lines', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Long titles are trimmed with "...". Cards stay aligned because every title keeps the same height.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab – carousel options.
	 *
	 * @param array $condition Controls the section depends on.
	 * @return void
	 */
	protected function register_carousel_controls( $condition = [ 'style' => 'carousel' ] ) {
		$this->start_controls_section(
			'section_carousel',
			[
				'label'     => esc_html__( 'Carousel', 'hkdev-shop-elements' ),
				'condition' => $condition,
			]
		);

		$this->add_control(
			'carousel_mobile',
			[
				'label'       => esc_html__( 'Mobile Slides', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => 1,
						'max'  => 3,
						'step' => 1,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => 2,
				],
				'description' => esc_html__( 'How many cards are fully visible at a time on phones.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'carousel_tablet',
			[
				'label'      => esc_html__( 'Tablet Slides', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 1,
						'max'  => 5,
						'step' => 1,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 3,
				],
			]
		);

		$this->add_control(
			'carousel_gap',
			[
				'label'      => esc_html__( 'Space Between', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 20,
				],
			]
		);

		$this->add_control(
			'carousel_speed',
			[
				'label'       => esc_html__( 'Transition Speed (ms)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => 150,
						'max'  => 1500,
						'step' => 50,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => 600,
				],
				'description' => esc_html__( 'Lower = snappier, higher = smoother glide.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'carousel_autoplay',
			[
				'label'        => esc_html__( 'Autoplay', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'carousel_delay',
			[
				'label'      => esc_html__( 'Autoplay Delay (ms)', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => 1000,
						'max'  => 12000,
						'step' => 250,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 5000,
				],
				'condition'  => [ 'carousel_autoplay' => 'yes' ],
			]
		);

		$this->add_control(
			'carousel_loop',
			[
				'label'        => esc_html__( 'Infinite Loop', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'carousel_arrows',
			[
				'label'        => esc_html__( 'Navigation Arrows', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'carousel_dots',
			[
				'label'        => esc_html__( 'Pagination Dots', 'hkdev-shop-elements' ),
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
	 * Card config passed to the shortcode attributes.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	protected function get_title_lines( $settings ) {
		return isset( $settings['title_lines'] ) ? absint( $settings['title_lines'] ) : 0;
	}

	/**
	 * Carousel config consumed by shop.js.
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	protected function get_carousel_config( $settings ) {
		$num = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return (float) $settings[ $key ]['size'];
			}
			return $fallback;
		};

		$is_yes = static function ( $key, $default = 'no' ) use ( $settings ) {
			if ( ! isset( $settings[ $key ] ) ) {
				return $default;
			}
			return ( 'yes' === $settings[ $key ] ) ? 'yes' : 'no';
		};

		return [
			'mobile'   => max( 1, (int) $num( 'carousel_mobile', 2 ) ),
			'tablet'   => max( 1, (int) $num( 'carousel_tablet', 3 ) ),
			'gap'      => max( 0, (int) $num( 'carousel_gap', 20 ) ),
			'speed'    => max( 150, (int) $num( 'carousel_speed', 600 ) ),
			'autoplay' => ( 'yes' === $is_yes( 'carousel_autoplay' ) ),
			'delay'    => max( 1000, (int) $num( 'carousel_delay', 5000 ) ),
			'loop'     => ( 'yes' === $is_yes( 'carousel_loop' ) ),
			'arrows'   => ( 'yes' === $is_yes( 'carousel_arrows', 'yes' ) ),
			'dots'     => ( 'yes' === $is_yes( 'carousel_dots', 'yes' ) ),
		];
	}
}
