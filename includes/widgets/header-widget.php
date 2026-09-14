<?php
/**
 * HKDEV Header Widget (HKDEV Shop Elements plugin).
 *
 * Elementor widget for the brand-matched WooCommerce header. Self-contained –
 * does not require the hkdev-shop theme.
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
 * Class Header_Widget
 */
class Header_Widget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_header';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Header', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-header';
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
		return [ 'header', 'menu', 'nav', 'navigation', 'logo', 'cart', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-header-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-header-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_header',
			[
				'label' => esc_html__( 'Header', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'logo',
			[
				'label'       => esc_html__( 'Logo', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [ 'url' => '' ],
				'description' => esc_html__( 'Leave empty to use the site logo (Customizer) or the site name.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'logo_width',
			[
				'label'   => esc_html__( 'Logo Width (px)', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 150 ],
				'range'   => [
					'px' => [
						'min'  => 40,
						'max'  => 400,
						'step' => 1,
					],
				],
			]
		);

		$this->add_control(
			'menu',
			[
				'label'   => esc_html__( 'Menu', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_menus_options(),
			]
		);

		$this->add_control(
			'sticky',
			[
				'label'        => esc_html__( 'Sticky Header', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_topbar',
			[
				'label' => esc_html__( 'Top Bar', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_topbar',
			[
				'label'        => esc_html__( 'Show Top Bar', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'Hide', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'announcement',
			[
				'label'     => esc_html__( 'Announcement Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => [ 'show_topbar' => 'yes' ],
			]
		);

		$this->add_control(
			'facebook',
			[
				'label'     => esc_html__( 'Facebook URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_topbar' => 'yes' ],
			]
		);

		$this->add_control(
			'instagram',
			[
				'label'     => esc_html__( 'Instagram URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_topbar' => 'yes' ],
			]
		);

		$this->add_control(
			'youtube',
			[
				'label'     => esc_html__( 'YouTube URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_topbar' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_elements',
			[
				'label' => esc_html__( 'Elements', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_menu',
			[
				'label'        => esc_html__( 'Show Menu', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_search',
			[
				'label'        => esc_html__( 'Show Search', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_account',
			[
				'label'        => esc_html__( 'Show Account Icon', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_cart',
			[
				'label'        => esc_html__( 'Show Cart Icon', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'mini_cart',
			[
				'label'        => esc_html__( 'Mini Cart on Icon Click', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Opens a slide-in cart drawer. Turn off to link straight to the cart page.', 'hkdev-shop-elements' ),
				'condition'    => [ 'show_cart' => 'yes' ],
			]
		);

		$this->add_control(
			'track_url',
			[
				'label'       => esc_html__( 'Track Order URL', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::URL,
				'default'     => [ 'url' => '' ],
				'description' => esc_html__( 'Leave empty to auto-detect a page with the slug track-order / order-tracking.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_categories',
			[
				'label'        => esc_html__( 'Show Categories Dropdown', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			]
		);

		$this->add_control(
			'categories_label',
			[
				'label'     => esc_html__( 'Categories Button Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All Categories', 'hkdev-shop-elements' ),
				'condition' => [ 'show_categories' => 'yes' ],
			]
		);

		$this->add_control(
			'categories_limit',
			[
				'label'     => esc_html__( 'Number of Categories', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 8,
				'min'       => 1,
				'max'       => 30,
				'condition' => [ 'show_categories' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_contact',
			[
				'label' => esc_html__( 'Contact', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'phone',
			[
				'label'       => esc_html__( 'Phone Number', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '01XXXXXXXXX',
				'description' => esc_html__( 'Shown in the top bar and used by the "Call Now" button.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'email',
			[
				'label'     => esc_html__( 'Email Address', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => [ 'show_topbar' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->register_hd_style_sections();
	}

	/**
	 * Style tab – bars, logo, search, actions and navigation.
	 *
	 * @return void
	 */
	protected function register_hd_style_sections() {
		$scope = '{{WRAPPER}} .hkdev-header-wrap';

		/* ---------------- Bars ---------------- */
		$this->start_controls_section(
			'hd_style_bars',
			[
				'label' => esc_html__( 'Header Bars', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hd_bg', esc_html__( 'Header Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_dimensions( 'hd_padding', esc_html__( 'Header Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_color( 'hd_top_bg', esc_html__( 'Top Bar Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-topbar', 'background-color' );
		$this->hkdev_dimensions( 'hd_top_padding', esc_html__( 'Top Bar Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-topbar .hkdev-header-container', 'padding' );
		$this->hkdev_color( 'hd_main_bg', esc_html__( 'Main Bar Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-main', 'background-color' );
		$this->hkdev_dimensions( 'hd_main_padding', esc_html__( 'Main Bar Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-main .hkdev-header-container', 'padding' );
		$this->hkdev_slider( 'hd_main_gap', esc_html__( 'Main Bar Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-main .hkdev-header-container', 'gap', 0, 60 );

		$this->end_controls_section();

		/* ---------------- Top bar text ---------------- */
		$this->start_controls_section(
			'hd_style_toptext',
			[
				'label' => esc_html__( 'Top Bar Text & Social', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'hd_tb', esc_html__( 'Announcement / Links', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-announcement' );
		$this->hkdev_color( 'hd_social_bg', esc_html__( 'Social Icon Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-social', 'background-color' );
		$this->hkdev_color( 'hd_social_color', esc_html__( 'Social Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-social', 'color' );
		$this->hkdev_dimensions( 'hd_social_radius', esc_html__( 'Social Icon Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-social', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Logo ---------------- */
		$this->start_controls_section(
			'hd_style_logo',
			[
				'label' => esc_html__( 'Logo', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'hd_logo_text', esc_html__( 'Logo Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-logo-text' );
		$this->hkdev_dimensions( 'hd_logo_radius', esc_html__( 'Logo Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-logo img', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Search ---------------- */
		$this->start_controls_section(
			'hd_style_search',
			[
				'label' => esc_html__( 'Search Box', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hd_search_bg', esc_html__( 'Field Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-input', 'background-color' );
		$this->hkdev_color( 'hd_search_border', esc_html__( 'Field Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-input', 'border-color' );
		$this->hkdev_color( 'hd_search_color', esc_html__( 'Field Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-input', 'color' );
		$this->hkdev_dimensions( 'hd_search_radius', esc_html__( 'Field Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-input', 'border-radius' );
		$this->hkdev_color( 'hd_search_btn_bg', esc_html__( 'Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-btn', 'background-color' );
		$this->hkdev_color( 'hd_search_btn_color', esc_html__( 'Button Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-btn', 'color' );
		$this->hkdev_dimensions( 'hd_search_btn_radius', esc_html__( 'Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-search-btn', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Actions ---------------- */
		$this->start_controls_section(
			'hd_style_actions',
			[
				'label' => esc_html__( 'Cart & Account', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hd_icon_bg', esc_html__( 'Icon Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-icon-btn', 'background-color' );
		$this->hkdev_color( 'hd_icon_color', esc_html__( 'Icon Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-icon-btn', 'color' );
		$this->hkdev_dimensions( 'hd_icon_radius', esc_html__( 'Icon Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-icon-btn', 'border-radius' );
		$this->hkdev_color( 'hd_count_bg', esc_html__( 'Cart Count Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-cart-count', 'background-color' );
		$this->hkdev_color( 'hd_count_color', esc_html__( 'Cart Count Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-cart-count', 'color' );
		$this->hkdev_typography( 'hd_login', esc_html__( 'Login Link', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-login' );
		$this->hkdev_color( 'hd_reg_bg', esc_html__( 'Register Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-register', 'background-color' );
		$this->hkdev_color( 'hd_reg_color', esc_html__( 'Register Text Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-register', 'color' );
		$this->hkdev_dimensions( 'hd_reg_radius', esc_html__( 'Register Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-register', 'border-radius' );

		$this->end_controls_section();

		/* ---------------- Navigation ---------------- */
		$this->start_controls_section(
			'hd_style_nav',
			[
				'label' => esc_html__( 'Navigation Bar', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'hd_navbar_bg', esc_html__( 'Navbar Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-navbar', 'background-color' );
		$this->hkdev_dimensions( 'hd_navbar_padding', esc_html__( 'Navbar Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-navbar .hkdev-header-container', 'padding' );
		$this->hkdev_typography( 'hd_nav_link', esc_html__( 'Menu Link', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-nav .hkdev-header-menu > li > a' );
		$this->hkdev_color( 'hd_nav_link_hover', esc_html__( 'Menu Link Hover Colour', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-nav .hkdev-header-menu > li > a:hover', 'color' );
		$this->hkdev_color( 'hd_cats_bg', esc_html__( 'Categories Button Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-cats-btn', 'background-color' );
		$this->hkdev_color( 'hd_cats_color', esc_html__( 'Categories Button Text', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-cats-btn', 'color' );
		$this->hkdev_dimensions( 'hd_cats_radius', esc_html__( 'Categories Button Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-header-cats-btn', 'border-radius' );

		$this->end_controls_section();
	}

	/**
	 * Options list of every registered WP menu.
	 *
	 * @return array
	 */
	private function get_menus_options() {
		$options = [ '' => esc_html__( '— Default —', 'hkdev-shop-elements' ) ];

		$menus = wp_get_nav_menus();
		if ( ! empty( $menus ) ) {
			foreach ( $menus as $menu ) {
				$options[ $menu->term_id ] = $menu->name;
			}
		}

		return $options;
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

		$atts = [
			'logo'             => isset( $settings['logo']['url'] ) ? $settings['logo']['url'] : '',
			'logo_width'       => isset( $settings['logo_width']['size'] ) ? absint( $settings['logo_width']['size'] ) : 150,
			'menu'             => isset( $settings['menu'] ) ? $settings['menu'] : '',
			'sticky'           => ( isset( $settings['sticky'] ) && 'yes' === $settings['sticky'] ) ? 'yes' : 'no',
			'show_topbar'      => ( isset( $settings['show_topbar'] ) && 'yes' === $settings['show_topbar'] ) ? 'yes' : 'no',
			'announcement'     => isset( $settings['announcement'] ) ? $settings['announcement'] : '',
			'phone'            => isset( $settings['phone'] ) ? $settings['phone'] : '',
			'email'            => isset( $settings['email'] ) ? $settings['email'] : '',
			'facebook'         => isset( $settings['facebook']['url'] ) ? $settings['facebook']['url'] : '',
			'instagram'        => isset( $settings['instagram']['url'] ) ? $settings['instagram']['url'] : '',
			'youtube'          => isset( $settings['youtube']['url'] ) ? $settings['youtube']['url'] : '',
			'track_url'        => isset( $settings['track_url']['url'] ) ? $settings['track_url']['url'] : '',
			'show_menu'        => ( isset( $settings['show_menu'] ) && 'yes' === $settings['show_menu'] ) ? 'yes' : 'no',
			'show_search'      => ( isset( $settings['show_search'] ) && 'yes' === $settings['show_search'] ) ? 'yes' : 'no',
			'show_account'     => ( isset( $settings['show_account'] ) && 'yes' === $settings['show_account'] ) ? 'yes' : 'no',
			'show_cart'        => ( isset( $settings['show_cart'] ) && 'yes' === $settings['show_cart'] ) ? 'yes' : 'no',
			'mini_cart'        => ( isset( $settings['mini_cart'] ) && 'yes' === $settings['mini_cart'] ) ? 'yes' : 'no',
			'show_categories'  => ( isset( $settings['show_categories'] ) && 'yes' === $settings['show_categories'] ) ? 'yes' : 'no',
			'categories_label' => isset( $settings['categories_label'] ) ? $settings['categories_label'] : '',
			'categories_limit' => isset( $settings['categories_limit'] ) ? absint( $settings['categories_limit'] ) : 8,
		];

		echo \HkdevShopElements\Includes\Header_Engine::instance()->header_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
