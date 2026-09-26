<?php
/**
 * Canonical core include entry for Header_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/header-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\HeaderEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Header_Engine', __NAMESPACE__ . '\\HeaderEngine' );
}
