<?php
/**
 * HKDEV Shop Elements - Standalone Shop Engine.
 *
 * Self-contained render pipeline for the product grid / trending carousel.
 * Replicates the original theme controller logic but owns its AJAX action,
 * nonce and helpers so it works with ANY WordPress theme + Elementor +
 * WooCommerce (no dependency on the hkdev-shop theme).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shop_Engine
 */
class Shop_Engine {

	const AJAX_ACTION = 'hkdev_elements_filter_products';
	const AJAX_LOAD_MORE = 'hkdev_elements_load_more_products';
	const NONCE_ACTION = 'hkdev_elements_shop_filter';

	/**
	 * @var ?Shop_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Shop_Engine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register front-end + AJAX hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'buy_now_redirect_handler' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_filter_products_callback' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_filter_products_callback' ] );
		add_action( 'wp_ajax_' . self::AJAX_LOAD_MORE, [ $this, 'ajax_load_more_products_callback' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_LOAD_MORE, [ $this, 'ajax_load_more_products_callback' ] );
	}

	/**
	 * Buy Now redirect handler.
	 *
	 * @param string $url Redirect URL.
	 * @return string
	 */
	public function buy_now_redirect_handler( $url ) {
		$buy_now = isset( $_REQUEST['hkdev_buy_now'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['hkdev_buy_now'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'yes' === $buy_now ) {
			return wc_get_checkout_url();
		}
		return $url;
	}

	/**
	 * Helper: Calculate product sales for a specific number of days.
	 *
	 * @param int $product_id Product ID.
	 * @param int $days Days to look back.
	 * @return int
	 */
	public function get_sales_by_period( $product_id, $days = 7 ) {
		if ( $days <= 0 ) {
			return 0;
		}
		global $wpdb;
		$product_id = absint( $product_id );
		$days       = absint( $days );
		$date_from  = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days", current_time( 'timestamp', true ) ) );

		$table_items = $wpdb->prefix . 'woocommerce_order_items';
		$table_meta  = $wpdb->prefix . 'woocommerce_order_itemmeta';

		$sql = $wpdb->prepare(
			"
            SELECT COALESCE(SUM(ABS(qty_meta.meta_value)), 0) AS total_qty
            FROM {$table_items} items
            INNER JOIN {$table_meta} pid_meta
                ON items.order_item_id = pid_meta.order_item_id
               AND pid_meta.meta_key = '_product_id'
               AND pid_meta.meta_value = %d
            INNER JOIN {$table_meta} qty_meta
                ON items.order_item_id = qty_meta.order_item_id
               AND qty_meta.meta_key = '_qty'
            INNER JOIN {$wpdb->posts} p ON p.ID = items.order_id
            WHERE items.order_item_type = 'line_item'
              AND p.post_type = 'shop_order'
              AND p.post_status IN ('wc-completed', 'wc-processing')
              AND p.post_date >= %s
            ",
			$product_id,
			$date_from
		);
		$total = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return is_null( $total ) ? 0 : absint( $total );
	}

	/**
	 * Get WooCommerce related product IDs for a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit Max number of IDs.
	 * @return int[]
	 */
	public function get_related_product_ids( $product_id, $limit = 8 ) {
		$product_id = absint( $product_id );
		$limit      = max( 1, absint( $limit ) );

		if ( ! $product_id || ! function_exists( 'wc_get_related_products' ) ) {
			return [];
		}

		$related = wc_get_related_products( $product_id, $limit, [ $product_id ] );

		return array_slice( array_values( array_filter( array_map( 'absint', (array) $related ) ) ), 0, $limit );
	}

	/**
	 * Render a single product card.
	 *
	 * @param int    $post_id       Product ID.
	 * @param int    $trending_days Trending lookback days.
	 * @param bool   $is_carousel   Whether inside a carousel slide.
	 * @param string $image_size       WooCommerce image size for the card image.
	 * @param bool   $show_hover_image Reveal the second gallery image on hover.
	 * @return void
	 */
	public function render_single_product_card( $post_id, $trending_days = 0, $is_carousel = false, $image_size = 'woocommerce_thumbnail', $show_hover_image = false ) {
		global $product;
		$previous_product = $product;
		$product          = wc_get_product( $post_id );
		if ( ! $product ) {
			$product = $previous_product;
			return;
		}

		$wc_defaults = [
			[ 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 ],
			[ 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 ],
			[ 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 ],
			[ 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 ],
			[ 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 ],
			[ 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 ],
			[ 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 ],
		];
		foreach ( $wc_defaults as $hook ) {
			remove_action( $hook[0], $hook[1], $hook[2] );
		}

		$stock_status = $product->get_stock_status();
		$is_variable  = $product->is_type( 'variable' );
		$permalink    = get_permalink( $post_id );
		$checkout_url = wc_get_checkout_url();

		// Second gallery image, revealed over the featured one on hover. Cards
		// stay single-image when the product only has the featured image.
		$hover_image_id = 0;
		if ( $show_hover_image ) {
			$gallery = $product->get_gallery_image_ids();
			if ( ! empty( $gallery ) ) {
				$hover_image_id = (int) $gallery[0];
			}
		}

		$total_sales    = (int) $product->get_total_sales();
		$trending_sales = ( $trending_days > 0 ) ? $this->get_sales_by_period( $post_id, $trending_days ) : 0;

		$card_class = $is_carousel ? 'hkdev-product-card swiper-slide' : 'hkdev-product-card';
		?>
		<div class="<?php echo esc_attr( $card_class ); ?> product type-product">
			<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

			<div class="hkdev-img-box" style="position: relative;">
				<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>

				<a href="<?php echo esc_url( $permalink ); ?>">
					<?php echo $product->get_image( $image_size ? sanitize_key( $image_size ) : 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<?php if ( $hover_image_id ) : ?>
					<span class="hkdev-hover-img" aria-hidden="true">
						<?php echo wp_get_attachment_image( $hover_image_id, $image_size ? sanitize_key( $image_size ) : 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				<?php endif; ?>

				<div class="hkdev-badge-container">
					<?php
					if ( $product->is_on_sale() ) {
						$percentage = 0;
						if ( $is_variable ) {
							$prices      = $product->get_variation_prices();
							$percentages = array();
							foreach ( $prices['regular_price'] as $key => $regular_price ) {
								if ( ! isset( $prices['sale_price'][ $key ] ) ) {
									continue;
								}
								$sale_price = $prices['sale_price'][ $key ];
								if ( $sale_price < $regular_price && (float) $regular_price > 0 ) {
									$percentages[] = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
								}
							}
							$percentage = ! empty( $percentages ) ? max( $percentages ) : 0;
						} else {
							$regular_price = (float) $product->get_regular_price();
							$sale_price    = (float) $product->get_sale_price();
							if ( $regular_price > 0 ) {
								$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
							}
						}
						if ( $percentage > 0 ) {
							echo '<span class="hkdev-badge hkdev-sale-badge">' . esc_html( $percentage ) . '% ' . esc_html__( 'Off', 'hkdev-shop-elements' ) . '</span>';
						}
					}
					if ( $trending_sales >= 3 ) {
						$label = ( 1 === (int) $trending_days ) ? __( 'Hot Today', 'hkdev-shop-elements' ) : __( 'Trending', 'hkdev-shop-elements' );
						echo '<span class="hkdev-badge hkdev-trending-badge">' . esc_html( $label ) . '</span>';
					} elseif ( $total_sales >= 15 ) {
						echo '<span class="hkdev-badge hkdev-best-seller-badge">' . esc_html__( 'Best Seller', 'hkdev-shop-elements' ) . '</span>';
					}
					?>
				</div>
			</div>

			<div class="hkdev-content-box">
				<div class="hkdev-meta-row">
					<span class="hkdev-cat-label"><?php echo wp_kses_post( wc_get_product_category_list( $post_id ) ); ?></span>
					<span class="hkdev-stock-dot <?php echo esc_attr( $stock_status ); ?>" title="<?php echo 'instock' === $stock_status ? esc_attr__( 'In Stock', 'hkdev-shop-elements' ) : esc_attr__( 'Out of Stock', 'hkdev-shop-elements' ); ?>"></span>
				</div>

				<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
				<h2 class="hkdev-title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h2>

				<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
				<div class="hkdev-price-container">
					<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>

				<?php if ( $is_variable ) : ?>
					<?php
					list( $variation_attributes, $variation_data ) = $this->get_variation_modal_data( $product );
					?>
					<div class="hkdev-variation-data" style="display:none"
						data-attributes="<?php echo esc_attr( wp_json_encode( $variation_attributes ) ); ?>"
						data-variations="<?php echo esc_attr( wp_json_encode( $variation_data ) ); ?>"></div>
				<?php endif; ?>

				<div class="hkdev-footer-actions">
					<?php if ( 'instock' === $stock_status ) : ?>
						<div class="hkdev-action-group">
							<?php if ( $is_variable ) : ?>
								<button type="button"
									class="hkdev-order-btn hkdev-open-variation"
									data-product-id="<?php echo esc_attr( $post_id ); ?>"><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?php echo esc_html__( 'Buy Now', 'hkdev-shop-elements' ); ?></button>
							<?php else : ?>
								<button type="button"
									class="hkdev-order-btn hkdev-buy-now"
									data-product-id="<?php echo esc_attr( $post_id ); ?>"
									data-checkout_url="<?php echo esc_url( $checkout_url ); ?>"><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?php echo esc_html__( 'Buy Now', 'hkdev-shop-elements' ); ?></button>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<button disabled class="hkdev-btn-disabled"><?php echo esc_html__( 'Out of Stock', 'hkdev-shop-elements' ); ?></button>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		foreach ( $wc_defaults as $hook ) {
			add_action( $hook[0], $hook[1], $hook[2] );
		}

		$product = $previous_product;
	}

	/**
	 * Variation modal markup (populated on demand by shop.js).
	 *
	 * Shared by the shop grids and the Catalog widget: the "Buy Now" button of
	 * a variable product only works when this markup exists inside the widget
	 * wrapper that shop.js looks up.
	 *
	 * @return string
	 */
	public function variation_modal_html() {
		ob_start();
		?>
		<div class="hkdev-variation-modal" style="display:none;">
			<div class="hkdev-vm-overlay"></div>
			<div class="hkdev-vm-box">
				<div class="hkdev-vm-header">
					<img class="hkdev-vm-thumb" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="">
					<h3 class="hkdev-vm-title"></h3>
					<button type="button" class="hkdev-vm-close" aria-label="<?php esc_attr_e( 'Close', 'hkdev-shop-elements' ); ?>">&times;</button>
				</div>
				<div class="hkdev-vm-body">
					<p class="hkdev-vm-note"><?php echo esc_html__( 'Select Size, Color, and other options that are in stock.', 'hkdev-shop-elements' ); ?></p>
					<div class="hkdev-vm-attributes"></div>
					<div class="hkdev-vm-summary">
						<div class="hkdev-vm-price"></div>
						<div class="hkdev-vm-stock"><?php echo esc_html__( 'Select options', 'hkdev-shop-elements' ); ?></div>
					</div>
				</div>
				<div class="hkdev-vm-footer">
					<button type="button" class="hkdev-vm-btn hkdev-vm-add-btn"><i class="fa-solid fa-cart-plus"></i> <?php echo esc_html__( 'Add to Cart', 'hkdev-shop-elements' ); ?></button>
					<button type="button" class="hkdev-vm-btn hkdev-vm-buy-btn"><i class="fa-solid fa-bolt"></i> <?php echo esc_html__( 'Buy Now', 'hkdev-shop-elements' ); ?></button>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the attribute groups + variation data used by the variation modal.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return array [ attribute_groups, variations ]
	 */
	public function get_variation_modal_data( $product ) {
		$attribute_groups = array();
		$variations       = array();

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return array( $attribute_groups, $variations );
		}

		$available = $product->get_available_variations();
		$value_map = array();

		foreach ( $available as $variation_data ) {
			$variation_id = (int) $variation_data['variation_id'];
			$variation    = wc_get_product( $variation_id );

			// get_available_variations() can return an empty price_html, which
			// left the quick view modal showing a stale price; fall back to the
			// variation object itself whenever the value is missing.
			$price_html = isset( $variation_data['price_html'] ) ? $variation_data['price_html'] : '';
			if ( '' === $price_html && $variation ) {
				$price_html = '<span class="price">' . $variation->get_price_html() . '</span>';
			}

			$variations[] = array(
				'variation_id' => $variation_id,
				'attributes'   => $variation_data['attributes'],
				'price_html'   => $price_html,
				'is_in_stock'  => ! empty( $variation_data['is_in_stock'] ),
				'image'        => ! empty( $variation_data['image']['src'] ) ? $variation_data['image']['src'] : '',
				'stock_qty'    => $variation ? $variation->get_stock_quantity() : null,
			);

			foreach ( $variation_data['attributes'] as $attr_key => $attr_value ) {
				if ( '' === $attr_value || null === $attr_value ) {
					continue;
				}
				$value_map[ $attr_key ][ (string) $attr_value ] = true;
			}
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_variation() ) {
				continue;
			}

			$attr_key = 'attribute_' . sanitize_title( $attribute->get_name() );
			if ( empty( $value_map[ $attr_key ] ) ) {
				continue;
			}

			$options = array();
			foreach ( array_keys( $value_map[ $attr_key ] ) as $slug ) {
				$label = $slug;
				if ( $attribute->is_taxonomy() ) {
					$term = get_term_by( 'slug', $slug, $attribute->get_name() );
					if ( $term && ! is_wp_error( $term ) ) {
						$label = $term->name;
					}
				}
				$options[] = array(
					'value' => $slug,
					'label' => $label,
				);
			}

			$attribute_groups[] = array(
				'key'     => $attr_key,
				'label'   => wc_attribute_label( $attribute->get_name() ),
				'options' => $options,
			);
		}

		return array( $attribute_groups, $variations );
	}

	/**
	 * Build the WP_Query arguments for a product listing.
	 *
	 * Shared by the category-tab filter and the "Load More" request so every
	 * batch of products is queried exactly the same way.
	 *
	 * @param array $params Listing parameters.
	 * @return array
	 */
	protected function build_product_query_args( $params = [] ) {
		$params = wp_parse_args(
			$params,
			[
				'category'         => '',
				'exclude'          => '',
				'tags'             => '',
				'brands'           => '',
				'exclude_ids'      => [],
				'limit'            => 12,
				'paged'            => 1,
				'type'             => 'recent',
				'days'             => 0,
				'order_by'         => 'DESC',
				'include_children' => true,
				'on_sale'          => 'no',
				'featured'         => 'no',
				'stock_status'     => '',
				'product_ids'      => '',
			]
		);

		$order = ( 'ASC' === strtoupper( (string) $params['order_by'] ) ) ? 'ASC' : 'DESC';
		$type  = (string) $params['type'];

		$manual_ids = array_values(
			array_unique(
				array_filter(
					wp_parse_id_list(
						is_array( $params['product_ids'] )
							? $params['product_ids']
							: explode( ',', (string) $params['product_ids'] )
					)
				)
			)
		);

		if ( ! empty( $manual_ids ) && in_array( $type, [ 'best_selling', 'trending' ], true ) ) {
			$limit  = max( 1, (int) $params['limit'] );
			$paged  = max( 1, (int) $params['paged'] );
			$offset = ( $paged - 1 ) * $limit;
			$slice  = array_slice( $manual_ids, $offset, $limit );

			$args = [
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'post__in'               => ! empty( $slice ) ? $slice : [ 0 ],
				'orderby'                => 'post__in',
				'posts_per_page'         => max( 1, count( $slice ) ),
				'paged'                  => 1,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			];

			$exclude_ids = wp_parse_id_list( (array) $params['exclude_ids'] );
			if ( ! empty( $exclude_ids ) ) {
				$args['post__not_in'] = $exclude_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			}

			$tax_query = [ 'relation' => 'AND' ];
			$hidden    = self::hidden_from_catalog_clause();
			if ( $hidden ) {
				$tax_query[] = $hidden;
			}

			if ( 'yes' === $params['featured'] ) {
				$tax_query[] = [
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				];
			}

			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query;
			}

			if ( ! empty( $params['stock_status'] ) ) {
				$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_stock_status',
						'value'   => sanitize_key( $params['stock_status'] ),
						'compare' => '=',
					],
				];
			}

			if ( 'yes' === $params['on_sale'] && function_exists( 'wc_get_product_ids_on_sale' ) ) {
				$on_sale_ids = wp_parse_id_list( (array) wc_get_product_ids_on_sale() );
				$on_sale_ids = array_values( array_intersect( $manual_ids, $on_sale_ids ) );
				$slice       = array_values( array_intersect( $slice, $on_sale_ids ) );
				if ( empty( $slice ) ) {
					$slice = [ 0 ];
				}
				$args['post__in']       = $slice;
				$args['posts_per_page'] = count( $slice );
			}

			return $args;
		}

		$args = [
			'post_type'      => 'product',
			'posts_per_page' => max( 1, (int) $params['limit'] ),
			'paged'          => max( 1, (int) $params['paged'] ),
			'post_status'    => 'publish',
		];

		$exclude_ids = wp_parse_id_list( (array) $params['exclude_ids'] );
		if ( ! empty( $exclude_ids ) ) {
			$args['post__not_in'] = $exclude_ids; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}

		$meta_query = [];

		if ( 'best_selling' === $type || 'trending' === $type ) {
			// Sales are stored in the "total_sales" meta; order by that clause so
			// the ranking is numeric instead of alphabetical.
			$meta_query['hkdev_total_sales'] = [
				'key'     => 'total_sales',
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			];
		}

		if ( 'best_selling' === $type ) {
			$args['orderby'] = [
				'hkdev_total_sales' => $order,
				'date'              => 'DESC',
				'ID'                => $order,
			];
		} elseif ( 'trending' === $type ) {
			$args['orderby'] = [
				'hkdev_total_sales' => 'DESC',
				'date'              => 'DESC',
				'ID'                => 'DESC',
			];

			// Trending = recently published products that sell the most. A zero
			// value falls back to a 30 day window so the listing is never empty.
			$days = (int) $params['days'];
			if ( $days <= 0 ) {
				$days = 30;
			}
			$args['date_query'] = [ [ 'after' => $days . ' days ago' ] ];
		} else {
			// ID is the tiebreaker: products often share the same post_date, and
			// MySQL gives no order guarantee for ties — which made the paginated
			// "Load More" batches overlap and repeat the same products.
			$args['orderby'] = [
				'date' => $order,
				'ID'   => $order,
			];
		}

		if ( 'yes' === $params['on_sale'] && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$on_sale_ids = wp_parse_id_list( (array) wc_get_product_ids_on_sale() );
			if ( empty( $on_sale_ids ) ) {
				$on_sale_ids = [ 0 ];
			}
			if ( ! empty( $args['post__in'] ) ) {
				$on_sale_ids = array_values( array_intersect( (array) $args['post__in'], $on_sale_ids ) );
				if ( empty( $on_sale_ids ) ) {
					$on_sale_ids = [ 0 ];
				}
			}
			$args['post__in'] = $on_sale_ids;
		}

		$tax_query = [ 'relation' => 'AND' ];

		if ( ! empty( $params['category'] ) ) {
			$tax_query[] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => array_map( 'trim', explode( ',', (string) $params['category'] ) ),
				'include_children' => (bool) $params['include_children'],
				'operator'         => 'IN',
			];
		}

