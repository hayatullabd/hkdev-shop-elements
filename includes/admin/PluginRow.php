<?php
/**
 * Plugin row links and deactivation survey modal.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginRow
 */
final class PluginRow {

	/**
	 * @var ?PluginRow
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $plugin_basename = '';

	/**
	 * @return PluginRow
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
		$this->plugin_basename = plugin_basename( HKDEV_ELEMENTS_PATH . 'hkdev-shop-elements.php' );

		add_filter( 'plugin_action_links_' . $this->plugin_basename, [ $this, 'plugin_action_links' ] );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 20, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_footer-plugins.php', [ $this, 'render_deactivation_modal' ] );
		add_action( 'wp_ajax_hkdev_elements_deactivation_feedback', [ $this, 'save_deactivation_feedback' ] );
	}

	/**
	 * Action links under plugin name.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		$new_links = [
			'<a href="' . esc_url( admin_url( 'admin.php?page=hkdev-shop-elements' ) ) . '">' . esc_html__( 'Settings', 'hkdev-shop-elements' ) . '</a>',
			'<a href="' . esc_url( 'https://github.com/hayatullabd/hkdev-shop-elements/wiki' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Docs', 'hkdev-shop-elements' ) . '</a>',
			'<a href="' . esc_url( 'https://github.com/hayatullabd/hkdev-shop-elements/issues' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'hkdev-shop-elements' ) . '</a>',
		];

		return array_merge( $new_links, $links );
	}

	/**
	 * Plugin row extra meta links.
	 *
	 * @param string[] $links Existing links.
	 * @param string   $file  Current plugin file.
	 * @return string[]
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $file !== $this->plugin_basename ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://github.com/hayatullabd/hkdev-shop-elements' ),
			esc_html__( 'GitHub', 'hkdev-shop-elements' )
		);

		return $links;
	}

	/**
	 * Enqueue modal assets only on Plugins screen.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		wp_enqueue_style(
			'hkdev-elements-plugin-row',
			HKDEV_ELEMENTS_URL . 'assets/css/admin-plugin-row.css',
			[],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin-plugin-row.css' )
		);

		wp_enqueue_script(
			'hkdev-elements-plugin-row',
			HKDEV_ELEMENTS_URL . 'assets/js/admin-plugin-row.js',
			[ 'jquery', 'jquery-ui-dialog' ],
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/admin-plugin-row.js' ),
			true
		);

		wp_localize_script(
			'hkdev-elements-plugin-row',
			'hkdevPluginRow',
			[
				'pluginSlug'      => $this->plugin_basename,
				'ajaxAction'      => 'hkdev_elements_deactivation_feedback',
				'nonce'           => wp_create_nonce( 'hkdev_elements_deactivation_feedback' ),
				'submitLabel'     => esc_html__( 'Submit & Deactivate', 'hkdev-shop-elements' ),
				'skipLabel'       => esc_html__( 'Skip & Deactivate', 'hkdev-shop-elements' ),
				'validationLabel' => esc_html__( 'Please choose a reason first.', 'hkdev-shop-elements' ),
			]
		);
	}

	/**
	 * Modal markup for deactivation feedback.
	 *
	 * @return void
	 */
	public function render_deactivation_modal() {
		?>
		<div id="hkdev-deactivate-dialog" class="hkdev-deactivate-dialog" title="<?php esc_attr_e( 'Quick Feedback', 'hkdev-shop-elements' ); ?>">
			<p class="hkdev-deactivate-intro">
				<?php esc_html_e( 'Before deactivating, please tell us what went wrong so we can improve.', 'hkdev-shop-elements' ); ?>
			</p>

			<div class="hkdev-deactivate-reasons">
				<label class="hkdev-deactivate-reason">
					<input type="radio" name="hkdev_reason" value="bug">
					<span><?php esc_html_e( 'I found a bug or issue', 'hkdev-shop-elements' ); ?></span>
				</label>
				<label class="hkdev-deactivate-reason">
					<input type="radio" name="hkdev_reason" value="missing_feature">
					<span><?php esc_html_e( 'A feature I need is missing', 'hkdev-shop-elements' ); ?></span>
				</label>
				<label class="hkdev-deactivate-reason">
					<input type="radio" name="hkdev_reason" value="not_needed">
					<span><?php esc_html_e( 'I no longer need this plugin', 'hkdev-shop-elements' ); ?></span>
				</label>
				<label class="hkdev-deactivate-reason">
					<input type="radio" name="hkdev_reason" value="temporary">
					<span><?php esc_html_e( 'This is temporary', 'hkdev-shop-elements' ); ?></span>
				</label>
				<label class="hkdev-deactivate-reason">
					<input type="radio" name="hkdev_reason" value="other">
					<span><?php esc_html_e( 'Other', 'hkdev-shop-elements' ); ?></span>
				</label>
			</div>

			<p class="hkdev-deactivate-help">
				<a href="<?php echo esc_url( 'https://github.com/hayatullabd/hkdev-shop-elements/issues' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Need support first? Open an issue on GitHub.', 'hkdev-shop-elements' ); ?>
				</a>
			</p>

			<label for="hkdev-deactivate-feedback" class="hkdev-deactivate-feedback-label">
				<?php esc_html_e( 'Optional details', 'hkdev-shop-elements' ); ?>
			</label>
			<textarea id="hkdev-deactivate-feedback" rows="3"></textarea>
			<p id="hkdev-deactivate-validation" class="hkdev-deactivate-validation" aria-live="polite"></p>
		</div>
		<?php
	}

	/**
	 * Save deactivation feedback via AJAX.
	 *
	 * @return void
	 */
	public function save_deactivation_feedback() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		check_ajax_referer( 'hkdev_elements_deactivation_feedback', 'nonce' );

		$reason   = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$feedback = isset( $_POST['feedback'] ) ? sanitize_textarea_field( wp_unslash( $_POST['feedback'] ) ) : '';

		if ( function_exists( '\HkdevShopElements\hkdev_elements_log_message' ) ) {
			\HkdevShopElements\hkdev_elements_log_message(
				sprintf(
					'Deactivation feedback: reason=%s, feedback=%s',
					$reason ? $reason : 'none',
					$feedback ? $feedback : 'none'
				)
			);
		}

		wp_send_json_success();
	}
}

/**
 * Backward-compatible alias for old class naming.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Plugin_Row' ) ) {
	class Plugin_Row extends PluginRow {}
}
