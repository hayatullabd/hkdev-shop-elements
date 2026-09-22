<?php
/**
 * GitHub Update Checker (HKDEV Shop Elements).
 *
 * Turns the GitHub repository into a WordPress plugin update source, so the
 * "update available" notice and the one-click "Update Now" button in
 * WP Admin → Plugins work without wordpress.org.
 *
 * How it works:
 *  - The repository's newest GitHub *release* (falling back to the newest tag)
 *    is treated as the latest version. Its tag name (v1.2.3 → 1.2.3) is
 *    compared against the plugin header version.
 *  - The release's uploaded .zip asset is preferred as the download package;
 *    otherwise GitHub's auto-generated zipball is used.
 *  - upgrader_source_selection() renames the extracted archive folder to the
 *    plugin slug, because a GitHub archive unpacks to "<owner>-<repo>-<sha>/".
 *  - GitHub API responses are cached in a transient (6 hours) to stay well
 *    inside the unauthenticated rate limit. The "Check again" link on the
 *    Dashboard → Updates screen bypasses the cache.
 *
 * Releasing an update: bump the Version header + HKDEV_ELEMENTS_VERSION, commit,
 * then push a tag such as v0.2.1 (and optionally publish a GitHub Release).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GitHub_Updater
 */
class GitHub_Updater {

	/**
	 * How long a successful GitHub API response is cached.
	 *
	 * @var int
	 */
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * How long an empty / failed response is cached (so a temporary outage does
	 * not hide a real update for six hours).
	 *
	 * @var int
	 */
	const FAIL_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * GitHub REST API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.github.com';

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin basename, e.g. hkdev-shop-elements/hkdev-shop-elements.php
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin slug (the plugin directory name).
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * GitHub repository in "owner/repo" form.
	 *
	 * @var string
	 */
	private $repository;

	/**
	 * Currently installed plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Transient key holding the cached release data.
	 *
	 * @var string
	 */
	private $cache_key;
	/**
	 * Transient key storing pre-update activation state.
	 *
	 * @var string
	 */
	private $activation_state_key;

	/**
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @param string $repository  GitHub "owner/repo".
	 */
	public function __construct( $plugin_file, $repository ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->slug            = dirname( $this->plugin_basename );
		$this->repository      = trim( (string) $repository, "/ \t\n\r\0\x0B" );
		$this->version         = defined( 'HKDEV_ELEMENTS_VERSION' ) ? (string) HKDEV_ELEMENTS_VERSION : '0';
		$this->cache_key       = 'hkdev_elements_github_release';
		$this->activation_state_key = 'hkdev_elements_update_state_' . md5( $this->plugin_basename );

		// Not configured yet – stay completely silent.
		if ( '' === $this->repository || false === strpos( $this->repository, '/' ) ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'inject_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 20, 3 );
		add_filter( 'upgrader_source_selection', [ $this, 'fix_source_dir' ], 10, 4 );
		add_action( 'upgrader_process_complete', [ $this, 'maybe_reactivate_after_update' ], 20, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 4 );

		// Priority 1: must run before core's _maybe_update_plugins() (priority
		// 10), otherwise the forced re-check reads our still-cached release and
		// the "Check again" link appears to do nothing.
		add_action( 'admin_init', [ $this, 'maybe_clear_cache' ], 1 );
	}

	/* ---------------------------------------------------------------------
	 * WordPress hooks
	 * ------------------------------------------------------------------- */

	/**
	 * Add our plugin to the update transient when a newer release exists.
	 *
	 * @param object $transient The update_plugins transient.
	 * @return object
	 */
	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_release( $this->may_fetch() );
		if ( empty( $release['version'] ) || empty( $release['package'] ) ) {
			return $transient;
		}

		$item = (object) [
			'id'           => 'github.com/' . $this->repository,
			'slug'         => $this->slug,
			'plugin'       => $this->plugin_basename,
			'new_version'  => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => '6.0',
			'tested'       => '',
			'requires_php' => '7.4',
		];

		if ( version_compare( $release['version'], $this->version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = $item;
			unset( $transient->no_update[ $this->plugin_basename ] );
		} else {
			$transient->no_update[ $this->plugin_basename ] = $item;
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Provide the data for the "View version details" popup.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action API action.
	 * @param object             $args   API arguments.
	 * @return false|object
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( empty( $release['version'] ) ) {
			return $result;
		}

		$changelog = ! empty( $release['body'] )
			? wp_kses_post( wpautop( $release['body'] ) )
			: esc_html__( 'See the GitHub repository for the full changelog.', 'hkdev-shop-elements' );

		return (object) [
			'name'          => 'HKDEV Shop Elements',
			'slug'          => $this->slug,
			'plugin'        => $this->plugin_basename,
			'version'       => $release['version'],
			'author'        => 'Md Hayatulla Kha',
			'homepage'      => 'https://github.com/' . $this->repository,
			'requires'      => '6.0',
			'tested'        => '',
			'requires_php'  => '7.4',
			'download_link' => $release['package'],
			'last_updated'  => $release['published_at'],
			'sections'      => [
				'description' => esc_html__( 'Standalone Elementor + WooCommerce widgets (Shop Grid / Carousel, Cart, Checkout, Single Product, Header, Footer, Contact Form) that work with any WordPress theme.', 'hkdev-shop-elements' ),
				'changelog'   => $changelog,
			],
		];
	}

	/**
	 * Add a "View details" link to the plugin row on the Plugins screen.
	 *
	 * WordPress only renders that modal link for wordpress.org-hosted plugins.
	 * For this external plugin we append our own link so the GitHub release
	 * notes (served by plugin_information above) open in the standard details
	 * popup.
	 *
	 * @param string[] $plugin_meta Existing row meta links.
	 * @param string   $plugin_file Plugin basename.
	 * @param array    $plugin_data Plugin header data.
	 * @param string   $status      Current list status.
	 * @return string[]
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( $plugin_file !== $this->plugin_basename ) {
			return $plugin_meta;
		}

		$plugin_name = isset( $plugin_data['Name'] ) && '' !== $plugin_data['Name'] ? $plugin_data['Name'] : 'HKDEV Shop Elements';

		$details_url = self_admin_url(
			'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $this->slug ) . '&section=changelog&TB_iframe=true&width=600&height=800'
		);

		$plugin_meta[] = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
			esc_url( $details_url ),
			/* translators: %s: Plugin name. */
			esc_attr( sprintf( __( 'More information about %s', 'hkdev-shop-elements' ), $plugin_name ) ),
			esc_attr( $plugin_name ),
			esc_html__( 'View details', 'hkdev-shop-elements' )
		);

		return $plugin_meta;
	}

