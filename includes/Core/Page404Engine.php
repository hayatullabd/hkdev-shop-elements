<?php
/**
 * Canonical core include entry for Page404_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/404-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\Page404Engine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Page404_Engine', __NAMESPACE__ . '\\Page404Engine' );
}
