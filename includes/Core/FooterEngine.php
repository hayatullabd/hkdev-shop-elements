<?php
/**
 * HKDEV Footer Engine (HKDEV Shop Elements plugin).
 *
 * Renders a brand-matched site footer: brand blurb + contact details, quick
 * links menus (Quick Links + optional second menu column), newsletter form with social icons
 * and a bottom bar holding the payment badges and the copyright notice.
 * Self-contained – works with ANY theme + Elementor + WooCommerce.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FooterEngine
 */
class FooterEngine {

	/**
	 * Option name holding the site-wide footer configuration.
	 *
	 * @var string
	 */
	const CONFIG_OPTION = 'hkdev_elements_footer_config';

	/**
	 * AJAX action used by the newsletter form.
	 *
	 * @var string
	 */
	const SUBSCRIBE_ACTION = 'hkdev_elements_footer_subscribe';

	/**
	 * Option name holding the collected newsletter subscribers (email => time).
	 *
	 * @var string
	 */
	const SUBSCRIBERS_OPTION = 'hkdev_elements_footer_subscribers';

	/**
	 * How many subscribers to keep before dropping the oldest.
	 *
	 * @var int
	 */
	const SUBSCRIBERS_LIMIT = 1000;

	/**
	 * @var ?FooterEngine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return FooterEngine
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
		add_action( 'wp_footer', [ $this, 'maybe_render' ], 5 );
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 30 );

		add_action( 'wp_ajax_' . self::SUBSCRIBE_ACTION, [ $this, 'ajax_subscribe' ] );
		add_action( 'wp_ajax_nopriv_' . self::SUBSCRIBE_ACTION, [ $this, 'ajax_subscribe' ] );
	}

	/**
	 * Default footer configuration.
	 *
	 * @return array
	 */
	public function config_defaults() {
		return [
			'enabled'           => 'no',
			'logo'              => '',
			'logo_width'        => 150,
			'about'             => __( 'Your one-stop shop for everyday essentials — quality products, honest prices and fast delivery.', 'hkdev-shop-elements' ),
			'address'           => '',
			'phone'             => '',
			'email'             => '',
			'hours'             => '',
			'menu'              => '',
			'menu_title'        => __( 'Quick Links', 'hkdev-shop-elements' ),
			'show_categories'   => 'yes',
			'categories_title'  => __( 'Categories', 'hkdev-shop-elements' ),
			'categories_menu'   => '',
			'show_newsletter'   => 'yes',
			'newsletter_title'  => __( 'Newsletter', 'hkdev-shop-elements' ),
			'newsletter_text'   => __( 'Subscribe to get special offers, free giveaways and once-in-a-lifetime deals.', 'hkdev-shop-elements' ),
			'newsletter_action' => '',
			'newsletter_btn'    => __( 'Subscribe', 'hkdev-shop-elements' ),
			'show_social'       => 'yes',
			'facebook'          => '',
			'instagram'         => '',
			'youtube'           => '',
			'whatsapp'          => '',
			'show_payments'       => 'yes',
			'payment_banner'      => '',
			'payment_banner_link' => '',
			'show_backtotop'    => 'yes',
			'copyright'         => '',

			// ---- Appearance (edited from the Footer admin → Appearance) ----
			// Empty/0 means "keep the plugin default". Size values are per device:
			// base = desktop, _t = ≤1024px (tablet), _m = ≤767px (mobile).
			'st_font'           => '',
			'st_font_size'      => 0,
			'st_font_size_t'    => 0,
			'st_font_size_m'    => 0,
			'st_container'      => 0,
			'st_container_t'    => 0,
			'st_container_m'    => 0,
			'st_bg'             => '',
			'st_bg2'            => '',
			'st_text'           => '',
			'st_heading'        => '',
			'st_muted'          => '',
			'st_green'          => '',
			'st_orange'         => '',
			'st_border'         => '',
			'st_soft'           => '',
			'st_main_pad_y'     => 0,
			'st_main_pad_y_t'   => 0,
			'st_main_pad_y_m'   => 0,
			'st_grid_gap'       => 0,
			'st_grid_gap_t'     => 0,
			'st_grid_gap_m'     => 0,
			'st_bottom_pad_y'   => 0,
			'st_bottom_pad_y_t' => 0,
			'st_bottom_pad_y_m' => 0,
			'st_title_fs'       => 0,
			'st_title_fs_t'     => 0,
			'st_title_fs_m'     => 0,
			'st_link_fs'        => 0,
			'st_link_fs_t'      => 0,
			'st_link_fs_m'      => 0,
		];
	}

