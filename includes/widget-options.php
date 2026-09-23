<?php
/**
 * HKDEV Widget Manager — admin grid to enable / disable Elementor widgets.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget_Options
 */
final class Widget_Options {

	const OPTION_NAME   = 'hkdev_elements_enabled_widgets';
	const MENU_SLUG     = 'hkdev-shop-elements';
	const SETTINGS_SLUG = 'hkdev-shop-elements-widgets';
	const NONCE_ACTION  = 'hkdev_elements_widgets_save';

	/**
	 * @var ?Widget_Options
	 */
	private static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return Widget_Options
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Load admin assets on the widget manager screen.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::SETTINGS_SLUG !== $page ) {
			return;
		}

		wp_enqueue_style(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'css/admin.css',
			[],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			'hkdev-elements-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'js/admin.js',
			[ 'jquery' ],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/admin.js' ),
			true
		);
	}

	/**
	 * Register submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Widget Manager', 'hkdev-shop-elements' ),
			esc_html__( 'Widget Manager', 'hkdev-shop-elements' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Whether a widget slug is enabled.
	 *
	 * When the option has never been saved, every widget stays enabled so
	 * existing sites behave exactly as before.
	 *
	 * @param string $slug Elementor widget name slug.
	 * @return bool
	 */
	public function is_widget_enabled( $slug ) {
		$saved = get_option( self::OPTION_NAME, null );

		if ( null === $saved || false === $saved ) {
			return true;
		}

		if ( ! is_array( $saved ) ) {
			return true;
		}

		return isset( $saved[ $slug ] ) && 'yes' === $saved[ $slug ];
	}

	/**
	 * Merge saved states with the registry defaults.
	 *
	 * @return array<string,bool> slug => enabled
	 */
	public function get_widget_states() {
		$registry = Widget_Manager::get_widget_registry();
		$saved    = get_option( self::OPTION_NAME, null );
		$states   = [];

		foreach ( $registry as $group ) {
			foreach ( $group['widgets'] as $slug => $widget ) {
				if ( null === $saved || false === $saved ) {
					$states[ $slug ] = true;
				} else {
					$states[ $slug ] = isset( $saved[ $slug ] ) && 'yes' === $saved[ $slug ];
				}
			}
		}

		return $states;
	}

	/**
	 * Count enabled widgets.
	 *
	 * @return array{enabled:int,total:int}
	 */
	public function count_enabled_widgets() {
		$states  = $this->get_widget_states();
		$enabled = 0;

		foreach ( $states as $is_on ) {
			if ( $is_on ) {
				++$enabled;
			}
		}

		return [
			'enabled' => $enabled,
			'total'   => count( $states ),
		];
	}

	/**
	 * Render the widget manager admin page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hkdev-shop-elements' ) );
		}

		$saved_notice = false;

		if ( isset( $_POST['hkdev_widgets_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( self::NONCE_ACTION );

			$registry = Widget_Manager::get_widget_registry();
			$posted   = isset( $_POST['hkdev_widget'] ) && is_array( $_POST['hkdev_widget'] ) ? wp_unslash( $_POST['hkdev_widget'] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$states   = [];

			foreach ( $registry as $group ) {
				foreach ( $group['widgets'] as $slug => $widget ) {
					$states[ $slug ] = ! empty( $posted[ $slug ] ) ? 'yes' : 'no';
				}
			}

			update_option( self::OPTION_NAME, $states, false );
			$saved_notice = true;
		}

		$registry = Widget_Manager::get_widget_registry();
		$states   = $this->get_widget_states();
		$counts   = $this->count_enabled_widgets();
		?>
		<div class="wrap hkdev-admin-wrap hkdev-admin-compact hkdev-widget-manager-wrap">
			<div class="hkdev-admin-header">
				<div class="hkdev-admin-branding">
					<span class="dashicons dashicons-screenoptions hkdev-admin-logo" aria-hidden="true"></span>
					<div class="hkdev-admin-titles">
						<h1><?php esc_html_e( 'Widget Manager', 'hkdev-shop-elements' ); ?></h1>
						<p><?php esc_html_e( 'Enable only the Elementor widgets you need. Disabled widgets are not registered, keeping the editor lighter and faster.', 'hkdev-shop-elements' ); ?></p>
					</div>
				</div>
				<div class="hkdev-admin-actions">
					<span class="hkdev-widget-summary">
						<?php
						printf(
							/* translators: 1: enabled count, 2: total count */
							esc_html__( '%1$d / %2$d enabled', 'hkdev-shop-elements' ),
							(int) $counts['enabled'],
							(int) $counts['total']
						);
						?>
					</span>
				</div>
			</div>
			<?php Admin_Menu::instance()->render_module_nav( self::SETTINGS_SLUG ); ?>

			<?php if ( $saved_notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Widget settings saved.', 'hkdev-shop-elements' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<?php foreach ( $registry as $group_key => $group ) : ?>
					<div class="hkdev-admin-card hkdev-widget-group" data-group="<?php echo esc_attr( $group_key ); ?>">
						<div class="hkdev-admin-card-header">
							<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?> hkdev-admin-card-icon" aria-hidden="true"></span>
							<h2><?php echo esc_html( $group['label'] ); ?></h2>
							<div class="hkdev-widget-group-actions">
								<button type="button" class="button button-small hkdev-widget-enable-group"><?php esc_html_e( 'Enable all', 'hkdev-shop-elements' ); ?></button>
								<button type="button" class="button button-small hkdev-widget-disable-group"><?php esc_html_e( 'Disable all', 'hkdev-shop-elements' ); ?></button>
							</div>
						</div>
						<div class="hkdev-admin-card-body">
							<div class="hkdev-widget-grid">
								<?php foreach ( $group['widgets'] as $slug => $widget ) : ?>
									<?php $is_on = ! empty( $states[ $slug ] ); ?>
									<label class="hkdev-widget-card<?php echo $is_on ? ' is-enabled' : ' is-disabled'; ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
										<div class="hkdev-widget-card-head">
											<span class="hkdev-widget-card-icon"><i class="<?php echo esc_attr( $widget['icon'] ); ?>" aria-hidden="true"></i></span>
											<span class="hkdev-toggle">
												<input type="checkbox" name="hkdev_widget[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $is_on ); ?> />
												<span class="hkdev-toggle-slider"></span>
											</span>
										</div>
										<strong class="hkdev-widget-card-title"><?php echo esc_html( $widget['title'] ); ?></strong>
										<span class="hkdev-widget-card-desc"><?php echo esc_html( $widget['description'] ); ?></span>
										<code class="hkdev-widget-card-slug"><?php echo esc_html( $slug ); ?></code>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="hkdev-admin-card">
					<div class="hkdev-admin-card-body">
						<p class="hkdev-widget-note">
							<?php esc_html_e( 'Note: Pages that already use a disabled widget will show a placeholder in Elementor until you re-enable that widget or replace it.', 'hkdev-shop-elements' ); ?>
						</p>
						<div class="hkdev-widget-toolbar">
							<button type="button" class="button hkdev-widget-enable-all"><?php esc_html_e( 'Enable all widgets', 'hkdev-shop-elements' ); ?></button>
							<button type="button" class="button hkdev-widget-disable-all"><?php esc_html_e( 'Disable all widgets', 'hkdev-shop-elements' ); ?></button>
							<?php submit_button( esc_html__( 'Save Widget Settings', 'hkdev-shop-elements' ), 'primary', 'hkdev_widgets_submit', false ); ?>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}
