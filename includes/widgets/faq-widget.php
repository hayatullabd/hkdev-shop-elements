<?php
/**
 * HKDEV FAQ Widget (HKDEV Shop Elements plugin).
 *
 * Reusable FAQ / accordion widget for Elementor. Can be used on single product
 * pages and any other page/template.
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

class FAQ_Widget extends Widget_Base {

	use Style_Controls;

	public function get_name() {
		return 'hkdev_faq';
	}

	public function get_title() {
		return esc_html__( 'HKDEV FAQ', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-help-o';
	}

	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	public function get_keywords() {
		return [ 'faq', 'accordion', 'question', 'answer', 'single product' ];
	}

	public function get_style_depends() {
		return [ 'hkdev-elements-faq-style', 'hkdev-elements-fontawesome' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'faq_content_section',
			[
				'label' => esc_html__( 'FAQ Content', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'faq_heading',
			[
				'label'       => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Frequently Asked Questions', 'hkdev-shop-elements' ),
				'placeholder' => esc_html__( 'Type heading...', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'faq_subheading',
			[
				'label'       => esc_html__( 'Subheading', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Find quick answers to common product and order questions.', 'hkdev-shop-elements' ),
				'rows'        => 3,
				'placeholder' => esc_html__( 'Type subheading...', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'faq_first_open',
			[
				'label'        => esc_html__( 'Open First Item By Default', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'hkdev-shop-elements' ),
				'label_off'    => esc_html__( 'No', 'hkdev-shop-elements' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'question',
			[
				'label'       => esc_html__( 'Question', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'What is your delivery time?', 'hkdev-shop-elements' ),
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'answer',
			[
				'label'   => esc_html__( 'Answer', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'Inside Dhaka usually takes 1-2 days, outside Dhaka 2-4 days depending on courier coverage.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'faq_items',
			[
				'label'       => esc_html__( 'FAQ Items', 'hkdev-shop-elements' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ question }}}',
				'default'     => [
					[
						'question' => esc_html__( 'What is your delivery time?', 'hkdev-shop-elements' ),
						'answer'   => esc_html__( 'Inside Dhaka usually takes 1-2 days, outside Dhaka 2-4 days depending on courier coverage.', 'hkdev-shop-elements' ),
					],
					[
						'question' => esc_html__( 'Can I return a product?', 'hkdev-shop-elements' ),
						'answer'   => esc_html__( 'Yes. If a product is damaged or incorrect, contact support within 24 hours with your order details.', 'hkdev-shop-elements' ),
					],
					[
						'question' => esc_html__( 'How can I track my order?', 'hkdev-shop-elements' ),
						'answer'   => esc_html__( 'Use the Order Tracking section and provide your order ID and phone number to check live status.', 'hkdev-shop-elements' ),
					],
				],
			]
		);

		$this->end_controls_section();

		$this->register_faq_style_controls();
	}

	protected function register_faq_style_controls() {
		$scope = '{{WRAPPER}} .hkdev-faq-wrap';

		$this->start_controls_section(
			'faq_style_layout',
			[
				'label' => esc_html__( 'Layout', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'faq_bg', esc_html__( 'Background', 'hkdev-shop-elements' ), $scope, 'background-color' );
		$this->hkdev_dimensions( 'faq_padding', esc_html__( 'Padding', 'hkdev-shop-elements' ), $scope, 'padding' );
		$this->hkdev_dimensions( 'faq_margin', esc_html__( 'Margin', 'hkdev-shop-elements' ), $scope, 'margin' );
		$this->hkdev_dimensions( 'faq_radius', esc_html__( 'Border Radius', 'hkdev-shop-elements' ), $scope, 'border-radius' );
		$this->hkdev_shadow( 'faq_shadow', esc_html__( 'Box Shadow', 'hkdev-shop-elements' ), $scope );

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_style_header',
			[
				'label' => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_typography( 'faq_title_typo', esc_html__( 'Title', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-title' );
		$this->hkdev_typography( 'faq_subtitle_typo', esc_html__( 'Subtitle', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-subtitle' );

		$this->end_controls_section();

		$this->start_controls_section(
			'faq_style_items',
			[
				'label' => esc_html__( 'FAQ Items', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_color( 'faq_item_bg', esc_html__( 'Item Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-item', 'background-color' );
		$this->hkdev_color( 'faq_item_border', esc_html__( 'Item Border', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-item', 'border-color' );
		$this->hkdev_slider( 'faq_item_border_w', esc_html__( 'Item Border Width', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-item', 'border-width', 0, 8 );
		$this->hkdev_dimensions( 'faq_item_radius', esc_html__( 'Item Radius', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-item', 'border-radius' );
		$this->hkdev_typography( 'faq_question_typo', esc_html__( 'Question', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-question' );
		$this->hkdev_typography( 'faq_answer_typo', esc_html__( 'Answer', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-answer' );
		$this->hkdev_color( 'faq_question_bg', esc_html__( 'Question Row Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-question', 'background-color' );
		$this->hkdev_color( 'faq_question_hover_bg', esc_html__( 'Question Hover Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-question:hover', 'background-color' );
		$this->hkdev_color( 'faq_answer_bg', esc_html__( 'Answer Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-answer', 'background-color' );
		$this->hkdev_dimensions( 'faq_question_padding', esc_html__( 'Question Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-question', 'padding' );
		$this->hkdev_dimensions( 'faq_answer_padding', esc_html__( 'Answer Padding', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-answer', 'padding' );
		$this->hkdev_color( 'faq_icon_color', esc_html__( 'Toggle Icon', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-toggle i', 'color' );
		$this->hkdev_color( 'faq_icon_bg', esc_html__( 'Toggle Icon Background', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-toggle', 'background-color' );
		$this->hkdev_slider( 'faq_item_gap', esc_html__( 'Item Gap', 'hkdev-shop-elements' ), $scope . ' .hkdev-faq-list', 'gap', 0, 30 );
		$this->hkdev_select(
			'faq_text_align',
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

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = isset( $settings['faq_items'] ) && is_array( $settings['faq_items'] ) ? $settings['faq_items'] : [];

		if ( empty( $items ) ) {
			return;
		}

		$open_first = isset( $settings['faq_first_open'] ) && 'yes' === $settings['faq_first_open'];
		?>
		<div class="hkdev-faq-wrap">
			<?php if ( ! empty( $settings['faq_heading'] ) || ! empty( $settings['faq_subheading'] ) ) : ?>
				<div class="hkdev-faq-head">
					<?php if ( ! empty( $settings['faq_heading'] ) ) : ?>
						<h3 class="hkdev-faq-title"><?php echo esc_html( $settings['faq_heading'] ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $settings['faq_subheading'] ) ) : ?>
						<p class="hkdev-faq-subtitle"><?php echo esc_html( $settings['faq_subheading'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="hkdev-faq-list">
				<?php foreach ( $items as $idx => $item ) : ?>
					<?php
					$question = isset( $item['question'] ) ? trim( (string) $item['question'] ) : '';
					$answer   = isset( $item['answer'] ) ? $item['answer'] : '';
					if ( '' === $question && '' === trim( wp_strip_all_tags( (string) $answer ) ) ) {
						continue;
					}
					?>
					<details class="hkdev-faq-item"<?php echo ( $open_first && 0 === (int) $idx ) ? ' open' : ''; ?>>
						<summary class="hkdev-faq-question">
							<span><?php echo esc_html( $question ); ?></span>
							<span class="hkdev-faq-toggle" aria-hidden="true">
								<i class="fa-solid fa-chevron-down"></i>
							</span>
						</summary>
						<div class="hkdev-faq-answer">
							<?php echo wp_kses_post( $this->parse_text_editor( $answer ) ); ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

