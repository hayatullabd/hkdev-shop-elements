<?php
/**
 * Canonical core include entry for Blog_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/blog-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\BlogEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Blog_Engine', __NAMESPACE__ . '\\BlogEngine' );
}
