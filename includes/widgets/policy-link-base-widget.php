<?php
/**
 * Shared base for single policy/info page widgets.
 *
 * Drop one widget into a page and it renders ready-to-use default content.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

abstract class Policy_Link_Base_Widget extends Widget_Base {

	use Style_Controls;

	abstract protected function default_page_title();
	abstract protected function default_page_content();

	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	public function get_style_depends() {
		return [ 'hkdev-elements-policy-link-style', 'hkdev-elements-fontawesome' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'page_title',
			[
				'label'       => esc_html__( 'Title', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $this->default_page_title(),
				'placeholder' => esc_html__( 'Type title...', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'page_content',
			[
				'label'   => esc_html__( 'Content', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => $this->default_page_content(),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$scope = '{{WRAPPER}} .hkdev-policy-link';
		$this->hkdev_select(
			'pl_text_align',
			esc_html__( 'Text Align', 'hkdev-shop-elements' ),
			$scope,
			'text-align',
			[
				''       => esc_html__( 'Default', 'hkdev-shop-elements' ),
				'left'   => esc_html__( 'Left', 'hkdev-shop-elements' ),
				'center' => esc_html__( 'Center', 'hkdev-shop-elements' ),
				'right'  => esc_html__( 'Right', 'hkdev-shop-elements' ),
			]
		);
		$this->hkdev_color( 'pl_wrap_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_color( 'pl_wrap_border', esc_html__( 'Block Border', 'hkdev-shop-elements' ), $scope, 'border-color' );
		$this->hkdev_slider( 'pl_wrap_border_w', esc_html__( 'Block Border Width', 'hkdev-shop-elements' ), $scope, 'border-width', 0, 10 );
		$this->hkdev_dimensions( 'pl_wrap_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_dimensions( 'pl_wrap_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'pl_wrap_radius', esc_html__( 'Block Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );
		$this->hkdev_shadow( 'pl_wrap_shadow', esc_html__( 'Block Shadow', 'hkdev-shop-elements' ), $scope );
		$this->hkdev_typography( 'pl_title_typo', esc_html__( 'Title', 'hkdev-shop-elements' ), $scope . ' .hkdev-policy-link-title' );
		$this->hkdev_typography( 'pl_content_typo', esc_html__( 'Content', 'hkdev-shop-elements' ), $scope . ' .hkdev-policy-link-content' );
		$this->hkdev_color( 'pl_content_link_color', esc_html__( 'Content Link Color', 'hkdev-shop-elements' ), $scope . ' .hkdev-policy-link-content a', 'color' );
		$this->hkdev_color( 'pl_content_link_hover', esc_html__( 'Content Link Hover', 'hkdev-shop-elements' ), $scope . ' .hkdev-policy-link-content a:hover', 'color' );
		$this->hkdev_slider( 'pl_title_gap', esc_html__( 'Title Bottom Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-policy-link-title', 'margin-bottom', 0, 60 );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title    = isset( $settings['page_title'] ) && '' !== trim( (string) $settings['page_title'] )
			? (string) $settings['page_title']
			: $this->default_page_title();
		$content  = isset( $settings['page_content'] ) && '' !== trim( wp_strip_all_tags( (string) $settings['page_content'] ) )
			? (string) $settings['page_content']
			: $this->default_page_content();
		?>
		<div class="hkdev-policy-link">
			<h2 class="hkdev-policy-link-title"><?php echo esc_html( $title ); ?></h2>
			<div class="hkdev-policy-link-content">
				<?php echo wp_kses_post( $this->parse_text_editor( $content ) ); ?>
			</div>
		</div>
		<?php
	}
}

