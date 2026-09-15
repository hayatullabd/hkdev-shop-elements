<?php
/**
 * HKDEV Customer Reviews Widget (HKDEV Shop Elements plugin).
 *
 * "Hear From Our Customers" block: Video Reviews / Social Proofs tabs, review
 * modals (video player or image slider) and an optional Top Pick product promo.
 * Every visual aspect is exposed as an Elementor control so the block can be
 * styled pixel-perfect without touching code.
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
use HkdevShopElements\Includes\Review_Engine;

/**
 * Class Reviews_Widget
 */
class Reviews_Widget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_customer_reviews';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Customer Reviews', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-star';
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
		return [ 'reviews', 'testimonials', 'social proof', 'video', 'customers', 'rating' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-reviews-style' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-reviews-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab controls.
	 *
	 * @return void
	 */
	protected function register_content_controls() {
		$this->register_header_section();
		$this->register_tabs_section();
		$this->register_videos_section();
		$this->register_proofs_section();
		$this->register_filter_section();
		$this->register_labels_section();
	}

	/**
	 * Header content section.
	 *
	 * @return void
	 */
	protected function register_header_section() {
		$this->start_controls_section(
			'rv_section_header',
			[
				'label' => esc_html__( 'Header', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_header',
			[
				'label'        => esc_html__( 'Show Header', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'heading',
			[
				'label'     => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Hear From Our Customers', 'hkdev-shop-elements' ),
				'condition' => [ 'show_header' => 'yes' ],
			]
		);

		$this->add_control(
			'subheading',
			[
				'label'       => esc_html__( 'Subheading', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Real stories, <strong>real results</strong>', 'hkdev-shop-elements' ),
				'rows'        => 2,
				'description' => esc_html__( 'Basic inline HTML is allowed (<strong>, <em>, <br>).', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_header' => 'yes' ],
			]
		);

		$this->add_control(
			'anchor',
			[
				'label'       => esc_html__( 'Anchor ID', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'hear-from-customers',
				'description' => esc_html__( 'Optional. Lets you link straight to this section.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Tabs content section.
	 *
	 * @return void
	 */
	protected function register_tabs_section() {
		$this->start_controls_section(
			'rv_section_tabs',
			[
				'label' => esc_html__( 'Tabs', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_tabs',
			[
				'label'        => esc_html__( 'Show Tab Switcher', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Hidden automatically when a tab has no reviews.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'tab_video_label',
			[
				'label'     => esc_html__( 'Video Tab Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Video Reviews', 'hkdev-shop-elements' ),
				'condition' => [ 'show_tabs' => 'yes' ],
			]
		);

		$this->add_control(
			'tab_written_label',
			[
				'label'     => esc_html__( 'Social Proof Tab Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Social Proofs', 'hkdev-shop-elements' ),
				'condition' => [ 'show_tabs' => 'yes' ],
			]
		);

		$this->add_control(
			'default_tab',
			[
				'label'     => esc_html__( 'Default Tab', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'written',
				'options'   => [
					'written' => esc_html__( 'Social Proofs', 'hkdev-shop-elements' ),
					'video'   => esc_html__( 'Video Reviews', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'show_tabs' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Video reviews section (display switches + repeater).
	 *
	 * @return void
	 */
	protected function register_videos_section() {
		$this->start_controls_section(
			'rv_section_videos',
			[
				'label' => esc_html__( 'Video Reviews', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_video_name',
			[
				'label'        => esc_html__( 'Show Name', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_video_stars',
			[
				'label'        => esc_html__( 'Show Stars', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_video_play',
			[
				'label'        => esc_html__( 'Show Play Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_video_overlay',
			[
				'label'        => esc_html__( 'Show Gradient Overlay', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'video_thumb_fallback',
			[
				'label'        => esc_html__( 'Fallback to YouTube Thumbnail', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'When a video has no thumbnail of its own, use the YouTube video image instead.', 'hkdev-shop-elements' ),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'name',
			[
				'label'   => esc_html__( 'Customer Name', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Customer Name', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'thumbnail',
			[
				'label' => esc_html__( 'Thumbnail', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::MEDIA,
			]
		);

		$repeater->add_control(
			'video',
			[
				'label'       => esc_html__( 'Video URL', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'label_block' => true,
				'description' => esc_html__( 'YouTube, Vimeo, or a direct .mp4 / .webm file.', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'rating',
			[
				'label'   => esc_html__( 'Rating', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 5,
				'step'    => 1,
			]
		);

		$repeater->add_control(
			'quote',
			[
				'label' => esc_html__( 'Review Text', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 3,
			]
		);

		$repeater->add_control(
			'product',
			[
				'label'       => esc_html__( 'Top Pick Product', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Review_Engine::product_options(),
				'label_block' => true,
				'description' => esc_html__( 'Optional. Shows a product promo inside the review modal.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'videos',
			[
				'label'       => esc_html__( 'Video Reviews', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => [],
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Social proofs section (display switches + repeater).
	 *
	 * @return void
	 */
	protected function register_proofs_section() {
		$this->start_controls_section(
			'rv_section_proofs',
			[
				'label' => esc_html__( 'Social Proofs', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_card_name',
			[
				'label'        => esc_html__( 'Show Name', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_verified',
			[
				'label'        => esc_html__( 'Show Verified Badge', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_proof_stars',
			[
				'label'        => esc_html__( 'Show Stars', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'name',
			[
				'label'   => esc_html__( 'Customer Name', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Customer Name', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'images',
			[
				'label'       => esc_html__( 'Images', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::GALLERY,
				'default'     => [],
				'description' => esc_html__( 'The first image is the card cover; all images appear in the modal slider.', 'hkdev-shop-elements' ),
			]
		);

		$repeater->add_control(
			'rating',
			[
				'label'   => esc_html__( 'Rating', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 5,
				'step'    => 1,
			]
		);

		$repeater->add_control(
			'quote',
			[
				'label' => esc_html__( 'Review Text', 'hkdev-shop-elements' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 3,
			]
		);

		$repeater->add_control(
			'product',
			[
				'label'       => esc_html__( 'Featured Product', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => Review_Engine::product_options(),
				'label_block' => true,
				'description' => esc_html__( 'Optional. Shows a product promo inside the review modal.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'proofs',
			[
				'label'       => esc_html__( 'Social Proofs', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => [],
				'separator'   => 'before',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Filter section.
	 *
	 * @return void
	 */
	protected function register_filter_section() {
		$this->start_controls_section(
			'rv_section_filter',
			[
				'label' => esc_html__( 'Filter', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_filter',
			[
				'label'        => esc_html__( 'Show Sort Filter', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'filter_label',
			[
				'label'     => esc_html__( 'Filter Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Filter by:', 'hkdev-shop-elements' ),
				'condition' => [ 'show_filter' => 'yes' ],
			]
		);

		$this->add_control(
			'filter_highest',
			[
				'label'     => esc_html__( '"Highest Rating" Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Highest Rating', 'hkdev-shop-elements' ),
				'condition' => [ 'show_filter' => 'yes' ],
			]
		);

		$this->add_control(
			'filter_recent',
			[
				'label'     => esc_html__( '"Most Recent" Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Most Recent', 'hkdev-shop-elements' ),
				'condition' => [ 'show_filter' => 'yes' ],
			]
		);

		$this->add_control(
			'filter_default',
			[
				'label'     => esc_html__( 'Default Sort', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'recent',
				'options'   => [
					'recent'  => esc_html__( 'Most Recent (author order)', 'hkdev-shop-elements' ),
					'highest' => esc_html__( 'Highest Rating', 'hkdev-shop-elements' ),
				],
				'condition' => [ 'show_filter' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Labels, buttons and modal section.
	 *
	 * @return void
	 */
	protected function register_labels_section() {
		$this->start_controls_section(
			'rv_section_labels',
			[
				'label' => esc_html__( 'Labels & Modal', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_product',
			[
				'label'        => esc_html__( 'Show Product Promo', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Turns the product block in every modal on/off.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'top_pick_label',
			[
				'label'     => esc_html__( 'Top Pick Ribbon', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Top Pick', 'hkdev-shop-elements' ),
				'condition' => [ 'show_product' => 'yes' ],
			]
		);

		$this->add_control(
			'order_button_text',
			[
				'label'     => esc_html__( 'Video Modal Button', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Order Now', 'hkdev-shop-elements' ),
				'condition' => [ 'show_product' => 'yes' ],
			]
		);

		$this->add_control(
			'view_button_text',
			[
				'label'     => esc_html__( 'Review Modal Button', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'View Product Details', 'hkdev-shop-elements' ),
				'condition' => [ 'show_product' => 'yes' ],
			]
		);

		$this->add_control(
			'show_modal_quote',
			[
				'label'        => esc_html__( 'Show Review Text in Modal', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'show_modal_badge',
			[
				'label'        => esc_html__( 'Show Badge in Modal', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'video_badge',
			[
				'label'     => esc_html__( 'Video Badge Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Verified Customer', 'hkdev-shop-elements' ),
				'condition' => [ 'show_modal_badge' => 'yes' ],
			]
		);

		$this->add_control(
			'proof_badge',
			[
				'label'     => esc_html__( 'Review Badge Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Verified Purchase', 'hkdev-shop-elements' ),
				'condition' => [ 'show_modal_badge' => 'yes' ],
			]
		);

		$this->add_control(
			'video_empty',
			[
				'label'     => esc_html__( 'Video Empty Message', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'No video reviews to show yet.', 'hkdev-shop-elements' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'written_empty',
			[
				'label'   => esc_html__( 'Review Empty Message', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'No reviews to show yet.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 *
	 * @return void
	 */
	protected function register_style_controls() {
		$this->register_style_layout_section();
		$this->register_style_colors_section();
		$this->register_style_header_section();
		$this->register_style_tabs_section();
		$this->register_style_video_section();
		$this->register_style_proof_section();
		$this->register_style_promo_section();
		$this->register_style_modal_section();
	}

	/**
	 * Layout style section.
	 *
	 * @return void
	 */
	protected function register_style_layout_section() {
		$scope = '{{WRAPPER}} .hkdev-rv';

		$this->start_controls_section(
			'rv_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'rv_max_width', esc_html__( 'Container Max Width', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-container', 'max-width', 400, 1800 );
		$this->hkdev_dimensions( 'rv_container_padding', esc_html__( 'Container Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-container', 'padding' );
		$this->hkdev_dimensions( 'rv_block_margin', esc_html__( 'Section Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'rv_block_padding', esc_html__( 'Section Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_slider( 'rv_grid_gap', esc_html__( 'Gap Between Cards', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-gap', 0, 60 );
		$this->hkdev_slider( 'rv_header_spacing', esc_html__( 'Header Bottom Spacing', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-header', 'margin-bottom', 0, 80 );
		$this->hkdev_slider( 'rv_tabs_spacing', esc_html__( 'Tabs Bottom Spacing', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tabs', 'margin-bottom', 0, 80 );

		$columns = [
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
		];

		$this->hkdev_select( 'rv_video_cols', esc_html__( 'Video Columns', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-video-cols', $columns );
		$this->hkdev_select( 'rv_proof_cols', esc_html__( 'Review Columns', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-proof-cols', $columns );

		$ratio = [
			''       => esc_html__( 'Default', 'hkdev-shop-elements' ),
			'1 / 1'  => esc_html__( 'Square (1:1)', 'hkdev-shop-elements' ),
			'4 / 5'  => esc_html__( 'Portrait (4:5)', 'hkdev-shop-elements' ),
			'3 / 4'  => esc_html__( 'Portrait (3:4)', 'hkdev-shop-elements' ),
			'9 / 16' => esc_html__( 'Reel (9:16)', 'hkdev-shop-elements' ),
			'4 / 3'  => esc_html__( 'Landscape (4:3)', 'hkdev-shop-elements' ),
			'16 / 9' => esc_html__( 'Wide (16:9)', 'hkdev-shop-elements' ),
		];

		$this->hkdev_select( 'rv_video_ratio', esc_html__( 'Video Card Shape', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-video-ratio', $ratio );
		$this->hkdev_select( 'rv_proof_ratio', esc_html__( 'Review Card Image Shape', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-proof-ratio', $ratio );

		$this->end_controls_section();
	}

	/**
	 * Colours style section.
	 *
	 * @return void
	 */
	protected function register_style_colors_section() {
		$scope = '{{WRAPPER}} .hkdev-rv';

		$this->start_controls_section(
			'rv_style_colors',
			[
				'label' => esc_html__( 'Colours', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_primary', esc_html__( 'Primary Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-primary' );
		$this->hkdev_color( 'rv_primary_dark', esc_html__( 'Primary Hover Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-primary-dark' );
		$this->hkdev_color( 'rv_secondary', esc_html__( 'Accent Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-secondary' );
		$this->hkdev_color( 'rv_section_bg', esc_html__( 'Section Background', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-section-bg' );
		$this->hkdev_color( 'rv_card_bg', esc_html__( 'Card / Modal Background', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-card-bg' );
		$this->hkdev_color( 'rv_soft_bg', esc_html__( 'Soft Background', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-soft' );
		$this->hkdev_color( 'rv_text', esc_html__( 'Text Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-text' );
		$this->hkdev_color( 'rv_muted', esc_html__( 'Muted Text Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-muted' );
		$this->hkdev_color( 'rv_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-border' );
		$this->hkdev_color( 'rv_star_on', esc_html__( 'Star Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-star' );
		$this->hkdev_color( 'rv_star_off', esc_html__( 'Empty Star Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-star-off' );
		$this->hkdev_slider( 'rv_star_size', esc_html__( 'Star Size', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-star-size', 8, 40 );

		$this->end_controls_section();
	}

	/**
	 * Header typography style section.
	 *
	 * @return void
	 */
	protected function register_style_header_section() {
		$this->start_controls_section(
			'rv_style_header',
			[
				'label' => esc_html__( 'Header', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'rv_title', esc_html__( 'Heading', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-title' );
		$this->hkdev_typography( 'rv_subtitle', esc_html__( 'Subheading', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-subtitle' );
		$this->hkdev_color( 'rv_subtitle_accent', esc_html__( 'Subheading Highlight', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-subtitle strong, {{WRAPPER}} .hkdev-rv-subtitle b', 'color' );

		$this->end_controls_section();
	}

	/**
	 * Tabs style section.
	 *
	 * @return void
	 */
	protected function register_style_tabs_section() {
		$this->start_controls_section(
			'rv_style_tabs',
			[
				'label' => esc_html__( 'Tabs', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_tab_bar_bg', esc_html__( 'Tab Bar Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-list', 'background-color' );
		$this->hkdev_color( 'rv_tab_bar_border', esc_html__( 'Tab Bar Border', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-list', 'border-color' );
		$this->hkdev_dimensions( 'rv_tab_bar_radius', esc_html__( 'Tab Bar Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-list', 'border-radius' );
		$this->hkdev_dimensions( 'rv_tab_bar_padding', esc_html__( 'Tab Bar Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-list', 'padding' );
		$this->hkdev_color( 'rv_tab_color', esc_html__( 'Tab Text Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn', 'color' );
		$this->hkdev_color( 'rv_tab_active_bg', esc_html__( 'Active Tab Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn.is-active', 'background-color' );
		$this->hkdev_color( 'rv_tab_active_color', esc_html__( 'Active Tab Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn.is-active', 'color' );
		$this->hkdev_typography( 'rv_tab_font', esc_html__( 'Tab Label', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn' );
		$this->hkdev_dimensions( 'rv_tab_padding', esc_html__( 'Tab Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn', 'padding' );
		$this->hkdev_dimensions( 'rv_tab_radius', esc_html__( 'Tab Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-tab-btn', 'border-radius' );

		$this->end_controls_section();
	}

	/**
	 * Video card style section.
	 *
	 * @return void
	 */
	protected function register_style_video_section() {
		$scope = '{{WRAPPER}} .hkdev-rv';

		$this->start_controls_section(
			'rv_style_video',
			[
				'label' => esc_html__( 'Video Cards', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_vcard_border', esc_html__( 'Card Border', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vcard', 'border-color' );
		$this->hkdev_dimensions( 'rv_vcard_radius', esc_html__( 'Card Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vcard', 'border-radius' );
		$this->hkdev_shadow( 'rv_vcard_shadow', esc_html__( 'Card Shadow', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vcard' );
		$this->hkdev_color( 'rv_overlay', esc_html__( 'Overlay Colour', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-overlay' );
		$this->hkdev_slider( 'rv_play_size', esc_html__( 'Play Button Size', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-play-size', 24, 110 );
		$this->hkdev_color( 'rv_play_bg', esc_html__( 'Play Button Background', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-play-bg' );
		$this->hkdev_color( 'rv_play_color', esc_html__( 'Play Button Icon', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-play-color' );
		$this->hkdev_color( 'rv_play_hover_bg', esc_html__( 'Play Button Hover Background', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-play-hover-bg' );
		$this->hkdev_typography( 'rv_vname', esc_html__( 'Card Name', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vname' );

		$this->end_controls_section();
	}

	/**
	 * Review card style section.
	 *
	 * @return void
	 */
	protected function register_style_proof_section() {
		$this->start_controls_section(
			'rv_style_proof',
			[
				'label' => esc_html__( 'Review Cards', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_pcard_bg', esc_html__( 'Card Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pcard', 'background-color' );
		$this->hkdev_color( 'rv_pcard_border', esc_html__( 'Card Border', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pcard', 'border-color' );
		$this->hkdev_dimensions( 'rv_pcard_radius', esc_html__( 'Card Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pcard', 'border-radius' );
		$this->hkdev_shadow( 'rv_pcard_shadow', esc_html__( 'Card Shadow', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pcard' );
		$this->hkdev_dimensions( 'rv_pbody_padding', esc_html__( 'Content Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pbody', 'padding' );
		$this->hkdev_typography( 'rv_pname', esc_html__( 'Card Name', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pname' );
		$this->hkdev_color( 'rv_verified_color', esc_html__( 'Verified Badge Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pverified', 'color' );
		$this->hkdev_slider( 'rv_verified_size', esc_html__( 'Verified Badge Size', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pverified', 'font-size', 8, 20 );

		$this->end_controls_section();
	}

	/**
	 * Product promo style section.
	 *
	 * @return void
	 */
	protected function register_style_promo_section() {
		$this->start_controls_section(
			'rv_style_promo',
			[
				'label' => esc_html__( 'Product Promo', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_promo_bg', esc_html__( 'Promo Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-product', 'background-color' );
		$this->hkdev_color( 'rv_promo_border', esc_html__( 'Promo Border', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-product', 'border-color' );
		$this->hkdev_dimensions( 'rv_promo_radius', esc_html__( 'Promo Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-product', 'border-radius' );
		$this->hkdev_dimensions( 'rv_promo_padding', esc_html__( 'Promo Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-product', 'padding' );
		$this->hkdev_color( 'rv_toppick_bg', esc_html__( 'Ribbon Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-toppick', 'background-color' );
		$this->hkdev_color( 'rv_toppick_color', esc_html__( 'Ribbon Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-toppick', 'color' );
		$this->hkdev_typography( 'rv_ptitle', esc_html__( 'Product Title', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-prod-title' );
		$this->hkdev_typography( 'rv_pprice', esc_html__( 'Product Price', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-prod-price' );
		$this->hkdev_color( 'rv_pold_color', esc_html__( 'Old (Struck) Price', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-prod-price del', 'color' );
		$this->hkdev_color( 'rv_pmeta_color', esc_html__( 'Rating Meta Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-prod-meta', 'color' );
		$this->hkdev_typography( 'rv_btn', esc_html__( 'Button Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn' );
		$this->hkdev_color( 'rv_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn', 'background-color' );
		$this->hkdev_color( 'rv_btn_hover_bg', esc_html__( 'Button Hover Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn:hover', 'background-color' );
		$this->hkdev_color( 'rv_btn_hover_color', esc_html__( 'Button Hover Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn:hover', 'color' );
		$this->hkdev_dimensions( 'rv_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn', 'border-radius' );
		$this->hkdev_dimensions( 'rv_btn_padding', esc_html__( 'Button Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-order-btn', 'padding' );

		$this->end_controls_section();
	}

	/**
	 * Modal style section.
	 *
	 * @return void
	 */
	protected function register_style_modal_section() {
		$scope = '{{WRAPPER}} .hkdev-rv';

		$this->start_controls_section(
			'rv_style_modal',
			[
				'label' => esc_html__( 'Modal', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'rv_modal_overlay', esc_html__( 'Overlay Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal, {{WRAPPER}} .hkdev-rv-pmodal', 'background-color' );
		$this->hkdev_color( 'rv_modal_bg', esc_html__( 'Modal Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-content, {{WRAPPER}} .hkdev-rv-pmodal-content', 'background-color' );
		$this->hkdev_dimensions( 'rv_modal_radius', esc_html__( 'Modal Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-content, {{WRAPPER}} .hkdev-rv-pmodal-content', 'border-radius' );
		$this->hkdev_slider( 'rv_modal_video_w', esc_html__( 'Video Modal Width', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-modal-video-w', 280, 900 );
		$this->hkdev_select(
			'rv_modal_media_ratio',
			esc_html__( 'Video Modal Shape', 'hkdev-shop-elements' ),
			$scope,
			'--hkdev-rv-modal-media-ratio',
			[
				''       => esc_html__( 'Default (16:9)', 'hkdev-shop-elements' ),
				'16 / 9' => esc_html__( 'Wide (16:9)', 'hkdev-shop-elements' ),
				'4 / 3'  => esc_html__( 'Classic (4:3)', 'hkdev-shop-elements' ),
				'1 / 1'  => esc_html__( 'Square (1:1)', 'hkdev-shop-elements' ),
				'9 / 16' => esc_html__( 'Reel (9:16)', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_slider( 'rv_modal_proof_w', esc_html__( 'Review Modal Width', 'hkdev-shop-elements' ), $scope, '--hkdev-rv-modal-proof-w', 480, 1600 );
		$this->hkdev_color( 'rv_close_bg', esc_html__( 'Close Button Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-modal-close, {{WRAPPER}} .hkdev-rv-pmodal-close', 'background-color' );
		$this->hkdev_color( 'rv_close_color', esc_html__( 'Close Button Icon', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-modal-close, {{WRAPPER}} .hkdev-rv-pmodal-close', 'color' );
		$this->hkdev_color( 'rv_close_hover_bg', esc_html__( 'Close Button Hover', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-modal-close:hover, {{WRAPPER}} .hkdev-rv-pmodal-close:hover', 'background-color' );
		$this->hkdev_typography( 'rv_mname', esc_html__( 'Modal Name', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-name, {{WRAPPER}} .hkdev-rv-pmodal-name' );
		$this->hkdev_color( 'rv_badge_bg', esc_html__( 'Modal Badge Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-badge, {{WRAPPER}} .hkdev-rv-pmodal-badge', 'background-color' );
		$this->hkdev_color( 'rv_badge_color', esc_html__( 'Modal Badge Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-badge, {{WRAPPER}} .hkdev-rv-pmodal-badge', 'color' );
		$this->hkdev_typography( 'rv_quote', esc_html__( 'Modal Review Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-vmodal-quote, {{WRAPPER}} .hkdev-rv-pmodal-quote' );
		$this->hkdev_color( 'rv_slider_bg', esc_html__( 'Slider Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-pmodal-left', 'background-color' );
		$this->hkdev_color( 'rv_slider_btn_bg', esc_html__( 'Slider Arrow Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-slider-btn', 'background-color' );
		$this->hkdev_color( 'rv_slider_btn_color', esc_html__( 'Slider Arrow Icon', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-slider-btn', 'color' );
		$this->hkdev_color( 'rv_counter_bg', esc_html__( 'Counter Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-slider-counter', 'background-color' );
		$this->hkdev_color( 'rv_counter_color', esc_html__( 'Counter Text', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-slider-counter', 'color' );

		$this->end_controls_section();

		$this->start_controls_section(
			'rv_style_misc',
			[
				'label' => esc_html__( 'Empty State & Filter', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'rv_empty', esc_html__( 'Empty Message', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-empty' );
		$this->hkdev_typography( 'rv_filter_label', esc_html__( 'Filter Label', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-filter-label' );
		$this->hkdev_color( 'rv_filter_bg', esc_html__( 'Filter Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-filter-select', 'background-color' );
		$this->hkdev_color( 'rv_filter_color', esc_html__( 'Filter Text Colour', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-filter-select', 'color' );
		$this->hkdev_color( 'rv_filter_border', esc_html__( 'Filter Border', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-filter-select', 'border-color' );
		$this->hkdev_dimensions( 'rv_filter_radius', esc_html__( 'Filter Radius', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-rv-filter-select', 'border-radius' );

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

		$yes_no = static function ( $key ) use ( $settings ) {
			return ( isset( $settings[ $key ] ) && 'yes' === $settings[ $key ] );
		};

		$videos = [];
		if ( ! empty( $settings['videos'] ) && is_array( $settings['videos'] ) ) {
			foreach ( $settings['videos'] as $row ) {
				$videos[] = [
					'name'       => isset( $row['name'] ) ? $row['name'] : '',
					'image'      => ! empty( $row['thumbnail']['url'] ) ? $row['thumbnail']['url'] : '',
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'video'      => isset( $row['video'] ) ? $row['video'] : '',
					'quote'      => isset( $row['quote'] ) ? $row['quote'] : '',
					'product_id' => isset( $row['product'] ) ? absint( $row['product'] ) : 0,
				];
			}
		}

		$proofs = [];
		if ( ! empty( $settings['proofs'] ) && is_array( $settings['proofs'] ) ) {
			foreach ( $settings['proofs'] as $row ) {
				$images = [];
				if ( ! empty( $row['images'] ) && is_array( $row['images'] ) ) {
					foreach ( $row['images'] as $image ) {
						if ( ! empty( $image['url'] ) ) {
							$images[] = $image['url'];
						}
					}
				}

				$proofs[] = [
					'name'       => isset( $row['name'] ) ? $row['name'] : '',
					'images'     => $images,
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'quote'      => isset( $row['quote'] ) ? $row['quote'] : '',
					'product_id' => isset( $row['product'] ) ? absint( $row['product'] ) : 0,
				];
			}
		}

		$config = [
			'anchor'               => isset( $settings['anchor'] ) ? sanitize_title( $settings['anchor'] ) : '',
			'heading'              => isset( $settings['heading'] ) ? $settings['heading'] : '',
			'subheading'           => isset( $settings['subheading'] ) ? $settings['subheading'] : '',
			'show_header'          => $yes_no( 'show_header' ),
			'show_heading'         => true,
			'show_subheading'      => true,
			'show_tabs'            => $yes_no( 'show_tabs' ),
			'tab_video_label'      => isset( $settings['tab_video_label'] ) ? $settings['tab_video_label'] : '',
			'tab_written_label'    => isset( $settings['tab_written_label'] ) ? $settings['tab_written_label'] : '',
			'default_tab'          => isset( $settings['default_tab'] ) ? $settings['default_tab'] : 'written',
			'show_video_name'      => $yes_no( 'show_video_name' ),
			'show_video_stars'     => $yes_no( 'show_video_stars' ),
			'show_video_play'      => $yes_no( 'show_video_play' ),
			'show_video_overlay'   => $yes_no( 'show_video_overlay' ),
			'video_thumb_fallback' => $yes_no( 'video_thumb_fallback' ),
			'show_card_name'       => $yes_no( 'show_card_name' ),
			'show_verified'        => $yes_no( 'show_verified' ),
			'show_proof_stars'     => $yes_no( 'show_proof_stars' ),
			'show_modal_quote'     => $yes_no( 'show_modal_quote' ),
			'show_modal_badge'     => $yes_no( 'show_modal_badge' ),
			'show_product'         => $yes_no( 'show_product' ),
			'video_empty'          => isset( $settings['video_empty'] ) ? $settings['video_empty'] : '',
			'written_empty'        => isset( $settings['written_empty'] ) ? $settings['written_empty'] : '',
			'show_filter'          => $yes_no( 'show_filter' ),
			'filter_label'         => isset( $settings['filter_label'] ) ? $settings['filter_label'] : '',
			'filter_default'       => isset( $settings['filter_default'] ) ? $settings['filter_default'] : 'recent',
			'filter_highest'       => isset( $settings['filter_highest'] ) ? $settings['filter_highest'] : '',
			'filter_recent'        => isset( $settings['filter_recent'] ) ? $settings['filter_recent'] : '',
			'top_pick_label'       => isset( $settings['top_pick_label'] ) ? $settings['top_pick_label'] : '',
			'order_button_text'    => isset( $settings['order_button_text'] ) ? $settings['order_button_text'] : '',
			'view_button_text'     => isset( $settings['view_button_text'] ) ? $settings['view_button_text'] : '',
			'video_badge'          => isset( $settings['video_badge'] ) ? $settings['video_badge'] : '',
			'proof_badge'          => isset( $settings['proof_badge'] ) ? $settings['proof_badge'] : '',
			'videos'               => $videos,
			'proofs'               => $proofs,
		];

		echo Review_Engine::instance()->render( $config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