	/**
	 * Build the front-end <style> block from the Appearance settings.
	 *
	 * Scoped to the footer's unique wrapper id so it wins over the stylesheet's
	 * !important rules (and its media queries) without editing the stylesheet.
	 * Only values that were actually set are emitted.
	 *
	 * @param string $uid  Wrapper id (e.g. hkdev-ft-1234).
	 * @param array  $atts Merged configuration.
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
		$vars = [];
		$map  = [
			'st_bg'      => '--ft-bg',
			'st_bg2'     => '--ft-bg-2',
			'st_text'    => '--ft-text',
			'st_heading' => '--ft-heading',
			'st_muted'   => '--ft-muted',
			'st_green'   => '--ft-green',
			'st_orange'  => '--ft-orange',
			'st_border'  => '--ft-border',
			'st_soft'    => '--ft-soft',
		];
		foreach ( $map as $key => $var ) {
			$safe = $color( $atts[ $key ] );
			if ( '' !== $safe ) {
				$vars[] = $var . ':' . $safe;
			}
		}
		if ( '' !== $atts['st_font'] ) {
			$safe = $font( $atts['st_font'] );
			if ( '' !== $safe ) {
				$vars[] = '--ft-font:' . $safe;
			}
		}
		if ( $vars ) {
			$base[] = $sel . '{' . implode( ';', $vars ) . '}';
		}

		// ---- Sizes (desktop / tablet / mobile) ------------------------------
		$sizes = [
			[ $sel, [ 'font-size' ], 'st_font_size' ],
			[ $sel, [ '--ft-container' ], 'st_container' ],
			[ $sel . ' .hkdev-footer-main', [ 'padding-top', 'padding-bottom' ], 'st_main_pad_y' ],
			[ $sel . ' .hkdev-footer-grid', [ 'gap' ], 'st_grid_gap' ],
			[ $sel . ' .hkdev-footer-bottom', [ 'padding-top', 'padding-bottom' ], 'st_bottom_pad_y' ],
			[ $sel . ' .hkdev-footer-title', [ 'font-size' ], 'st_title_fs' ],
			[ $sel . ' .hkdev-footer-menu li a,' . $sel . ' .hkdev-footer-links li a', [ 'font-size' ], 'st_link_fs' ],
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
	 * Whether the site-wide footer is switched on.
	 *
	 * @return bool
	 */
	public function is_active() {
		$config = $this->get_config();

		return ( 'yes' === $config['enabled'] );
	}

