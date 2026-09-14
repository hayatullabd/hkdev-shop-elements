<?php
/**
 * HKDEV Cart Engine (HKDEV Shop Elements plugin).
 *
 * Brand-matched custom AJAX cart with 100% native WooCommerce hooks for fees
 * (BOGO), coupons, shipping. Self-contained – works with ANY theme + Elementor
 * + WooCommerce. Cart display settings are plugin constants (defaults mirror
 * the hkdev-shop theme).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---- Cart display settings (theme-independent). 'yes'/'no' flags. ----
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_ITEM_IMAGE' ) ) { define( 'HKDEV_ELEMENTS_SHOW_ITEM_IMAGE', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_ITEM_PRICE' ) ) { define( 'HKDEV_ELEMENTS_SHOW_ITEM_PRICE', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_ITEM_SUBTOTAL' ) ) { define( 'HKDEV_ELEMENTS_SHOW_ITEM_SUBTOTAL', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_REMOVE_BTN' ) ) { define( 'HKDEV_ELEMENTS_SHOW_REMOVE_BTN', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_COUPON_FORM' ) ) { define( 'HKDEV_ELEMENTS_SHOW_COUPON_FORM', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_CART_SUBTOTAL' ) ) { define( 'HKDEV_ELEMENTS_SHOW_CART_SUBTOTAL', 'yes' ); }
if ( ! defined( 'HKDEV_ELEMENTS_SHOW_SHIPPING' ) ) { define( 'HKDEV_ELEMENTS_SHOW_SHIPPING', 'yes' ); }

/**
 * Class Cart_Engine
 */
class Cart_Engine {

	const AJAX_ACTION  = 'hkdev_elements_update_cart_ajax';
	const NONCE_ACTION = 'hkdev_elements_cart_update';

