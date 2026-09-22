<?php
/**
 * Careers page-ready widget.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Careers_Widget extends Policy_Link_Base_Widget {

	public function get_name() {
		return 'hkdev_careers_link';
	}

	public function get_title() {
		return esc_html__( 'HKDEV Careers', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	public function get_keywords() {
		return [ 'careers', 'jobs', 'hiring', 'page' ];
	}

	protected function default_page_title() {
		return esc_html__( 'Careers', 'hkdev-shop-elements' );
	}

	protected function default_page_content() {
		return wp_kses_post(
			'<p>We are always looking for passionate, responsible, and growth-minded people to join our team.</p><p>Open roles are published based on business needs across operations, customer support, and marketing.</p><p>Please send your CV and role preference to our official recruitment email. Shortlisted candidates will be contacted for the next steps.</p><p>We are an equal opportunity employer and value teamwork, ownership, and customer-first mindset.</p>'
		);
	}
}

