<?php
/**
 * HKDEV Account Engine (HKDEV Shop Elements plugin).
 *
 * Custom My Account page with orders, addresses, downloads, profile.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Account_Engine {

	const AJAX_UPDATE_PROFILE = 'hkdev_elements_account_update_profile';
	const AJAX_UPDATE_ADDRESS = 'hkdev_elements_account_update_address';
	const AJAX_CHANGE_PASSWORD = 'hkdev_elements_account_change_password';
	const NONCE_ACTION = 'hkdev_elements_account';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_UPDATE_PROFILE, [ $this, 'ajax_update_profile' ] );
		add_action( 'wp_ajax_' . self::AJAX_UPDATE_ADDRESS, [ $this, 'ajax_update_address' ] );
		add_action( 'wp_ajax_' . self::AJAX_CHANGE_PASSWORD, [ $this, 'ajax_change_password' ] );
	}

	public function account_shortcode( $atts = [] ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="hkdev-account-login-prompt"><p>' . esc_html__( 'Please login to view your account.', 'hkdev-shop-elements' ) . '</p><button type="button" class="hkdev-account-login-btn hkdev-auth-open" data-form="login">' . esc_html__( 'Login / Register', 'hkdev-shop-elements' ) . '</button></div>';
		}

		$atts = shortcode_atts(
			[
				'show_orders'    => 'yes',
				'show_addresses' => 'yes',
				'show_profile'   => 'yes',
				'show_downloads' => 'yes',
				'orders_per_page' => 10,
			],
			$atts,
			'hkdev_my_account'
		);

		$user = wp_get_current_user();
		ob_start();
		?>
		<div class="hkdev-account-wrap" id="hkdev-account-root">
			<div class="hkdev-account-sidebar">
				<div class="hkdev-account-user-card">
					<div class="hkdev-account-avatar">
						<?php echo get_avatar( $user->ID, 80 ); ?>
					</div>
					<div class="hkdev-account-user-info">
						<h4><?php echo esc_html( $user->display_name ); ?></h4>
						<span><?php echo esc_html( $user->user_email ); ?></span>
					</div>
				</div>
				<nav class="hkdev-account-nav">
					<ul>
						<li class="active"><a href="#hkdev-account-dashboard" data-tab="dashboard"><i class="fa-solid fa-gauge"></i> <?php esc_html_e( 'Dashboard', 'hkdev-shop-elements' ); ?></a></li>
						<li><a href="#hkdev-account-orders" data-tab="orders"><i class="fa-solid fa-box"></i> <?php esc_html_e( 'My Orders', 'hkdev-shop-elements' ); ?></a></li>
						<li><a href="#hkdev-account-addresses" data-tab="addresses"><i class="fa-solid fa-location-dot"></i> <?php esc_html_e( 'Addresses', 'hkdev-shop-elements' ); ?></a></li>
						<li><a href="#hkdev-account-profile" data-tab="profile"><i class="fa-solid fa-user-gear"></i> <?php esc_html_e( 'Profile', 'hkdev-shop-elements' ); ?></a></li>
						<li><a href="#hkdev-account-password" data-tab="password"><i class="fa-solid fa-lock"></i> <?php esc_html_e( 'Change Password', 'hkdev-shop-elements' ); ?></a></li>
						<?php if ( 'yes' === $atts['show_downloads'] ) : ?>
							<li><a href="#hkdev-account-downloads" data-tab="downloads"><i class="fa-solid fa-download"></i> <?php esc_html_e( 'Downloads', 'hkdev-shop-elements' ); ?></a></li>
						<?php endif; ?>
						<?php if ( class_exists( Wishlist_Engine::class ) ) : ?>
							<li><a href="#hkdev-account-wishlist" data-tab="wishlist"><i class="fa-solid fa-heart"></i> <?php esc_html_e( 'Wishlist', 'hkdev-shop-elements' ); ?> <span class="hkdev-account-wishlist-count"><?php echo esc_html( Wishlist_Engine::instance()->count() ); ?></span></a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><i class="fa-solid fa-right-from-bracket"></i> <?php esc_html_e( 'Logout', 'hkdev-shop-elements' ); ?></a></li>
					</ul>
				</nav>
			</div>
			<div class="hkdev-account-content">
				<div class="hkdev-account-tab active" id="hkdev-account-dashboard">
					<h3><?php esc_html_e( 'Dashboard', 'hkdev-shop-elements' ); ?></h3>
					<div class="hkdev-account-dashboard-stats">
						<div class="hkdev-account-stat">
							<i class="fa-solid fa-box"></i>
							<div>
								<span class="stat-number"><?php echo esc_html( $this->get_order_count( $user->ID ) ); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Total Orders', 'hkdev-shop-elements' ); ?></span>
							</div>
						</div>
						<div class="hkdev-account-stat">
							<i class="fa-solid fa-truck"></i>
							<div>
								<span class="stat-number"><?php echo esc_html( $this->get_order_count( $user->ID, [ 'wc-processing', 'wc-on-hold' ] ) ); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Processing', 'hkdev-shop-elements' ); ?></span>
							</div>
						</div>
						<div class="hkdev-account-stat">
							<i class="fa-solid fa-check-circle"></i>
							<div>
								<span class="stat-number"><?php echo esc_html( $this->get_order_count( $user->ID, [ 'wc-completed' ] ) ); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Completed', 'hkdev-shop-elements' ); ?></span>
							</div>
						</div>
					</div>
					<p><?php esc_html_e( 'Hello', 'hkdev-shop-elements' ); ?> <strong><?php echo esc_html( $user->display_name ); ?></strong>. <?php esc_html_e( 'From your account dashboard you can view your orders, manage your addresses, and edit your profile.', 'hkdev-shop-elements' ); ?></p>
				</div>

				<div class="hkdev-account-tab" id="hkdev-account-orders">
					<h3><?php esc_html_e( 'My Orders', 'hkdev-shop-elements' ); ?></h3>
					<?php echo $this->get_orders_html( $user->ID, $atts['orders_per_page'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="hkdev-account-tab" id="hkdev-account-addresses">
					<h3><?php esc_html_e( 'My Addresses', 'hkdev-shop-elements' ); ?></h3>
					<?php echo $this->get_addresses_html( $user->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="hkdev-account-tab" id="hkdev-account-profile">
					<h3><?php esc_html_e( 'Edit Profile', 'hkdev-shop-elements' ); ?></h3>
					<div class="hkdev-account-message" style="display:none;"></div>
					<form id="hkdev-profile-form" class="hkdev-account-form">
						<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_profile_nonce' ); ?>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'First Name', 'hkdev-shop-elements' ); ?></label>
							<input type="text" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" required>
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Last Name', 'hkdev-shop-elements' ); ?></label>
							<input type="text" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" required>
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Display Name', 'hkdev-shop-elements' ); ?></label>
							<input type="text" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required>
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Email', 'hkdev-shop-elements' ); ?></label>
							<input type="email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" required>
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></label>
							<input type="tel" name="phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ); ?>">
						</div>
						<button type="submit" class="hkdev-account-submit"><?php esc_html_e( 'Save Changes', 'hkdev-shop-elements' ); ?></button>
					</form>
				</div>

				<div class="hkdev-account-tab" id="hkdev-account-password">
					<h3><?php esc_html_e( 'Change Password', 'hkdev-shop-elements' ); ?></h3>
					<div class="hkdev-account-message" style="display:none;"></div>
					<form id="hkdev-password-form" class="hkdev-account-form">
						<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_password_nonce' ); ?>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Current Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
							<input type="password" name="current_password" required>
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'New Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
							<input type="password" name="new_password" required minlength="6">
						</div>
						<div class="hkdev-account-field">
							<label><?php esc_html_e( 'Confirm New Password', 'hkdev-shop-elements' ); ?> <span class="required">*</span></label>
							<input type="password" name="confirm_password" required>
						</div>
						<button type="submit" class="hkdev-account-submit"><?php esc_html_e( 'Change Password', 'hkdev-shop-elements' ); ?></button>
					</form>
				</div>

				<?php if ( 'yes' === $atts['show_downloads'] ) : ?>
					<div class="hkdev-account-tab" id="hkdev-account-downloads">
						<h3><?php esc_html_e( 'Downloads', 'hkdev-shop-elements' ); ?></h3>
						<?php echo $this->get_downloads_html( $user->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>

				<?php if ( class_exists( Wishlist_Engine::class ) ) : ?>
					<div class="hkdev-account-tab" id="hkdev-account-wishlist">
						<h3><?php esc_html_e( 'My Wishlist', 'hkdev-shop-elements' ); ?></h3>
						<?php echo Wishlist_Engine::instance()->wishlist_shortcode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'title'      => '',
								'show_count' => 'no',
								'columns'    => 3,
							]
						); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_order_count( $user_id, $statuses = [] ) {
		$args = [
			'customer_id' => $user_id,
			'limit'       => -1,
			'return'      => 'ids',
		];
		if ( ! empty( $statuses ) ) {
			$args['status'] = $statuses;
		}
		$orders = wc_get_orders( $args );
		return is_array( $orders ) ? count( $orders ) : 0;
	}

	private function get_orders_html( $user_id, $per_page = 10 ) {
		$orders = wc_get_orders( [
			'customer_id' => $user_id,
			'limit'       => $per_page,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		if ( empty( $orders ) ) {
			return '<p class="hkdev-account-no-data">' . esc_html__( 'No orders found.', 'hkdev-shop-elements' ) . '</p>';
		}

		ob_start();
		?>
		<div class="hkdev-account-orders-table">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Date', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Total', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'hkdev-shop-elements' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $order ) : ?>
						<tr>
							<td>#<?php echo esc_html( $order->get_order_number() ); ?></td>
							<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
							<td><span class="hkdev-order-status status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span></td>
							<td><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="hkdev-account-btn-small"><?php esc_html_e( 'View', 'hkdev-shop-elements' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_addresses_html( $user_id ) {
		$customer = new \WC_Customer( $user_id );
		$billing  = $customer->get_billing();
		$shipping = $customer->get_shipping();

		ob_start();
		?>
		<div class="hkdev-account-addresses-grid">
			<div class="hkdev-account-address-card">
				<h4><?php esc_html_e( 'Billing Address', 'hkdev-shop-elements' ); ?></h4>
				<div class="hkdev-account-message" style="display:none;"></div>
				<form class="hkdev-account-form hkdev-address-form" data-address-type="billing">
					<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_address_nonce' ); ?>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'First Name', 'hkdev-shop-elements' ); ?></label><input type="text" name="billing_first_name" value="<?php echo esc_attr( $billing['first_name'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Last Name', 'hkdev-shop-elements' ); ?></label><input type="text" name="billing_last_name" value="<?php echo esc_attr( $billing['last_name'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></label><input type="tel" name="billing_phone" value="<?php echo esc_attr( $billing['phone'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Email', 'hkdev-shop-elements' ); ?></label><input type="email" name="billing_email" value="<?php echo esc_attr( $billing['email'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Address', 'hkdev-shop-elements' ); ?></label><textarea name="billing_address_1" rows="3"><?php echo esc_textarea( $billing['address_1'] ); ?></textarea></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'City', 'hkdev-shop-elements' ); ?></label><input type="text" name="billing_city" value="<?php echo esc_attr( $billing['city'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Postcode', 'hkdev-shop-elements' ); ?></label><input type="text" name="billing_postcode" value="<?php echo esc_attr( $billing['postcode'] ); ?>"></div>
					<button type="submit" class="hkdev-account-submit"><?php esc_html_e( 'Save Billing Address', 'hkdev-shop-elements' ); ?></button>
				</form>
			</div>
			<div class="hkdev-account-address-card">
				<h4><?php esc_html_e( 'Shipping Address', 'hkdev-shop-elements' ); ?></h4>
				<div class="hkdev-account-message" style="display:none;"></div>
				<form class="hkdev-account-form hkdev-address-form" data-address-type="shipping">
					<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_address_nonce' ); ?>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'First Name', 'hkdev-shop-elements' ); ?></label><input type="text" name="shipping_first_name" value="<?php echo esc_attr( $shipping['first_name'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Last Name', 'hkdev-shop-elements' ); ?></label><input type="text" name="shipping_last_name" value="<?php echo esc_attr( $shipping['last_name'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></label><input type="tel" name="shipping_phone" value="<?php echo esc_attr( $shipping['phone'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Address', 'hkdev-shop-elements' ); ?></label><textarea name="shipping_address_1" rows="3"><?php echo esc_textarea( $shipping['address_1'] ); ?></textarea></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'City', 'hkdev-shop-elements' ); ?></label><input type="text" name="shipping_city" value="<?php echo esc_attr( $shipping['city'] ); ?>"></div>
					<div class="hkdev-account-field"><label><?php esc_html_e( 'Postcode', 'hkdev-shop-elements' ); ?></label><input type="text" name="shipping_postcode" value="<?php echo esc_attr( $shipping['postcode'] ); ?>"></div>
					<button type="submit" class="hkdev-account-submit"><?php esc_html_e( 'Save Shipping Address', 'hkdev-shop-elements' ); ?></button>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function get_downloads_html( $user_id ) {
		$downloads = wc_get_customer_available_downloads( $user_id );
		if ( empty( $downloads ) ) {
			return '<p class="hkdev-account-no-data">' . esc_html__( 'No downloads available.', 'hkdev-shop-elements' ) . '</p>';
		}
		ob_start();
		?>
		<div class="hkdev-account-downloads-table">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Downloads Remaining', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Download', 'hkdev-shop-elements' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $downloads as $download ) : ?>
						<tr>
							<td><?php echo esc_html( $download['product_name'] ); ?></td>
							<td><?php echo esc_html( $download['downloads_remaining'] ?: 'Unlimited' ); ?></td>
							<td><?php echo esc_html( $download['access_expires'] ? date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) ) : 'Never' ); ?></td>
							<td><a href="<?php echo esc_url( $download['download_url'] ); ?>" class="hkdev-account-btn-small"><i class="fa-solid fa-download"></i> <?php esc_html_e( 'Download', 'hkdev-shop-elements' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	public function ajax_update_profile() {
		check_ajax_referer( self::NONCE_ACTION, 'hkdev_profile_nonce' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You must be logged in.', 'hkdev-shop-elements' ) ] );
		}
		$first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please fill in all required fields.', 'hkdev-shop-elements' ) ] );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid email address.', 'hkdev-shop-elements' ) ] );
		}
		$email_owner = email_exists( $email );
		if ( $email_owner && $email_owner !== $user_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'This email is already in use.', 'hkdev-shop-elements' ) ] );
		}
		wp_update_user( [ 'ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'display_name' => $display_name, 'user_email' => $email ] );
		update_user_meta( $user_id, 'billing_phone', $phone );
		wp_send_json_success( [ 'message' => esc_html__( 'Profile updated successfully.', 'hkdev-shop-elements' ) ] );
	}

	public function ajax_update_address() {
		check_ajax_referer( self::NONCE_ACTION, 'hkdev_address_nonce' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You must be logged in.', 'hkdev-shop-elements' ) ] );
		}
		$type = isset( $_POST['address_type'] ) ? sanitize_text_field( wp_unslash( $_POST['address_type'] ) ) : 'billing';
		if ( ! in_array( $type, [ 'billing', 'shipping' ], true ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid address type.', 'hkdev-shop-elements' ) ] );
		}
		$fields = [ 'first_name', 'last_name', 'phone', 'email', 'address_1', 'city', 'postcode' ];
		foreach ( $fields as $field ) {
			$meta_key = $type . '_' . $field;
			$value    = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) : '';
			update_user_meta( $user_id, $meta_key, $value );
		}
		wp_send_json_success( [ 'message' => esc_html__( 'Address updated successfully.', 'hkdev-shop-elements' ) ] );
	}

	public function ajax_change_password() {
		check_ajax_referer( self::NONCE_ACTION, 'hkdev_password_nonce' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'You must be logged in.', 'hkdev-shop-elements' ) ] );
		}
		$current_password = isset( $_POST['current_password'] ) ? wp_unslash( $_POST['current_password'] ) : '';
		$new_password     = isset( $_POST['new_password'] ) ? wp_unslash( $_POST['new_password'] ) : '';
		$confirm_password = isset( $_POST['confirm_password'] ) ? wp_unslash( $_POST['confirm_password'] ) : '';

		$user = get_userdata( $user_id );
		if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Current password is incorrect.', 'hkdev-shop-elements' ) ] );
		}
		if ( strlen( $new_password ) < 6 ) {
			wp_send_json_error( [ 'message' => esc_html__( 'New password must be at least 6 characters.', 'hkdev-shop-elements' ) ] );
		}
		if ( $new_password !== $confirm_password ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Passwords do not match.', 'hkdev-shop-elements' ) ] );
		}
		wp_set_password( $new_password, $user_id );
		wp_set_auth_cookie( $user_id, true );
		wp_send_json_success( [ 'message' => esc_html__( 'Password changed successfully.', 'hkdev-shop-elements' ) ] );
	}
}

/**
 * Get order count by status for a user.
 *
 * @param int   $user_id  User ID.
 * @param array $statuses Order statuses to count.
 * @return int
 */
