<?php
namespace HkdevShopElements\Includes;
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Checkout_Options {

    const MENU_SLUG     = 'hkdev-shop-elements';
    const SETTINGS_SLUG = 'hkdev-shop-elements-checkout';

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) { self::$instance = new self(); }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Enqueue admin CSS/JS only on our plugin pages.
     */
    public function enqueue_admin_assets() {
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( ! in_array( $page, [ self::MENU_SLUG, self::SETTINGS_SLUG ], true ) ) { return; }

        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_style(
            'hkdev-elements-admin',
            HKDEV_ELEMENTS_ASSETS_URL . 'css/admin.css',
            [],
            \HkdevShopElements\hkdev_elements_asset_ver( 'assets/css/admin.css' )
        );

        wp_enqueue_script(
            'hkdev-elements-admin',
            HKDEV_ELEMENTS_ASSETS_URL . 'js/admin.js',
            [ 'jquery', 'jquery-ui-sortable' ],
            \HkdevShopElements\hkdev_elements_asset_ver( 'assets/js/admin.js' ),
            true
        );
    }

    /**
     * Register admin menu + submenu.
     */
    public function register_menu() {
        add_menu_page(
            esc_html__( 'HKDEV Shop Elements', 'hkdev-shop-elements' ),
            esc_html__( 'HKDEV Shop', 'hkdev-shop-elements' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_settings_page' ],
            'dashicons-cart',
            62
        );

        // Re-uses the parent slug, which is the WordPress way of relabelling the
        // auto-generated first submenu item instead of adding a duplicate entry.
        add_submenu_page(
            self::MENU_SLUG,
            esc_html__( 'Checkout Fields', 'hkdev-shop-elements' ),
            esc_html__( 'Checkout Fields', 'hkdev-shop-elements' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings + sanitize callback.
     */
    public function register_settings() {
        register_setting(
            'hkdev_elements_checkout_fields_group',
            'hkdev_elements_show_coupon',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'yes',
            ]
        );

        register_setting(
            'hkdev_elements_checkout_fields_group',
            'hkdev_elements_billing_as_delivery',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'no',
            ]
        );

        register_setting(
            'hkdev_elements_checkout_fields_group',
            'hkdev_elements_checkout_modal_scope',
            [
                'type'              => 'string',
                'sanitize_callback' => [ $this, 'sanitize_modal_scope' ],
                'default'           => 'both',
            ]
        );

        register_setting(
            'hkdev_elements_checkout_fields_group',
            \HkdevShopElements\Includes\Core\CheckoutEngine::instance()->config_option_name(),
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_fields' ],
            ]
        );
    }

    /**
     * Field groups rendered as tabs.
     *
     * @return array<string, array<string, string>>
     */
    private function get_groups() {
        return [
            'billing'  => [
                'label'  => __( 'Billing Fields', 'hkdev-shop-elements' ),
                'icon'   => 'dashicons-admin-home',
                'prefix' => 'billing_',
                'hint'   => __( 'Fields shown in the billing section of the checkout form.', 'hkdev-shop-elements' ),
            ],
            'shipping' => [
                'label'  => __( 'Shipping Fields', 'hkdev-shop-elements' ),
                'icon'   => 'dashicons-location',
                'prefix' => 'shipping_',
                'hint'   => __( 'Fields shown in the delivery / shipping section.', 'hkdev-shop-elements' ),
            ],
            'order'    => [
                'label'  => __( 'Order Fields', 'hkdev-shop-elements' ),
                'icon'   => 'dashicons-clipboard',
                'prefix' => '',
                'hint'   => __( 'Extra fields shown in the order notes section.', 'hkdev-shop-elements' ),
            ],
        ];
    }

    /**
     * Sanitize fields + reorder based on saved order.
     */
    public function sanitize_fields( $input ) {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $clean = [];

        foreach ( [ 'billing', 'shipping', 'order' ] as $group ) {
            if ( empty( $input[ $group ] ) || ! is_array( $input[ $group ] ) ) {
                continue;
            }

            // Get the saved order (comma separated keys)
            $order_keys = [];
            if ( ! empty( $input[ $group . '_order' ] ) ) {
                $order_keys = array_filter( array_map( 'sanitize_key', explode( ',', (string) $input[ $group . '_order' ] ) ) );
            }

            // Reorder fields according to saved order
            $sorted = [];
            foreach ( $order_keys as $k ) {
                if ( isset( $input[ $group ][ $k ] ) ) {
                    $sorted[ $k ] = $input[ $group ][ $k ];
                }
            }

            // Append any remaining (newly added) fields at the end
            foreach ( $input[ $group ] as $k => $v ) {
                if ( ! isset( $sorted[ $k ] ) ) {
                    $sorted[ $k ] = $v;
                }
            }

            // Sanitize each field
            $clean[ $group ] = [];
            $i = 0;
            foreach ( $sorted as $key => $field ) {
                $key = sanitize_key( $key );
                $clean[ $group ][ $key ] = [
                    'enabled'     => ( isset( $field['enabled'] ) && 'yes' === $field['enabled'] ) ? 'yes' : 'no',
                    'required'    => ( isset( $field['required'] ) && 'yes' === $field['required'] ) ? 'yes' : 'no',
                    'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
                    'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
                    'order'       => $i,
                ];
                $i++;
            }
        }

        return $clean;
    }

    /**
     * Sanitize the checkout modal scope setting.
     *
     * @param string $value Submitted value.
     * @return string
     */
    public function sanitize_modal_scope( $value ) {
        $allowed = [ 'simple', 'variable', 'both', 'none' ];
        return in_array( $value, $allowed, true ) ? $value : 'both';
    }

    /**
     * Render the fields table for one group.
     *
     * @param string $group        Group key (billing|shipping|order).
     * @param array  $group_config Group config.
     * @param array  $fields       Fields for this group.
     * @param string $option_name  Option name used for the input names.
     */
    private function render_fields_table( $group, $group_config, $fields, $option_name ) {
        ?>
        <table class="wp-list-table widefat fixed striped hkdev-fields-table">
            <thead>
                <tr>
                    <th class="hkdev-col-order" scope="col"><?php esc_html_e( 'Order', 'hkdev-shop-elements' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Field', 'hkdev-shop-elements' ); ?></th>
                    <th class="hkdev-col-toggle" scope="col"><?php esc_html_e( 'Enabled', 'hkdev-shop-elements' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Label', 'hkdev-shop-elements' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Placeholder', 'hkdev-shop-elements' ); ?></th>
                    <th class="hkdev-col-toggle" scope="col"><?php esc_html_e( 'Required', 'hkdev-shop-elements' ); ?></th>
                </tr>
            </thead>
            <tbody class="hkdev-sortable" data-group="<?php echo esc_attr( $group ); ?>">
                <?php $i = 0; foreach ( $fields as $key => $field ) : ?>
                    <tr class="hkdev-field-row" data-key="<?php echo esc_attr( $key ); ?>">
                        <td class="hkdev-col-order">
                            <span class="hkdev-drag-handle dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'hkdev-shop-elements' ); ?>"></span>
                            <span class="hkdev-row-position"><?php echo esc_html( $i + 1 ); ?></span>
                            <input type="hidden" class="hkdev-order-input"
                                   name="<?php echo esc_attr( $option_name . '[' . $group . '][' . $key . '][order]' ); ?>"
                                   value="<?php echo esc_attr( $i ); ?>">
                            <span class="hkdev-row-actions">
                                <button type="button" class="hkdev-row-move hkdev-row-up" title="<?php esc_attr_e( 'Move up', 'hkdev-shop-elements' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
                                <button type="button" class="hkdev-row-move hkdev-row-down" title="<?php esc_attr_e( 'Move down', 'hkdev-shop-elements' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
                            </span>
                        </td>
                        <td class="hkdev-col-field">
                            <span class="hkdev-field-name"><?php echo esc_html( ucwords( str_replace( '_', ' ', str_replace( $group_config['prefix'], '', $key ) ) ) ); ?></span>
                            <span class="hkdev-field-key"><code><?php echo esc_html( $key ); ?></code></span>
                        </td>
                        <td class="hkdev-col-toggle">
                            <label class="hkdev-toggle">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( $option_name . '[' . $group . '][' . $key . '][enabled]' ); ?>"
                                       value="yes" <?php checked( $field['enabled'], 'yes' ); ?>>
                                <span class="hkdev-toggle-slider"></span>
                            </label>
                        </td>
                        <td>
                            <input type="text" class="hkdev-field-input"
                                   name="<?php echo esc_attr( $option_name . '[' . $group . '][' . $key . '][label]' ); ?>"
                                   value="<?php echo esc_attr( $field['label'] ); ?>">
                        </td>
                        <td>
                            <input type="text" class="hkdev-field-input"
                                   name="<?php echo esc_attr( $option_name . '[' . $group . '][' . $key . '][placeholder]' ); ?>"
                                   value="<?php echo esc_attr( $field['placeholder'] ); ?>">
                        </td>
                        <td class="hkdev-col-toggle">
                            <label class="hkdev-toggle">
                                <input type="checkbox"
                                       name="<?php echo esc_attr( $option_name . '[' . $group . '][' . $key . '][required]' ); ?>"
                                       value="yes" <?php checked( $field['required'], 'yes' ); ?>>
                                <span class="hkdev-toggle-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php $i++; endforeach; ?>
            </tbody>
        </table>
        <input type="hidden" id="hkdev-order-<?php echo esc_attr( $group ); ?>"
               name="<?php echo esc_attr( $option_name . '[' . $group . '_order]' ); ?>" value="">
        <?php
    }

    /**
     * Render admin settings page.
     */
    public function render_settings_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'hkdev-shop-elements' ) );
        }

        $engine = \HkdevShopElements\Includes\Core\CheckoutEngine::instance();

        // Handle reset
        if ( isset( $_GET['reset'] ) && '1' === $_GET['reset'] ) {
            check_admin_referer( 'hkdev_elements_reset_fields', 'reset_nonce' );
            delete_option( $engine->config_option_name() );
            delete_option( 'hkdev_elements_show_coupon' );
            delete_option( 'hkdev_elements_billing_as_delivery' );
            delete_option( 'hkdev_elements_checkout_modal_scope' );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . esc_html__( 'Fields reset to defaults.', 'hkdev-shop-elements' )
                . '</p></div>';
        }

        $groups                = $this->get_groups();
        $fields                = $engine->get_field_config();
        $show_coupon           = get_option( 'hkdev_elements_show_coupon', 'yes' );
        $billing_as_delivery   = get_option( 'hkdev_elements_billing_as_delivery', 'no' );
        $checkout_modal_scope  = get_option( 'hkdev_elements_checkout_modal_scope', 'both' );
        $option_name           = $engine->config_option_name();
        $is_first              = true;

        ?>
        <div class="wrap hkdev-admin-wrap hkdev-admin-compact">

            <!-- HEADER -->
            <div class="hkdev-admin-header">
                <div class="hkdev-admin-branding">
                    <span class="hkdev-admin-logo dashicons dashicons-cart"></span>
                    <div class="hkdev-admin-titles">
                        <h1><?php esc_html_e( 'Checkout Fields Manager', 'hkdev-shop-elements' ); ?></h1>
                        <p><?php esc_html_e( 'Control which checkout fields are shown, their labels and their order.', 'hkdev-shop-elements' ); ?></p>
                    </div>
                </div>
                <div class="hkdev-admin-actions">
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&reset=1' ), 'hkdev_elements_reset_fields', 'reset_nonce' ) ); ?>"
                       class="button button-secondary"
                       onclick="return confirm('<?php esc_attr_e( 'Reset all fields to defaults?', 'hkdev-shop-elements' ); ?>')">
                        <span class="dashicons dashicons-image-rotate"></span>
                        <?php esc_html_e( 'Reset', 'hkdev-shop-elements' ); ?>
                    </a>
                </div>
            </div>
            <?php \HkdevShopElements\Includes\Admin\AdminMenu::instance()->render_module_nav( self::MENU_SLUG ); ?>

            <form method="post" action="options.php" id="hkdev-checkout-fields-form">
                <?php settings_fields( 'hkdev_elements_checkout_fields_group' ); ?>

                <!-- ============ GENERAL SETTINGS ============ -->
                <div class="hkdev-admin-card hkdev-general-settings">
                    <div class="hkdev-admin-card-header">
                        <span class="hkdev-admin-card-icon dashicons dashicons-admin-generic"></span>
                        <h2><?php esc_html_e( 'General Settings', 'hkdev-shop-elements' ); ?></h2>
                    </div>
                    <div class="hkdev-admin-card-body">
                        <table class="form-table" role="presentation"><tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Coupon / Voucher', 'hkdev-shop-elements' ); ?></th>
                                <td>
                                    <!-- hidden fallback so an unchecked box is still saved as "no" -->
                                    <input type="hidden" name="hkdev_elements_show_coupon" value="no">
                                    <label class="hkdev-toggle">
                                        <input type="checkbox" name="hkdev_elements_show_coupon" value="yes" <?php checked( 'yes', $show_coupon ); ?>>
                                        <span class="hkdev-toggle-slider"></span>
                                    </label>
                                    <span class="hkdev-toggle-text"><?php esc_html_e( 'Show coupon section on checkout', 'hkdev-shop-elements' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Billing = Delivery', 'hkdev-shop-elements' ); ?></th>
                                <td>
                                    <input type="hidden" name="hkdev_elements_billing_as_delivery" value="no">
                                    <label class="hkdev-toggle">
                                        <input type="checkbox" name="hkdev_elements_billing_as_delivery" value="yes" <?php checked( 'yes', $billing_as_delivery ); ?>>
                                        <span class="hkdev-toggle-slider"></span>
                                    </label>
                                    <span class="hkdev-toggle-text"><?php esc_html_e( 'Use billing address as delivery address', 'hkdev-shop-elements' ); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Checkout Modal', 'hkdev-shop-elements' ); ?></th>
                                <td>
                                    <select name="hkdev_elements_checkout_modal_scope">
                                        <option value="both" <?php selected( 'both', $checkout_modal_scope ); ?>><?php esc_html_e( 'Simple & Variable Products', 'hkdev-shop-elements' ); ?></option>
                                        <option value="simple" <?php selected( 'simple', $checkout_modal_scope ); ?>><?php esc_html_e( 'Simple Products Only', 'hkdev-shop-elements' ); ?></option>
                                        <option value="variable" <?php selected( 'variable', $checkout_modal_scope ); ?>><?php esc_html_e( 'Variable Products Only', 'hkdev-shop-elements' ); ?></option>
                                        <option value="none" <?php selected( 'none', $checkout_modal_scope ); ?>><?php esc_html_e( 'Disabled', 'hkdev-shop-elements' ); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose where the Buy Now checkout modal opens. When disabled, Buy Now redirects to the checkout page.', 'hkdev-shop-elements' ); ?></p>
                                </td>
                            </tr>
                        </tbody></table>
                    </div>
                </div>

                <!-- ============ TABS (pure CSS, JS not required) ============ -->
                <div class="hkdev-tabs-wrap">

                    <?php foreach ( $groups as $group_key => $group ) : ?>
                        <input type="radio" class="hkdev-tab-radio"
                               name="hkdev_active_tab"
                               id="hkdev-tab-radio-<?php echo esc_attr( $group_key ); ?>"
                               value="<?php echo esc_attr( $group_key ); ?>" <?php checked( $is_first ); ?>>
                        <?php $is_first = false; ?>
                    <?php endforeach; ?>

                    <div class="hkdev-tabs" role="tablist">
                        <?php foreach ( $groups as $group_key => $group ) : ?>
                            <label class="hkdev-tab" for="hkdev-tab-radio-<?php echo esc_attr( $group_key ); ?>">
                                <span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>"></span>
                                <span class="hkdev-tab-label"><?php echo esc_html( $group['label'] ); ?></span>
                                <span class="hkdev-tab-count"><?php echo esc_html( count( $fields[ $group_key ] ) ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ( $groups as $group_key => $group ) : ?>
                        <div class="hkdev-tab-content" id="hkdev-tab-<?php echo esc_attr( $group_key ); ?>">
                            <div class="hkdev-admin-card">
                                <div class="hkdev-admin-card-header">
                                    <span class="hkdev-admin-card-icon dashicons <?php echo esc_attr( $group['icon'] ); ?>"></span>
                                    <h2><?php echo esc_html( $group['label'] ); ?></h2>
                                    <p class="hkdev-admin-card-hint"><?php echo esc_html( $group['hint'] ); ?></p>
                                </div>
                                <div class="hkdev-admin-card-body">
                                    <?php $this->render_fields_table( $group_key, $group, $fields[ $group_key ], $option_name ); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <?php submit_button( __( 'Save Changes', 'hkdev-shop-elements' ), 'primary', 'submit', true ); ?>
            </form>
        </div>
        <?php
    }
}
