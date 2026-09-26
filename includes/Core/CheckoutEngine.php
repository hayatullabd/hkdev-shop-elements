<?php
/**
 * Canonical core include entry for Checkout_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/checkout-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\CheckoutEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Checkout_Engine', __NAMESPACE__ . '\\CheckoutEngine' );
}
