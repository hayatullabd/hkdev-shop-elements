<?php
/**
 * Canonical admin include entry for Header_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/header-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\HeaderOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Header_Options', __NAMESPACE__ . '\\HeaderOptions' );
}
