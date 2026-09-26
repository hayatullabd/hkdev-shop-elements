<?php
/**
 * Privacy Policy page-ready widget.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PrivacyPolicyWidget extends PolicyLinkBaseWidget {

	public function get_name() {
		return 'hkdev_privacy_policy_link';
	}

	public function get_title() {
		return esc_html__( 'HKDEV Privacy Policy', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-editor-link';
	}

	public function get_keywords() {
		return [ 'privacy', 'policy', 'legal', 'page' ];
	}

	protected function default_page_title() {
		return esc_html__( 'Privacy Policy', 'hkdev-shop-elements' );
	}

	protected function default_page_content() {
		return wp_kses_post(
			'<p>We collect necessary customer information (name, phone, email, address) to process orders, deliver products, and provide customer support.</p><p>Your payment details are handled by secure payment partners. We do not store sensitive card credentials on this website.</p><p>We do not sell personal information to third parties. Data may be shared only with delivery partners and service providers required to complete your order.</p><p>By using this website, you agree to this privacy policy. For questions, contact our support team.</p>'
		);
	}
}

