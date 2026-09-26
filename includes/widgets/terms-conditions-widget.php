<?php
/**
 * Terms & Conditions page-ready widget.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TermsConditionsWidget extends PolicyLinkBaseWidget {

	public function get_name() {
		return 'hkdev_terms_conditions_link';
	}

	public function get_title() {
		return esc_html__( 'HKDEV Terms & Conditions', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-document-file';
	}

	public function get_keywords() {
		return [ 'terms', 'conditions', 'legal', 'page' ];
	}

	protected function default_page_title() {
		return esc_html__( 'Terms & Conditions', 'hkdev-shop-elements' );
	}

	protected function default_page_content() {
		return wp_kses_post(
			'<p>By placing an order, you confirm that all provided information is accurate and complete.</p><p>Product prices and availability may change without prior notice. We reserve the right to cancel or adjust orders in case of stock or pricing issues.</p><p>Orders are confirmed after successful verification and payment/cash-on-delivery acceptance.</p><p>Use of this website indicates acceptance of our terms, delivery rules, and return policy.</p>'
		);
	}
}

