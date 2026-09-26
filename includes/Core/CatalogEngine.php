<?php
/**
 * Canonical core include entry for Catalog_Engine.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once HKDEV_ELEMENTS_PATH . 'includes/catalog-engine.php';

if ( ! class_exists( __NAMESPACE__ . '\\CatalogEngine' ) ) {
	class_alias( '\HkdevShopElements\Includes\Catalog_Engine', __NAMESPACE__ . '\\CatalogEngine' );
}
