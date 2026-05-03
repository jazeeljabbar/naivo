<?php
/**
 * Shipsy plugin config page.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/partials
 */

/** Shipsy plugin config page. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

$service_types = null;

$selected_service_type                      = null;
$saved_service_type                         = null;
$saved_download_label_option                = 0;
$saved_enable_auto_sync_option              = 0;
$saved_enable_auto_status_update_option     = 0;
$saved_enable_multipiece_edit_option        = 0;
$saved_enable_customer_order_edit_option    = 0;
$saved_enable_what3words_code_option        = 0;
$saved_club_multi_pieces_into_single_option = 0;

$access_token = shipsy_get_cookie( 'access_token' );
$org_id       = shipsy_get_cookie( 'org_id' );
$cust_code    = shipsy_get_cookie( 'cust_code' );

if ( ! is_null( $access_token ) && ! is_null( $org_id ) && ! is_null( $cust_code ) ) {
	$response      = shipsy_get_addresses();
	$all_addresses = $response && array_key_exists( 'data', $response ) ? $response['data'] : null;
	$service_types = ! is_null( $all_addresses ) ? $all_addresses['serviceTypes'] : array();

	$saved_download_label_option                = shipsy_get_option( 'download_label_option' );
	$saved_enable_auto_sync_option              = shipsy_get_option( 'enable_auto_sync_option' );
	$saved_enable_auto_status_update_option     = shipsy_get_option( 'enable_auto_status_update_option' );
	$saved_enable_multipiece_edit_option        = shipsy_get_option( 'enable_multipiece_edit_option' );
	$saved_service_type                         = shipsy_get_option( 'auto_sync_service_type' );
	$saved_enable_customer_order_edit_option    = shipsy_get_option( 'enable_customer_order_edit_option' );
	$saved_enable_what3words_code_option        = shipsy_get_option( 'enable_what3words_code_option' );
	$saved_club_multi_pieces_into_single_option = shipsy_get_option( 'club_multi_pieces_into_single_option' );
}

if ( ! is_null( $saved_service_type ) ) {
	foreach ( $service_types as $service_type ) {
		if ( $service_type['id'] === $saved_service_type ) {
			$selected_service_type = $service_type;
		}
	}
} elseif ( ! is_null( $service_types ) ) {
	foreach ( $service_types as $service_type ) {
		if ( 'PREMIUM' === $service_type['id'] ) {
			$selected_service_type = $service_type;
		}
	}

	if ( is_null( $selected_service_type ) ) {
		$selected_service_type = $service_types[0];
	}
} else {
	$service_types = array();
}

?>

<div class="container forms-container">
	<div class="row">
		<div class="col-md-6 config-form">
			<h3>Configure</h3>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data"
			id="config-form">
				<input type="hidden" name="action" value="on_config_submit"/>

				<div class="form-group" style="margin-bottom: 1em">
					<input type="text" class="form-control" required="true" id="forward-name" name="user-name"
						placeholder="Username *" value=""/>
				</div>
				<div class="form-group" style="margin-bottom: 1em">
					<input type="password" class="form-control" id="forward-line-1" required="true" name="password"
						placeholder="Password *" value=""/>
				</div>
				<div class="form-group" style="margin-bottom: 1em">
					<input type="text" class="form-control" placeholder="Organization Id *" required="true"
						id="forward-org-id" name="org_id" value=""/>
				</div>
				<div class="form-group" style="margin-top: 1em">
					<button type="submit" class="btnSubmit" form="config-form" value="Save">Configure</button>
				</div>
			</form>
		</div>

		<div class="col-md-6 settings-form">
			<h3>Settings</h3>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data"
			id="settings-form">
				<input type="hidden" name="action" value="on_setting_submit"/>

				<div class="block" style="margin-top:1rem;">
					<label style="width: 100%">
						<input type="checkbox" name="download_label_option" value="true"
							id="download_label_option" <?php checked( $saved_download_label_option, '1' ); ?>>
						Enable download shipping label in orders.
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="enable_what3words_code_option" value="true"
							id="what3words_code_option" <?php checked( $saved_enable_what3words_code_option, '1' ); ?> >
						Enable what3words address field.
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="club_multi_pieces_into_single_option" value="true"
							id="club_multi_pieces_option" <?php checked( $saved_club_multi_pieces_into_single_option, '1' ); ?> >
						Club Multiple Pieces into Single Piece
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="enable_multipiece_edit_option" value="true"
							id="shipsy-multi-piece-edit" <?php checked( $saved_enable_multipiece_edit_option, '1' ); ?>>
						Enable multi-piece edit option.
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="enable_customer_order_edit_option" value="true"
							id="shipsy-enable-customer-order-edit-option" <?php checked( $saved_enable_customer_order_edit_option, '1' ); ?>>
						Enable edit option for customer order notes.
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="enable_auto_status_update_option" value="true"
							id="shipsy-auto-status-update" <?php checked( $saved_enable_auto_status_update_option, '1' ); ?>>
						Enable auto consignment status update.
					</label>
				</div>

				<div class="block">
					<label style="width: 100%">
						<input type="checkbox" name="enable_auto_sync_option" value="true"
							id="shipsy-enable-auto-sync" <?php checked( $saved_enable_auto_sync_option, '1' ); ?>>
						Enable auto consignment sync.
					</label>
				</div>

				<div class="block"  style="margin-top: 0.5rem;">
					<div class="row">
						<div class="col-5">
							<label for="select" class="label-font">Auto Sync Service Type</label>
						</div>
						<div class="col-7">
							<select class="custom-select" name="auto_sync_service_type"
									id="select-shipsy-auto-sync-service-type" <?php echo esc_attr( $saved_enable_auto_sync_option ? '' : 'disabled' ); ?>
									style="width: 100%">
								<?php foreach ( $service_types as $service_type ) { ?>
									<option
										value="<?php echo esc_attr( $service_type['id'] ); ?>"
										<?php selected( $service_type['id'], $selected_service_type['id'] ); ?>>
											<?php echo esc_html( $service_type['name'] ); ?>
									</option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>

				<div class="form-group" style="margin-top:1rem;">
					<button type="submit" class="btnSubmit" form="settings-form" value="Save">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>
