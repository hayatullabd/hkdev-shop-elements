<?php
/**
 * Customer-facing UI labels for cart, checkout, thank you, modal, variation,
 * single product, related products, and mini cart.
 *
 * Edited from Elementor widgets (and Header admin for mini cart). Saved values
 * apply globally so AJAX / modal / drawer markup stay in sync.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UiLabels
 */
class UiLabels {

	const OPTION = 'hkdev_elements_ui_labels';

	/**
	 * Hook document-save so widget edits persist without a front-end view.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'elementor/document/after_save', [ self::class, 'sync_from_document' ] );
	}

	/**
	 * Default copy, grouped by surface.
	 *
	 * @return array<string,array<string,array{label:string,default:string,multiline?:bool}>>
	 */
	public static function fields() {
		return [
			'cart'     => [
				'heading'            => [ 'label' => __( 'Heading', 'hkdev-shop-elements' ), 'default' => __( 'Shopping Cart', 'hkdev-shop-elements' ) ],
				'subtitle'           => [ 'label' => __( 'Subtitle', 'hkdev-shop-elements' ), 'default' => __( 'Selected Products', 'hkdev-shop-elements' ) ],
				'empty_title'        => [ 'label' => __( 'Empty Title', 'hkdev-shop-elements' ), 'default' => __( 'Your cart is empty', 'hkdev-shop-elements' ) ],
				'empty_text'         => [ 'label' => __( 'Empty Text', 'hkdev-shop-elements' ), 'default' => __( 'Browse our products and add to cart.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'start_shopping'     => [ 'label' => __( 'Start Shopping', 'hkdev-shop-elements' ), 'default' => __( 'Start Shopping', 'hkdev-shop-elements' ) ],
				'coupon_placeholder' => [ 'label' => __( 'Coupon Placeholder', 'hkdev-shop-elements' ), 'default' => __( 'Coupon Code', 'hkdev-shop-elements' ) ],
				'apply'              => [ 'label' => __( 'Apply Coupon', 'hkdev-shop-elements' ), 'default' => __( 'Apply', 'hkdev-shop-elements' ) ],
				'summary'            => [ 'label' => __( 'Summary Heading', 'hkdev-shop-elements' ), 'default' => __( 'Cart Summary', 'hkdev-shop-elements' ) ],
				'continue'           => [ 'label' => __( 'Continue Shopping', 'hkdev-shop-elements' ), 'default' => __( 'Continue Shopping', 'hkdev-shop-elements' ) ],
				'updating'           => [ 'label' => __( 'Updating', 'hkdev-shop-elements' ), 'default' => __( 'Updating...', 'hkdev-shop-elements' ) ],
				'checkout_btn'       => [ 'label' => __( 'Checkout Button', 'hkdev-shop-elements' ), 'default' => __( 'Proceed to Checkout', 'hkdev-shop-elements' ) ],
				'subtotal'           => [ 'label' => __( 'Subtotal', 'hkdev-shop-elements' ), 'default' => __( 'Subtotal', 'hkdev-shop-elements' ) ],
				'coupon'             => [ 'label' => __( 'Coupon', 'hkdev-shop-elements' ), 'default' => __( 'Coupon', 'hkdev-shop-elements' ) ],
				'remove'             => [ 'label' => __( 'Remove', 'hkdev-shop-elements' ), 'default' => __( 'Remove', 'hkdev-shop-elements' ) ],
				'shipping'           => [ 'label' => __( 'Shipping', 'hkdev-shop-elements' ), 'default' => __( 'Shipping Charge', 'hkdev-shop-elements' ) ],
				'grand_total'        => [ 'label' => __( 'Grand Total', 'hkdev-shop-elements' ), 'default' => __( 'Grand Total', 'hkdev-shop-elements' ) ],
				'paid_items'         => [ 'label' => __( 'Paid Items', 'hkdev-shop-elements' ), 'default' => __( 'Paid Items:', 'hkdev-shop-elements' ) ],
				'free_items'         => [ 'label' => __( 'Free Items', 'hkdev-shop-elements' ), 'default' => __( 'Free items:', 'hkdev-shop-elements' ) ],
			],
			'checkout' => [
				'empty_title'        => [ 'label' => __( 'Empty Title', 'hkdev-shop-elements' ), 'default' => __( 'Your Cart is Empty', 'hkdev-shop-elements' ) ],
				'start_shopping'     => [ 'label' => __( 'Start Shopping', 'hkdev-shop-elements' ), 'default' => __( 'Start Shopping', 'hkdev-shop-elements' ) ],
				'order_review'       => [ 'label' => __( 'Order Review', 'hkdev-shop-elements' ), 'default' => __( 'Order Review', 'hkdev-shop-elements' ) ],
				'billing'            => [ 'label' => __( 'Billing Address', 'hkdev-shop-elements' ), 'default' => __( 'Billing Address', 'hkdev-shop-elements' ) ],
				'shipping'           => [ 'label' => __( 'Shipping Address', 'hkdev-shop-elements' ), 'default' => __( 'Shipping Address', 'hkdev-shop-elements' ) ],
				'payment'            => [ 'label' => __( 'Payment Method', 'hkdev-shop-elements' ), 'default' => __( 'Payment Method', 'hkdev-shop-elements' ) ],
				'coupon_title'       => [ 'label' => __( 'Coupon Heading', 'hkdev-shop-elements' ), 'default' => __( 'Have any coupon or gift voucher?', 'hkdev-shop-elements' ) ],
				'coupon_placeholder' => [ 'label' => __( 'Coupon Placeholder', 'hkdev-shop-elements' ), 'default' => __( 'Enter coupon code', 'hkdev-shop-elements' ) ],
				'apply'              => [ 'label' => __( 'Apply Coupon', 'hkdev-shop-elements' ), 'default' => __( 'Apply', 'hkdev-shop-elements' ) ],
				'order_total'        => [ 'label' => __( 'Order Total', 'hkdev-shop-elements' ), 'default' => __( 'Order Total', 'hkdev-shop-elements' ) ],
				'notes'              => [ 'label' => __( 'Order Notes', 'hkdev-shop-elements' ), 'default' => __( 'Special Notes (Optional)', 'hkdev-shop-elements' ) ],
				'confirm'            => [ 'label' => __( 'Confirm Button', 'hkdev-shop-elements' ), 'default' => __( 'Confirm Order', 'hkdev-shop-elements' ) ],
				'subtotal'           => [ 'label' => __( 'Subtotal', 'hkdev-shop-elements' ), 'default' => __( 'Subtotal', 'hkdev-shop-elements' ) ],
				'coupon'             => [ 'label' => __( 'Coupon', 'hkdev-shop-elements' ), 'default' => __( 'Coupon', 'hkdev-shop-elements' ) ],
				'shipping_label'     => [ 'label' => __( 'Shipping', 'hkdev-shop-elements' ), 'default' => __( 'Shipping', 'hkdev-shop-elements' ) ],
				'grand_total'        => [ 'label' => __( 'Grand Total', 'hkdev-shop-elements' ), 'default' => __( 'GRAND TOTAL', 'hkdev-shop-elements' ) ],
				'paid_items'         => [ 'label' => __( 'Paid Items', 'hkdev-shop-elements' ), 'default' => __( 'Paid Items', 'hkdev-shop-elements' ) ],
				'free_items'         => [ 'label' => __( 'Free Items', 'hkdev-shop-elements' ), 'default' => __( 'Free items', 'hkdev-shop-elements' ) ],
				'delivery_area'      => [ 'label' => __( 'Delivery Area', 'hkdev-shop-elements' ), 'default' => __( 'Delivery Area', 'hkdev-shop-elements' ) ],
				'no_payment'         => [ 'label' => __( 'No Payment Methods', 'hkdev-shop-elements' ), 'default' => __( 'No payment methods available.', 'hkdev-shop-elements' ) ],
				'terms'              => [ 'label' => __( 'Terms Agreement', 'hkdev-shop-elements' ), 'default' => __( 'I have read and agree to the %1$s, %2$s &amp; %3$s.', 'hkdev-shop-elements' ), 'multiline' => true ],
			],
			'thankyou' => [
				'invoice'        => [ 'label' => __( 'Invoice Title', 'hkdev-shop-elements' ), 'default' => __( 'INVOICE', 'hkdev-shop-elements' ) ],
				'from'           => [ 'label' => __( 'From', 'hkdev-shop-elements' ), 'default' => __( 'From', 'hkdev-shop-elements' ) ],
				'order_date'     => [ 'label' => __( 'Order Date', 'hkdev-shop-elements' ), 'default' => __( 'Order Date', 'hkdev-shop-elements' ) ],
				'bill_to'        => [ 'label' => __( 'Bill To', 'hkdev-shop-elements' ), 'default' => __( 'Bill To', 'hkdev-shop-elements' ) ],
				'phone'          => [ 'label' => __( 'Phone', 'hkdev-shop-elements' ), 'default' => __( 'Phone', 'hkdev-shop-elements' ) ],
				'email'          => [ 'label' => __( 'Email', 'hkdev-shop-elements' ), 'default' => __( 'Email', 'hkdev-shop-elements' ) ],
				'payment'        => [ 'label' => __( 'Payment', 'hkdev-shop-elements' ), 'default' => __( 'Payment', 'hkdev-shop-elements' ) ],
				'print'          => [ 'label' => __( 'Print Invoice', 'hkdev-shop-elements' ), 'default' => __( 'Print Invoice', 'hkdev-shop-elements' ) ],
				'billing'        => [ 'label' => __( 'Billing Address', 'hkdev-shop-elements' ), 'default' => __( 'Billing Address', 'hkdev-shop-elements' ) ],
				'shipping'       => [ 'label' => __( 'Shipping Address', 'hkdev-shop-elements' ), 'default' => __( 'Shipping Address', 'hkdev-shop-elements' ) ],
				'item'           => [ 'label' => __( 'Item Column', 'hkdev-shop-elements' ), 'default' => __( 'Item', 'hkdev-shop-elements' ) ],
				'qty'            => [ 'label' => __( 'Qty Column', 'hkdev-shop-elements' ), 'default' => __( 'Qty', 'hkdev-shop-elements' ) ],
				'total'          => [ 'label' => __( 'Total Column', 'hkdev-shop-elements' ), 'default' => __( 'Total', 'hkdev-shop-elements' ) ],
				'subtotal'       => [ 'label' => __( 'Subtotal', 'hkdev-shop-elements' ), 'default' => __( 'Subtotal', 'hkdev-shop-elements' ) ],
				'coupon'         => [ 'label' => __( 'Coupon', 'hkdev-shop-elements' ), 'default' => __( 'Coupon:', 'hkdev-shop-elements' ) ],
				'shipping_label' => [ 'label' => __( 'Shipping', 'hkdev-shop-elements' ), 'default' => __( 'Shipping', 'hkdev-shop-elements' ) ],
				'grand_total'    => [ 'label' => __( 'Grand Total', 'hkdev-shop-elements' ), 'default' => __( 'Grand Total', 'hkdev-shop-elements' ) ],
				'thanks'         => [ 'label' => __( 'Thank You Message', 'hkdev-shop-elements' ), 'default' => __( 'Thank you for shopping with us!', 'hkdev-shop-elements' ), 'multiline' => true ],
				'continue'       => [ 'label' => __( 'Continue Shopping', 'hkdev-shop-elements' ), 'default' => __( 'Continue Shopping', 'hkdev-shop-elements' ) ],
			],
			'modal'    => [
				'loading' => [ 'label' => __( 'Loading', 'hkdev-shop-elements' ), 'default' => __( 'Loading checkout...', 'hkdev-shop-elements' ) ],
				'error'   => [ 'label' => __( 'Load Error', 'hkdev-shop-elements' ), 'default' => __( 'Could not load checkout. Please try again.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'close'   => [ 'label' => __( 'Close', 'hkdev-shop-elements' ), 'default' => __( 'Close', 'hkdev-shop-elements' ) ],
			],
			'variation' => [
				'note'           => [ 'label' => __( 'Helper Note', 'hkdev-shop-elements' ), 'default' => __( 'Select Size, Color, and other options that are in stock.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'select_options' => [ 'label' => __( 'Select Options', 'hkdev-shop-elements' ), 'default' => __( 'Select options', 'hkdev-shop-elements' ) ],
				'not_available'  => [ 'label' => __( 'Not Available', 'hkdev-shop-elements' ), 'default' => __( 'Not available', 'hkdev-shop-elements' ) ],
				'in_stock'       => [ 'label' => __( 'In Stock', 'hkdev-shop-elements' ), 'default' => __( 'In stock', 'hkdev-shop-elements' ) ],
				'in_stock_qty'   => [ 'label' => __( 'In Stock With Quantity', 'hkdev-shop-elements' ), 'default' => __( 'In stock (%s)', 'hkdev-shop-elements' ), 'description' => __( 'Use %s where the stock number should appear.', 'hkdev-shop-elements' ) ],
				'out_of_stock'   => [ 'label' => __( 'Out of Stock', 'hkdev-shop-elements' ), 'default' => __( 'Out of stock', 'hkdev-shop-elements' ) ],
				'add_to_cart'    => [ 'label' => __( 'Add to Cart Button', 'hkdev-shop-elements' ), 'default' => __( 'Add to Cart', 'hkdev-shop-elements' ) ],
				'buy_now'        => [ 'label' => __( 'Buy Now Button', 'hkdev-shop-elements' ), 'default' => __( 'Buy Now', 'hkdev-shop-elements' ) ],
				'close'          => [ 'label' => __( 'Close', 'hkdev-shop-elements' ), 'default' => __( 'Close', 'hkdev-shop-elements' ) ],
			],
			'single'   => [
				'home'                 => [ 'label' => __( 'Breadcrumb Home', 'hkdev-shop-elements' ), 'default' => __( 'Home', 'hkdev-shop-elements' ) ],
				'shop'                 => [ 'label' => __( 'Breadcrumb Shop', 'hkdev-shop-elements' ), 'default' => __( 'Shop', 'hkdev-shop-elements' ) ],
				'off'                  => [ 'label' => __( 'Sale Badge Suffix', 'hkdev-shop-elements' ), 'default' => __( 'Off!', 'hkdev-shop-elements' ) ],
				'zoom'                 => [ 'label' => __( 'Zoom', 'hkdev-shop-elements' ), 'default' => __( 'Zoom', 'hkdev-shop-elements' ) ],
				'select'               => [ 'label' => __( 'Variation Select', 'hkdev-shop-elements' ), 'default' => __( 'Select', 'hkdev-shop-elements' ) ],
				'size_chart'           => [ 'label' => __( 'Size Chart', 'hkdev-shop-elements' ), 'default' => __( 'Size Chart', 'hkdev-shop-elements' ) ],
				'out_of_stock_notice'  => [ 'label' => __( 'Out of Stock Notice', 'hkdev-shop-elements' ), 'default' => __( 'This product is currently out of stock.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'add_to_cart'          => [ 'label' => __( 'Add to Cart Button', 'hkdev-shop-elements' ), 'default' => __( 'Add to Cart', 'hkdev-shop-elements' ) ],
				'buy_now'              => [ 'label' => __( 'Buy Now Button', 'hkdev-shop-elements' ), 'default' => __( 'Buy Now', 'hkdev-shop-elements' ) ],
				'order_completed'      => [ 'label' => __( 'Already In Cart', 'hkdev-shop-elements' ), 'default' => __( 'Order Completed', 'hkdev-shop-elements' ) ],
				'whatsapp'             => [ 'label' => __( 'WhatsApp Button', 'hkdev-shop-elements' ), 'default' => __( 'Order on WhatsApp', 'hkdev-shop-elements' ) ],
				'call'                 => [ 'label' => __( 'Call Button', 'hkdev-shop-elements' ), 'default' => __( 'Call For Order', 'hkdev-shop-elements' ) ],
				'brand'                => [ 'label' => __( 'Brand', 'hkdev-shop-elements' ), 'default' => __( 'Brand', 'hkdev-shop-elements' ) ],
				'category'             => [ 'label' => __( 'Category', 'hkdev-shop-elements' ), 'default' => __( 'Category', 'hkdev-shop-elements' ) ],
				'sku'                  => [ 'label' => __( 'SKU', 'hkdev-shop-elements' ), 'default' => __( 'SKU', 'hkdev-shop-elements' ) ],
				'sku_empty'            => [ 'label' => __( 'SKU Empty', 'hkdev-shop-elements' ), 'default' => 'N/A' ],
				'stock'                => [ 'label' => __( 'Stock', 'hkdev-shop-elements' ), 'default' => __( 'Stock', 'hkdev-shop-elements' ) ],
				'in_stock'             => [ 'label' => __( 'In Stock', 'hkdev-shop-elements' ), 'default' => __( 'In Stock', 'hkdev-shop-elements' ) ],
				'out_of_stock'         => [ 'label' => __( 'Out of Stock', 'hkdev-shop-elements' ), 'default' => __( 'Out of Stock', 'hkdev-shop-elements' ) ],
				'description'          => [ 'label' => __( 'Description Tab', 'hkdev-shop-elements' ), 'default' => __( 'Description', 'hkdev-shop-elements' ) ],
				'reviews'              => [ 'label' => __( 'Reviews Tab', 'hkdev-shop-elements' ), 'default' => __( 'Reviews', 'hkdev-shop-elements' ) ],
				'faq_title'            => [ 'label' => __( 'FAQ Heading', 'hkdev-shop-elements' ), 'default' => __( 'Product FAQ', 'hkdev-shop-elements' ) ],
				'product_not_found'    => [ 'label' => __( 'Product Not Found', 'hkdev-shop-elements' ), 'default' => __( 'Product not found.', 'hkdev-shop-elements' ) ],
				'select_variation'     => [ 'label' => __( 'Select Options Alert', 'hkdev-shop-elements' ), 'default' => __( 'Please select the product options first.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'add_to_cart_fail'     => [ 'label' => __( 'Add to Cart Error', 'hkdev-shop-elements' ), 'default' => __( 'Could not add product to cart. Try again.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'out_of_stock_alert'   => [ 'label' => __( 'Out of Stock Alert', 'hkdev-shop-elements' ), 'default' => __( 'This product is out of stock.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'server_error'         => [ 'label' => __( 'Server Error', 'hkdev-shop-elements' ), 'default' => __( 'Server error occurred. Please try again.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'complete_order'       => [ 'label' => __( 'Complete Order', 'hkdev-shop-elements' ), 'default' => __( 'Complete Order', 'hkdev-shop-elements' ) ],
				'play_video'           => [ 'label' => __( 'Play Video', 'hkdev-shop-elements' ), 'default' => __( 'Play video', 'hkdev-shop-elements' ) ],
				'product_video'        => [ 'label' => __( 'Product Video', 'hkdev-shop-elements' ), 'default' => __( 'Product video', 'hkdev-shop-elements' ) ],
			],
			'related'  => [
				'heading'      => [ 'label' => __( 'Heading', 'hkdev-shop-elements' ), 'default' => __( 'Related Products', 'hkdev-shop-elements' ) ],
				'empty'        => [ 'label' => __( 'Empty Text', 'hkdev-shop-elements' ), 'default' => __( 'No related products found for this item.', 'hkdev-shop-elements' ), 'multiline' => true ],
				'need_product' => [ 'label' => __( 'Needs Product Page', 'hkdev-shop-elements' ), 'default' => __( 'Related Products needs a product page (or a Product ID) to show items.', 'hkdev-shop-elements' ), 'multiline' => true ],
			],
			'minicart' => [
				'title'     => [ 'label' => __( 'Drawer Title', 'hkdev-shop-elements' ), 'default' => __( 'Your Cart', 'hkdev-shop-elements' ) ],
				'empty'     => [ 'label' => __( 'Empty Text', 'hkdev-shop-elements' ), 'default' => __( 'Your cart is empty.', 'hkdev-shop-elements' ) ],
				'continue'  => [ 'label' => __( 'Continue Shopping', 'hkdev-shop-elements' ), 'default' => __( 'Continue Shopping', 'hkdev-shop-elements' ) ],
				'subtotal'  => [ 'label' => __( 'Subtotal', 'hkdev-shop-elements' ), 'default' => __( 'Subtotal', 'hkdev-shop-elements' ) ],
				'view_cart' => [ 'label' => __( 'View Cart', 'hkdev-shop-elements' ), 'default' => __( 'View Cart', 'hkdev-shop-elements' ) ],
				'checkout'  => [ 'label' => __( 'Checkout', 'hkdev-shop-elements' ), 'default' => __( 'Checkout', 'hkdev-shop-elements' ) ],
			],
		];
	}

	/**
	 * Resolved label (saved value, else default).
	 *
	 * @param string $group Group key.
	 * @param string $key   Field key.
	 * @return string
	 */
	public static function text( $group, $key ) {
		$fields  = self::fields();
		$default = isset( $fields[ $group ][ $key ]['default'] ) ? (string) $fields[ $group ][ $key ]['default'] : '';
		$saved   = get_option( self::OPTION, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$value = isset( $saved[ $group ][ $key ] ) ? trim( (string) $saved[ $group ][ $key ] ) : '';

		return '' !== $value ? $value : $default;
	}

	/**
	 * Persist one group from Elementor settings (ui_{group}_{key}).
	 *
	 * @param string $group    Group key.
	 * @param array  $settings Widget settings.
	 * @return void
	 */
	public static function save_from_settings( $group, $settings ) {
		$fields = self::fields();

		if ( ! isset( $fields[ $group ] ) || ! is_array( $settings ) ) {
			return;
		}

		$next    = [];
		$has_any = false;

		foreach ( $fields[ $group ] as $key => $field ) {
			$control = 'ui_' . $group . '_' . $key;

			if ( isset( $settings[ $control ] ) ) {
				$has_any = true;
			}

			$value        = isset( $settings[ $control ] ) ? trim( wp_unslash( (string) $settings[ $control ] ) ) : '';
			$next[ $key ] = '' !== $value ? $value : $field['default'];
		}

		if ( ! $has_any ) {
			return;
		}

		self::save_group( $group, $next );
	}

	/**
	 * Persist a group map.
	 *
	 * @param string               $group Group key.
	 * @param array<string,string> $values Values.
	 * @return void
	 */
	public static function save_group( $group, $values ) {
		$saved = get_option( self::OPTION, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$saved[ $group ] = [];

		foreach ( $values as $key => $value ) {
			$saved[ $group ][ $key ] = sanitize_textarea_field( (string) $value );
		}

		update_option( self::OPTION, $saved, false );
	}

	/**
	 * Register an Elementor Content tab of text fields.
	 *
	 * @param \Elementor\Widget_Base $widget     Widget.
	 * @param string                 $group      Group key.
	 * @param string                 $section_id Section id.
	 * @param string                 $title      Section title.
	 * @return void
	 */
	public static function register_widget_section( $widget, $group, $section_id, $title ) {
		$fields = self::fields();

		if ( ! isset( $fields[ $group ] ) ) {
			return;
		}

		$widget->start_controls_section(
			$section_id,
			[
				'label' => $title,
			]
		);

		foreach ( $fields[ $group ] as $key => $field ) {
			$control = [
				'label'       => $field['label'],
				'type'        => ! empty( $field['multiline'] ) ? \Elementor\Controls_Manager::TEXTAREA : \Elementor\Controls_Manager::TEXT,
				'default'     => $field['default'],
				'label_block' => true,
			];

			if ( ! empty( $field['description'] ) ) {
				$control['description'] = $field['description'];
			}

			$widget->add_control(
				'ui_' . $group . '_' . $key,
				$control
			);
		}

		$widget->end_controls_section();
	}

	/**
	 * After Elementor save, copy widget texts into the option store.
	 *
	 * @param mixed $document Elementor document.
	 * @return void
	 */
	public static function sync_from_document( $document ) {
		if ( ! is_object( $document ) || ! method_exists( $document, 'get_elements_data' ) ) {
			return;
		}

		self::walk_elements( $document->get_elements_data() );
	}

	/**
	 * Walk Elementor tree for label widgets.
	 *
	 * @param array $elements Elements data.
	 * @return void
	 */
	private static function walk_elements( $elements ) {
		if ( ! is_array( $elements ) ) {
			return;
		}

		$map = [
			'hkdev_cart'          => [ 'cart' ],
			'hkdev_checkout'      => [ 'checkout', 'thankyou', 'modal' ],
			'hkdev_header'        => [ 'minicart' ],
			'hkdev_header_main'   => [ 'minicart' ],
			'hkdev_header_upper'  => [ 'minicart' ],
			'hkdev_header_bottom'     => [ 'minicart' ],
			'hkdev_shop_grid'         => [ 'variation' ],
			'hkdev_shop_carousel'     => [ 'variation' ],
			'hkdev_catalog'           => [ 'variation' ],
			'hkdev_related_products'  => [ 'variation', 'related' ],
			'hkdev_single_product'    => [ 'single' ],
		];

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$widget = isset( $element['widgetType'] ) ? (string) $element['widgetType'] : '';

			if ( isset( $map[ $widget ], $element['settings'] ) && is_array( $element['settings'] ) ) {
				foreach ( $map[ $widget ] as $group ) {
					self::save_from_settings( $group, $element['settings'] );
				}
			}

			if ( ! empty( $element['elements'] ) ) {
				self::walk_elements( $element['elements'] );
			}
		}
	}
}
