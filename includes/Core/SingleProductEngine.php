<?php
/**
 * HKDEV Single Product Engine (HKDEV Shop Elements plugin).
 *
 * Full custom WooCommerce single product layout: gallery, sale badge, variable
 * swatches, size chart, quantity, AJAX add-to-cart, buy-now, tabs, meta.
 * Self-contained – works with ANY WordPress theme + Elementor + WooCommerce.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SingleProductEngine
 */
class SingleProductEngine {

	const AJAX_ACTION  = 'hkdev_elements_ajax_add_to_cart';
	const NONCE_ACTION = 'hkdev_elements_add_to_cart';

	/**
	 * @var ?SingleProductEngine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SingleProductEngine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	const VIDEO_SLOTS = 5;

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_add_to_cart_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_add_to_cart_handler' ] );
		add_action( 'template_redirect', [ $this, 'track_recently_viewed' ], 20 );

		// Product video meta box (admin).
		add_action( 'add_meta_boxes', [ $this, 'register_video_meta_box' ] );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_video_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_video_meta_box_assets' ] );

		// Product FAQ.
		add_action( 'add_meta_boxes', [ $this, 'register_faq_media_meta_boxes' ] );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_faq_meta_box' ] );
	}

	/**
	 * Register the "Product Videos" meta box on the product edit screen.
	 *
	 * @return void
	 */
	public function register_video_meta_box() {
		add_meta_box(
			'hkdev_product_videos',
			__( 'Product Videos', 'hkdev-shop-elements' ),
			[ $this, 'render_video_meta_box' ],
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue the media uploader and our small admin script on the product
	 * edit screen only.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_video_meta_box_assets( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Render the "Product Videos" meta box.
	 *
	 * @param \WP_Post $post Product post object.
	 * @return void
	 */
	public function render_video_meta_box( $post ) {
		$videos   = get_post_meta( $post->ID, '_hkdev_product_videos', true );
		$videos   = is_array( $videos ) ? $videos : [];
		$old_url  = get_post_meta( $post->ID, '_hkdev_product_video_url', true );

		// Backward compat: migrate the single old key into the first slot.
		if ( empty( $videos ) && ! empty( $old_url ) ) {
			$videos[] = [ 'url' => $old_url ];
		}

		wp_nonce_field( 'hkdev_save_product_videos', 'hkdev_product_videos_nonce' );
		?>
		<p class="description" style="margin: 0 0 12px; padding: 0 12px;">
			<?php esc_html_e( 'Add YouTube, Vimeo or direct video file links. They appear as switchable thumbs in the product gallery next to the images. Use the upload button to pick a video from your media library, or paste a URL.', 'hkdev-shop-elements' ); ?>
		</p>
		<div class="hkdev-video-slots" style="padding: 0 12px;">
			<?php for ( $i = 0; $i < self::VIDEO_SLOTS; $i++ ) : ?>
				<?php
				$slot_url = isset( $videos[ $i ]['url'] ) ? $videos[ $i ]['url'] : '';
				?>
				<div class="hkdev-video-slot" style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
					<span class="hkdev-video-slot-num" style="width: 22px; font-weight: 700; color: #555;"><?php echo intval( $i + 1 ); ?>.</span>
					<input type="text"
						class="hkdev-video-url short-text"
						name="hkdev_video_urls[]"
						value="<?php echo esc_url( $slot_url ); ?>"
						placeholder="<?php esc_attr_e( 'Paste YouTube / Vimeo / .mp4 URL', 'hkdev-shop-elements' ); ?>"
						style="flex: 1; min-width: 200px;"
					/>
					<button type="button" class="button hkdev-video-upload-btn" data-target="hkdev_video_urls">
						<span class="dashicons dashicons-upload" style="vertical-align: middle; font-size: 16px; line-height: 1.4;"></span>
						<?php esc_html_e( 'Upload / Add Video', 'hkdev-shop-elements' ); ?>
					</button>
					<?php if ( $slot_url ) : ?>
						<button type="button" class="button hkdev-video-clear-btn" style="color: #a00;"><?php esc_html_e( 'Clear', 'hkdev-shop-elements' ); ?></button>
					<?php endif; ?>
				</div>
			<?php endfor; ?>
		</div>
		<script>
		(function($){
			'use strict';
			if ( typeof wp === 'undefined' || ! wp.media ) { return; }
			$(document).on('click', '.hkdev-video-upload-btn', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $row = $btn.closest('.hkdev-video-slot');
				var $input = $row.find('input[name="hkdev_video_urls[]"]');
				var frame = wp.media({
					title: '<?php echo esc_js( __( 'Select or Upload Product Video', 'hkdev-shop-elements' ) ); ?>',
					library: { type: 'video' },
					button: { text: '<?php echo esc_js( __( 'Use this video', 'hkdev-shop-elements' ) ); ?>' },
					multiple: false
				});
				frame.on('select', function() {
					var url = frame.state().get('selection').first().get('url');
					$input.val(url).trigger('change');
				});
				frame.open();
			});
			$(document).on('click', '.hkdev-video-clear-btn', function(e) {
				e.preventDefault();
				$(this).closest('.hkdev-video-slot').find('input[name="hkdev_video_urls[]"]').val('');
				$(this).remove();
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save the product videos meta box.
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function save_video_meta_box( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies its own nonce on this hook.
		if ( ! isset( $_POST['hkdev_product_videos_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hkdev_product_videos_nonce'] ) ), 'hkdev_save_product_videos' ) ) {
			return;
		}

		$raw_urls = isset( $_POST['hkdev_video_urls'] ) && is_array( $_POST['hkdev_video_urls'] ) ? wp_unslash( $_POST['hkdev_video_urls'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$videos = array();
		foreach ( $raw_urls as $raw ) {
			$url = esc_url_raw( trim( (string) $raw ) );
			if ( '' === $url ) {
				continue;
			}
			$videos[] = array( 'url' => $url );
		}

		if ( empty( $videos ) ) {
			$product->delete_meta_data( '_hkdev_product_videos' );
			$product->delete_meta_data( '_hkdev_product_video_url' );
		} else {
			$product->update_meta_data( '_hkdev_product_videos', $videos );
			// Keep the old single key in sync for any code still reading it.
			$product->update_meta_data( '_hkdev_product_video_url', $videos[0]['url'] );
		}
	}

	/**
	 * Collect all video URLs for a product (new array + old single fallback).
	 *
	 * @param int $product_id Product ID.
	 * @return string[]
	 */
	public static function get_product_video_urls( $product_id ) {
		$videos = get_post_meta( $product_id, '_hkdev_product_videos', true );
		if ( is_array( $videos ) && ! empty( $videos ) ) {
			$urls = array();
			foreach ( $videos as $v ) {
				if ( ! empty( $v['url'] ) ) {
					$urls[] = $v['url'];
				}
			}
			return $urls;
		}

		$old = get_post_meta( $product_id, '_hkdev_product_video_url', true );
		return ! empty( $old ) ? array( $old ) : array();
	}

	/**
	 * Get the saved FAQ pairs for a product (with backward-compat for the old
	 * single _hkdev_product_faq text key).
	 *
	 * @param int $product_id Product ID.
	 * @return array Array of { question: string, answer: string }.
	 */
	public static function get_product_faqs( $product_id ) {
		$faqs = get_post_meta( $product_id, '_hkdev_product_faqs', true );
		if ( is_array( $faqs ) && ! empty( $faqs ) ) {
			return $faqs;
		}

		$old = get_post_meta( $product_id, '_hkdev_product_faq', true );
		return ! empty( $old ) ? array( array( 'question' => '', 'answer' => wp_kses_post( $old ) ) ) : array();
	}

	/**
	 * Register the FAQ meta box on the product edit screen.
	 *
	 * @return void
	 */
	public function register_faq_media_meta_boxes() {
		add_meta_box(
			'hkdev_product_faq',
			__( 'Product FAQ', 'hkdev-shop-elements' ),
			[ $this, 'render_faq_meta_box' ],
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render the Product FAQ meta box.
	 *
	 * @param \WP_Post $post Product post object.
	 * @return void
	 */
	public function render_faq_meta_box( $post ) {
		$faqs = self::get_product_faqs( $post->ID );
		wp_nonce_field( 'hkdev_save_product_faq', 'hkdev_product_faq_nonce' );
		?>
		<div class="hkdev-faq-meta">
			<p class="description"><?php esc_html_e( 'Add frequently asked questions for this product. Customers can expand each question to see the answer.', 'hkdev-shop-elements' ); ?></p>
			<table class="widefat hkdev-faq-table" style="margin: 10px 0; border-collapse: collapse;">
				<thead>
					<tr>
						<th style="padding: 8px; text-align: left; border-bottom: 1px solid #ccc;"><?php esc_html_e( 'Question', 'hkdev-shop-elements' ); ?></th>
						<th style="padding: 8px; text-align: left; border-bottom: 1px solid #ccc;"><?php esc_html_e( 'Answer', 'hkdev-shop-elements' ); ?></th>
					</tr>
				</thead>
				<tbody id="hkdev-faq-rows">
					<?php
					// Ensure at least one empty row.
					if ( empty( $faqs ) ) {
						$faqs = array( array( 'question' => '', 'answer' => '' ) );
					}
					foreach ( $faqs as $i => $faq ) {
						$q = isset( $faq['question'] ) ? esc_textarea( $faq['question'] ) : '';
						$a = isset( $faq['answer'] ) ? esc_textarea( $faq['answer'] ) : '';
						?>
						<tr class="hkdev-faq-row">
							<td style="padding: 8px; vertical-align: top;">
								<textarea name="hkdev_faq_questions[]" rows="2" style="width:100%; box-sizing:border-box;"><?php echo $q; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
							</td>
							<td style="padding: 8px; vertical-align: top;">
								<textarea name="hkdev_faq_answers[]" rows="3" style="width:100%; box-sizing:border-box;"><?php echo $a; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<button type="button" class="button hkdev-faq-add-row"><?php esc_html_e( '+ Add FAQ', 'hkdev-shop-elements' ); ?></button>
		</div>
		<script>
		(function($){
			'use strict';
			$(function(){
				$('.hkdev-faq-add-row').on('click', function(e){
					e.preventDefault();
					$('#hkdev-faq-rows').append('<tr class="hkdev-faq-row">' +
						'<td style="padding:8px;vertical-align:top;">' +
						'<textarea name="hkdev_faq_questions[]" rows="2" style="width:100%;box-sizing:border-box;"></textarea>' +
						'</td>' +
						'<td style="padding:8px;vertical-align:top;">' +
						'<textarea name="hkdev_faq_answers[]" rows="3" style="width:100%;box-sizing:border-box;"></textarea>' +
						'</td></tr>');
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save FAQ meta box values.
	 *
	 * @param \WC_Product $product Product object.
	 * @return void
	 */
	public function save_faq_meta_box( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC verifies its own nonce on this hook.
		if ( ! isset( $_POST['hkdev_product_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hkdev_product_faq_nonce'] ) ), 'hkdev_save_product_faq' ) ) {
			return;
		}

		// FAQ.
		$questions = isset( $_POST['hkdev_faq_questions'] ) && is_array( $_POST['hkdev_faq_questions'] ) ? wp_unslash( $_POST['hkdev_faq_questions'] ) : array();
		$answers   = isset( $_POST['hkdev_faq_answers'] ) && is_array( $_POST['hkdev_faq_answers'] ) ? wp_unslash( $_POST['hkdev_faq_answers'] ) : array();

		$faqs = array();
		$count = min( count( $questions ), count( $answers ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$q = trim( (string) $questions[ $i ] );
			$a = trim( (string) $answers[ $i ] );
			if ( '' === $q && '' === $a ) {
				continue;
			}
			$faqs[] = array(
				'question' => sanitize_text_field( $q ),
				'answer'   => wp_kses_post( $a ),
			);
		}

		if ( empty( $faqs ) ) {
			$product->delete_meta_data( '_hkdev_product_faqs' );
		} else {
			$product->update_meta_data( '_hkdev_product_faqs', $faqs );
		}
	}

	/**
	 * Render the FAQ section on the frontend.
	 *
	 * @param int $product_id Product ID.
	 * @return string HTML.
	 */
	public static function render_product_faqs( $product_id ) {
		$faqs = self::get_product_faqs( $product_id );
		if ( empty( $faqs ) ) {
			return '';
		}

		$html   = '<div class="hkdev-sp-faq-section">';
		$html  .= '<h3 class="hkdev-sp-faq-title">' . esc_html__( 'Product FAQ', 'hkdev-shop-elements' ) . '</h3>';
		$html .= '<div class="hkdev-sp-faq-list">';
		foreach ( $faqs as $faq ) {
			$question = ! empty( $faq['question'] ) ? $faq['question'] : esc_html__( 'Question', 'hkdev-shop-elements' );
			$answer   = ! empty( $faq['answer'] ) ? $faq['answer'] : '';
			$html .= '<div class="hkdev-sp-faq-item">';
			$html .= '<button type="button" class="hkdev-sp-faq-question" aria-expanded="false">' . esc_html( $question ) . '</button>';
			$html .= '<div class="hkdev-sp-faq-answer" style="display:none;">' . wp_kses_post( $answer ) . '</div>';
			$html .= '</div>';
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build the embed markup for a stored video URL.
	 *
	 * Supports YouTube (lite poster + click-to-load), Vimeo (iframe), and
	 * direct .mp4 / .webm files (HTML5 video tag). Returns an empty string
	 * when the URL is not recognised.
	 *
	 * @param string $url Video URL.
	 * @return array{type:string, embed:string, thumbnail:string}
	 */
	public static function build_video_embed( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return [ 'type' => '', 'embed' => '', 'thumbnail' => '' ];
		}

		$youtube_id = \HkdevShopElements\Includes\Core\VideoEngine::youtube_id( $url );
		if ( '' !== $youtube_id ) {
			$embed     = \HkdevShopElements\Includes\Core\VideoEngine::youtube_embed_url( $youtube_id, true );
			$thumbnail = 'https://i.ytimg.com/vi/' . $youtube_id . '/hqdefault.jpg';
			return [
				'type'      => 'youtube',
				'embed'     => $embed,
				'thumbnail' => $thumbnail,
				'id'        => $youtube_id,
			];
		}

		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~i', $url, $match ) ) {
			$thumbnail = 'https://vumbnail.com/' . $match[1] . '.jpg';
			return [
				'type'      => 'vimeo',
				'embed'     => 'https://player.vimeo.com/video/' . $match[1],
				'thumbnail' => $thumbnail,
			];
		}

		if ( preg_match( '~\.(mp4|webm|ogg|ogv|mov|m4v)(\?.*)?$~i', $url, $match ) ) {
			$src = esc_url( $url );
			return [
				'type'      => 'direct',
				'embed'     => $src,
				'thumbnail' => '',
				'ext'       => strtolower( $match[1] ),
			];
		}

		// Anything else with a usable scheme: try a plain iframe.
		if ( preg_match( '~^https?://~i', $url ) ) {
			return [
				'type'      => 'iframe',
				'embed'     => esc_url( $url ),
				'thumbnail' => '',
			];
		}

		return [ 'type' => '', 'embed' => '', 'thumbnail' => '' ];
	}

	/**
	 * Build the player markup for a video (used in the gallery viewport).
	 *
	 * YouTube uses the lite poster + click-to-load pattern (same as the
	 * Video_Engine widget); Vimeo / iframe / direct use an immediate
	 * embed so the video is ready to play.
	 *
	 * @param array $video Array from build_video_embed().
	 * @return string HTML.
	 */
	public static function render_video_player( $video ) {
		if ( empty( $video['type'] ) || empty( $video['embed'] ) ) {
			return '';
		}

		if ( 'youtube' === $video['type'] ) {
			$poster     = ! empty( $video['thumbnail'] ) ? $video['thumbnail'] : 'https://i.ytimg.com/vi/' . $video['id'] . '/maxresdefault.jpg';
			$watch_url  = \HkdevShopElements\Includes\Core\VideoEngine::youtube_watch_url( $video['id'] );
			$play_label = __( 'Play video', 'hkdev-shop-elements' );
			return '<a href="' . esc_url( $watch_url ) . '" class="hkdev-sp-vp-lite" data-youtube-id="' . esc_attr( $video['id'] ) . '" data-youtube-embed="' . esc_url( $video['embed'] ) . '" data-youtube-fallback="" aria-label="' . esc_attr( $play_label ) . '" target="_blank" rel="noopener">'
				. '<img src="' . esc_url( $poster ) . '" alt="" loading="lazy" decoding="async" aria-hidden="true">'
				. '<span class="hkdev-sp-vp-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>'
				. '</a>';
		}

		if ( 'direct' === $video['type'] ) {
			$ext = isset( $video['ext'] ) ? $video['ext'] : 'mp4';
			return '<video class="hkdev-sp-vp-video" controls playsinline preload="metadata">'
				. '<source src="' . esc_url( $video['embed'] ) . '" type="video/' . esc_attr( $ext ) . '">'
				. '</video>';
		}

		// vimeo / iframe.
		return '<iframe class="hkdev-sp-vp-iframe" src="' . esc_url( $video['embed'] ) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="' . esc_attr__( 'Product video', 'hkdev-shop-elements' ) . '"></iframe>';
	}

	/**
	 * Whether the current request is an Elementor editor / preview request.
	 *
	 * @return bool
	 */
	public function is_elementor_edit() {
		return \HkdevShopElements\Includes\Core\HeaderEngine::instance()->is_elementor_edit();
	}

	/**
	 * Resolve which product to render in the widget / shortcode.
	 *
	 * @param array $atts Parsed shortcode attributes.
	 * @return \WC_Product|null
	 */
	private function resolve_product_for_render( $atts ) {
		global $product;

		if ( ! empty( $atts['id'] ) ) {
			$by_id = wc_get_product( absint( $atts['id'] ) );
			if ( $by_id instanceof \WC_Product ) {
				return $by_id;
			}
		}

		if ( is_product() && $product instanceof \WC_Product ) {
			return $product;
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance ) ) {
			$elementor = \Elementor\Plugin::$instance;

			if ( isset( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
				$editor_post_id = method_exists( $elementor->editor, 'get_post_id' ) ? absint( $elementor->editor->get_post_id() ) : 0;
				if ( $editor_post_id && 'product' === get_post_type( $editor_post_id ) ) {
					$editor_product = wc_get_product( $editor_post_id );
					if ( $editor_product instanceof \WC_Product ) {
						return $editor_product;
					}
				}
			}

			if ( isset( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
				$preview_post_id = method_exists( $elementor->preview, 'get_post_id' ) ? absint( $elementor->preview->get_post_id() ) : 0;
				if ( ! $preview_post_id ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$preview_post_id = isset( $_GET['preview_id'] ) ? absint( wp_unslash( $_GET['preview_id'] ) ) : 0;
				}
				if ( $preview_post_id && 'product' === get_post_type( $preview_post_id ) ) {
					$preview_product = wc_get_product( $preview_post_id );
					if ( $preview_product instanceof \WC_Product ) {
						return $preview_product;
					}
				}
			}
		}

		$current_id = get_the_ID();
		if ( $current_id && 'product' === get_post_type( $current_id ) ) {
			$current_product = wc_get_product( $current_id );
			if ( $current_product instanceof \WC_Product ) {
				return $current_product;
			}
		}

		if ( $this->is_elementor_edit() && function_exists( 'wc_get_products' ) ) {
			$sample = wc_get_products(
				[
					'limit'   => 1,
					'status'  => 'publish',
					'orderby' => 'date',
					'order'   => 'DESC',
				]
			);
			if ( ! empty( $sample[0] ) && $sample[0] instanceof \WC_Product ) {
				return $sample[0];
			}
		}

		return null;
	}

	/**
	 * Inline CSS variables for fonts and color theme on the single product wrapper.
	 *
	 * @param array $design Normalized design attributes.
	 * @return string
	 */
	private function design_style_attr( $design ) {
		$font_stack = \HkdevShopElements\Includes\Core\ShopEngine::widget_font_stack( $design['font_family'] );
		$font_latin = \HkdevShopElements\Includes\Core\ShopEngine::widget_font_stack( $design['font_family_latin'] );
		$css        = '--sp-font:' . $font_stack . ';';
		$css       .= '--sp-font-bn:' . $font_stack . ';';
		$css       .= '--sp-font-en:' . $font_latin . ';';
		$css       .= '--hkdev-font:' . $font_stack . ';';

		if ( 'orange' === $design['card_theme'] ) {
			$css .= '--sp-brand-primary:#f06724;--sp-brand-secondary:#03a550;--sp-brand-info:#c45822;--sp-brand-accent:#f06724;--sp-hover-border:rgba(240,103,36,0.28);';
		} elseif ( 'monochrome' === $design['card_theme'] ) {
			$css .= '--sp-brand-primary:#222222;--sp-brand-secondary:#4a4a4a;--sp-brand-info:#3a3a3a;--sp-brand-accent:#2f2f2f;--sp-page-bg:#f3f3f3;--sp-bg-soft:#f3f3f3;--sp-text-color:#1d1d1d;--sp-text-muted:#666666;';
		}

		return $css;
	}

	/**
	 * Render the custom single product layout.
	 *
	 * @param array $atts Widget/shortcode attributes.
	 * @return string
	 */
	public function custom_single_product_shortcode( $atts = [] ) {
		if ( is_admin() && ! $this->is_elementor_edit() ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'id'                 => '',
				'phone'              => '',
				'whatsapp'           => '',
				'show_whatsapp'      => 'yes',
				'show_call'          => 'yes',
				'show_brand'         => 'yes',
				'show_category'      => 'yes',
				'show_breadcrumb'    => 'yes',
				'show_sale_badge'    => 'yes',
				'show_zoom'          => 'yes',
				'show_qty'           => 'yes',
				'show_sku'           => 'yes',
				'show_stock'         => 'yes',
				'show_tabs'          => 'yes',
				'show_faq'           => 'yes',
				'gallery_sticky'     => 'no',
				'gallery_image_size' => 'large',
				'thumb_image_size'   => 'thumbnail',
				'font_mode'          => 'single',
				'font_family'        => 'hind_siliguri',
				'font_family_latin'  => 'system_sans',
				'card_preset'        => 'clean',
				'card_theme'         => 'green',
			],
			$atts,
			'hkdev_single_product'
		);

		global $product, $post;
		$previous_product = $product;

		$product = $this->resolve_product_for_render( $atts );

		if ( ! $product instanceof \WC_Product ) {
			$product = $previous_product;
			return '<div style="text-align:center; padding: 60px; color: #e5533d; font-family: inherit; background: #fff; border-radius: 12px; border: 1px solid #eee;">' . esc_html__( 'Product not found.', 'hkdev-shop-elements' ) . '</div>';
		}

		$product_id = $product->get_id();

		// Set global post so WooCommerce hooks keep working.
		$post = get_post( $product_id );
		setup_postdata( $post );

		// ============================================================
		// DUPLICATE PREVENTION: temporarily remove WC default elements.
		// ============================================================
		$wc_single_defaults = [
			[ 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 ],
			[ 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 ],
			[ 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 ],
			[ 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 ],
			[ 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 ],
			[ 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 ],
		];
		foreach ( $wc_single_defaults as $hook ) {
			remove_action( $hook[0], $hook[1], $hook[2] );
		}

		// Size chart URL from product meta.
		$size_chart_url = get_post_meta( $product_id, '_hkdev_size_chart_url', true );

		$percentage = 0;
		if ( $product->is_on_sale() && 'variable' !== $product->get_type() ) {
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();
			if ( $regular_price > 0 ) {
				$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
			}
		}

		$attachment_ids = $product->get_gallery_image_ids();
		$main_image_id  = $product->get_image_id();

		$is_in_cart = false;
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( (int) $cart_item['product_id'] === (int) $product_id ) {
					$is_in_cart = true;
					break;
				}
			}
		}

		$checkout_url = wc_get_checkout_url();

		$is_variable            = $product->is_type( 'variable' );
		$is_in_stock            = $product->is_in_stock();
		$disable_purchase_btns  = ! $is_in_stock && ! $is_in_cart;
		$purchase_disabled_attr = $disable_purchase_btns ? ' disabled' : '';
		$purchase_disabled_cls  = $disable_purchase_btns ? ' is-out-of-stock' : '';

		// Purchase-zone stock notice: simple / fully OOS on load; partial variable stock is handled in JS.
		$show_oos_notice_initial = $disable_purchase_btns && ! $is_variable;
		if ( $disable_purchase_btns && $is_variable && ! $is_in_stock ) {
			$show_oos_notice_initial = true;
		}

		// ---- WhatsApp / Call order buttons ----
		$phone         = trim( (string) $atts['phone'] );
		$whatsapp      = trim( (string) $atts['whatsapp'] );
		$show_whatsapp = ( 'yes' === $atts['show_whatsapp'] && '' !== $whatsapp );
		$show_call     = ( 'yes' === $atts['show_call'] && '' !== $phone );

		$whatsapp_link = '';
		if ( $show_whatsapp ) {
			$wa_digits = preg_replace( '/[^0-9]/', '', $whatsapp );
			if ( 0 === strpos( $wa_digits, '0' ) ) {
				$wa_digits = '88' . $wa_digits;
			}
			$wa_message    = sprintf( 'Hello, I want to order: %s - %s', $product->get_name(), get_permalink( $product_id ) );
			$whatsapp_link = 'https://wa.me/' . $wa_digits . '?text=' . rawurlencode( $wa_message );
		}

		$call_link = $show_call ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) : '';

		// ---- Brand (WooCommerce native product_brand) & Category ----
		$brand_name = '';
		$brand_logo = '';
		if ( 'yes' === $atts['show_brand'] ) {
			$brand_terms = get_the_terms( $product_id, 'product_brand' );
			if ( $brand_terms && ! is_wp_error( $brand_terms ) ) {
				$brand_name = $brand_terms[0]->name;
				$thumb_id   = (int) get_term_meta( $brand_terms[0]->term_id, 'thumbnail_id', true );
				if ( $thumb_id ) {
					$brand_logo = wp_get_attachment_image_url( $thumb_id, 'thumbnail' );
				}
			}
		}

		$category_names = [];
		if ( 'yes' === $atts['show_category'] ) {
			$category_names = wc_get_product_terms( $product_id, 'product_cat', [ 'fields' => 'names' ] );
			if ( is_wp_error( $category_names ) ) {
				$category_names = [];
			}
		}

		$design            = \HkdevShopElements\Includes\Core\ShopEngine::normalize_design_atts( $atts );
		$gallery_size      = sanitize_key( (string) $atts['gallery_image_size'] );
		$thumb_size        = sanitize_key( (string) $atts['thumb_image_size'] );
		$gallery_size      = $gallery_size ? $gallery_size : 'large';
		$thumb_size        = $thumb_size ? $thumb_size : 'thumbnail';
		$show_breadcrumb   = ( 'yes' === $atts['show_breadcrumb'] );
		$show_sale_badge   = ( 'yes' === $atts['show_sale_badge'] );
		$show_zoom         = ( 'yes' === $atts['show_zoom'] );
		$show_qty          = ( 'yes' === $atts['show_qty'] );
		$show_sku          = ( 'yes' === $atts['show_sku'] );
		$show_stock        = ( 'yes' === $atts['show_stock'] );
		$show_tabs         = ( 'yes' === $atts['show_tabs'] );
		$show_faq          = ( 'yes' === $atts['show_faq'] );
		$gallery_sticky    = ( 'yes' === $atts['gallery_sticky'] );
		$wrapper_style     = $this->design_style_attr( $design );

		ob_start();
		?>
		<?php
		$default_variation_attrs = [];
		if ( $is_variable && method_exists( $product, 'get_default_attributes' ) ) {
			foreach ( $product->get_default_attributes() as $attr_key => $attr_val ) {
				$default_variation_attrs[ 'attribute_' . sanitize_title( $attr_key ) ] = $attr_val;
			}
		}
		?>
		<div id="product-<?php echo esc_attr( $product_id ); ?>" <?php wc_product_class( 'hkdev-sp-wrapper', $product ); ?>
			style="<?php echo esc_attr( $wrapper_style ); ?>"
			data-product-type="<?php echo esc_attr( $is_variable ? 'variable' : 'simple' ); ?>"
			data-in-stock="<?php echo $is_in_stock ? 'yes' : 'no'; ?>"
			data-card-preset="<?php echo esc_attr( $design['card_preset'] ); ?>"
			data-card-theme="<?php echo esc_attr( $design['card_theme'] ); ?>"
			data-font-mode="<?php echo esc_attr( $design['font_mode'] ); ?>"
			data-gallery-sticky="<?php echo $gallery_sticky ? 'yes' : 'no'; ?>"
			data-zoom="<?php echo $show_zoom ? 'yes' : 'no'; ?>"
			data-default-attributes="<?php echo esc_attr( wp_json_encode( $default_variation_attrs ) ); ?>">

			<?php do_action( 'woocommerce_before_single_product' ); ?>

			<?php if ( $show_breadcrumb ) : ?>
			<nav class="hkdev-sp-breadcrumb">
				<a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'hkdev-shop-elements' ); ?></a> <i class="fa-solid fa-angle-right"></i>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Shop', 'hkdev-shop-elements' ); ?></a> <i class="fa-solid fa-angle-right"></i>
				<span class="current-crumb"><?php echo esc_html( $product->get_name() ); ?></span>
			</nav>
			<?php endif; ?>

			<div class="hkdev-sp-main-container">

				<!-- Gallery Section -->
				<div class="hkdev-sp-gallery">

					<?php do_action( 'woocommerce_before_single_product_summary' ); ?>

					<div class="hkdev-sp-viewport" id="hkdev-sp-viewport">
						<?php
						$off_text           = esc_html__( 'Off!', 'hkdev-shop-elements' );
						// Only show the badge when we actually know the discount.
						// Variable products show it after a variation with a discount is chosen (handled in JS).
						$show_badge         = $show_sale_badge && ( 'variable' !== $product->get_type() && $percentage > 0 );
						$display_percentage = $percentage > 0 ? $percentage . '% ' . $off_text : $off_text;
						$badge_style        = $show_badge ? '' : 'display:none;';

						// --- Product videos (multiple, with per-thumb embed data) ---
						$video_urls = self::get_product_video_urls( $product_id );

						// Show the gallery arrows only when there is more than one
						// thumbnail to navigate between (main image + gallery images
						// or main image + video). With a single image the arrows
						// have nothing to switch to, so they are omitted.
						$show_gallery_arrows = ! empty( $main_image_id ) && ( ! empty( $attachment_ids ) || ! empty( $video_urls ) );
						?>
						<?php if ( $show_sale_badge ) : ?>
						<span class="hkdev-sp-sale-badge" style="<?php echo esc_attr( $badge_style ); ?>"><?php echo esc_html( $display_percentage ); ?></span>
						<?php endif; ?>

						<?php if ( $show_zoom ) : ?>
						<button type="button" class="hkdev-sp-zoom-trigger" id="hkdev-sp-zoom-btn" title="<?php esc_attr_e( 'Zoom', 'hkdev-shop-elements' ); ?>">
							<i class="fa-solid fa-magnifying-glass-plus"></i>
						</button>
						<?php endif; ?>

						<?php if ( $show_gallery_arrows ) : ?>
						<button type="button" class="hkdev-sp-arrow prev-arrow" id="hkdev-sp-prev-img"><i class="fa-solid fa-chevron-left"></i></button>
						<button type="button" class="hkdev-sp-arrow next-arrow" id="hkdev-sp-next-img"><i class="fa-solid fa-chevron-right"></i></button>
						<?php endif; ?>

						<div class="hkdev-sp-zoom-inner" id="hkdev-sp-zoom-container">
							<img id="hkdev-sp-main-img" src="<?php echo esc_url( wp_get_attachment_image_url( $main_image_id, $gallery_size ) ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
						</div>

						<div class="hkdev-sp-video-player" id="hkdev-sp-video-player" style="display:none;"></div>
					</div>

					<div class="hkdev-sp-thumbnails">
						<?php if ( $main_image_id ) : ?>
							<div class="hkdev-sp-thumb active" data-type="image" data-full="<?php echo esc_url( wp_get_attachment_image_url( $main_image_id, $gallery_size ) ); ?>">
								<?php echo wp_get_attachment_image( $main_image_id, $thumb_size ); ?>
							</div>
						<?php endif; ?>
						<?php foreach ( $attachment_ids as $attachment_id ) : ?>
							<div class="hkdev-sp-thumb" data-type="image" data-full="<?php echo esc_url( wp_get_attachment_image_url( $attachment_id, $gallery_size ) ); ?>">
								<?php echo wp_get_attachment_image( $attachment_id, $thumb_size ); ?>
							</div>
						<?php endforeach; ?>
						<?php foreach ( $video_urls as $v_index => $v_url ) : ?>
							<?php
							$v_data = self::build_video_embed( $v_url );
							if ( empty( $v_data['type'] ) ) {
								continue;
							}
							$player_html = self::render_video_player( $v_data );
							if ( '' === $player_html ) {
								continue;
							}
							?>
						<div class="hkdev-sp-thumb hkdev-sp-video-thumb" data-type="video" data-video-embed="<?php echo esc_attr( $player_html ); ?>">
							<?php if ( ! empty( $v_data['thumbnail'] ) ) : ?>
								<img src="<?php echo esc_url( $v_data['thumbnail'] ); ?>" alt="<?php esc_attr_e( 'Product video', 'hkdev-shop-elements' ); ?>" loading="lazy">
							<?php else : ?>
								<span class="hkdev-sp-video-thumb-placeholder"><i class="fa-solid fa-film"></i></span>
							<?php endif; ?>
							<span class="hkdev-sp-video-thumb-icon"><i class="fa-solid fa-play"></i></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Details Section -->
				<div class="hkdev-sp-info-wrap">
					<h1 class="hkdev-sp-title"><?php echo esc_html( $product->get_name() ); ?></h1>

					<div class="hkdev-sp-price-box">
						<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<?php do_action( 'woocommerce_single_product_summary' ); ?>

					<?php if ( $product->is_type( 'variable' ) ) :
						$variations = $product->get_available_variations();
						foreach ( $variations as $key => $variation ) {
							$v_obj  = wc_get_product( $variation['variation_id'] );
							$v_reg  = $v_obj ? (float) $v_obj->get_regular_price() : 0;
							$v_sale = $v_obj ? (float) $v_obj->get_sale_price() : 0;
							$v_perc = 0;
							if ( $v_obj && $v_obj->is_on_sale() && $v_reg > 0 ) {
								$v_perc = round( ( ( $v_reg - $v_sale ) / $v_reg ) * 100 );
							}
							$variations[ $key ]['discount_percentage'] = $v_perc;

							// Some WooCommerce versions leave price_html empty in
							// get_available_variations(). An empty value blanked the
							// price box as soon as a variation was selected, so build
							// it from the variation object whenever it is missing.
							if ( empty( $variations[ $key ]['price_html'] ) && $v_obj ) {
								$variations[ $key ]['price_html'] = '<span class="price">' . $v_obj->get_price_html() . '</span>';
							}
						}
						$attributes = $product->get_variation_attributes();
						?>
						<div class="hkdev-sp-variable-options" data-variations='<?php echo esc_attr( wp_json_encode( $variations ) ); ?>'>
							<?php foreach ( $attributes as $attribute_name => $options ) : ?>
								<div class="hkdev-sp-variation-row" data-attribute="attribute_<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
									<span class="attr-label"><?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?>: <span class="selected-val"><?php esc_html_e( 'Select', 'hkdev-shop-elements' ); ?></span></span>
									<div class="attr-swatches">
										<?php foreach ( $options as $option ) : ?>
											<?php
											$option_label = $this->variation_option_label( $attribute_name, $option );
											?>
											<div class="hkdev-sp-swatch-item" data-value="<?php echo esc_attr( $option ); ?>" data-label="<?php echo esc_attr( $option_label ); ?>">
												<?php echo esc_html( $option_label ); ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $size_chart_url ) ) : ?>
						<div style="margin-bottom: 20px;">
							<button type="button" id="hkdev-size-chart-btn" style="background:none; border:none; color:var(--hkdev-brand-primary, #03a550); font-family:inherit; font-weight:600; cursor:pointer; text-decoration:underline; font-size:15px; padding:0; display:inline-flex; align-items:center; gap:6px;">
								<i class="fa-solid fa-ruler-combined"></i> <?php esc_html_e( 'Size Chart', 'hkdev-shop-elements' ); ?>
							</button>
						</div>
					<?php endif; ?>

					<div class="hkdev-sp-stock-notice<?php echo $show_oos_notice_initial ? ' is-visible' : ''; ?>" id="hkdev-sp-stock-notice" role="status" aria-live="polite">
						<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
						<span class="hkdev-sp-stock-notice-text"><?php esc_html_e( 'This product is currently out of stock.', 'hkdev-shop-elements' ); ?></span>
					</div>

					<!-- Quantity & Buttons -->
					<div class="hkdev-sp-action-row">
						<?php if ( $show_qty ) : ?>
						<div class="hkdev-sp-qty-control<?php echo esc_attr( $purchase_disabled_cls ); ?>">
							<button type="button" class="hkdev-sp-qty-btn minus"<?php echo $purchase_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>&minus;</button>
							<input type="number" id="hkdev-sp-qty-field" class="hkdev-sp-qty-input" value="1" min="1"<?php echo $disable_purchase_btns ? ' disabled' : ''; ?>>
							<button type="button" class="hkdev-sp-qty-btn plus"<?php echo $purchase_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>+</button>
						</div>
						<?php else : ?>
							<input type="hidden" id="hkdev-sp-qty-field" class="hkdev-sp-qty-input" value="1">
						<?php endif; ?>

						<div class="hkdev-sp-purchase-buttons">
							<button type="button" class="hkdev-sp-btn atc-btn<?php echo esc_attr( $purchase_disabled_cls ); ?>" id="hkdev-sp-add-to-cart"
									data-product-id="<?php echo esc_attr( $product_id ); ?>"
									data-variation-id="0"<?php echo $purchase_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<i class="fa-solid fa-cart-plus"></i> <?php esc_html_e( 'Add to Cart', 'hkdev-shop-elements' ); ?>
							</button>

							<button type="button" class="hkdev-sp-btn buy-now-btn <?php echo $is_in_cart ? 'checkout-active' : ''; ?><?php echo esc_attr( $purchase_disabled_cls ); ?>"
									id="hkdev-sp-buy-now"
									data-product-id="<?php echo esc_attr( $product_id ); ?>"
									data-variation-id="0"
									data-checkout-url="<?php echo esc_url( $checkout_url ); ?>"<?php echo $purchase_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<i class="fa-solid fa-bolt"></i>
								<span class="btn-text"><?php echo $is_in_cart ? esc_html__( 'Order Completed', 'hkdev-shop-elements' ) : esc_html__( 'Buy Now', 'hkdev-shop-elements' ); ?></span>
							</button>
						</div>

						<?php if ( $show_whatsapp || $show_call ) : ?>
							<div class="hkdev-sp-contact-buttons">
								<?php if ( $show_whatsapp ) : ?>
									<a class="hkdev-sp-btn whatsapp-btn" href="<?php echo esc_url( $whatsapp_link ); ?>" target="_blank" rel="noopener">
										<i class="fa-brands fa-whatsapp"></i> <?php esc_html_e( 'Order on WhatsApp', 'hkdev-shop-elements' ); ?>
									</a>
								<?php endif; ?>
								<?php if ( $show_call ) : ?>
									<a class="hkdev-sp-btn call-btn" href="<?php echo esc_url( $call_link ); ?>">
										<i class="fa-solid fa-phone"></i> <?php esc_html_e( 'Call For Order', 'hkdev-shop-elements' ); ?>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $brand_name || ! empty( $category_names ) ) : ?>
						<div class="hkdev-sp-brand-cat">
							<?php if ( $brand_name ) : ?>
								<span class="bc-item">
									<span class="bc-label"><?php esc_html_e( 'Brand', 'hkdev-shop-elements' ); ?>:</span>
									<?php if ( $brand_logo ) : ?>
										<img class="hkdev-sp-brand-logo" src="<?php echo esc_url( $brand_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>">
									<?php endif; ?>
									<span class="bc-value"><?php echo esc_html( $brand_name ); ?></span>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $category_names ) ) : ?>
								<span class="bc-item">
									<span class="bc-label"><?php esc_html_e( 'Category', 'hkdev-shop-elements' ); ?>:</span>
									<span class="bc-value"><?php echo esc_html( implode( ', ', $category_names ) ); ?></span>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $show_sku || $show_stock ) : ?>
					<div class="hkdev-sp-product-meta">
						<?php if ( $show_sku ) : ?>
						<div class="meta-row"><strong><?php esc_html_e( 'SKU', 'hkdev-shop-elements' ); ?>:</strong> <span class="sku-val"><?php echo $product->get_sku() ? esc_html( $product->get_sku() ) : 'N/A'; ?></span></div>
						<?php endif; ?>
						<?php if ( $show_stock ) : ?>
						<div class="meta-row"><strong><?php esc_html_e( 'Stock', 'hkdev-shop-elements' ); ?>:</strong> <span class="stock-val"><?php echo $product->is_in_stock() ? '<span class="in-stock-pill">' . esc_html__( 'In Stock', 'hkdev-shop-elements' ) . '</span>' : '<span class="out-stock-pill">' . esc_html__( 'Out of Stock', 'hkdev-shop-elements' ) . '</span>'; ?></span></div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $show_tabs ) : ?>
			<div class="hkdev-sp-tabs-section">
				<div class="hkdev-sp-tab-headers">
					<button class="hkdev-sp-tab-link active" data-tab="desc"><?php esc_html_e( 'Description', 'hkdev-shop-elements' ); ?></button>
					<button class="hkdev-sp-tab-link" data-tab="reviews"><?php esc_html_e( 'Reviews', 'hkdev-shop-elements' ); ?> (<?php echo esc_html( $product->get_review_count() ); ?>)</button>
				</div>
				<div id="desc" class="hkdev-sp-tab-content active">
					<div class="entry-content"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $product_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</div>
				<div id="reviews" class="hkdev-sp-tab-content"><?php comments_template(); ?></div>
			</div>
			<?php endif; ?>

			<?php if ( $show_faq ) : ?>
			<?php echo self::render_product_faqs( $product_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

			<!-- Size Chart Modal HTML -->
			<?php if ( ! empty( $size_chart_url ) ) : ?>
				<div id="hkdev-size-chart-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:999999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
					<div style="background:#fff; border-radius:12px; max-width:600px; width:90%; position:relative; box-shadow:0 25px 50px rgba(0,0,0,0.15); animation: zoomIn 0.3s ease;">
						<div style="display:flex; justify-content:space-between; align-items:center; padding:15px 25px; border-bottom:1px solid #eee;">
							<h3 style="margin:0; font-size:18px; font-family:inherit;"><?php esc_html_e( 'Size Chart', 'hkdev-shop-elements' ); ?></h3>
							<button type="button" id="hkdev-size-chart-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#888;">&times;</button>
						</div>
						<div style="padding:20px; text-align:center; overflow-y:auto; max-height:70vh;">
							<img src="<?php echo esc_url( $size_chart_url ); ?>" alt="Size Chart" style="max-width:100%; height:auto; border-radius:8px;">
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_after_single_product' ); ?>

		</div>
		<style>@keyframes zoomIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }</style>
		<?php

		// Restore defaults.
		foreach ( $wc_single_defaults as $hook ) {
			add_action( $hook[0], $hook[1], $hook[2] );
		}
		wp_reset_postdata();
		$product = $previous_product;

		return ob_get_clean();
	}

	/**
	 * Human-readable label for a variation option (taxonomy term name, not slug).
	 *
	 * @param string $attribute_name Attribute key from get_variation_attributes().
	 * @param string $option         Stored option value (slug for global attributes).
	 * @return string
	 */
	private function variation_option_label( $attribute_name, $option ) {
		$option = (string) $option;
		if ( '' === $option ) {
			return '';
		}

		if ( taxonomy_exists( $attribute_name ) ) {
			$term = get_term_by( 'slug', $option, $attribute_name );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}

		return $option;
	}

	/**
	 * AJAX add-to-cart handler.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart_handler() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		$product_id   = apply_filters( 'woocommerce_add_to_cart_product_id', absint( wp_unslash( $_POST['product_id'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$quantity     = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$variation_id = absint( wp_unslash( $_POST['variation_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// The named attributes (attribute_pa_size => m, ...) are required, otherwise
		// the cart item keeps no variation data and the order loses the customer's
		// chosen options. Derive them from the variation when the client only posts
		// a variation ID.
		$variation = [];
		if ( $variation_id ) {
			if ( ! empty( $_POST['variation'] ) && is_array( $_POST['variation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$variation = wc_clean( wp_unslash( $_POST['variation'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				$variation_product = wc_get_product( $variation_id );
				if ( $variation_product ) {
					$variation = $variation_product->get_variation_attributes();
				}
			}
		}

		$product_to_add = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
		if ( ! $product_to_add || ! $product_to_add->is_in_stock() ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'This product is out of stock.', 'hkdev-shop-elements' ),
				]
			);
		}

		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) ) {
			ob_start();
			woocommerce_mini_cart();
			$mini_cart = ob_get_clean();
			$fragments = apply_filters(
				'woocommerce_add_to_cart_fragments',
				[
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				]
			);
			wp_send_json_success(
				[
					'fragments'  => $fragments,
					'cart_hash'  => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_hash() ),
				]
			);
		} else {
			wp_send_json_error();
		}
		wp_die();
	}

	/**
	 * Track recently viewed products via cookie.
	 *
	 * @return void
	 */
	public function track_recently_viewed() {
		if ( ! is_product() || is_admin() ) {
			return;
		}
		$product_id = get_the_ID();
		if ( ! $product_id ) {
			return;
		}
		$viewed = [];
		if ( ! empty( $_COOKIE['hkdev_recently_viewed'] ) ) {
			$viewed = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['hkdev_recently_viewed'] ) ) ) ) );
		}
		$viewed = array_diff( $viewed, [ $product_id ] );
		array_unshift( $viewed, $product_id );
		$viewed = array_slice( array_values( $viewed ), 0, 8 );
		setcookie( 'hkdev_recently_viewed', implode( ',', $viewed ), time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
	}
}
