<?php
/**
 * HKDEV Blog Widget (Elementor).
 *
 * Renders a styled blog post grid / list. Configurable via Elementor controls.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Blog_Engine;
use HkdevShopElements\Includes\Shop_Engine;

class Blog_Widget extends Widget_Base {

	use Style_Controls;
	use Heading_Controls;

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_blog';
	}

	/**
	 * Widget title (Elementor panel).
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'HKDEV Blog', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-ticker';
	}

	/**
	 * Widget category.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return [ 'general' ];
	}

	/**
	 * Keywords for Elementor search.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return [ 'blog', 'post', 'posts', 'news', 'latest', 'category', 'tabs' ];
	}

	/**
	 * Style dependencies.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-blog-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script dependencies.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-blog-js' ];
	}

	/**
	 * Register content controls.
	 */
	protected function register_controls() {
		// Section heading (accent bar + heading + subtitle + "View All" link).
		// Same trait and renderer as the Shop Grid / Section Heading widgets.
		$this->register_heading_controls();

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'hkdev-shop-elements' ),
				'icon'  => 'eicon-posts-ticker',
			]
		);

		$this->add_control(
			'layout',
			[
				'label'     => __( 'Layout', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'grid' => __( 'Grid', 'hkdev-shop-elements' ),
					'list' => __( 'List', 'hkdev-shop-elements' ),
				],
				'default'   => 'grid',
			]
		);

		$this->add_control(
			'columns',
			[
				'label'     => __( 'Columns', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					1 => '1',
					2 => '2',
					3 => '3',
					4 => '4',
					5 => '5',
					6 => '6',
				],
				'default'   => 3,
				'condition' => [ 'layout' => 'grid' ],
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => __( 'Number of Posts', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 50,
				'default' => 9,
			]
		);

		$this->add_control(
			'category',
			[
				'label'     => __( 'Categories (slug)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'label_block' => false,
				'placeholder' => 'news, tech, design',
				'help'    => __( 'Comma-separated category slugs. Leave empty for all.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'     => __( 'Order By', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'date'          => __( 'Date', 'hkdev-shop-elements' ),
					'title'         => __( 'Title', 'hkdev-shop-elements' ),
					'comment_count' => __( 'Comment Count', 'hkdev-shop-elements' ),
					'rand'          => __( 'Random', 'hkdev-shop-elements' ),
				],
				'default'   => 'date',
			]
		);

		$this->add_control(
			'order',
			[
				'label'     => __( 'Order', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'DESC' => __( 'Descending', 'hkdev-shop-elements' ),
					'ASC'  => __( 'Ascending', 'hkdev-shop-elements' ),
				],
				'default'   => 'DESC',
			]
		);

		$this->add_control(
			'image_ratio',
			[
				'label'     => __( 'Image Ratio', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'1:1'  => '1:1',
					'4:3'  => '4:3',
					'16:9' => '16:9',
					'auto' => __( 'Auto', 'hkdev-shop-elements' ),
				],
				'default'   => '16:9',
				'condition' => [ 'show_image' => 'yes' ],
			]
		);

		$this->add_control(
			'show_image',
			[
				'label'        => __( 'Show Featured Image', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'hkdev-shop-elements' ),
				'label_off'    => __( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_excerpt',
			[
				'label'        => __( 'Show Excerpt', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'hkdev-shop-elements' ),
				'label_off'    => __( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_meta',
			[
				'label'        => __( 'Show Meta Info', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'hkdev-shop-elements' ),
				'label_off'    => __( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_readmore',
			[
				'label'        => __( 'Show Read More', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'hkdev-shop-elements' ),
				'label_off'    => __( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'readmore_text',
			[
				'label'     => __( 'Read More Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Read More', 'hkdev-shop-elements' ),
				'condition' => [ 'show_readmore' => 'yes' ],
			]
		);

		$this->add_control(
			'show_loadmore',
			[
				'label'        => __( 'Load More Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'hkdev-shop-elements' ),
				'label_off'    => __( 'Off', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
				'description'  => __( 'Appends the next batch of posts without reloading the page. Hidden when everything already fits on one page.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'loadmore_text',
			[
				'label'     => __( 'Load More Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Load More', 'hkdev-shop-elements' ),
				'condition' => [ 'show_loadmore' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// ---- Content: Category Tabs ----
		$this->start_controls_section(
			'tabs_section',
			[
				'label' => __( 'Category Tabs', 'hkdev-shop-elements' ),
				'icon'  => 'eicon-tabs',
			]
		);

		$this->add_control(
			'show_tabs',
			[
				'label'        => __( 'Show Category Tabs', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => __( 'No', 'hkdev-shop-elements' ),
				'default'      => 'no',
				'return_value' => 'yes',
				'description'  => __( 'Tabs filter the grid without reloading the page.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'tabs',
			[
				'label'       => __( 'Categories', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => [
					[
						'name'        => 'category',
						'label'       => __( 'Category', 'hkdev-shop-elements' ),
						'type'        => Controls_Manager::SELECT2,
						'options'     => Shop_Engine::term_options( 'category' ),
						'label_block' => true,
					],
				],
				'title_field' => '{{{ category }}}',
				'default'     => [],
				'condition'   => [ 'show_tabs' => 'yes' ],
				'description' => __( 'Add categories to set the tab order, then drag to rearrange. Leave empty to list categories automatically.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		// ---- Style: Layout & Spacing ----
		// Every control is scoped to {{WRAPPER}} so two Blog widgets on the same
		// page can be styled independently.
		$w = '{{WRAPPER}} ';

		$this->start_controls_section(
			'style_layout',
			[
				'label' => __( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_slider( 'items_gap', __( 'Gap Between Cards', 'hkdev-shop-elements' ), $w . '.hkdev-blog-items', 'gap', 0, 60 );

		$this->hkdev_slider( 'image_radius', __( 'Image Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-img-wrap', 'border-radius', 0, 40 );

		$this->add_control(
			'image_zoom',
			[
				'label'     => __( 'Image Hover Zoom', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [ 'px' => [ 'min' => 1, 'max' => 1.3, 'step' => 0.01 ] ],
				'default'   => [ 'size' => 1.05 ],
				'selectors' => [ $w . '.hkdev-blog-card:hover .hkdev-blog-card-img-wrap img' => 'transform: scale({{SIZE}}) !important;' ],
			]
		);

		$this->end_controls_section();

		// ---- Style: Card ----
		$this->start_controls_section(
			'style_card',
			[
				'label' => __( 'Card', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'card_bg', __( 'Card Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card', 'background-color' );

		$this->hkdev_color( 'card_border', __( 'Border Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card', 'border-color' );

		$this->hkdev_slider( 'card_radius', __( 'Border Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card', 'border-radius', 0, 40 );

		$this->hkdev_shadow( 'card_shadow', __( 'Box Shadow', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card' );

		$this->hkdev_dimensions( 'card_content_padding', __( 'Content Padding', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-body', 'padding' );

		$this->add_control(
			'cat_badge_heading',
			[
				'label'     => __( 'Category Badge', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->hkdev_color( 'cat_bg', __( 'Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-category', 'background-color' );

		$this->hkdev_color( 'cat_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-category', 'color' );

		$this->hkdev_slider( 'cat_radius', __( 'Border Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-category', 'border-radius', 0, 30 );

		$this->end_controls_section();

		// ---- Style: Title ----
		$this->start_controls_section(
			'style_title',
			[
				'label' => __( 'Title', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'title_typography', __( 'Title Typography', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-title, ' . $w . '.hkdev-blog-card-title a' );

		$this->hkdev_color( 'title_color', __( 'Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-title a' );

		$this->end_controls_section();

		// ---- Style: Meta ----
		$this->start_controls_section(
			'style_meta',
			[
				'label' => __( 'Meta', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'meta_color', __( 'Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-meta' );

		$this->hkdev_color( 'meta_icon_color', __( 'Icon Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-meta i' );

		$this->hkdev_typography( 'meta_typography', __( 'Meta Typography', 'hkdev-shop-elements' ), $w . '.hkdev-blog-card-meta' );

		$this->end_controls_section();

		// ---- Style: Excerpt ----
		$this->start_controls_section(
			'style_excerpt',
			[
				'label' => __( 'Excerpt', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'excerpt_color', __( 'Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-excerpt' );

		$this->hkdev_typography( 'excerpt_typography', __( 'Excerpt Typography', 'hkdev-shop-elements' ), $w . '.hkdev-blog-excerpt' );

		$this->hkdev_slider_raw( 'excerpt_lines', __( 'Maximum Lines', 'hkdev-shop-elements' ), $w . '.hkdev-blog-excerpt', '-webkit-line-clamp', 1, 8, 1 );

		$this->end_controls_section();

		// ---- Style: Button ----
		$this->start_controls_section(
			'style_button',
			[
				'label' => __( 'Button', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'button_typography', __( 'Typography', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore' );

		$this->hkdev_color( 'button_bg_color', __( 'Background Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore', 'background-color' );

		$this->hkdev_color( 'button_text_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore' );

		$this->hkdev_color( 'button_bg_hover', __( 'Hover Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore:hover', 'background-color' );

		$this->hkdev_color( 'button_text_hover', __( 'Hover Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore:hover' );

		$this->hkdev_slider( 'button_radius', __( 'Border Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore', 'border-radius', 0, 30 );

		$this->hkdev_dimensions( 'button_padding', __( 'Padding', 'hkdev-shop-elements' ), $w . '.hkdev-blog-readmore', 'padding' );

		$this->end_controls_section();

		// ---- Style: Load More ----
		$this->start_controls_section(
			'style_loadmore',
			[
				'label'     => __( 'Load More', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_loadmore' => 'yes' ],
			]
		);

		$this->hkdev_typography( 'loadmore_typography', __( 'Typography', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore' );

		$this->hkdev_color( 'loadmore_bg_color', __( 'Background Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore', 'background-color' );

		$this->hkdev_color( 'loadmore_text_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore' );

		$this->hkdev_color( 'loadmore_bg_hover', __( 'Hover Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore:hover', 'background-color' );

		$this->hkdev_color( 'loadmore_text_hover', __( 'Hover Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore:hover' );

		$this->hkdev_slider( 'loadmore_radius', __( 'Border Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore', 'border-radius', 0, 60 );

		$this->hkdev_dimensions( 'loadmore_padding', __( 'Padding', 'hkdev-shop-elements' ), $w . '.hkdev-blog-loadmore', 'padding' );

		$this->end_controls_section();

		// ---- Style: Category Tabs ----
		$this->start_controls_section(
			'style_tabs',
			[
				'label'     => __( 'Category Tabs', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_tabs' => 'yes' ],
			]
		);

		$this->hkdev_dimensions( 'tabs_margin', __( 'Spacing Below', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tabs', 'margin' );

		$this->hkdev_slider( 'tab_gap', __( 'Gap Between Tabs', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tabs-scroll', 'gap', 0, 30 );

		$this->hkdev_slider( 'tab_font_size', __( 'Font Size', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item', 'font-size', 8, 24 );

		$this->hkdev_dimensions( 'tab_padding', __( 'Padding', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item', 'padding' );

		$this->hkdev_slider( 'tab_radius', __( 'Border Radius', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item', 'border-radius', 0, 40 );

		$this->hkdev_color( 'tab_bg', __( 'Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item', 'background-color' );

		$this->hkdev_color( 'tab_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item' );

		$this->hkdev_color( 'tab_border', __( 'Border Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item', 'border-color' );

		$this->add_control(
			'tab_active_heading',
			[
				'label'     => __( 'Active Tab', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->hkdev_color( 'tab_active_bg', __( 'Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item.is-active', 'background-color' );

		$this->hkdev_color( 'tab_active_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-item.is-active' );

		$this->add_control(
			'tab_count_heading',
			[
				'label'     => __( 'Count Badge', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->hkdev_color( 'tab_count_bg', __( 'Background', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-count', 'background-color' );

		$this->hkdev_color( 'tab_count_color', __( 'Text Color', 'hkdev-shop-elements' ), $w . '.hkdev-blog-tab-count' );

		$this->end_controls_section();
	}

	/**
	 * Render the widget output.
	 */
	protected function render() {
		if ( ! class_exists( 'HkdevShopElements\Includes\Blog_Engine' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$atts = [
			'layout'         => $settings['layout'] ?? 'grid',
			'columns'        => (int) ( $settings['columns'] ?? 3 ),
			'posts_per_page' => (int) ( $settings['posts_per_page'] ?? 9 ),
			'category'       => $settings['category'] ?? '',
			'orderby'        => $settings['orderby'] ?? 'date',
			'order'          => $settings['order'] ?? 'DESC',
			'image_ratio'    => $settings['image_ratio'] ?? '16:9',
			'show_image'     => $settings['show_image'] ?? 'yes',
			'show_excerpt'   => $settings['show_excerpt'] ?? 'yes',
			'show_meta'      => $settings['show_meta'] ?? 'yes',
			'show_readmore'  => $settings['show_readmore'] ?? 'yes',
			'readmore_text'  => $settings['readmore_text'] ?? __( 'Read More', 'hkdev-shop-elements' ),
			'load_more'      => $settings['show_loadmore'] ?? 'yes',
			'load_more_text' => $settings['loadmore_text'] ?? __( 'Load More', 'hkdev-shop-elements' ),
			'show_tabs'      => $settings['show_tabs'] ?? 'no',
			'tabs'           => $this->get_tab_slugs( $settings ),
			'heading'        => $this->get_heading_config( $settings ),
		];

		echo Blog_Engine::instance()->render( $atts, false );
	}

	/**
	 * Category slugs from the tabs repeater.
	 *
	 * @param array $settings Widget settings.
	 * @return string[]
	 */
	private function get_tab_slugs( $settings ) {
		$slugs = [];

		if ( empty( $settings['tabs'] ) || ! is_array( $settings['tabs'] ) ) {
			return $slugs;
		}

		foreach ( $settings['tabs'] as $item ) {
			if ( ! empty( $item['category'] ) ) {
				$slugs[] = sanitize_title( $item['category'] );
			}
		}

		return array_values( array_unique( $slugs ) );
	}
}
