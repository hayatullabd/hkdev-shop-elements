<?php
/**
 * HKDEV Header Engine (HKDEV Shop Elements plugin).
 *
 * Renders a brand-matched WooCommerce header (logo, menu, categories dropdown,
 * product search, cart with live count/subtotal, account, call/WhatsApp, top
 * announcement bar) plus an off-canvas mobile panel. Self-contained – works
 * with ANY theme + Elementor + WooCommerce.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Header_Engine
 */
class Header_Engine {

	/**
	 * Option name holding the site-wide header configuration.
	 *
	 * @var string
	 */
	const CONFIG_OPTION = 'hkdev_elements_header_config';

	/**
	 * AJAX action used by the live product search.
	 *
	 * @var string
	 */
	const AJAX_ACTION = 'hkdev_elements_live_search';

	/**
	 * AJAX action used by the header mini cart.
	 *
	 * @var string
	 */
	const CART_ACTION = 'hkdev_elements_header_mini_cart';

	/**
	 * How many live search results to return.
	 *
	 * @var int
	 */
	const SEARCH_LIMIT = 6;

	/**
	 * @var ?Header_Engine
	 */
	private static $instance = null;

	/**
	 * Whether the mini cart drawer markup was already printed in this request.
	 *
	 * @var bool
	 */
	private $mini_cart_printed = false;

	/**
	 * Singleton.
	 *
	 * @return Header_Engine
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
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_fragments' ] );
		add_action( 'wp_body_open', [ $this, 'maybe_render' ], 5 );
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 30 );

		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_live_search' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_live_search' ] );

		add_action( 'wp_ajax_' . self::CART_ACTION, [ $this, 'ajax_mini_cart' ] );
		add_action( 'wp_ajax_nopriv_' . self::CART_ACTION, [ $this, 'ajax_mini_cart' ] );

		add_action( 'wp_footer', [ $this, 'render_floating_cart' ], 20 );
	}

	/**
	 * Default header configuration.
	 *
	 * @return array
	 */
	public function config_defaults() {
		return [
			'enabled'          => 'no',
			'logo'             => '',
			'logo_width'       => 150,
			'menu'             => '',
			'sticky'           => 'yes',
			'hide_desk'        => 'full',
			'hide_mobile'      => 'full',
			'show_topbar'      => 'no',
			'announcement'     => '',
			'phone'            => '',
			'email'            => '',
			'facebook'         => '',
			'instagram'        => '',
			'youtube'          => '',
			'track_url'        => '',
			'show_menu'        => 'yes',
			'show_search'      => 'yes',
			'show_account'     => 'yes',
			'show_cart'        => 'yes',
			'mini_cart'        => 'yes',
			'float_cart'       => 'yes',
			'float_cart_empty' => 'no',
			'show_categories'  => 'no',
			'categories_label' => __( 'All Categories', 'hkdev-shop-elements' ),
			'categories_limit' => 8,

			// ---- Appearance (edited from the Header admin → Appearance) ----
			// Empty/0 means "keep the plugin default". Size values are per device:
			// base = desktop, _t = ≤1024px (tablet), _m = ≤767px (mobile).
			'st_font'          => '',
			'st_font_size'     => 0,
			'st_font_size_t'   => 0,
			'st_font_size_m'   => 0,
			'st_container'     => 0,
			'st_container_t'   => 0,
			'st_container_m'   => 0,
			'st_radius'        => 0,
			'st_radius_t'      => 0,
			'st_radius_m'      => 0,
			'st_primary'       => '',
			'st_secondary'     => '',
			'st_text'          => '',
			'st_muted'         => '',
			'st_soft'          => '',
			'st_border'        => '',
			'st_topbar_bg'     => '',
			'st_topbar_color'  => '',
			'st_topbar_h'      => 0,
			'st_topbar_h_t'    => 0,
			'st_topbar_h_m'    => 0,
			'st_topbar_fs'     => 0,
			'st_topbar_fs_t'   => 0,
			'st_topbar_fs_m'   => 0,
			'st_topbar_icon'   => 0,
			'st_topbar_icon_t' => 0,
			'st_topbar_icon_m' => 0,
			'st_social_size'   => 0,
			'st_social_size_t' => 0,
			'st_social_size_m' => 0,
			'st_social_icon'   => 0,
			'st_social_icon_t' => 0,
			'st_social_icon_m' => 0,
			'st_main_bg'       => '',
			'st_main_h'        => 0,
			'st_main_h_t'      => 0,
			'st_main_h_m'      => 0,
			'st_navbar_bg'     => '',
			'st_navbar_color'  => '',
			'st_navbar_h'      => 0,
			'st_navbar_h_t'    => 0,
			'st_navbar_h_m'    => 0,
			'st_navbar_fs'     => 0,
			'st_navbar_fs_t'   => 0,
			'st_navbar_fs_m'   => 0,
			'st_search_h'      => 0,
			'st_search_h_t'    => 0,
			'st_search_h_m'    => 0,
			'st_logo_maxh'     => 0,
			'st_logo_maxh_t'   => 0,
			'st_logo_maxh_m'   => 0,
		];
	}