		if ( ! empty( $params['exclude'] ) ) {
			$tax_query[] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => array_map( 'trim', explode( ',', (string) $params['exclude'] ) ),
				'include_children' => (bool) $params['include_children'],
				'operator'         => 'NOT IN',
			];
		}

		if ( ! empty( $params['tags'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', (string) $params['tags'] ) ),
				'operator' => 'IN',
			];
		}

		if ( ! empty( $params['brands'] ) && taxonomy_exists( 'product_brand' ) ) {
			$tax_query[] = [
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', (string) $params['brands'] ) ),
				'operator' => 'IN',
			];
		}

		if ( 'yes' === $params['featured'] ) {
			$tax_query[] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => 'IN',
			];
		}

		if ( count( $tax_query ) > 1 ) {
			$args['tax_query'] = $tax_query;
		}

		if ( ! empty( $params['stock_status'] ) ) {
			$meta_query['hkdev_stock_status'] = [
				'key'     => '_stock_status',
				'value'   => sanitize_key( $params['stock_status'] ),
				'compare' => '=',
			];
		}

		if ( ! empty( $meta_query ) ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return $args;
	}

	/**
	 * Count the products a listing would show.
	 *
	 * Runs the exact same query arguments as the grid, so a category tab badge
	 * always matches the products that appear when the tab is clicked. The
	 * stored WooCommerce term count cannot be used for this: it ignores the
	 * widget filters (on sale, featured, stock, exclusions) and the products of
	 * child categories, which is why some tabs showed a wrong number.
	 *
	 * @param array  $params   Base listing parameters; the category is overridden.
	 * @param string $category Category slug(s) to count, comma separated.
	 * @return int
	 */
	protected function count_listing_products( $params, $category = '' ) {
		$params['category'] = $category;
		$params['paged']    = 1;

		$args = $this->build_product_query_args( $params );

		// Only the row count matters: skip the post / term / meta object caches.
		$args['posts_per_page']         = 1;
		$args['fields']                 = 'ids';
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;

		$query = new \WP_Query( $args );

		return (int) $query->found_posts;
	}

	/**
	 * Tax query clause that hides products set to "Hidden" catalog visibility.
	 *
	 * Returns an EMPTY array when the visibility term does not exist (which is
	 * also the case when nothing is hidden), so the query is never handed a
	 * clause whose terms cannot be resolved - an unresolvable NOT IN clause is
	 * what made whole categories come back empty. The term is looked up once and
	 * passed by term_id, which WP_Tax_Query maps to the taxonomy id itself.
	 *
	 * @return array Clause, or an empty array when there is nothing to exclude.
	 */
	public static function hidden_from_catalog_clause() {
		if ( ! taxonomy_exists( 'product_visibility' ) ) {
			return [];
		}

		$term = get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' );

		if ( ! $term || is_wp_error( $term ) ) {
			return [];
		}

		return [
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => [ (int) $term->term_id ],
			'operator' => 'NOT IN',
		];
	}

	/**
	 * Build slug => name options for a product taxonomy.
	 *
	 * Used by the Elementor controls so categories, tags and brands can be
	 * picked from a searchable dropdown instead of typing slugs by hand.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array<string,string>
	 */
	public static function term_options( $taxonomy ) {
		$options = [];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $options;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	/**
	 * Build id => title options for product pickers (Elementor / admin).
	 *
	 * @param int $limit Max products to list.
	 * @return array<int,string>
	 */
	public static function product_options( $limit = 500 ) {
		$options = [];

		if ( ! post_type_exists( 'product' ) ) {
			return $options;
		}

		$ids = get_posts(
			[
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => max( 1, min( 1000, (int) $limit ) ),
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			if ( '' === $title ) {
				continue;
			}
			$options[ (int) $id ] = $title . ' (#' . (int) $id . ')';
		}

		return $options;
	}

	/**
	 * Collect the sanitised listing parameters from a filter / load-more request.
	 *
	 * @param bool $with_paged Whether to read the requested page number.
	 * @return array
	 */
	protected function listing_params_from_request( $with_paged = false ) {
		$params = [
			'category'         => isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'exclude'          => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'tags'             => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'brands'           => isset( $_POST['brands'] ) ? sanitize_text_field( wp_unslash( $_POST['brands'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'limit'            => isset( $_POST['limit'] ) ? max( 1, min( 100, intval( wp_unslash( $_POST['limit'] ) ) ) ) : 12, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'type'             => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'recent', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'days'             => isset( $_POST['days'] ) ? intval( wp_unslash( $_POST['days'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'order_by'         => isset( $_POST['order_by'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'include_children' => ! ( isset( $_POST['include_children'] ) && 'no' === $_POST['include_children'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'on_sale'          => ( isset( $_POST['on_sale'] ) && 'yes' === $_POST['on_sale'] ) ? 'yes' : 'no', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'featured'         => ( isset( $_POST['featured'] ) && 'yes' === $_POST['featured'] ) ? 'yes' : 'no', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'stock_status'     => isset( $_POST['stock_status'] ) ? sanitize_key( wp_unslash( $_POST['stock_status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'image_size'       => isset( $_POST['image_size'] ) ? sanitize_key( wp_unslash( $_POST['image_size'] ) ) : 'woocommerce_thumbnail', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'hover_img'        => ( isset( $_POST['hover_img'] ) && 'no' === sanitize_text_field( wp_unslash( $_POST['hover_img'] ) ) ) ? 'no' : 'yes', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'product_ids'      => isset( $_POST['product_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['product_ids'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		];

		if ( $with_paged ) {
			$params['paged'] = isset( $_POST['page'] ) ? max( 1, intval( wp_unslash( $_POST['page'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		return $params;
	}

	/**
	 * AJAX callback for category tab filtering.
	 *
	 * @return void
	 */
	public function ajax_filter_products_callback() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$params = $this->listing_params_from_request();
		$style  = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : 'grid';

		$query = new \WP_Query( $this->build_product_query_args( $params ) );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->render_single_product_card( get_the_ID(), $params['days'], ( 'carousel' === $style ), $params['image_size'], 'yes' === $params['hover_img'] );
			}
			wp_reset_postdata();
		} else {
			echo '<div class="hkdev-no-product-msg">' . esc_html__( 'Product Not Found', 'hkdev-shop-elements' ) . '</div>';
		}
		wp_die();
	}

	/**
	 * AJAX callback for the "Load More" button (grid style only).
	 *
	 * Returns the next batch of product cards plus the pagination state so the
	 * button can hide itself once every product has been shown.
	 *
	 * @return void
	 */
	public function ajax_load_more_products_callback() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$params = $this->listing_params_from_request( true );
		$style  = isset( $_POST['style'] ) ? sanitize_text_field( wp_unslash( $_POST['style'] ) ) : 'grid'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$query  = new \WP_Query( $this->build_product_query_args( $params ) );

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			$this->render_single_product_card( get_the_ID(), $params['days'], ( 'carousel' === $style ), $params['image_size'], 'yes' === $params['hover_img'] );
		}
		wp_reset_postdata();
		$html = ob_get_clean();

		$max_pages = (int) $query->max_num_pages;

		wp_send_json_success(
			[
				'html'      => $html,
				'page'      => (int) $params['paged'],
				'max_pages' => $max_pages,
				'has_more'  => (int) $params['paged'] < $max_pages,
			]
		);
	}

	/**
	 * Optional section heading rendered above the product grid / carousel.
	 *
	 * Uses the same markup contract as the HKDEV Section Heading widget so both
	 * look identical: accent bar + heading (+ optional subtitle) on the left,
	 * "View All" link on the right, optionally inside a bordered box.
	 *
	 * @param array $c Heading configuration.
	 * @return string
	 */
	public function shop_heading_html( $c = [] ) {
		$c = wp_parse_args(
			is_array( $c ) ? $c : [],
			[
				'show'             => 'yes',
				'text'             => '',
				'tag'              => 'h2',
				'sub'              => '',
				'link_text'        => '',
				'link'             => '',
				'link_target'      => '',
				'link_rel'         => '',
				'link_arrow'       => 'no',

				'boxed'            => 'yes',
				'bg'               => '#ffffff',
				'border'           => 'rgba(0, 0, 0, 0.09)',
				'border_w'         => '1px',
				'radius'           => '8px',
				'pad'              => '11px 18px 11px 18px',
				'gap'              => '18px',

				'accent'           => 'yes',
				'accent_color'     => '#03a550',
				'accent_w'         => '5px',
				'accent_h'         => '18px',
				'accent_r'         => '999px',

				'color'            => '#f06724',
				'size'             => '18px',
				'weight'           => '700',
				'lh'               => '1.3',
				'ls'               => 'normal',
				'tt'               => 'none',

				'sub_color'        => '#6b7280',
				'sub_size'         => '13px',
				'sub_weight'       => '500',
				'sub_lh'           => '1.35',
				'sub_ls'           => 'normal',
				'sub_tt'           => 'none',

				'link_color'       => '#f06724',
				'link_hover_color' => '',
				'link_hover'       => 'rgba(240, 103, 36, 0.10)',
				'link_size'        => '13px',
				'link_weight'      => '600',
				'link_ls'          => 'normal',
				'link_tt'          => 'none',
				'link_deco'        => 'none',
				'link_deco_hover'  => 'no',
				'link_radius'      => '7px',
				'link_pad'         => '6px 10px 6px 10px',
				'link_margin'      => '-6px -10px -6px -10px',
				'link_icon'        => '11px',
				'link_gap'         => '6px',
			]
		);

		if ( 'yes' !== $c['show'] ) {
			return '';
		}

		$text      = trim( wp_strip_all_tags( (string) $c['text'] ) );
		$sub       = trim( wp_strip_all_tags( (string) $c['sub'] ) );
		$link_text = trim( wp_strip_all_tags( (string) $c['link_text'] ) );

		if ( '' === $text && '' === $sub ) {
			return '';
		}

		$allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
		$tag          = in_array( $c['tag'], $allowed_tags, true ) ? $c['tag'] : 'h2';

		$classes   = [ 'hkdev-sh-heading', 'hkdev-shop-heading' ];
		$classes[] = ( 'yes' === $c['boxed'] ) ? 'is-boxed' : '';
		$classes[] = ( 'yes' === $c['accent'] ) ? 'has-accent' : '';
		$classes[] = ( 'yes' === $c['link_deco_hover'] ) ? 'has-underline-hover' : '';
		$classes   = array_filter( $classes );

		$vars = [
			'--hkdev-sh-bg'          => $c['bg'],
			'--hkdev-sh-border'      => $c['border'],
			'--hkdev-sh-bw'          => $c['border_w'],
			'--hkdev-sh-radius'      => $c['radius'],
			'--hkdev-sh-pad'         => $c['pad'],
			'--hkdev-sh-gap'         => $c['gap'],
			'--hkdev-sh-accent'      => $c['accent_color'],
			'--hkdev-sh-accent-w'    => $c['accent_w'],
			'--hkdev-sh-accent-h'    => $c['accent_h'],
			'--hkdev-sh-accent-r'    => $c['accent_r'],
			'--hkdev-sh-title'       => $c['color'],
			'--hkdev-sh-title-size'  => $c['size'],
			'--hkdev-sh-weight'      => $c['weight'],
			'--hkdev-sh-lh'          => $c['lh'],
			'--hkdev-sh-ls'          => $c['ls'],
			'--hkdev-sh-tt'          => $c['tt'],
			'--hkdev-sh-sub'         => $c['sub_color'],
			'--hkdev-sh-sub-size'    => $c['sub_size'],
			'--hkdev-sh-sub-weight'  => $c['sub_weight'],
			'--hkdev-sh-sub-lh'      => $c['sub_lh'],
			'--hkdev-sh-sub-ls'      => $c['sub_ls'],
			'--hkdev-sh-sub-tt'      => $c['sub_tt'],
			'--hkdev-sh-link'        => $c['link_color'],
			'--hkdev-sh-link-hover'  => $c['link_hover'],
			'--hkdev-sh-link-size'   => $c['link_size'],
			'--hkdev-sh-link-weight' => $c['link_weight'],
			'--hkdev-sh-link-ls'     => $c['link_ls'],
			'--hkdev-sh-link-tt'     => $c['link_tt'],
			'--hkdev-sh-link-deco'   => $c['link_deco'],
			'--hkdev-sh-link-radius' => $c['link_radius'],
			'--hkdev-sh-link-pad'    => $c['link_pad'],
			'--hkdev-sh-link-neg'    => $c['link_margin'],
			'--hkdev-sh-link-icon'   => $c['link_icon'],
			'--hkdev-sh-link-gap'    => $c['link_gap'],
		];

		// Only emitted when set, so the CSS fallback keeps the normal link colour.
		if ( '' !== (string) $c['link_hover_color'] ) {
			$vars['--hkdev-sh-link-hover-color'] = $c['link_hover_color'];
		}

		$style = '';
		foreach ( $vars as $prop => $value ) {
			$style .= $prop . ':' . sanitize_text_field( (string) $value ) . ';';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>">

			<div class="hkdev-sh-heading-left">
				<span class="hkdev-sh-heading-accent" aria-hidden="true"></span>
				<div class="hkdev-sh-heading-texts">
					<?php if ( '' !== $text ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="hkdev-sh-heading-title"><?php echo wp_kses_post( $c['text'] ); ?></<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
					<?php if ( '' !== $sub ) : ?>
						<span class="hkdev-sh-heading-sub"><?php echo wp_kses_post( $c['sub'] ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( '' !== $link_text ) : ?>
				<?php if ( ! empty( $c['link'] ) ) : ?>
					<a class="hkdev-sh-heading-link" href="<?php echo esc_url( $c['link'] ); ?>"<?php echo $c['link_target'] ? ' target="' . esc_attr( $c['link_target'] ) . '"' : ''; ?><?php echo $c['link_rel'] ? ' rel="' . esc_attr( $c['link_rel'] ) . '"' : ''; ?>>
						<span><?php echo wp_kses_post( $c['link_text'] ); ?></span>
						<?php if ( 'yes' === $c['link_arrow'] ) : ?>
							<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
						<?php endif; ?>
					</a>
				<?php else : ?>
					<span class="hkdev-sh-heading-link is-static">
						<span><?php echo wp_kses_post( $c['link_text'] ); ?></span>
						<?php if ( 'yes' === $c['link_arrow'] ) : ?>
							<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Master shop renderer (grid + carousel + optional category tabs).
	 *
	 * @param array $atts Shortcode/widget attributes.
	 * @return string
	 */
	public function master_shop_shortcode( $atts = [] ) {
		$atts = shortcode_atts(
			[
				'limit'            => 12,
				'columns'          => 4,
				'image_size'       => 'woocommerce_thumbnail',
				'category'         => '',
				'exclude'          => '',
				'tags'             => '',
				'brands'           => '',
				'on_sale'          => 'no',
				'featured'         => 'no',
				'stock_status'     => '',
				'type'             => 'recent',
				'days'             => 0,
				'order_by'         => 'DESC',
				'show_tabs'        => 'yes',
				'tabs'             => '',
				'include_children' => 'yes',
				'is_related'       => 'no',
				'id'               => 0,
				'product_ids'      => '',
				'style'            => 'grid',
				'heading'          => [],
				'carousel'         => [],
				'title_lines'      => 0,
				'hover_img'        => 'yes',
				'load_more'        => 'no',
				'load_more_text'   => __( 'Load More', 'hkdev-shop-elements' ),
			],
			$atts
		);

		$carousel = wp_parse_args(
			is_array( $atts['carousel'] ) ? $atts['carousel'] : [],
			[
				'mobile'   => 2,
				'tablet'   => 3,
				'gap'      => 20,
				'speed'    => 600,
				'autoplay' => false,
				'delay'    => 5000,
				'loop'     => false,
				'arrows'   => true,
				'dots'     => true,
			]
		);

		$unique_id            = 'hkdev-shop-' . wp_rand( 1000, 9999 );
		$include_children_val = ( 'no' === $atts['include_children'] ) ? false : true;

		$wrapper_class = 'hkdev-shop-wrapper';

		// Second gallery image on hover (grid, carousel and related listings).
		$show_hover = ( 'yes' === $atts['hover_img'] );

		$current_cat_id = 0;
		$exclude_ids    = [];

		if ( 'yes' === $atts['is_related'] ) {
			$current_product_id = ! empty( $atts['id'] ) ? absint( $atts['id'] ) : ( is_product() ? get_the_ID() : 0 );
			if ( $current_product_id ) {
				$exclude_ids[] = $current_product_id;
				$terms         = get_the_terms( $current_product_id, 'product_cat' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$cat_slugs        = wp_list_pluck( $terms, 'slug' );
					$atts['category'] = implode( ',', $cat_slugs );
				}
			}
		}

		if ( is_product_category() ) {
			$current_cat_obj = get_queried_object();
			$current_cat_id  = ( $current_cat_obj && isset( $current_cat_obj->term_id ) ) ? $current_cat_obj->term_id : 0;
			if ( empty( $atts['category'] ) && $current_cat_obj && isset( $current_cat_obj->slug ) ) {
				$atts['category'] = $current_cat_obj->slug;
			}
		}

		$product_ids = array_values(
			array_unique(
				array_filter( array_map( 'absint', explode( ',', (string) $atts['product_ids'] ) ) )
			)
		);

		$listing_params = [
			'category'         => $atts['category'],
			'exclude'          => $atts['exclude'],
			'tags'             => $atts['tags'],
			'brands'           => $atts['brands'],
			'exclude_ids'      => $exclude_ids,
			'limit'            => $atts['limit'],
			'type'             => $atts['type'],
			'days'             => $atts['days'],
			'order_by'         => $atts['order_by'],
			'include_children' => $include_children_val,
			'on_sale'          => $atts['on_sale'],
			'featured'         => $atts['featured'],
			'stock_status'     => $atts['stock_status'],
			'product_ids'      => implode( ',', $product_ids ),
		];

		$args = $this->build_product_query_args( $listing_params );

		// Explicit product ID list (Related Products widget — not Best Selling / Trending manual picks).
		if ( ! empty( $product_ids ) && ! in_array( $atts['type'], [ 'best_selling', 'trending' ], true ) ) {
			$args = [
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post__in'       => $product_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $product_ids ),
			];
		}

		$query = new \WP_Query( $args );

		if ( ! empty( $product_ids ) && in_array( $atts['type'], [ 'best_selling', 'trending' ], true ) ) {
			$limit              = max( 1, (int) $atts['limit'] );
			$query->found_posts = count( $product_ids );
			$query->max_num_pages = (int) max( 1, ceil( count( $product_ids ) / $limit ) );
		}
		ob_start();
		?>

		<div class="<?php echo esc_attr( $wrapper_class ); ?>" id="<?php echo esc_attr( $unique_id ); ?>"
			 data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
			 data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
			 data-image_size="<?php echo esc_attr( $atts['image_size'] ); ?>"
			 data-type="<?php echo esc_attr( $atts['type'] ); ?>"
			 data-days="<?php echo esc_attr( $atts['days'] ); ?>"
			 data-order_by="<?php echo esc_attr( $atts['order_by'] ); ?>"
			 data-exclude="<?php echo esc_attr( $atts['exclude'] ); ?>"
			 data-include_children="<?php echo esc_attr( $atts['include_children'] ); ?>"
			 data-style="<?php echo esc_attr( $atts['style'] ); ?>"
			 data-carousel="<?php echo esc_attr( wp_json_encode( $carousel ) ); ?>"
			 data-car-arrows="<?php echo esc_attr( $carousel['arrows'] ? '1' : '0' ); ?>"
			 data-car-dots="<?php echo esc_attr( $carousel['dots'] ? '1' : '0' ); ?>"
			 data-title-lines="<?php echo absint( $atts['title_lines'] ); ?>"
			 data-category="<?php echo esc_attr( $atts['category'] ); ?>"
			 data-tags="<?php echo esc_attr( $atts['tags'] ); ?>"
			 data-brands="<?php echo esc_attr( $atts['brands'] ); ?>"
			 data-on_sale="<?php echo esc_attr( $atts['on_sale'] ); ?>"
			 data-featured="<?php echo esc_attr( $atts['featured'] ); ?>"
			 data-stock_status="<?php echo esc_attr( $atts['stock_status'] ); ?>"
			 data-product_ids="<?php echo esc_attr( implode( ',', $product_ids ) ); ?>"
			 data-hover-img="<?php echo esc_attr( $atts['hover_img'] ); ?>"
			 data-hkdev-elements="1">

			<?php echo $this->shop_heading_html( is_array( $atts['heading'] ) ? $atts['heading'] : [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( 'yes' === $atts['show_tabs'] ) : ?>
				<?php
				$excluded_slugs = ! empty( $atts['exclude'] ) ? array_map( 'trim', explode( ',', $atts['exclude'] ) ) : [];
				$manual_tabs    = array_values( array_filter( array_map( 'trim', explode( ',', (string) $atts['tabs'] ) ) ) );
				$categories     = [];

				if ( ! empty( $manual_tabs ) ) {
					foreach ( $manual_tabs as $slug ) {
						if ( in_array( $slug, $excluded_slugs, true ) ) {
							continue;
						}
						$term = get_term_by( 'slug', $slug, 'product_cat' );
						if ( $term && ! is_wp_error( $term ) ) {
							$categories[] = $term;
						}
					}
				} else {
					$get_terms_args = [ 'taxonomy' => 'product_cat', 'hide_empty' => true ];

					if ( ! empty( $atts['category'] ) ) {
						$first_slug  = array_map( 'trim', explode( ',', $atts['category'] ) )[0];
						$parent_term = get_term_by( 'slug', $first_slug, 'product_cat' );
						if ( $parent_term ) {
							$get_terms_args['parent'] = $parent_term->term_id;
						}
					} else {
						if ( $current_cat_id > 0 ) {
							$get_terms_args['parent'] = $current_cat_id;
						} else {
							$get_terms_args['parent'] = 0;
						}
					}

					if ( ! empty( $excluded_slugs ) ) {
						$ex_ids = [];
						foreach ( $excluded_slugs as $es ) {
							$t = get_term_by( 'slug', $es, 'product_cat' );
							if ( $t ) {
								$ex_ids[] = $t->term_id;
							}
						}
						if ( ! empty( $ex_ids ) ) {
							$get_terms_args['exclude'] = $ex_ids;
						}
					}

					$categories = get_terms( $get_terms_args );
				}
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
					?>
					<div class="hkdev-tabs-container">
						<button type="button" class="hkdev-tabs-arrow hkdev-tabs-prev" aria-label="<?php esc_attr_e( 'Previous categories', 'hkdev-shop-elements' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
						<div class="hkdev-tabs-scroll">
							<button class="hkdev-tab-item active" data-slug="<?php echo esc_attr( $atts['category'] ); ?>"><?php echo esc_html__( 'All', 'hkdev-shop-elements' ); ?> <span class="hkdev-tab-count"><?php echo absint( $query->found_posts ); ?></span></button>
							<?php foreach ( $categories as $cat ) : ?>
								<button class="hkdev-tab-item" data-slug="<?php echo esc_attr( $cat->slug ); ?>">
									<?php echo esc_html( $cat->name ); ?> <span class="hkdev-tab-count"><?php echo absint( $this->count_listing_products( $listing_params, $cat->slug ) ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>
						<button type="button" class="hkdev-tabs-arrow hkdev-tabs-next" aria-label="<?php esc_attr_e( 'Next categories', 'hkdev-shop-elements' ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="hkdev-grid-container">
				<div class="hkdev-ajax-loader"><div class="hkdev-spinner"><i class="fa-solid fa-spinner fa-spin"></i></div></div>

				<?php if ( 'carousel' === $atts['style'] ) : ?>
					<div class="swiper hkdev-swiper-container hkdev-loading-carousel">
						<div class="swiper-wrapper hkdev-shop-grid">
							<?php if ( $query->have_posts() ) : ?>
								<?php
								while ( $query->have_posts() ) {
									$query->the_post();
									$this->render_single_product_card( get_the_ID(), (int) $atts['days'], true, $atts['image_size'], $show_hover );
								}
								wp_reset_postdata();
								?>
							<?php else : ?>
								<div class="hkdev-no-product-msg"><?php echo esc_html__( 'Product Not Found', 'hkdev-shop-elements' ); ?></div>
							<?php endif; ?>
						</div>
						<div class="hkdev-carousel-dots-wrap">
							<div class="hkdev-carousel-dots swiper-pagination"></div>
						</div>
					</div>
					<div class="hkdev-nav-btn hkdev-prev-<?php echo esc_attr( $unique_id ); ?> kh-prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></div>
					<div class="hkdev-nav-btn hkdev-next-<?php echo esc_attr( $unique_id ); ?> kh-next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
				<?php else : ?>
					<div class="hkdev-shop-grid hkdev-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
						<?php if ( $query->have_posts() ) : ?>
							<?php
							while ( $query->have_posts() ) {
								$query->the_post();
								$this->render_single_product_card( get_the_ID(), (int) $atts['days'], false, $atts['image_size'], $show_hover );
							}
							wp_reset_postdata();
							?>
						<?php else : ?>
							<div class="hkdev-no-product-msg"><?php echo esc_html__( 'Product Not Found', 'hkdev-shop-elements' ); ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php
			$max_pages  = (int) $query->max_num_pages;
			$show_more  = ( 'yes' === $atts['load_more'] && $max_pages > 1 );
			$more_label = ( '' !== (string) $atts['load_more_text'] ) ? $atts['load_more_text'] : __( 'Load More', 'hkdev-shop-elements' );
			?>
			<?php if ( $show_more ) : ?>
				<div class="hkdev-load-more-wrap">
					<button type="button" class="hkdev-load-more"
							data-page="1"
							data-label="<?php echo esc_attr( $more_label ); ?>"
							data-loading-label="<?php echo esc_attr__( 'Loading…', 'hkdev-shop-elements' ); ?>">
						<span class="hkdev-lm-label"><?php echo esc_html( $more_label ); ?></span>
						<span class="hkdev-lm-spinner" aria-hidden="true"></span>
					</button>
					<span class="hkdev-lm-end"><?php echo esc_html__( 'No more products', 'hkdev-shop-elements' ); ?></span>
				</div>
			<?php endif; ?>

			<!-- Variation Modal (populated on demand by shop.js) -->
			<?php echo $this->variation_modal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<!-- Toast Notification -->
		<div id="hkdev-toast-master"><div class="hkdev-toast-inner"><span class="hkdev-toast-icon"><i class="fa-solid fa-check-circle"></i></span><span class="hkdev-toast-text"><?php echo esc_html__( 'Successfully added!', 'hkdev-shop-elements' ); ?></span></div></div>

		<?php
		return ob_get_clean();
	}
}
