<?php
/**
 * Canonical admin include entry for Checkout_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/checkout-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\CheckoutOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Checkout_Options', __NAMESPACE__ . '\\CheckoutOptions' );
}
