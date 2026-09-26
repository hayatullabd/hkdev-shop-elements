<?php
/**
 * HKDEV Hero Slider Widget (HKDEV Shop Elements plugin).
 *
 * Responsive full-width banner slider: fade slides with arrows, dots, an
 * autoplay progress bar, touch swipe, hover pause and keyboard navigation.
 * Optional per-slide copy (heading, text, button) layered over the image.
 *
 * Self-contained: no Tailwind / icon-font dependency, the arrows are inline SVG
 * and the runtime lives in assets/js/hero-slider.js.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

/**
 * Class HeroSliderWidget
 */
class HeroSliderWidget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_hero_slider';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Hero Slider', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-full-screen';
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
		return [ 'hero', 'banner', 'slider', 'carousel', 'slideshow', 'image slider', 'fullscreen' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-hero-style' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-hero-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_slides_section();
		$this->register_settings_section();
		$this->register_layout_style();
		$this->register_content_style();
		$this->register_arrows_style();
		$this->register_dots_style();
		$this->register_progress_style();
		$this->register_animation_style();
	}

	/**
	 * Style tab – motion.
	 *
	 * @return void
	 */
	protected function register_animation_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_animation',
			[
				'label' => esc_html__( 'Animation', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_select(
			'hero_easing',
			esc_html__( 'Easing', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-hero-curve',
			[
				'cubic-bezier(0.4, 0, 0.2, 1)'   => esc_html__( 'Smooth (default)', 'hkdev-shop-elements' ),
				'cubic-bezier(0.22, 1, 0.36, 1)' => esc_html__( 'Soft - long settle', 'hkdev-shop-elements' ),
				'cubic-bezier(0.65, 0, 0.35, 1)' => esc_html__( 'Balanced ease in / out', 'hkdev-shop-elements' ),
				'ease-out'                       => esc_html__( 'Gentle', 'hkdev-shop-elements' ),
				'linear'                         => esc_html__( 'Linear', 'hkdev-shop-elements' ),
			],
			[],
			esc_html__( 'How a slide change accelerates. Smooth starts quickly and settles softly.', 'hkdev-shop-elements' )
		);

		$this->hkdev_slider_raw(
			'hero_image_zoom',
			esc_html__( 'Image Drift', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-hero-kb-scale',
			1,
			1.3,
			0.01,
			[],
			esc_html__( 'The active image zooms very slowly from 1 to this value while its slide is on screen. Leave at 1.00 to switch the drift off. The speed follows the autoplay timing automatically.', 'hkdev-shop-elements' )
		);

		$this->add_control(
			'hero_text_anim',
			[
				'label'        => esc_html__( 'Animate Slide Text', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
				'description'  => esc_html__( 'Heading, description and button rise into place one after another.', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_slider(
			'hero_text_rise',
			esc_html__( 'Text Rise Distance', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-hero-anim-y',
			0,
			60,
			[ 'hero_text_anim' => 'yes' ]
		);

		$this->add_control(
			'hero_text_stagger',
			[
				'label'     => esc_html__( 'Text Stagger (ms)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 400,
						'step' => 10,
					],
				],
				'condition' => [ 'hero_text_anim' => 'yes' ],
				'selectors' => [ $scope => '--hkdev-hero-stagger: {{SIZE}}ms !important;' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab – the slides.
	 *
	 * @return void
	 */
	protected function register_slides_section() {
		$this->start_controls_section(
			'hero_section_slides',
			[
				'label' => esc_html__( 'Slides', 'hkdev-shop-elements' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			[
				'label'       => esc_html__( 'Image', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [ 'url' => \Elementor\Utils::get_placeholder_image_src() ],
				'description' => esc_html__( 'Landscape artwork suits the desktop banner: 1920 x 1080 (16:9) for a full-screen hero, or 1920 x 600 for a short strip. Keep it under ~300 KB (WebP).', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'image_mobile',
			[
				'label'       => esc_html__( 'Mobile Image (optional)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Shown instead of the main image below 768px. A portrait image (1080 x 1350 / 4:5, or 1080 x 1920 / 9:16) fills a tall phone banner without heavy cropping.', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'alt',
			[
				'label'       => esc_html__( 'Alt Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Describe the image for screen readers. Falls back to the heading.', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'link',
			[
				'label'       => esc_html__( 'Slide Link', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://your-store.com/shop',
				'description' => esc_html__( 'Optional. Makes the whole slide clickable.', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'heading',
			[
				'label'     => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'separator' => 'before',
			]
		);

		$repeater->add_control(
			'description',
			[
				'label' => esc_html__( 'Description', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 2,
			]
		);

		$repeater->add_control(
			'button_text',
			[
				'label' => esc_html__( 'Button Text', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'button_link',
			[
				'label'       => esc_html__( 'Button Link', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://your-store.com/shop',
				'description' => esc_html__( 'Empty falls back to the slide link.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'slides',
			[
				'label'       => esc_html__( 'Slides', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ heading || "Slide" }}}',
				'default'     => [],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab – behaviour.
	 *
	 * @return void
	 */
	protected function register_settings_section() {
		$this->start_controls_section(
			'hero_section_settings',
			[
				'label' => esc_html__( 'Settings', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => esc_html__( 'Autoplay', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'delay',
			[
				'label'     => esc_html__( 'Autoplay Speed (ms)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'max'       => 30000,
				'step'      => 500,
				'condition' => [ 'autoplay' => 'yes' ],
			]
		);

		$this->add_control(
			'transition',
			[
				'label'     => esc_html__( 'Transition', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fade',
				'separator' => 'before',
				'options'   => [
					'fade'  => esc_html__( 'Fade', 'hkdev-shop-elements' ),
					'slide' => esc_html__( 'Slide', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'infinite',
			[
				'label'        => esc_html__( 'Infinite Loop', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => esc_html__( 'Pause on Hover', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Autoplay resumes when the pointer leaves the banner.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'pause_on_interaction',
			[
				'label'        => esc_html__( 'Pause on Interaction', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Stops autoplay for good once a visitor uses an arrow, dot or swipe.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'navigation',
			[
				'label'     => esc_html__( 'Navigation', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'both',
				'separator' => 'before',
				'options'   => [
					'both'   => esc_html__( 'Arrows and Dots', 'hkdev-shop-elements' ),
					'arrows' => esc_html__( 'Arrows', 'hkdev-shop-elements' ),
					'dots'   => esc_html__( 'Dots', 'hkdev-shop-elements' ),
					'none'   => esc_html__( 'None', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'show_progress',
			[
				'label'        => esc_html__( 'Show Progress Bar', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Only appears while autoplay is on.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'content_position',
			[
				'label'     => esc_html__( 'Slide Text Position', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center',
				'separator' => 'before',
				'options'   => [
					'center'        => esc_html__( 'Centered', 'hkdev-shop-elements' ),
					'left'          => esc_html__( 'Left', 'hkdev-shop-elements' ),
					'right'         => esc_html__( 'Right', 'hkdev-shop-elements' ),
					'bottom-left'   => esc_html__( 'Bottom Left', 'hkdev-shop-elements' ),
					'bottom-center' => esc_html__( 'Bottom Center', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'content_width',
			[
				'label'       => esc_html__( 'Content Width', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ '%', 'px' ],
				'range'       => [
					'%'  => [
						'min' => 20,
						'max' => 100,
					],
					'px' => [
						'min' => 240,
						'max' => 1600,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => 900,
				],
				'separator'   => 'before',
				'selectors'   => [
					'{{WRAPPER}} .hkdev-hero-content-inner' => 'max-width: {{SIZE}}{{UNIT}} !important;',
				],
				'description' => esc_html__( 'Maximum width of the slide text block.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'       => esc_html__( 'Image Size', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '1536x1536',
				'separator'   => 'before',
				'options'     => [
					'full'              => esc_html__( 'Full (original) - best for full-width', 'hkdev-shop-elements' ),
					'2048x2048'         => esc_html__( '2048 x 2048', 'hkdev-shop-elements' ),
					'1536x1536'         => esc_html__( '1536 x 1536 (recommended)', 'hkdev-shop-elements' ),
					'large'             => esc_html__( 'Large (1024px)', 'hkdev-shop-elements' ),
					'woocommerce_single' => esc_html__( 'WooCommerce Single (600px)', 'hkdev-shop-elements' ),
					'medium_large'      => esc_html__( 'Medium Large (768px)', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Preferred file the browser starts from. Srcset still serves a smaller file on phones. Use Full only when the artwork is already compressed WebP.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab – size and shape.
	 *
	 * @return void
	 */
	protected function register_layout_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		// Responsive: the panel gets a Desktop / Tablet / Mobile tab, so the
		// banner can be 100vh on desktop and a shorter strip on phones.
		$this->add_responsive_control(
			'hero_height',
			[
				'label'      => esc_html__( 'Height', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh', 'rem' ],
				'range'      => [
					'px'  => [
						'min' => 120,
						'max' => 1400,
					],
					'vh'  => [
						'min' => 10,
						'max' => 100,
					],
					'rem' => [
						'min' => 5,
						'max' => 80,
					],
				],
				'default'    => [
					'unit' => 'vh',
					'size' => 100,
				],
				'selectors'  => [ $scope => '--hkdev-hero-height: {{SIZE}}{{UNIT}} !important;' ],
			]
		);

		$this->add_responsive_control(
			'hero_width',
			[
				'label'      => esc_html__( 'Width', 'hkdev-shop-elements' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px', 'vw' ],
				'range'      => [
					'%'  => [
						'min' => 20,
						'max' => 100,
					],
					'px' => [
						'min' => 320,
						'max' => 1920,
					],
					'vw' => [
						'min' => 20,
						'max' => 100,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors'  => [
					$scope => 'width: {{SIZE}}{{UNIT}} !important; margin-left: auto !important; margin-right: auto !important;',
				],
				'description' => esc_html__( '100% fills the container. Lower values centre a narrower banner.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'hero_full_width',
			[
				'label'        => esc_html__( 'Full Width (edge to edge)', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Breaks out of a boxed container so the banner spans the whole screen, ignoring the section padding. Leave it off if your section is already Full Width.', 'hkdev-shop-elements' ),
			]
		);

		$this->hkdev_dimensions( 'hero_radius', esc_html__( 'Corner Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );
		$this->hkdev_dimensions( 'hero_margin', esc_html__( 'Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		// Written as a plain SLIDER rather than hkdev_slider_raw: a duration has
		// to carry its unit, and that helper emits a unit-less value.
		$this->add_control(
			'hero_ease',
			[
				'label'       => esc_html__( 'Transition Speed (s)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [
						'min'  => 0.2,
						'max'  => 2,
						'step' => 0.1,
					],
				],
				'selectors'   => [ $scope => '--hkdev-hero-ease: {{SIZE}}s !important;' ],
				'description' => esc_html__( 'How long a single slide change takes.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->register_image_style();
	}

	/**
	 * Style tab – how the artwork fills the banner.
	 *
	 * @return void
	 */
	protected function register_image_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_image',
			[
				'label' => esc_html__( 'Image', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_select(
			'hero_img_fit',
			esc_html__( 'Image Fit', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-hero-img-fit',
			[
				''        => esc_html__( 'Default (cover)', 'hkdev-shop-elements' ),
				'cover'   => esc_html__( 'Cover - fill, crop the edges', 'hkdev-shop-elements' ),
				'contain' => esc_html__( 'Contain - show the whole image', 'hkdev-shop-elements' ),
			],
			[],
			esc_html__( 'Cover fills the banner and crops what does not fit. Contain shows the entire image and leaves the background colour visible around it.', 'hkdev-shop-elements' )
		);

		$this->hkdev_select(
			'hero_img_pos',
			esc_html__( 'Image Focal Point', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-hero-img-pos',
			[
				''       => esc_html__( 'Default (center)', 'hkdev-shop-elements' ),
				'center' => esc_html__( 'Center', 'hkdev-shop-elements' ),
				'top'    => esc_html__( 'Top', 'hkdev-shop-elements' ),
				'bottom' => esc_html__( 'Bottom', 'hkdev-shop-elements' ),
				'left'   => esc_html__( 'Left', 'hkdev-shop-elements' ),
				'right'  => esc_html__( 'Right', 'hkdev-shop-elements' ),
			],
			[],
			esc_html__( 'Which part of the image stays visible when it is cropped.', 'hkdev-shop-elements' )
		);

		$this->hkdev_color( 'hero_img_bg', esc_html__( 'Background (behind the image)', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-bg' );

		$this->end_controls_section();
	}

	/**
	 * Style tab – overlay, copy and button.
	 *
	 * @return void
	 */
	protected function register_content_style() {
		$this->start_controls_section(
			'hero_style_content',
			[
				'label' => esc_html__( 'Overlay & Slide Text', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hero_overlay', esc_html__( 'Overlay Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero', '--hkdev-hero-overlay' );
		$this->add_responsive_control(
			'hero_content_padding',
			[
				'label'          => esc_html__( 'Content Padding', 'hkdev-shop-elements' ),
				'type'           => Controls_Manager::DIMENSIONS,
				'size_units'     => [ 'px', 'em', 'rem', '%' ],
				'default'        => [
					'top'    => 48,
					'right'  => 28,
					'bottom' => 48,
					'left'   => 28,
					'unit'   => 'px',
				],
				'mobile_default' => [
					'top'    => 32,
					'right'  => 18,
					'bottom' => 32,
					'left'   => 18,
					'unit'   => 'px',
				],
				'selectors'      => [
					'{{WRAPPER}} .hkdev-hero-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
				],
			]
		);
		$this->hkdev_typography( 'hero_title', esc_html__( 'Heading', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-title' );
		$this->hkdev_typography( 'hero_text', esc_html__( 'Description', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-text' );
		$this->hkdev_typography( 'hero_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-btn' );
		$this->hkdev_color( 'hero_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-btn', 'background-color' );
		$this->hkdev_color( 'hero_btn_hover_bg', esc_html__( 'Button Hover Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-btn:hover', 'background-color' );
		$this->hkdev_dimensions( 'hero_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-btn', 'border-radius' );
		$this->hkdev_dimensions( 'hero_btn_padding', esc_html__( 'Button Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-hero-btn', 'padding' );

		$this->end_controls_section();
	}

	/**
	 * Style tab – navigation arrows.
	 *
	 * @return void
	 */
	protected function register_arrows_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_arrows',
			[
				'label' => esc_html__( 'Arrows', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		// Responsive (with phone defaults) rather than a hardcoded media query:
		// a fixed media-query value can never win against the !important rule
		// these controls emit, so it would silently do nothing.
		$this->add_responsive_control(
			'hero_arrow_size',
			[
				'label'          => esc_html__( 'Button Size', 'hkdev-shop-elements' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px'  => [
						'min' => 28,
						'max' => 110,
					],
					'em'  => [
						'min' => 1,
						'max' => 8,
					],
					'rem' => [
						'min' => 1,
						'max' => 8,
					],
				],
				'default'        => [
					'unit' => 'px',
					'size' => 50,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 40,
				],
				'selectors'      => [ $scope => '--hkdev-hero-arrow-size: {{SIZE}}{{UNIT}} !important;' ],
			]
		);

		$this->add_responsive_control(
			'hero_arrow_offset',
			[
				'label'          => esc_html__( 'Side Offset', 'hkdev-shop-elements' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px', 'em', 'rem' ],
				'range'          => [
					'px'  => [
						'min' => 0,
						'max' => 120,
					],
					'em'  => [
						'min' => 0,
						'max' => 8,
					],
					'rem' => [
						'min' => 0,
						'max' => 8,
					],
				],
				'default'        => [
					'unit' => 'px',
					'size' => 20,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors'      => [ $scope => '--hkdev-hero-arrow-offset: {{SIZE}}{{UNIT}} !important;' ],
			]
		);
		$this->hkdev_color( 'hero_arrow_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-arrow-bg' );
		$this->hkdev_color( 'hero_arrow_color', esc_html__( 'Arrow Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-arrow-color' );
		$this->hkdev_color( 'hero_arrow_hover_bg', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-arrow-hover-bg' );
		$this->hkdev_color( 'hero_arrow_hover_border', esc_html__( 'Hover Border', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-arrow-hover-border' );

		$this->end_controls_section();
	}

	/**
	 * Style tab – pagination dots.
	 *
	 * @return void
	 */
	protected function register_dots_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_dots',
			[
				'label' => esc_html__( 'Dots', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'hero_dot_size', esc_html__( 'Dot Size', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dot-size', 4, 30 );
		$this->hkdev_slider( 'hero_dot_gap', esc_html__( 'Gap Between Dots', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dots-gap', 0, 40 );
		$this->hkdev_slider( 'hero_dots_bottom', esc_html__( 'Bottom Distance', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dots-bottom', 0, 120 );
		$this->hkdev_color( 'hero_dot_bg', esc_html__( 'Dot Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dot-bg' );
		$this->hkdev_color( 'hero_dot_hover', esc_html__( 'Dot Hover Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dot-hover' );
		$this->hkdev_color( 'hero_dot_active', esc_html__( 'Active Dot Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-dot-active' );

		$this->end_controls_section();
	}

	/**
	 * Style tab – autoplay progress bar.
	 *
	 * @return void
	 */
	protected function register_progress_style() {
		$scope = '{{WRAPPER}} .hkdev-hero';

		$this->start_controls_section(
			'hero_style_progress',
			[
				'label' => esc_html__( 'Progress Bar', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hero_progress_color', esc_html__( 'Bar Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-progress-color' );
		$this->hkdev_slider( 'hero_progress_h', esc_html__( 'Bar Thickness', 'hkdev-shop-elements' ), $scope, '--hkdev-hero-progress-h', 1, 14 );

		$this->end_controls_section();
	}

	/**
	 * Resolve a slide image to the configured WordPress size.
	 *
	 * Falls back to the stored URL when the attachment id is missing or that
	 * size was never generated.
	 *
	 * @param array  $image Image control value (id + url).
	 * @param string $size  WordPress image size.
	 * @return string
	 */
	protected function slide_image_url( $image, $size ) {
		$image = (array) $image;

		if ( ! empty( $image['id'] ) && 'full' !== $size ) {
			$url = wp_get_attachment_image_url( absint( $image['id'] ), $size );

			if ( $url ) {
				return $url;
			}
		}

		return ! empty( $image['url'] ) ? $image['url'] : '';
	}

	/**
	 * Responsive hero image: one file per viewport, with srcset.
	 *
	 * @param array  $desktop Desktop MEDIA control value.
	 * @param array  $mobile  Optional mobile MEDIA control value.
	 * @param string $size    WordPress image size.
	 * @param string $alt     Alt text.
	 * @param bool   $priority First slide (LCP).
	 * @return string
	 */
	protected function slide_picture_html( $desktop, $mobile, $size, $alt, $priority ) {
		$desktop = is_array( $desktop ) ? $desktop : [];
		$mobile  = is_array( $mobile ) ? $mobile : [];
		$desk_id = ! empty( $desktop['id'] ) ? absint( $desktop['id'] ) : 0;
		$mob_id  = ! empty( $mobile['id'] ) ? absint( $mobile['id'] ) : 0;

		$attrs = [
			'class'    => 'hkdev-hero-img',
			'alt'      => $alt,
			'decoding' => 'async',
			'loading'  => $priority ? 'eager' : 'lazy',
			'sizes'    => '100vw',
		];

		if ( $priority ) {
			$attrs['fetchpriority'] = 'high';
		}

		if ( $desk_id && $mob_id ) {
			$mob_srcset = wp_get_attachment_image_srcset( $mob_id, $size );
			$mob_src    = wp_get_attachment_image_url( $mob_id, $size );
			$html       = '<picture>';
			if ( $mob_srcset || $mob_src ) {
				$html .= '<source media="(max-width:767px)" sizes="100vw" srcset="' . esc_attr( $mob_srcset ? $mob_srcset : $mob_src ) . '">';
			}
			$html .= wp_get_attachment_image( $desk_id, $size, false, $attrs );
			$html .= '</picture>';
			return $html;
		}

		if ( $desk_id ) {
			return wp_get_attachment_image( $desk_id, $size, false, $attrs );
		}

		$url = ! empty( $desktop['url'] ) ? (string) $desktop['url'] : '';
		if ( '' === $url ) {
			return '';
		}

		return '<img class="hkdev-hero-img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $priority ? 'eager' : 'lazy' ) . '" decoding="async"' . ( $priority ? ' fetchpriority="high"' : '' ) . '>';
	}

	/**
	 * Preload the first slide so LCP does not wait on CSS.
	 *
	 * @param array  $desktop Desktop MEDIA control value.
	 * @param array  $mobile  Optional mobile MEDIA control value.
	 * @param string $size    WordPress image size.
	 * @return void
	 */
	protected function print_lcp_preload( $desktop, $mobile, $size ) {
		$desktop = is_array( $desktop ) ? $desktop : [];
		$mobile  = is_array( $mobile ) ? $mobile : [];
		$desk_id = ! empty( $desktop['id'] ) ? absint( $desktop['id'] ) : 0;
		$mob_id  = ! empty( $mobile['id'] ) ? absint( $mobile['id'] ) : 0;

		$desk_url    = $desk_id ? wp_get_attachment_image_url( $desk_id, $size ) : ( ! empty( $desktop['url'] ) ? $desktop['url'] : '' );
		$desk_srcset = $desk_id ? wp_get_attachment_image_srcset( $desk_id, $size ) : '';
		$mob_url     = $mob_id ? wp_get_attachment_image_url( $mob_id, $size ) : ( ! empty( $mobile['url'] ) ? $mobile['url'] : '' );

		if ( $mob_url ) {
			echo '<link rel="preload" as="image" href="' . esc_url( $mob_url ) . '" media="(max-width:767px)" fetchpriority="high">' . "\n";
		}

		if ( $desk_url ) {
			$attr = ' rel="preload" as="image" href="' . esc_url( $desk_url ) . '" fetchpriority="high"';
			if ( $desk_srcset ) {
				$attr .= ' imagesrcset="' . esc_attr( $desk_srcset ) . '" imagesizes="100vw"';
			}
			if ( $mob_url ) {
				$attr .= ' media="(min-width:768px)"';
			}
			echo '<link' . $attr . '>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
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

		$sizes      = [ 'full', '2048x2048', '1536x1536', 'large', 'woocommerce_single', 'medium_large' ];
		$image_size = ( isset( $settings['image_size'] ) && in_array( $settings['image_size'], $sizes, true ) ) ? $settings['image_size'] : 'full';

		$slides = [];
		if ( ! empty( $settings['slides'] ) && is_array( $settings['slides'] ) ) {
			foreach ( $settings['slides'] as $row ) {
				if ( empty( $row['image']['url'] ) ) {
					continue;
				}

				$link = isset( $row['link'] ) ? (array) $row['link'] : [];
				$btn  = isset( $row['button_link'] ) ? (array) $row['button_link'] : [];

				$slides[] = [
					'image'        => isset( $row['image'] ) ? (array) $row['image'] : [],
					'image_mobile' => ! empty( $row['image_mobile']['url'] ) ? (array) $row['image_mobile'] : [],
					'alt'        => ! empty( $row['alt'] ) ? $row['alt'] : ( isset( $row['heading'] ) ? $row['heading'] : '' ),
					'link'       => ! empty( $link['url'] ) ? $link['url'] : '',
					'link_blank' => ! empty( $link['is_external'] ),
					'link_nofollow' => ! empty( $link['nofollow'] ),
					'heading'    => isset( $row['heading'] ) ? $row['heading'] : '',
					'text'       => isset( $row['description'] ) ? $row['description'] : '',
					'btn'        => isset( $row['button_text'] ) ? $row['button_text'] : '',
					'btn_link'   => ! empty( $btn['url'] ) ? $btn['url'] : '',
					'btn_blank'  => ! empty( $btn['is_external'] ),
				];
			}
		}

		if ( empty( $slides ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="hkdev-hero-empty">' . esc_html__( 'Add a slide to the Hero Slider.', 'hkdev-shop-elements' ) . '</div>';
			}
			return;
		}

		$count    = count( $slides );
		$auto     = ( ! isset( $settings['autoplay'] ) || 'yes' === $settings['autoplay'] ) && $count > 1;
		$delay    = isset( $settings['delay'] ) ? max( 1000, absint( $settings['delay'] ) ) : 5000;
		$progress = ( ! isset( $settings['show_progress'] ) || 'yes' === $settings['show_progress'] ) && $auto;

		$nav = isset( $settings['navigation'] ) ? $settings['navigation'] : 'both';
		if ( ! in_array( $nav, [ 'both', 'arrows', 'dots', 'none' ], true ) ) {
			$nav = 'both';
		}

		$arrows = ( 'both' === $nav || 'arrows' === $nav ) && $count > 1;
		$dots   = ( 'both' === $nav || 'dots' === $nav ) && $count > 1;

		$transition        = ( isset( $settings['transition'] ) && 'slide' === $settings['transition'] ) ? 'slide' : 'fade';
		$infinite          = ( ! isset( $settings['infinite'] ) || 'yes' === $settings['infinite'] );
		$pause_hover       = ( ! isset( $settings['pause_on_hover'] ) || 'yes' === $settings['pause_on_hover'] );
		$pause_interaction = ( isset( $settings['pause_on_interaction'] ) && 'yes' === $settings['pause_on_interaction'] );

		$positions = [ 'center', 'left', 'right', 'bottom-left', 'bottom-center' ];
		$position  = isset( $settings['content_position'] ) ? $settings['content_position'] : 'center';
		if ( ! in_array( $position, $positions, true ) ) {
			$position = 'center';
		}

		$classes = 'hkdev-hero hkdev-hero-position-' . $position . ' hkdev-hero-transition-' . $transition;

		$this->print_lcp_preload( $slides[0]['image'], $slides[0]['image_mobile'], $image_size );

		if ( isset( $settings['hero_full_width'] ) && 'yes' === $settings['hero_full_width'] ) {
			$classes .= ' hkdev-hero-full';
		}

		if ( ! isset( $settings['hero_text_anim'] ) || 'yes' === $settings['hero_text_anim'] ) {
			$classes .= ' hkdev-hero-text-anim';
		}

		// The image drift should finish roughly as the slide changes, so its
		// duration follows the autoplay timing rather than a fixed guess.
		$drift_ms = $auto ? ( $delay + 800 ) : 8000;
		?>
		<div class="<?php echo esc_attr( $classes ); ?>"
			 style="--hkdev-hero-kb-dur:<?php echo esc_attr( $drift_ms ); ?>ms;"
			 data-autoplay="<?php echo $auto ? '1' : '0'; ?>"
			 data-delay="<?php echo esc_attr( $delay ); ?>"
			 data-transition="<?php echo esc_attr( $transition ); ?>"
			 data-loop="<?php echo $infinite ? '1' : '0'; ?>"
			 data-pause-hover="<?php echo $pause_hover ? '1' : '0'; ?>"
			 data-pause-interaction="<?php echo $pause_interaction ? '1' : '0'; ?>"
			 tabindex="0"
			 role="region"
			 aria-label="<?php esc_attr_e( 'Image banner slider', 'hkdev-shop-elements' ); ?>">

			<?php if ( $progress ) : ?>
				<div class="hkdev-hero-progress" aria-hidden="true"></div>
			<?php endif; ?>

			<div class="hkdev-hero-slides">
				<?php
				foreach ( $slides as $index => $slide ) :
					$active     = ( 0 === $index );
					$has_copy   = ( '' !== trim( (string) $slide['heading'] ) || '' !== trim( (string) $slide['text'] ) || '' !== trim( (string) $slide['btn'] ) );
					$link_attrs = '';

					if ( '' !== $slide['link'] ) {
						$link_attrs = ' href="' . esc_url( $slide['link'] ) . '"';
						if ( $slide['link_blank'] ) {
							$link_attrs .= ' target="_blank"';
						}
						if ( $slide['link_nofollow'] ) {
							$link_attrs .= ' rel="nofollow"';
						}
					}

					$img = $this->slide_picture_html( $slide['image'], $slide['image_mobile'], $image_size, $slide['alt'], $active );

					$slide_class = 'hkdev-hero-slide' . ( $active ? ' is-active' : '' );
					?>
					<div class="<?php echo esc_attr( $slide_class ); ?>">
						<?php if ( '' !== $link_attrs ) : ?>
							<a class="hkdev-hero-link"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php else : ?>
							<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>

						<?php if ( $has_copy ) : ?>
							<div class="hkdev-hero-shade" aria-hidden="true"></div>
							<div class="hkdev-hero-content">
								<div class="hkdev-hero-content-inner">
									<?php if ( '' !== trim( (string) $slide['heading'] ) ) : ?>
										<h2 class="hkdev-hero-title"><?php echo esc_html( $slide['heading'] ); ?></h2>
									<?php endif; ?>

									<?php if ( '' !== trim( (string) $slide['text'] ) ) : ?>
										<p class="hkdev-hero-text"><?php echo esc_html( $slide['text'] ); ?></p>
									<?php endif; ?>

									<?php if ( '' !== trim( (string) $slide['btn'] ) ) : ?>
										<a class="hkdev-hero-btn"
											href="<?php echo esc_url( '' !== $slide['btn_link'] ? $slide['btn_link'] : $slide['link'] ); ?>"
											<?php echo $slide['btn_blank'] ? ' target="_blank" rel="noopener"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
											<?php echo esc_html( $slide['btn'] ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $arrows ) : ?>
				<button type="button" class="hkdev-hero-nav hkdev-hero-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'hkdev-shop-elements' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
				</button>
				<button type="button" class="hkdev-hero-nav hkdev-hero-next" aria-label="<?php esc_attr_e( 'Next slide', 'hkdev-shop-elements' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
				</button>
			<?php endif; ?>

			<?php if ( $dots ) : ?>
				<div class="hkdev-hero-dots"></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
