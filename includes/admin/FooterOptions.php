<?php
/**
 * Canonical admin include entry for Footer_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/footer-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\FooterOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Footer_Options', __NAMESPACE__ . '\\FooterOptions' );
}
