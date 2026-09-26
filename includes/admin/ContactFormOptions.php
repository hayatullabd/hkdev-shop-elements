<?php
/**
 * Canonical admin include entry for Contact_Form_Options.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/contact-form-options.php';

if ( ! class_exists( __NAMESPACE__ . '\\ContactFormOptions' ) ) {
	class_alias( '\HkdevShopElements\Includes\Contact_Form_Options', __NAMESPACE__ . '\\ContactFormOptions' );
}
