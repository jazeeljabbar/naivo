<?php
/**
 * Shipsy order sync result page.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/partials
 */

/** Shipsy order sync result page. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

$success = array();
$failed  = array();

/**
 * TODO: Handle nonce verification.
 */
// phpcs:ignore
$req = shipsy_sanitize_array( wp_unslash( $_REQUEST ) );
if ( array_key_exists( 'success', $req ) ) {
	$request = $req['success'];
	foreach ( $request as $res ) {
		$splitted                = explode( ',', $res );
		$success[ $splitted[0] ] = $splitted[1];
	}
}
if ( array_key_exists( 'failed', $req ) ) {
	$request = $req['failed'];
	foreach ( $request as $res ) {
		$splitted               = explode( ',', $res );
		$failed[ $splitted[0] ] = $splitted[1];
	}
}

?>

<?php if ( count( $success ) > 0 || count( $failed ) > 0 ) { ?>

<div class="container-fluid">
	<div class="pb-2 mt-4 mb-2 border-bottom">
		<h3>Sync Result</h3>
	</div>
</div>

<div class="container-fluid">
	<div class="main-container-card" style="margin-right: 2em">
		<?php if ( count( $success ) > 0 ) { ?>
			<div class="container-card" style="border-top: 4px solid #00aa00">
				<h3>Successful Syncs</h3>
				<table class="table">
					<thead>
					<tr>
						<th>Order Id</th>
						<th>Reason</th>
					</tr>
					</thead>

					<tbody>

					<?php
					foreach ( $success as $ord_id => $reason ) {
						?>
						<tr>
							<td><?php echo esc_html( sanitize_text_field( $ord_id ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $reason ) ); ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
			<?php
		}

		if ( count( $success ) > 0 && count( $failed ) > 0 ) {
			?>
			<hr/>
			<?php
		}
		if ( count( $failed ) > 0 ) {
			?>
			<div class="container-card" style="border-top: 4px solid #dd0048">
				<h3>Failed Syncs</h3>
				<table class="table">
					<thead>
					<tr>
						<th>Order Id</th>
						<th>Reason</th>
					</tr>
					</thead>

					<tbody>
					<?php
					foreach ( $failed as $ord_id => $reason ) {
						?>
						<tr>
							<td><?php echo esc_html( sanitize_text_field( $ord_id ) ); ?></td>
							<td><?php echo esc_html( sanitize_text_field( $reason ) ); ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
			</div>
			<?php
		}
		?>
	</div>
</div>
	<?php
} else {
	?>
<div class="container-fluid">
	<div class="alert alert-danger" role="alert" style="margin-top: 2em">
	Something went wrong or no ordered synced!
	</div>
</div>
<?php } ?>
