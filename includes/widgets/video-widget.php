<?php
/**
 * HKDEV Video Embed Widget (HKDEV Shop Elements plugin).
 *
 * A lite YouTube embed: heading + description + a poster frame with a play
 * button that swaps in the real player on click. Every visual aspect is exposed
 * as an Elementor control.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Core\VideoEngine;

/**
 * Class VideoWidget
 */
class VideoWidget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_video_embed';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Video Embed', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-youtube';
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
		return [ 'youtube', 'video', 'embed', 'manufacturing', 'behind the scenes' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-video-style' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-video-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'yt_section_content',
			[
				'label' => esc_html__( 'Content', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_heading',
			[
				'label'        => esc_html__( 'Show Heading', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'heading',
			[
				'label'     => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Our Manufacturing Process (Watch Behind the Scenes):', 'hkdev-shop-elements' ),
				'condition' => [ 'show_heading' => 'yes' ],
			]
		);

		$this->add_control(
			'show_separator',
			[
				'label'        => esc_html__( 'Show Separator', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_description',
			[
				'label'        => esc_html__( 'Show Description', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'description',
			[
				'label'     => esc_html__( 'Description', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::WYSIWYG,
				'default'   => esc_html__( 'Take a behind-the-scenes look at our manufacturing process, where strict hygiene standards are followed at every stage. From clean facilities to controlled handling, every step is designed to ensure safety, purity, and consistency.', 'hkdev-shop-elements' ),
				'condition' => [ 'show_description' => 'yes' ],
			]
		);

		$this->add_control(
			'video',
			[
				'label'       => esc_html__( 'YouTube URL', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'label_block' => true,
				'separator'   => 'before',
				'description' => esc_html__( 'Paste a YouTube watch, share, embed or Shorts link (or just the video id).', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'poster',
			[
				'label'       => esc_html__( 'Poster Image', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Optional. Leave empty to use the YouTube thumbnail automatically.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'play_label',
			[
				'label'   => esc_html__( 'Play Button Label', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Play manufacturing process video', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'anchor',
			[
				'label'       => esc_html__( 'Anchor ID', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'manufacturing-process',
			]
		);

		$this->end_controls_section();

		$this->register_video_style_controls();
	}

	/**
	 * Style tab controls.
	 *
	 * @return void
	 */
	protected function register_video_style_controls() {
		$scope = '{{WRAPPER}} .hkdev-yt';

		$this->start_controls_section(
			'yt_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'yt_max_width', esc_html__( 'Container Max Width', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-container', 'max-width', 400, 1800 );
		$this->hkdev_dimensions( 'yt_container_padding', esc_html__( 'Container Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-container', 'padding' );
		$this->hkdev_dimensions( 'yt_block_margin', esc_html__( 'Section Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'yt_block_padding', esc_html__( 'Section Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_slider( 'yt_video_width', esc_html__( 'Video Max Width', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-video-width', 240, 1600 );
		$this->hkdev_slider( 'yt_heading_spacing', esc_html__( 'Heading Bottom Spacing', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-heading', 'margin-bottom', 0, 60 );
		$this->hkdev_slider( 'yt_desc_spacing', esc_html__( 'Description Bottom Spacing', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-description', 'margin-bottom', 0, 60 );
		$this->hkdev_select(
			'yt_video_ratio',
			esc_html__( 'Video Shape', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-yt-ratio',
			[
				''       => esc_html__( 'Default (16:9)', 'hkdev-shop-elements' ),
				'16 / 9' => esc_html__( 'Wide (16:9)', 'hkdev-shop-elements' ),
				'4 / 3'  => esc_html__( 'Classic (4:3)', 'hkdev-shop-elements' ),
				'1 / 1'  => esc_html__( 'Square (1:1)', 'hkdev-shop-elements' ),
				'9 / 16' => esc_html__( 'Reel (9:16)', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_dimensions( 'yt_video_radius', esc_html__( 'Video Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-lite', 'border-radius' );
		$this->hkdev_shadow( 'yt_video_shadow', esc_html__( 'Video Shadow', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-lite' );

		$this->end_controls_section();

		$this->start_controls_section(
			'yt_style_text',
			[
				'label' => esc_html__( 'Text', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'yt_bg', esc_html__( 'Section Background', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-bg' );
		$this->hkdev_typography( 'yt_heading', esc_html__( 'Heading', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-heading' );
		$this->hkdev_typography( 'yt_desc', esc_html__( 'Description', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-description' );
		$this->hkdev_color( 'yt_separator', esc_html__( 'Separator Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-separator', 'background-color' );
		$this->hkdev_slider( 'yt_separator_w', esc_html__( 'Separator Width', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-separator', 'width', 0, 240 );
		$this->hkdev_slider( 'yt_separator_h', esc_html__( 'Separator Thickness', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-separator', 'height', 1, 10 );
		$this->hkdev_slider( 'yt_separator_spacing', esc_html__( 'Separator Bottom Spacing', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-yt-separator', 'margin-bottom', 0, 60 );

		$this->end_controls_section();

		$this->start_controls_section(
			'yt_style_play',
			[
				'label' => esc_html__( 'Play Button', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'yt_play_size', esc_html__( 'Button Size', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-play-size', 28, 130 );
		$this->hkdev_color( 'yt_play_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-play-bg' );
		$this->hkdev_color( 'yt_play_color', esc_html__( 'Button Icon Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-play-color' );
		$this->hkdev_color( 'yt_play_hover_bg', esc_html__( 'Hover Background', 'hkdev-shop-elements' ), $scope, '--hkdev-yt-play-hover-bg' );

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

		$config = [
			'anchor'           => isset( $settings['anchor'] ) ? sanitize_title( $settings['anchor'] ) : '',
			'heading'          => isset( $settings['heading'] ) ? $settings['heading'] : '',
			'description'      => isset( $settings['description'] ) ? $settings['description'] : '',
			'video'            => isset( $settings['video'] ) ? $settings['video'] : '',
			'poster'           => ! empty( $settings['poster']['url'] ) ? $settings['poster']['url'] : '',
			'play_label'       => isset( $settings['play_label'] ) ? $settings['play_label'] : '',
			'show_heading'     => ( isset( $settings['show_heading'] ) && 'yes' === $settings['show_heading'] ),
			'show_description' => ( isset( $settings['show_description'] ) && 'yes' === $settings['show_description'] ),
			'show_separator'   => ( isset( $settings['show_separator'] ) && 'yes' === $settings['show_separator'] ),
		];

		echo VideoEngine::instance()->render( $config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
