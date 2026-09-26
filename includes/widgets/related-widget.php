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
use HkdevShopElements\Includes\Core\ShopEngine;

/**
 * Class RelatedWidget
 */
class RelatedWidget extends Widget_Base {

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
		return esc_html__( 'Related Products', 'hkdev-shop-elements' );
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
		return [ 'hkdev-shop-elements', 'woocommerce-elements' ];
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'related', 'related product', 'related products', 'upsell', 'cross-sell', 'single', 'woocommerce', 'hkdev' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-shop-style', 'hkdev-elements-swiper-css', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-shop-js', 'hkdev-elements-swiper-js' ];
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
			'section_presentation',
			[
				'label' => esc_html__( 'Presentation', 'hkdev-shop-elements' ),
			]
		);

		$this->register_cards_per_view_controls( '4' );

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

		$this->register_design_controls();

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

		$this->register_product_controls( true, true );
		$this->register_style_sections();
		$this->register_carousel_style_controls();

		$this->start_controls_section(
			'hkdev_style_related_heading',
			[
				'label'     => esc_html__( 'Related Heading', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_title' => 'yes' ],
			]
		);

		$this->hkdev_typography( 'sk_rel_heading', esc_html__( 'Heading Typography', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-related-heading' );
		$this->hkdev_color( 'sk_rel_heading_color', esc_html__( 'Heading Color', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-related-heading', 'color' );
		$this->hkdev_color( 'sk_rel_heading_accent', esc_html__( 'Accent Bar', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-related-heading::before', 'background-color' );
		$this->hkdev_dimensions( 'sk_rel_heading_margin', esc_html__( 'Heading Margin', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-related-heading', 'margin' );

		$this->end_controls_section();
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

		$product_id = $this->resolve_product_id( $settings );

		if ( ! $product_id ) {
			$this->render_empty_state( esc_html__( 'Related Products needs a product page (or a Product ID) to show items.', 'hkdev-shop-elements' ) );
			return;
		}

		$limit = isset( $settings['limit'] ) ? max( 1, absint( $settings['limit'] ) ) : 4;

		$cols = $this->get_cards_per_view( $settings );

		$atts = array_merge(
			$this->get_design_atts( $settings ),
			$this->get_image_atts( $settings ),
			$this->get_buy_now_atts( $settings ),
			[
				'limit'            => $limit,
				'columns'          => (string) $cols['desktop'],
				'columns_tablet'   => (string) $cols['tablet'],
				'columns_mobile'   => (string) $cols['mobile'],
				'style'            => isset( $settings['style'] ) ? $settings['style'] : 'grid',
				'show_tabs'        => 'no',
				'include_children' => 'yes',
				'type'             => 'recent',
				'carousel'         => $this->get_carousel_config( $settings ),
				'title_lines'      => $this->get_title_lines( $settings ),
				'hover_img'        => $this->get_hover_img( $settings ),
			]
		);

		$related_ids = ShopEngine::instance()->get_related_product_ids( $product_id, $limit );

		if ( ! empty( $related_ids ) ) {
			$atts['product_ids'] = implode( ',', $related_ids );
		} elseif ( ! isset( $settings['fallback'] ) || 'yes' === $settings['fallback'] ) {
			$atts['is_related'] = 'yes';
			$atts['id']         = $product_id;
		} else {
			$this->render_empty_state( esc_html__( 'No related products found for this item.', 'hkdev-shop-elements' ) );
			return;
		}

		$grid = ShopEngine::instance()->master_shop_shortcode( $atts );

		if ( '' === trim( wp_strip_all_tags( (string) $grid ) ) ) {
			$this->render_empty_state( esc_html__( 'No related products found for this item.', 'hkdev-shop-elements' ) );
			return;
		}

		$show_title = isset( $settings['show_title'] ) && 'yes' === $settings['show_title'];
		$title      = isset( $settings['title'] ) ? trim( (string) $settings['title'] ) : '';

		echo '<div class="hkdev-related-wrapper" style="' . esc_attr( ShopEngine::design_style_attr( $atts ) ) . '" data-card-preset="' . esc_attr( $atts['card_preset'] ) . '" data-card-theme="' . esc_attr( $atts['card_theme'] ) . '" data-font-mode="' . esc_attr( $atts['font_mode'] ) . '">';
		if ( $show_title && '' !== $title ) {
			echo '<h2 class="hkdev-related-heading">' . esc_html( $title ) . '</h2>';
		}
		echo $grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Product ID from widget setting, current product, or Elementor preview.
	 *
	 * @param array $settings Widget settings.
	 * @return int
	 */
	private function resolve_product_id( $settings ) {
		if ( ! empty( $settings['id'] ) ) {
			return absint( $settings['id'] );
		}

		global $product;
		if ( $product instanceof \WC_Product ) {
			return absint( $product->get_id() );
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			$current_id = get_the_ID();
			if ( $current_id && 'product' === get_post_type( $current_id ) ) {
				return absint( $current_id );
			}
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance ) ) {
			$elementor = \Elementor\Plugin::$instance;

			if ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
				$editor_post_id = method_exists( $elementor->editor, 'get_post_id' ) ? absint( $elementor->editor->get_post_id() ) : 0;
				if ( $editor_post_id && 'product' === get_post_type( $editor_post_id ) ) {
					return $editor_post_id;
				}
			}

			if ( isset( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
				$preview_post_id = method_exists( $elementor->preview, 'get_post_id' ) ? absint( $elementor->preview->get_post_id() ) : 0;
				if ( ! $preview_post_id && isset( $_GET['preview_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$preview_post_id = absint( wp_unslash( $_GET['preview_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				if ( $preview_post_id && 'product' === get_post_type( $preview_post_id ) ) {
					return $preview_post_id;
				}
			}
		}

		$current_id = get_the_ID();
		if ( $current_id && 'product' === get_post_type( $current_id ) ) {
			return absint( $current_id );
		}

		if ( $this->is_editor_mode() && function_exists( 'wc_get_products' ) ) {
			$sample = wc_get_products(
				[
					'limit'   => 1,
					'status'  => 'publish',
					'orderby' => 'date',
					'order'   => 'DESC',
				]
			);
			if ( ! empty( $sample[0] ) && $sample[0] instanceof \WC_Product ) {
				return absint( $sample[0]->get_id() );
			}
		}

		return 0;
	}

	/**
	 * Whether Elementor editor or preview is active.
	 *
	 * @return bool
	 */
	private function is_editor_mode() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::$instance;

		if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}

		return isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode();
	}

	/**
	 * Visible empty state so the widget is not invisible in the editor.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function render_empty_state( $message ) {
		if ( ! $this->is_editor_mode() ) {
			return;
		}

		echo '<div class="hkdev-related-empty" style="padding:18px 20px;border:1px dashed #c3c4c7;border-radius:8px;color:#50575e;background:#fff;">';
		echo '<strong>' . esc_html__( 'Related Products', 'hkdev-shop-elements' ) . '</strong>';
		echo '<p style="margin:8px 0 0;">' . esc_html( $message ) . '</p>';
		echo '</div>';
	}
}

