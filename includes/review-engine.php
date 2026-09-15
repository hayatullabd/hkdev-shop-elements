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
	 * @var ?Review_Engine
	 */
	private static $instance = null;

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
			'anchor'             => '',
			'heading'            => '',
			'subheading'         => '',
			'video_icon'         => 'video',
			'written_icon'       => 'list',
			'show_header'        => true,
			'show_heading'       => true,
			'show_subheading'    => true,
			'show_tabs'          => true,
			'tab_video_label'    => __( 'Video Reviews', 'hkdev-shop-elements' ),
			'tab_written_label'  => __( 'Social Proofs', 'hkdev-shop-elements' ),
			'default_tab'        => 'written',
			'show_video_name'    => true,
			'show_video_stars'   => true,
			'show_video_play'    => true,
			'show_video_overlay' => true,
			'show_card_name'     => true,
			'show_verified'      => true,
			'show_proof_stars'   => true,
			'show_modal_quote'   => true,
			'show_modal_badge'   => true,
			'show_product'       => true,
			'video_empty'        => __( 'No video reviews to show yet.', 'hkdev-shop-elements' ),
			'written_empty'      => __( 'No reviews to show yet.', 'hkdev-shop-elements' ),
			'show_filter'        => true,
			'filter_label'       => __( 'Filter by:', 'hkdev-shop-elements' ),
			'filter_default'     => 'recent',
			'filter_highest'     => __( 'Highest Rating', 'hkdev-shop-elements' ),
			'filter_recent'      => __( 'Most Recent', 'hkdev-shop-elements' ),
			'top_pick_label'     => __( 'Top Pick', 'hkdev-shop-elements' ),
			'order_button_text'  => __( 'Order Now', 'hkdev-shop-elements' ),
			'view_button_text'   => __( 'View Product Details', 'hkdev-shop-elements' ),
			'video_badge'        => __( 'Verified Customer', 'hkdev-shop-elements' ),
			'proof_badge'        => __( 'Verified Purchase', 'hkdev-shop-elements' ),
			'videos'             => [],
			'proofs'             => [],
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
		$proofs = array_values( array_filter( (array) $config['proofs'] ) );

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
								$name    = isset( $item['name'] ) ? $item['name'] : '';
								$image   = isset( $item['image'] ) ? $item['image'] : '';
								$rating  = isset( $item['rating'] ) ? (int) $item['rating'] : 5;
								$quote   = isset( $item['quote'] ) ? $item['quote'] : '';
								$product = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
								$media   = $this->video_media( isset( $item['video'] ) ? $item['video'] : '' );

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
									<?php if ( $image ) : ?>
										<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
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
									'product' => ! empty( $config['show_product'] ) ? $this->product_promo_html( $product, '', $config['view_button_text'] ) : '',
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
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
					<div class="hkdev-rv-pmodal-left">
						<img class="hkdev-rv-slider-img" src="" alt="" />
						<button type="button" class="hkdev-rv-slider-btn hkdev-rv-prev" aria-label="<?php esc_attr_e( 'Previous image', 'hkdev-shop-elements' ); ?>">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
						</button>
						<button type="button" class="hkdev-rv-slider-btn hkdev-rv-next" aria-label="<?php esc_attr_e( 'Next image', 'hkdev-shop-elements' ); ?>">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
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

		if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_\-]{11})~i', $url, $match ) ) {
			return [
				'type' => 'iframe',
				'url'  => 'https://www.youtube.com/embed/' . $match[1] . '?autoplay=1&rel=0',
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

		return [
			'type' => 'iframe',
			'url'  => $url,
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
		if ( ! $product || ! $product->is_visible() ) {
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
