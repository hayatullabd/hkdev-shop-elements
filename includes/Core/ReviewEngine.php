<?php
/**
 * Canonical core include entry for Review_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/review-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\ReviewEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Review_Engine', __NAMESPACE__ . '\\ReviewEngine' );
}
