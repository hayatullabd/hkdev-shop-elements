<?php
/**
 * Canonical admin include entry for Widget_Manager.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/widget-manager.php';

if ( ! class_exists( __NAMESPACE__ . '\\WidgetManager' ) ) {
	class_alias( '\HkdevShopElements\Includes\Widget_Manager', __NAMESPACE__ . '\\WidgetManager' );
}