	/**
	 * Build the front-end <style> block from the Appearance settings.
	 *
	 * Everything is scoped to the header's unique wrapper id so it wins over the
	 * stylesheet's !important rules (and its media queries) without editing the
	 * stylesheet itself. Only values that were actually set are emitted.
	 *
	 * @param string $uid   Wrapper id (e.g. hkdev-hd-1234).
	 * @param array  $atts  Merged configuration.
	 * @return string
	 */
	public function style_css( $uid, $atts ) {
		$sel  = '#' . $uid;
		$base = []; // desktop / all widths
		$t    = []; // @media (max-width: 1024px) — tablet
		$m    = []; // @media (max-width: 767px)  — mobile

		$color = static function ( $value ) {
			return \HkdevShopElements\hkdev_elements_sanitize_css_color( $value );
		};
		$font = static function ( $value ) {
			return \HkdevShopElements\hkdev_elements_sanitize_font_stack( $value );
		};

		// ---- Colour + font tokens (shared by every device) ------------------
		$vars   = [];
		$tokens = [
			'st_primary'   => [ '--hd-primary' ],
			'st_secondary' => [ '--hd-secondary', '--hd-secondary-dark' ],
			'st_text'      => [ '--hd-text' ],
			'st_muted'     => [ '--hd-muted' ],
			'st_soft'      => [ '--hd-soft' ],
			'st_border'    => [ '--hd-border' ],
		];
		foreach ( $tokens as $key => $names ) {
			$safe = $color( $atts[ $key ] );
			if ( '' !== $safe ) {
				foreach ( $names as $name ) {
					$vars[] = $name . ':' . $safe;
				}
			}
		}
		if ( '' !== $atts['st_font'] ) {
			$safe = $font( $atts['st_font'] );
			if ( '' !== $safe ) {
				$vars[] = '--hd-font:' . $safe;
			}
		}
		if ( $vars ) {
			$base[] = $sel . '{' . implode( ';', $vars ) . '}';
		}

		// ---- Bar backgrounds / text colours (shared by every device) --------
		$paints = [
			[ 'st_topbar_bg', $sel . ' .hkdev-header-topbar', 'background' ],
			[ 'st_topbar_color', $sel . ' .hkdev-header-topbar', 'color' ],
			[ 'st_main_bg', $sel, 'background' ],
			[ 'st_main_bg', $sel . ' .hkdev-header-main', 'background' ],
			[ 'st_navbar_bg', $sel . ' .hkdev-header-navbar', 'background' ],
			[ 'st_navbar_color', $sel . ' .hkdev-header-menu>li>a', 'color' ],
		];
		foreach ( $paints as $p ) {
			$safe = $color( $atts[ $p[0] ] );
			if ( '' !== $safe ) {
				$base[] = $p[1] . '{' . $p[2] . ':' . $safe . ' !important}';
			}
		}

		// ---- Sizes (per device: desktop / tablet / mobile) ------------------
		$sizes = [
			[ $sel, [ 'font-size' ], 'st_font_size' ],
			[ $sel, [ '--hd-container' ], 'st_container' ],
			[ $sel, [ '--hd-radius' ], 'st_radius' ],
			[ $sel . ' .hkdev-header-topbar', [ 'font-size' ], 'st_topbar_fs' ],
			[ $sel . ' .hkdev-header-announcement i,' . $sel . ' .hkdev-header-toplink i', [ 'font-size' ], 'st_topbar_icon' ],
			[ $sel . ' .hkdev-header-social', [ 'width', 'height' ], 'st_social_size' ],
			[ $sel . ' .hkdev-header-social i', [ 'font-size' ], 'st_social_icon' ],
			[ $sel . ' .hkdev-header-topbar .hkdev-header-container', [ 'min-height' ], 'st_topbar_h' ],
			[ $sel . ' .hkdev-header-main .hkdev-header-container', [ 'min-height' ], 'st_main_h' ],
			[ $sel . ' .hkdev-header-navbar .hkdev-header-container', [ 'min-height' ], 'st_navbar_h' ],
			[ $sel . ' .hkdev-header-menu>li>a', [ 'font-size' ], 'st_navbar_fs' ],
			[ $sel . ' .hkdev-header-search-form', [ 'height' ], 'st_search_h' ],
			[ $sel . ' .hkdev-header-logo img', [ 'max-height' ], 'st_logo_maxh' ],
		];
		foreach ( $sizes as $row ) {
			foreach ( [ '' => 'base', '_t' => 't', '_m' => 'm' ] as $sfx => $which ) {
				$n = absint( $atts[ $row[2] . $sfx ] );
				if ( $n <= 0 ) {
					continue;
				}
				$decls = '';
				foreach ( $row[1] as $prop ) {
					$decls .= $prop . ':' . $n . 'px !important;';
				}
				$rule = $row[0] . '{' . $decls . '}';
				if ( 'base' === $which ) {
					$base[] = $rule;
				} elseif ( 't' === $which ) {
					$t[] = $rule;
				} else {
					$m[] = $rule;
				}
			}
		}

		if ( $t ) {
			$base[] = '@media (max-width:1024px){' . implode( '', $t ) . '}';
		}
		if ( $m ) {
			$base[] = '@media (max-width:767px){' . implode( '', $m ) . '}';
		}

		return $base ? '<style>' . implode( '', $base ) . '</style>' : '';
	}

