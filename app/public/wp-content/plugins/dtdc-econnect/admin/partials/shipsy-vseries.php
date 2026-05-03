<?php
/**
 * Shipsy virtual series page.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/partials
 */

/** Shipsy virtual series page. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
$response = shipsy_get_virtual_series();
if ( array_key_exists( 'data', $response ) && ! empty( $response['data'] ) ) {
	$virtual_series_array = $response['data'];
	?>
<div id="content">
	<div class="container-fluid">
		<div class="card" style="max-width: 98rem;">
		<div class="card-header">
		<h4 class="card-title"><i class="fa fa-spinner"></i> Virtual Series</h4>
		</div>
	<div class="card-body">
	<div class="table-responsive custom-class">
			<table class="table table-hover"> 
				<thead class="thead-dark">
				<tr> 
					<th scope="col">Service Types</th> 
					<th scope="col">Prefix</th> 
					<th scope="col">Start</th>
					<th scope="col">End</th>
					<th scope="col">Counter</th>
					<th scope="col">Available Count</th>
				</tr> 
				</thead>
				<tbody>
				<?php foreach ( $virtual_series_array as $virtual_series ) : ?>
						<tr scope="row">
							<td><?php echo esc_html( sanitize_text_field( implode( ', ', $virtual_series['serviceType'] ) ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $virtual_series['prefix'] ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $virtual_series['start'] ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $virtual_series['end'] ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $virtual_series['counter'] ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $virtual_series['availableCount'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table> 
		</div>
	<?php
} elseif ( array_key_exists( 'data', $response ) && empty( $response['data'] ) ) {
	?>
		<div class="alert alert-warning" role="alert">No virtual series alloted</div>
	<?php
} elseif ( array_key_exists( 'error', $response ) ) {
	?>
		<div class="alert alert-danger" role="alert"><?php echo esc_html( sanitize_text_field( shipsy_parse_response_error( $response['error'] ) ) ); ?></div>
	<?php
}
?>
