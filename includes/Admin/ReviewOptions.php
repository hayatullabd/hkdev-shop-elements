<?php
/**
 * Canonical admin include entry for Review_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/review-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\ReviewOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Review_Options', __NAMESPACE__ . '\\ReviewOptions' );
}
