<?php
/**
 * Canonical core include entry for Shop_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/shop-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\ShopEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Shop_Engine', __NAMESPACE__ . '\\ShopEngine' );
}
