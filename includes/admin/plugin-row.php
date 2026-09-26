<?php
/**
 * Backward compatibility shim for old include path.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/Admin/PluginRow.php';
