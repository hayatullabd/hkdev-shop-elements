<?php
/**
 * HKDEV Checkout Engine (HKDEV Shop Elements plugin).
 *
 * Premium single-page WooCommerce checkout: order summary, shipping/rate
 * selection, dynamic totals (coupon/fees/BOGO), native hooks and a custom
 * order placement AJAX handler. Self-contained – works with ANY theme +
 * Elementor + WooCommerce.
 *
 * All AJAX actions/nonces are plugin-scoped (hkdev_elements_*) and each hook
 * callback bails out when the hkdev-shop theme controller is present, so this
 * engine never double-processes checkout when the theme is also active.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Checkout_Engine
 */
class Checkout_Engine {

	const AJAX_UPDATE   = 'hkdev_elements_co_update_cart';
	const AJAX_COUPON   = 'hkdev_elements_co_apply_coupon';
	const AJAX_RCOUPON  = 'hkdev_elements_co_remove_coupon';
	const AJAX_PLACE    = 'hkdev_elements_co_place_order';
	const AJAX_MODAL    = 'hkdev_elements_co_modal';
	const NONCE_ACTION  = 'hkdev_elements_co_place_order';
	const NONCE_MODAL   = 'hkdev_elements_co_modal';
	const NONCE_UPDATE  = 'hkdev_elements_co_update_cart';
	const NONCE_COUPON  = 'hkdev_elements_co_apply_coupon';
	const NONCE_RCOUPON = 'hkdev_elements_co_remove_coupon';
	const OPTION_NAME   = 'hkdev_elements_checkout_fields';

	/**
	 * DEFAULT FIELD CONFIGURATION (grouped).
	 *
	 * Every WooCommerce billing / shipping / order field can be enabled,
	 * disabled, relabelled or reordered from WP Admin → HKDEV Shop →
	 * Checkout Fields. 'enabled' is the default on/off state. The order of
	 * the arrays below is the default display order; the saved option may
	 * override it (drag & drop in the admin panel).
	 *
	 * @var array
	 */
	private $field_groups = [
		'billing' => [
			'billing_first_name' => [ 'enabled' => 'yes', 'label' => 'Full Name',           'placeholder' => 'e.g. Md. Abdullah',           'required' => 'yes' ],
			'billing_phone'      => [ 'enabled' => 'yes', 'label' => 'Mobile Number',       'placeholder' => '017XXXXXXXX',                 'required' => 'yes' ],
			'billing_email'      => [ 'enabled' => 'no',  'label' => 'Email Address',       'placeholder' => 'your@email.com',               'required' => 'yes' ],
			'billing_address_1'  => [ 'enabled' => 'yes', 'label' => 'Full Address',        'placeholder' => 'House, Road, Thana, District', 'required' => 'yes' ],
			'billing_address_2'  => [ 'enabled' => 'no',  'label' => 'Address Line 2',      'placeholder' => 'Flat / House no',             'required' => 'no' ],
			'billing_city'       => [ 'enabled' => 'no',  'label' => 'City',                'placeholder' => 'e.g. Dhaka',                  'required' => 'yes' ],
			'billing_state'      => [ 'enabled' => 'no',  'label' => 'District / State',    'placeholder' => 'e.g. Dhaka',                  'required' => 'no' ],
			'billing_postcode'   => [ 'enabled' => 'no',  'label' => 'Postcode / ZIP',      'placeholder' => 'e.g. 1200',                   'required' => 'no' ],
			'billing_country'    => [ 'enabled' => 'no',  'label' => 'Country',             'placeholder' => '',                             'required' => 'yes' ],
			'billing_last_name'  => [ 'enabled' => 'no',  'label' => 'Last Name',           'placeholder' => 'e.g. Rahman',                  'required' => 'no' ],
			'billing_company'    => [ 'enabled' => 'no',  'label' => 'Company Name',        'placeholder' => 'Company (optional)',           'required' => 'no' ],
		],
		'shipping' => [
			'shipping_first_name' => [ 'enabled' => 'no', 'label' => 'First Name',          'placeholder' => 'e.g. Md. Abdullah',           'required' => 'yes' ],
			'shipping_last_name'  => [ 'enabled' => 'no', 'label' => 'Last Name',           'placeholder' => 'e.g. Rahman',                  'required' => 'no' ],
			'shipping_company'    => [ 'enabled' => 'no', 'label' => 'Company Name',        'placeholder' => 'Company (optional)',           'required' => 'no' ],
			'shipping_phone'      => [ 'enabled' => 'no', 'label' => 'Mobile Number',       'placeholder' => '017XXXXXXXX',                 'required' => 'no' ],
			'shipping_address_1'  => [ 'enabled' => 'no', 'label' => 'Full Address',        'placeholder' => 'House, Road, Thana, District', 'required' => 'yes' ],
			'shipping_address_2'  => [ 'enabled' => 'no', 'label' => 'Address Line 2',      'placeholder' => 'Flat / House no',             'required' => 'no' ],
			'shipping_city'       => [ 'enabled' => 'no', 'label' => 'City',                'placeholder' => 'e.g. Dhaka',                  'required' => 'yes' ],
			'shipping_state'      => [ 'enabled' => 'no', 'label' => 'District / State',    'placeholder' => 'e.g. Dhaka',                  'required' => 'no' ],
			'shipping_postcode'   => [ 'enabled' => 'no', 'label' => 'Postcode / ZIP',      'placeholder' => 'e.g. 1200',                   'required' => 'no' ],
			'shipping_country'    => [ 'enabled' => 'no', 'label' => 'Country',             'placeholder' => '',                             'required' => 'yes' ],
		],
		'order' => [
			'order_comments'     => [ 'enabled' => 'no',  'label' => 'Order Notes',         'placeholder' => 'Notes about your order',       'required' => 'no' ],
		],
	];

	/**
	 * @var ?Checkout_Engine
	 */
	private static $instance = null;

	/**
	 * Pristine WooCommerce field set captured before any theme/plugin filter
	 * modifies it. Used to restore fields that another provider removed so the
	 * admin settings can re-enable them.
	 *
	 * @var ?array
	 */
	private $original_fields = null;

	/**
	 * When true, the checkout shortcode may render during an admin context.
	 * WordPress reports is_admin() as true for admin-ajax.php requests, so the
	 * modal AJAX handler sets this flag to render the checkout form for the
	 * shop's checkout popup.
	 *
	 * @var bool
	 */
	private $render_in_admin = false;

