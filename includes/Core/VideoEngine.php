<?php
/**
 * Canonical core include entry for Video_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/video-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\VideoEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Video_Engine', __NAMESPACE__ . '\\VideoEngine' );
}
