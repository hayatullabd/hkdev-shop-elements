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

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Single_Product_Engine
 */
class Single_Product_Engine {

	const AJAX_ACTION  = 'hkdev_elements_ajax_add_to_cart';
	const NONCE_ACTION = 'hkdev_elements_add_to_cart';

	/**
	 * @var ?Single_Product_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Single_Product_Engine
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
	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_add_to_cart_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_add_to_cart_handler' ] );
		add_action( 'template_redirect', [ $this, 'track_recently_viewed' ], 20 );
	}

	/**
	 * Render the custom single product layout.
	 *
	 * @param array $atts Widget/shortcode attributes.
	 * @return string
	 */
	public function custom_single_product_shortcode( $atts = [] ) {
		if ( is_admin() ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'id'            => '',
				'phone'         => '',
				'whatsapp'      => '',
				'show_whatsapp' => 'yes',
				'show_call'     => 'yes',
				'show_brand'    => 'yes',
				'show_category' => 'yes',
			],
			$atts,
			'hkdev_single_product'
		);

		global $product, $post;

		if ( ! empty( $atts['id'] ) ) {
			$product_id = absint( $atts['id'] );
			$product    = wc_get_product( $product_id );
		} elseif ( is_product() && is_a( $product, 'WC_Product' ) ) {
			$product_id = $product->get_id();
		} else {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );
		}

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return '<div style="text-align:center; padding: 60px; color: #e5533d; font-family: \'Hind Siliguri\', sans-serif; background: #fff; border-radius: 12px; border: 1px solid #eee;">' . esc_html__( 'Product not found.', 'hkdev-shop-elements' ) . '</div>';
		}

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

		ob_start();
		?>
		<div id="product-<?php echo esc_attr( $product_id ); ?>" <?php wc_product_class( 'hkdev-sp-wrapper', $product ); ?>>

			<?php do_action( 'woocommerce_before_single_product' ); ?>

			<nav class="hkdev-sp-breadcrumb">
				<a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'hkdev-shop-elements' ); ?></a> <i class="fa-solid fa-angle-right"></i>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Shop', 'hkdev-shop-elements' ); ?></a> <i class="fa-solid fa-angle-right"></i>
				<span class="current-crumb"><?php echo esc_html( $product->get_name() ); ?></span>
			</nav>

			<div class="hkdev-sp-main-container">

				<!-- Gallery Section -->
				<div class="hkdev-sp-gallery">

					<?php do_action( 'woocommerce_before_single_product_summary' ); ?>

					<div class="hkdev-sp-viewport" id="hkdev-sp-viewport">
						<?php
						$off_text           = esc_html__( 'Off!', 'hkdev-shop-elements' );
						// Only show the badge when we actually know the discount.
						// Variable products show it after a variation with a discount is chosen (handled in JS).
						$show_badge         = ( 'variable' !== $product->get_type() && $percentage > 0 );
						$display_percentage = $percentage > 0 ? $percentage . '% ' . $off_text : $off_text;
						$badge_style        = $show_badge ? '' : 'display:none;';
						?>
						<span class="hkdev-sp-sale-badge" style="<?php echo esc_attr( $badge_style ); ?>"><?php echo esc_html( $display_percentage ); ?></span>

						<button type="button" class="hkdev-sp-zoom-trigger" id="hkdev-sp-zoom-btn" title="<?php esc_attr_e( 'Zoom', 'hkdev-shop-elements' ); ?>">
							<i class="fa-solid fa-magnifying-glass-plus"></i>
						</button>

						<button type="button" class="hkdev-sp-arrow prev-arrow" id="hkdev-sp-prev-img"><i class="fa-solid fa-chevron-left"></i></button>
						<button type="button" class="hkdev-sp-arrow next-arrow" id="hkdev-sp-next-img"><i class="fa-solid fa-chevron-right"></i></button>

						<div class="hkdev-sp-zoom-inner" id="hkdev-sp-zoom-container">
							<img id="hkdev-sp-main-img" src="<?php echo esc_url( wp_get_attachment_image_url( $main_image_id, 'large' ) ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
						</div>
					</div>

					<div class="hkdev-sp-thumbnails">
						<?php if ( $main_image_id ) : ?>
							<div class="hkdev-sp-thumb active" data-full="<?php echo esc_url( wp_get_attachment_image_url( $main_image_id, 'large' ) ); ?>">
								<?php echo wp_get_attachment_image( $main_image_id, 'thumbnail' ); ?>
							</div>
						<?php endif; ?>
						<?php foreach ( $attachment_ids as $attachment_id ) : ?>
							<div class="hkdev-sp-thumb" data-full="<?php echo esc_url( wp_get_attachment_image_url( $attachment_id, 'large' ) ); ?>">
								<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
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
							$v_reg  = (float) $v_obj->get_regular_price();
							$v_sale = (float) $v_obj->get_sale_price();
							$v_perc = 0;
							if ( $v_obj->is_on_sale() && $v_reg > 0 ) {
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
											<div class="hkdev-sp-swatch-item" data-value="<?php echo esc_attr( $option ); ?>">
												<?php echo esc_html( $option ); ?>
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

					<!-- Quantity & Buttons -->
					<div class="hkdev-sp-action-row">
						<div class="hkdev-sp-qty-control">
							<button type="button" class="hkdev-sp-qty-btn minus">&minus;</button>
							<input type="number" id="hkdev-sp-qty-field" class="hkdev-sp-qty-input" value="1" min="1">
							<button type="button" class="hkdev-sp-qty-btn plus">+</button>
						</div>

						<div class="hkdev-sp-purchase-buttons">
							<button type="button" class="hkdev-sp-btn atc-btn" id="hkdev-sp-add-to-cart"
									data-product-id="<?php echo esc_attr( $product_id ); ?>"
									data-variation-id="0">
								<i class="fa-solid fa-cart-plus"></i> <?php esc_html_e( 'Add to Cart', 'hkdev-shop-elements' ); ?>
							</button>

							<button type="button" class="hkdev-sp-btn buy-now-btn <?php echo $is_in_cart ? 'checkout-active' : ''; ?>"
									id="hkdev-sp-buy-now"
									data-product-id="<?php echo esc_attr( $product_id ); ?>"
									data-variation-id="0"
									data-checkout-url="<?php echo esc_url( $checkout_url ); ?>">
								<i class="fa-solid fa-bolt"></i>
								<span class="btn-text"><?php echo $is_in_cart ? esc_html__( 'Order Completed', 'hkdev-shop-elements' ) : esc_html__( 'Buy Now', 'hkdev-shop-elements' ); ?></span>
							</button>

							<?php if ( class_exists( Wishlist_Engine::class ) ) : ?>
								<?php echo Wishlist_Engine::instance()->button_html( $product_id, [ 'style' => 'button' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
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

					<div class="hkdev-sp-product-meta">
						<div class="meta-row"><strong><?php esc_html_e( 'SKU', 'hkdev-shop-elements' ); ?>:</strong> <span class="sku-val"><?php echo $product->get_sku() ? esc_html( $product->get_sku() ) : 'N/A'; ?></span></div>
						<div class="meta-row"><strong><?php esc_html_e( 'Stock', 'hkdev-shop-elements' ); ?>:</strong> <span class="stock-val"><?php echo $product->is_in_stock() ? '<span class="in-stock-pill">' . esc_html__( 'In Stock', 'hkdev-shop-elements' ) . '</span>' : '<span class="out-stock-pill">' . esc_html__( 'Out of Stock', 'hkdev-shop-elements' ) . '</span>'; ?></span></div>
					</div>
				</div>
			</div>

			<!-- Tabs -->
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

			<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

			<!-- Size Chart Modal HTML -->
			<?php if ( ! empty( $size_chart_url ) ) : ?>
				<div id="hkdev-size-chart-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:999999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
					<div style="background:#fff; border-radius:12px; max-width:600px; width:90%; position:relative; box-shadow:0 25px 50px rgba(0,0,0,0.15); animation: zoomIn 0.3s ease;">
						<div style="display:flex; justify-content:space-between; align-items:center; padding:15px 25px; border-bottom:1px solid #eee;">
							<h3 style="margin:0; font-size:18px; font-family:'Hind Siliguri', sans-serif;"><?php esc_html_e( 'Size Chart', 'hkdev-shop-elements' ); ?></h3>
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

		return ob_get_clean();
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
