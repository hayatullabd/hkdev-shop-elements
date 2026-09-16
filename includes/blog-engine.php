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

		// Auto-style the standard blog / posts-page and post taxonomy archives.
		add_filter( 'template_include', [ $this, 'blog_archive_template' ] );

		// Ensure assets are enqueued when the shortcode or archive is active.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets_check' ] );
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
	 * Whether the current request is a blog / post-archive page that we should
	 * auto-style.
	 *
	 * @return bool
	 */
	private function is_blog_page() {
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
	 * Archive auto-styling
	 * ------------------------------------------------------------------ */

	/**
	 * When the standard blog / posts-page or post-taxonomy archive is visited and
	 * the page hasn't been built with Elementor or marked with the [hkdev_blog]
	 * shortcode, swap in our plugin-provided template so the archive gets the
	 * styled HKDEV blog grid.
	 *
	 * @param string $template Absolute path to the theme template file.
	 * @return string
	 */
	public function blog_archive_template( $template ) {
		if ( is_admin() ) {
			return $template;
		}
		if ( ! is_main_query() ) {
			return $template;
		}
		if ( ! $this->is_blog_page() ) {
			return $template;
		}

		// Respect Elementor-built pages and explicit shortcode usage.
		if ( $this->is_elementor_page() ) {
			return $template;
		}
		if ( has_shortcode( get_post()->post_content, 'hkdev_blog' ) ) {
			return $template;
		}

		$tpl = HKDEV_ELEMENTS_PATH . 'templates/blog-archive.php';
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

		$layout        = isset( $atts['layout'] ) && 'list' === $atts['layout'] ? 'list' : 'grid';
		$columns       = isset( $atts['columns'] ) ? max( 1, min( 6, absint( $atts['columns'] ) ) ) : 3;
		$show_image    = empty( $atts['show_image'] ) || 'yes' === $atts['show_image'];
		$show_excerpt  = empty( $atts['show_excerpt'] ) || 'yes' === $atts['show_excerpt'];
		$show_meta     = empty( $atts['show_meta'] ) || 'yes' === $atts['show_meta'];
		$show_readmore = empty( $atts['show_readmore'] ) || 'yes' === $atts['show_readmore'];
		$readmore_text = ! empty( $atts['readmore_text'] ) ? $atts['readmore_text'] : __( 'Read More', 'hkdev-shop-elements' );
		$image_ratio   = ! empty( $atts['image_ratio'] ) ? sanitize_text_field( $atts['image_ratio'] ) : '16:9';

		ob_start();
		?>
		<div class="hkdev-blog hkdev-blog-<?php echo esc_attr( $layout ); ?>" data-layout="<?php echo esc_attr( $layout ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">

			<?php if ( $is_archive ) : ?>
				<?php echo self::render_category_pills(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<div class="hkdev-blog-list">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php echo $this->render_card( get_the_ID(), $show_image, $show_excerpt, $show_meta, $show_readmore, $readmore_text, $image_ratio ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endwhile; ?>
			</div>

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
	 * Build a WP_Query for the shortcode (non-archive) context.
	 *
	 * @param array $atts Attributes.
	 * @return \WP_Query
	 */
	private function build_query( $atts ) {
		$cat_slugs = [];
		if ( ! empty( $atts['category'] ) ) {
			$cat_slugs = array_values( array_filter( array_map( 'trim', explode( ',', $atts['category'] ) ) ) );
		}

		$args = [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, absint( $atts['posts_per_page'] ?? 9 ) ),
			'paged'               => max( 1, get_query_var( 'paged', 1 ) ),
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

		return new \WP_Query( $args );
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

}
