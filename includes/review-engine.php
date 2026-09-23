<?php
/**
 * HKDEV Customer Reviews Engine (HKDEV Shop Elements plugin).
 *
 * Self-contained renderer for the "Hear From Our Customers" review block:
 * a Video Reviews / Social Proofs tab switcher, per-review modals (video player
 * or image slider) and an optional "Top Pick" product promo. Works with any
 * WordPress theme; the Elementor widget only maps settings to this config.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Review_Engine
 */
class Review_Engine {

	/**
	 * WP option key for plugin-wide review content (admin + shortcode + Elementor "global" mode).
	 *
	 * @var string
	 */
	const OPTION_NAME = 'hkdev_rv_settings';

	/**
	 * @var ?Review_Engine
	 */
	private static $instance = null;

	/**
	 * Register front-end shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'hkdev_customer_reviews', [ $this, 'shortcode' ] );
	}

	/**
	 * Shortcode output using global admin settings.
	 *
	 * @param array|string $atts Shortcode attributes (unused).
	 * @return string
	 */
	public function shortcode( $atts ) {
		unset( $atts );

		wp_enqueue_style( 'hkdev-elements-reviews-style' );
		wp_enqueue_script( 'hkdev-elements-reviews-js' );

		return $this->render( $this->get_global_render_config() );
	}

	/**
	 * Read stored admin settings merged with render defaults.
	 *
	 * @return array
	 */
	public function get_stored_settings() {
		$stored = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		return wp_parse_args( $stored, $this->admin_settings_defaults() );
	}

	/**
	 * Defaults for the admin settings form (content only).
	 *
	 * @return array
	 */
	public function admin_settings_defaults() {
		$render = $this->defaults();

		$render['show_header']          = 'yes';
		$render['show_tabs']            = 'yes';
		$render['show_video_name']      = 'yes';
		$render['show_video_stars']     = 'yes';
		$render['show_video_play']      = 'yes';
		$render['show_video_overlay']   = 'yes';
		$render['video_thumb_fallback'] = 'yes';
		$render['show_card_name']       = 'yes';
		$render['show_verified']        = 'yes';
		$render['show_proof_stars']     = 'yes';
		$render['show_modal_quote']     = 'yes';
		$render['show_modal_badge']     = 'yes';
		$render['show_product']         = 'yes';
		$render['show_filter']          = 'yes';

		return $render;
	}

	/**
	 * Build render config from global plugin settings.
	 *
	 * @return array
	 */
	public function get_global_render_config() {
		return $this->settings_to_render_config( $this->get_stored_settings() );
	}

