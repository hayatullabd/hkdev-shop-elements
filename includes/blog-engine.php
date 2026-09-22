<?php
/**
 * HKDEV Blog Engine (HKDEV Shop Elements plugin).
 *
 * Renders a styled blog post grid / list via the [hkdev_blog] shortcode or the
 * "HKDEV Blog" Elementor widget. Also auto-styling the site's standard blog /
 * posts-page (is_home) and post taxonomy archives (category / tag / author / date).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blog_Engine {

	/**
	 * @var ?Blog_Engine
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Blog_Engine
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
		add_shortcode( 'hkdev_blog', [ $this, 'shortcode' ] );

		// Auto-style the standard blog / posts-page, post taxonomy archives,
		// and single blog post pages when not built with Elementor.
		add_filter( 'template_include', [ $this, 'blog_template' ] );

		// Ensure assets are enqueued when the shortcode, archive or single is active.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets_check' ] );

		// Category-tab filtering for the Blog widget (same contract as the shop
		// grid: the clicked tab replaces the widget's own category filter).
		add_action( 'wp_ajax_hkdev_elements_filter_blog', [ $this, 'ajax_filter_blog' ] );
		add_action( 'wp_ajax_nopriv_hkdev_elements_filter_blog', [ $this, 'ajax_filter_blog' ] );

		// "Load more" for the Blog widget: appends the next page of the
		// currently selected category tab to the existing grid.
		add_action( 'wp_ajax_hkdev_elements_load_more_blog', [ $this, 'ajax_load_more_blog' ] );
		add_action( 'wp_ajax_nopriv_hkdev_elements_load_more_blog', [ $this, 'ajax_load_more_blog' ] );
	}

	/* ---------------------------------------------------------------------
	 * Asset enqueue
	 * ------------------------------------------------------------------- */

	/**
	 * Enqueue assets (if not already enqueued by a widget / shortcode on the
	 * current request, enqueue when the standard blog archive is shown).
	 *
	 * @return void
	 */
	public function enqueue_assets_check() {
		if ( is_admin() ) {
			return;
		}

		// Already handled by an Elementor widget / shortcode render.
		if ( wp_style_is( 'hkdev-elements-blog-style', 'enqueued' ) ) {
			return;
		}

		$should_enqueue = false;

		if ( $this->is_blog_page() ) {
			$should_enqueue = true;
		} elseif ( is_singular() ) {
			$post = get_post();
			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'hkdev_blog' ) ) {
				$should_enqueue = true;
			}
		}

		if ( $should_enqueue ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Enqueue the blog stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! wp_style_is( 'hkdev-elements-blog-style', 'enqueued' ) ) {
			wp_enqueue_style( 'hkdev-elements-blog-style' );
		}
		wp_enqueue_style( 'hkdev-elements-fontawesome' );
	}

	/**
	 * Whether the current request is a blog / post-archive page or a single
	 * blog post that we should auto-style.
	 *
	 * @return bool
	 */
	private function is_blog_page() {
		if ( is_singular( 'post' ) ) {
			return true;
		}
		if ( is_singular() ) {
			return false;
		}
		if ( is_home() ) {
			return true;
		}
		if ( is_archive() ) {
			return is_category() || is_tag() || is_author() || is_date();
		}
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Archive + single auto-styling
	 * ------------------------------------------------------------------ */

	/**
	 * Decide which plugin template (if any) to load for the blog.
	 *
	 * - Single post pages  → templates/blog-single.php
	 * - Archive / list pages → templates/blog-archive.php
	 *
	 * If the page is built with Elementor or uses the [hkdev_blog] shortcode,
	 * the theme template is kept so the widget handles rendering.
	 *
	 * @param string $template Absolute path to the theme template file.
	 * @return string
	 */
	public function blog_template( $template ) {
		if ( is_admin() ) {
			return $template;
		}
		if ( ! is_main_query() ) {
			return $template;
		}
		if ( ! $this->is_blog_page() ) {
			return $template;
		}

		// Respect Elementor-built pages.
		if ( $this->is_elementor_page() ) {
			return $template;
		}

		// Respect explicit shortcode usage — let the shortcode handle rendering.
		if ( is_singular() && has_shortcode( get_post()->post_content, 'hkdev_blog' ) ) {
			return $template;
		}

		if ( is_singular( 'post' ) ) {
			$tpl = HKDEV_ELEMENTS_PATH . 'templates/blog-single.php';
		} else {
			$tpl = HKDEV_ELEMENTS_PATH . 'templates/blog-archive.php';
		}
		if ( file_exists( $tpl ) ) {
			return $tpl;
		}

		return $template;
	}

	/**
	 * Whether the current queried page is built with Elementor.
	 *
	 * @return bool
	 */
	private function is_elementor_page() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$doc = \Elementor\Plugin::$instance->documents->get( get_queried_object_id() );
		return $doc ? $doc->is_built_with_elementor() : false;
	}

	/* ---------------------------------------------------------------------
	 * Shortcode
	 * ------------------------------------------------------------------- */

	/**
	 * [hkdev_blog] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'layout'          => 'grid',
				'columns'         => 3,
				'posts_per_page'  => 9,
				'show_image'      => 'yes',
				'show_excerpt'    => 'yes',
				'show_meta'       => 'yes',
				'show_readmore'   => 'yes',
				'readmore_text'   => __( 'Read More', 'hkdev-shop-elements' ),
				'category'        => '',
				'orderby'         => 'date',
				'order'           => 'DESC',
				'image_ratio'     => '16:9',
			],
			$atts,
			'hkdev_blog'
		);

		return $this->render( $atts, false );
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------- */

	/**
	 * Render the blog grid / list.
	 *
	 * @param array $atts       Configuration (for shortcode / widget / archive).
	 * @param bool  $is_archive Whether to use the main WP query (archive mode).
	 * @return string HTML.
	 */
	public function render( $atts, $is_archive = false ) {
		if ( $is_archive ) {
			$query = $GLOBALS['wp_query'];
		} else {
			$query = $this->build_query( $atts );
		}

		if ( ! $query->have_posts() ) {
			if ( ! $is_archive ) {
				wp_reset_postdata();
			}
			return '<p class="hkdev-blog-empty">' . esc_html__( 'No posts found.', 'hkdev-shop-elements' ) . '</p>';
		}

		$this->enqueue_assets();

		$atts          = $this->normalise_atts( $atts );
		$layout        = $atts['layout'];
		$columns       = $atts['columns'];
		$show_image    = 'yes' === $atts['show_image'];
		$show_excerpt  = 'yes' === $atts['show_excerpt'];
		$show_meta     = 'yes' === $atts['show_meta'];
		$show_readmore = 'yes' === $atts['show_readmore'];
		$readmore_text = $atts['readmore_text'];
		$image_ratio   = $atts['image_ratio'];

		// Section heading (same markup renderer as the Section Heading widget and
		// the Shop Grid, so all three stay visually identical).
		$heading_html = ( ! empty( $atts['heading'] ) && is_array( $atts['heading'] ) )
			? Shop_Engine::instance()->shop_heading_html( $atts['heading'] )
			: '';

		// Category tabs (widget context only — archives keep their pill links).
		$tabs_html = '';
		if ( ! $is_archive && 'yes' === $atts['show_tabs'] ) {
			$tabs_html = $this->render_tabs( $atts, $query );
		}

		$has_tabs  = ( '' !== $tabs_html );
		$max_pages = (int) $query->max_num_pages;
		$show_more = ( ! $is_archive && 'yes' === $atts['load_more'] && $max_pages > 1 );
		$more_label = $atts['load_more_text'];

		// Tabs and load-more both talk to the same AJAX contract, so the widget
		// only needs the config attribute when one of them is active.
		$is_ajax = ( $has_tabs || $show_more );

		if ( $is_ajax ) {
			wp_enqueue_script( 'hkdev-elements-blog-js' );
		}

		// The tab request only needs the listing params, so the heading config
		// (dozens of keys) is left out of the attribute.
		$ajax_config = $atts;
		unset( $ajax_config['heading'], $ajax_config['tabs'] );

		// BEM-style modifier: the old `hkdev-blog-{layout}` class collided with
		// the `.hkdev-blog-list` posts container and broke the list layout.
		$wrapper_class = 'hkdev-blog hkdev-blog--' . $layout . ( $is_archive ? ' hkdev-blog--archive' : '' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>"<?php echo $is_ajax ? ' data-hkdev-blog="1" data-hkdev-blog-config="' . esc_attr( wp_json_encode( $ajax_config ) ) . '"' : ''; ?>>

			<?php echo $heading_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( $is_archive ) : ?>
				<?php echo $this->render_archive_header( $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo self::render_category_pills(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php echo $tabs_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php if ( $has_tabs ) : ?>
			<div class="hkdev-blog-grid-wrap">
				<div class="hkdev-blog-loader" aria-hidden="true"><i class="fa-solid fa-spinner fa-spin"></i></div>
			<?php endif; ?>

				<div class="hkdev-blog-items">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php echo $this->render_card( get_the_ID(), $show_image, $show_excerpt, $show_meta, $show_readmore, $readmore_text, $image_ratio ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endwhile; ?>
				</div>

			<?php if ( $has_tabs ) : ?>
			</div>
			<?php endif; ?>

			<?php if ( $show_more ) : ?>
				<div class="hkdev-blog-loadmore-wrap">
					<button type="button" class="hkdev-blog-loadmore"
							data-page="1"
							data-max-pages="<?php echo esc_attr( $max_pages ); ?>"
							data-label="<?php echo esc_attr( $more_label ); ?>"
							data-loading-label="<?php echo esc_attr__( 'Loading…', 'hkdev-shop-elements' ); ?>">
						<span class="hkdev-blog-lm-label"><?php echo esc_html( $more_label ); ?></span>
						<span class="hkdev-blog-lm-spinner" aria-hidden="true"></span>
					</button>
					<span class="hkdev-blog-lm-end"><?php echo esc_html__( 'No more posts', 'hkdev-shop-elements' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $is_archive ) : ?>
				<div class="hkdev-blog-pagination">
					<?php echo $this->render_pagination( $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php else : ?>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Normalise shortcode / widget / AJAX attributes to one canonical shape so
	 * the first render and every tab request build the exact same query.
	 *
	 * @param array $atts Raw attributes.
	 * @return array
	 */
	private function normalise_atts( $atts ) {
		$atts = is_array( $atts ) ? $atts : [];

		$yes_no = static function ( $key ) use ( $atts ) {
			return ( ! isset( $atts[ $key ] ) || 'yes' === $atts[ $key ] ) ? 'yes' : 'no';
		};

		$orderby = isset( $atts['orderby'] ) ? sanitize_text_field( $atts['orderby'] ) : 'date';
		if ( ! in_array( $orderby, [ 'date', 'title', 'rand', 'comment_count', 'modified' ], true ) ) {
			$orderby = 'date';
		}

		$order = isset( $atts['order'] ) ? strtoupper( (string) $atts['order'] ) : 'DESC';
		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'DESC';
		}

		$ratio = isset( $atts['image_ratio'] ) ? sanitize_text_field( $atts['image_ratio'] ) : '16:9';
		if ( ! in_array( $ratio, [ '1:1', '4:3', '16:9', 'auto' ], true ) ) {
			$ratio = '16:9';
		}

		return [
			'layout'         => ( isset( $atts['layout'] ) && 'list' === $atts['layout'] ) ? 'list' : 'grid',
			'columns'        => max( 1, min( 6, absint( $atts['columns'] ?? 3 ) ) ),
			'posts_per_page' => max( 1, absint( $atts['posts_per_page'] ?? 9 ) ),
			'category'       => isset( $atts['category'] ) ? (string) $atts['category'] : '',
			'orderby'        => $orderby,
			'order'          => $order,
			'image_ratio'    => $ratio,
			'show_image'     => $yes_no( 'show_image' ),
			'show_excerpt'   => $yes_no( 'show_excerpt' ),
			'show_meta'      => $yes_no( 'show_meta' ),
			'show_readmore'  => $yes_no( 'show_readmore' ),
			'readmore_text'  => ! empty( $atts['readmore_text'] ) ? (string) $atts['readmore_text'] : __( 'Read More', 'hkdev-shop-elements' ),
			// Load more is opt-in as well.
			'load_more'      => ( isset( $atts['load_more'] ) && 'yes' === $atts['load_more'] ) ? 'yes' : 'no',
			'load_more_text' => ! empty( $atts['load_more_text'] ) ? (string) $atts['load_more_text'] : __( 'Load More', 'hkdev-shop-elements' ),
			// Tabs are opt-in: only an explicit "yes" turns them on.
			'show_tabs'      => ( isset( $atts['show_tabs'] ) && 'yes' === $atts['show_tabs'] ) ? 'yes' : 'no',
			'tabs'           => isset( $atts['tabs'] ) ? $atts['tabs'] : [],
			// Heading config is opaque here: it is passed straight to
			// Shop_Engine::shop_heading_html().
			'heading'        => ( isset( $atts['heading'] ) && is_array( $atts['heading'] ) ) ? $atts['heading'] : [],
		];
	}

	/**
	 * Category tab bar (widget context).
	 *
	 * @param array     $atts  Normalised attributes.
	 * @param \WP_Query $query The rendered query (for the "All" count).
	 * @return string HTML.
	 */
	private function render_tabs( $atts, $query ) {
		$terms = $this->tab_terms( $atts );

		if ( empty( $terms ) ) {
			return '';
		}

		// "All" carries the widget's own category filter, so clicking it restores
		// the widget's original listing.
		$all_slug = (string) $atts['category'];

		ob_start();
		?>
		<div class="hkdev-blog-tabs">
			<button type="button" class="hkdev-blog-tabs-arrow hkdev-blog-tabs-prev" aria-label="<?php esc_attr_e( 'Previous categories', 'hkdev-shop-elements' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
			<div class="hkdev-blog-tabs-scroll" role="tablist" aria-label="<?php esc_attr_e( 'Filter posts by category', 'hkdev-shop-elements' ); ?>">
				<button type="button" class="hkdev-blog-tab-item is-active" role="tab" aria-selected="true" data-slug="<?php echo esc_attr( $all_slug ); ?>">
					<?php esc_html_e( 'All', 'hkdev-shop-elements' ); ?>
					<span class="hkdev-blog-tab-count"><?php echo absint( $query->found_posts ); ?></span>
				</button>
				<?php foreach ( $terms as $term ) : ?>
					<button type="button" class="hkdev-blog-tab-item" role="tab" aria-selected="false" data-slug="<?php echo esc_attr( $term->slug ); ?>">
						<?php echo esc_html( $term->name ); ?>
						<span class="hkdev-blog-tab-count"><?php echo absint( $this->count_posts( $atts, $term->slug ) ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<button type="button" class="hkdev-blog-tabs-arrow hkdev-blog-tabs-next" aria-label="<?php esc_attr_e( 'Next categories', 'hkdev-shop-elements' ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Categories shown as tabs.
	 *
	 * Manual (repeater) tabs set the order; otherwise the children of the
	 * widget's own category filter are listed, and top-level categories when no
	 * filter is set.
	 *
	 * @param array $atts Normalised attributes.
	 * @return \WP_Term[]
	 */
	private function tab_terms( $atts ) {
		$manual = [];
		if ( ! empty( $atts['tabs'] ) ) {
			$raw    = is_array( $atts['tabs'] ) ? $atts['tabs'] : explode( ',', (string) $atts['tabs'] );
			$manual = array_values( array_filter( array_map( 'sanitize_title', $raw ) ) );
		}

		if ( ! empty( $manual ) ) {
			$terms = [];
			foreach ( $manual as $slug ) {
				$term = get_term_by( 'slug', $slug, 'category' );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
			return $terms;
		}

		$parent = 0;
		$slugs  = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['category'] ) ) ) );
		if ( ! empty( $slugs ) ) {
			$parent_term = get_term_by( 'slug', $slugs[0], 'category' );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent = (int) $parent_term->term_id;
			}
		}

		$terms = get_categories(
			[
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'parent'     => $parent,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		return is_wp_error( $terms ) ? [] : $terms;
	}

	/**
	 * How many posts a tab will show.
	 *
	 * Runs the same query args as the listing (page 1, one row) so the badge
	 * always matches the cards that appear when the tab is clicked.
	 *
	 * @param array  $atts     Normalised attributes.
	 * @param string $cat_slug Category slug.
	 * @return int
	 */
	private function count_posts( $atts, $cat_slug ) {
		static $cache = [];

		$key = md5( wp_json_encode( [ $atts, $cat_slug ] ) );
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$count_atts             = $atts;
		$count_atts['category'] = (string) $cat_slug;

		$args                           = $this->build_query_args( $count_atts );
		$args['posts_per_page']         = 1;
		$args['paged']                  = 1;
		$args['fields']                 = 'ids';
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;

		$query = new \WP_Query( $args );

		$cache[ $key ] = (int) $query->found_posts;

		return $cache[ $key ];
	}

	/**
	 * AJAX: re-render the cards for the clicked category tab.
	 *
	 * @return void
	 */
	public function ajax_filter_blog() {
		check_ajax_referer( 'hkdev_elements_blog_filter', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$raw_config = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';
		$config     = is_string( $raw_config ) ? json_decode( $raw_config, true ) : [];

		$atts = $this->normalise_atts( is_array( $config ) ? $config : [] );

		// The clicked tab replaces the widget's own category filter. "All" sends
		// that filter back, so the original listing is restored.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tab = isset( $_POST['category'] ) ? (string) wp_unslash( $_POST['category'] ) : '';
		$atts['category'] = implode( ',', array_filter( array_map( 'sanitize_title', explode( ',', $tab ) ) ) );

		$query = $this->build_query( $atts );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo $this->render_card( get_the_ID(), 'yes' === $atts['show_image'], 'yes' === $atts['show_excerpt'], 'yes' === $atts['show_meta'], 'yes' === $atts['show_readmore'], $atts['readmore_text'], $atts['image_ratio'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} else {
			echo '<p class="hkdev-blog-empty">' . esc_html__( 'No posts found.', 'hkdev-shop-elements' ) . '</p>';
		}
		wp_reset_postdata();

		wp_send_json_success(
			[
				'html'      => ob_get_clean(),
				'count'     => (int) $query->found_posts,
				'max_pages' => (int) $query->max_num_pages,
			]
		);
	}

	/**
	 * AJAX: append the next page of posts for the "Load more" button.
	 *
	 * The clicked tab (when present) travels in `category`, so loading more
	 * always extends the listing the visitor is currently looking at.
	 *
	 * @return void
	 */
	public function ajax_load_more_blog() {
		check_ajax_referer( 'hkdev_elements_blog_filter', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$raw_config = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';
		$config     = is_string( $raw_config ) ? json_decode( $raw_config, true ) : [];

		$atts = $this->normalise_atts( is_array( $config ) ? $config : [] );

		// Only a tab click sends a category; without one the widget's own
		// category filter (already inside the config) stays in charge.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['category'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$tab              = (string) wp_unslash( $_POST['category'] );
			$atts['category'] = implode( ',', array_filter( array_map( 'sanitize_title', explode( ',', $tab ) ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$paged = isset( $_POST['paged'] ) ? absint( wp_unslash( $_POST['paged'] ) ) : 1;
		$paged = max( 1, $paged );

		$query = $this->build_query( $atts, $paged );

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo $this->render_card( get_the_ID(), 'yes' === $atts['show_image'], 'yes' === $atts['show_excerpt'], 'yes' === $atts['show_meta'], 'yes' === $atts['show_readmore'], $atts['readmore_text'], $atts['image_ratio'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		wp_reset_postdata();

		$max_pages = (int) $query->max_num_pages;

		wp_send_json_success(
			[
				'html'      => ob_get_clean(),
				'page'      => $paged,
				'max_pages' => $max_pages,
				'has_more'  => $paged < $max_pages,
			]
		);
	}

	/**
	 * Build a WP_Query for the shortcode (non-archive) context.
	 *
	 * @param array $atts  Attributes.
	 * @param int   $paged Page to fetch (0 = use the current query var).
	 * @return \WP_Query
	 */
	private function build_query( $atts, $paged = 0 ) {
		return new \WP_Query( $this->build_query_args( $atts, $paged ) );
	}

	/**
	 * Query args for the shortcode / widget context (non-archive).
	 *
	 * Shared with the tab counters, the AJAX filter and the load-more request so
	 * the badge count and the rendered cards can never drift apart.
	 *
	 * @param array $atts  Attributes.
	 * @param int   $paged Page to fetch (0 = use the current query var).
	 * @return array
	 */
	private function build_query_args( $atts, $paged = 0 ) {
		$cat_slugs = [];
		if ( ! empty( $atts['category'] ) ) {
			$cat_slugs = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $atts['category'] ) ) ) );
		}

		$args = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, absint( $atts['posts_per_page'] ?? 9 ) ),
			'paged'               => $paged > 0 ? max( 1, absint( $paged ) ) : max( 1, get_query_var( 'paged', 1 ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		];

		if ( ! empty( $cat_slugs ) ) {
			$args['category_name'] = implode( ',', $cat_slugs );
		}

		$orderby = sanitize_text_field( $atts['orderby'] ?? 'date' );
		if ( in_array( $orderby, [ 'date', 'title', 'rand', 'comment_count', 'modified' ], true ) ) {
			$args['orderby'] = $orderby;
		}

		$order = strtoupper( $atts['order'] ?? 'DESC' );
		if ( in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$args['order'] = $order;
		}

		return $args;
	}

	/**
	 * Archive / blog-page header: eyebrow, title, description and result count.
	 *
	 * @param \WP_Query $query The main query.
	 * @return string HTML.
	 */
	private function render_archive_header( $query ) {
		$eyebrow = '';

		if ( is_home() ) {
			$page_id = (int) get_option( 'page_for_posts' );
			$title   = $page_id ? get_the_title( $page_id ) : __( 'Blog', 'hkdev-shop-elements' );
			$desc    = $page_id ? (string) get_post_field( 'post_excerpt', $page_id ) : '';
			$eyebrow = __( 'Latest Articles', 'hkdev-shop-elements' );
		} elseif ( is_category() ) {
			$title   = single_cat_title( '', false );
			$desc    = category_description();
			$eyebrow = __( 'Category', 'hkdev-shop-elements' );
		} elseif ( is_tag() ) {
			$title   = single_tag_title( '', false );
			$desc    = tag_description();
			$eyebrow = __( 'Tag', 'hkdev-shop-elements' );
		} elseif ( is_author() ) {
			$title   = get_the_author();
			$desc    = get_the_author_meta( 'description' );
			$eyebrow = __( 'Author', 'hkdev-shop-elements' );
		} else {
			$title   = wp_strip_all_tags( get_the_archive_title() );
			$desc    = wp_strip_all_tags( get_the_archive_description() );
			$eyebrow = __( 'Archive', 'hkdev-shop-elements' );
		}

		$title = trim( wp_strip_all_tags( (string) $title ) );
		$desc  = trim( wp_strip_all_tags( (string) $desc ) );
		$count = (int) $query->found_posts;

		if ( '' === $title ) {
			$title = __( 'Blog', 'hkdev-shop-elements' );
		}

		ob_start();
		?>
		<header class="hkdev-blog-archive-head">
			<?php if ( $eyebrow ) : ?>
				<span class="hkdev-blog-archive-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<?php endif; ?>
			<h1 class="hkdev-blog-archive-title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $desc ) : ?>
				<p class="hkdev-blog-archive-desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<?php if ( $count > 0 ) : ?>
				<span class="hkdev-blog-archive-count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: number of posts. */
							_n( '%d article', '%d articles', $count, 'hkdev-shop-elements' ),
							$count
						)
					);
					?>
				</span>
			<?php endif; ?>
		</header>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single blog post card.
	 *
	 * @param int    $post_id       Post ID.
	 * @param bool   $show_image    Whether to show the featured image.
	 * @param bool   $show_excerpt  Whether to show the excerpt.
	 * @param bool   $show_meta     Whether to show meta info.
	 * @param bool   $show_readmore Whether to show the read-more button.
	 * @param string $readmore_text Read-more button text.
	 * @param string $image_ratio   Image crop ratio key.
	 * @return string HTML.
	 */
	private function render_card( $post_id, $show_image, $show_excerpt, $show_meta, $show_readmore, $readmore_text, $image_ratio ) {
		$title       = get_the_title( $post_id ) ?: __( '(no title)', 'hkdev-shop-elements' );
		$permalink   = get_permalink( $post_id );
		$excerpt     = $show_excerpt ? get_the_excerpt( $post_id ) : '';
		$date        = get_the_date( get_option( 'date_format' ), $post_id );
		$author_id   = (int) get_post_field( 'post_author', $post_id );
		$author      = get_the_author_meta( 'display_name', $author_id );
		$time        = self::reading_time( $post_id );
		$categories  = get_the_category( $post_id );
		$has_image   = has_post_thumbnail( $post_id );
		$img_html    = '';

		if ( $show_image && $has_image ) {
			$img_id   = get_post_thumbnail_id( $post_id );
			$img_html = wp_get_attachment_image( $img_id, 'large' );
			$img_html = '<div class="hkdev-blog-card-img-wrap" data-ratio="' . esc_attr( $image_ratio ) . '">' . $img_html . '</div>';
		} elseif ( $show_image ) {
			$img_html = '<div class="hkdev-blog-card-img-wrap hkdev-blog-card-img-placeholder" data-ratio="' . esc_attr( $image_ratio ) . '"><span class="hkdev-blog-card-placeholder-icon"><i class="fa-solid fa-image"></i></span></div>';
		}

		$first_cat = ! empty( $categories ) ? $categories[0] : null;
		$cat_label = $first_cat ? esc_html( $first_cat->name ) : '';

		ob_start();
		?>
		<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( [ 'hkdev-blog-card' ] ); ?>>
			<?php if ( $show_image ) : ?>
				<a href="<?php echo esc_url( $permalink ); ?>" class="hkdev-blog-card-link-img">
					<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $cat_label ) : ?>
						<span class="hkdev-blog-card-category"><?php echo esc_html( $first_cat->name ); ?></span>
					<?php endif; ?>
				</a>
			<?php endif; ?>

			<div class="hkdev-blog-card-body">
				<?php if ( ! $show_image && $cat_label ) : ?>
					<span class="hkdev-blog-card-category hkdev-blog-card-category-top"><?php echo esc_html( $first_cat->name ); ?></span>
				<?php endif; ?>

				<h3 class="hkdev-blog-card-title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>

				<?php if ( $show_meta ) : ?>
					<div class="hkdev-blog-card-meta">
						<?php if ( $author ) : ?>
							<span class="hkdev-blog-author">
								<i class="fa-solid fa-user"></i> <?php echo esc_html( $author ); ?>
							</span>
						<?php endif; ?>
						<span class="hkdev-blog-date">
							<i class="fa-solid fa-calendar"></i> <?php echo esc_html( $date ); ?>
						</span>
						<?php if ( $time > 0 ) : ?>
							<span class="hkdev-blog-time">
								<i class="fa-solid fa-clock"></i> <?php echo sprintf( esc_html_x( '%d min read', 'reading time', 'hkdev-shop-elements' ), $time ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $show_excerpt && $excerpt ) : ?>
					<p class="hkdev-blog-excerpt"><?php echo wp_kses_post( wp_strip_all_tags( $excerpt ) ); ?></p>
				<?php endif; ?>

				<?php if ( $show_readmore ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="hkdev-blog-readmore">
						<?php echo esc_html( $readmore_text ); ?>
						<i class="fa-solid fa-arrow-right"></i>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Category filter pills for the archive page.
	 *
	 * @return string HTML.
	 */
	public static function render_category_pills() {
		$cats = get_categories( [
			'taxonomy'   => 'category',
			'hide_empty' => true,
			'number'     => 12,
			'orderby'    => 'count',
			'order'      => 'DESC',
		] );

		if ( empty( $cats ) ) {
			return '';
		}

		$current_cat = get_queried_object();
		$current_id  = is_object( $current_cat ) && isset( $current_cat->term_id ) ? $current_cat->term_id : 0;

		// "All" link: go to the main blog page (posts page or home).
		$all_url = ( get_option( 'page_for_posts' ) )
			? get_permalink( (int) get_option( 'page_for_posts' ) )
			: home_url();

		$html = '<div class="hkdev-blog-categories" role="group" aria-label="' . esc_attr__( 'Filter by category', 'hkdev-shop-elements' ) . '">';

		$all_class = empty( $current_id ) ? ' hkdev-blog-cat-active' : '';
		$html     .= '<a href="' . esc_url( $all_url ) . '" class="hkdev-blog-cat' . esc_attr( $all_class ) . '" data-cat="all">' . esc_html__( 'All', 'hkdev-shop-elements' ) . '</a>';

		foreach ( $cats as $cat ) {
			$class = ( $cat->term_id === $current_id ) ? ' hkdev-blog-cat-active' : '';
			$html  .= '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="hkdev-blog-cat' . esc_attr( $class ) . '" data-cat="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . '</a>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Reading time estimate in minutes.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function reading_time( $post_id ) {
		$content      = get_post_field( 'post_content', $post_id );
		$word_count   = str_word_count( strip_tags( $content ) );
		$reading_time = (int) ceil( $word_count / 200 );

		return max( 1, $reading_time );
	}

	/**
	 * Render pagination for the archive.
	 *
	 * @param \WP_Query $query The main query.
	 * @return string HTML.
	 */
	private function render_pagination( $query ) {
		$big = 999999999;

		return paginate_links( [
			'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, get_query_var( 'paged', 1 ) ),
			'prev_text' => __( '&larr; Previous', 'hkdev-shop-elements' ),
			'next_text' => __( 'Next &rarr;', 'hkdev-shop-elements' ),
			'type'      => 'list',
			'end_size'  => 1,
			'mid_size'  => 2,
		] );
	}

	/* ---------------------------------------------------------------------
	 * Single post
	 * ------------------------------------------------------------------- */

	/**
	 * Render a single blog post page (used by templates/blog-single.php).
	 *
	 * @return string HTML.
	 */
	public function render_single() {
		if ( ! have_posts() ) {
			return '';
		}

		$this->enqueue_assets();

		ob_start();

		while ( have_posts() ) :
			the_post();

			$post_id   = get_the_ID();
			$author_id = (int) get_post_field( 'post_author', $post_id );
			$author    = get_the_author_meta( 'display_name', $author_id );
			$bio       = get_the_author_meta( 'description', $author_id );
			$time      = self::reading_time( $post_id );
			$cats      = get_the_category( $post_id );
			$cat       = ! empty( $cats ) ? $cats[0] : null;
			$comments  = (int) get_comments_number( $post_id );
			$tags      = get_the_tag_list( '', '', '', $post_id );
			?>
			<div class="hkdev-blog-single-wrap">
				<?php echo $this->render_breadcrumb( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<article id="post-<?php echo esc_attr( $post_id ); ?>" <?php post_class( [ 'hkdev-blog-single' ] ); ?>>
					<header class="hkdev-blog-single-header">
						<?php if ( $cat ) : ?>
							<a class="hkdev-blog-single-cat" href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
						<?php endif; ?>

						<h1 class="entry-title"><?php the_title(); ?></h1>

						<div class="entry-meta">
							<?php if ( $author ) : ?>
								<span class="hkdev-blog-author">
									<?php echo get_avatar( $author_id, 48, '', $author, [ 'class' => 'hkdev-blog-author-img' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo esc_html( $author ); ?>
								</span>
							<?php endif; ?>
							<span class="hkdev-blog-date">
								<i class="fa-solid fa-calendar"></i> <?php echo esc_html( get_the_date( get_option( 'date_format' ), $post_id ) ); ?>
							</span>
							<span class="hkdev-blog-time">
								<i class="fa-solid fa-clock"></i>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: reading time in minutes. */
										_x( '%d min read', 'reading time', 'hkdev-shop-elements' ),
										$time
									)
								);
								?>
							</span>
							<?php if ( comments_open( $post_id ) || $comments > 0 ) : ?>
								<span class="hkdev-blog-comments">
									<i class="fa-solid fa-comment"></i>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %d: number of comments. */
											_n( '%d Comment', '%d Comments', $comments, 'hkdev-shop-elements' ),
											$comments
										)
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					</header>

					<?php if ( has_post_thumbnail( $post_id ) ) : ?>
						<figure class="hkdev-blog-single-featured">
							<?php echo get_the_post_thumbnail( $post_id, 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</figure>
					<?php endif; ?>

					<div class="entry-content">
						<?php the_content(); ?>
						<?php
						wp_link_pages(
							[
								'before' => '<div class="hkdev-blog-page-links">' . esc_html__( 'Pages:', 'hkdev-shop-elements' ),
								'after'  => '</div>',
							]
						);
						?>
					</div>

					<?php if ( $tags ) : ?>
						<div class="hkdev-blog-tags">
							<i class="fa-solid fa-tags"></i> <?php echo wp_kses_post( $tags ); ?>
						</div>
					<?php endif; ?>

					<?php echo $this->render_author_box( $author_id, $author, $bio ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</article>

				<?php echo $this->render_post_nav(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo $this->render_related( $post_id, 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( comments_open( $post_id ) || $comments > 0 ) : ?>
					<div class="hkdev-blog-comments-wrap">
						<?php comments_template(); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		endwhile;

		return ob_get_clean();
	}

	/**
	 * Breadcrumb trail for a single post.
	 *
	 * @param int $post_id Post ID.
	 * @return string HTML.
	 */
	private function render_breadcrumb( $post_id ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		$blog_url   = $posts_page ? get_permalink( $posts_page ) : home_url( '/' );
		$blog_label = ( $posts_page && get_the_title( $posts_page ) )
			? get_the_title( $posts_page )
			: __( 'Blog', 'hkdev-shop-elements' );

		$cats = get_the_category( $post_id );
		$cat  = ! empty( $cats ) ? $cats[0] : null;

		ob_start();
		?>
		<nav class="hkdev-blog-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'hkdev-shop-elements' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="fa-solid fa-house"></i> <?php esc_html_e( 'Home', 'hkdev-shop-elements' ); ?></a>
			<span class="hkdev-blog-breadcrumb-sep"><i class="fa-solid fa-angle-right"></i></span>
			<a href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( $blog_label ); ?></a>
			<?php if ( $cat ) : ?>
				<span class="hkdev-blog-breadcrumb-sep"><i class="fa-solid fa-angle-right"></i></span>
				<a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Author bio box shown after the post content.
	 *
	 * @param int    $author_id Author user ID.
	 * @param string $author    Display name.
	 * @param string $bio       Author biography.
	 * @return string HTML.
	 */
	private function render_author_box( $author_id, $author, $bio ) {
		if ( ! $author ) {
			return '';
		}

		ob_start();
		?>
		<div class="hkdev-blog-author-box">
			<div class="hkdev-blog-author-avatar">
				<?php echo get_avatar( $author_id, 192, '', $author ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="hkdev-blog-author-body">
				<span class="hkdev-blog-author-label"><?php esc_html_e( 'Written by', 'hkdev-shop-elements' ); ?></span>
				<h2 class="hkdev-blog-author-name"><?php echo esc_html( $author ); ?></h2>
				<?php if ( $bio ) : ?>
					<p class="hkdev-blog-author-bio"><?php echo esc_html( wp_strip_all_tags( $bio ) ); ?></p>
				<?php endif; ?>
				<a class="hkdev-blog-author-link" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
					<?php esc_html_e( 'View all posts', 'hkdev-shop-elements' ); ?> <i class="fa-solid fa-arrow-right"></i>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Previous / next post navigation.
	 *
	 * @return string HTML.
	 */
	private function render_post_nav() {
		$prev = get_previous_post();
		$next = get_next_post();

		if ( ! $prev && ! $next ) {
			return '';
		}

		ob_start();
		?>
		<nav class="hkdev-blog-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'hkdev-shop-elements' ); ?>">
			<?php if ( $prev ) : ?>
				<a class="nav-prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
					<span class="nav-label"><i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Previous Post', 'hkdev-shop-elements' ); ?></span>
					<span class="nav-title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $next ) : ?>
				<a class="nav-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
					<span class="nav-label"><?php esc_html_e( 'Next Post', 'hkdev-shop-elements' ); ?> <i class="fa-solid fa-arrow-right"></i></span>
					<span class="nav-title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
				</a>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Related posts grid (same categories, newest first).
	 *
	 * @param int $post_id Current post ID.
	 * @param int $count   Number of posts.
	 * @return string HTML.
	 */
	private function render_related( $post_id, $count = 3 ) {
		$cat_ids = wp_get_post_categories( $post_id );

		$args = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, absint( $count ) ),
			'post__not_in'        => [ $post_id ],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];

		if ( ! empty( $cat_ids ) ) {
			$args['category__in'] = $cat_ids;
		}

		$related = new \WP_Query( $args );

		if ( ! $related->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		ob_start();
		?>
		<section class="hkdev-blog hkdev-blog--grid hkdev-blog--related" data-columns="3">
			<h2 class="hkdev-blog-related-title"><?php esc_html_e( 'Related Posts', 'hkdev-shop-elements' ); ?></h2>
			<div class="hkdev-blog-items">
				<?php while ( $related->have_posts() ) : ?>
					<?php $related->the_post(); ?>
					<?php echo $this->render_card( get_the_ID(), true, false, true, true, __( 'Read More', 'hkdev-shop-elements' ), '16:9' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endwhile; ?>
			</div>
		</section>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

}