	/**
	 * Rename the extracted GitHub archive folder to the plugin slug.
	 *
	 * A GitHub archive unpacks to "<owner>-<repo>-<sha>/", so without this the
	 * update would land in the wrong directory.
	 *
	 * @param string $source      Extracted source path.
	 * @param string $remote_source Remote working directory.
	 * @param object $upgrader    Upgrader instance.
	 * @param array  $hook_extra  Extra hook arguments.
	 * @return string
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $args = [] ) {
		// WordPress passes the full install-package arguments array here, not
		// the hook_extra sub-array. Read the plugin key from the right place.
		$hook_extra = ( isset( $args['hook_extra'] ) && is_array( $args['hook_extra'] ) ) ? $args['hook_extra'] : [];

		// Only act on an update of this exact plugin.
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}
		$this->remember_activation_state();

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$source = trailingslashit( $source );

		// Archives that wrap everything in a single folder: step into it so the
		// path we rename is the one that actually holds the plugin.
		if ( ! $this->looks_like_plugin( $source ) ) {
			$children = $wp_filesystem->dirlist( $source );
			if ( is_array( $children ) && 1 === count( $children ) ) {
				$child = key( $children );
				if ( $wp_filesystem->is_dir( $source . $child ) && $this->looks_like_plugin( trailingslashit( $source . $child ) ) ) {
					$source = trailingslashit( $source . $child );
				}
			}
		}

		if ( ! $this->looks_like_plugin( $source ) ) {
			if ( function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
				\HkdevShopElements\hkdev_elements_log_message(
					'GitHub updater: extracted package does not look like plugin root; aborting update to avoid deactivation.'
				);
			}

			return new \WP_Error(
				'hkdev_invalid_update_package',
				esc_html__( 'Update package is invalid: plugin root files were not found.', 'hkdev-shop-elements' )
			);
		}

		if ( basename( untrailingslashit( $source ) ) === $this->slug ) {
			return $source;
		}

		$remote = untrailingslashit( $remote_source );
		$parent = ( untrailingslashit( $source ) === $remote ) ? dirname( $remote ) : $remote;
		$target = trailingslashit( $parent ) . $this->slug;

		if ( $wp_filesystem->move( $source, $target, true ) ) {
			if ( ! $this->looks_like_plugin( trailingslashit( $target ) ) ) {
				if ( function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
					\HkdevShopElements\hkdev_elements_log_message(
						'GitHub updater: moved package missing plugin root files; aborting update to prevent broken install.'
					);
				}

				return new \WP_Error(
					'hkdev_invalid_update_target',
					esc_html__( 'Update failed: plugin files were not found after installation.', 'hkdev-shop-elements' )
				);
			}
			return trailingslashit( $target );
		}

		return $source;
	}

	/**
	 * Reactivate the plugin after a successful self-update when it was active
	 * before the update and the main plugin file still exists.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $hook_extra Upgrader hook args.
	 * @return void
	 */
	public function maybe_reactivate_after_update( $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['action'] ) || 'update' !== $hook_extra['action'] ) {
			return;
		}
		if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) {
			return;
		}
		if ( empty( $hook_extra['plugins'] ) || ! is_array( $hook_extra['plugins'] ) ) {
			return;
		}
		if ( ! in_array( $this->plugin_basename, $hook_extra['plugins'], true ) ) {
			return;
		}

		$state = get_transient( $this->activation_state_key );
		delete_transient( $this->activation_state_key );

		if ( ! is_array( $state ) || empty( $state['active'] ) ) {
			return;
		}

		$plugin_full_path = WP_PLUGIN_DIR . '/' . $this->plugin_basename;
		if ( ! file_exists( $plugin_full_path ) ) {
			if ( function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
				\HkdevShopElements\hkdev_elements_log_message(
					'GitHub updater: plugin file missing after update, auto-reactivation skipped.'
				);
			}
			return;
		}

		$this->ensure_plugin_functions_loaded();
		if ( ! function_exists( 'activate_plugin' ) ) {
			return;
		}

		$network_wide = ! empty( $state['network'] ) && is_multisite();
		$result       = activate_plugin( $this->plugin_basename, '', $network_wide, false );

		if ( is_wp_error( $result ) && function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
			\HkdevShopElements\hkdev_elements_log_message(
				sprintf(
					'GitHub updater: auto-reactivation failed after update (%s)',
					$result->get_error_message()
				)
			);
		}
	}

	/**
	 * Forget the cached release data when the "Check again" link is used
	 * (Dashboard → Updates, or Plugins → Check again).
	 *
	 * @return void
	 */
	public function maybe_clear_cache() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core link, no state change.
		if ( isset( $_GET['force-check'] ) ) {
			delete_transient( $this->cache_key );
		}
	}

	/* ---------------------------------------------------------------------
	 * GitHub API
	 * ------------------------------------------------------------------- */

	/**
	 * Whether a network request to GitHub is acceptable for this request.
	 *
	 * The updater itself only loads for admin screens and WP-Cron, and the
	 * GitHub response below is cached for six hours, so a cold cache is the
	 * only case that ever reaches the network. WordPress rebuilds the update
	 * transient at most every 12 hours, which bounds this to roughly two
	 * requests a day per site.
	 *
	 * This used to be limited to the update / plugins screens, which looked
	 * safer but hid real updates: WordPress checks for updates from *every*
	 * admin screen, so a request on any other screen (Dashboard, a post, the
	 * Elementor editor) wrote "no update" into the transient together with a
	 * fresh timestamp. For the next 12 hours `_maybe_update_plugins()` then
	 * considered the check recent and never asked again — the Plugins screen
	 * showed nothing even though a newer release existed.
	 *
	 * @return bool
	 */
	private function may_fetch() {
		return true;
	}

	/**
	 * Latest release (or tag) as a normalized array. Cached in a transient.
	 *
	 * @param bool $allow_fetch Whether a network request may be made on a cache miss.
	 * @return array
	 */
	private function get_release( $allow_fetch = true ) {
		$cached = get_transient( $this->cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		if ( ! $allow_fetch ) {
			return [];
		}

		$release = $this->request( '/repos/' . $this->repository . '/releases/latest' );
		if ( is_array( $release ) && ! empty( $release['tag_name'] ) ) {
			$data = $this->normalize_release( $release );
		} else {
			$data = $this->latest_tag();
		}

		set_transient( $this->cache_key, $data, empty( $data ) ? self::FAIL_TTL : self::CACHE_TTL );

		return $data;
	}

	/**
	 * Normalize a GitHub release payload.
	 *
	 * @param array $release Release payload.
	 * @return array
	 */
	private function normalize_release( $release ) {
		$tag     = (string) $release['tag_name'];
		$package = '';

		// Prefer an uploaded .zip asset (it already has the correct folder).
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
				if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $name ) ) {
					$package = (string) $asset['browser_download_url'];
					break;
				}
			}
		}

		if ( '' === $package && ! empty( $release['zipball_url'] ) ) {
			$package = (string) $release['zipball_url'];
		}

		return [
			'version'      => $this->clean_version( $tag ),
			'tag'          => $tag,
			'package'      => $package,
			'url'          => isset( $release['html_url'] ) ? (string) $release['html_url'] : 'https://github.com/' . $this->repository,
			'body'         => isset( $release['body'] ) ? (string) $release['body'] : '',
			'published_at' => isset( $release['published_at'] ) ? (string) $release['published_at'] : '',
		];
	}

	/**
	 * Newest tag when the repository has no releases yet.
	 *
	 * @return array
	 */
	private function latest_tag() {
		$tags = $this->request( '/repos/' . $this->repository . '/tags?per_page=100' );
		if ( ! is_array( $tags ) || empty( $tags ) ) {
			return [];
		}

		$newest = '';
		foreach ( $tags as $tag ) {
			$name = isset( $tag['name'] ) ? (string) $tag['name'] : '';
			if ( '' === $name ) {
				continue;
			}
			if ( '' === $newest || version_compare( $this->clean_version( $name ), $this->clean_version( $newest ), '>' ) ) {
				$newest = $name;
			}
		}

		if ( '' === $newest ) {
			return [];
		}

		return [
			'version'      => $this->clean_version( $newest ),
			'tag'          => $newest,
			'package'      => self::API_BASE . '/repos/' . $this->repository . '/zipball/' . rawurlencode( $newest ),
			'url'          => 'https://github.com/' . $this->repository . '/releases/tag/' . rawurlencode( $newest ),
			'body'         => '',
			'published_at' => '',
		];
	}

	/**
	 * Strip a leading "v" from a tag name to get a comparable version.
	 *
	 * @param string $tag Tag name.
	 * @return string
	 */
	private function clean_version( $tag ) {
		return (string) preg_replace( '/^v/i', '', trim( (string) $tag ) );
	}

	/**
	 * Authenticated GitHub request (token optional).
	 *
	 * @param string $path API path (starting with a slash).
	 * @return array|null
	 */
	private function request( $path ) {
		$args = [
			'timeout' => 5,
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'hkdev-shop-elements/' . $this->version,
			],
		];

		$token = $this->token();
		if ( '' !== $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get( self::API_BASE . $path, $args );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// A silent failure here is invisible from the admin, so record why
			// the check produced nothing (request failures are cached for only
			// 30 minutes, which also bounds how often this can be written).
			if ( function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
				\HkdevShopElements\hkdev_elements_log_message(
					sprintf(
						'GitHub updater: request to %s failed (%s)',
						$path,
						is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . (int) wp_remote_retrieve_response_code( $response )
					)
				);
			}

			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : null;
	}

	/**
	 * Optional GitHub token (needed for private repos / higher rate limits).
	 *
	 * @return string
	 */
	private function token() {
		$token = defined( 'HKDEV_ELEMENTS_GITHUB_TOKEN' ) ? (string) HKDEV_ELEMENTS_GITHUB_TOKEN : '';

		return (string) apply_filters( 'hkdev_elements_github_token', $token );
	}

	/**
	 * Whether a directory looks like this plugin's root.
	 *
	 * @param string $dir Directory path.
	 * @return bool
	 */
	private function looks_like_plugin( $dir ) {
		global $wp_filesystem;

		$dir = trailingslashit( $dir );

		if ( $wp_filesystem ) {
			return $wp_filesystem->exists( $dir . 'hkdev-shop-elements.php' )
				|| $wp_filesystem->exists( $dir . 'includes/shop-engine.php' );
		}

		return false;
	}

	/**
	 * Persist whether this plugin was active before update starts.
	 *
	 * @return void
	 */
	private function remember_activation_state() {
		$this->ensure_plugin_functions_loaded();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			return;
		}

		$active = is_plugin_active( $this->plugin_basename );
		$network_active = is_multisite() && is_plugin_active_for_network( $this->plugin_basename );

		set_transient(
			$this->activation_state_key,
			[
				'active'  => $active || $network_active,
				'network' => $network_active,
				'time'    => time(),
			],
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Load core plugin helper functions when not already loaded.
	 *
	 * @return void
	 */
	private function ensure_plugin_functions_loaded() {
		if ( function_exists( 'activate_plugin' ) && function_exists( 'is_plugin_active' ) ) {
			return;
		}
		if ( defined( 'ABSPATH' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
