<?php
/**
 * Backward compatibility shim for moved Plugin_Row class.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\Admin\\PluginRow' ) ) {
	require_once HKDEV_ELEMENTS_PATH . 'includes/Admin/PluginRow.php';
}

/**
 * @deprecated 0.5.137 Use \HkdevShopElements\Includes\Admin\PluginRow.
 */
final class Plugin_Row {

	/**
	 * Proxy for old namespace usage.
	 *
	 * @return \HkdevShopElements\Includes\Admin\PluginRow
	 */
	public static function instance() {
		return \HkdevShopElements\Includes\Admin\PluginRow::instance();
	}
}
