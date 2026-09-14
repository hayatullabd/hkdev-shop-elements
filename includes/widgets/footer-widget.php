<?php
/**
 * HKDEV Footer Widget (HKDEV Shop Elements plugin).
 *
 * Elementor widget for the brand-matched site footer. Self-contained – does
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
 * Class Footer_Widget
 */
class Footer_Widget extends Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_footer';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Footer', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-footer';
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
		return [ 'footer', 'bottom', 'copyright', 'newsletter', 'social', 'payment' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-footer-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-footer-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_brand',
			[
				'label' => esc_html__( 'Brand & Contact', 'hkdev-shop-elements' ),
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
			'about',
			[
				'label'   => esc_html__( 'About Text', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => '',
			]
		);

		$this->add_control(
			'address',
			[
				'label'     => esc_html__( 'Address', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'phone',
			[
				'label'   => esc_html__( 'Phone Number', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'email',
			[
				'label'   => esc_html__( 'Email Address', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'hours',
			[
				'label'   => esc_html__( 'Opening Hours', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_columns',
			[
				'label' => esc_html__( 'Link Columns', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'menu',
			[
				'label'   => esc_html__( 'Quick Links Menu', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_menus_options(),
			]
		);

		$this->add_control(
			'menu_title',
			[
				'label'   => esc_html__( 'Menu Column Title', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Quick Links', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_categories',
			[
				'label'        => esc_html__( 'Show Product Categories', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'categories_title',
			[
				'label'     => esc_html__( 'Categories Title', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Categories', 'hkdev-shop-elements' ),
				'condition' => [ 'show_categories' => 'yes' ],
			]
		);

		$this->add_control(
			'categories_limit',
			[
				'label'     => esc_html__( 'Number of Categories', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 1,
				'max'       => 30,
				'condition' => [ 'show_categories' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_newsletter',
			[
				'label' => esc_html__( 'Newsletter & Social', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_newsletter',
			[
				'label'        => esc_html__( 'Show Newsletter Form', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'newsletter_title',
			[
				'label'     => esc_html__( 'Newsletter Title', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Newsletter', 'hkdev-shop-elements' ),
				'condition' => [ 'show_newsletter' => 'yes' ],
			]
		);

		$this->add_control(
			'newsletter_text',
			[
				'label'     => esc_html__( 'Newsletter Text', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXTAREA,
				'rows'      => 2,
				'default'   => '',
				'condition' => [ 'show_newsletter' => 'yes' ],
			]
		);

		$this->add_control(
			'newsletter_action',
			[
				'label'       => esc_html__( 'Form Action URL (optional)', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::URL,
				'default'     => [ 'url' => '' ],
				'description' => esc_html__( 'Point to Mailchimp / your mailing provider. Leave empty to store signups inside WordPress.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_newsletter' => 'yes' ],
			]
		);

		$this->add_control(
			'newsletter_btn',
			[
				'label'     => esc_html__( 'Button Label', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Subscribe', 'hkdev-shop-elements' ),
				'condition' => [ 'show_newsletter' => 'yes' ],
			]
		);

		$this->add_control(
			'show_social',
			[
				'label'        => esc_html__( 'Show Social Icons', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'facebook',
			[
				'label'     => esc_html__( 'Facebook URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_social' => 'yes' ],
			]
		);

		$this->add_control(
			'instagram',
			[
				'label'     => esc_html__( 'Instagram URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_social' => 'yes' ],
			]
		);

		$this->add_control(
			'youtube',
			[
				'label'     => esc_html__( 'YouTube URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_social' => 'yes' ],
			]
		);

		$this->add_control(
			'whatsapp',
			[
				'label'     => esc_html__( 'WhatsApp URL', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_social' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_bottom',
			[
				'label' => esc_html__( 'Payments & Copyright', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'show_payments',
			[
				'label'        => esc_html__( 'Show Payment Banner', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'payment_banner',
			[
				'label'       => esc_html__( 'Payment Banner', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::MEDIA,
				'default'     => [ 'url' => '' ],
				'description' => esc_html__( 'Upload the payment / SSL provider banner image.', 'hkdev-shop-elements' ),
				'condition'   => [ 'show_payments' => 'yes' ],
			]
		);

		$this->add_control(
			'payment_banner_link',
			[
				'label'     => esc_html__( 'Banner Link (optional)', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::URL,
				'default'   => [ 'url' => '' ],
				'condition' => [ 'show_payments' => 'yes' ],
			]
		);

		$this->add_control(
			'copyright',
			[
				'label'       => esc_html__( 'Copyright Text', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Use {year} and {site} as placeholders. Leave empty for the default notice.', 'hkdev-shop-elements' ),
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'show_backtotop',
			[
				'label'        => esc_html__( 'Back to Top Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

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
			'logo'              => isset( $settings['logo']['url'] ) ? $settings['logo']['url'] : '',
			'logo_width'        => isset( $settings['logo_width']['size'] ) ? absint( $settings['logo_width']['size'] ) : 150,
			'about'             => isset( $settings['about'] ) ? $settings['about'] : '',
			'address'           => isset( $settings['address'] ) ? $settings['address'] : '',
			'phone'             => isset( $settings['phone'] ) ? $settings['phone'] : '',
			'email'             => isset( $settings['email'] ) ? $settings['email'] : '',
			'hours'             => isset( $settings['hours'] ) ? $settings['hours'] : '',
			'menu'              => isset( $settings['menu'] ) ? $settings['menu'] : '',
			'menu_title'        => isset( $settings['menu_title'] ) ? $settings['menu_title'] : '',
			'show_categories'   => ( isset( $settings['show_categories'] ) && 'yes' === $settings['show_categories'] ) ? 'yes' : 'no',
			'categories_title'  => isset( $settings['categories_title'] ) ? $settings['categories_title'] : '',
			'categories_limit'  => isset( $settings['categories_limit'] ) ? absint( $settings['categories_limit'] ) : 6,
			'show_newsletter'   => ( isset( $settings['show_newsletter'] ) && 'yes' === $settings['show_newsletter'] ) ? 'yes' : 'no',
			'newsletter_title'  => isset( $settings['newsletter_title'] ) ? $settings['newsletter_title'] : '',
			'newsletter_text'   => isset( $settings['newsletter_text'] ) ? $settings['newsletter_text'] : '',
			'newsletter_action' => isset( $settings['newsletter_action']['url'] ) ? $settings['newsletter_action']['url'] : '',
			'newsletter_btn'    => isset( $settings['newsletter_btn'] ) ? $settings['newsletter_btn'] : '',
			'show_social'       => ( isset( $settings['show_social'] ) && 'yes' === $settings['show_social'] ) ? 'yes' : 'no',
			'facebook'          => isset( $settings['facebook']['url'] ) ? $settings['facebook']['url'] : '',
			'instagram'         => isset( $settings['instagram']['url'] ) ? $settings['instagram']['url'] : '',
			'youtube'           => isset( $settings['youtube']['url'] ) ? $settings['youtube']['url'] : '',
			'whatsapp'          => isset( $settings['whatsapp']['url'] ) ? $settings['whatsapp']['url'] : '',
			'show_payments'       => ( isset( $settings['show_payments'] ) && 'yes' === $settings['show_payments'] ) ? 'yes' : 'no',
			'payment_banner'      => isset( $settings['payment_banner']['url'] ) ? $settings['payment_banner']['url'] : '',
			'payment_banner_link' => isset( $settings['payment_banner_link']['url'] ) ? $settings['payment_banner_link']['url'] : '',
			'copyright'         => isset( $settings['copyright'] ) ? $settings['copyright'] : '',
			'show_backtotop'    => ( isset( $settings['show_backtotop'] ) && 'yes' === $settings['show_backtotop'] ) ? 'yes' : 'no',
		];

		echo \HkdevShopElements\Includes\Footer_Engine::instance()->footer_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
