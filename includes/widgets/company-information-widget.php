<?php
/**
 * Company Information page-ready widget.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CompanyInformationWidget extends PolicyLinkBaseWidget {

	public function get_name() {
		return 'hkdev_company_information_link';
	}

	public function get_title() {
		return esc_html__( 'HKDEV Company Information', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-info-circle';
	}

	public function get_keywords() {
		return [ 'company', 'information', 'business', 'page' ];
	}

	protected function default_page_title() {
		return esc_html__( 'Company Information', 'hkdev-shop-elements' );
	}

	protected function default_page_content() {
		return wp_kses_post(
			'<p>HKDEV is a customer-focused ecommerce business committed to quality products, transparent service, and reliable delivery.</p><p>Our operations include product sourcing, quality control, order fulfillment, and after-sales support.</p><p>For business inquiries, partnerships, or corporate collaboration, please contact us through our official support channels.</p><p>Company details such as trade information, support contacts, and service coverage are updated periodically.</p>'
		);
	}
}