	/**
	 * Convert stored settings (admin or legacy) into Review_Engine render config.
	 *
	 * @param array $settings Stored or merged settings.
	 * @return array
	 */
	public function settings_to_render_config( array $settings ) {
		$yes = static function ( $key ) use ( $settings ) {
			if ( is_bool( $settings[ $key ] ?? null ) ) {
				return (bool) $settings[ $key ];
			}

			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		$videos = [];
		if ( ! empty( $settings['videos'] ) && is_array( $settings['videos'] ) ) {
			foreach ( $settings['videos'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$image = isset( $row['image'] ) ? (string) $row['image'] : '';
				if ( '' === $image && ! empty( $row['thumbnail_id'] ) ) {
					$image = wp_get_attachment_image_url( absint( $row['thumbnail_id'] ), 'medium_large' );
					$image = $image ? $image : '';
				}

				$videos[] = [
					'name'       => isset( $row['name'] ) ? (string) $row['name'] : '',
					'image'      => $image,
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'video'      => isset( $row['video'] ) ? (string) $row['video'] : '',
					'quote'      => isset( $row['quote'] ) ? (string) $row['quote'] : '',
					'product_id' => isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0,
				];
			}
		}

		$proofs = [];
		if ( ! empty( $settings['proofs'] ) && is_array( $settings['proofs'] ) ) {
			foreach ( $settings['proofs'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$images = [];
				if ( ! empty( $row['images'] ) && is_array( $row['images'] ) ) {
					foreach ( $row['images'] as $url ) {
						if ( is_array( $url ) && ! empty( $url['url'] ) ) {
							$url = (string) $url['url'];
						}
						$url = esc_url_raw( (string) $url );
						if ( '' !== $url ) {
							$images[] = $url;
						}
					}
				} elseif ( ! empty( $row['image_ids'] ) ) {
					$ids = is_array( $row['image_ids'] ) ? $row['image_ids'] : explode( ',', (string) $row['image_ids'] );
					foreach ( $ids as $id ) {
						$id = absint( $id );
						if ( $id ) {
							$url = wp_get_attachment_image_url( $id, 'medium_large' );
							if ( $url ) {
								$images[] = $url;
							}
						}
					}
				}

				$proofs[] = [
					'name'       => isset( $row['name'] ) ? (string) $row['name'] : '',
					'images'     => $images,
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'quote'      => isset( $row['quote'] ) ? (string) $row['quote'] : '',
					'product_id' => isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0,
				];
			}
		}

		return [
			'anchor'               => isset( $settings['anchor'] ) ? sanitize_title( (string) $settings['anchor'] ) : '',
			'heading'              => isset( $settings['heading'] ) ? (string) $settings['heading'] : '',
			'subheading'           => isset( $settings['subheading'] ) ? (string) $settings['subheading'] : '',
			'video_icon'           => isset( $settings['video_icon'] ) ? (string) $settings['video_icon'] : 'video',
			'written_icon'         => isset( $settings['written_icon'] ) ? (string) $settings['written_icon'] : 'list',
			'show_header'          => $yes( 'show_header' ),
			'show_heading'         => true,
			'show_subheading'      => true,
			'show_tabs'            => $yes( 'show_tabs' ),
			'tab_video_label'      => isset( $settings['tab_video_label'] ) ? (string) $settings['tab_video_label'] : '',
			'tab_written_label'    => isset( $settings['tab_written_label'] ) ? (string) $settings['tab_written_label'] : '',
			'default_tab'          => isset( $settings['default_tab'] ) ? (string) $settings['default_tab'] : 'written',
			'show_video_name'      => $yes( 'show_video_name' ),
			'show_video_stars'     => $yes( 'show_video_stars' ),
			'show_video_play'      => $yes( 'show_video_play' ),
			'show_video_overlay'   => $yes( 'show_video_overlay' ),
			'video_thumb_fallback' => $yes( 'video_thumb_fallback' ),
			'show_card_name'       => $yes( 'show_card_name' ),
			'show_verified'        => $yes( 'show_verified' ),
			'show_proof_stars'     => $yes( 'show_proof_stars' ),
			'show_modal_quote'     => $yes( 'show_modal_quote' ),
			'show_modal_badge'     => $yes( 'show_modal_badge' ),
			'show_product'         => $yes( 'show_product' ),
			'video_empty'          => isset( $settings['video_empty'] ) ? (string) $settings['video_empty'] : '',
			'written_empty'        => isset( $settings['written_empty'] ) ? (string) $settings['written_empty'] : '',
			'show_filter'          => $yes( 'show_filter' ),
			'filter_label'         => isset( $settings['filter_label'] ) ? (string) $settings['filter_label'] : '',
			'filter_default'       => isset( $settings['filter_default'] ) ? (string) $settings['filter_default'] : 'recent',
			'filter_highest'       => isset( $settings['filter_highest'] ) ? (string) $settings['filter_highest'] : '',
			'filter_recent'        => isset( $settings['filter_recent'] ) ? (string) $settings['filter_recent'] : '',
			'top_pick_label'       => isset( $settings['top_pick_label'] ) ? (string) $settings['top_pick_label'] : '',
			'order_button_text'    => isset( $settings['order_button_text'] ) ? (string) $settings['order_button_text'] : '',
			'view_button_text'     => isset( $settings['view_button_text'] ) ? (string) $settings['view_button_text'] : '',
			'video_badge'          => isset( $settings['video_badge'] ) ? (string) $settings['video_badge'] : '',
			'proof_badge'          => isset( $settings['proof_badge'] ) ? (string) $settings['proof_badge'] : '',
			'videos'               => $videos,
			'proofs'               => $proofs,
		];
	}

	/**
	 * Build render config from Elementor widget settings (custom / per-widget content).
	 *
	 * @param array $settings Elementor widget settings array.
	 * @return array
	 */
	public function build_config_from_elementor( array $settings ) {
		$yes_no = static function ( $key, $default_on = false ) use ( $settings ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				return $default_on;
			}

			return 'yes' === $settings[ $key ];
		};

		$videos = [];
		if ( ! empty( $settings['videos'] ) && is_array( $settings['videos'] ) ) {
			foreach ( $settings['videos'] as $row ) {
				$videos[] = [
					'name'       => isset( $row['name'] ) ? $row['name'] : '',
					'image'      => ! empty( $row['thumbnail']['url'] ) ? $row['thumbnail']['url'] : '',
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'video'      => isset( $row['video'] ) ? $row['video'] : '',
					'quote'      => isset( $row['quote'] ) ? $row['quote'] : '',
					'product_id' => isset( $row['product'] ) ? absint( $row['product'] ) : 0,
				];
			}
		}

		$proofs = [];
		if ( ! empty( $settings['proofs'] ) && is_array( $settings['proofs'] ) ) {
			foreach ( $settings['proofs'] as $row ) {
				$images = [];
				if ( ! empty( $row['images'] ) && is_array( $row['images'] ) ) {
					foreach ( $row['images'] as $image ) {
						if ( ! empty( $image['url'] ) ) {
							$images[] = $image['url'];
						}
					}
				}

				$proofs[] = [
					'name'       => isset( $row['name'] ) ? $row['name'] : '',
					'images'     => $images,
					'rating'     => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
					'quote'      => isset( $row['quote'] ) ? $row['quote'] : '',
					'product_id' => isset( $row['product'] ) ? absint( $row['product'] ) : 0,
				];
			}
		}

		return [
			'anchor'               => isset( $settings['anchor'] ) ? sanitize_title( $settings['anchor'] ) : '',
			'heading'              => isset( $settings['heading'] ) ? $settings['heading'] : '',
			'subheading'           => isset( $settings['subheading'] ) ? $settings['subheading'] : '',
			'show_header'          => $yes_no( 'show_header', true ),
			'show_heading'         => true,
			'show_subheading'      => true,
			'show_tabs'            => $yes_no( 'show_tabs', true ),
			'tab_video_label'      => isset( $settings['tab_video_label'] ) ? $settings['tab_video_label'] : '',
			'tab_written_label'    => isset( $settings['tab_written_label'] ) ? $settings['tab_written_label'] : '',
			'default_tab'          => isset( $settings['default_tab'] ) ? $settings['default_tab'] : 'written',
			'show_video_name'      => $yes_no( 'show_video_name', true ),
			'show_video_stars'     => $yes_no( 'show_video_stars', true ),
			'show_video_play'      => $yes_no( 'show_video_play', true ),
			'show_video_overlay'   => $yes_no( 'show_video_overlay', true ),
			'video_thumb_fallback' => $yes_no( 'video_thumb_fallback', true ),
			'show_card_name'       => $yes_no( 'show_card_name', true ),
			'show_verified'        => $yes_no( 'show_verified', true ),
			'show_proof_stars'     => $yes_no( 'show_proof_stars', true ),
			'show_modal_quote'     => $yes_no( 'show_modal_quote', true ),
			'show_modal_badge'     => $yes_no( 'show_modal_badge', true ),
			'show_product'         => $yes_no( 'show_product', true ),
			'video_empty'          => isset( $settings['video_empty'] ) ? $settings['video_empty'] : '',
			'written_empty'        => isset( $settings['written_empty'] ) ? $settings['written_empty'] : '',
			'show_filter'          => $yes_no( 'show_filter', true ),
			'filter_label'         => isset( $settings['filter_label'] ) ? $settings['filter_label'] : '',
			'filter_default'       => isset( $settings['filter_default'] ) ? $settings['filter_default'] : 'recent',
			'filter_highest'       => isset( $settings['filter_highest'] ) ? $settings['filter_highest'] : '',
			'filter_recent'        => isset( $settings['filter_recent'] ) ? $settings['filter_recent'] : '',
			'top_pick_label'       => isset( $settings['top_pick_label'] ) ? $settings['top_pick_label'] : '',
			'order_button_text'    => isset( $settings['order_button_text'] ) ? $settings['order_button_text'] : '',
			'view_button_text'     => isset( $settings['view_button_text'] ) ? $settings['view_button_text'] : '',
			'video_badge'          => isset( $settings['video_badge'] ) ? $settings['video_badge'] : '',
			'proof_badge'          => isset( $settings['proof_badge'] ) ? $settings['proof_badge'] : '',
			'videos'               => $videos,
			'proofs'               => $proofs,
		];
	}

	/**
	 * Singleton.
	 *
	 * @return Review_Engine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Published products, id => name, for the Elementor product selectors.
	 *
	 * @return array
	 */
	public static function product_options() {
		$options = [];

		if ( ! function_exists( 'wc_get_products' ) ) {
			return $options;
		}

		$products = wc_get_products(
			[
				'limit'   => 200,
				'status'  => 'publish',
				'orderby' => 'title',
				'order'   => 'ASC',
			]
		);

		foreach ( $products as $product ) {
			$options[ $product->get_id() ] = $product->get_name();
		}

		return $options;
	}

	/**
	 * Default render configuration.
	 *
	 * @return array
	 */
	public function defaults() {
		return [
			'anchor'               => '',
			'heading'              => '',
			'subheading'           => '',
			'video_icon'           => 'video',
			'written_icon'         => 'list',
			'show_header'          => true,
			'show_heading'         => true,
			'show_subheading'      => true,
			'show_tabs'            => true,
			'tab_video_label'      => __( 'Video Reviews', 'hkdev-shop-elements' ),
			'tab_written_label'    => __( 'Social Proofs', 'hkdev-shop-elements' ),
			'default_tab'          => 'written',
			'show_video_name'      => true,
			'show_video_stars'     => true,
			'show_video_play'      => true,
			'show_video_overlay'   => true,
			'video_thumb_fallback' => true,
			'show_card_name'       => true,
			'show_verified'        => true,
			'show_proof_stars'     => true,
			'show_modal_quote'     => true,
			'show_modal_badge'     => true,
			'show_product'         => true,
			'video_empty'          => __( 'No video reviews to show yet.', 'hkdev-shop-elements' ),
			'written_empty'        => __( 'No reviews to show yet.', 'hkdev-shop-elements' ),
			'show_filter'          => true,
			'filter_label'         => __( 'Filter by:', 'hkdev-shop-elements' ),
			'filter_default'       => 'recent',
			'filter_highest'       => __( 'Highest Rating', 'hkdev-shop-elements' ),
			'filter_recent'        => __( 'Most Recent', 'hkdev-shop-elements' ),
			'top_pick_label'       => __( 'Top Pick', 'hkdev-shop-elements' ),
			'order_button_text'    => __( 'Order Now', 'hkdev-shop-elements' ),
			'view_button_text'     => __( 'View Product Details', 'hkdev-shop-elements' ),
			'video_badge'          => __( 'Verified Customer', 'hkdev-shop-elements' ),
			'proof_badge'          => __( 'Verified Purchase', 'hkdev-shop-elements' ),
			'videos'               => [],
			'proofs'               => [],
		];
	}

	/**
	 * Render the reviews block.
	 *
	 * @param array $config Render configuration.
	 * @return string
	 */
	public function render( $config = [] ) {
		$config = wp_parse_args( $config, $this->defaults() );

		$videos = array_values( array_filter( (array) $config['videos'] ) );
		$proofs = array_values(
			array_filter(
				(array) $config['proofs'],
				static function ( $row ) {
					if ( ! is_array( $row ) ) {
						return false;
					}

					$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
					$quote = isset( $row['quote'] ) ? trim( (string) $row['quote'] ) : '';
					$images = isset( $row['images'] ) && is_array( $row['images'] ) ? array_filter( $row['images'] ) : [];

					if ( ! empty( $images ) || '' !== $name || '' !== $quote ) {
						return true;
					}

					if ( ! empty( $row['image_ids'] ) ) {
						return true;
					}

					return false;
				}
			)
		);

		$subheading = ! empty( $config['show_subheading'] ) ? $this->allow_inline_html( $config['subheading'] ) : '';
		$heading    = ! empty( $config['show_heading'] ) ? trim( (string) $config['heading'] ) : '';

		$has_video_tab = ! empty( $videos );
		$has_proof_tab = ! empty( $proofs );

		// Fall back to whichever tab has content when the default one is empty.
		$active = ( 'video' === $config['default_tab'] ) ? 'video' : 'written';
		if ( 'video' === $active && ! $has_video_tab && $has_proof_tab ) {
			$active = 'written';
		} elseif ( 'written' === $active && ! $has_proof_tab && $has_video_tab ) {
			$active = 'video';
		}

		$show_tabs = ! empty( $config['show_tabs'] ) && $has_video_tab && $has_proof_tab;
		$show_head = ! empty( $config['show_header'] ) && ( '' !== $heading || '' !== $subheading );

		$video_json = [];
		$proof_json = [];

		ob_start();
		?>
		<section class="hkdev-rv"<?php echo $config['anchor'] ? ' id="' . esc_attr( $config['anchor'] ) . '"' : ''; ?>>
			<div class="hkdev-rv-container">

				<?php if ( $show_head ) : ?>
					<div class="hkdev-rv-header">
						<?php if ( '' !== $heading ) : ?>
							<h2 class="hkdev-rv-title"><?php echo esc_html( $heading ); ?></h2>
						<?php endif; ?>
						<?php if ( '' !== $subheading ) : ?>
							<p class="hkdev-rv-subtitle"><?php echo wp_kses_post( $subheading ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $show_tabs ) : ?>
					<div class="hkdev-rv-tabs">
						<div class="hkdev-rv-tab-list" role="tablist">
							<button type="button" class="hkdev-rv-tab-btn<?php echo 'video' === $active ? ' is-active' : ''; ?>" data-tab="video" role="tab" aria-selected="<?php echo 'video' === $active ? 'true' : 'false'; ?>">
								<?php $this->icon( $config['video_icon'] ); ?>
								<?php echo esc_html( $config['tab_video_label'] ); ?>
							</button>
							<button type="button" class="hkdev-rv-tab-btn<?php echo 'written' === $active ? ' is-active' : ''; ?>" data-tab="written" role="tab" aria-selected="<?php echo 'written' === $active ? 'true' : 'false'; ?>">
								<?php $this->icon( $config['written_icon'] ); ?>
								<?php echo esc_html( $config['tab_written_label'] ); ?>
							</button>
						</div>
					</div>
				<?php endif; ?>

				<div class="hkdev-rv-panel<?php echo 'video' === $active ? ' is-active' : ''; ?>" data-panel="video" role="tabpanel">
					<?php if ( $has_video_tab ) : ?>
						<div class="hkdev-rv-video-grid">
							<?php
							foreach ( $videos as $index => $item ) :
								$name      = isset( $item['name'] ) ? $item['name'] : '';
								$image     = isset( $item['image'] ) ? $item['image'] : '';
								$rating    = isset( $item['rating'] ) ? (int) $item['rating'] : 5;
								$quote     = isset( $item['quote'] ) ? $item['quote'] : '';
								$product   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
								$video_url = isset( $item['video'] ) ? $item['video'] : '';
								$media     = $this->video_media( $video_url );

								// No thumbnail of its own: fall back to the YouTube poster.
								$thumb          = $image;
								$thumb_fallback = '';

								if ( '' === $thumb && ! empty( $config['video_thumb_fallback'] ) ) {
									$youtube_id = Video_Engine::youtube_id( $video_url );

									if ( '' !== $youtube_id ) {
										$thumb          = 'https://i.ytimg.com/vi/' . $youtube_id . '/maxresdefault.jpg';
										$thumb_fallback = 'https://i.ytimg.com/vi/' . $youtube_id . '/hqdefault.jpg';
									}
								}

								$video_json[] = [
									'name'    => ! empty( $config['show_video_name'] ) ? (string) $name : '',
									'stars'   => ! empty( $config['show_video_stars'] ) ? $this->stars_html( $rating ) : '',
									'media'   => $media,
									'quote'   => ! empty( $config['show_modal_quote'] ) ? (string) $quote : '',
									'badge'   => ! empty( $config['show_modal_badge'] ) ? (string) $config['video_badge'] : '',
									'product' => ! empty( $config['show_product'] ) ? $this->product_promo_html( $product, $config['top_pick_label'], $config['order_button_text'] ) : '',
								];
								?>
								<div class="hkdev-rv-vcard" data-open-video="<?php echo esc_attr( $index ); ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr( $name ); ?>">
									<?php if ( $thumb ) : ?>
										<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" data-thumb-fallback="<?php echo esc_url( $thumb_fallback ); ?>" />
									<?php endif; ?>
									<?php if ( ! empty( $config['show_video_overlay'] ) ) : ?>
										<div class="hkdev-rv-voverlay" aria-hidden="true"></div>
									<?php endif; ?>
									<?php if ( ! empty( $config['show_video_play'] ) ) : ?>
										<div class="hkdev-rv-play" aria-hidden="true">
											<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $config['show_video_name'] ) || ! empty( $config['show_video_stars'] ) ) : ?>
										<div class="hkdev-rv-vinfo">
											<?php if ( ! empty( $config['show_video_name'] ) && '' !== $name ) : ?>
												<div class="hkdev-rv-vname"><?php echo esc_html( $name ); ?></div>
											<?php endif; ?>
											<?php if ( ! empty( $config['show_video_stars'] ) ) : ?>
												<div class="hkdev-rv-vtag"><?php echo $this->stars_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="hkdev-rv-empty"><?php echo esc_html( $config['video_empty'] ); ?></p>
					<?php endif; ?>
				</div>

				<div class="hkdev-rv-panel<?php echo 'written' === $active ? ' is-active' : ''; ?>" data-panel="written" role="tabpanel">
					<?php if ( ! empty( $config['show_filter'] ) && $has_proof_tab ) : ?>
						<div class="hkdev-rv-topbar">
							<div class="hkdev-rv-filter">
								<span class="hkdev-rv-filter-label"><?php echo esc_html( $config['filter_label'] ); ?></span>
								<select class="hkdev-rv-filter-select">
									<option value="highest"<?php echo 'highest' === $config['filter_default'] ? ' selected' : ''; ?>><?php echo esc_html( $config['filter_highest'] ); ?></option>
									<option value="recent"<?php echo 'recent' === $config['filter_default'] ? ' selected' : ''; ?>><?php echo esc_html( $config['filter_recent'] ); ?></option>
								</select>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $has_proof_tab ) : ?>
						<div class="hkdev-rv-proof-grid" data-sort="<?php echo esc_attr( $config['filter_default'] ); ?>">
							<?php
							foreach ( $proofs as $index => $item ) :
								$name    = isset( $item['name'] ) ? $item['name'] : '';
								$images  = isset( $item['images'] ) ? array_values( array_filter( (array) $item['images'] ) ) : [];
								$rating  = isset( $item['rating'] ) ? (int) $item['rating'] : 5;
								$quote   = isset( $item['quote'] ) ? $item['quote'] : '';
								$product = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
								$cover   = ! empty( $images ) ? $images[0] : '';

