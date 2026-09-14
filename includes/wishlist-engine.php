<?php
/**
 * HKDEV Wishlist Engine (HKDEV Shop Elements plugin).
 *
 * Self-contained saved-products list:
 *  - logged-in shoppers  -> user meta (hkdev_wishlist)
 *  - guests              -> a 30 day cookie, merged into the account on login
 *  - toggling            -> AJAX (works with page caching, no reload)
 *
 * Renders the "Add to Wishlist" button (product cards, cart rows, single
 * product page) and the wishlist page itself through the Shop_Engine product
 * card, so the listing looks identical to every other grid in the plugin.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wishlist_Engine
 */
class Wishlist_Engine {

	const AJAX_TOGGLE  = 'hkdev_elements_wishlist_toggle';
	const AJAX_MOVE    = 'hkdev_elements_wishlist_move';
	const AJAX_ADD_ALL = 'hkdev_elements_wishlist_add_all';
	const NONCE_ACTION = 'hkdev_elements_wishlist';
	const META_KEY     = 'hkdev_wishlist';
	const COOKIE_NAME  = 'hkdev_wishlist';
	const SHORTCODE    = 'hkdev_wishlist';
	const COOKIE_DAYS  = 30;

	/**
	 * @var ?Wishlist_Engine
	 */
	private static $instance = null;

	/**
	 * Per-request cache of the resolved list (avoids repeated meta/cookie reads).
	 *
	 * @var ?int[]
	 */
	private $ids = null;

	/**
	 * Singleton.
	 *
	 * @return Wishlist_Engine
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
		add_action( 'wp_ajax_' . self::AJAX_TOGGLE, [ $this, 'ajax_toggle' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_TOGGLE, [ $this, 'ajax_toggle' ] );

		add_action( 'wp_ajax_' . self::AJAX_MOVE, [ $this, 'ajax_move_from_cart' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_MOVE, [ $this, 'ajax_move_from_cart' ] );

		add_action( 'wp_ajax_' . self::AJAX_ADD_ALL, [ $this, 'ajax_add_all_to_cart' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ADD_ALL, [ $this, 'ajax_add_all_to_cart' ] );

		// A guest list follows the shopper into their account on login.
		add_action( 'wp_login', [ $this, 'merge_guest_list' ], 10, 2 );
	}

	/* ---------------------------------------------------------------------
	 * Storage
	 * ------------------------------------------------------------------- */

	/**
	 * Normalise an arbitrary value into a clean list of product IDs.
	 *
	 * @param mixed $value Raw value (array or comma string).
	 * @return int[]
	 */
	private function normalize( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		$value = is_array( $value ) ? $value : [];
		$value = array_map( 'absint', $value );
		$value = array_filter( $value );

		return array_values( array_unique( $value ) );
	}

	/**
	 * The current shopper's wishlist.
	 *
	 * @return int[]
	 */
	public function get_ids() {
		if ( null !== $this->ids ) {
			return $this->ids;
		}

		if ( is_user_logged_in() ) {
			$raw = get_user_meta( get_current_user_id(), self::META_KEY, true );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only cookie lookup, sanitised in normalize().
			$raw = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
		}

		$this->ids = $this->normalize( $raw );

		return $this->ids;
	}

