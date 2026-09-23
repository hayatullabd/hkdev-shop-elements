<?php
/**
 * HKDEV Customer Reviews — WP Admin settings (shared review library).
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Review_Options
 */
final class Review_Options {

	const MENU_SLUG     = 'hkdev-shop-elements';
	const SETTINGS_SLUG = 'hkdev-shop-elements-reviews';
	const NONCE_ACTION  = 'hkdev_rv_settings_save';

	/**
	 * @var ?Review_Options
	 */
	private static $instance = null;

	/**
	 * @return Review_Options
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 100 );
		add_action( 'wp_ajax_hkdev_rv_search_products', [ $this, 'ajax_search_products' ] );
	}

	/**
	 * AJAX product search for Top Pick / Featured product fields.
	 *
	 * @return void
	 */
	public function ajax_search_products() {
		check_ajax_referer( 'hkdev_rv_admin', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			wp_send_json( [] );
		}

		$term = isset( $_REQUEST['term'] ) ? wc_clean( wp_unslash( $_REQUEST['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $term ) {
			wp_send_json( [] );
		}

		$limit = 30;
		$ids   = [];

		if ( class_exists( 'WC_Data_Store' ) ) {
			$ids = WC_Data_Store::load( 'product' )->search_products( $term, '', true, false, $limit );
		}

		if ( empty( $ids ) ) {
			$products = wc_get_products(
				[
					'limit'   => $limit,
					'status'  => 'publish',
					'orderby' => 'title',
					'order'   => 'ASC',
					's'       => $term,
					'return'  => 'ids',
				]
			);
			$ids = is_array( $products ) ? $products : [];
		}

		$out = [];

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$out[ (string) $product->get_id() ] = wp_strip_all_tags( $product->get_formatted_name() );
		}

		wp_send_json( $out );
	}

	/**
	 * Register + enqueue WooCommerce SelectWoo on this screen (WC does not always register it on custom admin pages).
	 *
	 * @return bool
	 */
	private function ensure_woocommerce_select_assets() {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		$wc      = WC();
		$version = $wc->version;
		$base    = $wc->plugin_url() . '/assets/';
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style( 'select2', $base . 'css/select2.css', [], $version );
		}

		if ( ! wp_script_is( 'selectWoo', 'registered' ) ) {
			wp_register_script(
				'selectWoo',
				$base . 'js/selectWoo/selectWoo.full' . $suffix . '.js',
				[ 'jquery' ],
				'1.0.9-wc.' . $version,
				true
			);
		}

		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'selectWoo' );