								$proof_json[] = [
									'name'    => ! empty( $config['show_card_name'] ) ? (string) $name : '',
									'stars'   => ! empty( $config['show_proof_stars'] ) ? $this->stars_html( $rating ) : '',
									'images'  => $images,
									'quote'   => ! empty( $config['show_modal_quote'] ) ? (string) $quote : '',
									'badge'   => ! empty( $config['show_modal_badge'] ) ? (string) $config['proof_badge'] : '',
									'product' => ! empty( $config['show_product'] ) ? $this->product_promo_html( $product, (string) $config['top_pick_label'], $config['view_button_text'] ) : '',
								];
								?>
								<div class="hkdev-rv-pcard" data-open-proof="<?php echo esc_attr( $index ); ?>" data-rating="<?php echo esc_attr( $rating ); ?>" data-order="<?php echo esc_attr( $index ); ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr( $name ); ?>">
									<?php if ( $cover ) : ?>
										<div class="hkdev-rv-pimg-wrap">
											<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
										</div>
									<?php endif; ?>
									<div class="hkdev-rv-pbody">
										<div class="hkdev-rv-pheader">
											<?php if ( ! empty( $config['show_card_name'] ) && '' !== $name ) : ?>
												<span class="hkdev-rv-pname"><?php echo esc_html( $name ); ?></span>
											<?php endif; ?>
											<?php if ( ! empty( $config['show_verified'] ) ) : ?>
												<span class="hkdev-rv-pverified">
													<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
													<?php esc_html_e( 'Verified', 'hkdev-shop-elements' ); ?>
												</span>
											<?php endif; ?>
										</div>
										<?php if ( ! empty( $config['show_proof_stars'] ) ) : ?>
											<div class="hkdev-rv-pstars"><?php echo $this->stars_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="hkdev-rv-empty"><?php echo esc_html( $config['written_empty'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<script type="application/json" class="hkdev-rv-json"><?php
				echo wp_json_encode(
					[
						'videos' => $video_json,
						'proofs' => $proof_json,
					],
					JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?></script>

			<div class="hkdev-rv-vmodal" hidden>
				<div class="hkdev-rv-vmodal-content">
					<button type="button" class="hkdev-rv-modal-close" aria-label="<?php esc_attr_e( 'Close video', 'hkdev-shop-elements' ); ?>">
						<svg class="hkdev-rv-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
					<div class="hkdev-rv-vmodal-media"></div>
					<div class="hkdev-rv-vmodal-details">
						<div class="hkdev-rv-vmodal-name"></div>
						<div class="hkdev-rv-vmodal-meta">
							<span class="hkdev-rv-vmodal-stars"></span>
							<span class="hkdev-rv-vmodal-badge"></span>
						</div>
						<div class="hkdev-rv-vmodal-quote"></div>
						<div class="hkdev-rv-vmodal-product"></div>
					</div>
				</div>
			</div>

			<div class="hkdev-rv-pmodal" hidden>
				<div class="hkdev-rv-pmodal-content">
					<button type="button" class="hkdev-rv-pmodal-close" aria-label="<?php esc_attr_e( 'Close review', 'hkdev-shop-elements' ); ?>">
						<svg class="hkdev-rv-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
					<div class="hkdev-rv-pmodal-left">
						<img class="hkdev-rv-slider-img" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="" />
						<button type="button" class="hkdev-rv-slider-btn hkdev-rv-prev" aria-label="<?php esc_attr_e( 'Previous image', 'hkdev-shop-elements' ); ?>">
							<svg class="hkdev-rv-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button type="button" class="hkdev-rv-slider-btn hkdev-rv-next" aria-label="<?php esc_attr_e( 'Next image', 'hkdev-shop-elements' ); ?>">
							<svg class="hkdev-rv-control-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
						</button>
						<div class="hkdev-rv-slider-counter"></div>
					</div>
					<div class="hkdev-rv-pmodal-right">
						<div class="hkdev-rv-pmodal-name-row">
							<div class="hkdev-rv-pmodal-name"></div>
							<span class="hkdev-rv-pmodal-badge"></span>
						</div>
						<div class="hkdev-rv-pmodal-stars"></div>
						<div class="hkdev-rv-pmodal-quote"></div>
						<div class="hkdev-rv-pmodal-product"></div>
					</div>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inline tab icon markup.
	 *
	 * @param string $name Icon slug (video, list, play, star).
	 * @return void
	 */
	private function icon( $name ) {
		switch ( $name ) {
			case 'list':
				echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>';
				break;
			case 'play':
				echo '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
				break;
			case 'star':
				echo '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg>';
				break;
			case 'video':
			default:
				echo '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
				break;
		}
	}

	/**
	 * Five-star markup with the given number of filled stars.
	 *
	 * @param int $rating Rating 0-5.
	 * @return string
	 */
	private function stars_html( $rating ) {
		$rating = max( 0, min( 5, (int) $rating ) );

		$html = '<span class="hkdev-rv-stars" aria-label="' . esc_attr( sprintf( /* translators: %d: rating out of five. */ __( '%d out of 5 stars', 'hkdev-shop-elements' ), $rating ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$html .= '<span class="hkdev-rv-star' . ( $i <= $rating ? ' is-on' : '' ) . '" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg></span>';
		}
		$html .= '</span>';

		return $html;
	}

	/**
	 * Turn a review video URL into a playable media descriptor.
	 *
	 * YouTube links are normalised to a proper embed URL with the shared
	 * Video_Engine helpers; the iframe that plays them also carries a referrer
	 * policy (assets/js/reviews.js), which is what keeps YouTube from replying
	 * with "Error 153: Video player configuration error".
	 *
	 * @param string $url YouTube / Vimeo / direct file URL.
	 * @return array {type: iframe|video|none, url: string}
	 */
	private function video_media( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return [
				'type' => 'none',
				'url'  => '',
			];
		}

		$youtube_id = Video_Engine::youtube_id( $url );
		if ( '' !== $youtube_id ) {
			return [
				'type' => 'iframe',
				'url'  => Video_Engine::youtube_embed_url( $youtube_id ),
			];
		}

		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~i', $url, $match ) ) {
			return [
				'type' => 'iframe',
				'url'  => 'https://player.vimeo.com/video/' . $match[1] . '?autoplay=1',
			];
		}

		if ( preg_match( '~\.(mp4|webm|ogv|ogg|mov|m4v)(\?.*)?$~i', $url ) ) {
			return [
				'type' => 'video',
				'url'  => $url,
			];
		}

		// Anything else has to be a real URL, otherwise the modal would embed a
		// broken frame instead of simply showing no player.
		if ( preg_match( '~^https?://~i', $url ) ) {
			return [
				'type' => 'iframe',
				'url'  => $url,
			];
		}

		return [
			'type' => 'none',
			'url'  => '',
		];
	}

	/**
	 * "Top Pick" product promo markup (product image, title, price, CTA).
	 *
	 * @param int    $product_id  WooCommerce product id.
	 * @param string $top_label   Optional ribbon label (empty to hide).
	 * @param string $button_text CTA label.
	 * @return string
	 */
	private function product_promo_html( $product_id, $top_label, $button_text ) {
		if ( empty( $product_id ) || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			if ( $parent ) {
				$product = $parent;
			}
		}

		// Admin/Elementor picks may include catalog-hidden products; still show when published.
		if ( 'publish' !== $product->get_status() ) {
			return '';
		}

		$image = $product->get_image( 'woocommerce_thumbnail', [ 'loading' => 'lazy' ] );
		$title = $product->get_name();
		$price = $product->get_price_html();
		$url   = $product->get_permalink();
		$count = (int) $product->get_review_count();

		ob_start();
		?>
		<div class="hkdev-rv-product">
			<?php if ( '' !== trim( (string) $top_label ) ) : ?>
				<div class="hkdev-rv-toppick"><?php echo esc_html( $top_label ); ?></div>
			<?php endif; ?>
			<div class="hkdev-rv-prod-row">
				<div class="hkdev-rv-prod-img"><?php echo wp_kses_post( $image ); ?></div>
				<div class="hkdev-rv-prod-info">
					<div class="hkdev-rv-prod-title"><?php echo esc_html( $title ); ?></div>
					<div class="hkdev-rv-prod-price"><?php echo wp_kses_post( $price ); ?></div>
					<div class="hkdev-rv-prod-meta">
						<?php echo $this->stars_html( (int) round( (float) $product->get_average_rating() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( sprintf( /* translators: %d: review count. */ _n( '(%d review)', '(%d reviews)', $count, 'hkdev-shop-elements' ), $count ) ); ?></span>
					</div>
				</div>
			</div>
			<a href="<?php echo esc_url( $url ); ?>" class="hkdev-rv-order-btn">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
				<?php echo esc_html( $button_text ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Keep only the harmless inline tags in a subtitle.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function allow_inline_html( $text ) {
		$text = (string) $text;
		if ( '' === trim( $text ) ) {
			return '';
		}

		return wp_kses(
			$text,
			[
				'strong' => [],
				'b'      => [],
				'em'     => [],
				'i'      => [],
				'br'     => [],
				'span'   => [ 'class' => [] ],
			]
		);
	}
}
