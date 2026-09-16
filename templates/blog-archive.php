<?php
/**
 * Blog archive template.
 *
 * Loaded via Blog_Engine::blog_archive_template() (template_include filter) when
 * the site's standard posts-page (is_home) or a post taxonomy archive is visited
 * and the page is not built with Elementor or marked with the [hkdev_blog]
 * shortcode.
 *
 * @package HkdevShopElements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

echo \HkdevShopElements\Includes\Blog_Engine::instance()->render(
	[
		'layout'         => 'grid',
		'columns'        => 3,
		'posts_per_page' => get_option( 'posts_per_page', 9 ),
	],
	true
);

get_footer();
