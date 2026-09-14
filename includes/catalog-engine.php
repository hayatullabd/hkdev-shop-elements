<?php
/**
 * HKDEV Catalog Engine (HKDEV Shop Elements plugin).
 *
 * Professional search + filter + sort module for the WooCommerce Shop and
 * product-category / product-tag archives, and for any page (via the
 * [hkdev_catalog] shortcode or the "HKDEV Catalog" Elementor widget).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Catalog_Engine {

	const AJAX_ACTION  = 'hkdev_elements_catalog_filter';
	const NONCE_ACTION = 'hkdev_elements_catalog_filter';

	const P_SEARCH = 'hk_s';
	const P_CAT    = 'hk_cat';
	const P_BRAND  = 'hk_brand';
	const P_TAG    = 'hk_tag';
	const P_ATTR   = 'hk_attr';
	const P_MIN    = 'hk_min';
	const P_MAX    = 'hk_max';
	const P_STOCK  = 'hk_stock';
	const P_SALE   = 'hk_sale';
	const P_RATING = 'hk_rating';
	const P_SORT   = 'hk_sort';
	const P_PAGE   = 'hk_page';
	const P_APPEND = 'hk_append';

	/**
	 * @var ?Catalog_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Catalog_Engine
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
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_filter' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'ajax_filter' ] );
		add_shortcode( 'hkdev_catalog', [ $this, 'shortcode' ] );

		// WooCommerce archive integration (Shop / product category / product tag).
		add_action( 'woocommerce_product_query', [ $this, 'apply_archive_query' ] );
		add_action( 'woocommerce_before_shop_loop', [ $this, 'render_archive_controls' ], 15 );
	}

	/* ---------------------------------------------------------------------
	 * Request parsing
	 * ------------------------------------------------------------------- */

	/**
	 * Read and sanitize the filter parameters from a request array.
	 *
	 * @param array $source Usually $_GET or $_POST.
	 * @return array
	 */
	private function read_params( $source ) {
		$scalar = static function ( $key, $default = '' ) use ( $source ) {
			if ( ! isset( $source[ $key ] ) ) {
				return $default;
			}
			$value = wp_unslash( $source[ $key ] );
			if ( is_array( $value ) ) {
				$value = reset( $value );
			}
			return sanitize_text_field( (string) $value );
		};

		$list = static function ( $key ) use ( $source ) {
			if ( ! isset( $source[ $key ] ) ) {
				return [];
			}
			$value = wp_unslash( $source[ $key ] );
			$value = is_array( $value ) ? $value : explode( ',', (string) $value );
			return array_values( array_filter( array_map( 'sanitize_title', $value ) ) );
		};

		$params = [
			'search' => $scalar( self::P_SEARCH ),
			'sort'   => $scalar( self::P_SORT, 'newest' ),
			'min'    => $scalar( self::P_MIN ),
			'max'    => $scalar( self::P_MAX ),
			'stock'  => ( '1' === $scalar( self::P_STOCK ) ),
			'sale'   => ( '1' === $scalar( self::P_SALE ) ),
			'rating' => absint( $scalar( self::P_RATING ) ),
			'page'   => max( 1, absint( $scalar( self::P_PAGE, 1 ) ) ),
			'append' => ( '1' === $scalar( self::P_APPEND ) ),
			'cats'   => $list( self::P_CAT ),
			'brands' => $list( self::P_BRAND ),
			'tags'   => $list( self::P_TAG ),
			'attrs'  => [],
		];

		if ( isset( $source[ self::P_ATTR ] ) && is_array( $source[ self::P_ATTR ] ) ) {
			foreach ( wp_unslash( $source[ self::P_ATTR ] ) as $tax => $terms ) {
				$tax = sanitize_key( $tax );
				if ( ! $tax || ! is_array( $terms ) ) {
					continue;
				}
				$terms = array_values( array_filter( array_map( 'sanitize_title', $terms ) ) );
				if ( $terms ) {
					$params['attrs'][ $tax ] = $terms;
				}
			}
		}

		return $params;
	}

	/**
	 * Whether the parameter set contains any active filter.
	 *
	 * @param array $params Parsed params.
	 * @return bool
	 */
	private function has_filters( $params ) {
		return ( '' !== $params['search'] || $params['cats'] || $params['brands'] || $params['tags']
			|| $params['attrs'] || '' !== $params['min'] || '' !== $params['max']
			|| $params['stock'] || $params['sale'] || $params['rating'] > 0 );
	}

	/**
	 * Category / tag the current page is locked to.
	 *
	 * On a product category or product tag page the listing must only ever
	 * contain that term's products, no matter what the filter panel sends.
	 *
	 * @return array { cats: string[], tags: string[] }
	 */
	private function current_locks() {
		$locks = [ 'cats' => [], 'tags' => [] ];

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			if ( $term && ! empty( $term->slug ) ) {
				$locks['cats'][] = $term->slug;
			}
		} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
			$term = get_queried_object();
			if ( $term && ! empty( $term->slug ) ) {
				$locks['tags'][] = $term->slug;
			}
		}

		return $locks;
	}

	/**
	 * Read a comma-separated slug list from $_POST.
	 *
	 * @param string $key Request key.
	 * @return string[]
	 */
	private function post_slugs( $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return [];
		}
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		return array_values( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Query building
	 * ------------------------------------------------------------------- */

	/**
	 * Translate parsed params into WP_Query arguments.
	 *
	 * @param array $params   Parsed params.
	 * @param int   $per_page Products per page.
	 * @return array
	 */
	public function build_query_args( $params, $per_page ) {
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page > 0 ? $per_page : 12,
		];

		if ( '' !== $params['search'] ) {
			$args['s'] = $params['search'];
		}

		$visibility = function_exists( 'wc_get_product_visibility_term_ids' ) ? wc_get_product_visibility_term_ids() : [];
		if ( ! empty( $visibility['exclude-from-catalog'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => [ $visibility['exclude-from-catalog'] ],
				'operator' => 'NOT IN',
			];
		}

		if ( $params['cats'] ) {
			$args['tax_query'][] = [
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => $params['cats'],
				'operator'         => 'IN',
				'include_children' => true,
			];
		}
		if ( $params['tags'] ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => $params['tags'],
				'operator' => 'IN',
			];
		}
		if ( $params['brands'] && taxonomy_exists( 'product_brand' ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => $params['brands'],
				'operator' => 'IN',
			];
		}
		foreach ( $params['attrs'] as $tax => $terms ) {
			if ( taxonomy_exists( $tax ) ) {
				$args['tax_query'][] = [
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $terms,
					'operator' => 'IN',
				];
			}
		}

		$min = ( '' !== $params['min'] ) ? (float) $params['min'] : null;
		$max = ( '' !== $params['max'] ) ? (float) $params['max'] : null;
		if ( null !== $min && null !== $max ) {
			$args['meta_query'][] = [ 'key' => '_price', 'value' => [ $min, $max ], 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ];
		} elseif ( null !== $min ) {
			$args['meta_query'][] = [ 'key' => '_price', 'value' => $min, 'compare' => '>=', 'type' => 'NUMERIC' ];
		} elseif ( null !== $max ) {
			$args['meta_query'][] = [ 'key' => '_price', 'value' => $max, 'compare' => '<=', 'type' => 'NUMERIC' ];
		}

		if ( $params['stock'] ) {
			$args['meta_query'][] = [ 'key' => '_stock_status', 'value' => 'instock' ];
		}
		if ( $params['rating'] > 0 ) {
			$args['meta_query'][] = [ 'key' => '_wc_average_rating', 'value' => $params['rating'], 'compare' => '>=', 'type' => 'NUMERIC' ];
		}
		if ( $params['sale'] && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$on_sale          = wc_get_product_ids_on_sale();
			$args['post__in'] = ! empty( $on_sale ) ? $on_sale : [ 0 ];
		}

		switch ( $params['sort'] ) {
			case 'price_asc':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = [ 'meta_value_num' => 'ASC', 'date' => 'DESC' ];
				break;
			case 'price_desc':
				$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ];
				break;
			case 'popular':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ];
				break;
			case 'rating':
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['orderby']  = [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ];
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'newest':
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
		}

		return $args;
	}

	/**
	 * Merge our query args into an existing WP_Query (archive main query).
	 *
	 * @param \WP_Query $query Main query.
	 * @param array     $args  Args from build_query_args().
	 * @return void
	 */
	private function merge_into_query( $query, $args ) {
		foreach ( [ 'tax_query', 'meta_query' ] as $key ) {
			if ( empty( $args[ $key ] ) ) {
				continue;
			}
			$existing = (array) $query->get( $key );
			if ( ! empty( $existing ) && ! isset( $existing['relation'] ) ) {
				$existing = array_merge( [ 'relation' => 'AND' ], $existing );
			}
			$query->set( $key, array_merge( $existing, $args[ $key ] ) );
		}

		if ( ! empty( $args['s'] ) ) {
			$query->set( 's', $args['s'] );
		}
		if ( ! empty( $args['post__in'] ) ) {
			$current = (array) $query->get( 'post__in' );
			$query->set( 'post__in', $current ? array_values( array_intersect( $current, $args['post__in'] ) ) : $args['post__in'] );
		}
		if ( ! empty( $args['meta_key'] ) ) {
			$query->set( 'meta_key', $args['meta_key'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}
		if ( ! empty( $args['orderby'] ) ) {
			$query->set( 'orderby', $args['orderby'] );
		}
		if ( ! empty( $args['order'] ) ) {
			$query->set( 'order', $args['order'] );
		}
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------- */

	/**
	 * AJAX: return the filtered grid + counts + chips.
	 *
	 * @return void
	 */
	public function ajax_filter() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$params   = $this->read_params( $_POST );
		$per_page = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 12;
		$per_page = $per_page > 0 ? min( $per_page, 60 ) : 12;
		$columns  = isset( $_POST['columns'] ) ? absint( wp_unslash( $_POST['columns'] ) ) : 4;
		$columns  = ( $columns >= 1 && $columns <= 6 ) ? $columns : 4;

		$args          = $this->build_query_args( $params, $per_page );
		$args['paged'] = max( 1, (int) $params['page'] );
		$query         = new \WP_Query( $args );

		// Locks are reported by the client only so they can be excluded from the
		// chips; the actual scoping is already inside $params (hk_cat / hk_tag).
		$locks = [ 'cats' => $this->post_slugs( 'hk_locked_cats' ), 'tags' => [] ];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce was verified above.
		$wishlist = ! isset( $_POST['wishlist_btn'] ) || 'no' !== sanitize_text_field( wp_unslash( $_POST['wishlist_btn'] ) );
		$hover    = ! isset( $_POST['hover_img'] ) || 'no' !== sanitize_text_field( wp_unslash( $_POST['hover_img'] ) );

		wp_send_json_success(
			[
				'html'       => $this->grid_html( $query, $columns, (bool) $params['append'], $wishlist, $hover ),
				'chips_html' => $this->chips_html( $params, $locks ),
				'count_html' => $this->count_html( $query, $params ),
				'found'      => (int) $query->found_posts,
				'page'       => (int) $params['page'],
				'max_pages'  => (int) $query->max_num_pages,
				'has_more'   => (int) $params['page'] < (int) $query->max_num_pages,
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------- */

	/**
	 * [hkdev_catalog] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'columns'      => 4,
				'per_page'     => 12,
				'categories'   => '',
				'show_search'  => 'yes',
				'show_sort'    => 'yes',
				'show_filters' => 'yes',
				'show_view'    => 'yes',
				'default_view' => 'grid',
				'wishlist_btn' => 'yes',
				'hover_img'    => 'yes',
			],
			$atts,
			'hkdev_catalog'
		);

		return $this->render( $atts, false );
	}

	/**
	 * Ensure the catalog assets are enqueued (shortcode / widget render).
	 *
	 * @param bool $with_wishlist Also load the wishlist assets (the cards carry
	 *                            a heart everywhere except the archive bar,
	 *                            whose loop belongs to the theme).
	 * @return void
	 */
	private function enqueue_assets( $with_wishlist = false ) {
		wp_enqueue_style( 'hkdev-elements-catalog-style' );
		wp_enqueue_script( 'hkdev-elements-catalog-js' );

		if ( $with_wishlist ) {
			wp_enqueue_style( 'hkdev-elements-wishlist-style' );
			wp_enqueue_script( 'hkdev-elements-wishlist-js' );
		}
	}

	/**
	 * Render the full AJAX catalog block (widget / shortcode / archive bar).
	 *
	 * @param array $atts       Configuration.
	 * @param bool  $is_archive Whether this is the WooCommerce archive bar.
	 * @return string
	 */
	public function render( $atts = [], $is_archive = false ) {
		if ( ! function_exists( 'WC' ) ) {
			return '';
		}

		$columns      = isset( $atts['columns'] ) ? max( 1, min( 6, absint( $atts['columns'] ) ) ) : 4;
		$per_page     = isset( $atts['per_page'] ) ? max( 1, min( 60, absint( $atts['per_page'] ) ) ) : 12;
		$show_search  = ( ! isset( $atts['show_search'] ) || 'yes' === $atts['show_search'] );
		$show_sort    = ( ! isset( $atts['show_sort'] ) || 'yes' === $atts['show_sort'] );
		$show_filters = ( ! isset( $atts['show_filters'] ) || 'yes' === $atts['show_filters'] );

		// The grid / list switch only works on our own product grid, so it is
		// never offered on the archive bar (that loop belongs to the theme).
		$show_view    = ( ! $is_archive ) && ( ! isset( $atts['show_view'] ) || 'yes' === $atts['show_view'] );
		$default_view = ( isset( $atts['default_view'] ) && 'list' === $atts['default_view'] ) ? 'list' : 'grid';

		// Wishlist heart on every card of the catalog grid.
		$show_wishlist = ( ! isset( $atts['wishlist_btn'] ) || 'yes' === $atts['wishlist_btn'] );

		// Second gallery image on hover.
		$show_hover = ( ! isset( $atts['hover_img'] ) || 'yes' === $atts['hover_img'] );

		$this->enqueue_assets( ! $is_archive && $show_wishlist );

		// A category / tag page (or a locked "categories" attribute) always wins:
		// the listing can never be widened to other categories by the filter UI.
		$locks = $is_archive ? [ 'cats' => [], 'tags' => [] ] : $this->current_locks();
		if ( ! empty( $atts['categories'] ) ) {
			$att_cats      = array_values( array_filter( array_map( 'sanitize_title', explode( ',', $atts['categories'] ) ) ) );
			$locks['cats'] = array_values( array_unique( array_merge( $locks['cats'], $att_cats ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$params = $this->read_params( $_GET );
		if ( $locks['cats'] ) {
			$params['cats'] = $locks['cats'];
		}
		if ( $locks['tags'] ) {
			$params['tags'] = $locks['tags'];
		}

		$bounds = $this->price_bounds();

		ob_start();
		?>
		<div class="hkdev-catalog<?php echo ( $show_view && 'list' === $default_view ) ? ' hkdev-view-list' : ''; ?>" data-catalog="1"
			data-archive="<?php echo $is_archive ? '1' : '0'; ?>"
			data-view-toggle="<?php echo $show_view ? '1' : '0'; ?>"
			data-default-view="<?php echo esc_attr( $default_view ); ?>"
			data-wishlist-btn="<?php echo $show_wishlist ? 'yes' : 'no'; ?>"
			data-hover-img="<?php echo $show_hover ? 'yes' : 'no'; ?>"
			data-columns="<?php echo esc_attr( $columns ); ?>"
			data-per-page="<?php echo esc_attr( $per_page ); ?>"
			data-locked-cats="<?php echo esc_attr( implode( ',', $locks['cats'] ) ); ?>"
			data-locked-tags="<?php echo esc_attr( implode( ',', $locks['tags'] ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE_ACTION ) ); ?>">
			<?php
			echo $this->controls_html( $params, $bounds, $show_search, $show_sort, $show_filters, $is_archive, $locks, $show_view, $default_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( ! $is_archive ) {
				$args          = $this->build_query_args( $params, $per_page );
				$args['paged'] = $params['page'];
				$query         = new \WP_Query( $args );
				?>
				<div class="hkdev-cat-results">
					<div class="hkdev-cat-chips"><?php echo $this->chips_html( $params, $locks ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="hkdev-cat-count"><?php echo $this->count_html( $query, $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="hkdev-cat-grid hkdev-shop-grid hkdev-columns-<?php echo esc_attr( $columns ); ?>">
						<?php echo $this->grid_html( $query, $columns, false, $show_wishlist, $show_hover ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="hkdev-cat-foot">
						<button type="button" class="hkdev-cat-more" <?php echo ( $params['page'] >= (int) $query->max_num_pages ) ? 'hidden' : ''; ?>>
							<?php esc_html_e( 'Load More', 'hkdev-shop-elements' ); ?>
						</button>
					</div>
				</div>
				<?php
				wp_reset_postdata();

				// Variable products open this modal from their "Buy Now" button.
				echo Shop_Engine::instance()->variation_modal_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<div class="hkdev-cat-overlay"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the control bar + filter panel.
	 *
	 * @param array $params       Current params.
	 * @param array $bounds       Price bounds.
	 * @param bool  $show_search  Show search field.
	 * @param bool  $show_sort    Show sort dropdown.
	 * @param bool  $show_filters Show the filter panel.
	 * @param bool  $is_archive   Archive mode.
	 * @param array $locks        Locked cats/tags (hidden from the filter panel).
	 * @param bool  $show_view    Show the grid / list switch.
	 * @param string $default_view View that starts active.
	 * @return string
	 */
	private function controls_html( $params, $bounds, $show_search, $show_sort, $show_filters, $is_archive, $locks = [], $show_view = false, $default_view = 'grid' ) {
		$sort_options = [
			'newest'     => __( 'Newest', 'hkdev-shop-elements' ),
			'oldest'     => __( 'Oldest', 'hkdev-shop-elements' ),
			'price_asc'  => __( 'Price: Low to High', 'hkdev-shop-elements' ),
			'price_desc' => __( 'Price: High to Low', 'hkdev-shop-elements' ),
			'popular'    => __( 'Popularity', 'hkdev-shop-elements' ),
			'rating'     => __( 'Customer Rating', 'hkdev-shop-elements' ),
			'title'      => __( 'Name (A-Z)', 'hkdev-shop-elements' ),
		];

		ob_start();
		?>
		<div class="hkdev-cat-bar">
			<?php if ( $show_search ) : ?>
				<label class="hkdev-cat-search">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="search" class="hkdev-cat-search-input"
						value="<?php echo esc_attr( $params['search'] ); ?>"
						placeholder="<?php esc_attr_e( 'Search products...', 'hkdev-shop-elements' ); ?>"
						aria-label="<?php esc_attr_e( 'Search products', 'hkdev-shop-elements' ); ?>">
				</label>
			<?php endif; ?>

			<?php if ( $show_filters ) : ?>
				<button type="button" class="hkdev-cat-drawer-toggle">
					<i class="fa-solid fa-sliders"></i> <?php esc_html_e( 'Filters', 'hkdev-shop-elements' ); ?>
					<span class="hkdev-cat-drawer-count" hidden>0</span>
				</button>
			<?php endif; ?>

			<?php if ( $show_sort ) : ?>
				<div class="hkdev-cat-sort">
					<select class="hkdev-cat-sort-select" aria-label="<?php esc_attr_e( 'Sort products', 'hkdev-shop-elements' ); ?>">
						<?php foreach ( $sort_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $params['sort'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( $show_view ) : ?>
				<?php echo Shop_Engine::instance()->view_switch_html( $default_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>

		<?php if ( $show_filters ) : ?>
			<div class="hkdev-cat-panel">
				<div class="hkdev-cat-panel-head">
					<span><?php esc_html_e( 'Filters', 'hkdev-shop-elements' ); ?></span>
					<button type="button" class="hkdev-cat-clear"><?php esc_html_e( 'Clear all', 'hkdev-shop-elements' ); ?></button>
					<button type="button" class="hkdev-cat-panel-close" aria-label="<?php esc_attr_e( 'Close', 'hkdev-shop-elements' ); ?>">&times;</button>
				</div>
				<div class="hkdev-cat-panel-body">
					<?php
					if ( ! empty( $locks['cats'] ) ) {
						$locked_label = $this->term_name( 'product_cat', $locks['cats'][0] );
						echo '<div class="hkdev-cat-locked"><i class="fa-solid fa-filter"></i> ' . esc_html( sprintf( /* translators: %s: category name. */ __( 'Showing only: %s', 'hkdev-shop-elements' ), $locked_label ) ) . '</div>';
					}
					if ( empty( $locks['cats'] ) ) {
						echo $this->group_categories_html( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					echo $this->group_price_html( $params, $bounds ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->group_attributes_html( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->group_brands_html( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->group_flags_html( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<?php if ( $is_archive ) : ?>
					<div class="hkdev-cat-panel-foot">
						<button type="button" class="hkdev-cat-apply"><?php esc_html_e( 'Apply Filters', 'hkdev-shop-elements' ); ?></button>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * Filter groups
	 * ------------------------------------------------------------------- */

	/**
	 * Category checkboxes group.
	 *
	 * @param array $params Params.
	 * @return string
	 */
	private function group_categories_html( $params ) {
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'number'     => 60,
			]
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="hkdev-cat-group">
			<button type="button" class="hkdev-cat-group-head"><?php esc_html_e( 'Category', 'hkdev-shop-elements' ); ?><i class="fa-solid fa-chevron-down"></i></button>
			<div class="hkdev-cat-group-body">
				<?php foreach ( $terms as $term ) : ?>
					<label class="hkdev-cat-check">
						<input type="checkbox" name="<?php echo esc_attr( self::P_CAT ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $params['cats'], true ) ); ?>>
						<span><?php echo esc_html( $term->name ); ?></span>
						<em><?php echo esc_html( $term->count ); ?></em>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Price range group.
	 *
	 * @param array $params Params.
	 * @param array $bounds Min/max bounds.
	 * @return string
	 */
	private function group_price_html( $params, $bounds ) {
		$min_val = ( '' !== $params['min'] ) ? (float) $params['min'] : $bounds['min'];
		$max_val = ( '' !== $params['max'] ) ? (float) $params['max'] : $bounds['max'];

		ob_start();
		?>
		<div class="hkdev-cat-group">
			<button type="button" class="hkdev-cat-group-head"><?php esc_html_e( 'Price', 'hkdev-shop-elements' ); ?><i class="fa-solid fa-chevron-down"></i></button>
			<div class="hkdev-cat-group-body">
				<div class="hkdev-cat-price" data-min="<?php echo esc_attr( $bounds['min'] ); ?>" data-max="<?php echo esc_attr( $bounds['max'] ); ?>">
					<input type="range" class="hkdev-cat-price-min-range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" value="<?php echo esc_attr( $min_val ); ?>">
					<input type="range" class="hkdev-cat-price-max-range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" value="<?php echo esc_attr( $max_val ); ?>">
					<div class="hkdev-cat-price-fields">
						<input type="number" class="hkdev-cat-price-min" data-key="<?php echo esc_attr( self::P_MIN ); ?>" value="<?php echo esc_attr( $min_val ); ?>" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>">
						<span>&mdash;</span>
						<input type="number" class="hkdev-cat-price-max" data-key="<?php echo esc_attr( self::P_MAX ); ?>" value="<?php echo esc_attr( $max_val ); ?>" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>">
					</div>
					<div class="hkdev-cat-price-display">
						<span class="hkdev-cat-price-min-label"><?php echo wp_kses_post( wc_price( $min_val ) ); ?></span>
						<span class="hkdev-cat-price-max-label"><?php echo wp_kses_post( wc_price( $max_val ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Attribute filters group (pa_* taxonomies that have terms).
	 *
	 * @param array $params Params.
	 * @return string
	 */
	private function group_attributes_html( $params ) {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return '';
		}
		$taxonomies = wc_get_attribute_taxonomies();
		if ( empty( $taxonomies ) ) {
			return '';
		}

		ob_start();
		foreach ( $taxonomies as $attr ) {
			$tax = wc_attribute_taxonomy_name( $attr->attribute_name );
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true, 'number' => 60 ] );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$selected = isset( $params['attrs'][ $tax ] ) ? $params['attrs'][ $tax ] : [];
			?>
			<div class="hkdev-cat-group">
				<button type="button" class="hkdev-cat-group-head"><?php echo esc_html( $attr->attribute_label ); ?><i class="fa-solid fa-chevron-down"></i></button>
				<div class="hkdev-cat-group-body">
					<?php foreach ( $terms as $term ) : ?>
						<label class="hkdev-cat-check">
							<input type="checkbox" name="<?php echo esc_attr( self::P_ATTR . '[' . $tax . '][]' ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $selected, true ) ); ?>>
							<span><?php echo esc_html( $term->name ); ?></span>
							<em><?php echo esc_html( $term->count ); ?></em>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Brand filter group (WooCommerce Brands taxonomy, when present).
	 *
	 * @param array $params Params.
	 * @return string
	 */
	private function group_brands_html( $params ) {
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return '';
		}
		$terms = get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => true, 'number' => 60 ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="hkdev-cat-group">
			<button type="button" class="hkdev-cat-group-head"><?php esc_html_e( 'Brand', 'hkdev-shop-elements' ); ?><i class="fa-solid fa-chevron-down"></i></button>
			<div class="hkdev-cat-group-body">
				<?php foreach ( $terms as $term ) : ?>
					<label class="hkdev-cat-check">
						<input type="checkbox" name="<?php echo esc_attr( self::P_BRAND ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $params['brands'], true ) ); ?>>
						<span><?php echo esc_html( $term->name ); ?></span>
						<em><?php echo esc_html( $term->count ); ?></em>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Stock / on-sale / rating group.
	 *
	 * @param array $params Params.
	 * @return string
	 */
	private function group_flags_html( $params ) {
		ob_start();
		?>
		<div class="hkdev-cat-group">
			<button type="button" class="hkdev-cat-group-head"><?php esc_html_e( 'Availability', 'hkdev-shop-elements' ); ?><i class="fa-solid fa-chevron-down"></i></button>
			<div class="hkdev-cat-group-body">
				<label class="hkdev-cat-check">
					<input type="checkbox" name="<?php echo esc_attr( self::P_STOCK ); ?>" value="1" <?php checked( $params['stock'] ); ?>>
					<span><?php esc_html_e( 'In stock only', 'hkdev-shop-elements' ); ?></span>
				</label>
				<label class="hkdev-cat-check">
					<input type="checkbox" name="<?php echo esc_attr( self::P_SALE ); ?>" value="1" <?php checked( $params['sale'] ); ?>>
					<span><?php esc_html_e( 'On sale', 'hkdev-shop-elements' ); ?></span>
				</label>
			</div>
		</div>

		<div class="hkdev-cat-group">
			<button type="button" class="hkdev-cat-group-head"><?php esc_html_e( 'Rating', 'hkdev-shop-elements' ); ?><i class="fa-solid fa-chevron-down"></i></button>
			<div class="hkdev-cat-group-body">
				<label class="hkdev-cat-check">
					<input type="radio" name="<?php echo esc_attr( self::P_RATING ); ?>" value="" <?php checked( $params['rating'], 0 ); ?>>
					<span><?php esc_html_e( 'Any rating', 'hkdev-shop-elements' ); ?></span>
				</label>
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<label class="hkdev-cat-check hkdev-cat-rating">
						<input type="radio" name="<?php echo esc_attr( self::P_RATING ); ?>" value="<?php echo esc_attr( $i ); ?>" <?php checked( $params['rating'], $i ); ?>>
						<span class="hkdev-cat-stars">
							<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
								<i class="fa-solid fa-star<?php echo ( $s <= $i ) ? '' : ' is-empty'; ?>"></i>
							<?php endfor; ?>
							<?php if ( 5 === $i ) : ?>
								<span class="hkdev-cat-rating-label"><?php esc_html_e( '& up', 'hkdev-shop-elements' ); ?></span>
							<?php endif; ?>
						</span>
					</label>
				<?php endfor; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * Chips + helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Active filter chips.
	 *
	 * @param array $params Params.
	 * @return string
	 */
	private function chips_html( $params, $locks = [] ) {
		$chips     = [];
		$lock_cats = isset( $locks['cats'] ) ? (array) $locks['cats'] : [];

		if ( '' !== $params['search'] ) {
			/* translators: %s: search term. */
			$chips[] = [ 'label' => sprintf( __( 'Search: %s', 'hkdev-shop-elements' ), $params['search'] ), 'key' => self::P_SEARCH ];
		}
		foreach ( $params['cats'] as $slug ) {
			if ( in_array( $slug, $lock_cats, true ) ) {
				continue; // Locked by the current category page, not a user filter.
			}
			$chips[] = [ 'label' => $this->term_name( 'product_cat', $slug ), 'key' => self::P_CAT, 'value' => $slug ];
		}
		foreach ( $params['brands'] as $slug ) {
			$chips[] = [ 'label' => $this->term_name( 'product_brand', $slug ), 'key' => self::P_BRAND, 'value' => $slug ];
		}
		foreach ( $params['attrs'] as $tax => $terms ) {
			foreach ( $terms as $slug ) {
				$chips[] = [ 'label' => $this->term_name( $tax, $slug ), 'key' => self::P_ATTR . '[' . $tax . ']', 'value' => $slug ];
			}
		}
		if ( '' !== $params['min'] || '' !== $params['max'] ) {
			$chips[] = [ 'label' => __( 'Price', 'hkdev-shop-elements' ), 'key' => self::P_MIN ];
		}
		if ( $params['stock'] ) {
			$chips[] = [ 'label' => __( 'In stock', 'hkdev-shop-elements' ), 'key' => self::P_STOCK ];
		}
		if ( $params['sale'] ) {
			$chips[] = [ 'label' => __( 'On sale', 'hkdev-shop-elements' ), 'key' => self::P_SALE ];
		}
		if ( $params['rating'] > 0 ) {
			/* translators: %d: star count. */
			$chips[] = [ 'label' => sprintf( __( '%d stars & up', 'hkdev-shop-elements' ), $params['rating'] ), 'key' => self::P_RATING ];
		}

		if ( empty( $chips ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="hkdev-cat-chips-list">
			<?php foreach ( $chips as $chip ) : ?>
				<button type="button" class="hkdev-cat-chip" data-key="<?php echo esc_attr( $chip['key'] ); ?>"<?php echo isset( $chip['value'] ) ? ' data-value="' . esc_attr( $chip['value'] ) . '"' : ''; ?>>
					<?php echo esc_html( $chip['label'] ); ?> <i class="fa-solid fa-xmark"></i>
				</button>
			<?php endforeach; ?>
			<button type="button" class="hkdev-cat-clear hkdev-cat-clear-inline"><?php esc_html_e( 'Clear all', 'hkdev-shop-elements' ); ?></button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resolve a term name (falls back to the slug).
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $slug     Term slug.
	 * @return string
	 */
	private function term_name( $taxonomy, $slug ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $slug;
		}
		$term = get_term_by( 'slug', $slug, $taxonomy );
		return ( $term && ! is_wp_error( $term ) ) ? $term->name : $slug;
	}

	/**
	 * Results count markup.
	 *
	 * @param \WP_Query $query  Query.
	 * @param array     $params Params.
	 * @return string
	 */
	private function count_html( $query, $params ) {
		$per_page = (int) $query->get( 'posts_per_page' );
		$shown    = min( (int) $query->found_posts, $params['page'] * $per_page );
		/* translators: 1: shown count, 2: total count. */
		$text = sprintf( __( 'Showing %1$d of %2$d products', 'hkdev-shop-elements' ), $shown, (int) $query->found_posts );
		return '<span class="hkdev-cat-count-text">' . esc_html( $text ) . '</span>';
	}

	/**
	 * Render product cards.
	 *
	 * @param \WP_Query $query    Query.
	 * @param int       $columns  Columns.
	 * @param bool      $append   Whether this is a load-more append.
	 * @param bool      $wishlist Render the wishlist heart on the cards.
	 * @param bool      $hover    Reveal the second gallery image on hover.
	 * @return string
	 */
	private function grid_html( $query, $columns, $append, $wishlist = true, $hover = true ) {
		$engine = Shop_Engine::instance();

		if ( ! $query->have_posts() ) {
			return $append ? '' : '<div class="hkdev-no-product-msg">' . esc_html__( 'No products found.', 'hkdev-shop-elements' ) . '</div>';
		}

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			$engine->render_single_product_card( get_the_ID(), 0, false, 'woocommerce_thumbnail', $wishlist, $hover );
		}
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Min/max price across the catalog (WooCommerce lookup table).
	 *
	 * @return array
	 */
	private function price_bounds() {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_product_meta_lookup';
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT MIN(min_price) AS min_price, MAX(max_price) AS max_price FROM {$table} WHERE min_price IS NOT NULL" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$min = ( $row && null !== $row->min_price ) ? floor( (float) $row->min_price ) : 0;
		$max = ( $row && null !== $row->max_price ) ? ceil( (float) $row->max_price ) : 1000;
		if ( $max <= $min ) {
			$max = $min + 1;
		}

		return [ 'min' => $min, 'max' => $max ];
	}

	/* ---------------------------------------------------------------------
	 * WooCommerce archive integration
	 * ------------------------------------------------------------------- */

	/**
	 * Apply the URL filters to the main WooCommerce archive query.
	 *
	 * @param \WP_Query $query Product query.
	 * @return void
	 */
	public function apply_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		// Use the query's own conditionals: the global template tags are not
		// reliable yet while "woocommerce_product_query" fires.
		$is_product_archive = $query->is_post_type_archive( 'product' )
			|| $query->is_page( wc_get_page_id( 'shop' ) )
			|| $query->is_tax( [ 'product_cat', 'product_tag', 'product_brand' ] );
		if ( ! $is_product_archive ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$params = $this->read_params( $_GET );
		if ( ! $this->has_filters( $params ) ) {
			return;
		}

		$this->merge_into_query( $query, $this->build_query_args( $params, (int) $query->get( 'posts_per_page' ) ) );
	}

	/**
	 * Render the search / filter / sort bar above the archive loop.
	 *
	 * @return void
	 */
	public function render_archive_controls() {
		if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}

		// Our bar replaces the default ordering + result count so they are not duplicated.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

		echo $this->render( [ 'columns' => 4, 'per_page' => 12 ], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
