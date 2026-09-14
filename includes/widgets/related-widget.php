<?php
/**
 * HKDEV Related Products Widget (HKDEV Shop Elements plugin).
 *
 * Renders WooCommerce related products using the exact same product card as
 * the shop grid. Self-contained – does not require the hkdev-shop theme.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Class Related_Widget
 */
class Related_Widget extends Widget_Base {

	use Product_Controls;
	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_related_products';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Related Products', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-related';
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
		return [ 'related', 'product', 'woocommerce', 'upsell', 'cross-sell' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-shop-style', 'hkdev-elements-wishlist-style', 'hkdev-elements-swiper-css', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-shop-js', 'hkdev-elements-wishlist-js', 'hkdev-elements-swiper-js' ];
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

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Related Products', 'hkdev-shop-elements' ),
				'placeholder' => esc_html__( 'Related Products', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_title',
			[
				'label'        => esc_html__( 'Show Heading', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_query',
			[
				'label' => esc_html__( 'Query', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'id',
			[
				'label'       => esc_html__( 'Product ID (optional)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to use the product from the current page.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => esc_html__( 'Number of Products', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 20,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'   => esc_html__( 'Image Size', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'woocommerce_thumbnail',
				'options' => $this->hkdev_image_size_options(),
			]
		);

		$this->add_control(
			'style',
			[
				'label'   => esc_html__( 'Layout Style', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid'     => esc_html__( 'Grid', 'hkdev-shop-elements' ),
					'carousel' => esc_html__( 'Carousel', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'fallback',
			[
				'label'        => esc_html__( 'Fallback to Same Category', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'If no WooCommerce related products are found, show products from the same category.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->register_product_controls();
		$this->register_style_sections();
	}

	/**
	 * Render the widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$product_id = ! empty( $settings['id'] ) ? absint( $settings['id'] ) : 0;
		if ( ! $product_id && is_product() ) {
			$product_id = get_the_ID();
		}

		if ( ! $product_id ) {
			return;
		}

		$limit = isset( $settings['limit'] ) ? max( 1, absint( $settings['limit'] ) ) : 4;

		$atts = [
			'limit'            => $limit,
			'columns'          => isset( $settings['columns'] ) ? $settings['columns'] : '4',
			'image_size'       => isset( $settings['image_size'] ) ? sanitize_key( $settings['image_size'] ) : 'woocommerce_thumbnail',
			'style'            => isset( $settings['style'] ) ? $settings['style'] : 'grid',
			'show_tabs'        => 'no',
			'include_children' => 'yes',
			'type'             => 'recent',
			'carousel'         => $this->get_carousel_config( $settings ),
			'title_lines'      => $this->get_title_lines( $settings ),
			'wishlist_btn'     => $this->get_wishlist_btn( $settings ),
		];

		$related_ids = \HkdevShopElements\Includes\Shop_Engine::instance()->get_related_product_ids( $product_id, $limit );

		if ( ! empty( $related_ids ) ) {
			$atts['product_ids'] = implode( ',', $related_ids );
		} elseif ( isset( $settings['fallback'] ) && 'yes' === $settings['fallback'] ) {
			$atts['is_related'] = 'yes';
			$atts['id']         = $product_id;
		} else {
			return;
		}

		$grid = \HkdevShopElements\Includes\Shop_Engine::instance()->master_shop_shortcode( $atts );

		$show_title = isset( $settings['show_title'] ) && 'yes' === $settings['show_title'];
		$title      = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';

		echo '<div class="hkdev-related-wrapper">';
		if ( $show_title && '' !== $title ) {
			echo '<h2 class="hkdev-related-heading">' . esc_html( $title ) . '</h2>';
		}
		echo $grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
