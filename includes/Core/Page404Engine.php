<?php
/**
 * HKDEV 404 Engine (HKDEV Shop Elements plugin).
 *
 * Custom 404 page with search and popular products. Self-contained.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Page404Engine {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function page404_shortcode( $atts = [] ) {
		$this->enqueue_assets();

		$atts = shortcode_atts(
			[
				'title'         => __( '404', 'hkdev-shop-elements' ),
				'heading'       => __( 'Oops! Page Not Found', 'hkdev-shop-elements' ),
				'message'       => __( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'hkdev-shop-elements' ),
				'show_search'   => 'yes',
				'show_products' => 'yes',
				'products_count' => 4,
				'show_home_btn' => 'yes',
				'shop_url'      => '',
				'bg_color'      => '#f8f9fa',
				'accent_color'  => '#03a550',
			],
			$atts,
			'hkdev_404'
		);

		ob_start();
		$accent = esc_attr( $atts['accent_color'] );
		?>
		<div class="hkdev-404-wrap" style="--hkdev-404-accent: <?php echo $accent; ?>;">
			<div class="hkdev-404-content">
				<div class="hkdev-404-code"><?php echo esc_html( $atts['title'] ); ?></div>
				<h1 class="hkdev-404-heading"><?php echo esc_html( $atts['heading'] ); ?></h1>
				<p class="hkdev-404-message"><?php echo esc_html( $atts['message'] ); ?></p>

				<?php if ( 'yes' === $atts['show_search'] ) : ?>
					<div class="hkdev-404-search">
						<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<div class="hkdev-404-search-fields">
								<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search for products...', 'hkdev-shop-elements' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
								<input type="hidden" name="post_type" value="product">
								<button type="submit"><i class="fa-solid fa-search"></i> <?php esc_html_e( 'Search', 'hkdev-shop-elements' ); ?></button>
							</div>
						</form>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $atts['show_home_btn'] ) : ?>
					<div class="hkdev-404-actions">
						<a href="<?php echo esc_url( home_url() ); ?>" class="hkdev-404-btn hkdev-404-btn-primary">
							<i class="fa-solid fa-house"></i> <?php esc_html_e( 'Go to Homepage', 'hkdev-shop-elements' ); ?>
						</a>
						<?php
						$shop_url = $atts['shop_url'];
						if ( empty( $shop_url ) && function_exists( 'wc_get_page_id' ) ) {
							$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
						}
						if ( $shop_url ) :
							?>
							<a href="<?php echo esc_url( $shop_url ); ?>" class="hkdev-404-btn hkdev-404-btn-secondary">
								<i class="fa-solid fa-store"></i> <?php esc_html_e( 'Visit Shop', 'hkdev-shop-elements' ); ?>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( 'yes' === $atts['show_products'] && function_exists('wc_get_products') ) : ?>
				<?php
				$products = wc_get_products( [
					'limit'   => absint( $atts['products_count'] ),
					'orderby' => 'popularity',
					'order'   => 'DESC',
					'status'  => 'publish',
				] );

				if ( ! empty( $products ) ) :
					?>
					<div class="hkdev-404-products">
						<h3><?php esc_html_e( 'Popular Products', 'hkdev-shop-elements' ); ?></h3>
						<div class="hkdev-404-products-grid">
							<?php foreach ( $products as $product ) : ?>
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="hkdev-404-product-card">
									<div class="hkdev-404-product-image">
										<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<h4><?php echo esc_html( $product->get_name() ); ?></h4>
									<span class="hkdev-404-product-price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ensure 404 block assets load when the shortcode renders.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
		wp_enqueue_style( 'hkdev-elements-404-style' );
	}
}