	/**
	 * Saved configuration merged over the defaults.
	 *
	 * @return array
	 */
	public function get_config() {
		$saved = get_option( self::CONFIG_OPTION, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		return array_merge( $this->config_defaults(), $saved );
	}

	/**
	 * Whether the site-wide header is switched on.
	 *
	 * @return bool
	 */
	public function is_active() {
		$config = $this->get_config();

		return ( 'yes' === $config['enabled'] );
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
	 * Print the header site-wide (wp_body_open) when enabled in the settings.
	 *
	 * Also renders inside the Elementor editor / preview so the header looks
	 * the same while editing as it does on the live site.
	 *
	 * @return void
	 */
	public function maybe_render() {
		if ( ! $this->is_active() || is_admin() ) {
			return;
		}

		echo $this->header_shortcode( [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Flag the body when the plugin renders the site-wide header so the CSS
	 * can hide the theme's own header.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( $this->is_active() && ! is_admin() ) {
			$classes[] = 'hkdev-header-active';
		}

		return $classes;
	}

	/**
	 * Whether the site-wide floating cart button is enabled.
	 *
	 * @return bool
	 */
	public function float_cart_enabled() {
		$config = $this->get_config();

		return ( 'yes' === $config['float_cart'] );
	}

	/**
	 * Load the header assets for the site-wide (non-widget) render.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		if ( ! $this->is_active() && ! $this->float_cart_enabled() ) {
			return;
		}

		wp_enqueue_style( 'hkdev-elements-header-style' );
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_script( 'hkdev-elements-header-js' );
	}

	/**
	 * Keep the header cart count / subtotal in sync after AJAX add-to-cart.
	 *
	 * @param array $fragments WC fragments.
	 * @return array
	 */
	public function cart_fragments( $fragments ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return $fragments;
		}

		$count = WC()->cart->get_cart_contents_count();

		$fragments['span.hkdev-header-cart-count'] = '<span class="hkdev-header-cart-count">' . esc_html( $count ) . '</span>';

		return $fragments;
	}

	/**
	 * Product search form markup (with a live-results shell).
	 *
	 * @param string $class Extra wrapper class.
	 * @return string
	 */
	private function search_form( $class = '' ) {
		ob_start();
		?>
		<form role="search" method="get" class="hkdev-header-search-form <?php echo esc_attr( $class ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>" autocomplete="off">
			<input type="search" class="hkdev-header-search-input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search your product here...', 'hkdev-shop-elements' ); ?>" />
			<input type="hidden" name="post_type" value="product" />
			<button type="button" class="hkdev-header-search-clear" aria-label="<?php esc_attr_e( 'Clear', 'hkdev-shop-elements' ); ?>">
				<i class="fa-solid fa-xmark"></i>
			</button>
			<button type="submit" class="hkdev-header-search-btn" aria-label="<?php esc_attr_e( 'Search', 'hkdev-shop-elements' ); ?>">
				<i class="fa-solid fa-magnifying-glass"></i>
			</button>
		</form>
		<div class="hkdev-header-search-results" role="listbox" aria-live="polite"></div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX live product search (partial match on the product title).
	 *
	 * @return void
	 */
	public function ajax_live_search() {
		check_ajax_referer( 'hkdev_elements_live_search', 'nonce' );

		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$term = trim( $term );

		$term_length = function_exists( 'mb_strlen' ) ? mb_strlen( $term ) : strlen( $term );
		if ( $term_length < 2 ) {
			wp_send_json_success(
				[
					'term'  => $term,
					'items' => [],
					'total' => 0,
				]
			);
		}

		$args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => self::SEARCH_LIMIT,
			's'                   => $term,
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		];

		if ( taxonomy_exists( 'product_visibility' ) ) {
			$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'exclude-from-catalog',
					'operator' => 'NOT IN',
				],
			];
		}

		$query = new \WP_Query( $args );
		$items = [];

		foreach ( $query->posts as $post ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : false;
			if ( ! $product ) {
				continue;
			}

			$image = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
			if ( ! $image && function_exists( 'wc_placeholder_img_src' ) ) {
				$image = wc_placeholder_img_src( 'thumbnail' );
			}

			$items[] = [
				'id'    => $post->ID,
				'title' => get_the_title( $post->ID ),
				'url'   => get_permalink( $post->ID ),
				'img'   => $image ? $image : '',
				'price' => $product->get_price_html(),
			];
		}

		wp_reset_postdata();

		wp_send_json_success(
			[
				'term'       => $term,
				'items'      => $items,
				'total'      => (int) $query->found_posts,
				'search_url' => add_query_arg(
					[
						's'         => rawurlencode( $term ),
						'post_type' => 'product',
					],
					home_url( '/' )
				),
			]
		);
	}

