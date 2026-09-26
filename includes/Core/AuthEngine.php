<?php
/**
 * Canonical core include entry for Auth_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/auth-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\AuthEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Auth_Engine', __NAMESPACE__ . '\\AuthEngine' );
}
