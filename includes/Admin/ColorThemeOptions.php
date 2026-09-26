<?php
/**
 * HKDEV Color Themes — built-in presets plus custom color sets.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use HkdevShopElements\Includes\Core\ColorTheme;

/**
 * Class ColorThemeOptions
 */
final class ColorThemeOptions {

	const MENU_SLUG     = 'hkdev-shop-elements';
	const SETTINGS_SLUG = 'hkdev-shop-elements-colors';
	const NONCE_ACTION  = 'hkdev_elements_colors_save';

	/**
	 * @var ?ColorThemeOptions
	 */
	private static $instance = null;

	/**
	 * @return ColorThemeOptions
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
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::SETTINGS_SLUG !== $page ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'css/admin.css',
			[],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'js/admin.js',
			[ 'jquery', 'wp-color-picker' ],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/admin.js' ),
			true
		);
	}

	/**
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Color Themes', 'hkdev-shop-elements' ),
			esc_html__( 'Color Themes', 'hkdev-shop-elements' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hkdev-shop-elements' ) );
		}

		$notice = $this->handle_request();
		$edit   = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from   = isset( $_GET['from'] ) ? sanitize_key( wp_unslash( $_GET['from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$form_preset = $this->form_preset( $edit, $from );
		$is_edit     = ( '' !== $edit && ColorTheme::exists( $edit ) && ! ColorTheme::is_builtin( $edit ) );

		?>
		<div class="wrap hkdev-admin-wrap hkdev-admin-compact hkdev-color-themes-wrap">
			<div class="hkdev-admin-header">
				<div class="hkdev-admin-branding">
					<span class="dashicons dashicons-art hkdev-admin-logo" aria-hidden="true"></span>
					<div class="hkdev-admin-titles">
						<h1><?php esc_html_e( 'Color Themes', 'hkdev-shop-elements' ); ?></h1>
						<p><?php esc_html_e( 'Built-in and custom color presets. Every HKDEV widget can pick one from the Color Theme control.', 'hkdev-shop-elements' ); ?></p>
					</div>
				</div>
			</div>
			<?php \HkdevShopElements\Includes\Admin\AdminMenu::instance()->render_module_nav( self::SETTINGS_SLUG ); ?>

			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo $notice['ok'] ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
			<?php endif; ?>

			<div class="hkdev-admin-card">
				<div class="hkdev-admin-card-header">
					<span class="dashicons dashicons-admin-appearance hkdev-admin-card-icon" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Built-in presets', 'hkdev-shop-elements' ); ?></h2>
					<span class="hkdev-admin-card-hint"><?php esc_html_e( 'Always available in Elementor', 'hkdev-shop-elements' ); ?></span>
				</div>
				<div class="hkdev-admin-card-body">
					<div class="hkdev-theme-grid">
						<?php foreach ( ColorTheme::builtins() as $slug => $preset ) : ?>
							<?php $this->render_preset_card( $slug, $preset, false ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="hkdev-admin-card">
				<div class="hkdev-admin-card-header">
					<span class="dashicons dashicons-plus-alt2 hkdev-admin-card-icon" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Custom presets', 'hkdev-shop-elements' ); ?></h2>
					<span class="hkdev-admin-card-hint"><?php esc_html_e( 'Saved on this site only', 'hkdev-shop-elements' ); ?></span>
				</div>
				<div class="hkdev-admin-card-body">
					<?php $custom = ColorTheme::custom(); ?>
					<?php if ( empty( $custom ) ) : ?>
						<p class="hkdev-theme-empty"><?php esc_html_e( 'No custom presets yet. Use the form below to add one, or start from a built-in theme.', 'hkdev-shop-elements' ); ?></p>
					<?php else : ?>
						<div class="hkdev-theme-grid">
							<?php foreach ( $custom as $slug => $preset ) : ?>
								<?php $this->render_preset_card( $slug, $preset, true ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="hkdev-admin-card">
				<div class="hkdev-admin-card-header">
					<span class="dashicons dashicons-admin-customizer hkdev-admin-card-icon" aria-hidden="true"></span>
					<h2><?php echo $is_edit ? esc_html__( 'Edit custom preset', 'hkdev-shop-elements' ) : esc_html__( 'Add custom preset', 'hkdev-shop-elements' ); ?></h2>
				</div>
				<div class="hkdev-admin-card-body">
					<form method="post" action="<?php echo esc_url( ColorTheme::admin_url() ); ?>" class="hkdev-theme-form">
						<?php wp_nonce_field( self::NONCE_ACTION ); ?>
						<input type="hidden" name="hkdev_theme_action" value="save" />
						<?php if ( $is_edit ) : ?>
							<input type="hidden" name="original_slug" value="<?php echo esc_attr( $edit ); ?>" />
						<?php endif; ?>

						<div class="hkdev-theme-form-top">
							<label class="hkdev-theme-field">
								<span><?php esc_html_e( 'Preset name', 'hkdev-shop-elements' ); ?></span>
								<input type="text" name="theme_name" class="regular-text" required maxlength="60" value="<?php echo esc_attr( $form_preset['name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Festival Blue', 'hkdev-shop-elements' ); ?>" />
							</label>
							<label class="hkdev-theme-field">
								<span><?php esc_html_e( 'Slug', 'hkdev-shop-elements' ); ?></span>
								<input type="text" name="theme_slug" class="regular-text" maxlength="40" value="<?php echo esc_attr( $is_edit ? $edit : '' ); ?>" placeholder="<?php esc_attr_e( 'auto from name', 'hkdev-shop-elements' ); ?>" <?php disabled( $is_edit ); ?> />
								<?php if ( $is_edit ) : ?>
									<input type="hidden" name="theme_slug" value="<?php echo esc_attr( $edit ); ?>" />
								<?php endif; ?>
							</label>
						</div>

						<div class="hkdev-theme-live-preview" aria-hidden="true">
							<?php foreach ( ColorTheme::color_fields() as $key => $field ) : ?>
								<span class="hkdev-theme-live-swatch" data-color-key="<?php echo esc_attr( $key ); ?>" style="background:<?php echo esc_attr( $form_preset['colors'][ $key ] ); ?>" title="<?php echo esc_attr( $field['label'] ); ?>"></span>
							<?php endforeach; ?>
						</div>

						<div class="hkdev-theme-colors">
							<?php foreach ( ColorTheme::color_fields() as $key => $field ) : ?>
								<label class="hkdev-theme-color-field" data-color-key="<?php echo esc_attr( $key ); ?>">
									<span class="hkdev-theme-color-label"><?php echo esc_html( $field['label'] ); ?></span>
									<input type="text" class="hkdev-color-input" name="theme_colors[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $form_preset['colors'][ $key ] ); ?>" data-default-color="<?php echo esc_attr( $form_preset['colors'][ $key ] ); ?>" />
									<?php if ( ! empty( $field['help'] ) ) : ?>
										<em><?php echo esc_html( $field['help'] ); ?></em>
									<?php endif; ?>
								</label>
							<?php endforeach; ?>
						</div>

						<div class="hkdev-theme-form-actions">
							<?php
							submit_button(
								$is_edit ? esc_html__( 'Update preset', 'hkdev-shop-elements' ) : esc_html__( 'Add preset', 'hkdev-shop-elements' ),
								'primary',
								'hkdev_theme_submit',
								false
							);
							?>
							<?php if ( $is_edit ) : ?>
								<a class="button" href="<?php echo esc_url( ColorTheme::admin_url() ); ?>"><?php esc_html_e( 'Cancel', 'hkdev-shop-elements' ); ?></a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array{ok:bool,message:string}|null
	 */
	private function handle_request() {
		if ( ! isset( $_POST['hkdev_theme_action'] ) && ! isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return null;
		}

		if ( isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$slug  = sanitize_key( wp_unslash( $_GET['delete'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				return [
					'ok'      => false,
					'message' => __( 'Security check failed.', 'hkdev-shop-elements' ),
				];
			}

			$deleted = ColorTheme::delete_custom( $slug );
			return [
				'ok'      => $deleted,
				'message' => $deleted
					? __( 'Custom preset deleted.', 'hkdev-shop-elements' )
					: __( 'That preset could not be deleted.', 'hkdev-shop-elements' ),
			];
		}

		check_admin_referer( self::NONCE_ACTION );

		$action = sanitize_key( wp_unslash( $_POST['hkdev_theme_action'] ) );
		if ( 'save' !== $action ) {
			return null;
		}

		$result = ColorTheme::save_custom(
			[
				'name'          => isset( $_POST['theme_name'] ) ? wp_unslash( $_POST['theme_name'] ) : '',
				'slug'          => isset( $_POST['theme_slug'] ) ? wp_unslash( $_POST['theme_slug'] ) : '',
				'original_slug' => isset( $_POST['original_slug'] ) ? wp_unslash( $_POST['original_slug'] ) : '',
				'colors'        => isset( $_POST['theme_colors'] ) && is_array( $_POST['theme_colors'] ) ? wp_unslash( $_POST['theme_colors'] ) : [],
			]
		);

		return [
			'ok'      => ! empty( $result['ok'] ),
			'message' => isset( $result['message'] ) ? (string) $result['message'] : '',
		];
	}

	/**
	 * @param string $edit Edit slug.
	 * @param string $from Source slug to copy.
	 * @return array{name:string,colors:array<string,string>}
	 */
	private function form_preset( $edit, $from ) {
		$defaults = ColorTheme::builtins()[ ColorTheme::DEFAULT ];
		$preset   = $defaults;

		if ( $edit && ColorTheme::exists( $edit ) ) {
			$found = ColorTheme::get( $edit );
			if ( $found ) {
				$preset = $found;
			}
		} elseif ( $from && ColorTheme::exists( $from ) ) {
			$found = ColorTheme::get( $from );
			if ( $found ) {
				$preset         = $found;
				$preset['name'] = '';
			}
		}

		return [
			'name'   => isset( $preset['name'] ) ? (string) $preset['name'] : '',
			'colors' => isset( $preset['colors'] ) && is_array( $preset['colors'] ) ? $preset['colors'] : $defaults['colors'],
		];
	}

	/**
	 * @param string $slug   Preset slug.
	 * @param array  $preset Preset data.
	 * @param bool   $custom Whether this is a custom preset.
	 * @return void
	 */
	private function render_preset_card( $slug, $preset, $custom ) {
		$colors = isset( $preset['colors'] ) && is_array( $preset['colors'] ) ? $preset['colors'] : [];
		$swatch_keys = [ 'primary', 'secondary', 'accent', 'info', 'text', 'bg_soft' ];
		?>
		<article class="hkdev-theme-card<?php echo $custom ? ' is-custom' : ''; ?>">
			<div class="hkdev-theme-swatches">
				<?php foreach ( $swatch_keys as $key ) : ?>
					<?php if ( empty( $colors[ $key ] ) ) { continue; } ?>
					<span class="hkdev-theme-swatch" style="background:<?php echo esc_attr( $colors[ $key ] ); ?>" title="<?php echo esc_attr( $key ); ?>"></span>
				<?php endforeach; ?>
			</div>
			<strong class="hkdev-theme-card-title"><?php echo esc_html( $preset['name'] ); ?></strong>
			<code class="hkdev-theme-card-slug"><?php echo esc_html( $slug ); ?></code>
			<div class="hkdev-theme-card-actions">
				<?php if ( $custom ) : ?>
					<a class="button button-small" href="<?php echo esc_url( ColorTheme::admin_url( [ 'edit' => $slug ] ) ); ?>"><?php esc_html_e( 'Edit', 'hkdev-shop-elements' ); ?></a>
					<a class="button button-small" href="<?php echo esc_url( ColorTheme::admin_url( [ 'from' => $slug ] ) ); ?>"><?php esc_html_e( 'Duplicate', 'hkdev-shop-elements' ); ?></a>
					<a class="button button-small hkdev-theme-delete" href="<?php echo esc_url( wp_nonce_url( ColorTheme::admin_url( [ 'delete' => $slug ] ), self::NONCE_ACTION ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this custom preset?', 'hkdev-shop-elements' ) ); ?>');"><?php esc_html_e( 'Delete', 'hkdev-shop-elements' ); ?></a>
				<?php else : ?>
					<a class="button button-small" href="<?php echo esc_url( ColorTheme::admin_url( [ 'from' => $slug ] ) ); ?>"><?php esc_html_e( 'Use as starting point', 'hkdev-shop-elements' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