	/**
	 * @var ?Cart_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Cart_Engine
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
		// Replace the WooCommerce default "Proceed to Checkout" button with the
		// brand-matched one, natively (no duplicate button when hooks fire).
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'native_custom_checkout_button' ], 20 );

		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_cart_update_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_cart_update_handler' ] );
	}

	/**
	 * Main cart shortcode/widget renderer.
	 *
	 * @return string
	 */
	public function custom_cart_shortcode() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 'WooCommerce plugin is not active.';
		}
		if ( is_admin() ) {
			return '';
		}

		ob_start();

		if ( WC()->cart->is_empty() ) {
			?>
			<div class="hkdev-cart-empty-wrap">
				<i class="fa-solid fa-cart-arrow-down" style="font-size: 60px; color: #b7c6be; margin-bottom: 25px;"></i>
				<h3><?php esc_html_e( 'Your cart is empty', 'hkdev-shop-elements' ); ?></h3>
				<p><?php esc_html_e( 'Browse our products and add to cart.', 'hkdev-shop-elements' ); ?></p>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="hkdev-cart-primary-btn" style="display: inline-block; width: auto; padding: 15px 40px;"><?php esc_html_e( 'Start Shopping', 'hkdev-shop-elements' ); ?></a>
			</div>
			<?php
			return ob_get_clean();
		}

		// Recalculate totals & fees before generating the HTML (BOGO etc.).
		WC()->cart->calculate_totals();
		?>

		<div class="hkdev-cart-container" id="hkdev-cart-root">

			<?php do_action( 'woocommerce_before_cart' ); ?>

			<div class="hkdev-cart-header">
				<h2><?php esc_html_e( 'Shopping Cart', 'hkdev-shop-elements' ); ?></h2>
				<p><?php esc_html_e( 'Selected Products', 'hkdev-shop-elements' ); ?></p>
			</div>

			<div class="hkdev-cart-grid">
				<div class="hkdev-cart-items-column">
					<div class="hkdev-cart-card">
						<div id="hkdev-cart-items-area">
							<?php echo $this->get_cart_items_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_COUPON_FORM && wc_coupons_enabled() ) : ?>
						<div class="hkdev-cart-coupon-wrap">
							<div class="coupon-box">
								<input type="text" id="hkdev-coupon-input" placeholder="<?php esc_attr_e( 'Coupon Code', 'hkdev-shop-elements' ); ?>">
								<button type="button" id="hkdev-apply-coupon-btn"><?php esc_html_e( 'Apply', 'hkdev-shop-elements' ); ?></button>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="hkdev-cart-totals-column">
					<div class="hkdev-cart-card hkdev-sticky-sidebar">
						<div class="hkdev-cart-totals-header">
							<h3><?php esc_html_e( 'Cart Summary', 'hkdev-shop-elements' ); ?></h3>
						</div>

						<div id="hkdev-cart-totals-area">
							<?php echo $this->get_cart_totals_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>

						<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="hkdev-cart-secondary-btn"><?php esc_html_e( 'Continue Shopping', 'hkdev-shop-elements' ); ?></a>
					</div>
				</div>
			</div>

			<?php do_action( 'woocommerce_after_cart' ); ?>

			<div id="hkdev-cart-global-loader"><div class="loader-box-inner"><i class="fa-solid fa-circle-notch fa-spin"></i> &nbsp; <?php esc_html_e( 'Updating...', 'hkdev-shop-elements' ); ?></div></div>
		</div>
		<input type="hidden" id="hkdev_cart_nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
		<?php
		return ob_get_clean();
	}

	/**
	 * NATIVE HOOK OVERRIDE: custom checkout button.
	 *
	 * @return void
	 */
	public function native_custom_checkout_button() {
		// If the hkdev-shop theme (or another provider) is active it adds its
		// own custom button on the same hook. Bail here to avoid a duplicate.
		if ( class_exists( '\HkdevShop\App\Controllers\Cart_Controller' ) ) {
			return;
		}
		?>
		<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward hkdev-cart-primary-btn proceed-btn">
			<?php esc_html_e( 'Proceed to Checkout', 'hkdev-shop-elements' ); ?> <i class="fa-solid fa-arrow-right"></i>
		</a>
		<?php
	}

	/**
	 * Cart items HTML generator (native hooks preserved).
	 *
	 * @return string
	 */
	public function get_cart_items_html() {
		ob_start();

		do_action( 'woocommerce_before_cart_contents' );
		?>
		<div class="hkdev-cart-items-list">
			<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

					$row_class = apply_filters( 'woocommerce_cart_item_class', 'hkdev-cart-item-row', $cart_item, $cart_item_key );
					?>
					<div class="<?php echo esc_attr( $row_class ); ?>" data-key="<?php echo esc_attr( $cart_item_key ); ?>" data-free-count="<?php echo esc_attr( intval( $cart_item['hkdev_free_count'] ?? 0 ) ); ?>">

						<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_ITEM_IMAGE ) : ?>
						<div class="item-img">
							<?php
							$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'thumbnail' ), $cart_item, $cart_item_key );
							if ( ! $product_permalink ) {
								echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
							}
							?>
						</div>
						<?php endif; ?>

						<div class="item-details">
							<h4 class="item-title">
								<?php
								$item_name   = $_product->get_name();
								$linked_name = $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), esc_html( $item_name ) ) : esc_html( $item_name );
								echo apply_filters( 'woocommerce_cart_item_name', $linked_name, $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
								echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
								}
								?>
							</h4>

							<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_ITEM_PRICE ) : ?>
							<div class="item-price-unit">
								<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<?php endif; ?>

							<div class="hkdev-cart-qty-wrap">
								<?php if ( $_product->is_sold_individually() ) : ?>
									<span class="hkdev-qty-val">1</span>
								<?php else : ?>
									<div class="hkdev-qty-stepper-ui">
										<button type="button" class="hkdev-qty-mod minus" data-act="minus">&minus;</button>
										<span class="hkdev-qty-val"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
										<button type="button" class="hkdev-qty-mod plus" data-act="plus">+</button>
									</div>
								<?php endif; ?>

								<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_REMOVE_BTN ) : ?>
									<button type="button" class="hkdev-item-remove-btn" title="<?php esc_attr_e( 'Remove', 'hkdev-shop-elements' ); ?>"><i class="fa-solid fa-trash-can"></i></button>
								<?php endif; ?>

								<?php echo Wishlist_Engine::instance()->button_html( $product_id, [ 'class' => 'hkdev-cart-wishlist-btn' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>

							<button type="button" class="hkdev-item-move-btn" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
								<i class="fa-solid fa-heart" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Move to Wishlist', 'hkdev-shop-elements' ); ?></span>
							</button>
						</div>

						<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_ITEM_SUBTOTAL ) : ?>
						<div class="item-total-price">
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<?php endif; ?>

					</div>
					<?php
				}
			endforeach; ?>
		</div>
		<?php
		$hkdev_cart_total_qty  = WC()->cart->get_cart_contents_count();
		$hkdev_cart_total_free = function_exists( 'hkdev_get_total_free_items_in_cart' ) ? hkdev_get_total_free_items_in_cart() : 0;
		$hkdev_cart_paid_qty   = max( 0, $hkdev_cart_total_qty - $hkdev_cart_total_free );
		?>
		<div class="hkdev-co-items-count-summary">
			<span class="paid-count"><?php esc_html_e( 'Paid Items:', 'hkdev-shop-elements' ); ?> <strong><?php echo esc_html( $hkdev_cart_paid_qty ); ?></strong></span>
			<span class="separator"> | </span>
			<span class="free-count"><?php esc_html_e( 'Free items:', 'hkdev-shop-elements' ); ?> <strong><?php echo esc_html( $hkdev_cart_total_free ); ?></strong></span>
		</div>
		<?php
		do_action( 'woocommerce_cart_contents' );
		do_action( 'woocommerce_after_cart_contents' );

		return ob_get_clean();
	}

	/**
	 * Cart totals HTML generator.
	 *
	 * @return string
	 */
	public function get_cart_totals_html() {
		ob_start();

		do_action( 'woocommerce_before_cart_totals' );
		?>
		<div class="hkdev-cart-calc-wrap">

			<?php if ( 'yes' === HKDEV_ELEMENTS_SHOW_CART_SUBTOTAL ) : ?>
			<div class="calc-line"><span><?php esc_html_e( 'Subtotal', 'hkdev-shop-elements' ); ?></span><strong><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
			<?php endif; ?>

			<?php
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) :
				$coupon_obj    = new \WC_Coupon( $code );
				$discount_type = $coupon_obj->get_discount_type();
				$coupon_amount = $coupon_obj->get_amount();

				$display_label = esc_html( $code );
				if ( 'percent' === $discount_type ) {
					$display_label .= ' (' . floatval( $coupon_amount ) . '%)';
				}
				?>
				<div class="calc-line coupon-line">
					<span><i class="fa-solid fa-tag"></i> <?php esc_html_e( 'Coupon', 'hkdev-shop-elements' ); ?> <?php echo esc_html( $display_label ); ?></span>
					<span>
						<strong>-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						<a href="#" class="hkdev-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>" style="color:var(--hkdev-brand-secondary, #f06724); font-size:13px; margin-left:5px; text-decoration:underline;">[<?php esc_html_e( 'Remove', 'hkdev-shop-elements' ); ?>]</a>
					</span>
				</div>
			<?php endforeach; ?>

			<?php
			foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<div class="calc-line fee-line" style="color: #03a550; font-weight: 500;">
					<span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->name ); ?></span>
					<strong><?php echo wc_price( $fee->total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</div>
			<?php endforeach; ?>

			<?php
			if ( 'yes' === HKDEV_ELEMENTS_SHOW_SHIPPING && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) :
				do_action( 'woocommerce_cart_totals_before_shipping' );
				?>
				<div class="calc-line"><span><?php esc_html_e( 'Shipping Charge', 'hkdev-shop-elements' ); ?></span><strong><?php echo wc_cart_totals_shipping_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
				<?php
				do_action( 'woocommerce_cart_totals_after_shipping' );
			endif;
			?>

			<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

			<div class="calc-line grand-total-line">
				<span><?php esc_html_e( 'Grand Total', 'hkdev-shop-elements' ); ?></span>
				<strong><?php echo WC()->cart->get_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			</div>

			<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>
		</div>
		<?php
		do_action( 'woocommerce_after_cart_totals' );

		return ob_get_clean();
	}

	/**
	 * AJAX cart update handler (qty / remove / coupon).
	 *
	 * @return void
	 */
	public function ajax_cart_update_handler() {
		check_ajax_referer( self::NONCE_ACTION, 'security' );

		$type = isset( $_POST['update_type'] ) ? sanitize_text_field( wp_unslash( $_POST['update_type'] ) ) : '';

		if ( 'qty' === $type && isset( $_POST['cart_key'], $_POST['new_qty'] ) ) {
			WC()->cart->set_quantity( sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ), absint( $_POST['new_qty'] ), true );
		} elseif ( 'remove' === $type && isset( $_POST['cart_key'] ) ) {
			WC()->cart->remove_cart_item( sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) );
		} elseif ( 'apply_coupon' === $type && ! empty( $_POST['coupon_code'] ) ) {
			WC()->cart->add_discount( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) );
		} elseif ( 'remove_coupon' === $type && ! empty( $_POST['coupon_code'] ) ) {
			WC()->cart->remove_coupon( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) );
		}

		WC()->cart->calculate_totals();

		if ( WC()->cart->is_empty() ) {
			wp_send_json_success( [ 'is_empty' => true ] );
		} else {
			wp_send_json_success(
				[
					'items_html'  => $this->get_cart_items_html(),
					'totals_html' => $this->get_cart_totals_html(),
				]
			);
		}
	}
}