	/**
	 * Inner HTML of the header mini cart (items + subtotal + actions).
	 *
	 * @return string
	 */
	private function mini_cart_html() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$cart = WC()->cart;

		ob_start();

		if ( $cart->is_empty() ) {
			?>
			<div class="hkdev-mc-empty">
				<span class="hkdev-mc-empty-icon"><i class="fa-solid fa-cart-shopping"></i></span>
				<p><?php esc_html_e( 'Your cart is empty.', 'hkdev-shop-elements' ); ?></p>
				<a class="hkdev-mc-btn is-solid" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Continue Shopping', 'hkdev-shop-elements' ); ?>
				</a>
			</div>
			<?php
		} else {
			?>
			<div class="hkdev-mc-items">
				<?php
				foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) :
					$product = isset( $cart_item['data'] ) ? $cart_item['data'] : false;
					if ( ! $product ) {
						continue;
					}
					$qty     = isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 1;
					$max_qty = (int) $product->get_max_purchase_quantity();
					?>
					<div class="hkdev-mc-item" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
						<div class="hkdev-mc-top">
							<a class="hkdev-mc-thumb" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
							</a>
							<a class="hkdev-mc-name" href="<?php echo esc_url( $product->get_permalink() ); ?>">
								<?php echo esc_html( $product->get_name() ); ?>
							</a>
							<button type="button" class="hkdev-mc-remove" data-key="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'hkdev-shop-elements' ); ?>">
								<i class="fa-solid fa-xmark"></i>
							</button>
						</div>

