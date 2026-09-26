<?php
/**
 * Canonical core include entry for Single_Product_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/single-product-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\SingleProductEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Single_Product_Engine', __NAMESPACE__ . '\\SingleProductEngine' );
}
