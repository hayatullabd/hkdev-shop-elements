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
	 * @param bool $for_products Product card controls (hover image).
	 *                           Category cards have neither.
	 * @return void
	 */
	protected function register_product_controls( $for_products = true, $include_image_size = false ) {
		$this->register_product_title_controls();
		if ( $for_products ) {
			$this->register_product_image_controls( $include_image_size );
			$this->register_buy_now_controls();
		}
		$this->register_carousel_controls();
	}

	/**
	 * Font family options used by product widgets.
	 *
	 * @param bool $include_system Include the system-sans fallback.
	 * @return array<string,string>
	 */
	protected function font_family_options( $include_system = true ) {
		$options = [
			'hind_siliguri'      => esc_html__( 'Hind Siliguri', 'hkdev-shop-elements' ),
			'noto_sans_bengali'  => esc_html__( 'Noto Sans Bengali', 'hkdev-shop-elements' ),
			'noto_serif_bengali' => esc_html__( 'Noto Serif Bengali', 'hkdev-shop-elements' ),
			'tiro_bangla'        => esc_html__( 'Tiro Bangla', 'hkdev-shop-elements' ),
			'inter'              => esc_html__( 'Inter', 'hkdev-shop-elements' ),
			'poppins'            => esc_html__( 'Poppins', 'hkdev-shop-elements' ),
			'roboto'             => esc_html__( 'Roboto', 'hkdev-shop-elements' ),
			'open_sans'          => esc_html__( 'Open Sans', 'hkdev-shop-elements' ),
			'montserrat'         => esc_html__( 'Montserrat', 'hkdev-shop-elements' ),
		];

		if ( $include_system ) {
			$options['system_sans'] = esc_html__( 'System Sans', 'hkdev-shop-elements' );
		}

		return $options;
	}

	/**
	 * Content tab – 1-click design preset, color theme, and local fonts.
	 *
	 * @return void
	 */
	protected function register_design_controls( $preset_label = '' ) {
		$this->add_control(
			'card_preset',
			[
				'label'       => $preset_label ? $preset_label : esc_html__( 'Card Design Preset', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'clean',
				'options'     => [
					'clean'   => esc_html__( 'Clean (Default)', 'hkdev-shop-elements' ),
					'compact' => esc_html__( 'Compact', 'hkdev-shop-elements' ),
					'premium' => esc_html__( 'Premium', 'hkdev-shop-elements' ),
					'minimal' => esc_html__( 'Minimal', 'hkdev-shop-elements' ),
					'bold'    => esc_html__( 'Bold', 'hkdev-shop-elements' ),
					'classic' => esc_html__( 'Classic', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Sets a visual base for cards. Style tab controls can still override it.', 'hkdev-shop-elements' ),
			]
		);

		$theme_options = class_exists( '\HkdevShopElements\Includes\Core\ColorTheme' )
			? \HkdevShopElements\Includes\Core\ColorTheme::select_options()
			: [
				'green'      => esc_html__( 'Green (Default)', 'hkdev-shop-elements' ),
				'orange'     => esc_html__( 'Orange', 'hkdev-shop-elements' ),
				'monochrome' => esc_html__( 'Monochrome', 'hkdev-shop-elements' ),
			];

		$this->add_control(
			'card_theme',
			[
				'label'              => esc_html__( 'Color Theme', 'hkdev-shop-elements' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'green',
				'frontend_available' => true,
				'options'            => $theme_options,
				'description' => sprintf(
					/* translators: %s: Color Themes admin URL */
					esc_html__( 'Add custom presets in %s.', 'hkdev-shop-elements' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=hkdev-shop-elements-colors' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WP Admin → Color Themes', 'hkdev-shop-elements' ) . '</a>'
				),
			]
		);

		$this->add_control(
			'font_family',
			[
				'label'   => esc_html__( 'Primary Font (Bangla)', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hind_siliguri',
				'options' => $this->font_family_options(),
			]
		);

		$this->add_control(
			'font_mode',
			[
				'label'   => esc_html__( 'Typography Mode', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'single',
				'options' => [
					'single' => esc_html__( 'Single Font', 'hkdev-shop-elements' ),
					'dual'   => esc_html__( 'Dual Font (Bangla + Latin)', 'hkdev-shop-elements' ),
				],
			]
		);

		$this->add_control(
			'font_family_latin',
			[
				'label'     => esc_html__( 'Secondary Font (Latin/Numbers)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'system_sans',
				'options'   => $this->font_family_options(),
				'condition' => [ 'font_mode' => 'dual' ],
			]
		);
	}

	/**
	 * Design attributes passed through to ShopEngine / CatalogEngine.
	 *
	 * @param array $settings Widget settings.
	 * @return array<string,string>
	 */
	protected function get_design_atts( $settings ) {
		return [
			'font_mode'         => isset( $settings['font_mode'] ) ? sanitize_key( $settings['font_mode'] ) : 'single',
			'font_family'       => isset( $settings['font_family'] ) ? sanitize_key( $settings['font_family'] ) : 'hind_siliguri',
			'font_family_latin' => isset( $settings['font_family_latin'] ) ? sanitize_key( $settings['font_family_latin'] ) : 'system_sans',
			'card_preset'       => isset( $settings['card_preset'] ) ? sanitize_key( $settings['card_preset'] ) : 'clean',
			'card_theme'        => class_exists( '\HkdevShopElements\Includes\Core\ColorTheme' )
				? \HkdevShopElements\Includes\Core\ColorTheme::sanitize( isset( $settings['card_theme'] ) ? $settings['card_theme'] : 'green' )
				: ( isset( $settings['card_theme'] ) ? sanitize_key( $settings['card_theme'] ) : 'green' ),
		];
	}

	/**
	 * Product image attributes passed through to ShopEngine / CatalogEngine.
	 *
	 * @param array $settings Widget settings.
	 * @return array<string,int|string>
	 */
	protected function get_image_atts( $settings ) {
		return [
			'image_size'          => isset( $settings['image_size'] ) ? sanitize_key( $settings['image_size'] ) : 'woocommerce_thumbnail',
			'image_size_mode'     => isset( $settings['image_size_mode'] ) ? sanitize_key( $settings['image_size_mode'] ) : 'preset',
			'custom_img_w'        => isset( $settings['custom_img_w'] ) ? max( 0, absint( $settings['custom_img_w'] ) ) : 0,
			'custom_img_h'        => isset( $settings['custom_img_h'] ) ? max( 0, absint( $settings['custom_img_h'] ) ) : 0,
			'custom_img_w_tablet' => isset( $settings['custom_img_w_tablet'] ) ? max( 0, absint( $settings['custom_img_w_tablet'] ) ) : 0,
			'custom_img_h_tablet' => isset( $settings['custom_img_h_tablet'] ) ? max( 0, absint( $settings['custom_img_h_tablet'] ) ) : 0,
			'custom_img_w_mobile' => isset( $settings['custom_img_w_mobile'] ) ? max( 0, absint( $settings['custom_img_w_mobile'] ) ) : 0,
			'custom_img_h_mobile' => isset( $settings['custom_img_h_mobile'] ) ? max( 0, absint( $settings['custom_img_h_mobile'] ) ) : 0,
			'custom_img_fit'      => isset( $settings['custom_img_fit'] ) ? sanitize_key( $settings['custom_img_fit'] ) : 'cover',
			'custom_img_pos_x'    => ( isset( $settings['custom_img_pos_x']['size'] ) && '' !== $settings['custom_img_pos_x']['size'] ) ? max( 0, min( 100, (int) $settings['custom_img_pos_x']['size'] ) ) : 50,
			'custom_img_pos_y'    => ( isset( $settings['custom_img_pos_y']['size'] ) && '' !== $settings['custom_img_pos_y']['size'] ) ? max( 0, min( 100, (int) $settings['custom_img_pos_y']['size'] ) ) : 50,
		];
	}

	/**
	 * Content tab – product title on the card.
	 *
	 * @return void
	 */
	protected function register_product_title_controls() {
		$this->start_controls_section(
			'section_product_title',
			[
				'label' => esc_html__( 'Product Title', 'hkdev-shop-elements' ),
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
	 * Content tab – product image behaviour on the card.
	 *
	 * @return void
	 */
	protected function register_product_image_controls( $include_image_size = false ) {
		$this->start_controls_section(
			'section_product_images',
			[
				'label' => esc_html__( 'Product Images', 'hkdev-shop-elements' ),
			]
		);

		if ( $include_image_size && method_exists( $this, 'hkdev_image_size_options' ) ) {
			$this->add_control(
				'image_size_mode',
				[
					'label'   => esc_html__( 'Image Size Mode', 'hkdev-shop-elements' ),
					'type'    => Controls_Manager::SELECT,
					'default' => 'preset',
					'options' => [
						'preset' => esc_html__( 'Preset (WordPress Sizes)', 'hkdev-shop-elements' ),
						'custom' => esc_html__( 'Custom Width/Height', 'hkdev-shop-elements' ),
					],
				]
			);

			$this->add_control(
				'image_size',
				[
					'label'       => esc_html__( 'Thumbnail Size', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::SELECT,
					'default'     => 'woocommerce_thumbnail',
					'options'     => $this->hkdev_image_size_options(),
					'description' => esc_html__( 'WordPress image size loaded in the card. Crop/shape: Style → Image Styles.', 'hkdev-shop-elements' ),
					'condition'   => [ 'image_size_mode' => 'preset' ],
				]
			);

			$this->add_control(
				'custom_img_w',
				[
					'label'       => esc_html__( 'Desktop Width (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 360,
					'min'         => 40,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( 'Set 0 to use 100% width.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_h',
				[
					'label'       => esc_html__( 'Desktop Height (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 360,
					'min'         => 0,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( 'Set 0 to keep auto height.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_w_tablet',
				[
					'label'       => esc_html__( 'Tablet Width (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 0,
					'min'         => 0,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( '0 = use desktop width.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_h_tablet',
				[
					'label'       => esc_html__( 'Tablet Height (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 0,
					'min'         => 0,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( '0 = use desktop height.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_w_mobile',
				[
					'label'       => esc_html__( 'Mobile Width (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 0,
					'min'         => 0,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( '0 = use desktop width.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_h_mobile',
				[
					'label'       => esc_html__( 'Mobile Height (px)', 'hkdev-shop-elements' ),
					'type'        => Controls_Manager::NUMBER,
					'default'     => 0,
					'min'         => 0,
					'max'         => 2200,
					'condition'   => [ 'image_size_mode' => 'custom' ],
					'description' => esc_html__( '0 = use desktop height.', 'hkdev-shop-elements' ),
				]
			);

			$this->add_control(
				'custom_img_fit',
				[
					'label'     => esc_html__( 'Custom Image Fit', 'hkdev-shop-elements' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => 'cover',
					'options'   => [
						'cover'   => esc_html__( 'Cover', 'hkdev-shop-elements' ),
						'contain' => esc_html__( 'Contain', 'hkdev-shop-elements' ),
					],
					'condition' => [ 'image_size_mode' => 'custom' ],
				]
			);

			$this->add_control(
				'custom_img_pos_x',
				[
					'label'      => esc_html__( 'Image Position X (%)', 'hkdev-shop-elements' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ '%' ],
					'range'      => [
						'%' => [ 'min' => 0, 'max' => 100 ],
					],
					'default'    => [ 'unit' => '%', 'size' => 50 ],
					'condition'  => [ 'image_size_mode' => 'custom' ],
				]
			);

			$this->add_control(
				'custom_img_pos_y',
				[
					'label'      => esc_html__( 'Image Position Y (%)', 'hkdev-shop-elements' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ '%' ],
					'range'      => [
						'%' => [ 'min' => 0, 'max' => 100 ],
					],
					'default'    => [ 'unit' => '%', 'size' => 50 ],
					'condition'  => [ 'image_size_mode' => 'custom' ],
				]
			);
		}

		$this->add_control(
			'hover_img',
			[
				'label'        => esc_html__( 'Second Image on Hover', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Swaps in the next gallery image while the pointer is over a card. Products with a single image are unaffected.', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab – product card Buy Now button text and click behaviour.
	 *
	 * @return void
	 */
	protected function register_buy_now_controls() {
		$this->start_controls_section(
			'section_buy_now',
			[
				'label' => esc_html__( 'Buy Now Button', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_buy_now',
			[
				'label'        => esc_html__( 'Show Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'buy_now_text',
			[
				'label'       => esc_html__( 'Button Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Buy Now', 'hkdev-shop-elements' ),
				'placeholder' => esc_html__( 'Buy Now', 'hkdev-shop-elements' ),
				'label_block' => true,
				'condition'   => [ 'show_buy_now' => 'yes' ],
			]
		);

		$this->add_control(
			'buy_now_icon',
			[
				'label'        => esc_html__( 'Show Icon', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => [ 'show_buy_now' => 'yes' ],
			]
		);

		$this->add_control(
			'buy_now_action',
			[
				'label'       => esc_html__( 'Click Action', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'checkout',
				'options'     => [
					'checkout'    => esc_html__( 'Buy Now (add to cart + checkout)', 'hkdev-shop-elements' ),
					'add_to_cart' => esc_html__( 'Add to Cart (stay on page)', 'hkdev-shop-elements' ),
					'product'     => esc_html__( 'Open Product Page', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'What happens when a customer taps the card button.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_buy_now' => 'yes' ],
			]
		);

		$this->add_control(
			'buy_now_after',
			[
				'label'       => esc_html__( 'After Buy Now', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => [
					'auto'  => esc_html__( 'Plugin setting (modal or page)', 'hkdev-shop-elements' ),
					'modal' => esc_html__( 'Checkout popup', 'hkdev-shop-elements' ),
					'page'  => esc_html__( 'Checkout page', 'hkdev-shop-elements' ),
				],
				'description' => esc_html__( 'Where to go after the product is added to cart.', 'hkdev-shop-elements' ),
				'condition'   => [
					'show_buy_now'    => 'yes',
					'buy_now_action'  => 'checkout',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Buy Now attributes passed through to ShopEngine / CatalogEngine.
	 *
	 * @param array $settings Widget settings.
	 * @return array<string,string>
	 */
	protected function get_buy_now_atts( $settings ) {
		$action = isset( $settings['buy_now_action'] ) ? sanitize_key( $settings['buy_now_action'] ) : 'checkout';
		if ( ! in_array( $action, [ 'checkout', 'add_to_cart', 'product' ], true ) ) {
			$action = 'checkout';
		}

		$after = isset( $settings['buy_now_after'] ) ? sanitize_key( $settings['buy_now_after'] ) : 'auto';
		if ( ! in_array( $after, [ 'auto', 'modal', 'page' ], true ) ) {
			$after = 'auto';
		}

		$text = isset( $settings['buy_now_text'] ) ? sanitize_text_field( $settings['buy_now_text'] ) : '';

		return [
			'show_buy_now'    => ( ! isset( $settings['show_buy_now'] ) || 'yes' === $settings['show_buy_now'] ) ? 'yes' : 'no',
			'buy_now_text'    => $text,
			'buy_now_icon'    => ( ! isset( $settings['buy_now_icon'] ) || 'yes' === $settings['buy_now_icon'] ) ? 'yes' : 'no',
			'buy_now_action'  => $action,
			'buy_now_after'   => $after,
		];
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
				'label'     => esc_html__( 'Carousel Settings', 'hkdev-shop-elements' ),
				'condition' => $condition,
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
			'carousel_pause_on_hover',
			[
				'label'        => esc_html__( 'Pause On Hover', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'carousel_autoplay' => 'yes' ],
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
			'carousel_drag',
			[
				'label'        => esc_html__( 'Mouse / Touch Drag', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Off', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'carousel_centered',
			[
				'label'        => esc_html__( 'Centered Slides', 'hkdev-shop-elements' ),
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
	 * Select options for column / slide counts.
	 *
	 * @param int $max Maximum value.
	 * @param int $min Minimum value.
	 * @return array<string,string>
	 */
	protected function column_count_options( $max = 6, $min = 1 ) {
		$options = [];
		for ( $i = $min; $i <= $max; $i++ ) {
			$options[ (string) $i ] = (string) $i;
		}
		return $options;
	}

	/**
	 * Mobile / tablet / desktop cards per row (grid) or slides (carousel).
	 *
	 * @param string $desktop_default Default desktop count.
	 * @return void
	 */
	protected function register_cards_per_view_controls( $desktop_default = '4', $desktop_max = 6 ) {
		$this->add_control(
			'cards_per_view_help',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'How many cards show per row (grid) or at once (carousel) on each breakpoint.', 'hkdev-shop-elements' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'columns_mobile',
			[
				'label'   => esc_html__( 'Mobile', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '2',
				'options' => $this->column_count_options( 3, 1 ),
			]
		);

		$this->add_control(
			'columns_tablet',
			[
				'label'   => esc_html__( 'Tablet', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => $this->column_count_options( 5, 1 ),
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Desktop', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => $desktop_default,
				'options' => $this->column_count_options( max( 1, (int) $desktop_max ), 1 ),
			]
		);
	}

	/**
	 * Normalized responsive card counts from widget settings.
	 *
	 * @param array $settings Widget settings.
	 * @return array{mobile:int,tablet:int,desktop:int}
	 */
	protected function get_cards_per_view( $settings ) {
		$pick = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				return max( 1, (int) $settings[ $key ] );
			}
			return $fallback;
		};

		$num = static function ( $key, $fallback ) use ( $settings ) {
			if ( isset( $settings[ $key ]['size'] ) && '' !== $settings[ $key ]['size'] ) {
				return max( 1, (int) $settings[ $key ]['size'] );
			}
			return $fallback;
		};

		$desktop = $pick( 'columns', 4 );

		return [
			'mobile'  => min( 3, $pick( 'columns_mobile', $num( 'carousel_mobile', 2 ) ) ),
			'tablet'  => min( 6, $pick( 'columns_tablet', $num( 'carousel_tablet', 3 ) ) ),
			'desktop' => min( 8, max( 1, $desktop ) ),
		];
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
	 * Whether cards swap in the second gallery image on hover (on by default).
	 *
	 * @param array $settings Widget settings.
	 * @return string "yes" or "no".
	 */
	protected function get_hover_img( $settings ) {
		return ( ! isset( $settings['hover_img'] ) || 'yes' === $settings['hover_img'] ) ? 'yes' : 'no';
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

		$counts = $this->get_cards_per_view( $settings );

		return [
			'mobile'   => $counts['mobile'],
			'tablet'   => $counts['tablet'],
			'desktop'  => $counts['desktop'],
			'gap'      => max( 0, (int) $num( 'carousel_gap', 20 ) ),
			'speed'    => max( 150, (int) $num( 'carousel_speed', 600 ) ),
			'autoplay' => ( 'yes' === $is_yes( 'carousel_autoplay' ) ),
			'delay'    => max( 1000, (int) $num( 'carousel_delay', 5000 ) ),
			'pause_on_hover' => ( 'yes' === $is_yes( 'carousel_pause_on_hover', 'yes' ) ),
			'loop'     => ( 'yes' === $is_yes( 'carousel_loop' ) ),
			'drag'     => ( 'yes' === $is_yes( 'carousel_drag', 'yes' ) ),
			'centered' => ( 'yes' === $is_yes( 'carousel_centered' ) ),
			'arrows'   => ( 'yes' === $is_yes( 'carousel_arrows', 'yes' ) ),
			'dots'     => ( 'yes' === $is_yes( 'carousel_dots', 'yes' ) ),
		];
	}
}