						<div class="hkdev-mc-meta">
							<span class="hkdev-mc-unit"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $product ) ) ); ?></span>
							<span class="hkdev-mc-op">&times;</span>
							<div class="hkdev-mc-stepper">
								<button type="button" class="hkdev-mc-step is-minus" data-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'hkdev-shop-elements' ); ?>">
									<i class="fa-solid fa-minus"></i>
								</button>
								<input
									type="text"
									class="hkdev-mc-qty"
									value="<?php echo esc_attr( $qty ); ?>"
									inputmode="numeric"
									data-min="1"
									<?php echo ( $max_qty > 0 ) ? 'data-max="' . esc_attr( $max_qty ) . '"' : ''; ?>
									aria-label="<?php esc_attr_e( 'Quantity', 'hkdev-shop-elements' ); ?>"
								/>
								<button type="button" class="hkdev-mc-step is-plus" data-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'hkdev-shop-elements' ); ?>">
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
							<span class="hkdev-mc-total">
								<span class="hkdev-mc-op">=</span>
								<span class="hkdev-mc-price"><?php echo wp_kses_post( $cart->get_product_subtotal( $product, $qty ) ); ?></span>
							</span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="hkdev-mc-footer">
				<div class="hkdev-mc-subtotal">
					<span><?php esc_html_e( 'Subtotal', 'hkdev-shop-elements' ); ?></span>
					<strong><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></strong>
				</div>
				<div class="hkdev-mc-actions">
					<a class="hkdev-mc-btn is-ghost" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						<?php esc_html_e( 'View Cart', 'hkdev-shop-elements' ); ?>
					</a>
					<a class="hkdev-mc-btn is-solid" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
						<?php esc_html_e( 'Checkout', 'hkdev-shop-elements' ); ?>
					</a>
				</div>
			</div>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Mini cart drawer markup, shared by the header and the floating cart button.
	 *
	 * @return string
	 */
	private function mini_cart_drawer_html() {
		$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

		$this->mini_cart_printed = true;

		ob_start();
		?>
		<div class="hkdev-mini-cart-overlay"></div>
		<aside class="hkdev-mini-cart" aria-hidden="true">
			<div class="hkdev-mini-cart-head">
				<span class="hkdev-mini-cart-title">
					<i class="fa-solid fa-cart-shopping"></i>
					<?php esc_html_e( 'Your Cart', 'hkdev-shop-elements' ); ?>
					<b class="hkdev-mini-cart-count-inline"><?php echo esc_html( $count ); ?></b>
				</span>
				<button type="button" class="hkdev-mini-cart-close" aria-label="<?php esc_attr_e( 'Close cart', 'hkdev-shop-elements' ); ?>">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>
			<div class="hkdev-mini-cart-body">
				<?php echo $this->mini_cart_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</aside>
		<?php

		return ob_get_clean();
	}

	/**
	 * Site-wide floating cart button pinned to the right edge of the viewport.
	 * Falls back to printing the drawer when the header is not rendering it.
	 *
	 * @return void
	 */
	public function render_floating_cart() {
		if ( ! $this->float_cart_enabled() || is_admin() || $this->is_elementor_edit() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$cart  = WC()->cart;
		$count = $cart->get_cart_contents_count();

		$config     = $this->get_config();
		$hide_empty = ( 'yes' === $config['float_cart_empty'] ) ? '0' : '1';

		if ( ! $this->mini_cart_printed ) {
			echo $this->mini_cart_drawer_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<a
			class="hkdev-float-cart<?php echo $count ? '' : ' is-empty'; ?>"
			href="<?php echo esc_url( wc_get_cart_url() ); ?>"
			data-mini-cart="<?php echo ( 'yes' === $config['mini_cart'] ) ? '1' : '0'; ?>"
			data-hide-empty="<?php echo esc_attr( $hide_empty ); ?>"
			aria-label="<?php esc_attr_e( 'Cart', 'hkdev-shop-elements' ); ?>"
		>
			<span class="hkdev-float-cart-top">
				<i class="fa-solid fa-cart-shopping"></i>
				<span class="hkdev-float-cart-count">
					<?php
					printf(
						/* translators: %d: number of cart items */
						esc_html( _n( '%d Item', '%d Items', $count, 'hkdev-shop-elements' ) ),
						(int) $count
					);
					?>
				</span>
			</span>
			<span class="hkdev-float-cart-total"><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></span>
		</a>
		<?php
	}
	/**
	 * AJAX: return the mini cart HTML after an optional remove / quantity change.
	 *
	 * @return void
	 */
	public function ajax_mini_cart() {
		check_ajax_referer( 'hkdev_elements_mini_cart', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error();
		}

		$cart = WC()->cart;

		$remove_key = isset( $_POST['remove_key'] ) ? sanitize_text_field( wp_unslash( $_POST['remove_key'] ) ) : '';
		$qty_key    = isset( $_POST['qty_key'] ) ? sanitize_text_field( wp_unslash( $_POST['qty_key'] ) ) : '';
		$qty_value  = isset( $_POST['qty_value'] ) ? wc_clean( wp_unslash( $_POST['qty_value'] ) ) : '';

		if ( '' !== $remove_key && $cart->get_cart_item( $remove_key ) ) {
			$cart->remove_cart_item( $remove_key );
		} elseif ( '' !== $qty_key && '' !== $qty_value ) {
			$item = $cart->get_cart_item( $qty_key );

			if ( $item && ! empty( $item['data'] ) ) {
				$product = $item['data'];
				$qty     = absint( $qty_value );
				$max     = (int) $product->get_max_purchase_quantity();

				$qty = max( 1, $qty );
				if ( $max > 0 ) {
					$qty = min( $qty, $max );
				}

				$cart->set_quantity( $qty_key, $qty, true );
			}
		}

		wp_send_json_success(
			[
				'html'  => $this->mini_cart_html(),
				'count' => $cart->get_cart_contents_count(),
				'total' => $cart->get_cart_subtotal(),
			]
		);
	}

	/**
	 * Navigation menu markup (same menu reused for desktop + mobile panel).
	 *
	 * @param string $menu Menu id/slug/name.
	 * @param string $class UL class.
	 * @return string
	 */
	private function menu_html( $menu, $class ) {
		$args = [
			'echo'        => false,
			'container'   => false,
			'menu_class'  => $class,
			'menu_id'     => '',
			'fallback_cb' => false,
			'depth'       => 3,
		];

		if ( ! empty( $menu ) ) {
			$args['menu'] = $menu;
		}

		$html = wp_nav_menu( $args );

		return $html ? $html : '';
	}

	/**
	 * Render the header.
	 *
	 * @param array $atts Widget attributes.
	 * @return string
	 */
	public function header_shortcode( $atts = [] ) {
		if ( ! class_exists( '\WooCommerce' ) || is_admin() ) {
			return '';
		}

		// Site-wide settings first, then the widget's own attributes on top.
		$atts = array_merge( $this->get_config(), (array) $atts );

		$uid = 'hkdev-hd-' . wp_rand( 1000, 9999 );

		// ---- Logo -------------------------------------------------------
		$logo_url = '';
		if ( ! empty( $atts['logo'] ) ) {
			$logo_url = $atts['logo'];
		} elseif ( has_custom_logo() ) {
			$logo_url = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );
		}
		$logo_width = absint( $atts['logo_width'] ) ? absint( $atts['logo_width'] ) : 150;

		// ---- Contact links ----------------------------------------------
		$phone_display = trim( (string) $atts['phone'] );
		$call_link     = $phone_display ? 'tel:' . preg_replace( '/[^0-9+]/', '', $phone_display ) : '';
		$email         = trim( (string) $atts['email'] );

		// ---- Menu -------------------------------------------------------
		$menu_html = ( 'yes' === $atts['show_menu'] ) ? $this->menu_html( $atts['menu'], 'hkdev-header-menu' ) : '';

		// ---- Cart -------------------------------------------------------
		$cart_url   = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
		$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );

		// ---- Track order link (explicit setting, else a matching page) ---
		$track_url = trim( (string) $atts['track_url'] );
		if ( '' === $track_url ) {
			foreach ( [ 'track-order', 'order-tracking', 'tracking' ] as $track_slug ) {
				$track_page = get_page_by_path( $track_slug );
				if ( $track_page ) {
					$track_url = (string) get_permalink( $track_page );
					break;
				}
			}
		}

		// ---- Account / auth ---------------------------------------------
		$is_logged_in = is_user_logged_in();

		$show_navbar  = (bool) $menu_html;
		$show_mini    = ( 'yes' === $atts['mini_cart'] && 'yes' === $atts['show_cart'] );
		$sticky_class = ( 'yes' === $atts['sticky'] ) ? ' hkdev-header-sticky' : '';

		// Auto-hide modes: full (slide the whole header), top (collapse the top
		// bar only) or none (keep the header visible).
		$hide_desk   = in_array( $atts['hide_desk'], [ 'full', 'top' ], true ) ? $atts['hide_desk'] : 'none';
		$hide_mobile = in_array( $atts['hide_mobile'], [ 'full', 'top' ], true ) ? $atts['hide_mobile'] : 'none';
		$hide_attr   = '';

		if ( '' !== $sticky_class && ( 'none' !== $hide_desk || 'none' !== $hide_mobile ) ) {
			$sticky_class .= ' hkdev-header-autohide';
			$hide_attr     = ' data-hide-desk="' . esc_attr( $hide_desk ) . '" data-hide-mobile="' . esc_attr( $hide_mobile ) . '"';
		}

		$socials = [
			'facebook'  => [ 'fa-brands fa-facebook-f', trim( (string) $atts['facebook'] ) ],
			'instagram' => [ 'fa-brands fa-instagram', trim( (string) $atts['instagram'] ) ],
			'youtube'   => [ 'fa-brands fa-youtube', trim( (string) $atts['youtube'] ) ],
		];

		ob_start();
		?>
		<div class="hkdev-header-wrap<?php echo esc_attr( $sticky_class ); ?>"<?php echo $hide_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?> id="<?php echo esc_attr( $uid ); ?>">

			<?php echo $this->style_css( $uid, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- colours/fonts sanitised in style_css() ?>

			<?php if ( 'yes' === $atts['show_topbar'] ) : ?>
				<div class="hkdev-header-topbar">
					<div class="hkdev-header-container">
						<div class="hkdev-header-topbar-left">
							<?php if ( '' !== trim( (string) $atts['announcement'] ) ) : ?>
								<span class="hkdev-header-announcement">
									<i class="fa-solid fa-leaf"></i> <?php echo wp_kses_post( $atts['announcement'] ); ?>
								</span>
							<?php endif; ?>
							<?php if ( $email ) : ?>
								<a class="hkdev-header-toplink" href="mailto:<?php echo esc_attr( $email ); ?>">
									<i class="fa-solid fa-envelope"></i> <span><?php echo esc_html( $email ); ?></span>
								</a>
							<?php endif; ?>
						</div>
						<div class="hkdev-header-topbar-right">
							<?php if ( $call_link ) : ?>
								<a class="hkdev-header-toplink" href="<?php echo esc_url( $call_link ); ?>">
									<i class="fa-solid fa-phone"></i> <span><?php echo esc_html( $phone_display ); ?></span>
								</a>
							<?php endif; ?>
							<?php if ( $track_url ) : ?>
								<a class="hkdev-header-toplink" href="<?php echo esc_url( $track_url ); ?>">
									<i class="fa-solid fa-truck-fast"></i> <span><?php esc_html_e( 'Track Your Order', 'hkdev-shop-elements' ); ?></span>
								</a>
							<?php endif; ?>
							<?php foreach ( $socials as $social ) : ?>
								<?php if ( '' !== $social[1] ) : ?>
									<a class="hkdev-header-social" href="<?php echo esc_url( $social[1] ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Social link', 'hkdev-shop-elements' ); ?>">
										<i class="<?php echo esc_attr( $social[0] ); ?>"></i>
									</a>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="hkdev-header-main">
				<div class="hkdev-header-container">

					<button type="button" class="hkdev-header-hamburger" aria-label="<?php esc_attr_e( 'Open menu', 'hkdev-shop-elements' ); ?>">
						<i class="fa-solid fa-bars"></i>
					</button>

					<a class="hkdev-header-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php if ( $logo_url ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" style="width:<?php echo esc_attr( $logo_width ); ?>px">
						<?php else : ?>
							<span class="hkdev-header-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<?php endif; ?>
					</a>

					<?php if ( 'yes' === $atts['show_search'] ) : ?>
						<div class="hkdev-header-search">
							<?php echo $this->search_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>

					<div class="hkdev-header-actions">

						<?php if ( $call_link ) : ?>
							<a class="hkdev-header-call" href="<?php echo esc_url( $call_link ); ?>">
								<span class="call-icon"><i class="fa-solid fa-phone"></i></span>
								<span class="call-text">
									<small><?php esc_html_e( 'Call Us Now', 'hkdev-shop-elements' ); ?></small>
									<strong><?php echo esc_html( $phone_display ); ?></strong>
								</span>
							</a>
						<?php endif; ?>

						<?php if ( 'yes' === $atts['show_cart'] ) : ?>
							<a class="hkdev-header-icon-btn hkdev-header-cart" href="<?php echo esc_url( $cart_url ); ?>" data-mini-cart="<?php echo ( 'yes' === $atts['mini_cart'] ) ? '1' : '0'; ?>" aria-label="<?php esc_attr_e( 'Cart', 'hkdev-shop-elements' ); ?>">
								<i class="fa-solid fa-cart-shopping"></i>
								<span class="hkdev-header-cart-count"><?php echo esc_html( $cart_count ); ?></span>
							</a>
						<?php endif; ?>

						<?php if ( 'yes' === $atts['show_account'] ) : ?>
							<div class="hkdev-header-auth">
								<?php if ( $is_logged_in ) : ?>
									<a class="hkdev-header-login" href="<?php echo esc_url( $account_url ); ?>">
										<?php esc_html_e( 'My Account', 'hkdev-shop-elements' ); ?>
									</a>
									<a class="hkdev-header-register" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
										<i class="fa-solid fa-arrow-right-from-bracket"></i>
										<span><?php esc_html_e( 'Logout', 'hkdev-shop-elements' ); ?></span>
									</a>
								<?php else : ?>
									<a class="hkdev-header-login" href="<?php echo esc_url( $account_url ); ?>">
										<?php esc_html_e( 'Log In', 'hkdev-shop-elements' ); ?>
									</a>
									<a class="hkdev-header-register" href="<?php echo esc_url( $account_url ); ?>">
										<i class="fa-regular fa-user"></i>
										<span><?php esc_html_e( 'Register', 'hkdev-shop-elements' ); ?></span>
									</a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php if ( $show_navbar ) : ?>
				<div class="hkdev-header-navbar">
					<div class="hkdev-header-container">

						<?php if ( $menu_html ) : ?>
							<div class="hkdev-header-nav-wrap">
								<button type="button" class="hkdev-header-nav-arrow is-prev" aria-label="<?php esc_attr_e( 'Scroll menu left', 'hkdev-shop-elements' ); ?>">
									<i class="fa-solid fa-chevron-left"></i>
								</button>
								<nav class="hkdev-header-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'hkdev-shop-elements' ); ?>">
									<?php echo $menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</nav>
								<button type="button" class="hkdev-header-nav-arrow is-next" aria-label="<?php esc_attr_e( 'Scroll menu right', 'hkdev-shop-elements' ); ?>">
									<i class="fa-solid fa-chevron-right"></i>
								</button>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="hkdev-header-overlay"></div>

		<?php
		if ( $show_mini ) {
			echo $this->mini_cart_drawer_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

		<aside class="hkdev-header-panel" aria-hidden="true">
			<div class="hkdev-header-panel-head">
				<span class="hkdev-header-panel-title"><?php esc_html_e( 'Menu', 'hkdev-shop-elements' ); ?></span>
				<button type="button" class="hkdev-header-panel-close" aria-label="<?php esc_attr_e( 'Close menu', 'hkdev-shop-elements' ); ?>">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>

			<?php if ( 'yes' === $atts['show_search'] ) : ?>
				<div class="hkdev-header-panel-search">
					<?php echo $this->search_form( 'is-panel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<div class="hkdev-header-panel-links">
				<?php if ( $track_url ) : ?>
					<a class="hkdev-header-panel-link" href="<?php echo esc_url( $track_url ); ?>">
						<i class="fa-solid fa-truck-fast"></i> <?php esc_html_e( 'Track Your Order', 'hkdev-shop-elements' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( 'yes' === $atts['show_account'] ) : ?>
					<a class="hkdev-header-panel-link" href="<?php echo esc_url( $account_url ); ?>">
						<i class="fa-regular fa-user"></i> <?php echo $is_logged_in ? esc_html__( 'My Account', 'hkdev-shop-elements' ) : esc_html__( 'Log In / Register', 'hkdev-shop-elements' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $call_link ) : ?>
				<div class="hkdev-header-panel-contact">
					<a class="hkdev-header-contact-btn call" href="<?php echo esc_url( $call_link ); ?>">
						<i class="fa-solid fa-phone"></i> <?php esc_html_e( 'Call For Order', 'hkdev-shop-elements' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</aside>
		<?php
		return ob_get_clean();
	}
}
