<?php
/**
 * HKDEV Tracking Engine (HKDEV Shop Elements plugin).
 *
 * Order tracking by order ID and phone number.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tracking_Engine {

	const AJAX_TRACK = 'hkdev_elements_track_order';
	const NONCE_ACTION = 'hkdev_elements_tracking';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_TRACK, [ $this, 'ajax_track_order' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_TRACK, [ $this, 'ajax_track_order' ] );
	}

	public function tracking_shortcode( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'title'       => __( 'Track Your Order', 'hkdev-shop-elements' ),
				'description' => __( 'Enter your Order ID and phone number to track your order securely.', 'hkdev-shop-elements' ),
				'show_status_legend' => 'yes',
			],
			$atts,
			'hkdev_track_order'
		);

		ob_start();
		?>
		<div class="hkdev-tracking-wrap" id="hkdev-tracking-root">
			<div class="hkdev-tracking-header">
				<h3><?php echo esc_html( $atts['title'] ); ?></h3>
				<p><?php echo esc_html( $atts['description'] ); ?></p>
			</div>
			<div class="hkdev-tracking-form-wrap">
				<form id="hkdev-tracking-form" class="hkdev-tracking-form">
					<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_tracking_nonce' ); ?>
					<div class="hkdev-tracking-fields">
						<div class="hkdev-tracking-field">
							<label for="hkdev-track-phone"><?php esc_html_e( 'Phone Number', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
							<input type="tel" id="hkdev-track-phone" name="phone" required placeholder="<?php esc_attr_e( '01XXXXXXXXX', 'hkdev-shop-elements' ); ?>">
						</div>
						<div class="hkdev-tracking-field">
							<label for="hkdev-track-order-id"><?php esc_html_e( 'Order ID', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
							<input type="text" id="hkdev-track-order-id" name="order_id" required placeholder="<?php esc_attr_e( 'Enter your order number', 'hkdev-shop-elements' ); ?>">
						</div>
					</div>
					<button type="submit" class="hkdev-tracking-submit">
						<span class="hkdev-tracking-btn-text"><?php esc_html_e( 'Track Order', 'hkdev-shop-elements' ); ?></span>
						<span class="hkdev-tracking-spinner" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i></span>
					</button>
				</form>
			</div>
			<div class="hkdev-tracking-message" style="display:none;"></div>
			<div class="hkdev-tracking-result" style="display:none;"></div>
			<?php if ( 'yes' === $atts['show_status_legend'] ) : ?>
				<div class="hkdev-tracking-legend">
					<h4><?php esc_html_e( 'Order Status Guide', 'hkdev-shop-elements' ); ?></h4>
					<div class="hkdev-tracking-legend-items">
						<div class="hkdev-tracking-legend-item"><span class="hkdev-tracking-dot status-pending"></span><span><?php esc_html_e( 'Pending', 'hkdev-shop-elements' ); ?> — <?php esc_html_e( 'Order placed, awaiting confirmation', 'hkdev-shop-elements' ); ?></span></div>
						<div class="hkdev-tracking-legend-item"><span class="hkdev-tracking-dot status-processing"></span><span><?php esc_html_e( 'Processing', 'hkdev-shop-elements' ); ?> — <?php esc_html_e( 'Order confirmed, being prepared', 'hkdev-shop-elements' ); ?></span></div>
						<div class="hkdev-tracking-legend-item"><span class="hkdev-tracking-dot status-on-hold"></span><span><?php esc_html_e( 'On Hold', 'hkdev-shop-elements' ); ?> — <?php esc_html_e( 'Awaiting payment or action', 'hkdev-shop-elements' ); ?></span></div>
						<div class="hkdev-tracking-legend-item"><span class="hkdev-tracking-dot status-completed"></span><span><?php esc_html_e( 'Delivered', 'hkdev-shop-elements' ); ?> — <?php esc_html_e( 'Order delivered successfully', 'hkdev-shop-elements' ); ?></span></div>
						<div class="hkdev-tracking-legend-item"><span class="hkdev-tracking-dot status-cancelled"></span><span><?php esc_html_e( 'Cancelled', 'hkdev-shop-elements' ); ?> — <?php esc_html_e( 'Order has been cancelled', 'hkdev-shop-elements' ); ?></span></div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function ajax_track_order() {
		check_ajax_referer( self::NONCE_ACTION, 'hkdev_tracking_nonce' );

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( '' === trim( $phone ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter your phone number.', 'hkdev-shop-elements' ) ] );
		}
		if ( ! $order_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid Order ID.', 'hkdev-shop-elements' ) ] );
		}

		$orders = [];
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Order not found. Please check the Order ID.', 'hkdev-shop-elements' ) ] );
		}

		// The phone must match the order, otherwise anyone could read customer
		// data by guessing a (sequential) order ID.
		if ( ! $this->order_matches_phone( $order, $phone ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Phone number does not match this order. Please check and try again.', 'hkdev-shop-elements' ) ] );
		}
		$orders[] = $order;

		$results = [];
		foreach ( $orders as $order ) {
			$results[] = $this->build_tracking_data( $order );
		}

		$count = count( $results );

		wp_send_json_success( [
			'message' => $count > 1
				/* translators: %d: number of matched orders. */
				? sprintf( esc_html__( 'Found %d orders for this phone number.', 'hkdev-shop-elements' ), $count )
				: esc_html__( 'Order found!', 'hkdev-shop-elements' ),
			'orders'  => $results,
		] );
	}

	/**
	 * Build the tracking payload for a single order.
	 */
	private function build_tracking_data( $order ) {
		$status   = $order->get_status();
		$statuses = $this->get_order_status_progress( $status );

		$items = [];
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = [
				'name'     => esc_html( $item->get_name() ),
				'quantity' => (int) $item->get_quantity(),
				'image'    => $product ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
				'total'    => $order->get_formatted_line_subtotal( $item ),
			];
		}

		// Plain-text fields are escaped here because tracking.js injects this
		// payload into the DOM with .html(). Totals stay as (safe) price HTML.
		return [
			'order_id'        => esc_html( $order->get_order_number() ),
			'order_date'      => esc_html( wc_format_datetime( $order->get_date_created() ) ),
			'status'          => esc_attr( $status ),
			'status_name'     => esc_html( wc_get_order_status_name( $status ) ),
			'status_progress' => $statuses,
			'total'           => $order->get_formatted_order_total(),
			'payment_method'  => esc_html( $order->get_payment_method_title() ),
			'billing'         => [
				'name'    => esc_html( $order->get_formatted_billing_full_name() ),
				'phone'   => esc_html( $order->get_billing_phone() ),
				'email'   => esc_html( $order->get_billing_email() ),
				'address' => esc_html( $this->plain_address( $order->get_formatted_billing_address() ) ),
			],
			'shipping'        => [
				'name'    => esc_html( $order->get_formatted_shipping_full_name() ),
				'address' => esc_html( $this->plain_address( $order->get_formatted_shipping_address() ) ),
			],
			'items'           => $items,
			'notes'           => $this->get_order_notes( $order ),
		];
	}

	/**
	 * Phone number stored on an order (billing → shipping → customer meta).
	 *
	 * @return string
	 */
	private function get_order_phone( $order ) {
		$order_phone = $order->get_billing_phone();

		if ( empty( $order_phone ) && method_exists( $order, 'get_shipping_phone' ) ) {
			$order_phone = $order->get_shipping_phone();
		}

		if ( empty( $order_phone ) ) {
			$customer_id = $order->get_customer_id();
			if ( $customer_id ) {
				$order_phone = get_user_meta( $customer_id, 'billing_phone', true );
			}
		}

		return (string) $order_phone;
	}

	/**
	 * Whether the submitted phone matches the order (tolerates +880 / spaces).
	 *
	 * @return bool
	 */
	private function order_matches_phone( $order, $phone ) {
		$order_digits = $this->digits( $this->get_order_phone( $order ) );
		$input_digits = $this->digits( $phone );

		if ( '' === $order_digits || '' === $input_digits ) {
			return false;
		}

		if ( $order_digits === $input_digits ) {
			return true;
		}

		// Allow country-code differences by comparing the last 10 digits.
		return strlen( $order_digits ) >= 10
			&& strlen( $input_digits ) >= 10
			&& substr( $order_digits, -10 ) === substr( $input_digits, -10 );
	}

	/**
	 * Digits only.
	 *
	 * @return string
	 */
	private function digits( $value ) {
		return preg_replace( '/[^0-9]/', '', (string) $value );
	}

	private function get_order_status_progress( $current_status ) {
		$all_statuses = [
			'pending'    => [ 'label' => __( 'Order Placed', 'hkdev-shop-elements' ), 'icon' => 'fa-clipboard-check' ],
			'processing' => [ 'label' => __( 'Confirmed', 'hkdev-shop-elements' ), 'icon' => 'fa-check-circle' ],
			'shipped'    => [ 'label' => __( 'Shipped', 'hkdev-shop-elements' ), 'icon' => 'fa-truck-fast' ],
			'completed'  => [ 'label' => __( 'Delivered', 'hkdev-shop-elements' ), 'icon' => 'fa-box-open' ],
		];

		$status_map = [
			'pending'    => 'pending',
			'processing' => 'processing',
			'on-hold'    => 'processing',
			'shipped'    => 'shipped',
			'completed'  => 'completed',
		];

		$current_step = isset( $status_map[ $current_status ] ) ? $status_map[ $current_status ] : 'pending';

		$steps = [];
		$keys  = array_keys( $all_statuses );
		$current_idx = array_search( $current_step, $keys, true );

		foreach ( $all_statuses as $key => $info ) {
			$step_status = 'pending';
			$idx = array_search( $key, $keys, true );
			if ( $key === $current_step ) {
				$step_status = 'active';
			} elseif ( $idx < $current_idx ) {
				$step_status = 'completed';
			}
			$steps[ $key ] = array_merge( $info, [ 'state' => $step_status ] );
		}

		if ( in_array( $current_status, [ 'cancelled', 'refunded' ], true ) ) {
			$steps['cancelled'] = [
				'label' => 'cancelled' === $current_status ? __( 'Cancelled', 'hkdev-shop-elements' ) : __( 'Refunded', 'hkdev-shop-elements' ),
				'icon'  => 'fa-circle-xmark',
				'state' => 'cancelled',
			];
		}

		return $steps;
	}

	private function get_order_notes( $order ) {
		$notes = wc_get_order_notes( [
			'order_id' => $order->get_id(),
			'type'     => 'customer',
		] );

		$result = [];
		foreach ( $notes as $note ) {
			$result[] = [
				'content' => wp_kses_post( $note->content ),
				'date'    => esc_html( wc_format_datetime( $note->date_created ) ),
			];
		}
		return $result;
	}

	/**
	 * Turn a WooCommerce formatted (HTML) address into a single-line string.
	 *
	 * @param string $address HTML address.
	 * @return string
	 */
	private function plain_address( $address ) {
		if ( empty( $address ) ) {
			return '';
		}

		$address = preg_replace( '#<br\s*/?>#i', ', ', (string) $address );
		$address = wp_strip_all_tags( $address );
		$address = preg_replace( '/\s*,\s*/', ', ', $address );

		return trim( preg_replace( '/(,\s*)+/', ', ', $address ), " \t\n\r\0\x0B," );
	}
}