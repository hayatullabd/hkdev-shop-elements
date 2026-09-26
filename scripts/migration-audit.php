<?php
/**
 * Migration audit for canonical architecture.
 *
 * Usage:
 *   php scripts/migration-audit.php
 *   php scripts/migration-audit.php --strict
 *
 * Exits with non-zero code when blocked legacy runtime patterns are found.
 */

declare(strict_types=1);

$root       = dirname(__DIR__);
$configPath = __DIR__ . '/migration-audit.config.json';
$strictMode = in_array('--strict', $argv ?? [], true);

$defaults = [
	'legacy_tokens' => [
		'Shop_Engine',
		'Single_Product_Engine',
		'Header_Engine',
		'Footer_Engine',
		'Cart_Engine',
		'Checkout_Engine',
		'Contact_Form_Engine',
		'Catalog_Engine',
		'Review_Engine',
		'Video_Engine',
		'Auth_Engine',
		'Account_Engine',
		'Tracking_Engine',
		'Page404_Engine',
		'Blog_Engine',
		'Admin_Menu',
		'Checkout_Options',
		'Header_Options',
		'Footer_Options',
		'Contact_Form_Options',
		'Review_Options',
		'Widget_Options',
		'Widget_Manager',
		'Plugin_Row',
	],
	'skip_paths' => [
		'/includes/Core/',
		'/includes/Admin/',
		'/includes/admin/',
		'/docs/',
	],
	'file_extensions' => [ 'php' ],
	'allow_line_patterns' => [],
	'strict_block_patterns' => [],
];

$config = $defaults;
if (file_exists($configPath)) {
	$decoded = json_decode((string) file_get_contents($configPath), true);
	if (is_array($decoded)) {
		$config = array_merge($defaults, $decoded);
	}
}

$legacyTokens       = (array) ($config['legacy_tokens'] ?? []);
$skipPaths          = (array) ($config['skip_paths'] ?? []);
$fileExtensions     = array_map('strtolower', (array) ($config['file_extensions'] ?? [ 'php' ]));
$allowLinePatterns  = (array) ($config['allow_line_patterns'] ?? []);
$strictBlockPattern = (array) ($config['strict_block_patterns'] ?? []);

$errors = [];

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
	/** @var SplFileInfo $file */
	if (! in_array(strtolower($file->getExtension()), $fileExtensions, true)) {
		continue;
	}

	$path = str_replace('\\', '/', $file->getPathname());
	$rel  = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');

	$skip = false;
	foreach ($skipPaths as $skipPath) {
		if (false !== strpos($path, (string) $skipPath)) {
			$skip = true;
			break;
		}
	}
	if ($skip) {
		continue;
	}

	$content = (string) file_get_contents($path);
	if ('' === $content) {
		continue;
	}

	$lines = preg_split('/\R/', $content) ?: [];
	foreach ($lines as $lineNumber => $line) {
		if (preg_match('/^\s*(class|final class|abstract class)\s+/', $line)) {
			continue;
		}
		if (preg_match('/^\s*(\*|\/\/|\/\*)/', $line)) {
			continue;
		}

		$allowed = false;
		foreach ($allowLinePatterns as $pattern) {
			if (@preg_match((string) $pattern, $line)) {
				$allowed = true;
				break;
			}
		}
		if ($allowed) {
			continue;
		}

		foreach ($legacyTokens as $token) {
			$token = (string) $token;
			if (
				false !== strpos($line, $token . '::instance(') ||
				false !== strpos($line, '\\HkdevShopElements\\Includes\\' . $token . '::')
			) {
				$errors[] = sprintf(
					'%s:%d legacy runtime reference detected: %s',
					$rel,
					$lineNumber + 1,
					$token
				);
			}
		}

		if ($strictMode) {
			foreach ($strictBlockPattern as $pattern) {
				if (@preg_match((string) $pattern, $line)) {
					$errors[] = sprintf(
						'%s:%d strict rule match: %s',
						$rel,
						$lineNumber + 1,
						(string) $pattern
					);
				}
			}
		}
	}
}

if (! empty($errors)) {
	echo "Migration audit failed:\n";
	foreach ($errors as $error) {
		echo ' - ' . $error . "\n";
	}
	exit(1);
}

echo sprintf(
	"Migration audit passed (%s mode): no blocked legacy runtime references found.\n",
	$strictMode ? 'strict' : 'normal'
);
exit(0);
