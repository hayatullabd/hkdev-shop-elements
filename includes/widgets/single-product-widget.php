<?php
/**
 * HKDEV Single Product Widget (HKDEV Shop Elements plugin).
 *
 * Renders the full custom single product layout (gallery, price, variants,
 * size chart, quantity, AJAX add-to-cart, tabs, meta). Self-contained – does
 * not require the hkdev-shop theme.
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
 * Class Single_Product_Widget
 */
class Single_Product_Widget extends Widget_Base {

	use Product_Controls;
	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_single_product';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Single Product', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-single-product';
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
		return [ 'product', 'single', 'detail', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-single-product-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-single-product-js' ];
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
				'label' => esc_html__( 'Product', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'id',
			[
				'label'       => esc_html__( 'Product ID (optional)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to use the product from the current page. On a single product page just drop this widget anywhere.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_presentation',
			[
				'label' => esc_html__( 'Presentation', 'hkdev-shop-elements' ),
			]
		);

		$this->register_design_controls( esc_html__( 'Design Preset', 'hkdev-shop-elements' ) );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_display',
			[
				'label' => esc_html__( 'Display', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_breadcrumb',
			[
				'label'        => esc_html__( 'Show Breadcrumb', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_sale_badge',
			[
				'label'        => esc_html__( 'Show Sale Badge', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_zoom',
			[
				'label'        => esc_html__( 'Image Zoom', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_qty',
			[
				'label'        => esc_html__( 'Show Quantity', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_sku',
			[
				'label'        => esc_html__( 'Show SKU', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_stock',
			[
				'label'        => esc_html__( 'Show Stock Status', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_tabs',
			[
				'label'        => esc_html__( 'Show Description / Reviews Tabs', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_faq',
			[
				'label'        => esc_html__( 'Show Product FAQ', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'gallery_sticky',
			[
				'label'        => esc_html__( 'Sticky Gallery', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Keeps the gallery in view while the customer scrolls the product info.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_gallery',
			[
				'label' => esc_html__( 'Gallery Images', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'gallery_image_size',
			[
				'label'   => esc_html__( 'Main Image Size', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => $this->hkdev_image_size_options(),
			]
		);

		$this->add_control(
			'thumb_image_size',
			[
				'label'   => esc_html__( 'Thumbnail Size', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'thumbnail',
				'options' => $this->hkdev_image_size_options(),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_contact',
			[
				'label' => esc_html__( 'Order Buttons & Info', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_whatsapp',
			[
				'label'        => esc_html__( 'Show WhatsApp Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'whatsapp',
			[
				'label'       => esc_html__( 'WhatsApp Number', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '88017XXXXXXXX',
				'description' => esc_html__( 'Leave empty to hide the WhatsApp button.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_whatsapp' => 'yes' ],
			]
		);

		$this->add_control(
			'show_call',
			[
				'label'        => esc_html__( 'Show Call Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'phone',
			[
				'label'       => esc_html__( 'Phone Number', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '01XXXXXXXXX',
				'description' => esc_html__( 'Leave empty to hide the Call button.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_call' => 'yes' ],
			]
		);

		$this->add_control(
			'show_brand',
			[
				'label'        => esc_html__( 'Show Brand', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_category',
			[
				'label'        => esc_html__( 'Show Category', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->register_sp_style_sections();
	}

	/**
	 * Style tab – layout, gallery, title/price, buttons, swatches and tabs.
	 *
	 * @return void
	 */
	protected function register_sp_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-sp-wrapper';

		/* ---------------- Layout ---------------- */
		$this->start_controls_section(
			'sp_style_layout',
			[
				'label' => esc_html__( 'Layout & Spacing', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'sp_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'sp_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_slider( 'sp_col_gap', esc_html__( 'Gallery / Info Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-main-container', 'gap', 0, 100 );
		$this->hkdev_color( 'sp_page_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );

		$this->end_controls_section();

		/* ---------------- Gallery ---------------- */
		$this->start_controls_section(
			'sp_style_gallery',
			[
				'label' => esc_html__( 'Gallery', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'sp_img_radius', esc_html__( 'Main Image Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-viewport', 'border-radius' );
		$this->hkdev_color( 'sp_view_bg', esc_html__( 'Image Area Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-viewport', 'background-color' );
		$this->hkdev_color( 'sp_thumb_border', esc_html__( 'Thumbnail Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-thumb', 'border-color' );
		$this->hkdev_dimensions( 'sp_thumb_radius', esc_html__( 'Thumbnail Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-thumb', 'border-radius' );
		$this->hkdev_slider( 'sp_thumb_size', esc_html__( 'Thumbnail Size', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-thumb', 'width', 40, 140 );
		$this->hkdev_color( 'sp_badge_bg', esc_html__( 'Sale Badge Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-sale-badge', 'background-color' );
		$this->hkdev_color( 'sp_badge_color', esc_html__( 'Sale Badge Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-sale-badge', 'color' );
		$this->hkdev_slider( 'sp_gallery_width', esc_html__( 'Gallery Max Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-gallery', 'max-width', 220, 720 );

		$this->end_controls_section();

		$this->start_controls_section(
			'sp_style_breadcrumb',
			[
				'label'     => esc_html__( 'Breadcrumb', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_breadcrumb' => 'yes' ],
			]
		);

		$this->hkdev_typography( 'sp_crumb', esc_html__( 'Breadcrumb Typography', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-breadcrumb' );
		$this->hkdev_color( 'sp_crumb_color', esc_html__( 'Link Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-breadcrumb a', 'color' );
		$this->hkdev_color( 'sp_crumb_current', esc_html__( 'Current Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-breadcrumb .current-crumb', 'color' );

		$this->end_controls_section();

		/* ---------------- Title & price ---------------- */
		$this->start_controls_section(
			'sp_style_text',
			[
				'label' => esc_html__( 'Title & Price', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sp_title', esc_html__( 'Product Title', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-title' );
		$this->hkdev_typography( 'sp_price', esc_html__( 'Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-price-box .price .amount' );
		$this->hkdev_color( 'sp_price_old', esc_html__( 'Old (Struck) Price', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-price-box del .amount', 'color' );

		$this->end_controls_section();

		/* ---------------- Buttons ---------------- */
		$this->start_controls_section(
			'sp_style_buttons',
			[
				'label' => esc_html__( 'Buttons', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sp_atc', esc_html__( 'Add to Cart Text', 'hkdev-shop-elements' ), $scope . ' .atc-btn' );
		$this->hkdev_color( 'sp_atc_bg', esc_html__( 'Add to Cart Background', 'hkdev-shop-elements' ), $scope . ' .atc-btn', 'background-color' );
		$this->hkdev_color( 'sp_atc_color', esc_html__( 'Add to Cart Text Colour', 'hkdev-shop-elements' ), $scope . ' .atc-btn', 'color' );
		$this->hkdev_color( 'sp_buy_bg', esc_html__( 'Buy Now Background', 'hkdev-shop-elements' ), $scope . ' .buy-now-btn', 'background-color' );
		$this->hkdev_color( 'sp_buy_color', esc_html__( 'Buy Now Text Colour', 'hkdev-shop-elements' ), $scope . ' .buy-now-btn', 'color' );
		$this->hkdev_color( 'sp_wa_bg', esc_html__( 'WhatsApp Button Background', 'hkdev-shop-elements' ), $scope . ' .whatsapp-btn', 'background-color' );
		$this->hkdev_color( 'sp_call_bg', esc_html__( 'Call Button Background', 'hkdev-shop-elements' ), $scope . ' .call-btn', 'background-color' );
		$this->hkdev_slider( 'sp_btn_height', esc_html__( 'Button Height', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-btn', 'height', 34, 72 );
		$this->hkdev_dimensions( 'sp_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-btn', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Variation swatches ---------------- */
		$this->start_controls_section(
			'sp_style_swatch',
			[
				'label' => esc_html__( 'Variation Swatches', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'sp_sw_color', esc_html__( 'Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item', 'color' );
		$this->hkdev_color( 'sp_sw_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item', 'background-color' );
		$this->hkdev_color( 'sp_sw_border', esc_html__( 'Border Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item', 'border-color' );
		$this->hkdev_color( 'sp_sw_sel_bg', esc_html__( 'Selected Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item.selected', 'background-color' );
		$this->hkdev_color( 'sp_sw_sel_color', esc_html__( 'Selected Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item.selected', 'color' );
		$this->hkdev_dimensions( 'sp_sw_radius', esc_html__( 'Swatch Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-swatch-item', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Tabs & meta ---------------- */
		$this->start_controls_section(
			'sp_style_tabs',
			[
				'label' => esc_html__( 'Tabs & Meta', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'sp_tab', esc_html__( 'Tab Label', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-tab-link' );
		$this->hkdev_color( 'sp_tab_active', esc_html__( 'Active Tab Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-tab-link.active', 'color' );
		$this->hkdev_typography( 'sp_tab_body', esc_html__( 'Tab Content', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-tab-content' );
		$this->hkdev_typography( 'sp_meta', esc_html__( 'SKU / Stock Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-product-meta' );

		$this->end_controls_section();

		$this->start_controls_section(
			'sp_style_qty',
			[
				'label'     => esc_html__( 'Quantity', 'hkdev-shop-elements' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_qty' => 'yes' ],
			]
		);

		$this->hkdev_color( 'sp_qty_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-qty-control', 'background-color' );
		$this->hkdev_color( 'sp_qty_color', esc_html__( 'Text Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-qty-input', 'color' );
		$this->hkdev_color( 'sp_qty_border', esc_html__( 'Border Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-qty-control', 'border-color' );
		$this->hkdev_dimensions( 'sp_qty_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-sp-qty-control', 'border-radius' );

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
		$yes_no   = static function ( $key, $default = 'yes' ) use ( $settings ) {
			if ( ! isset( $settings[ $key ] ) ) {
				return $default;
			}
			return ( 'yes' === $settings[ $key ] ) ? 'yes' : 'no';
		};

		$atts = array_merge(
			$this->get_design_atts( $settings ),
			[
				'phone'              => isset( $settings['phone'] ) ? $settings['phone'] : '',
				'whatsapp'           => isset( $settings['whatsapp'] ) ? $settings['whatsapp'] : '',
				'show_whatsapp'      => $yes_no( 'show_whatsapp' ),
				'show_call'          => $yes_no( 'show_call' ),
				'show_brand'         => $yes_no( 'show_brand' ),
				'show_category'      => $yes_no( 'show_category' ),
				'show_breadcrumb'    => $yes_no( 'show_breadcrumb' ),
				'show_sale_badge'    => $yes_no( 'show_sale_badge' ),
				'show_zoom'          => $yes_no( 'show_zoom' ),
				'show_qty'           => $yes_no( 'show_qty' ),
				'show_sku'           => $yes_no( 'show_sku' ),
				'show_stock'         => $yes_no( 'show_stock' ),
				'show_tabs'          => $yes_no( 'show_tabs' ),
				'show_faq'           => $yes_no( 'show_faq' ),
				'gallery_sticky'     => $yes_no( 'gallery_sticky', 'no' ),
				'gallery_image_size' => isset( $settings['gallery_image_size'] ) ? sanitize_key( $settings['gallery_image_size'] ) : 'large',
				'thumb_image_size'   => isset( $settings['thumb_image_size'] ) ? sanitize_key( $settings['thumb_image_size'] ) : 'thumbnail',
			]
		);

		if ( isset( $settings['id'] ) && ! empty( $settings['id'] ) ) {
			$atts['id'] = absint( $settings['id'] );
		}

		echo \HkdevShopElements\Includes\Core\SingleProductEngine::instance()->custom_single_product_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
