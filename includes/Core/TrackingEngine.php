<?php
/**
 * Canonical core include entry for Tracking_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/tracking-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\TrackingEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Tracking_Engine', __NAMESPACE__ . '\\TrackingEngine' );
}