	/**
	 * Print the footer site-wide (wp_footer) when enabled in the settings.
	 *
	 * Also renders inside the Elementor editor / preview so the footer looks
	 * the same while editing as it does on the live site.
	 *
	 * @return void
	 */
	public function maybe_render() {
		if ( ! $this->is_active() || is_admin() ) {
			return;
		}

		echo $this->footer_shortcode( [ '__sitewide' => 'yes' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Flag the body when the plugin renders the site-wide footer so the CSS
	 * can hide the theme's own footer.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( $this->is_active() && ! is_admin() ) {
			$classes[] = 'hkdev-footer-active';
		}

		return $classes;
	}

	/**
	 * Load the footer assets for the site-wide (non-widget) render.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() || ! $this->is_active() ) {
			return;
		}

		wp_enqueue_style( 'hkdev-elements-footer-style' );
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_script( 'hkdev-elements-footer-js' );
	}

	/**
	 * Store a newsletter subscription posted from the footer form.
	 *
	 * @return void
	 */
	public function ajax_subscribe() {
		check_ajax_referer( self::SUBSCRIBE_ACTION, 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$email = trim( (string) $email );

		if ( '' === $email || ! is_email( $email ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Please enter a valid email address.', 'hkdev-shop-elements' ) ]
			);
		}

		$list = get_option( self::SUBSCRIBERS_OPTION, [] );
		if ( ! is_array( $list ) ) {
			$list = [];
		}

		$key    = strtolower( $email );
		$is_new = ! isset( $list[ $key ] );

		$list[ $key ] = time();

		if ( count( $list ) > self::SUBSCRIBERS_LIMIT ) {
			$list = array_slice( $list, -self::SUBSCRIBERS_LIMIT, null, true );
		}

		update_option( self::SUBSCRIBERS_OPTION, $list );

		if ( $is_new ) {
			wp_mail(
				get_option( 'admin_email' ),
				/* translators: %s: subscriber email address */
				sprintf( esc_html__( 'New newsletter subscriber: %s', 'hkdev-shop-elements' ), $email ),
				/* translators: %s: subscriber email address */
				sprintf( esc_html__( 'A visitor subscribed to the newsletter from the site footer: %s', 'hkdev-shop-elements' ), $email )
			);
		}

		wp_send_json_success(
			[ 'message' => esc_html__( 'Thanks for subscribing! Please check your inbox.', 'hkdev-shop-elements' ) ]
		);
	}

	/**
	 * Stored newsletter subscribers.
	 *
	 * @return array Map of email => unix timestamp.
	 */
	public function get_subscribers() {
		$list = get_option( self::SUBSCRIBERS_OPTION, [] );

		return is_array( $list ) ? $list : [];
	}

	/**
	 * Navigation menu markup for a footer link column.
	 *
	 * @param string $menu              Menu id/slug/name.
	 * @param bool   $fallback_to_first When menu is empty, use the first registered menu.
	 * @return string
	 */
	private function menu_html( $menu, $fallback_to_first = true ) {
		$args = [
			'echo'        => false,
			'container'   => false,
			'menu_class'  => 'hkdev-footer-menu',
			'menu_id'     => '',
			'fallback_cb' => false,
			'depth'       => 1,
		];

		if ( empty( $menu ) ) {
			if ( ! $fallback_to_first ) {
				return '';
			}
			$menus = wp_get_nav_menus();
			if ( ! empty( $menus ) ) {
				$args['menu'] = $menus[0]->term_id;
			}
		} else {
			$args['menu'] = $menu;
		}

		if ( empty( $args['menu'] ) ) {
			return '';
		}

		$html = wp_nav_menu( $args );

		return $html ? $html : '';
	}

	/**
	 * Render the footer.
	 *
	 * @param array $atts Widget attributes.
	 * @return string
	 */
	public function footer_shortcode( $atts = [] ) {
		$is_editor = \HkdevShopElements\Includes\Core\HeaderEngine::instance()->is_elementor_edit();

		if ( is_admin() && ! $is_editor ) {
			return '';
		}

		$sitewide_context = ( isset( $atts['__sitewide'] ) && 'yes' === (string) $atts['__sitewide'] );
		if ( $this->is_active() && ! $sitewide_context && ! $is_editor && ! is_customize_preview() ) {
			// Site-wide footer is already rendered through wp_footer hook.
			return '';
		}

		// Site-wide settings first, then the widget's own attributes on top.
		$atts = array_merge( $this->get_config(), (array) $atts );

		$uid = 'hkdev-ft-' . wp_rand( 1000, 9999 );

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
		$address       = trim( (string) $atts['address'] );
		$hours         = trim( (string) $atts['hours'] );

		// ---- Quick links menu -------------------------------------------
		$menu_html = $this->menu_html( $atts['menu'] );

		// ---- Second link column (custom menu, e.g. categories) ----------
		$categories_menu_html = '';
		if ( 'yes' === $atts['show_categories'] ) {
			$categories_menu_html = $this->menu_html(
				isset( $atts['categories_menu'] ) ? $atts['categories_menu'] : '',
				false
			);
		}

		// ---- Social links -----------------------------------------------
		$socials = [
			'facebook'  => [ 'fa-brands fa-facebook-f', trim( (string) $atts['facebook'] ) ],
			'instagram' => [ 'fa-brands fa-instagram', trim( (string) $atts['instagram'] ) ],
			'youtube'   => [ 'fa-brands fa-youtube', trim( (string) $atts['youtube'] ) ],
			'whatsapp'  => [ 'fa-brands fa-whatsapp', trim( (string) $atts['whatsapp'] ) ],
		];

		// ---- Payment / SSL banner ---------------------------------------
		$banner_url  = trim( (string) $atts['payment_banner'] );
		$banner_link = trim( (string) $atts['payment_banner_link'] );
		$show_banner = ( 'yes' === $atts['show_payments'] && '' !== $banner_url );

		// ---- Copyright --------------------------------------------------
		$copyright = trim( (string) $atts['copyright'] );
		if ( '' === $copyright ) {
			/* translators: 1: current year, 2: site name */
			$copyright = sprintf( esc_html__( '© %1$s %2$s. All rights reserved.', 'hkdev-shop-elements' ), gmdate( 'Y' ), get_bloginfo( 'name' ) );
		} else {
			$copyright = str_replace(
				[ '{year}', '{site}' ],
				[ gmdate( 'Y' ), get_bloginfo( 'name' ) ],
				$copyright
			);
		}

		// ---- Newsletter -------------------------------------------------
		$show_news    = ( 'yes' === $atts['show_newsletter'] );
		$news_action  = trim( (string) $atts['newsletter_action'] );
		$news_ajax    = ( '' === $news_action );
		$news_btn     = trim( (string) $atts['newsletter_btn'] );
		$news_btn     = ( '' === $news_btn ) ? esc_html__( 'Subscribe', 'hkdev-shop-elements' ) : $news_btn;
		$has_social   = ( 'yes' === $atts['show_social'] ) && ( ! empty( array_filter( wp_list_pluck( $socials, 1 ) ) ) );

		ob_start();
		?>
		<div class="hkdev-footer-wrap" id="<?php echo esc_attr( $uid ); ?>" role="contentinfo">
			<?php echo $this->style_css( $uid, $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- colours/fonts sanitised in style_css() ?>
			<span class="hkdev-footer-accent" aria-hidden="true"></span>

			<div class="hkdev-footer-main">
				<div class="hkdev-footer-container">
					<div class="hkdev-footer-grid">

						<!-- Brand + contact -->
						<div class="hkdev-footer-col is-brand">
							<a class="hkdev-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
								<?php if ( $logo_url ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" style="width:<?php echo esc_attr( $logo_width ); ?>px">
								<?php else : ?>
									<span class="hkdev-footer-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
								<?php endif; ?>
							</a>

							<?php if ( '' !== trim( (string) $atts['about'] ) ) : ?>
								<p class="hkdev-footer-about"><?php echo wp_kses_post( $atts['about'] ); ?></p>
							<?php endif; ?>

							<?php if ( $address || $phone_display || $email || $hours ) : ?>
								<ul class="hkdev-footer-contact">
									<?php if ( $address ) : ?>
										<li>
											<span class="hkdev-footer-contact-icon"><i class="fa-solid fa-location-dot"></i></span>
											<span><?php echo esc_html( $address ); ?></span>
										</li>
									<?php endif; ?>
									<?php if ( $phone_display ) : ?>
										<li>
											<span class="hkdev-footer-contact-icon"><i class="fa-solid fa-phone"></i></span>
											<a href="<?php echo esc_url( $call_link ); ?>"><?php echo esc_html( $phone_display ); ?></a>
										</li>
									<?php endif; ?>
									<?php if ( $email ) : ?>
										<li>
											<span class="hkdev-footer-contact-icon"><i class="fa-solid fa-envelope"></i></span>
											<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
										</li>
									<?php endif; ?>
									<?php if ( $hours ) : ?>
										<li>
											<span class="hkdev-footer-contact-icon"><i class="fa-regular fa-clock"></i></span>
											<span><?php echo esc_html( $hours ); ?></span>
										</li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>
						</div>

						<!-- Quick links -->
						<?php if ( $menu_html ) : ?>
							<div class="hkdev-footer-col">
								<h3 class="hkdev-footer-title"><?php echo esc_html( $atts['menu_title'] ); ?></h3>
								<nav class="hkdev-footer-nav" aria-label="<?php echo esc_attr( $atts['menu_title'] ); ?>">
									<?php echo $menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</nav>
							</div>
						<?php endif; ?>

						<!-- Second menu column -->
						<?php if ( $categories_menu_html ) : ?>
							<div class="hkdev-footer-col">
								<h3 class="hkdev-footer-title"><?php echo esc_html( $atts['categories_title'] ); ?></h3>
								<nav class="hkdev-footer-nav" aria-label="<?php echo esc_attr( $atts['categories_title'] ); ?>">
									<?php echo $categories_menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</nav>
							</div>
						<?php endif; ?>

						<!-- Newsletter + social -->
						<?php if ( $show_news || $has_social ) : ?>
							<div class="hkdev-footer-col is-news">
								<?php if ( $show_news ) : ?>
									<h3 class="hkdev-footer-title"><?php echo esc_html( $atts['newsletter_title'] ); ?></h3>

									<?php if ( '' !== trim( (string) $atts['newsletter_text'] ) ) : ?>
										<p class="hkdev-footer-news-text"><?php echo wp_kses_post( $atts['newsletter_text'] ); ?></p>
									<?php endif; ?>

									<form
										class="hkdev-footer-news-form"
										method="post"
										action="<?php echo esc_url( $news_ajax ? home_url( '/' ) : $news_action ); ?>"
										<?php echo $news_ajax ? 'data-hkdev-news-ajax="1"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									>
										<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-email"><?php esc_html_e( 'Email address', 'hkdev-shop-elements' ); ?></label>
										<input
											type="email"
											id="<?php echo esc_attr( $uid ); ?>-email"
											name="email"
											class="hkdev-footer-news-input"
											placeholder="<?php esc_attr_e( 'Enter your email...', 'hkdev-shop-elements' ); ?>"
											autocomplete="email"
											required
										>
										<button type="submit" class="hkdev-footer-news-btn">
											<i class="fa-regular fa-paper-plane"></i>
											<span><?php echo esc_html( $news_btn ); ?></span>
										</button>
									</form>
									<p class="hkdev-footer-news-msg" role="status" aria-live="polite"></p>
								<?php endif; ?>

								<?php if ( $has_social ) : ?>
									<div class="hkdev-footer-social">
										<?php foreach ( $socials as $key => $social ) : ?>
											<?php if ( '' !== $social[1] ) : ?>
												<a
													class="hkdev-footer-social-link is-<?php echo esc_attr( $key ); ?>"
													href="<?php echo esc_url( $social[1] ); ?>"
													target="_blank"
													rel="noopener noreferrer"
													aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>"
												>
													<i class="<?php echo esc_attr( $social[0] ); ?>"></i>
												</a>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

					</div>
				</div>
			</div>

			<div class="hkdev-footer-bottom">
				<div class="hkdev-footer-container">
					<?php if ( $show_banner ) : ?>
						<div class="hkdev-footer-pay">
							<?php if ( '' !== $banner_link ) : ?>
								<a class="hkdev-footer-pay-link" href="<?php echo esc_url( $banner_link ); ?>" target="_blank" rel="noopener noreferrer">
									<img class="hkdev-footer-pay-banner" src="<?php echo esc_url( $banner_url ); ?>" alt="<?php esc_attr_e( 'Accepted payment methods', 'hkdev-shop-elements' ); ?>" loading="lazy">
								</a>
							<?php else : ?>
								<img class="hkdev-footer-pay-banner" src="<?php echo esc_url( $banner_url ); ?>" alt="<?php esc_attr_e( 'Accepted payment methods', 'hkdev-shop-elements' ); ?>" loading="lazy">
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<p class="hkdev-footer-copy"><?php echo wp_kses_post( $copyright ); ?></p>
				</div>
			</div>

			<?php if ( 'yes' === $atts['show_backtotop'] ) : ?>
				<button type="button" class="hkdev-footer-top" aria-label="<?php esc_attr_e( 'Back to top', 'hkdev-shop-elements' ); ?>">
					<i class="fa-solid fa-chevron-up"></i>
				</button>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