		return true;
	}

	/**
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

		wp_enqueue_media();

		$script_deps = [ 'jquery' ];

		if ( $this->ensure_woocommerce_select_assets() ) {
			$script_deps[] = 'selectWoo';
		}

		wp_enqueue_script(
			'hkdev-elements-reviews-admin',
			HKDEV_ELEMENTS_ASSETS_URL . 'js/reviews-admin.js',
			$script_deps,
			\HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/reviews-admin.js' ),
			true
		);

		wp_localize_script(
			'hkdev-elements-reviews-admin',
			'hkdevRvAdmin',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'searchNonce'  => wp_create_nonce( 'hkdev_rv_admin' ),
				'searchAction' => 'hkdev_rv_search_products',
				'placeholder'  => __( 'Search product…', 'hkdev-shop-elements' ),
				'minInput'     => 1,
			]
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
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__( 'Customer Reviews', 'hkdev-shop-elements' ),
			esc_html__( 'Customer Reviews', 'hkdev-shop-elements' ),
			'manage_options',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * @return array
	 */
	private function stored() {
		return Review_Engine::instance()->get_stored_settings();
	}

	/**
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hkdev-shop-elements' ) );
		}

		$saved_notice = false;

		if ( isset( $_POST['hkdev_rv_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			check_admin_referer( self::NONCE_ACTION );

			$text = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$textarea = static function ( $key ) {
				return isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$yes = static function ( $key ) {
				return isset( $_POST[ $key ] ) && '1' === $_POST[ $key ] ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			};

			$videos = [];
			if ( ! empty( $_POST['hkdev_rv_videos'] ) && is_array( $_POST['hkdev_rv_videos'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				foreach ( wp_unslash( $_POST['hkdev_rv_videos'] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( ! is_array( $row ) ) {
						continue;
					}
					$thumb_id = isset( $row['thumbnail_id'] ) ? absint( $row['thumbnail_id'] ) : 0;
					$image    = isset( $row['image'] ) ? esc_url_raw( $row['image'] ) : '';
					if ( $thumb_id && '' === $image ) {
						$url = wp_get_attachment_image_url( $thumb_id, 'medium_large' );
						$image = $url ? $url : '';
					}

					$videos[] = [
						'name'          => isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '',
						'thumbnail_id'  => $thumb_id,
						'image'         => $image,
						'video'         => isset( $row['video'] ) ? esc_url_raw( $row['video'] ) : '',
						'rating'        => isset( $row['rating'] ) ? max( 1, min( 5, (int) $row['rating'] ) ) : 5,
						'quote'         => isset( $row['quote'] ) ? sanitize_textarea_field( $row['quote'] ) : '',
						'product_id'    => isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0,
					];
				}
			}

			$proofs = [];
			if ( ! empty( $_POST['hkdev_rv_proofs'] ) && is_array( $_POST['hkdev_rv_proofs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				foreach ( wp_unslash( $_POST['hkdev_rv_proofs'] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( ! is_array( $row ) ) {
						continue;
					}
					$ids_raw = isset( $row['image_ids'] ) ? (string) $row['image_ids'] : '';
					$ids     = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );

					$proofs[] = [
						'name'       => isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '',
						'image_ids'  => implode( ',', $ids ),
						'rating'     => isset( $row['rating'] ) ? max( 1, min( 5, (int) $row['rating'] ) ) : 5,
						'quote'      => isset( $row['quote'] ) ? sanitize_textarea_field( $row['quote'] ) : '',
						'product_id' => isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0,
					];
				}
			}

			$settings = [
				'anchor'               => sanitize_title( $text( 'hkdev_rv_anchor' ) ),
				'heading'              => $text( 'hkdev_rv_heading' ),
				'subheading'           => $textarea( 'hkdev_rv_subheading' ),
				'show_header'          => $yes( 'hkdev_rv_show_header' ),
				'show_tabs'            => $yes( 'hkdev_rv_show_tabs' ),
				'tab_video_label'      => $text( 'hkdev_rv_tab_video_label' ),
				'tab_written_label'    => $text( 'hkdev_rv_tab_written_label' ),
				'default_tab'          => in_array( $text( 'hkdev_rv_default_tab' ), [ 'video', 'written' ], true ) ? $text( 'hkdev_rv_default_tab' ) : 'written',
				'show_video_name'      => $yes( 'hkdev_rv_show_video_name' ),
				'show_video_stars'     => $yes( 'hkdev_rv_show_video_stars' ),
				'show_video_play'      => $yes( 'hkdev_rv_show_video_play' ),
				'show_video_overlay'   => $yes( 'hkdev_rv_show_video_overlay' ),
				'video_thumb_fallback' => $yes( 'hkdev_rv_video_thumb_fallback' ),
				'show_card_name'       => $yes( 'hkdev_rv_show_card_name' ),
				'show_verified'        => $yes( 'hkdev_rv_show_verified' ),
				'show_proof_stars'     => $yes( 'hkdev_rv_show_proof_stars' ),
				'show_filter'          => $yes( 'hkdev_rv_show_filter' ),
				'filter_label'         => $text( 'hkdev_rv_filter_label' ),
				'filter_highest'       => $text( 'hkdev_rv_filter_highest' ),
				'filter_recent'        => $text( 'hkdev_rv_filter_recent' ),
				'filter_default'       => in_array( $text( 'hkdev_rv_filter_default' ), [ 'recent', 'highest' ], true ) ? $text( 'hkdev_rv_filter_default' ) : 'recent',
				'show_product'         => $yes( 'hkdev_rv_show_product' ),
				'top_pick_label'       => $text( 'hkdev_rv_top_pick_label' ),
				'order_button_text'    => $text( 'hkdev_rv_order_button_text' ),
				'view_button_text'     => $text( 'hkdev_rv_view_button_text' ),
				'show_modal_quote'     => $yes( 'hkdev_rv_show_modal_quote' ),
				'show_modal_badge'     => $yes( 'hkdev_rv_show_modal_badge' ),
				'video_badge'          => $text( 'hkdev_rv_video_badge' ),
				'proof_badge'          => $text( 'hkdev_rv_proof_badge' ),
				'video_empty'          => $text( 'hkdev_rv_video_empty' ),
				'written_empty'        => $text( 'hkdev_rv_written_empty' ),
				'videos'               => $videos,
				'proofs'               => $proofs,
			];

			update_option( Review_Engine::OPTION_NAME, $settings );
			$saved_notice = true;
		}

		$s        = $this->stored();
		$defaults = Review_Engine::instance()->admin_settings_defaults();

		$val = static function ( $key ) use ( $s, $defaults ) {
			return $s[ $key ] ?? ( $defaults[ $key ] ?? '' );
		};

		$checked = static function ( $key ) use ( $s, $defaults ) {
			$v = $s[ $key ] ?? ( $defaults[ $key ] ?? 'no' );

			return ( 'yes' === $v ) ? ' checked' : '';
		};

		$videos = is_array( $s['videos'] ?? null ) ? $s['videos'] : [];
		$proofs = is_array( $s['proofs'] ?? null ) ? $s['proofs'] : [];

		?>
		<div class="wrap hkdev-admin-wrap hkdev-admin-compact hkdev-reviews-admin">
			<div class="hkdev-admin-header">
				<div class="hkdev-admin-branding">
					<span class="hkdev-admin-logo dashicons dashicons-star-filled"></span>
					<div class="hkdev-admin-titles">
						<h1><?php esc_html_e( 'Customer Reviews', 'hkdev-shop-elements' ); ?></h1>
						<p><?php esc_html_e( 'Central library for Elementor (global) and shortcode.', 'hkdev-shop-elements' ); ?></p>
					</div>
				</div>
				<div class="hkdev-admin-actions">
					<span class="hkdev-rv-shortcode-hint" title="<?php esc_attr_e( 'Shortcode', 'hkdev-shop-elements' ); ?>"><span class="hkdev-rv-shortcode-label"><?php esc_html_e( 'Shortcode', 'hkdev-shop-elements' ); ?></span><code>[hkdev_customer_reviews]</code></span>
				</div>
			</div>
			<?php Admin_Menu::instance()->render_module_nav( self::SETTINGS_SLUG ); ?>

			<?php if ( $saved_notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Customer review settings saved.', 'hkdev-shop-elements' ); ?></p></div>
			<?php endif; ?>

			<form method="post" id="hkdev-reviews-settings">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="hkdev_rv_submit" value="1">

				<div class="hkdev-tabs-wrap hkdev-rv-tabs-wrap">
					<input type="radio" name="hkdev_rv_tab" id="hkdev-tab-radio-rv-general" class="hkdev-tab-radio" value="general" checked>
					<input type="radio" name="hkdev_rv_tab" id="hkdev-tab-radio-rv-videos" class="hkdev-tab-radio" value="videos">
					<input type="radio" name="hkdev_rv_tab" id="hkdev-tab-radio-rv-proofs" class="hkdev-tab-radio" value="proofs">
					<input type="radio" name="hkdev_rv_tab" id="hkdev-tab-radio-rv-labels" class="hkdev-tab-radio" value="labels">

					<div class="hkdev-tabs">
						<label class="hkdev-tab" for="hkdev-tab-radio-rv-general"><span class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'Header & Tabs', 'hkdev-shop-elements' ); ?></label>
						<label class="hkdev-tab" for="hkdev-tab-radio-rv-videos"><span class="dashicons dashicons-video-alt3"></span><?php esc_html_e( 'Video Reviews', 'hkdev-shop-elements' ); ?></label>
						<label class="hkdev-tab" for="hkdev-tab-radio-rv-proofs"><span class="dashicons dashicons-format-gallery"></span><?php esc_html_e( 'Social Proofs', 'hkdev-shop-elements' ); ?></label>
						<label class="hkdev-tab" for="hkdev-tab-radio-rv-labels"><span class="dashicons dashicons-editor-ul"></span><?php esc_html_e( 'Labels & Modal', 'hkdev-shop-elements' ); ?></label>
					</div>

					<div class="hkdev-tab-content" id="hkdev-tab-rv-general">
						<?php $this->render_general_tab( $val, $checked ); ?>
					</div>
					<div class="hkdev-tab-content" id="hkdev-tab-rv-videos">
						<?php $this->render_videos_tab( $videos, $checked ); ?>
					</div>
					<div class="hkdev-tab-content" id="hkdev-tab-rv-proofs">
						<?php $this->render_proofs_tab( $proofs, $checked ); ?>
					</div>
					<div class="hkdev-tab-content" id="hkdev-tab-rv-labels">
						<?php $this->render_labels_tab( $val, $checked ); ?>
					</div>
				</div>

				<div class="hkdev-admin-form-footer">
					<?php submit_button( __( 'Save Changes', 'hkdev-shop-elements' ), 'primary', 'submit', true ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * @param callable $val     Value helper.
	 * @param callable $checked Checkbox helper.
	 * @return void
	 */
	private function render_general_tab( $val, $checked ) {
		?>
		<div class="hkdev-admin-card">
			<div class="hkdev-admin-card-header">
				<span class="hkdev-admin-card-icon dashicons dashicons-admin-settings"></span>
				<h2><?php esc_html_e( 'Header & Tabs', 'hkdev-shop-elements' ); ?></h2>
			</div>
			<div class="hkdev-admin-card-body">
				<div class="hkdev-admin-subsection">
					<h3 class="hkdev-admin-subtitle"><?php esc_html_e( 'Section header', 'hkdev-shop-elements' ); ?></h3>
					<div class="hkdev-settings-grid hkdev-settings-grid-3">
						<div class="hkdev-field hkdev-field-check">
							<label class="hkdev-checkbox">
								<input type="checkbox" name="hkdev_rv_show_header" value="1"<?php echo $checked( 'show_header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php esc_html_e( 'Show header', 'hkdev-shop-elements' ); ?>
							</label>
						</div>
						<div class="hkdev-field">
							<label for="hkdev_rv_heading"><?php esc_html_e( 'Heading', 'hkdev-shop-elements' ); ?></label>
							<input type="text" id="hkdev_rv_heading" name="hkdev_rv_heading" value="<?php echo esc_attr( $val( 'heading' ) ); ?>">
						</div>
						<div class="hkdev-field">
							<label for="hkdev_rv_anchor"><?php esc_html_e( 'Anchor ID', 'hkdev-shop-elements' ); ?></label>
							<input type="text" id="hkdev_rv_anchor" name="hkdev_rv_anchor" value="<?php echo esc_attr( $val( 'anchor' ) ); ?>" placeholder="customer-reviews">
						</div>
						<div class="hkdev-field hkdev-field-wide">
							<label for="hkdev_rv_subheading"><?php esc_html_e( 'Subheading', 'hkdev-shop-elements' ); ?></label>
							<textarea id="hkdev_rv_subheading" name="hkdev_rv_subheading" rows="2"><?php echo esc_textarea( $val( 'subheading' ) ); ?></textarea>
						</div>
					</div>
				</div>
				<div class="hkdev-admin-subsection">
					<h3 class="hkdev-admin-subtitle"><?php esc_html_e( 'Tab switcher', 'hkdev-shop-elements' ); ?></h3>
					<div class="hkdev-settings-grid hkdev-settings-grid-3">
						<div class="hkdev-field hkdev-field-check">
							<label class="hkdev-checkbox">
								<input type="checkbox" name="hkdev_rv_show_tabs" value="1"<?php echo $checked( 'show_tabs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
								<?php esc_html_e( 'Show tabs when both have items', 'hkdev-shop-elements' ); ?>
							</label>
						</div>
						<div class="hkdev-field">
							<label for="hkdev_rv_tab_video_label"><?php esc_html_e( 'Video tab', 'hkdev-shop-elements' ); ?></label>
							<input type="text" id="hkdev_rv_tab_video_label" name="hkdev_rv_tab_video_label" value="<?php echo esc_attr( $val( 'tab_video_label' ) ); ?>">
						</div>
						<div class="hkdev-field">
							<label for="hkdev_rv_tab_written_label"><?php esc_html_e( 'Social proof tab', 'hkdev-shop-elements' ); ?></label>
							<input type="text" id="hkdev_rv_tab_written_label" name="hkdev_rv_tab_written_label" value="<?php echo esc_attr( $val( 'tab_written_label' ) ); ?>">
						</div>
						<div class="hkdev-field">
							<label for="hkdev_rv_default_tab"><?php esc_html_e( 'Default tab', 'hkdev-shop-elements' ); ?></label>
							<select id="hkdev_rv_default_tab" name="hkdev_rv_default_tab">
								<option value="written"<?php selected( $val( 'default_tab' ), 'written' ); ?>><?php esc_html_e( 'Social Proofs', 'hkdev-shop-elements' ); ?></option>
								<option value="video"<?php selected( $val( 'default_tab' ), 'video' ); ?>><?php esc_html_e( 'Video Reviews', 'hkdev-shop-elements' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Product label for the searchable select.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	private function product_select_label( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		return $product ? wp_strip_all_tags( $product->get_formatted_name() ) : '';
	}

	/**
	 * Searchable WooCommerce product select.
	 *
	 * @param string $input_name Field name attribute.
	 * @param int    $product_id Selected product ID.
	 * @return void
	 */
	private function render_product_select( $input_name, $product_id ) {
		$product_id = absint( $product_id );
		$label      = $this->product_select_label( $product_id );

		if ( function_exists( 'wc_get_product' ) ) {
			?>
			<select
				class="hkdev-rv-product-search"
				name="<?php echo esc_attr( $input_name ); ?>"
				data-placeholder="<?php esc_attr_e( 'Search product…', 'hkdev-shop-elements' ); ?>"
				data-allow_clear="true"
				data-minimum_input_length="1"
			>
				<option value=""></option>
				<?php if ( $product_id && '' !== $label ) : ?>
					<option value="<?php echo esc_attr( (string) $product_id ); ?>" selected="selected"><?php echo esc_html( $label ); ?></option>
				<?php endif; ?>
			</select>
			<?php
			return;
		}

		?>
		<input type="number" min="0" step="1" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $product_id ? (string) $product_id : '' ); ?>" placeholder="<?php esc_attr_e( 'Product ID', 'hkdev-shop-elements' ); ?>">
		<?php
	}

	/**
	 * @param array    $videos   Stored video rows.
	 * @param callable $checked  Checkbox helper.
	 * @return void
	 */
	private function render_videos_tab( array $videos, $checked ) {
		?>
		<div class="hkdev-admin-card">
			<div class="hkdev-admin-card-header">
				<span class="hkdev-admin-card-icon dashicons dashicons-video-alt3"></span>
				<h2><?php esc_html_e( 'Video Reviews', 'hkdev-shop-elements' ); ?></h2>
			</div>
			<div class="hkdev-admin-card-body">
				<div class="hkdev-rv-toggle-row hkdev-chip-row">
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_video_name" value="1"<?php echo $checked( 'show_video_name' ); // phpcs:ignore ?>><?php esc_html_e( 'Name', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_video_stars" value="1"<?php echo $checked( 'show_video_stars' ); // phpcs:ignore ?>><?php esc_html_e( 'Stars', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_video_play" value="1"<?php echo $checked( 'show_video_play' ); // phpcs:ignore ?>><?php esc_html_e( 'Play btn', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_video_overlay" value="1"<?php echo $checked( 'show_video_overlay' ); // phpcs:ignore ?>><?php esc_html_e( 'Overlay', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_video_thumb_fallback" value="1"<?php echo $checked( 'video_thumb_fallback' ); // phpcs:ignore ?>><?php esc_html_e( 'YT thumb', 'hkdev-shop-elements' ); ?></label>
				</div>

				<div class="hkdev-rv-repeater" data-type="video">
					<div class="hkdev-rv-repeater-list">
						<?php
						if ( empty( $videos ) ) {
							$this->render_video_row( 0, [] );
						} else {
							foreach ( $videos as $i => $row ) {
								$this->render_video_row( (int) $i, $row );
							}
						}
						?>
					</div>
					<p><button type="button" class="button hkdev-rv-add-row" data-type="video"><?php esc_html_e( '+ Add video review', 'hkdev-shop-elements' ); ?></button></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int   $index Row index.
	 * @param array $row   Row data.
	 * @return void
	 */
	private function render_video_row( $index, array $row ) {
		$name         = $row['name'] ?? '';
		$thumb_id     = isset( $row['thumbnail_id'] ) ? absint( $row['thumbnail_id'] ) : 0;
		$image        = $row['image'] ?? '';
		if ( $thumb_id && '' === $image ) {
			$url = wp_get_attachment_image_url( $thumb_id, 'medium' );
			$image = $url ? $url : '';
		}
		$video        = $row['video'] ?? '';
		$rating       = isset( $row['rating'] ) ? (int) $row['rating'] : 5;
		$quote        = $row['quote'] ?? '';
		$product_id   = isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0;
		?>
		<div class="hkdev-rv-repeater-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="hkdev-rv-repeater-head">
				<strong><?php echo esc_html( $name ? $name : __( 'Video review', 'hkdev-shop-elements' ) ); ?></strong>
				<button type="button" class="hkdev-rv-remove-row" aria-label="<?php esc_attr_e( 'Remove review', 'hkdev-shop-elements' ); ?>">
					<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Remove', 'hkdev-shop-elements' ); ?></span>
				</button>
			</div>
			<div class="hkdev-settings-grid">
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Customer name', 'hkdev-shop-elements' ); ?></label>
					<input type="text" name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" class="hkdev-rv-name-input">
				</div>
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Rating', 'hkdev-shop-elements' ); ?></label>
					<input type="number" min="1" max="5" step="1" name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][rating]" value="<?php echo esc_attr( (string) $rating ); ?>">
				</div>
				<div class="hkdev-field hkdev-field-wide">
					<label><?php esc_html_e( 'Video URL', 'hkdev-shop-elements' ); ?></label>
					<input type="url" name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][video]" value="<?php echo esc_attr( $video ); ?>" placeholder="https://www.youtube.com/watch?v=...">
				</div>
				<div class="hkdev-field hkdev-field-wide">
					<label><?php esc_html_e( 'Review text (modal)', 'hkdev-shop-elements' ); ?></label>
					<textarea name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][quote]" rows="2"><?php echo esc_textarea( $quote ); ?></textarea>
				</div>
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Top pick product', 'hkdev-shop-elements' ); ?></label>
					<?php $this->render_product_select( 'hkdev_rv_videos[' . (int) $index . '][product_id]', $product_id ); ?>
				</div>
				<div class="hkdev-field hkdev-field-wide">
					<label><?php esc_html_e( 'Thumbnail', 'hkdev-shop-elements' ); ?></label>
					<input type="hidden" class="hkdev-rv-thumb-id" name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][thumbnail_id]" value="<?php echo esc_attr( (string) $thumb_id ); ?>">
					<input type="hidden" class="hkdev-rv-thumb-url" name="hkdev_rv_videos[<?php echo esc_attr( (string) $index ); ?>][image]" value="<?php echo esc_attr( $image ); ?>">
					<div class="hkdev-rv-media-toolbar">
						<button type="button" class="button button-small hkdev-rv-pick-thumb"><?php esc_html_e( 'Thumbnail', 'hkdev-shop-elements' ); ?></button>
						<button type="button" class="button button-small hkdev-rv-clear-thumb"><?php esc_html_e( 'Clear', 'hkdev-shop-elements' ); ?></button>
						<img class="hkdev-rv-thumb-preview<?php echo $image ? ' is-visible' : ''; ?>" src="<?php echo esc_url( $image ); ?>" alt="">
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array    $proofs  Stored proof rows.
	 * @param callable $checked Checkbox helper.
	 * @return void
	 */
	private function render_proofs_tab( array $proofs, $checked ) {
		?>
		<div class="hkdev-admin-card">
			<div class="hkdev-admin-card-header">
				<span class="hkdev-admin-card-icon dashicons dashicons-format-gallery"></span>
				<h2><?php esc_html_e( 'Social Proofs', 'hkdev-shop-elements' ); ?></h2>
			</div>
			<div class="hkdev-admin-card-body">
				<div class="hkdev-rv-toggle-row hkdev-chip-row">
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_card_name" value="1"<?php echo $checked( 'show_card_name' ); // phpcs:ignore ?>><?php esc_html_e( 'Name', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_verified" value="1"<?php echo $checked( 'show_verified' ); // phpcs:ignore ?>><?php esc_html_e( 'Verified', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_proof_stars" value="1"<?php echo $checked( 'show_proof_stars' ); // phpcs:ignore ?>><?php esc_html_e( 'Stars', 'hkdev-shop-elements' ); ?></label>
					<label class="hkdev-checkbox"><input type="checkbox" name="hkdev_rv_show_filter" value="1"<?php echo $checked( 'show_filter' ); // phpcs:ignore ?>><?php esc_html_e( 'Sort filter', 'hkdev-shop-elements' ); ?></label>
				</div>

				<div class="hkdev-rv-repeater" data-type="proof">
					<div class="hkdev-rv-repeater-list">
						<?php
						if ( empty( $proofs ) ) {
							$this->render_proof_row( 0, [] );
						} else {
							foreach ( $proofs as $i => $row ) {
								$this->render_proof_row( (int) $i, $row );
							}
						}
						?>
					</div>
					<p><button type="button" class="button hkdev-rv-add-row" data-type="proof"><?php esc_html_e( '+ Add social proof', 'hkdev-shop-elements' ); ?></button></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int   $index Row index.
	 * @param array $row   Row data.
	 * @return void
	 */
	private function render_proof_row( $index, array $row ) {
		$name       = $row['name'] ?? '';
		$rating     = isset( $row['rating'] ) ? (int) $row['rating'] : 5;
		$quote      = $row['quote'] ?? '';
		$product_id = isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0;
		$ids_raw    = $row['image_ids'] ?? '';
		if ( is_array( $ids_raw ) ) {
			$ids_raw = implode( ',', $ids_raw );
		}
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $ids_raw ) ) );
		$previews = [];
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_image_url( $id, 'thumbnail' );
			if ( $url ) {
				$previews[] = $url;
			}
		}
		?>
		<div class="hkdev-rv-repeater-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<div class="hkdev-rv-repeater-head">
				<strong><?php echo esc_html( $name ? $name : __( 'Social proof', 'hkdev-shop-elements' ) ); ?></strong>
				<button type="button" class="hkdev-rv-remove-row" aria-label="<?php esc_attr_e( 'Remove review', 'hkdev-shop-elements' ); ?>">
					<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Remove', 'hkdev-shop-elements' ); ?></span>
				</button>
			</div>
			<div class="hkdev-settings-grid">
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Customer name', 'hkdev-shop-elements' ); ?></label>
					<input type="text" name="hkdev_rv_proofs[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" class="hkdev-rv-name-input">
				</div>
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Rating', 'hkdev-shop-elements' ); ?></label>
					<input type="number" min="1" max="5" step="1" name="hkdev_rv_proofs[<?php echo esc_attr( (string) $index ); ?>][rating]" value="<?php echo esc_attr( (string) $rating ); ?>">
				</div>
				<div class="hkdev-field hkdev-field-wide">
					<label><?php esc_html_e( 'Review text (modal)', 'hkdev-shop-elements' ); ?></label>
					<textarea name="hkdev_rv_proofs[<?php echo esc_attr( (string) $index ); ?>][quote]" rows="2"><?php echo esc_textarea( $quote ); ?></textarea>
				</div>
				<div class="hkdev-field">
					<label><?php esc_html_e( 'Featured product', 'hkdev-shop-elements' ); ?></label>
					<?php $this->render_product_select( 'hkdev_rv_proofs[' . (int) $index . '][product_id]', $product_id ); ?>
				</div>
				<div class="hkdev-field hkdev-field-wide">
					<label><?php esc_html_e( 'Images', 'hkdev-shop-elements' ); ?></label>
					<input type="hidden" class="hkdev-rv-gallery-ids" name="hkdev_rv_proofs[<?php echo esc_attr( (string) $index ); ?>][image_ids]" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
					<div class="hkdev-rv-media-toolbar">
						<button type="button" class="button button-small hkdev-rv-pick-gallery"><?php esc_html_e( 'Images', 'hkdev-shop-elements' ); ?></button>
						<button type="button" class="button button-small hkdev-rv-clear-gallery"><?php esc_html_e( 'Clear', 'hkdev-shop-elements' ); ?></button>
					</div>
					<div class="hkdev-rv-gallery-previews">
						<?php foreach ( $previews as $url ) : ?>
							<img src="<?php echo esc_url( $url ); ?>" alt="">
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param callable $val     Value helper.
	 * @param callable $checked Checkbox helper.
	 * @return void
	 */
	private function render_labels_tab( $val, $checked ) {
		?>
		<div class="hkdev-admin-card">
			<div class="hkdev-admin-card-header">
				<span class="hkdev-admin-card-icon dashicons dashicons-editor-ul"></span>
				<h2><?php esc_html_e( 'Labels & Modal', 'hkdev-shop-elements' ); ?></h2>
			</div>
			<div class="hkdev-admin-card-body">
				<div class="hkdev-admin-subsection">
					<h3 class="hkdev-admin-subtitle"><?php esc_html_e( 'Filter & promo', 'hkdev-shop-elements' ); ?></h3>
				<div class="hkdev-settings-grid hkdev-settings-grid-3">
					<div class="hkdev-field">
						<label for="hkdev_rv_filter_label"><?php esc_html_e( 'Filter label', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_filter_label" name="hkdev_rv_filter_label" value="<?php echo esc_attr( $val( 'filter_label' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_filter_highest"><?php esc_html_e( 'Highest rating label', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_filter_highest" name="hkdev_rv_filter_highest" value="<?php echo esc_attr( $val( 'filter_highest' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_filter_recent"><?php esc_html_e( 'Most recent label', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_filter_recent" name="hkdev_rv_filter_recent" value="<?php echo esc_attr( $val( 'filter_recent' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_filter_default"><?php esc_html_e( 'Default sort', 'hkdev-shop-elements' ); ?></label>
						<select id="hkdev_rv_filter_default" name="hkdev_rv_filter_default">
							<option value="recent"<?php selected( $val( 'filter_default' ), 'recent' ); ?>><?php esc_html_e( 'Most recent', 'hkdev-shop-elements' ); ?></option>
							<option value="highest"<?php selected( $val( 'filter_default' ), 'highest' ); ?>><?php esc_html_e( 'Highest rating', 'hkdev-shop-elements' ); ?></option>
						</select>
					</div>
					<div class="hkdev-field">
						<label class="hkdev-checkbox">
							<input type="checkbox" name="hkdev_rv_show_product" value="1"<?php echo $checked( 'show_product' ); // phpcs:ignore ?>>
							<?php esc_html_e( 'Show product promo in modals', 'hkdev-shop-elements' ); ?>
						</label>
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_top_pick_label"><?php esc_html_e( 'Top pick ribbon', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_top_pick_label" name="hkdev_rv_top_pick_label" value="<?php echo esc_attr( $val( 'top_pick_label' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_order_button_text"><?php esc_html_e( 'Video modal button', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_order_button_text" name="hkdev_rv_order_button_text" value="<?php echo esc_attr( $val( 'order_button_text' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_view_button_text"><?php esc_html_e( 'Review modal button', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_view_button_text" name="hkdev_rv_view_button_text" value="<?php echo esc_attr( $val( 'view_button_text' ) ); ?>">
					</div>
				</div>
				</div>
				<div class="hkdev-admin-subsection">
					<h3 class="hkdev-admin-subtitle"><?php esc_html_e( 'Modal & empty states', 'hkdev-shop-elements' ); ?></h3>
				<div class="hkdev-settings-grid hkdev-settings-grid-3">
					<div class="hkdev-field">
						<label class="hkdev-checkbox">
							<input type="checkbox" name="hkdev_rv_show_modal_quote" value="1"<?php echo $checked( 'show_modal_quote' ); // phpcs:ignore ?>>
							<?php esc_html_e( 'Show review text in modal', 'hkdev-shop-elements' ); ?>
						</label>
					</div>
					<div class="hkdev-field">
						<label class="hkdev-checkbox">
							<input type="checkbox" name="hkdev_rv_show_modal_badge" value="1"<?php echo $checked( 'show_modal_badge' ); // phpcs:ignore ?>>
							<?php esc_html_e( 'Show badge in modal', 'hkdev-shop-elements' ); ?>
						</label>
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_video_badge"><?php esc_html_e( 'Video badge text', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_video_badge" name="hkdev_rv_video_badge" value="<?php echo esc_attr( $val( 'video_badge' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_proof_badge"><?php esc_html_e( 'Review badge text', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_proof_badge" name="hkdev_rv_proof_badge" value="<?php echo esc_attr( $val( 'proof_badge' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_video_empty"><?php esc_html_e( 'Video empty message', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_video_empty" name="hkdev_rv_video_empty" value="<?php echo esc_attr( $val( 'video_empty' ) ); ?>">
					</div>
					<div class="hkdev-field">
						<label for="hkdev_rv_written_empty"><?php esc_html_e( 'Review empty message', 'hkdev-shop-elements' ); ?></label>
						<input type="text" id="hkdev_rv_written_empty" name="hkdev_rv_written_empty" value="<?php echo esc_attr( $val( 'written_empty' ) ); ?>">
					</div>
				</div>
				</div>
			</div>
		</div>
		<?php
	}
}
