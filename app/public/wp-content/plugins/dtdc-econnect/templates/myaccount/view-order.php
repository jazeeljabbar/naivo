<?php
/**
 * The file to view orders in public end.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 */

/**
 * The file to view orders in public end.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require SHIPSY_ECONNECT_PATH . 'config/settings.php';

$invalid_status      = array( 'Softdata Uploading', 'Sync Failed', 'Not Synced' );
$synced_consignments = array();
foreach ( $tracking_items as $key => $tracking_item ) {
	if ( ! in_array( $tracking_item->status, $invalid_status, true ) ) {
		$synced_consignments[] = $tracking_item;
	}
}

if ( count( $synced_consignments ) > 0 ) : ?>
	<h2 class="woocommerce-order-details__title">Courier details</h2>
	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<thead>
			<tr>
				<th class="courier"><span class="nobr"><?php esc_html_e( 'Courier', 'shipsy-econnect' ); ?></span></th>
				<th class="tracking-number"><span class="nobr"><?php esc_html_e( 'Waybill', 'shipsy-econnect' ); ?></span></th>
				<th class="courier-status"><span class="nobr"><?php esc_html_e( 'Status', 'shipsy-econnect' ); ?></span></th>
				<th class="tracking-url"><?php esc_html_e( 'Actions', 'shipsy-econnect' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $synced_consignments as $key => $tracking_item ) {
				?>
				<tr class="tracking">
					<td class="courier" data-title="<?php esc_attr_e( 'Courier', 'shipsy-econnect' ); ?>">
						<?php echo esc_html( $ORGANISATION ); // phpcs:ignore ?>
					</td>
					<td class="tracking-number" data-title="<?php esc_attr_e( 'Waybill No', 'shipsy-econnect' ); ?>">
						<?php echo esc_html( $tracking_item->shipsy_refno ); ?>
					</td>
					<td class="courier-status" data-title="<?php esc_attr_e( 'Courier Status', 'shipsy-econnect' ); ?>">
						<?php
						if ( 'Sync Success' === $tracking_item->status ) {
							echo esc_html( 'Awaited' );
						} else {
							echo esc_html( $tracking_item->status );
						}
						?>
					</td>
					<?php if ( $tracking_item->track_url ) { ?>
						<td class="tracking-url" style="text-align: center;">
							<a target="_blank" href="<?php echo esc_url( $tracking_item->track_url ); ?>"><button>Track Order</button></a>
						</td>
					<?php } ?>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
endif; ?>
