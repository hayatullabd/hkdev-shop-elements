<?php
/**
 * HKDEV Catalog Widget (HKDEV Shop Elements plugin).
 *
 * Advanced search + filter + sort catalog block (AJAX, load-more, URL state).
 * Works on any page; on the WooCommerce Shop / category archives the same
 * filter bar is injected automatically.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Core\CatalogEngine;

/**
 * Class CatalogWidget
 */
class CatalogWidget extends Widget_Base {

	use Product_Controls;
	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_catalog';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Catalog (Search + Filter)', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-filter';
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
		return [ 'shop', 'catalog', 'filter', 'search', 'sort', 'archive', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-catalog-style', 'hkdev-elements-shop-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-catalog-js', 'hkdev-elements-shop-js' ];
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
				'label' => esc_html__( 'Content', 'hkdev-shop-elements' ),
			]
		);

		$this->register_cards_per_view_controls( '4' );

		$this->add_control(
			'per_page',
			[
				'label'   => esc_html__( 'Products per page', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 60,
			]
		);

		$this->register_design_controls();

		$this->add_control(
			'categories',
			[
				'label'       => esc_html__( 'Limit to categories', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Comma-separated product category slugs. Leave empty for all.', 'hkdev-shop-elements' ),
				'default'     => '',
			]
		);

		$this->add_control(
			'show_search',
			[
				'label'        => esc_html__( 'Show search', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_sort',
			[
				'label'        => esc_html__( 'Show sort dropdown', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'show_filters',
			[
				'label'        => esc_html__( 'Show filter panel', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'hide_hidden',
			[
				'label'        => esc_html__( 'Hide "Hidden" products', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Excludes products whose WooCommerce catalog visibility is set to Hidden. Off by default, so the Catalog lists exactly what the Shop Grid lists.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->register_product_title_controls();
		$this->register_product_image_controls( true );
		$this->register_buy_now_controls();

		// Product card styling (same controls as the Shop Grid widget).
		$this->register_style_sections( '{{WRAPPER}} .hkdev-catalog' );
	}

	/**
	 * Render the widget.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( CatalogEngine::class ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$cols     = $this->get_cards_per_view( $settings );

		echo CatalogEngine::instance()->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array_merge(
				$this->get_design_atts( $settings ),
				$this->get_image_atts( $settings ),
				$this->get_buy_now_atts( $settings ),
				[
					'columns'        => (string) $cols['desktop'],
					'columns_tablet' => (string) $cols['tablet'],
					'columns_mobile' => (string) $cols['mobile'],
					'per_page'       => $settings['per_page'],
					'categories'     => $settings['categories'],
					'show_search'    => $settings['show_search'],
					'show_sort'      => $settings['show_sort'],
					'show_filters'   => $settings['show_filters'],
					'hover_img'      => $this->get_hover_img( $settings ),
					'hide_hidden'    => ( isset( $settings['hide_hidden'] ) && 'yes' === $settings['hide_hidden'] ) ? 'yes' : 'no',
					'title_lines'    => $this->get_title_lines( $settings ),
				]
			),
			false
		);
	}
}