	/**
	 * Persist the wishlist for the current shopper.
	 *
	 * @param int[] $ids Product IDs.
	 * @return void
	 */
	public function set_ids( $ids ) {
		$ids       = $this->normalize( $ids );
		$this->ids = $ids;

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $ids );
			return;
		}

		$this->write_guest_cookie( $ids );
	}

	/**
	 * Store the guest list in a cookie.
	 *
	 * @param int[] $ids Product IDs.
	 * @return void
	 */
	private function write_guest_cookie( $ids ) {
		$value = implode( ',', $ids );

		if ( ! headers_sent() ) {
			$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
			$domain = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
			setcookie( self::COOKIE_NAME, $value, time() + ( self::COOKIE_DAYS * DAY_IN_SECONDS ), $path, $domain );
		}

		// Keep the current request in sync even when the header was already sent.
		$_COOKIE[ self::COOKIE_NAME ] = $value;
	}

	/**
	 * Empty a guest cookie.
	 *
	 * @return void
	 */
	private function clear_guest_cookie() {
		if ( ! headers_sent() ) {
			$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
			$domain = defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
			setcookie( self::COOKIE_NAME, '', time() - DAY_IN_SECONDS, $path, $domain );
		}

		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * How many products are saved.
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->get_ids() );
	}

	/**
	 * Whether a product is saved.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function has( $product_id ) {
		return in_array( absint( $product_id ), $this->get_ids(), true );
	}

	/**
	 * Add or remove a product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool True when the product ended up in the list.
	 */
	public function toggle( $product_id ) {
		$product_id = absint( $product_id );
		$ids        = $this->get_ids();
		$index      = array_search( $product_id, $ids, true );

		if ( false === $index ) {
			$ids[] = $product_id;
			$this->set_ids( $ids );
			return true;
		}

		unset( $ids[ $index ] );
		$this->set_ids( $ids );
		return false;
	}

	/**
	 * Move a guest list into the account once the shopper logs in.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       Logged-in user.
	 * @return void
	 */
	public function merge_guest_list( $user_login, $user ) {
		unset( $user_login );

		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) || ! $user instanceof \WP_User ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only cookie lookup, sanitised in normalize().
		$guest = $this->normalize( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) );
		if ( empty( $guest ) ) {
			return;
		}

		$existing = $this->normalize( get_user_meta( $user->ID, self::META_KEY, true ) );

		update_user_meta( $user->ID, self::META_KEY, array_values( array_unique( array_merge( $existing, $guest ) ) ) );
		$this->ids = null;

		$this->clear_guest_cookie();
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------- */

	/**
	 * AJAX: add / remove a product and report the fresh state.
	 *
	 * @return void
	 */
	public function ajax_toggle() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( ! $product_id || ! function_exists( 'wc_get_product' ) || ! wc_get_product( $product_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Product not found.', 'hkdev-shop-elements' ) ] );
		}

		$added = $this->toggle( $product_id );

		wp_send_json_success(
			[
				'product_id' => $product_id,
				'active'     => $added,
				'count'      => $this->count(),
				'ids'        => $this->get_ids(),
				'message'    => $added
					? __( 'Added to wishlist', 'hkdev-shop-elements' )
					: __( 'Removed from wishlist', 'hkdev-shop-elements' ),
			]
		);
	}

	/**
	 * AJAX: save a cart item to the wishlist and take it out of the cart.
	 *
	 * Returns the refreshed cart markup so the row disappears without a
	 * reload, mirroring the plugin's own cart AJAX contract.
	 *
	 * @return void
	 */
	public function ajax_move_from_cart() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'The cart is not available.', 'hkdev-shop-elements' ) ] );
		}

		$cart_key  = isset( $_POST['cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_key'] ) ) : '';
		$cart_item = ( '' !== $cart_key ) ? WC()->cart->get_cart_item( $cart_key ) : false;

		if ( ! $cart_item ) {
			wp_send_json_error( [ 'message' => __( 'That item is no longer in the cart.', 'hkdev-shop-elements' ) ] );
		}

		// Save first: the product must never be lost between the two steps.
		$product_id = absint( $cart_item['product_id'] );
		$ids        = $this->get_ids();

		if ( ! in_array( $product_id, $ids, true ) ) {
			$ids[] = $product_id;
			$this->set_ids( $ids );
		}

		WC()->cart->remove_cart_item( $cart_key );
		WC()->cart->calculate_totals();

		$payload = [
			'is_empty'   => false,
			'count'      => $this->count(),
			'ids'        => $this->get_ids(),
			'cart_count' => WC()->cart->get_cart_contents_count(),
			'message'    => __( 'Moved to your wishlist.', 'hkdev-shop-elements' ),
		];

		if ( WC()->cart->is_empty() ) {
			wp_send_json_success( array_merge( $payload, [ 'is_empty' => true ] ) );
		}

		wp_send_json_success(
			array_merge(
				$payload,
				[
					'items_html'  => Cart_Engine::instance()->get_cart_items_html(),
					'totals_html' => Cart_Engine::instance()->get_cart_totals_html(),
				]
			)
		);
	}

	/**
	 * AJAX: add every wishlist product that can go straight into the cart.
	 *
	 * Variable / grouped products need the shopper to choose options, so they
	 * are left in the wishlist and reported back as skipped instead of being
	 * forced into the cart without a variation.
	 *
	 * @return void
	 */
	public function ajax_add_all_to_cart() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => __( 'The cart is not available.', 'hkdev-shop-elements' ) ] );
		}

		$ids = $this->get_ids();

		if ( empty( $ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Your wishlist is empty.', 'hkdev-shop-elements' ) ] );
		}

		$added   = 0;
		$skipped = 0;

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$skipped++;
				continue;
			}

			if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
				$skipped++;
				continue;
			}

			$quantity = 1;

			if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, 0, [] ) ) {
				$skipped++;
				continue;
			}

			if ( WC()->cart->add_to_cart( $product_id, $quantity ) ) {
				$added++;
			} else {
				$skipped++;
			}
		}

		if ( $added < 1 ) {
			wp_send_json_error(
				[
					'message' => __( 'Nothing could be added — variable products need their options chosen on the product page.', 'hkdev-shop-elements' ),
					'skipped' => $skipped,
				]
			);
		}

		WC()->cart->calculate_totals();

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
				'added'      => $added,
				'skipped'    => $skipped,
				'cart_count' => WC()->cart->get_cart_contents_count(),
				'fragments'  => $fragments,
				'cart_hash'  => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_hash() ),
				'message'    => $this->add_all_message( $added, $skipped ),
			]
		);
	}

	/**
	 * Result sentence for the "Add All to Cart" action.
	 *
	 * @param int $added   Products added.
	 * @param int $skipped Products left behind.
	 * @return string
	 */
	private function add_all_message( $added, $skipped ) {
		$message = sprintf(
			/* translators: %d: number of products. */
			_n( '%d product added to your cart.', '%d products added to your cart.', $added, 'hkdev-shop-elements' ),
			$added
		);

		if ( $skipped > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of products. */
				_n( '%d item needs its options chosen on the product page.', '%d items need their options chosen on the product page.', $skipped, 'hkdev-shop-elements' ),
				$skipped
			);
		}

		return $message;
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------- */

	/**
	 * Enqueue the wishlist assets (shortcode / account tab can render late).
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'hkdev-elements-wishlist-style' );
		wp_enqueue_script( 'hkdev-elements-wishlist-js' );
		wp_enqueue_style( 'hkdev-elements-checkout-style' );
		wp_enqueue_script( 'hkdev-elements-shop-js' );
	}

	/**
	 * "Add to Wishlist" button.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $args       style (icon|button), label, class.
	 * @return string
	 */
	public function button_html( $product_id, $args = [] ) {
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			[
				'style' => 'icon',
				'label' => '',
				'class' => '',
			]
		);

		$active     = $this->has( $product_id );
		$add_label  = ( '' !== $args['label'] ) ? $args['label'] : __( 'Add to Wishlist', 'hkdev-shop-elements' );
		$done_label = __( 'In Wishlist', 'hkdev-shop-elements' );

		$classes = [ 'hkdev-wishlist-btn', 'hkdev-wishlist-btn--' . sanitize_html_class( $args['style'] ) ];
		if ( $active ) {
			$classes[] = 'is-active';
		}
		if ( '' !== $args['class'] ) {
			$classes[] = sanitize_html_class( $args['class'] );
		}

		ob_start();
		?>
		<button type="button" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-label-add="<?php echo esc_attr( $add_label ); ?>"
			data-label-added="<?php echo esc_attr( $done_label ); ?>"
			aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
			title="<?php echo esc_attr( $active ? $done_label : $add_label ); ?>">
			<i class="<?php echo esc_attr( $active ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ); ?>" aria-hidden="true"></i>
			<?php if ( 'button' === $args['style'] ) : ?>
				<span class="hkdev-wishlist-label"><?php echo esc_html( $active ? $done_label : $add_label ); ?></span>
			<?php else : ?>
				<span class="screen-reader-text"><?php echo esc_html( $active ? $done_label : $add_label ); ?></span>
			<?php endif; ?>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * "Add All to Cart" button plus the inline status message next to it.
	 *
	 * @return string
	 */
	private function add_all_button_html() {
		ob_start();
		?>
		<div class="hkdev-wishlist-actions">
			<span class="hkdev-wishlist-notice" role="status" aria-live="polite" hidden></span>
			<button type="button" class="hkdev-wishlist-add-all">
				<i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
				<span class="hkdev-wishlist-add-all-label"><?php esc_html_e( 'Add All to Cart', 'hkdev-shop-elements' ); ?></span>
				<span class="hkdev-wishlist-add-all-spinner" aria-hidden="true"></span>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ready-to-print markup for the wishlist page (widget + shortcode).
	 *
	 * @param array $atts Configuration.
	 * @return string
	 */
	public function wishlist_shortcode( $atts = [] ) {
		if ( ! function_exists( 'WC' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'columns'    => 4,
				'title'      => __( 'My Wishlist', 'hkdev-shop-elements' ),
				'subtitle'   => '',
				'show_count' => 'yes',
				'add_all'    => 'yes',
				'empty_text' => __( 'Your wishlist is empty.', 'hkdev-shop-elements' ),
				'empty_btn'  => __( 'Continue Shopping', 'hkdev-shop-elements' ),
			],
			$atts,
			self::SHORTCODE
		);

		$this->enqueue_assets();

		$columns  = max( 1, min( 6, absint( $atts['columns'] ) ) );
		$ids      = $this->get_ids();
		$has      = ! empty( $ids );
		$total    = count( $ids );
		$title    = trim( (string) $atts['title'] );
		$subtitle = trim( (string) $atts['subtitle'] );
		$add_all  = ( 'yes' === $atts['add_all'] );

		$shop_id  = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
		$shop_url = ( $shop_id > 0 ) ? get_permalink( $shop_id ) : home_url( '/' );

		ob_start();
		?>
		<div class="hkdev-wishlist" data-wishlist="1" data-count="<?php echo esc_attr( $total ); ?>">

			<?php if ( '' !== $title || '' !== $subtitle || ( $has && $add_all ) ) : ?>
				<div class="hkdev-wishlist-head">
					<?php if ( '' !== $title || '' !== $subtitle ) : ?>
						<span class="hkdev-wishlist-accent" aria-hidden="true"></span>
						<div class="hkdev-wishlist-head-texts">
							<?php if ( '' !== $title ) : ?>
								<h2 class="hkdev-wishlist-title">
									<?php echo esc_html( $title ); ?>
									<?php if ( 'yes' === $atts['show_count'] ) : ?>
										<span class="hkdev-wishlist-count" data-count="<?php echo esc_attr( $total ); ?>"><?php echo esc_html( $total ); ?></span>
									<?php endif; ?>
								</h2>
							<?php endif; ?>
							<?php if ( '' !== $subtitle ) : ?>
								<span class="hkdev-wishlist-sub"><?php echo esc_html( $subtitle ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $has && $add_all ) : ?>
						<?php echo $this->add_all_button_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="hkdev-wishlist-grid hkdev-shop-grid hkdev-columns-<?php echo esc_attr( $columns ); ?>"<?php echo $has ? '' : ' hidden'; ?>>
				<?php
				if ( $has ) {
					// Newest first, and only products that are still published.
					$query = new \WP_Query(
						[
							'post_type'      => 'product',
							'post_status'    => 'publish',
							'post__in'       => array_reverse( $ids ),
							'orderby'        => 'post__in',
							'posts_per_page' => count( $ids ),
							'no_found_rows'  => true,
						]
					);

					if ( $query->have_posts() ) {
						while ( $query->have_posts() ) {
							$query->the_post();
							Shop_Engine::instance()->render_single_product_card( get_the_ID(), 0, false, 'woocommerce_thumbnail', true, true );
						}
						wp_reset_postdata();
					}
				}
				?>
			</div>

			<div class="hkdev-wishlist-empty"<?php echo $has ? ' hidden' : ''; ?>>
				<span class="hkdev-wishlist-empty-icon" aria-hidden="true"><i class="fa-regular fa-heart"></i></span>
				<h3><?php echo esc_html( $atts['empty_text'] ); ?></h3>
				<a class="hkdev-wishlist-empty-btn" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html( $atts['empty_btn'] ); ?></a>
			</div>

			<?php echo Shop_Engine::instance()->variation_modal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
