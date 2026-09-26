<?php
/**
 * Canonical core include entry for Footer_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/footer-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\FooterEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Footer_Engine', __NAMESPACE__ . '\\FooterEngine' );
}
