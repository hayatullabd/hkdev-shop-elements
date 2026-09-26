<?php
/**
 * Shared color-theme presets for every HKDEV widget.
 *
 * Built-in slugs stay compatible with existing saved widgets (green / orange /
 * monochrome). Custom presets are stored in a WP option and appear in the
 * same Elementor dropdown.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ColorTheme
 */
final class ColorTheme {

	const OPTION_NAME = 'hkdev_elements_color_presets';
	const DEFAULT     = 'green';

	/**
	 * @var ?ColorTheme
	 */
	private static $instance = null;

	/**
	 * @return ColorTheme
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_theme_css' ], 30 );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_theme_css' ] );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_theme_css' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_theme_css' ] );
	}

	/**
	 * Color fields shown in the custom-preset admin form.
	 *
	 * @return array<string,array{label:string,help:string}>
	 */
	public static function color_fields() {
		return [
			'primary'   => [
				'label' => __( 'Primary', 'hkdev-shop-elements' ),
				'help'  => __( 'Buttons, links, active states.', 'hkdev-shop-elements' ),
			],
			'hover'     => [
				'label' => __( 'Primary Hover', 'hkdev-shop-elements' ),
				'help'  => __( 'Leave empty to darken Primary automatically.', 'hkdev-shop-elements' ),
			],
			'secondary' => [
				'label' => __( 'Secondary', 'hkdev-shop-elements' ),
				'help'  => __( 'Sale badges and secondary accents.', 'hkdev-shop-elements' ),
			],
			'info'      => [
				'label' => __( 'Info / Deep', 'hkdev-shop-elements' ),
				'help'  => __( 'Darker brand shade for pressed states.', 'hkdev-shop-elements' ),
			],
			'accent'    => [
				'label' => __( 'Accent', 'hkdev-shop-elements' ),
				'help'  => __( 'Highlights and extra emphasis.', 'hkdev-shop-elements' ),
			],
			'text'      => [
				'label' => __( 'Text', 'hkdev-shop-elements' ),
				'help'  => __( 'Main body and title color.', 'hkdev-shop-elements' ),
			],
			'muted'     => [
				'label' => __( 'Muted Text', 'hkdev-shop-elements' ),
				'help'  => __( 'Secondary copy and meta.', 'hkdev-shop-elements' ),
			],
			'page_bg'   => [
				'label' => __( 'Page Background', 'hkdev-shop-elements' ),
				'help'  => '',
			],
			'card_bg'   => [
				'label' => __( 'Card Background', 'hkdev-shop-elements' ),
				'help'  => '',
			],
			'bg_soft'   => [
				'label' => __( 'Soft Background', 'hkdev-shop-elements' ),
				'help'  => __( 'Filters, chips, and muted panels.', 'hkdev-shop-elements' ),
			],
		];
	}

	/**
	 * Built-in presets. Keys must stay stable — they are stored in Elementor.
	 *
	 * @return array<string,array{name:string,builtin:bool,colors:array<string,string>}>
	 */
	public static function builtins() {
		return [
			'green'      => [
				'name'    => __( 'Green (Default)', 'hkdev-shop-elements' ),
				'builtin' => true,
				'colors'  => [
					'primary'   => '#03a550',
					'hover'     => '#028a40',
					'secondary' => '#f06724',
					'info'      => '#026b33',
					'accent'    => '#f06724',
					'text'      => '#141a14',
					'muted'     => '#5f6e66',
					'page_bg'   => '#f7faf7',
					'card_bg'   => '#ffffff',
					'bg_soft'   => '#f2f9f3',
				],
			],
			'orange'     => [
				'name'    => __( 'Orange', 'hkdev-shop-elements' ),
				'builtin' => true,
				'colors'  => [
					'primary'   => '#f06724',
					'hover'     => '#c45822',
					'secondary' => '#03a550',
					'info'      => '#c45822',
					'accent'    => '#f06724',
					'text'      => '#141a14',
					'muted'     => '#5f6e66',
					'page_bg'   => '#fff8f4',
					'card_bg'   => '#ffffff',
					'bg_soft'   => '#fff3eb',
				],
			],
			'monochrome' => [
				'name'    => __( 'Monochrome', 'hkdev-shop-elements' ),
				'builtin' => true,
				'colors'  => [
					'primary'   => '#222222',
					'hover'     => '#111111',
					'secondary' => '#4a4a4a',
					'info'      => '#3a3a3a',
					'accent'    => '#2f2f2f',
					'text'      => '#1d1d1d',
					'muted'     => '#666666',
					'page_bg'   => '#f3f3f3',
					'card_bg'   => '#ffffff',
					'bg_soft'   => '#f3f3f3',
				],
			],
		];
	}

