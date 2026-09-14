<?php
/**
 * Fatal error logger (HKDEV Shop Elements).
 *
 * Records PHP fatal errors into
 * wp-content/uploads/hkdev-elements-error.log so a crash that breaks the
 * Elementor editor (or any screen) can be diagnosed without turning on
 * WP_DEBUG on the live site.
 *
 * Only the most recent crash is kept, and the file is capped at ~64 KB.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Path of the fatal-error log file.
 *
 * @return string
 */
function hkdev_elements_error_log_file() {
	if ( function_exists( 'wp_get_upload_dir' ) ) {
		$uploads = wp_get_upload_dir();
		if ( ! empty( $uploads['basedir'] ) ) {
			return trailingslashit( $uploads['basedir'] ) . 'hkdev-elements-error.log';
		}
	}

	return trailingslashit( WP_CONTENT_DIR ) . 'hkdev-elements-error.log';
}

/**
 * Append the last fatal error (if any) to the log file.
 *
 * @return void
 */
function hkdev_elements_log_fatal() {
	$error = error_get_last();

	if ( empty( $error ) || ! isset( $error['type'], $error['message'], $error['file'], $error['line'] ) ) {
		return;
	}

	$fatal_types = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ];

	if ( ! in_array( (int) $error['type'], $fatal_types, true ) ) {
		return;
	}

	$file = hkdev_elements_error_log_file();

	if ( file_exists( $file ) && filesize( $file ) > 64000 ) {
		@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$line = sprintf(
		"[%s] %s in %s:%d\n",
		gmdate( 'Y-m-d H:i:s' ),
		$error['message'],
		$error['file'],
		$error['line']
	);

	@file_put_contents( $file, $line, FILE_APPEND ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}

register_shutdown_function( __NAMESPACE__ . '\\hkdev_elements_log_fatal' );