function hkdev_elements_get_order_count_by_status( $user_id, $statuses = [] ) {
	$args = [
		'customer_id' => $user_id,
		'limit'       => -1,
		'return'      => 'ids',
	];

	if ( ! empty( $statuses ) ) {
		$args['status'] = $statuses;
	}

	$orders = wc_get_orders( $args );
	return is_array( $orders ) ? count( $orders ) : 0;
}

class Account_Engine_Orders {

	/**
	 * Get orders HTML for a user.
	 *
	 * @param int $user_id   User ID.
	 * @param int $per_page  Orders per page.
	 * @return string
	 */
	public function get_orders_html( $user_id, $per_page = 10 ) {
		$orders = wc_get_orders( [
			'customer_id' => $user_id,
			'limit'       => $per_page,
			'orderby'     => 'date',
			'order'       => 'DESC',
		] );

		if ( empty( $orders ) ) {
			return '<p class="hkdev-account-no-data">' . esc_html__( 'No orders found.', 'hkdev-shop-elements' ) . '</p>';
		}

		ob_start();
		?>
		<div class="hkdev-account-orders-table">
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Date', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Total', 'hkdev-shop-elements' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'hkdev-shop-elements' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $order ) : ?>
						<tr>
							<td>#<?php echo esc_html( $order->get_order_number() ); ?></td>
							<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
							<td><span class="hkdev-order-status status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span></td>
							<td><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="hkdev-account-btn-small"><?php esc_html_e( 'View', 'hkdev-shop-elements' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}
}