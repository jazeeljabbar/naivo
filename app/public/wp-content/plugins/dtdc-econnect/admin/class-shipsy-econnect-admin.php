<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://shipsy.io/
 * @since      1.0.0
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 * @author     shipsyplugins <pradeep.mishra@shipsy.co.in>
 */

/**
 * TODO: Handle nonce verification.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Shipsy_Econnect_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register CSS for admin area.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		$valid_pages = array( 'shipsy-configuration', 'shipsy-setup', 'shipsy-vseries', 'sync-form', 'manage-form', 'shipsy-sync-result' );
		$page        = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : ''; // phpcs:ignore
		if ( in_array( $page, $valid_pages, true ) ) {
			// adding css files in valid pages.
			wp_enqueue_style( 'ec-bootstrap', SHIPSY_ECONNECT_URL . 'assets/css/bootstrap.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ec-datatable', SHIPSY_ECONNECT_URL . 'assets/css/jquery.dataTables.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ec-sweetalert', SHIPSY_ECONNECT_URL . 'assets/css/sweetalert.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ec-ui-style', SHIPSY_ECONNECT_URL . 'admin/css/ec-ui-style.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ec-overlay-style', SHIPSY_ECONNECT_URL . 'admin/css/ec-overlay-style.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ec-config-style.css', SHIPSY_ECONNECT_URL . 'admin/css/ec-config-style.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';

		$valid_pages = array( 'shipsy-configuration', 'shipsy-setup', 'shipsy-vseries', 'sync-form', 'manage-form', 'shipsy-sync-result' );
		$valid_posts = array( 'shop_order' );
		$page        = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';    // phpcs:ignore
		$post        = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ) : '';  // phpcs:ignore
		if ( in_array( $page, $valid_pages, true ) || in_array( $post, $valid_posts, true ) ) {
			// adding js files in valid pages.
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'ec-bootstrap-js', SHIPSY_ECONNECT_URL . 'assets/js/bootstrap.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'ec-datatable-js', SHIPSY_ECONNECT_URL . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'ec-validate-js', SHIPSY_ECONNECT_URL . 'assets/js/jquery.validate.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'ec-sweetalert-js', SHIPSY_ECONNECT_URL . 'assets/js/sweetalert.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'ec-libphonenumber-js', SHIPSY_ECONNECT_URL . 'assets/js/libphonenumber-js.max.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'shipsy-admin-script', SHIPSY_ECONNECT_URL . 'admin/js/shipsy-econnect-admin.js', array( 'jquery' ), $this->version, false );
			wp_localize_script(
				'shipsy-admin-script',
				'localized_data',
				array(
					'ajaxurl'            => admin_url( 'admin-ajax.php' ),
					'adminurl'           => admin_url( 'admin.php' ),
					'countries-json'     => json_decode( file_get_contents( SHIPSY_ECONNECT_PATH . 'assets/json/countries.json' ) ), // phpcs:ignore
					'consignment-config' => array(
						'origin-country'    => $ORIGIN_COUNTRY, // phpcs:ignore
						'domestic-shipping' => $DOMESTIC,   // phpcs:ignore
					),
				)
			);

		}

	}

	/**
	 * Shipsy order management page.
	 *
	 * @return void
	 */
	public function wpse_73623_render_hidden_page() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-manage.php'; // included template file.
	}

	/**
	 * Shipsy sync order page.
	 *
	 * @return void
	 */
	public function wpse_73622_render_hidden_page() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-sync-form.php'; // included template file.
	}

	/**
	 * Shipsy Plugin's menu configuration.
	 *
	 * @return void
	 */
	public function shipsy_config_menu() {
		// create menu method.

		add_menu_page( 'Shipsy Configuration', 'Shipsy Configuration', 'manage_woocommerce', 'shipsy-configuration', array( $this, 'shipsy_config' ), 'dashicons-admin-site-alt3', 3 );
		// create submenu methods.
		add_submenu_page( 'shipsy-configuration', 'Configuration', 'Configuration', 'manage_woocommerce', 'shipsy-configuration', array( $this, 'shipsy_config' ) );
		add_submenu_page( 'shipsy-configuration', 'Setup', 'Setup', 'manage_woocommerce', 'shipsy-setup', array( $this, 'shipsy_setup' ) );
		add_submenu_page( 'shipsy-configuration', 'Virtual Series', 'Virtual Series', 'manage_woocommerce', 'shipsy-vseries', array( $this, 'shipsy_vseries' ) );
		add_submenu_page( null, null, null, 'manage_woocommerce', 'shipsy-sync-result', array( $this, 'shipsy_sync_result' ) );

		add_submenu_page( null, null, null, 'manage_woocommerce', 'sync-form', array( $this, 'wpse_73622_render_hidden_page' ) );
		add_submenu_page( null, null, null, 'manage_woocommerce', 'manage-form', array( $this, 'wpse_73623_render_hidden_page' ) );
	}
	// menu and submenu callback functions.

	/**
	 * Shipsy Virtual Series page.
	 *
	 * @return void
	 */
	public function shipsy_vseries() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-vseries.php'; // included template file.
	}

	/**
	 * Shipsy setup page.
	 *
	 * @return void
	 */
	public function shipsy_setup() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-setup.php'; // included template file.
	}

	/**
	 * Shipsy configuration page.
	 *
	 * @return void
	 */
	public function shipsy_config() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-config.php'; // included template file.
	}

	/**
	 * Shipsy order sync result page.
	 *
	 * @return void
	 */
	public function shipsy_sync_result() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/partials/shipsy-sync-result.php'; // included template file.
	}

	/**
	 * Internal Endpoint API interceptor.
	 *
	 * @return void
	 */
	public function ajax_endpoint_url_helper() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/apis/shipsy-endpoint-helper-api.php';
	}

	/**
	 * Internal Addresses API interceptor.
	 *
	 * @return void
	 */
	public function ajax_all_addresses_helper() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/apis/shipsy-addresses-helper-api.php';
	}

	/**
	 * Internal Shipping Address API interceptor.
	 *
	 * @return void
	 */
	public function ajax_shipping_address_helper() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/apis/shipsy-shipping-address-helper-api.php';
	}

	/**
	 * Function to download single shipping label.
	 *
	 * @return void
	 */
	public function ajax_shipsy_download_label() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/apis/shipsy-shipping-label-download-api.php';
	}

	/**
	 * Shipsy config submit handler.
	 *
	 * @return void
	 */
	public function shipsy_config_submit() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		$_REQUEST = shipsy_sanitize_array( $_REQUEST ); // phpcs:ignore
		shipsy_config( $_REQUEST ); // phpcs:ignore
	}

	/**
	 * Shipsy settings submit handler.
	 *
	 * @return void
	 */
	public function shipsy_setting_submit() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		$_REQUEST = shipsy_sanitize_array( $_REQUEST ); // phpcs:ignore
		shipsy_settings( $_REQUEST ); // phpcs:ignore
	}

	/**
	 * Shipsy setup submit handler.
	 *
	 * @return void
	 */
	public function shipsy_setup_submit() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		$_REQUEST = shipsy_sanitize_array( $_REQUEST ); // phpcs:ignore
		shipsy_update_addresses( $_REQUEST );   // phpcs:ignore
	}

	/**
	 * Shipsy order sync submit handler.
	 *
	 * @return void
	 */
	public function shipsy_sync_submit() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/apis/shipsy-softdata-upload-api.php';
	}

	/**
	 * Function to add custom actions to woocommerce actions.
	 *
	 * @param mixed $actions The list of actions that are provided by woocommerce.
	 * @param mixed $order  The current order.
	 *
	 * @return mixed
	 */
	public function shipsy_order_action_button( $actions, $order ) {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		$order_id = $order->get_id();
		if ( is_null( shipsy_get_ref_no( $order_id ) ) ) { // order is not synced.
			$action_slug  = 'shipsy_sync';
			$redirect     = array(
				'page'    => 'sync-form',
				'orderid' => $order_id,
			);
			$redirect_url = add_query_arg( $redirect, admin_url( 'admin.php' ) );

			$actions[ $action_slug ] = array(
				'url'    => $redirect_url,
				'name'   => __( 'Sync Order', 'woocommerce' ),
				'action' => $action_slug,
			);
		} else { // order is synced.
			$action_slug = 'shipsy_download_label';

			$params                  = array(
				'ref_no' => shipsy_get_ref_no( $order_id ),
				'action' => $action_slug,
			);
			$redirect_url            = add_query_arg( $params, admin_url( 'admin-ajax.php' ) );
			$actions[ $action_slug ] = array(
				'url'    => $redirect_url,
				'name'   => __( 'Download Label', 'woocommerce' ),
				'action' => $action_slug,
			);
		}
		return $actions;
	}

	/**
	 * Add styles to custom woocommerce order actions.
	 *
	 * @return void
	 */
	public function shipsy_order_action_button_css() {
		$action_slugs = array(
			'shipsy_sync'           => array(
				'content' => '\21BB',
			),
			'shipsy_download_label' => array(
				'content' => '\2193',
			),
		);

		foreach ( $action_slugs as $action_slug => $style ) {
			$style = "
				<style>
					.wc-action-button-$action_slug::after {
						font-family: woocommerce !important;
						content: '{$style['content']}' !important;
					}
				</style>";

			$allowed_html = array( 'style' => array() );
			echo wp_kses( $style, $allowed_html );
		}
	}

	/**
	 * Function to add metabox for consignment status and comment.
	 *
	 * @return void
	 */
	public function shipsy_woocommerce_add_order_metabox() {
		add_meta_box( 'shipsy-sync-comment', __( 'Consignment Status', 'woocommerce' ), array( $this, 'shipsy_woocommerce_sync_status_metabox' ), 'shop_order', 'side', 'core' );
	}

	/**
	 * Function to populate metabox in shop order update page.
	 *
	 * @return void
	 */
	public function shipsy_woocommerce_sync_status_metabox() {
		require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		global $post;

		$order    = wc_get_order( sanitize_text_field( $post->ID ) );
		$order_id = $order->get_id();

		$order_status = 'Not Synced';
		if ( shipsy_check_consignment_synced( $order_id ) ) {
			$consignment  = shipsy_get_synced_consignment( $order_id );
			$order_status = "$consignment->status: $consignment->comment";
		}

		$sync_comment_metabox = "
            <div class='note_content'>
            <p>$order_status</p>
            </div>";

		$allowed_html = array(
			'div' => array(
				'class' => array(),
			),
			'p'   => array(),
		);

		echo wp_kses( $sync_comment_metabox, $allowed_html );
	}

	/**
	 * Function to add custom interval for scheduling cron jobs.
	 *
	 * @param mixed $schedules Array of all the available schedule intervals.
	 * @return mixed
	 */
	public function shipsy_add_cron_interval( $schedules ) {
		$schedules['fifteen_minutes'] = array(
			'interval' => 60 * 15,
			'display'  => __( 'Fifteen minutes', '##text-domain##'),
		);
		return $schedules;
	}

	/**
	 * Function to set up cron jobs.
	 *
	 * @return void
	 */
	public function shipsy_setup_cron() {
		require_once SHIPSY_ECONNECT_PATH . 'admin/crons/shipsy-cron-handler.php';
		shipsy_setup_cron();
	}

	/**
	 * Add style to order status button
	 *
	 * @return void
	 */
	public function shipsy_style_order_status_list() {

		global $pagenow, $post;

		if ( 'edit.php' !== $pagenow ) {
			return; // Exit.
		}
		if ( get_post_type( $post->ID ) !== 'shop_order' ) {
			return; // Exit.
		}

		$custom_status = array(
			'Softdata Upload' => array(
				'background' => '#e5e5e5',
				'color'      => '#777',
			),
			'Not Synced'      => array(
				'background' => '#c4dcf2',
				'color'      => '#416585',
			),
			'Sync Success'    => array(
				'background' => '#c6e1c6',
				'color'      => '#5b841b',
			),
			'Sync Failed'     => array(
				'background' => '#e5adae',
				'color'      => '#6d4546',
			),
		);

		foreach ( $custom_status as $status => $style ) {
			?>
			<style>
				.order-status.status-<?php echo esc_attr( sanitize_title( $status ) ); ?> {
					background: <?php echo esc_attr( $style['background'] ); ?>;
					color: <?php echo esc_attr( $style['color'] ); ?>;
				}
			</style>
			<?php
		}

		require SHIPSY_ECONNECT_PATH . 'config/settings.php';
        // phpcs:ignore
		foreach ( $VALID_CONSIGNMENT_STATUSES as $status => $config ) {
			?>
			<style>
				.order-status.status-<?php echo esc_attr( sanitize_title( $config['status'] ) ); ?> {
					background: <?php echo esc_attr( $config['style']['background'] ); ?>;
					color: <?php echo esc_attr( $config['style']['color'] ); ?>;
				}
			</style>
			<?php
		}

	}

	/**
	 * Function to add bulk actions.
	 *
	 * @param mixed $actions The name of bulk actions in woocommerce.
	 * @return mixed
	 */
	public function track_manage_multiple_order( $actions ) {
		require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		if ( (int) shipsy_get_option( 'enable_auto_sync_option' ) ) {
			$actions['softdata_upload'] = 'Change consignment status to softdata-upload';
		}
		$actions['track_multiple']  = 'Track orders';
		$actions['manage_multiple'] = 'Manage orders';
		$actions['sync_multiple']   = 'Sync orders';
		$actions['label_multiple']  = 'Download labels';

		return $actions;
	}

	/**
	 * Woocommerce bulk action handler.
	 *
	 * @param mixed $redirect_to URL to redirect to.
	 * @param mixed $action The action that was triggered.
	 * @param mixed $post_ids The post ids that were selected when triggering the action.
	 *
	 * @return mixed|string
	 */
	public function multiple_track_manage( $redirect_to, $action, $post_ids ) {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';

		if ( 'manage_multiple' === $action ) {
			$synced_orders   = array();
			$unsynced_orders = array();
			foreach ( $post_ids as $post_id ) {
				$order    = wc_get_order( $post_id );
				$order_number = $order->get_order_number();
				$order_id = $order->get_id();
				if ( ! is_null( shipsy_get_ref_no( $order_number ) ) ) {
					$synced_orders[] = $order_id;
				} else {
					$unsynced_orders[] = $order_id;
				}
			}
			$notifications                  = array();
			$notifications['page']          = 'manage-form';
			$notifications['synced_orders'] = $synced_orders;
			$redirect_to                    = add_query_arg( $notifications, admin_url( 'admin.php' ), $redirect_to );
			return $redirect_to;
		} elseif ( 'track_multiple' === $action ) {
			$success_tracked_orders = array();
			$failed_tracked_orders  = array();
			$warning_tracked_orders = array();
			$message_tracked_orders = array();
			foreach ( $post_ids as $post_id ) {
				$order    = wc_get_order( $post_id );
				$order_number = $order->get_order_number();
				$order_id = $order->get_id();
				if ( ! is_null( shipsy_get_ref_no( $order_number ) ) ) {
					if ( is_null( shipsy_get_tracking_url( $order_number ) ) ) {
						if ( shipsy_add_tracking_url( $order_number ) ) {
							$success_tracked_orders[] = $order_id;
						} else {
							$failed_tracked_orders[] = $order_id;
						}
					} else {
						$message_tracked_orders[] = $order_id;
					}
				} else {
					$warning_tracked_orders[] = $order_id;
				}
			}
			$warning_tracked_orders     = implode( ' , ', $warning_tracked_orders );
			$success_tracked_orders     = implode( ' , ', $success_tracked_orders );
			$failure_tracked_orders     = implode( ' , ', $failed_tracked_orders );
			$message_tracked_orders     = implode( ' , ', $message_tracked_orders );
			$notifications              = array();
			$notifications['post_type'] = 'shop_order';
			if ( ! empty( $message_tracked_orders ) ) {
				$notifications['message'] = "Track URL already generated for order Id $message_tracked_orders";
			}
			if ( ! empty( $warning_tracked_orders ) ) {
				$notifications['warning'] = "Order with order Ids $warning_tracked_orders is not synced.";
			}
			if ( ! empty( $success_tracked_orders ) ) {
				$notifications['success'] = "Success: Added track URL for order Ids $success_tracked_orders";
			}
			if ( ! empty( $failure_tracked_orders ) ) {
				$notifications['failure'] = "Failed to add tracking URL for order with order Id $failure_tracked_orders";
			}
			$redirect_to = add_query_arg( $notifications, admin_url( 'edit.php' ), $redirect_to );
			return $redirect_to;
		} elseif ( 'sync_multiple' === $action ) {
			$synced_orders   = array();
			$unsynced_orders = array();

			foreach ( $post_ids as $post_id ) {
				$order    = wc_get_order( $post_id );
				$order_number = $order->get_order_number();
				$order_id = $order->get_id();
				if ( ! is_null( shipsy_get_ref_no( $order_number ) ) ) {
					$synced_orders[] = $order_id;
				} else {
					$unsynced_orders[] = $order_id;
				}
			}

			$notifications = array();
			$url           = admin_url( 'edit.php' );
			if ( count( $unsynced_orders ) > $SYNC_CONSIGNMENTS_LIMIT ) { // phpcs:ignore
				$notifications['post_type'] = 'shop_order';
				$notifications['failure']   = 'Cannot sync ' . count( $unsynced_orders ) . ' orders. ' .
				                              'Please select less than ' . ( $SYNC_CONSIGNMENTS_LIMIT + 1 ) . ' orders to sync!'; // phpcs:ignore
			} elseif ( count( $unsynced_orders ) > 0 ) {
				$notifications['page']    = 'sync-form';
				$notifications['orderid'] = $unsynced_orders;
				$url                      = admin_url( 'admin.php' );
			} else {
				$notifications['post_type'] = 'shop_order';
			}
			$redirect_to = add_query_arg( $notifications, $url, $redirect_to );
			return $redirect_to;
		} elseif ( 'label_multiple' === $action ) {
			$synced_orders   = array();
			$unsynced_orders = array();
			foreach ( $post_ids as $post_id ) {
				$order    = wc_get_order( $post_id );
				$order_number = $order->get_order_number();
				$order_id = $order->get_id();
				if ( ! is_null( shipsy_get_ref_no( $order_number ) ) ) {
					$synced_orders[] = $order_id;
				} else {
					$unsynced_orders[] = $order_id;
				}
			}

			$notifications = array();
			$url           = admin_url( 'edit.php' );
			if ( count( $synced_orders ) > 0 ) {
				$notifications = shipsy_bulk_label_download( $synced_orders );
			} else {
				$notifications['post_type'] = 'shop_order';
			}
			$redirect_to = add_query_arg( $notifications, $url, $redirect_to );
			return $redirect_to;
		} elseif ( 'softdata_upload' === $action ) {
			$synced_orders   = array();
			$unsynced_orders = array();

			foreach ( $post_ids as $post_id ) {
				$order    = wc_get_order( $post_id );
				$order_number = $order->get_order_number();
				$order_id = $order->get_id();
				if ( ! is_null( shipsy_get_ref_no( $order_number ) ) ) {
					$synced_orders[] = $order_id;
				} else {
					$unsynced_orders[] = $order_id;
				}
			}

			$notifications              = array();
			$url                        = admin_url( 'edit.php' );
			$notifications['post_type'] = 'shop_order';
			if ( count( $unsynced_orders ) > 0 ) {
				$notifications['success'] = 'Successfully changed consignment status for orders ' . implode( ', ', $unsynced_orders );

				foreach ( $unsynced_orders as $order_id ) {
					shipsy_add_consignment_to_sync( $order_id );
				}
			}
			$redirect_to = add_query_arg( $notifications, $url, $redirect_to );
			return $redirect_to;
		} else {
			return $redirect_to;
		}
	}

	/**
	 * Function to add column to woocommerce order table.
	 *
	 * @param mixed $columns Name of the columns that appear in the orders table.
	 * @return mixed
	 */
	public function wc_add_column( $columns ) {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		$columns['sync_order']         = 'Sync Order';
		$columns['consignment_status'] = 'Consignment Status';
		$columns['track_order']        = 'Track Order';
		if ( (int) shipsy_get_option( 'download_label_option' ) ) {
			$columns['download_label'] = 'Download Label';
		}
		return $columns;
	}

	/**
	 * Values to show in order table data.
	 *
	 * @param mixed $column The columns for which the values are being rendered.
	 * @return void
	 */
	public function column_value( $column, $order ) {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		global $post;
		global $wpdb;
		$url      = admin_url( 'admin.php?page=sync-form' );
		$order_number = $order->get_order_number();
		$order_id = $order->get_id();

		$shipsy_ref_no = shipsy_get_ref_no( sanitize_text_field( $order_number ) );
		if ( 'sync_order' === $column ) {
			$url = ( add_query_arg( array( 'orderid' => $order_id ), $url ) );
			if ( ! is_null( $shipsy_ref_no ) ) {
				echo esc_html( shipsy_get_ref_no( $order_number ) );
			} else {
				$sync_link = '<a href="' . esc_url( $url ) . '">Sync Order</a>';

				$allowed_html = array(
					'a' => array(
						'href' => array(),
					),
				);
				echo wp_kses( $sync_link, $allowed_html );
			}
		} if ( 'track_order' === $column ) {
			if ( ! is_null( $shipsy_ref_no ) ) {
				$tracking_url = shipsy_get_tracking_url( sanitize_text_field( $order_number ) );
				if ( ! is_null( $tracking_url ) ) {
					$track_url = '<a href="' . esc_url( $tracking_url ) . '">Tracking Link</a>';

					$allowed_html = array(
						'a' => array(
							'href' => array(),
						),
					);
					echo wp_kses( $track_url, $allowed_html );
				} else {
					esc_html_e( 'Not Tracked', 'shipsy-econnect' );
				}
			} else {
				esc_html_e( 'Not Synced', 'shipsy-econnect' );
			}
		} if ( 'download_label' === $column ) {
			if ( ! is_null( $shipsy_ref_no ) ) {
				$shop_url     = shipsy_get_shop_url();
				$params       = array(
					'ref_no' => shipsy_get_ref_no( $order_number ),
					'action' => 'shipsy_download_label',
				);
				$download_url = add_query_arg( $params, admin_url( 'admin-ajax.php' ) );
				$dl_btn       = '<a id="download-label-' . esc_attr( $shipsy_ref_no ) . '" href="' . esc_url( $download_url ) . '" class="woocommerce-button button blue">Download</button>';

				$allowed_html = array(
					'a' => array(
						'id'    => array(),
						'href'  => array(),
						'class' => array(),
					),
				);
				echo wp_kses( $dl_btn, $allowed_html );
			} else {
				esc_html_e( 'Not Synced', 'shipsy-econnect' );
			}
		} if ( 'consignment_status' === $column ) {
			$consignment        = shipsy_get_synced_consignment( $order_number );
			$consignment_status = 'Not Synced';
			$consignment_class_status = 'Not Synced';

			if ( ! is_null( $consignment ) && ! is_null( $consignment->status ) ) {
				$consignment_status = $consignment->status;
			} elseif ( ! is_null( $shipsy_ref_no ) ) {
				$consignment_status = 'Sync Success';

				// For backward compatibility.
				shipsy_update_synced_consignment(
					$order_number,
					$shipsy_ref_no,
					'Sync Success',
					'Successfully synced.'
				);
			}
			if ($consignment_status==="Sync Success"){
				$consignment_class_status = 'Processing';
			}
			if ($consignment_status==="Sync Failed"){
				$consignment_class_status = 'failed';
			}

			$class_name              = 'status-' . shipsy_slugify( $consignment_class_status );
			$consignment_status_html = "<mark class='order-status $class_name'><span>$consignment_status</span></mark>";
			$allowed_html            = array(
				'mark' => array(
					'class' => array(),
				),
				'span' => array(),
			);

			echo wp_kses( $consignment_status_html, $allowed_html );
		}
	}

	public function column_value_legacy( $column ) {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		global $post;
		global $wpdb;
		$order    = wc_get_order( sanitize_text_field( $post->ID ) );
		$url      = admin_url( 'admin.php?page=sync-form' );
		$order_number = $order->get_order_number();
		$order_id = $order->get_id();

		$shipsy_ref_no = shipsy_get_ref_no( sanitize_text_field( $order_number ) );
		if ( 'sync_order' === $column ) {
			$url = ( add_query_arg( array( 'orderid' => $order_id ), $url ) );
			if ( ! is_null( $shipsy_ref_no ) ) {
				echo esc_html( shipsy_get_ref_no( $order_number ) );
			} else {
				$sync_link = '<a href="' . esc_url( $url ) . '">Sync Order</a>';

				$allowed_html = array(
					'a' => array(
						'href' => array(),
					),
				);
				echo wp_kses( $sync_link, $allowed_html );
			}
		} if ( 'track_order' === $column ) {
			if ( ! is_null( $shipsy_ref_no ) ) {
				$tracking_url = shipsy_get_tracking_url( sanitize_text_field( $order_number ) );
				if ( ! is_null( $tracking_url ) ) {
					$track_url = '<a href="' . esc_url( $tracking_url ) . '">Tracking Link</a>';

					$allowed_html = array(
						'a' => array(
							'href' => array(),
						),
					);
					echo wp_kses( $track_url, $allowed_html );
				} else {
					esc_html_e( 'Not Tracked', 'shipsy-econnect');
				}
			} else {
				esc_html_e( 'Not Synced', 'shipsy-econnect');
			}
		} if ( 'download_label' === $column ) {
			if ( ! is_null( $shipsy_ref_no ) ) {
				$shop_url     = shipsy_get_shop_url();
				$params       = array(
					'ref_no' => shipsy_get_ref_no( $order_number ),
					'action' => 'shipsy_download_label',
				);
				$download_url = add_query_arg( $params, admin_url( 'admin-ajax.php' ) );
				$dl_btn       = '<a id="download-label-' . esc_attr( $shipsy_ref_no ) . '" href="' . esc_url( $download_url ) . '" class="woocommerce-button button blue">Download</button>';

				$allowed_html = array(
					'a' => array(
						'id'    => array(),
						'href'  => array(),
						'class' => array(),
					),
				);
				echo wp_kses( $dl_btn, $allowed_html );
			} else {
				esc_html_e( 'Not Synced', 'shipsy-econnect' );
			}
		} if ( 'consignment_status' === $column ) {
			$consignment        = shipsy_get_synced_consignment( $order_number );
			$consignment_status = 'Not Synced';

			if ( ! is_null( $consignment ) && ! is_null( $consignment->status ) ) {
				$consignment_status = $consignment->status;
			} elseif ( ! is_null( $shipsy_ref_no ) ) {
				$consignment_status = 'Sync Success';

				// For backward compatibility.
				shipsy_update_synced_consignment(
					$order_number,
					$shipsy_ref_no,
					'Sync Success',
					'Successfully synced.'
				);
			}

			$class_name              = 'status-' . shipsy_slugify( $consignment_status );
			$consignment_status_html = "<mark class='order-status $class_name'><span>$consignment_status</span></mark>";
			$allowed_html            = array(
				'mark' => array(
					'class' => array(),
				),
				'span' => array(),
			);

			echo wp_kses( $consignment_status_html, $allowed_html );
		}
	}

	/**
	 * Function to display banner with information at the top of any page.
	 *
	 * @return void
	 */
	public function notice_messages() {
		include_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		$notice = '';
		$get    = shipsy_sanitize_array( $_GET );   // phpcs:ignore
		if ( isset( $get['success'] ) ) {
			$notice = $notice . '
                <div class="notice notice-success is-dismissible">
                    <p>' . $get['success'] . '</p>
                </div>';
		}
		if ( isset( $get['failure'] ) ) {
			$notice = $notice . '
                <div class="notice notice-error is-dismissible">
                    <p>' . $get['failure'] . '</p>
                </div>';
		}
		if ( isset( $get['warning'] ) ) {
			$notice = $notice . '
                <div class="notice notice-warning is-dismissible">
                    <p>' . $get['warning'] . '</p>
                </div>';
		}
		if ( isset( $get['message'] ) ) {
			$notice = $notice . '
                <div class="notice notice-info is-dismissible">
                    <p>' . $get['message'] . '</p>
                </div>';
		}

		$allowed_html = array(
			'div' => array(
				'class' => array(),
			),
			'p'   => array(),
		);
		echo wp_kses( $notice, $allowed_html );
	}
}
