<?php
/**
 * Canonical admin include entry for Widget_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/widget-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\WidgetOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Widget_Options', __NAMESPACE__ . '\\WidgetOptions' );
}
