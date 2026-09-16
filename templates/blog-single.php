<?php
/**
 * Single blog post template.
 *
 * Loaded via Blog_Engine::blog_template() (template_include filter) when a
 * standard post is visited and the post is not built with Elementor or marked
 * with the [hkdev_blog] shortcode.
 *
 * @package HkdevShopElements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

echo \HkdevShopElements\Includes\Blog_Engine::instance()->render_single(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

get_footer();
