<?php
/**
 * Canonical admin include entry for Admin_Menu.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/admin-menu.php';

if ( ! class_exists( __NAMESPACE__ . '\\AdminMenu' ) ) {
	class_alias( '\HkdevShopElements\Includes\Admin_Menu', __NAMESPACE__ . '\\AdminMenu' );
}