	/**
	 * Singleton.
	 *
	 * @return Checkout_Engine
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
		// Hide default WooCommerce coupon notice on checkout.
		add_action( 'init', function () {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		} );

		// Phone validation (BD format).
		add_action( 'woocommerce_checkout_process', [ $this, 'validate_bd_phone_number_mobile' ] );
		add_filter( 'woocommerce_checkout_posted_data', [ $this, 'sanitize_bd_phone_number_mobile' ] );

		// Capture the pristine WC field set before any theme/plugin modifies
		// it, so enabled fields can always be restored.
		add_filter( 'woocommerce_checkout_fields', [ $this, 'capture_original_checkout_fields' ], 0 );

		// Apply the admin Checkout Fields config last (theme/plugin filters
		// normally run at 9999) so the plugin settings always win.
		add_filter( 'woocommerce_checkout_fields', [ $this, 'custom_checkout_fields_filter' ], 100000 );

		// AJAX handlers (plugin-scoped).
		add_action( 'wp_ajax_' . self::AJAX_UPDATE, [ $this, 'co_ajax_update_cart_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_UPDATE, [ $this, 'co_ajax_update_cart_handler' ] );
		add_action( 'wp_ajax_' . self::AJAX_COUPON, [ $this, 'co_apply_coupon_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_COUPON, [ $this, 'co_apply_coupon_handler' ] );
		add_action( 'wp_ajax_' . self::AJAX_RCOUPON, [ $this, 'co_remove_coupon_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_RCOUPON, [ $this, 'co_remove_coupon_handler' ] );
		add_action( 'wp_ajax_' . self::AJAX_PLACE, [ $this, 'co_ajax_place_order_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_PLACE, [ $this, 'co_ajax_place_order_handler' ] );
		add_action( 'wp_ajax_' . self::AJAX_MODAL, [ $this, 'co_ajax_modal_html_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_MODAL, [ $this, 'co_ajax_modal_html_handler' ] );
	}

	/**
	 * Phone validation (BD format).
	 *
	 * @return void
	 */
	public function validate_bd_phone_number_mobile() {
		if ( isset( $_POST['billing_phone'] ) ) {
			$phone = trim( sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) );
			if ( ! preg_match( '/^(?:\+?88)?01[3-9]\d{8}$/', $phone ) ) {
				wc_add_notice( 'Invalid Phone Number', 'error' );
			}
		}
	}

	/**
	 * Phone sanitization (BD format).
	 *
	 * @param array $data Posted data.
	 * @return array
	 */
	public function sanitize_bd_phone_number_mobile( $data ) {
		if ( isset( $data['billing_phone'] ) ) {
			$data['billing_phone'] = preg_replace( '/[^0-9+]/', '', $data['billing_phone'] );
		}
		return $data;
	}

	/**
	 * Merge the saved option over the code defaults. Returns a grouped array:
	 * [ 'billing' => [ key => cfg ], 'shipping' => [...], 'order' => [...] ].
	 * The saved order (from drag & drop) is respected; keys missing from the
	 * saved option keep their default position.
	 *
	 * Legacy (flat) option data is migrated into the grouped format here.
	 *
	 * @return array
	 */
	public function get_field_config() {
		$saved = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		// Legacy migration: a flat option used to live under key => cfg.
		if ( ! isset( $saved['billing'] ) && ! isset( $saved['shipping'] ) ) {
			$flat = $saved;
			$saved = [];
			foreach ( $this->field_groups as $group => $defaults ) {
				$saved[ $group ] = [];
				foreach ( $defaults as $key => $default ) {
					if ( isset( $flat[ $key ] ) && is_array( $flat[ $key ] ) ) {
						$saved[ $group ][ $key ] = $flat[ $key ];
					}
				}
			}
		}

		$config = [];

		foreach ( $this->field_groups as $group => $defaults ) {
			$saved_group = ( isset( $saved[ $group ] ) && is_array( $saved[ $group ] ) ) ? $saved[ $group ] : [];

			// Walk the saved (ordered) keys first so drag & drop order wins.
			$config[ $group ] = [];
			foreach ( $saved_group as $key => $stored ) {
				if ( ! isset( $defaults[ $key ] ) || ! is_array( $stored ) ) {
					continue;
				}
				$config[ $group ][ $key ] = $this->merge_field_config( $defaults[ $key ], $stored );
			}
			// Append any defaults not present in the saved option.
			foreach ( $defaults as $key => $default ) {
				if ( ! isset( $config[ $group ][ $key ] ) ) {
					$config[ $group ][ $key ] = $this->merge_field_config( $default, [] );
				}
			}
		}

		return $config;
	}

	/**
	 * Merge one field's saved config over its defaults.
	 *
	 * @param array $default Defaults.
	 * @param array $stored  Saved values.
	 * @return array
	 */
	private function merge_field_config( $default, $stored ) {
		return [
			// Saved 'yes'/'no' wins; fall back to the default only when the
			// option was never set (or holds an unexpected value).
			'enabled'     => ( isset( $stored['enabled'] ) && in_array( $stored['enabled'], [ 'yes', 'no' ], true ) ) ? $stored['enabled'] : $default['enabled'],
			'label'       => ( isset( $stored['label'] ) && '' !== $stored['label'] ) ? $stored['label'] : $default['label'],
			'placeholder' => isset( $stored['placeholder'] ) ? $stored['placeholder'] : ( $default['placeholder'] ?? '' ),
			'required'    => ( isset( $stored['required'] ) && in_array( $stored['required'], [ 'yes', 'no' ], true ) ) ? $stored['required'] : 'no',
		];
	}

	/**
	 * Whether a checkout field is currently enabled.
	 *
	 * @param string $key Field key (e.g. billing_city / shipping_city).
	 * @return bool
	 */
	public function is_field_enabled( $key ) {
		$config = $this->get_field_config();
		foreach ( $config as $group ) {
			if ( isset( $group[ $key ] ) ) {
				return 'yes' === $group[ $key ]['enabled'];
			}
		}
		return false;
	}

	/**
	 * Option name used to persist the checkout field configuration.
	 *
	 * @return string
	 */
	public function config_option_name() {
		return self::OPTION_NAME;
	}

	/**
	 * Default, grouped field configuration (labels, placeholders, required).
	 *
	 * @return array
	 */
	public function field_defaults() {
		return $this->field_groups;
	}

	/**
	 * Snapshot the unmodified WooCommerce field set.
	 *
	 * Registered at priority 0 so every field keeps its pristine definition.
	 * Later used to restore fields that another provider (e.g. a theme) removed
	 * from the field set, so the admin settings can re-enable them.
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	public function capture_original_checkout_fields( $fields ) {
		if ( is_array( $fields ) ) {
			$this->original_fields = $fields;
		}
		return $fields;
	}

	/**
	 * WooCommerce checkout fields filter.
	 *
	 * Runs last (priority 100000) so the admin Checkout Fields settings always
	 * win over themes/plugins that also filter checkout fields. Enabled fields
	 * get the brand styling plus the customised label / placeholder / required
	 * flag and are re-ordered according to the drag & drop order; disabled
	 * fields are removed from the form completely and are never re-added.
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	public function custom_checkout_fields_filter( $fields ) {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}

		$config   = $this->get_field_config();
		$original = is_array( $this->original_fields ) ? $this->original_fields : [];

		foreach ( [ 'billing', 'shipping', 'order' ] as $section ) {
			if ( ! isset( $fields[ $section ] ) || ! is_array( $fields[ $section ] ) ) {
				$fields[ $section ] = [];
			}
			$section_cfg   = ( isset( $config[ $section ] ) && is_array( $config[ $section ] ) ) ? $config[ $section ] : [];
			$new_section   = [];
			$disabled_keys = [];
			$field_order   = 10;

			// Build the section in the configured (drag & drop) order. The
			// sequential priority keeps that order intact – WooCommerce
			// re-sorts each section by 'priority' after the filter runs.
			foreach ( $section_cfg as $field_key => $cfg ) {
				// Prefer the already-filtered definition, then fall back to the
				// pristine snapshot (covers fields a theme may have removed).
				$field = null;
				if ( isset( $fields[ $section ][ $field_key ] ) ) {
					$field = $fields[ $section ][ $field_key ];
				} elseif ( isset( $original[ $section ][ $field_key ] ) ) {
					$field = $original[ $section ][ $field_key ];
				}

				if ( 'no' === $cfg['enabled'] ) {
					$disabled_keys[ $field_key ] = true;
					continue;
				}

				// Recreate the field when no definition is available anymore.
				if ( ! is_array( $field ) ) {
					$field = $this->default_field_definition( $field_key, $cfg );
				}

				if ( '' !== $cfg['label'] ) {
					$field['label'] = $cfg['label'];
				}
				if ( isset( $cfg['placeholder'] ) ) {
					$field['placeholder'] = $cfg['placeholder'];
				}
				$field['required'] = ( 'yes' === $cfg['required'] );
				$field['class']    = [ 'hkdev-co-form-group' ];
				$field['priority'] = $field_order;

				$new_section[ $field_key ] = $field;
				$field_order               += 10;
			}

			// Keep every other field (3rd-party plugins) but NEVER re-add a
			// disabled one. Check the filtered set first, then the pristine
			// snapshot for fields another provider removed.
			$pool = [ $fields[ $section ] ];
			if ( isset( $original[ $section ] ) && is_array( $original[ $section ] ) ) {
				$pool[] = $original[ $section ];
			}
			foreach ( $pool as $source ) {
				foreach ( $source as $field_key => $field ) {
					if ( isset( $disabled_keys[ $field_key ] ) || isset( $new_section[ $field_key ] ) ) {
						continue;
					}
					$new_section[ $field_key ] = $field;
				}
			}

			$fields[ $section ] = $new_section;
		}

		return $fields;
	}

	/**
	 * Sensible field definition for a config-managed field that WooCommerce or
	 * another provider removed from the field set entirely.
	 *
	 * @param string $field_key Field key.
	 * @param array  $cfg       Field config.
	 * @return array
	 */
	private function default_field_definition( $field_key, $cfg ) {
		$type = 'text';
		if ( false !== strpos( $field_key, 'email' ) ) {
			$type = 'email';
		} elseif ( false !== strpos( $field_key, 'phone' ) ) {
			$type = 'tel';
		} elseif ( false !== strpos( $field_key, 'country' ) ) {
			$type = 'country';
		} elseif ( 'order_comments' === $field_key ) {
			$type = 'textarea';
		}

		return [
			'label'       => $cfg['label'],
			'placeholder' => $cfg['placeholder'],
			'required'    => ( 'yes' === $cfg['required'] ),
			'type'        => $type,
			'class'       => [ 'hkdev-co-form-group', 'form-row-wide' ],
			'priority'    => 95,
		];
	}

	/**
	 * Whether the current request is an Elementor editor / preview request.
	 *
	 * @return bool
	 */
	public function is_elementor_edit() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}
		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}
		if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}
		return false;
	}

	/**
	 * Build a synthetic email for guest checkouts that do not collect a billing
	 * email address. Uses the customer's phone number as the local part and the
	 * site domain, so the stored order email stays readable and traceable
	 * instead of a random "@order.local" placeholder.
	 *
	 * @param string $phone Customer phone number (digits only are kept).
	 * @return string
	 */
	private function guest_email( $phone = '' ) {
		$local = preg_replace( '/[^0-9]/', '', (string) $phone );
		if ( '' === $local ) {
			$local = 'guest';
		}

		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		$domain = preg_replace( '/^www\./i', '', (string) $domain );
		if ( '' === $domain ) {
			$domain = 'example.com';
		}

		return $local . '@' . $domain;
	}

	public function custom_checkout_shortcode() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 'WooCommerce plugin is not active.';
		}

		$is_editor = $this->is_elementor_edit();

		if ( is_admin() && ! $this->render_in_admin && ! $is_editor ) {
			return '';
		}

		ob_start();

		// --- A. THANK YOU PAGE ---
		$order_id = isset( $_GET['order-received'] ) ? absint( $_GET['order-received'] ) : absint( get_query_var( 'order-received' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $order_id && ! $is_editor ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				echo '<div class="hkdev-co-error-msg">Order Not Found</div>';
				return ob_get_clean();
			}

			// The order key (or ownership) must match, otherwise anyone could
			// read a customer's details by guessing a sequential order ID.
			$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : (string) get_query_var( 'key' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_owner  = is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id();
			if ( ! $is_owner && ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
				echo '<div class="hkdev-co-error-msg">Order Not Found</div>';
				return ob_get_clean();
			}

			$payment_method_title = $order->get_payment_method_title();
			if ( empty( $payment_method_title ) ) {
				$payment_method_title = 'Cash On Delivery';
			}

			// --- Logo (auto from Customizer, fallback to site name) ---
			$logo_html = '';
			if ( function_exists( 'get_custom_logo' ) && get_theme_mod( 'custom_logo' ) ) {
				$logo_html = get_custom_logo();
			}
			if ( '' === $logo_html ) {
				$logo_html = '<h2 class="hkdev-co-invoice-site-name">' . esc_html( get_bloginfo( 'name' ) ) . '</h2>';
			}

			$order_number = $order->get_order_number();
			?>
			<div class="hkdev-co-invoice fade-in" id="hkdev-co-invoice">
				<!-- Invoice header: logo + order info + print button -->
				<div class="hkdev-co-invoice-header">
					<div class="hkdev-co-invoice-brand">
						<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="hkdev-co-invoice-head-right">
						<span class="hkdev-co-invoice-title"><?php esc_html_e( 'INVOICE', 'hkdev-shop-elements' ); ?></span>
						<span class="hkdev-co-invoice-order-no">#<?php echo esc_html( $order_number ); ?></span>
						<span class="hkdev-co-invoice-status"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
					</div>
				</div>

				<div class="hkdev-co-invoice-top-row">
					<div class="hkdev-co-invoice-meta-grid">
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'From', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						</div>
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Order Date', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
						</div>
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Bill To', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></span>
						</div>
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Phone', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( $order->get_billing_phone() ? $order->get_billing_phone() : '—' ); ?></span>
						</div>
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Email', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( $order->get_billing_email() ? $order->get_billing_email() : '—' ); ?></span>
						</div>
						<div class="hkdev-co-invoice-meta-block">
							<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Payment', 'hkdev-shop-elements' ); ?></span>
							<span class="hkdev-co-invoice-meta-val"><?php echo esc_html( $payment_method_title ); ?></span>
						</div>
					</div>
					<div class="hkdev-co-invoice-print-wrap">
						<button type="button" class="hkdev-co-print-btn" onclick="window.print()">
							<i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Invoice', 'hkdev-shop-elements' ); ?>
						</button>
					</div>
				</div>

				<!-- Billing / Shipping addresses -->
				<div class="hkdev-co-invoice-address-row">
					<div class="hkdev-co-invoice-address-block">
						<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Billing Address', 'hkdev-shop-elements' ); ?></span>
						<span class="hkdev-co-invoice-meta-val"><?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'hkdev-shop-elements' ) ) ); ?></span>
					</div>
					<?php if ( $order->get_formatted_shipping_address() ) : ?>
					<div class="hkdev-co-invoice-address-block">
						<span class="hkdev-co-invoice-meta-label"><?php esc_html_e( 'Shipping Address', 'hkdev-shop-elements' ); ?></span>
						<span class="hkdev-co-invoice-meta-val"><?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?></span>
					</div>
					<?php endif; ?>
				</div>

				<!-- Items table -->
				<table class="hkdev-co-invoice-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Item', 'hkdev-shop-elements' ); ?></th>
							<th class="col-qty"><?php esc_html_e( 'Qty', 'hkdev-shop-elements' ); ?></th>
							<th class="col-total"><?php esc_html_e( 'Total', 'hkdev-shop-elements' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
						<tr>
							<td>
								<?php
								$product   = $item->get_product();
								$item_name = apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );
								echo wp_kses_post( $item_name );
								if ( $product && $product->get_sku() ) :
									?>
									<span class="hkdev-co-invoice-sku"><?php esc_html_e( 'SKU:', 'hkdev-shop-elements' ); ?> <?php echo esc_html( $product->get_sku() ); ?></span>
								<?php endif; ?>
							</td>
							<td class="col-qty"><?php echo esc_html( $item->get_quantity() ); ?></td>
							<td class="col-total"><?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Totals -->
				<div class="hkdev-co-invoice-totals">
					<div class="hkdev-co-invoice-total-row"><span><?php esc_html_e( 'Subtotal', 'hkdev-shop-elements' ); ?></span><strong><?php echo $order->get_subtotal_to_display(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
					<?php foreach ( $order->get_items( 'coupon' ) as $coupon_item ) : ?>
					<div class="hkdev-co-invoice-total-row is-discount"><span><i class="fa-solid fa-tag"></i> <?php esc_html_e( 'Coupon:', 'hkdev-shop-elements' ); ?> <?php echo esc_html( $coupon_item->get_name() ); ?></span><strong>-<?php echo wc_price( $coupon_item->get_discount() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
					<?php endforeach; ?>
					<?php foreach ( $order->get_fees() as $fee ) : ?>
					<div class="hkdev-co-invoice-total-row is-fee"><span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->get_name() ); ?></span><strong><?php echo wc_price( $fee->get_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
					<?php endforeach; ?>
					<div class="hkdev-co-invoice-total-row"><span><?php esc_html_e( 'Shipping', 'hkdev-shop-elements' ); ?></span><strong><?php echo $order->get_shipping_to_display(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
					<div class="hkdev-co-invoice-total-row grand-total"><span><?php esc_html_e( 'Grand Total', 'hkdev-shop-elements' ); ?></span><strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>
				</div>

				<!-- Footer -->
				<div class="hkdev-co-invoice-footer">
					<p class="hkdev-co-invoice-thanks"><i class="fa-solid fa-circle-check"></i> <?php esc_html_e( 'Thank you for shopping with us!', 'hkdev-shop-elements' ); ?></p>
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="hkdev-co-shop-more-btn"><?php esc_html_e( 'Continue Shopping', 'hkdev-shop-elements' ); ?></a>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		// --- B. EMPTY CART (skipped inside the Elementor editor so the widget
		// can be previewed / laid out even with an empty cart) ---
		if ( ! $is_editor && ( ! WC()->cart || WC()->cart->is_empty() ) ) {
			?>
			<div class="hkdev-co-empty-cart-wrap">
				<div class="empty-icon-circle"><i class="fa-solid fa-cart-arrow-down"></i></div>
				<h3><?php esc_html_e( 'Your Cart is Empty', 'hkdev-shop-elements' ); ?></h3>
				<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="hkdev-co-confirm-btn" style="display: inline-block; width: auto; padding: 15px 40px;"><?php esc_html_e( 'Start Shopping', 'hkdev-shop-elements' ); ?></a>
			</div>
			<?php
			return ob_get_clean();
		}

		// --- C. INITIALIZE TOTALS ---
		if ( WC()->cart ) {
			WC()->cart->calculate_totals();
			WC()->cart->calculate_shipping();
		}

		$guest_email = $this->guest_email();
		?>
		<!-- --- MAIN CHECKOUT UI --- -->
		<div class="hkdev-co-container" id="hkdev-co-root">
			<?php do_action( 'woocommerce_before_checkout_form', WC()->checkout() ); ?>

			<form id="hkdev-co-process-order" class="woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" method="post">
				<div class="hkdev-co-checkout-grid">

					<!-- LEFT COLUMN: Order review -> Shipping address -> Billing address -->
					<div class="checkout-section left-column">

						<div class="hkdev-co-section-card">
							<div class="hkdev-co-card-header">
								<span class="hkdev-co-step-icon"><i class="fa-solid fa-basket-shopping"></i></span>
								<h3><?php esc_html_e( 'Order Review', 'hkdev-shop-elements' ); ?></h3>
							</div>

							<div id="hkdev-co-items-ajax">
								<?php echo $this->co_get_items_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>

						<div class="hkdev-co-section-card">
							<div class="hkdev-co-card-header">
								<span class="hkdev-co-step-icon"><i class="fa-solid fa-file-invoice"></i></span>
								<h3><?php esc_html_e( 'Billing Address', 'hkdev-shop-elements' ); ?></h3>
							</div>

							<div class="form-body-wrap hkdev-co-form-area">
								<?php
								$field_cfg = $this->get_field_config();
								$checkout  = WC()->checkout();

								// Billing fields (configured order from settings).
								$billing_fields = $checkout->get_checkout_fields( 'billing' );
								foreach ( $billing_fields as $key => $field ) {
									woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
								}
								?>
								<?php if ( isset( $field_cfg['billing']['billing_email'] ) && 'no' === $field_cfg['billing']['billing_email']['enabled'] ) : ?>
									<input type="hidden" name="billing_email" value="<?php echo esc_attr( $guest_email ); ?>">
								<?php endif; ?>
							</div>
						</div>

						<?php
						$ship_rows = ( isset( $field_cfg['shipping'] ) && is_array( $field_cfg['shipping'] ) ) ? $field_cfg['shipping'] : [];
						$ship_on   = false;
						foreach ( $ship_rows as $cfg ) {
							if ( 'yes' === $cfg['enabled'] ) {
								$ship_on = true;
								break;
							}
						}

						// "Billing = Delivery" mode: never render the separate
						// shipping address section.
						if ( 'yes' === get_option( 'hkdev_elements_billing_as_delivery', 'no' ) ) {
							$ship_on = false;
						}
						$ship_fields = [];
						if ( $ship_on ) {
							foreach ( $checkout->get_checkout_fields( 'shipping' ) as $ship_key => $ship_field ) {
								if ( isset( $field_cfg['shipping'][ $ship_key ] ) && 'yes' !== $field_cfg['shipping'][ $ship_key ]['enabled'] ) {
									continue;
								}
								$ship_fields[ $ship_key ] = $ship_field;
							}
						}
						?>
						<?php if ( ! empty( $ship_fields ) ) : ?>
							<div class="hkdev-co-section-card">
								<div class="hkdev-co-shipping-acc">
									<button type="button" class="hkdev-co-shipping-acc-head" aria-expanded="false">
										<span class="hkdev-co-acc-icon"><i class="fa-solid fa-truck-fast"></i></span>
										<span class="hkdev-co-acc-title"><?php esc_html_e( 'Shipping Address', 'hkdev-shop-elements' ); ?></span>
										<span class="hkdev-co-acc-arrow"><i class="fa-solid fa-chevron-down"></i></span>
									</button>
									<div class="hkdev-co-shipping-acc-body">
										<?php
										foreach ( $ship_fields as $key => $field ) {
											woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
										}
										?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- RIGHT COLUMN: Payment -> Coupon -> Delivery -> Total -> Notes -> Confirm -->
					<div class="checkout-section right-column">

						<div class="hkdev-co-section-card">
							<div class="hkdev-co-card-header">
								<span class="hkdev-co-step-icon"><i class="fa-regular fa-credit-card"></i></span>
								<h3><?php esc_html_e( 'Payment Method', 'hkdev-shop-elements' ); ?></h3>
							</div>

							<div id="hkdev-co-payment-ajax">
								<?php echo $this->co_get_payment_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>

						<?php if ( 'yes' === get_option( 'hkdev_elements_show_coupon', 'yes' ) ) : ?>
						<div class="hkdev-co-section-card">
							<div class="hkdev-co-shipping-acc">
								<button type="button" class="hkdev-co-shipping-acc-head" aria-expanded="false">
									<span class="hkdev-co-acc-icon"><i class="fa-solid fa-ticket"></i></span>
									<span class="hkdev-co-acc-title"><?php esc_html_e( 'Have any coupon or gift voucher?', 'hkdev-shop-elements' ); ?></span>
									<span class="hkdev-co-acc-arrow"><i class="fa-solid fa-chevron-down"></i></span>
								</button>
								<div class="hkdev-co-shipping-acc-body">
									<div id="hkdev-co-coupon-ajax">
										<?php echo $this->co_get_coupon_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								</div>
							</div>
						</div>
						<?php endif; ?>

						<div class="hkdev-co-section-card">
							<div id="hkdev-co-delivery-ajax">
								<?php echo $this->co_get_delivery_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>

						<div class="hkdev-co-section-card">
							<div class="hkdev-co-card-header">
								<span class="hkdev-co-step-icon"><i class="fa-solid fa-receipt"></i></span>
								<h3><?php esc_html_e( 'Order Total', 'hkdev-shop-elements' ); ?></h3>
							</div>

							<div id="hkdev-co-totals-ajax">
								<?php echo $this->co_get_totals_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>

						<?php
						$order_fields = $checkout->get_checkout_fields( 'order' );
						$notes_on     = isset( $field_cfg['order']['order_comments'] ) && 'yes' === $field_cfg['order']['order_comments']['enabled'];
						?>
						<?php if ( $notes_on ) : ?>
							<div class="hkdev-co-section-card">
								<div class="hkdev-co-card-header">
									<span class="hkdev-co-step-icon"><i class="fa-solid fa-pen"></i></span>
									<h3><?php esc_html_e( 'Special Notes (Optional)', 'hkdev-shop-elements' ); ?></h3>
								</div>

								<div class="hkdev-co-notes-wrap">
									<?php
									foreach ( $order_fields as $key => $field ) {
										if ( 'order_comments' === $key ) {
											woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
										}
									}
									?>
								</div>
							</div>
						<?php endif; ?>

						<?php
						$terms_url   = wc_get_page_permalink( 'terms' );
						$privacy_url = get_privacy_policy_url();
						?>
						<div class="hkdev-co-terms-row">
							<label class="hkdev-co-terms-label" for="hkdev-co-terms">
								<input type="checkbox" id="hkdev-co-terms" name="hkdev_terms" value="1">
								<span class="hkdev-co-terms-text">
									<?php
									printf(
										wp_kses_post( __( 'I have read and agree to the <a href="%1$s" target="_blank" rel="noopener">Terms and Conditions</a>, <a href="%2$s" target="_blank" rel="noopener">Privacy Policy</a> &amp; <a href="%3$s" target="_blank" rel="noopener">Refund and Return Policy</a>.', 'hkdev-shop-elements' ) ),
										esc_url( $terms_url ? $terms_url : '#' ),
										esc_url( $privacy_url ? $privacy_url : '#' ),
										esc_url( $terms_url ? $terms_url : '#' )
									);
									?>
								</span>
							</label>
						</div>

						<div class="hkdev-co-submit-area">
							<button type="submit" id="hkdev-co-submit-btn" class="hkdev-co-confirm-btn">
								<i class="fa-solid fa-lock" style="margin-right: 8px;"></i> <?php esc_html_e( 'Confirm Order', 'hkdev-shop-elements' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div style="display:none;">
					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>

				<input type="hidden" name="action" value="<?php echo esc_attr( self::AJAX_PLACE ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, 'hkdev_co_nonce' ); ?>
				<input type="hidden" id="hkdev_co_update_nonce" value="<?php echo esc_attr( wp_create_nonce( self::NONCE_UPDATE ) ); ?>">
			</form>

			<?php do_action( 'woocommerce_after_checkout_form', WC()->checkout() ); ?>

			<div id="hkdev-co-toast" class="hkdev-co-toast"><div class="toast-icon"><i class="fa-solid fa-circle-check"></i></div><div class="toast-msg">Message</div></div>
			<div id="hkdev-co-global-loader"><div class="loader-box-inner"><i class="fa-solid fa-circle-notch fa-spin"></i> Processing...</div></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Mini cart HTML (used for fragment refresh after AJAX).
	 *
	 * @return string
	 */
	public function mc_get_cart_html() {
		ob_start();
		woocommerce_mini_cart();
		return ob_get_clean();
	}

	/**
	 * Checkout order summary items HTML.
	 *
	 * @return string
	 */
	public function co_get_items_html() {
		ob_start();
		?>
		<div class="hkdev-co-summary-items-list">
			<?php
			foreach ( WC()->cart->get_cart() as $key => $item ) :
				$_prod     = $item['data'];
				$item_name = apply_filters( 'woocommerce_cart_item_name', $_prod->get_name(), $item, $key );
				$line_total = apply_filters( 'woocommerce_cart_item_subtotal', wc_price( $item['line_total'] ), $item, $key );
				?>
				<div class="hkdev-co-summary-item" data-key="<?php echo esc_attr( $key ); ?>" data-free-count="<?php echo esc_attr( intval( $item['hkdev_free_count'] ?? 0 ) ); ?>">
					<div class="item-img-box"><?php echo wp_kses_post( $_prod->get_image() ); ?></div>
					<div class="item-meta">
						<h4 class="item-name"><?php echo esc_html( $item_name ); ?></h4>
						<div class="item-price-bottom"><span class="item-price-val"><?php echo wp_kses_post( $line_total ); ?></span></div>
						<div class="hkdev-co-item-qty-row">
							<div class="hkdev-co-qty-stepper-ui">
								<button type="button" class="hkdev-co-qty-mod" data-act="minus">&minus;</button>
								<span class="hkdev-co-qty-val"><?php echo esc_html( $item['quantity'] ); ?></span>
								<button type="button" class="hkdev-co-qty-mod" data-act="plus">+</button>
							</div>
							<button type="button" class="hkdev-co-item-remove-trigger" title="<?php esc_attr_e( 'Remove', 'hkdev-shop-elements' ); ?>"><i class="fa-regular fa-trash-can"></i></button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$hkdev_total_qty  = WC()->cart->get_cart_contents_count();
		$hkdev_total_free = function_exists( 'hkdev_get_total_free_items_in_cart' ) ? hkdev_get_total_free_items_in_cart() : 0;
		$hkdev_paid_qty   = max( 0, $hkdev_total_qty - $hkdev_total_free );
		?>
		<div class="hkdev-co-items-count-summary">
			<span class="paid-count"><?php esc_html_e( 'Paid Items', 'hkdev-shop-elements' ); ?>: <strong><?php echo esc_html( $hkdev_paid_qty ); ?></strong></span>
			<span class="separator"> | </span>
			<span class="free-count"><?php esc_html_e( 'Free items', 'hkdev-shop-elements' ); ?>: <strong><?php echo esc_html( $hkdev_total_free ); ?></strong></span>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Coupon / voucher section HTML (own checkout section).
	 *
	 * @return string
	 */
	public function co_get_coupon_html() {
		ob_start();
		?>
		<div class="hkdev-co-coupon-wrap">
			<div class="hkdev-co-coupon-box">
				<i class="fa-solid fa-ticket"></i>
				<input type="text" id="hkdev-co-coupon-code" name="coupon_code" placeholder="<?php esc_attr_e( 'Enter coupon code', 'hkdev-shop-elements' ); ?>">
				<button type="button" id="hkdev-co-apply-coupon"><?php esc_html_e( 'Apply', 'hkdev-shop-elements' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Payment method section HTML (own checkout section).
	 *
	 * @return string
	 */
	public function co_get_payment_html() {
		ob_start();
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$chosen   = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : ( ! empty( $gateways ) ? array_keys( $gateways )[0] : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		?>
		<div class="hkdev-co-payment-method-selector-wrap">
			<div class="hkdev-co-payment-pill-list">
				<?php
				if ( ! empty( $gateways ) ) {
					foreach ( $gateways as $id => $gateway ) :
						?>
						<label class="hkdev-co-pay-pill-box <?php echo $id === $chosen ? 'active' : ''; ?>">
							<input type="radio" name="payment_method" value="<?php echo esc_attr( $id ); ?>" <?php checked( $id, $chosen ); ?>>
							<div class="pay-radio-icon"><i class="fa-solid fa-circle-check"></i></div>
							<span><?php echo esc_html( $gateway->get_title() ); ?></span>
						</label>
						<?php
					endforeach;
				} else {
					echo '<p>' . esc_html__( 'No payment methods available.', 'hkdev-shop-elements' ) . '</p>';
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Checkout totals + payment method HTML.
	 *
	 * @return string
	 */
	public function co_get_totals_html() {
		ob_start();
		?>
		<div class="hkdev-co-calculation-wrap">
			<div class="hkdev-co-calc-line"><span><?php esc_html_e( 'Subtotal', 'hkdev-shop-elements' ); ?></span><strong><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<div class="hkdev-co-calc-line coupon-line">
					<span class="coupon-title"><i class="fa-solid fa-tag"></i> <?php esc_html_e( 'Coupon', 'hkdev-shop-elements' ); ?>: <?php echo esc_html( $code ); ?></span>
					<span class="coupon-val">
						<strong>-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						<a href="#" class="hkdev-co-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>"><i class="fa-solid fa-xmark"></i></a>
					</span>
				</div>
			<?php endforeach; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<div class="hkdev-co-calc-line fee-line" style="color: #03a550;">
					<span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->name ); ?></span>
					<strong><?php echo wc_price( $fee->total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</div>
			<?php endforeach; ?>

			<div class="hkdev-co-calc-line"><span><?php esc_html_e( 'Shipping', 'hkdev-shop-elements' ); ?></span><strong><?php echo wc_price( WC()->cart->get_shipping_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></div>

			<div class="hkdev-co-calc-line grand-total-line">
				<span><?php esc_html_e( 'GRAND TOTAL', 'hkdev-shop-elements' ); ?></span>
				<strong class="total-amt"><?php echo WC()->cart->get_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Delivery option list.
	 *
	 * Starts from the rates WooCommerce calculated for the current destination
	 * (real prices) and then adds every delivery charge configured on any
	 * shipping zone, so the list stays stable and only the checkmark follows
	 * the customer's district.
	 *
	 * Deliberately defensive: free shipping / local pickup are skipped, charges
	 * without a resolvable price are left out, duplicate names are merged, the
	 * list is capped and any unexpected zone setup falls back to the live rates
	 * instead of breaking the checkout.
	 *
	 * @param array $rates Rates available for the current destination.
	 * @return array
	 */
	private function co_delivery_options( $rates ) {
		$options = [];

		foreach ( $rates as $rate_id => $rate ) {
			$options[ $rate_id ] = [
				'label' => $rate->label,
				'cost'  => $rate->cost,
			];
		}

		$skip = [ 'free_shipping', 'local_pickup' ];

		try {
			if ( class_exists( '\WC_Shipping_Zones' ) ) {
				foreach ( \WC_Shipping_Zones::get_zones() as $zone_data ) {
					$zone = \WC_Shipping_Zones::get_zone( $zone_data['zone_id'] );
					if ( ! $zone ) {
						continue;
					}
					foreach ( $zone->get_shipping_methods( true ) as $method ) {
						if ( ! isset( $method->id ) || in_array( $method->id, $skip, true ) ) {
							continue;
						}
						$key = $method->id . ':' . $method->instance_id;
						if ( isset( $options[ $key ] ) ) {
							continue;
						}
						$cost = $this->co_delivery_method_cost( $method );
						if ( null === $cost ) {
							continue;
						}
						$options[ $key ] = [
							'label' => $method->get_title(),
							'cost'  => $cost,
						];
					}
				}
			}
		} catch ( \Throwable $e ) {
			// Never let an unusual shipping setup break the checkout.
			$options = [];

			foreach ( $rates as $rate_id => $rate ) {
				$options[ $rate_id ] = [
					'label' => $rate->label,
					'cost'  => $rate->cost,
				];
			}
		}

		// One entry per charge name (the same charge usually exists in several
		// zones) and a sane cap so a large zone setup stays tidy.
		$seen     = [];
		$filtered = [];
		foreach ( $options as $rate_id => $option ) {
			$label_key = strtolower( trim( wp_strip_all_tags( (string) $option['label'] ) ) );
			if ( '' !== $label_key && isset( $seen[ $label_key ] ) ) {
				continue;
			}
			$seen[ $label_key ]    = true;
			$filtered[ $rate_id ]  = $option;
			if ( count( $filtered ) >= 12 ) {
				break;
			}
		}

		return $filtered;
	}

	/**
	 * Configured (static) price of a shipping method instance.
	 *
	 * @param \WC_Shipping_Method $method Method instance.
	 * @return float|null Null when no static price is configured.
	 */
	private function co_delivery_method_cost( $method ) {
		$settings = ( isset( $method->instance_settings ) && is_array( $method->instance_settings ) ) ? $method->instance_settings : [];

		if ( isset( $settings['cost'] ) && is_numeric( $settings['cost'] ) ) {
			return (float) $settings['cost'];
		}

		if ( method_exists( $method, 'get_option' ) ) {
			$cost = $method->get_option( 'cost' );
			if ( is_numeric( $cost ) ) {
				return (float) $cost;
			}
		}

		return null;
	}

	/**
	 * Delivery area / shipping rate selection HTML.
	 *
	 * Renders the available shipping rates for the customer's current
	 * destination. The same HTML is returned by the cart AJAX handler so the
	 * list (and its fees) can be refreshed when the billing/shipping state or
	 * another address field changes on the checkout form.
	 *
	 * @return string
	 */
	public function co_get_delivery_html() {
		$packages        = ( WC()->shipping() ) ? WC()->shipping()->get_packages() : [];
		$rates           = $packages[0]['rates'] ?? [];
		$chosen_methods  = ( WC()->session ) ? (array) WC()->session->get( 'chosen_shipping_methods' ) : [];
		$chosen_shipping = $chosen_methods[0] ?? '';

		// When the session's chosen rate is no longer available for the current
		// destination, fall back to the first available rate – this mirrors what
		// the cart uses for the shipping total, so a radio is always selected
		// and the submitted method matches the displayed fee.
		if ( '' === $chosen_shipping || ! isset( $rates[ $chosen_shipping ] ) ) {
			$chosen_shipping = ! empty( $rates ) ? (string) array_key_first( $rates ) : '';
		}

		$options = $this->co_delivery_options( $rates );

		ob_start();
		?>
		<div class="hkdev-co-delivery-selection-wrap">
			<div class="hkdev-co-card-header mini-header">
				<span class="hkdev-co-step-icon mini"><i class="fa-solid fa-truck-fast"></i></span>
				<h4><?php esc_html_e( 'Delivery Area', 'hkdev-shop-elements' ); ?></h4>
			</div>
			<div class="radio-stack-custom">
				<?php foreach ( $options as $rate_id => $option ) : $active = ( $chosen_shipping === $rate_id ) ? 'active' : ''; ?>
					<label class="hkdev-co-radio-row <?php echo esc_attr( $active ); ?>">
						<input type="radio" name="shipping_method[0]" value="<?php echo esc_attr( $rate_id ); ?>" <?php checked( $chosen_shipping, $rate_id ); ?>>
						<div class="hkdev-co-radio-box-ui">
							<div class="hkdev-co-radio-indicator"><i class="fa-solid fa-check"></i></div>
							<span class="label-text"><?php echo esc_html( $option['label'] ); ?></span>
							<span class="price-text"><?php echo wc_price( $option['cost'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: render the checkout form HTML for the shop's checkout modal.
	 *
	 * Reuses the exact same markup the checkout page shortcode outputs (same
	 * nonces, same field config, same shipping/payment selectors), so the
	 * existing checkout.js handlers power the popup form too.
	 *
	 * @return void
	 */
	public function co_ajax_modal_html_handler() {
		nocache_headers();
		check_ajax_referer( self::NONCE_MODAL, 'security' );

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( [ 'message' => 'Cart is empty' ] );
		}

		if ( ob_get_length() ) {
			ob_clean();
		}

		$this->render_in_admin = true;
		$html                  = $this->custom_checkout_shortcode();
		$this->render_in_admin = false;

		if ( '' === trim( $html ) ) {
			wp_send_json_error( [ 'message' => 'Could not render checkout.' ] );
		}

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * AJAX: update cart (qty/remove) + chosen shipping.
	 *
	 * @return void
	 */
	public function co_ajax_update_cart_handler() {
		nocache_headers();
		check_ajax_referer( self::NONCE_UPDATE, 'security' );

		if ( isset( $_POST['type'] ) ) {
			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
			if ( 'qty' === $type && isset( $_POST['key'], $_POST['qty'] ) ) {
				WC()->cart->set_quantity( sanitize_key( $_POST['key'] ), absint( $_POST['qty'] ), true );
			} elseif ( 'remove' === $type && isset( $_POST['key'] ) ) {
				WC()->cart->remove_cart_item( sanitize_key( $_POST['key'] ) );
			}
		}
		if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) {
			$sanitized = array_map( 'sanitize_text_field', wp_unslash( $_POST['shipping_method'] ) );
			WC()->session->set( 'chosen_shipping_methods', $sanitized );
		}

		// Update the customer's billing/shipping address from the posted
		// checkout fields so state/city/postcode based delivery fees are
		// recalculated on the server for the new destination.
		$this->update_customer_address_from_post();

		// Calculate shipping before totals (same order as core
		// WC_AJAX::update_order_review) so the new rates drive the totals.
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
		if ( WC()->cart->is_empty() ) {
			wp_send_json_success( [ 'cart_empty' => true ] );
		}

		if ( ob_get_length() ) {
			ob_clean();
		}

		wp_send_json_success(
			[
				'items_html'         => $this->co_get_items_html(),
				'totals_html'        => $this->co_get_totals_html(),
				'delivery_html'      => $this->co_get_delivery_html(),
				'cart_count'         => WC()->cart->get_cart_contents_count(),
				'minicart_body_html' => '<div class="hkdev-mini-cart-body">' . $this->mc_get_cart_html() . '</div>',
			]
		);
	}

	/**
	 * Sync the WC customer's billing/shipping address from the posted checkout
	 * fields before recalculating totals.
	 *
	 * The custom checkout posts the real field names (billing_state,
	 * billing_city, ...) directly, so those are written to the customer
	 * session. When the separate shipping section is disabled the shipping
	 * destination mirrors billing – the same behaviour used at order creation.
	 *
	 * @return void
	 */
	private function update_customer_address_from_post() {
		$post     = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$customer = WC()->customer;
		// A disabled checkout field is absent from the form and therefore from
		// $_POST. Fall back to the customer's current value so it is never
		// wiped to null (which would also wipe the state-based delivery fee).
		$clean = static function ( $key, $current ) use ( $post ) {
			return ( isset( $post[ $key ] ) && '' !== $post[ $key ] ) ? wc_clean( $post[ $key ] ) : $current;
		};

		$customer->set_props(
			[
				'billing_country'   => $clean( 'billing_country', $customer->get_billing_country() ),
				'billing_state'     => $clean( 'billing_state', $customer->get_billing_state() ),
				'billing_postcode'  => $clean( 'billing_postcode', $customer->get_billing_postcode() ),
				'billing_city'      => $clean( 'billing_city', $customer->get_billing_city() ),
				'billing_address_1' => $clean( 'billing_address_1', $customer->get_billing_address_1() ),
				'billing_address_2' => $clean( 'billing_address_2', $customer->get_billing_address_2() ),
			]
		);

		// Use the separate shipping fields only when they hold a real value,
		// otherwise mirror the billing address field by field. A rendered but
		// empty shipping field must never override the billing destination,
		// otherwise the state-based delivery fee stops following the district
		// the customer picked.
		$ship = static function ( $key ) use ( $post ) {
			return ( isset( $post[ $key ] ) && '' !== trim( (string) $post[ $key ] ) ) ? wc_clean( $post[ $key ] ) : null;
		};

		// The separate shipping address only counts when the customer actually
		// filled it in. An untouched (or hidden) shipping form still posts its
		// default / empty values, and those must never override the billing
		// destination – otherwise the state-based delivery fee stops following
		// the district the customer picked.
		$shipping_given = ( $ship( 'shipping_first_name' ) || $ship( 'shipping_address_1' ) );

		$ship_country  = $shipping_given ? $ship( 'shipping_country' ) : null;
		$ship_state    = $shipping_given ? $ship( 'shipping_state' ) : null;
		$ship_city     = $shipping_given ? $ship( 'shipping_city' ) : null;
		$ship_postcode = $shipping_given ? $ship( 'shipping_postcode' ) : null;
		$ship_addr1    = $shipping_given ? $ship( 'shipping_address_1' ) : null;
		$ship_addr2    = $shipping_given ? $ship( 'shipping_address_2' ) : null;

		$customer->set_props(
			[
				'shipping_country'   => $ship_country ? $ship_country : $clean( 'billing_country', $customer->get_billing_country() ),
				'shipping_state'     => $ship_state ? $ship_state : $clean( 'billing_state', $customer->get_billing_state() ),
				'shipping_postcode'  => $ship_postcode ? $ship_postcode : $clean( 'billing_postcode', $customer->get_billing_postcode() ),
				'shipping_city'      => $ship_city ? $ship_city : $clean( 'billing_city', $customer->get_billing_city() ),
				'shipping_address_1' => $ship_addr1 ? $ship_addr1 : $clean( 'billing_address_1', $customer->get_billing_address_1() ),
				'shipping_address_2' => $ship_addr2 ? $ship_addr2 : $clean( 'billing_address_2', $customer->get_billing_address_2() ),
			]
		);

		WC()->customer->save();
	}

	/**
	 * AJAX: apply coupon.
	 *
	 * @return void
	 */
	public function co_apply_coupon_handler() {
		nocache_headers();
		check_ajax_referer( self::NONCE_COUPON, 'security' );

		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
		if ( empty( $coupon_code ) ) {
			wp_send_json_error( [ 'message' => 'Enter Coupon' ] );
		}

		$applied = WC()->cart->add_discount( $coupon_code );
		WC()->cart->calculate_totals();

		if ( ob_get_length() ) {
			ob_clean();
		}

		if ( $applied ) {
			wc_clear_notices();
			wp_send_json_success(
				[
					'message'            => 'Coupon Applied!',
					'items_html'         => $this->co_get_items_html(),
					'totals_html'        => $this->co_get_totals_html(),
					'cart_count'         => WC()->cart->get_cart_contents_count(),
					'minicart_body_html' => '<div class="hkdev-mini-cart-body">' . $this->mc_get_cart_html() . '</div>',
				]
			);
		} else {
			$errors    = wc_get_notices( 'error' );
			$error_msg = ! empty( $errors ) ? html_entity_decode( wp_strip_all_tags( $errors[0]['notice'] ), ENT_QUOTES, 'UTF-8' ) : 'Invalid Coupon';
			wc_clear_notices();
			wp_send_json_error( [ 'message' => $error_msg ] );
		}
	}

	/**
	 * AJAX: remove coupon.
	 *
	 * @return void
	 */
	public function co_remove_coupon_handler() {
		nocache_headers();
		check_ajax_referer( self::NONCE_RCOUPON, 'security' );

		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
		if ( ! empty( $coupon_code ) ) {
			WC()->cart->remove_coupon( $coupon_code );
		}
		WC()->cart->calculate_totals();
		wc_clear_notices();

		if ( ob_get_length() ) {
			ob_clean();
		}

		wp_send_json_success(
			[
				'message'            => 'Coupon Removed',
				'items_html'         => $this->co_get_items_html(),
				'totals_html'        => $this->co_get_totals_html(),
				'cart_count'         => WC()->cart->get_cart_contents_count(),
				'minicart_body_html' => '<div class="hkdev-mini-cart-body">' . $this->mc_get_cart_html() . '</div>',
			]
		);
	}

	/**
	 * AJAX: place order.
	 *
	 * @return void
	 */
	public function co_ajax_place_order_handler() {
		check_ajax_referer( self::NONCE_ACTION, 'hkdev_co_nonce' );

		if ( WC()->cart->is_empty() ) {
			wp_send_json_error( [ 'message' => 'Cart is empty' ] );
		}

		if ( empty( $_POST['hkdev_terms'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Please agree to the Terms and Conditions to place your order.', 'hkdev-shop-elements' ) ] );
		}

		$config         = $this->get_field_config();
		$cfg_billing    = ( isset( $config['billing'] ) && is_array( $config['billing'] ) ) ? $config['billing'] : [];
		$cfg_shipping   = ( isset( $config['shipping'] ) && is_array( $config['shipping'] ) ) ? $config['shipping'] : [];

		// "Billing = Delivery" mode: ignore the separate shipping fields so the
		// order's delivery address always mirrors the billing address.
		if ( 'yes' === get_option( 'hkdev_elements_billing_as_delivery', 'no' ) ) {
			$cfg_shipping = [];
		}

		$phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';
		if ( isset( $cfg_billing['billing_phone'] ) && 'yes' === $cfg_billing['billing_phone']['enabled'] && ( empty( $phone ) || ! preg_match( '/^(?:\+?88)?01[3-9]\d{8}$/', $phone ) ) ) {
			wp_send_json_error( [ 'message' => 'Invalid Phone Number' ] );
		}

		try {
			WC()->cart->calculate_totals();
			$order_id = wc_create_order();
			$order    = wc_get_order( $order_id );

			if ( ! $order instanceof \WC_Order ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Could not create your order. Please try again.', 'hkdev-shop-elements' ) ] );
			}

			$order->set_created_via( 'checkout' );
			$order->set_customer_ip_address( \WC_Geolocation::get_ip_address() );
			$order->set_customer_user_agent( wc_get_user_agent() );
			if ( is_user_logged_in() ) {
				$order->set_customer_id( get_current_user_id() );
			}

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$order->add_product(
					$cart_item['data'],
					$cart_item['quantity'],
					[
						'variation' => $cart_item['variation'],
						'totals'    => [
							'subtotal' => $cart_item['line_subtotal'],
							'total'    => $cart_item['line_total'],
						],
					]
				);
			}

			// Billing – only fields enabled in Checkout Fields settings are read
			// from the form; disabled ones fall back to safe defaults.
			$b_enabled = static function ( $key ) use ( $cfg_billing ) {
				return isset( $cfg_billing[ $key ] ) && 'yes' === $cfg_billing[ $key ]['enabled'];
			};
			$s_enabled = static function ( $key ) use ( $cfg_shipping ) {
				return isset( $cfg_shipping[ $key ] ) && 'yes' === $cfg_shipping[ $key ]['enabled'];
			};

			$first    = ( $b_enabled( 'billing_first_name' ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ?? '' ) ) : '';
			$last     = ( $b_enabled( 'billing_last_name' ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ?? '' ) ) : '';
			$company  = ( $b_enabled( 'billing_company' ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_company'] ?? '' ) ) : '';
			$address1 = ( $b_enabled( 'billing_address_1' ) ) ? sanitize_textarea_field( wp_unslash( $_POST['billing_address_1'] ?? '' ) ) : '';
			$address2 = ( $b_enabled( 'billing_address_2' ) ) ? sanitize_textarea_field( wp_unslash( $_POST['billing_address_2'] ?? '' ) ) : '';
			$country  = ( $b_enabled( 'billing_country' ) && ! empty( $_POST['billing_country'] ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) : 'BD';
			$city     = ( $b_enabled( 'billing_city' ) && ! empty( $_POST['billing_city'] ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_city'] ) ) : 'Dhaka';
			$state    = ( $b_enabled( 'billing_state' ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_state'] ?? '' ) ) : '';
			$postcode = ( $b_enabled( 'billing_postcode' ) ) ? sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ?? '' ) ) : '';
			$email    = ( $b_enabled( 'billing_email' ) && ! empty( $_POST['billing_email'] ) ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : $this->guest_email( $phone );

			$order->set_billing_first_name( $first );
			$order->set_billing_phone( $phone );
			$order->set_billing_email( $email );
			$order->set_billing_country( $country );
			$order->set_billing_city( $city );

			if ( '' !== $last ) { $order->set_billing_last_name( $last ); }
			if ( '' !== $company ) { $order->set_billing_company( $company ); }
			if ( '' !== $address1 ) { $order->set_billing_address_1( $address1 ); }
			if ( '' !== $address2 ) { $order->set_billing_address_2( $address2 ); }
			if ( '' !== $state ) { $order->set_billing_state( $state ); }
			if ( '' !== $postcode ) { $order->set_billing_postcode( $postcode ); }

			// Shipping – read from the shipping fields when enabled, otherwise
			// mirror the billing address.
			$ship_first    = ( $s_enabled( 'shipping_first_name' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_first_name'] ?? '' ) ) : $first;
			$ship_last     = ( $s_enabled( 'shipping_last_name' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_last_name'] ?? '' ) ) : $last;
			$ship_company  = ( $s_enabled( 'shipping_company' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_company'] ?? '' ) ) : $company;
			$ship_phone    = ( $s_enabled( 'shipping_phone' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_phone'] ?? '' ) ) : $phone;
			$ship_address1 = ( $s_enabled( 'shipping_address_1' ) ) ? sanitize_textarea_field( wp_unslash( $_POST['shipping_address_1'] ?? '' ) ) : $address1;
			$ship_address2 = ( $s_enabled( 'shipping_address_2' ) ) ? sanitize_textarea_field( wp_unslash( $_POST['shipping_address_2'] ?? '' ) ) : $address2;
			$ship_country  = ( $s_enabled( 'shipping_country' ) && ! empty( $_POST['shipping_country'] ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_country'] ) ) : $country;
			$ship_city     = ( $s_enabled( 'shipping_city' ) && ! empty( $_POST['shipping_city'] ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_city'] ) ) : $city;
			$ship_state    = ( $s_enabled( 'shipping_state' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_state'] ?? '' ) ) : $state;
			$ship_postcode = ( $s_enabled( 'shipping_postcode' ) ) ? sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ?? '' ) ) : $postcode;

			$order->set_shipping_first_name( $ship_first );
			$order->set_shipping_phone( $ship_phone );
			$order->set_shipping_country( $ship_country );
			$order->set_shipping_city( $ship_city );

			if ( '' !== $ship_last ) { $order->set_shipping_last_name( $ship_last ); }
			if ( '' !== $ship_company ) { $order->set_shipping_company( $ship_company ); }
			if ( '' !== $ship_address1 ) { $order->set_shipping_address_1( $ship_address1 ); }
			if ( '' !== $ship_address2 ) { $order->set_shipping_address_2( $ship_address2 ); }
			if ( '' !== $ship_state ) { $order->set_shipping_state( $ship_state ); }
			if ( '' !== $ship_postcode ) { $order->set_shipping_postcode( $ship_postcode ); }

			$shipping_methods = $_POST['shipping_method'] ?? []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $shipping_methods[0] ) ) {
				$chosen_rate_id = sanitize_text_field( wp_unslash( $shipping_methods[0] ) );
				WC()->session->set( 'chosen_shipping_methods', [ $chosen_rate_id ] );
				WC()->cart->calculate_shipping();
				$packages = WC()->shipping()->get_packages();
				$rates    = $packages[0]['rates'] ?? [];

				if ( isset( $rates[ $chosen_rate_id ] ) ) {
					$rate = $rates[ $chosen_rate_id ];
					$item = new \WC_Order_Item_Shipping();
					$item->set_method_id( $rate->get_method_id() );
					$item->set_method_title( $rate->label );
					$item->set_total( $rate->cost );
					$order->add_item( $item );
				}
			}

			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				$item = new \WC_Order_Item_Coupon();
				$item->set_props(
					[
						'code'         => $code,
						'discount'     => WC()->cart->get_coupon_discount_amount( $code ),
						'discount_tax' => 0,
					]
				);
				$order->add_item( $item );
			}

			foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
				$item = new \WC_Order_Item_Fee();
				$item->set_props(
					[
						'name'      => $fee->name,
						'tax_class' => $fee->taxable ? $fee->tax_class : 0,
						'total'     => $fee->amount,
						'total_tax' => $fee->tax,
					]
				);
				$order->add_item( $item );
			}

			// Only accept a payment method that WooCommerce actually offers. When
			// no gateway is available we keep the previous (manual) behaviour.
			$available_gateways = ( WC()->payment_gateways ) ? WC()->payment_gateways->get_available_payment_gateways() : [];
			$submitted_method   = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';

			if ( ! empty( $available_gateways ) && ! isset( $available_gateways[ $submitted_method ] ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Please select a valid payment method.', 'hkdev-shop-elements' ) ] );
			}

			$order->set_payment_method( $submitted_method );

			// Attribution (Sourcebuster cookies → WC order attribution).
			$attribution_data = [];

			if ( isset( $_COOKIE['sbjs_current'] ) ) {
				$sbjs_current = stripslashes( $_COOKIE['sbjs_current'] );
				parse_str( str_replace( '|||', '&', $sbjs_current ), $current_data );

				$mapping = [
					'typ' => 'source_type',
					'src' => 'utm_source',
					'mdm' => 'utm_medium',
					'cmp' => 'utm_campaign',
					'cnt' => 'utm_content',
					'trm' => 'utm_term',
				];

				foreach ( $mapping as $sb_key => $wc_key ) {
					if ( isset( $current_data[ $sb_key ] ) && '(none)' !== $current_data[ $sb_key ] ) {
						$attribution_data[ $wc_key ] = sanitize_text_field( $current_data[ $sb_key ] );
					}
				}

				if ( isset( $current_data['typ'] ) && 'type_in' === $current_data['typ'] ) {
					$attribution_data['source_type'] = 'type_in';
					if ( empty( $attribution_data['utm_source'] ) ) {
						$attribution_data['utm_source'] = '(direct)';
					}
				}
			}

			if ( isset( $_COOKIE['sbjs_session'] ) ) {
				$sbjs_session = stripslashes( $_COOKIE['sbjs_session'] );
				parse_str( str_replace( '|||', '&', $sbjs_session ), $session_data );
				if ( isset( $session_data['pgs'] ) ) {
					$attribution_data['session_pages'] = sanitize_text_field( $session_data['pgs'] );
				}
			}

			$attribution_data['user_agent']  = wc_get_user_agent();
			$attribution_data['device_type'] = wp_is_mobile() ? 'Mobile' : 'Desktop';

			foreach ( $attribution_data as $key => $value ) {
				$order->update_meta_data( '_wc_order_attribution_' . $key, $value );
			}

			$order->save();
			$order->calculate_totals();
			$order->update_status( 'processing', 'Order placed from custom checkout.' );

			// Pass sanitized data (not the raw superglobal) to the WooCommerce
			// action hooks that third-party extensions subscribe to.
			$posted_data = wc_clean( wp_unslash( $_POST ) );

			do_action( 'woocommerce_checkout_update_order_meta', $order->get_id(), $posted_data );
			do_action( 'woocommerce_checkout_order_processed', $order->get_id(), $posted_data, $order );

			WC()->cart->empty_cart();

			wp_send_json_success( [ 'redirect' => $order->get_checkout_order_received_url() ] );
		} catch ( \Throwable $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