	/**
	 * Custom presets from the admin screen.
	 *
	 * @return array<string,array{name:string,builtin:bool,colors:array<string,string>}>
	 */
	public static function custom() {
		$saved = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $saved ) ) {
			return [];
		}

		$out = [];
		foreach ( $saved as $slug => $preset ) {
			$clean = self::sanitize_preset( $slug, $preset, false );
			if ( $clean ) {
				$out[ $clean['slug'] ] = $clean;
			}
		}

		return $out;
	}

	/**
	 * Built-in + custom, custom last.
	 *
	 * @return array<string,array{name:string,builtin:bool,colors:array<string,string>}>
	 */
	public static function all() {
		return array_merge( self::builtins(), self::custom() );
	}

	/**
	 * @param string $slug Preset slug.
	 * @return bool
	 */
	public static function is_builtin( $slug ) {
		return isset( self::builtins()[ sanitize_key( (string) $slug ) ] );
	}

	/**
	 * @param string $slug Preset slug.
	 * @return bool
	 */
	public static function exists( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return '' !== $slug && isset( self::all()[ $slug ] );
	}

	/**
	 * @param string $slug Raw slug.
	 * @return string Known slug, or green.
	 */
	public static function sanitize( $slug ) {
		$slug = sanitize_key( (string) $slug );
		return self::exists( $slug ) ? $slug : self::DEFAULT;
	}

	/**
	 * @param string $slug Preset slug.
	 * @return array{name:string,builtin:bool,colors:array<string,string>}|null
	 */
	public static function get( $slug ) {
		$slug  = sanitize_key( (string) $slug );
		$all   = self::all();
		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Elementor SELECT options.
	 *
	 * @return array<string,string>
	 */
	public static function select_options() {
		$options = [];
		foreach ( self::all() as $slug => $preset ) {
			$label = $preset['name'];
			if ( empty( $preset['builtin'] ) ) {
				$label = sprintf(
					/* translators: %s: custom preset name */
					__( '%s (Custom)', 'hkdev-shop-elements' ),
					$preset['name']
				);
			}
			$options[ $slug ] = $label;
		}

		return $options;
	}

	/**
	 * Resolved color tokens (including derived hover / tint values).
	 *
	 * @param string $slug Preset slug.
	 * @return array<string,string>
	 */
	public static function tokens( $slug ) {
		$preset = self::get( self::sanitize( $slug ) );
		$colors = is_array( $preset ) ? $preset['colors'] : self::builtins()[ self::DEFAULT ]['colors'];
		$green  = self::builtins()[ self::DEFAULT ]['colors'];

		$primary = self::safe_color( isset( $colors['primary'] ) ? $colors['primary'] : '', $green['primary'] );
		$hover   = self::safe_color( isset( $colors['hover'] ) ? $colors['hover'] : '', '' );
		if ( '' === $hover ) {
			$hover = self::darken_hex( $primary, 12 );
		}

		$tokens = [
			'primary'      => $primary,
			'hover'        => $hover,
			'secondary'    => self::safe_color( isset( $colors['secondary'] ) ? $colors['secondary'] : '', $green['secondary'] ),
			'info'         => self::safe_color( isset( $colors['info'] ) ? $colors['info'] : '', $green['info'] ),
			'accent'       => self::safe_color( isset( $colors['accent'] ) ? $colors['accent'] : '', $green['accent'] ),
			'text'         => self::safe_color( isset( $colors['text'] ) ? $colors['text'] : '', $green['text'] ),
			'muted'        => self::safe_color( isset( $colors['muted'] ) ? $colors['muted'] : '', $green['muted'] ),
			'page_bg'      => self::safe_color( isset( $colors['page_bg'] ) ? $colors['page_bg'] : '', $green['page_bg'] ),
			'card_bg'      => self::safe_color( isset( $colors['card_bg'] ) ? $colors['card_bg'] : '', $green['card_bg'] ),
			'bg_soft'      => self::safe_color( isset( $colors['bg_soft'] ) ? $colors['bg_soft'] : '', $green['bg_soft'] ),
			'hover_border' => self::hex_to_rgba( $primary, 0.28 ),
			'brand_light'  => self::hex_to_rgba( $primary, 0.10 ),
		];

		return $tokens;
	}

	/**
	 * Inline CSS custom properties for a preset.
	 *
	 * @param string          $slug     Preset slug.
	 * @param string[]        $prefixes Variable prefixes (hkdev, sp).
	 * @return string
	 */
	public static function tokens_css( $slug, $prefixes = [ 'hkdev' ] ) {
		$tokens   = self::tokens( $slug );
		$prefixes = is_array( $prefixes ) ? $prefixes : [ 'hkdev' ];
		$map      = [
			'primary'      => 'brand-primary',
			'hover'        => 'brand-hover',
			'secondary'    => 'brand-secondary',
			'info'         => 'brand-info',
			'accent'       => 'brand-accent',
			'text'         => 'text-color',
			'muted'        => 'text-muted',
			'page_bg'      => 'page-bg',
			'card_bg'      => 'card-bg',
			'bg_soft'      => 'bg-soft',
			'hover_border' => 'hover-border',
			'brand_light'  => 'brand-light',
		];

		$css = '';
		foreach ( $prefixes as $prefix ) {
			$prefix = sanitize_key( (string) $prefix );
			if ( '' === $prefix ) {
				continue;
			}
			foreach ( $map as $key => $suffix ) {
				if ( ! isset( $tokens[ $key ] ) || '' === $tokens[ $key ] ) {
					continue;
				}
				$css .= '--' . $prefix . '-' . $suffix . ':' . $tokens[ $key ] . ';';
			}
		}

		if ( in_array( 'hkdev', $prefixes, true ) ) {
			$css .= '--hd-primary:' . $tokens['primary'] . ';';
			$css .= '--hd-primary-dark:' . $tokens['hover'] . ';';
			$css .= '--hd-secondary:' . $tokens['secondary'] . ';';
			$css .= '--hd-secondary-dark:' . $tokens['info'] . ';';
			$css .= '--hd-info:' . $tokens['info'] . ';';
			$css .= '--hd-text:' . $tokens['text'] . ';';
			$css .= '--hd-muted:' . $tokens['muted'] . ';';
			$css .= '--hd-soft:' . $tokens['bg_soft'] . ';';
			$css .= '--brand-primary:' . $tokens['primary'] . ';';
			$css .= '--brand-hover:' . $tokens['hover'] . ';';
			$css .= '--brand-accent:' . $tokens['accent'] . ';';
			$css .= '--brand-light:' . $tokens['brand_light'] . ';';
			$css .= '--text-dark:' . $tokens['text'] . ';';
			$css .= '--text-muted:' . $tokens['muted'] . ';';
			$css .= '--bg-soft:' . $tokens['bg_soft'] . ';';
			$css .= '--hkcat-primary:' . $tokens['primary'] . ';';
			$css .= '--hkcat-primary-dark:' . $tokens['hover'] . ';';
			$css .= '--hkcat-soft:' . $tokens['bg_soft'] . ';';
			$css .= '--hkcat-text:' . $tokens['text'] . ';';
			$css .= '--hkcat-muted:' . $tokens['muted'] . ';';
		}

		return $css;
	}

	/**
	 * Full stylesheet for every preset (built-in + custom).
	 *
	 * @return string
	 */
	public static function stylesheet() {
		$rules = '';
		foreach ( self::all() as $slug => $preset ) {
			unset( $preset );
			$vars = self::tokens_css( $slug, [ 'hkdev', 'sp' ] );
			if ( '' === $vars ) {
				continue;
			}
			$rules .= '[data-card-theme="' . esc_attr( $slug ) . '"]{' . $vars . '}';
		}

		return $rules;
	}

	/**
	 * Persist a custom preset.
	 *
	 * @param array $raw Raw form data.
	 * @return array{ok:bool,slug:string,message:string}
	 */
	public static function save_custom( $raw ) {
		$raw     = is_array( $raw ) ? $raw : [];
		$name    = sanitize_text_field( isset( $raw['name'] ) ? (string) $raw['name'] : '' );
		$slug    = sanitize_key( isset( $raw['slug'] ) ? (string) $raw['slug'] : '' );
		$current = self::custom();

		if ( '' === $name ) {
			return [
				'ok'      => false,
				'slug'    => '',
				'message' => __( 'Please enter a preset name.', 'hkdev-shop-elements' ),
			];
		}

		if ( '' === $slug ) {
			$slug = sanitize_key( sanitize_title( $name ) );
		}

		if ( '' === $slug ) {
			$slug = 'theme-' . substr( md5( $name . wp_rand() ), 0, 6 );
		}

		$original = sanitize_key( isset( $raw['original_slug'] ) ? (string) $raw['original_slug'] : '' );
		if ( self::is_builtin( $slug ) ) {
			$slug .= '-custom';
		}

		if ( $original && isset( $current[ $original ] ) && $original !== $slug ) {
			unset( $current[ $original ] );
		}

		if ( isset( $current[ $slug ] ) && $original !== $slug ) {
			$slug .= '-' . substr( md5( (string) microtime( true ) ), 0, 4 );
		}

		$preset = self::sanitize_preset(
			$slug,
			[
				'name'   => $name,
				'colors' => isset( $raw['colors'] ) && is_array( $raw['colors'] ) ? $raw['colors'] : [],
			],
			false
		);

		if ( ! $preset ) {
			return [
				'ok'      => false,
				'slug'    => '',
				'message' => __( 'Could not save this preset. Check the colors and try again.', 'hkdev-shop-elements' ),
			];
		}

		$current[ $slug ] = $preset;
		update_option( self::OPTION_NAME, self::export_custom( $current ), false );

		return [
			'ok'      => true,
			'slug'    => $slug,
			'message' => __( 'Color preset saved.', 'hkdev-shop-elements' ),
		];
	}

	/**
	 * @param string $slug Custom preset slug.
	 * @return bool
	 */
	public static function delete_custom( $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || self::is_builtin( $slug ) ) {
			return false;
		}

		$current = self::custom();
		if ( ! isset( $current[ $slug ] ) ) {
			return false;
		}

		unset( $current[ $slug ] );
		update_option( self::OPTION_NAME, self::export_custom( $current ), false );

		return true;
	}

	/**
	 * Admin screen URL.
	 *
	 * @param array<string,string> $args Extra query args.
	 * @return string
	 */
	public static function admin_url( $args = [] ) {
		$args = is_array( $args ) ? $args : [];
		$args['page'] = 'hkdev-shop-elements-colors';

		return admin_url( 'admin.php?' . http_build_query( $args ) );
	}

	/**
	 * Print theme CSS once per request.
	 *
	 * @return void
	 */
	public function enqueue_theme_css() {
		$css = self::stylesheet();
		if ( '' === $css ) {
			return;
		}

		if ( ! wp_style_is( 'hkdev-elements-color-themes', 'registered' ) ) {
			wp_register_style( 'hkdev-elements-color-themes', false, [], HKDEV_ELEMENTS_VERSION );
		}

		if ( ! wp_style_is( 'hkdev-elements-color-themes', 'enqueued' ) ) {
			wp_enqueue_style( 'hkdev-elements-color-themes' );
			wp_add_inline_style( 'hkdev-elements-color-themes', $css );
		}
	}

	/**
	 * @param string $slug   Preset slug.
	 * @param mixed  $preset Raw preset.
	 * @param bool   $builtin Whether this is a built-in row.
	 * @return array{slug:string,name:string,builtin:bool,colors:array<string,string>}|null
	 */
	private static function sanitize_preset( $slug, $preset, $builtin ) {
		$slug = sanitize_key( (string) $slug );
		if ( '' === $slug || ! is_array( $preset ) ) {
			return null;
		}

		$name = sanitize_text_field( isset( $preset['name'] ) ? (string) $preset['name'] : '' );
		if ( '' === $name ) {
			$name = ucwords( str_replace( '-', ' ', $slug ) );
		}

		$raw_colors = isset( $preset['colors'] ) && is_array( $preset['colors'] ) ? $preset['colors'] : [];
		$defaults   = self::builtins()[ self::DEFAULT ]['colors'];
		$colors     = [];

		foreach ( self::color_fields() as $key => $field ) {
			unset( $field );
			$raw = isset( $raw_colors[ $key ] ) ? (string) $raw_colors[ $key ] : '';
			if ( 'hover' === $key ) {
				$colors[ $key ] = self::safe_color( $raw, '' );
				continue;
			}
			$fallback       = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
			$colors[ $key ] = self::safe_color( $raw, $fallback );
		}

		if ( '' === $colors['primary'] ) {
			return null;
		}

		return [
			'slug'    => $slug,
			'name'    => $name,
			'builtin' => (bool) $builtin,
			'colors'  => $colors,
		];
	}

	/**
	 * @param array<string,array{name:string,colors:array<string,string>}> $presets Custom presets.
	 * @return array<string,array{name:string,colors:array<string,string>}>
	 */
	private static function export_custom( $presets ) {
		$out = [];
		foreach ( $presets as $slug => $preset ) {
			$out[ $slug ] = [
				'name'   => $preset['name'],
				'colors' => $preset['colors'],
			];
		}

		return $out;
	}

	/**
	 * @param string $value    Raw color.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	private static function safe_color( $value, $fallback ) {
		$safe = \HkdevShopElements\hkdev_elements_sanitize_css_color( $value );
		if ( '' !== $safe ) {
			return $safe;
		}

		return \HkdevShopElements\hkdev_elements_sanitize_css_color( $fallback );
	}

	/**
	 * @param string $hex     Hex color.
	 * @param int    $percent Percent to darken (0–100).
	 * @return string
	 */
	private static function darken_hex( $hex, $percent ) {
		$rgb = self::hex_to_rgb( $hex );
		if ( ! $rgb ) {
			return $hex;
		}

		$percent = max( 0, min( 100, (int) $percent ) );
		$factor  = 1 - ( $percent / 100 );

		return sprintf(
			'#%02x%02x%02x',
			max( 0, (int) floor( $rgb[0] * $factor ) ),
			max( 0, (int) floor( $rgb[1] * $factor ) ),
			max( 0, (int) floor( $rgb[2] * $factor ) )
		);
	}

	/**
	 * @param string $hex   Hex color.
	 * @param float  $alpha Alpha 0–1.
	 * @return string
	 */
	private static function hex_to_rgba( $hex, $alpha ) {
		$rgb = self::hex_to_rgb( $hex );
		if ( ! $rgb ) {
			return 'rgba(0,0,0,' . (float) $alpha . ')';
		}

		return sprintf( 'rgba(%d,%d,%d,%s)', $rgb[0], $rgb[1], $rgb[2], rtrim( rtrim( number_format( (float) $alpha, 2, '.', '' ), '0' ), '.' ) );
	}

	/**
	 * @param string $hex Hex color.
	 * @return int[]|null
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( trim( (string) $hex ), '#' );
		if ( 3 === strlen( $hex ) && ctype_xdigit( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}

		return [
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		];
	}
}
