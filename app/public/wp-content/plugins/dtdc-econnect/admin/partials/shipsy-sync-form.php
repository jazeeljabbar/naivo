<?php
/**
 * Shipsy order sync form page.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/partials
 */

/** Shipsy order sync form page. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

/**
 * TODO: Handle nonce verification.
 */
// phpcs:ignore
if ( ! isset( $_GET['orderid'] ) ) {
	?>
	<div class="alert alert-danger" role="alert"><?php echo esc_html( 'Order id not found!' ); ?></div>
	<?php
	return;
}

// phpcs:ignore
$get       = shipsy_sanitize_array( $_GET );
$order_id  = $get['orderid'];
$order_ids = shipsy_clean_order_ids( $order_id );

$response                      = shipsy_get_addresses();
$all_addresses                 = $response['data'];
$forward_address               = ( array_key_exists( 'forwardAddress', $all_addresses ) ) ? $all_addresses['forwardAddress'] : array();
$reverse_address               = ( array_key_exists( 'reverseAddress', $all_addresses ) ) ? $all_addresses['reverseAddress'] : array();
$exceptional_return_address    = ( array_key_exists( 'exceptionalReturnAddress', $all_addresses ) ) ? $all_addresses['exceptionalReturnAddress'] : array();
$valid_service_types           = $all_addresses['serviceTypes'];
$payment_types                 = ( array_key_exists( 'paymentTypeList', $all_addresses ) ) ? $all_addresses['paymentTypeList'] : array();
$club_multi_pieces_into_single = shipsy_get_option( 'club_multi_pieces_into_single_option' ) === '1' ? true : false;

$form_ids = '';
foreach ( $order_ids as $ord_id ) {
	$form_ids = $form_ids . 'sync-form-' . $ord_id . ' ';
}
$form_ids      = sanitize_text_field( $form_ids );
$order_ids_str = implode( ',', $order_ids );

?>

<div id="sync-form-overlay" class="overlay"></div>
<div id="sync-form-spanner" class="spanner">
	<div class="loader"></div>
	<p>Syncing data, please be patient.</p>
</div>

<div class="container-fluid">
	<div class="pb-2 mt-4 mb-2 border-bottom">
		<h3>Sync Orders</h3>
	</div>
</div>

<script>
	const originW3wFields = [], destinationW3wFields = [], originErrorMessages= [], destinationErrorMessages = [], originSuccessMessages =[], destinationSuccessMessages = [];
</script>

<?php
if ( array_key_exists( 'data', $response ) && ! empty( $response['data'] ) ) {

	foreach ( $order_ids as $idx => $order_id ) {

		$curr_order       = wc_get_order( $order_id );
		$order_number     = $curr_order->get_order_number();
		$customer_notes   = $curr_order->get_customer_note();
		$shipping_address = $curr_order->get_address( 'shipping' );
		$meta_data        = $curr_order->get_meta_data();
		$piece_count      = $club_multi_pieces_into_single ? 1 : count( $curr_order->get_items() );
		?>

<div class="container-fluid">
	<div class="main-container-card" style="font-size: 0.8em; margin-right: 2em">
		<form id="<?php echo esc_attr( 'sync-form-' . $order_id ); ?>" class="form-horizontal">
<!--            <input type="hidden" name="action" value="on_sync_submit"/>-->
			<div class="row">
				<div class="col-12">
					<div class="form-group container-card" id="order-details" style="padding: 2%; margin: 1em 2em">
						<!--                <div class="header-style" style="width : 90% !important">-->
						<!--                    <span class="header-font">Order Details</span>-->
						<!--                </div>-->
						<div class="container">
							<div class="row">
								<div class="col-sm-2">
									<label for="textInput" class="label-font">Order Number<span
												class="required-text">*</span></label>

									<input type="text" required="true"
										value="<?php echo esc_attr( sanitize_text_field( $order_number ) ); ?>"
										id="customer-reference-number" name="customer-reference-number"
										class="form-control" readonly>
									<div class="orderText" style="color : red ; font-size : 10px; display:none"> Order
										Number is required
									</div>
								</div>
								<div class="col-sm-2">
									<label for="textInput" class="label-font">AWB Number</label>
									<input type="text" name="awb-number" id="awb-number" class="form-control" placeholder="Text input">
								</div>
								<div class="col-sm-2">
									<label for="select" class="label-font">Service Type<span
												class="required-text">*</span></label>
									<select class="custom-select" required="true" name="service-type"
											id="select-service-type" style="width: 100%">
										<?php foreach ( $valid_service_types as $service_type ) { ?>
											<option value="<?php echo esc_attr( $service_type['id'] ); ?>"
													<?php selected( $service_type['id'], 'PREMIUM' ); ?>><?php echo esc_html( $service_type['name'] ); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="col-sm-2">
									<label for="select" class="label-font">Courier Type<span
												class="required-text">*</span></label>
									<select class="custom-select" required="true" name="courier-type"
											id="select-courier-type" style="width: 100%">
										<option value="NON-DOCUMENT" selected>NON-DOCUMENT</option>
										<option value="DOCUMENT">DOCUMENT</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label for="select" class="label-font">Consignment Type<span class="required-text">*</span></label>
									<select class=" custom-select" required="true" name="consignment-type"
											id="select-consignment-type" onchange="onConsignmentTypeChangeHandler(<?php echo esc_html( $order_id ); ?>)"
											style="width: 100%">
										<option disabled selected value> -- select consignment type -- </option>
										<option value="forward" selected>FORWARD</option>
										<option value="reverse">REVERSE</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label for="num-pieces" class="label-font">Number of Pieces<span
												class="required-text">*</span></label>
									<input type="number" id="num-pieces" required="true"
										oninput="this.value = Math.abs(this.value)" min="1" pattern="\d+"
										name="num-pieces" class="form-control" value="<?php echo esc_attr( $piece_count ); ?>"
										<?php echo esc_attr( shipsy_get_option( 'enable_multipiece_edit_option' ) === '0' ? 'readonly' : '' ); ?>
										onkeyup="onNumPieceChangeHandler('<?php echo esc_html( $order_id ); ?>')"
									>
									<div class="numpiecesError" style="color : red ; font-size : 10px;display:none">
										Value should be greater than 0
									</div>

									<div class="block form-group" style="margin: 4% 0 0 0; float left">
										<label for="useForwardCheck" style="width: 100%">
											<input type="checkbox" name="multiPieceCheck"  value="true" id="multiPieceCheck"
												onchange="onMultiPieceCheckChangeHandler('<?php echo esc_html( $order_id ); ?>')"
											>
											All pieces same
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-6">
					<div class="form-group col-12 container-card" id="origin-details" style="margin: 1em 2em">
						<h5>Origin Details</h5>
						<div class="container">
							<div class="row">
								<div class="col-sm-4">
									<label for="origin-name" class="label-font">Name<span
												class="required-text">*</span></label>
									<input type="text" id="origin-name" required="true"
										name="origin-name" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['name'] ) ); ?>">
									<div class="origin-name-error" style="color : red ; font-size : 10px;display:none">
										Origin Name is required
									</div>
								</div>
								<div class="col-sm-4">
									<label for="origin-number" class="label-font">Phone Number<span
												class="required-text">*</span></label>
									<input type="tel" id="origin-number" required="true"
										name="origin-number" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['phone'] ) ); ?>">
									<div class="origin-number-error" style="color : red ; font-size : 10px;display:none">
										Phone number is required
									</div>
								</div>
								<div class="col-sm-4">
									<label for="origin-alt-number" class="label-font">Alternate Phone Number</label>
									<input type="tel" id="origin-alt-number"
										name="origin-alt-number" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['alternate_phone'] ) ); ?>">
									<div class="origin-alt-phone-error" style="color : red ; font-size : 10px;display:none">
										Invalid value for Alternate Phone Number
									</div>
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-sm-6">
									<label for="origin-line-1" class="label-font">Address Line 1<span
												class="required-text">*</span></label>
									<input type="text" id="origin-line-1" required="true"
										name="origin-line-1" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['address_line_1'] ) ); ?>">
									<div class="origin-line-1-error" style="color : red ; font-size : 10px;display:none">
										Origin Address is required
									</div>

								</div>
								<div class="col-sm-6">
									<label for="origin-line-2" class="label-font">Address Line 2</label>
									<input type="text" id="origin-line-2"
										name="origin-line-2" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['address_line_2'] ) ); ?>">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-sm-3">
									<label for="origin-city" class="label-font"> City<span
												class="required-text">*</span></label>
									<input type="text" id="origin-city" name="origin-city"
										class="form-control" required="true"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['city'] ) ); ?>">
										<div class="origin-city-error" style="color : red ; font-size : 10px;display:none">
										Origin City is required
									</div>
								</div>
								<div class="col-sm-3">
									<label for="origin-state" class="label-font">State</label>
									<input type="text" id="origin-state" name="origin-state"
										class="form-control" value="<?php echo esc_attr( sanitize_text_field( $forward_address['state'] ) ); ?>">
								</div>
								<div class="col-sm-3">
									<label for="origin-country" class="label-font">Country<span
												class="required-text">*</span></label>
									<input type="text" id="origin-country" required="true"
										name="origin-country" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['country'] ) ); ?>">
									<div class="origin-country-error" style="color : red ; font-size : 10px;display:none">
										Origin Country is required
									</div>

								</div>
								<div class="col-sm-3">
									<label for="origin-pincode">Pincode</label>
									<input type="text" id="origin-pincode"
										name="origin-pincode" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $forward_address['pincode'] ) ); ?>">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
									<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="origin-w3w-number-<?php echo esc_html( $idx ); ?>" id="origin-w3w-number-<?php echo esc_html( $idx ); ?>" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo esc_attr( sanitize_text_field( $forward_address['w3w_code'] ) ); ?>">
									<span id="originErrorMessage-<?php echo esc_html( $idx ); ?>" style="color: red; display: none;"></span>
									<span id="originSuccessMessage-<?php echo esc_html( $idx ); ?>" style="color: green; display: none;"></span>
									<script>
									originW3wFields.push(document.getElementById('origin-w3w-number-<?php echo esc_html( $idx ); ?>'));
									originErrorMessages.push(document.getElementById("originErrorMessage-<?php echo esc_html( $idx ); ?>"));
									originSuccessMessages.push(document.getElementById("originSuccessMessage-<?php echo esc_html( $idx ); ?>"));
									originW3wFields["<?php echo esc_html( $idx ); ?>"].addEventListener('input', function() {
										console.log('originW3wField',originW3wFields["<?php echo esc_html( $idx ); ?>"]);
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = originW3wFields["<?php echo esc_html( $idx ); ?>"].value;
										if (regex.test(inputValue)) {
											originW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '2px solid green';
											originErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
											originSuccessMessages["<?php echo esc_html( $idx ); ?>"].textContent = "Valid Code";
											originSuccessMessages["<?php echo esc_html( $idx ); ?>"].style.display = "block";
										} else {
											originSuccessMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
											if (!(inputValue === "")) {
												originW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '2px solid red';
												originErrorMessages["<?php echo esc_html( $idx ); ?>"].textContent = "Please enter a valid what3word code";
												originErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "block";
											} else {
												originErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
												originW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '1px solid black';
											}
										}
									});

									</script>
								</div>
							</div>

						</div>
					</div>
				</div>
				<div class="col-6">
					<div class="form-group container-card" id="destination-details" style="margin: 1em 2em">
						<h5>Destination Details</h5>
						<div class="container" style="margin-left : 0px">
							<div class="row">
								<div class="col-sm-4">
									<label for="destination-name" class="label-font">Name<span
												class="required-text">*</span></label>
									<input type="text" id="destination-name" required="true" name="destination-name"
										class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $shipping_address['first_name'] . ' ' . $shipping_address['last_name'] ) ); ?>">
									<div class="destination-name-error" style="color : red ; font-size : 10px;display:none">
										Destination Name is required
									</div>
								</div>
								<div class="col-sm-4">
									<label for="destination-number" class="label-font">Phone Number<span
												class="required-text">*</span></label>
									<input type="tel" id="destination-number" required="true"
										name="destination-number" class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $curr_order->get_billing_phone() ) ); ?>">
									<div class="destination-number-error" style="color : red ; font-size : 10px;display:none">
										Phone number is required
									</div>

								</div>
								<div class="col-sm-4">
									<label for="destination-alt-number" class="label-font">Alt Phone Number </label>
									<input type="tel" id="destination-alt-number"
										name="destination-alt-number"
										class="form-control" value="<?php echo esc_attr( sanitize_text_field( $curr_order->get_billing_phone() ) ); ?>">
									<div class="destination-alt-phone-error" style="color : red ; font-size : 10px;display:none">
										Invalid value for Alternate Phone Number
									</div>
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-sm-6">
									<label for="destination-line-1" class="label-font">Address Line 1<span
												class="required-text">*</span></label>
									<input type="text" id="destination-line-1" required="true" name="destination-line-1"
										class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $shipping_address['address_1'] ) ); ?>">
									<div class="destination-line-1-error" style="color : red ; font-size : 10px;display:none">
										Destination Address is required
									</div>

								</div>
								<div class="col-sm-6">
									<label for="destination-line-2" class="label-font">Address Line 2</label>
									<input type="text" id="destination-line-2" name="destination-line-2"
										class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $shipping_address['address_2'] ) ); ?>">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-sm-3">
									<label for="destination-city" class="label-font">City<span
												class="required-text">*</span></label>
									<input type="text" id="destination-city" name="destination-city"
										class="form-control" required="true"
										value="<?php echo esc_attr( sanitize_text_field( $shipping_address['city'] ) ); ?>">
										<div class="destination-city-error" style="color : red ; font-size : 10px;display:none">
										Destination City is required
									</div>
								</div>
								<div class="col-sm-3">
									<label for="destination-state" class="label-font">State</label>
									<input type="text" id="destination-state" name="destination-state"
										class="form-control"
										value="<?php echo esc_attr( sanitize_text_field( $shipping_address['state'] ) ); ?>">
								</div>
								<div class="col-sm-3">
									<label for="destination-country" class="label-font">Country<span
												class="required-text">*</span></label>
									<input type="text" id="destination-country" required="true"
										name="destination-country"
										class="form-control" value="<?php echo esc_attr( sanitize_text_field( $shipping_address['country'] ) ); ?>">
									<div class="destination-country-error" style="color : red ; font-size : 10px;display:none">
										Destination Country is required
									</div>

								</div>
								<div class="col-sm-3">
									<label for="destination-pincode" class="label-font">Pincode</label>
									<input type="text" id="destination-pincode"
										name="destination-pincode"
										class="form-control" value="<?php echo esc_attr( sanitize_text_field( $shipping_address['postcode'] ) ); ?>">
								</div>
							</div>

							<div class="row mt-3">
								<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
									<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="destination-w3w-number-<?php echo esc_html( $idx ); ?>" id="destination-w3w-number-<?php echo esc_html( $idx ); ?>" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo ( is_array( $meta_data ) && count( $meta_data ) > 0 ) ? esc_attr( sanitize_text_field( $meta_data[0]->value ) === 'no' ? '' : sanitize_text_field( $meta_data[0]->value ) ) : ''; ?>">
									<span id="destinationErrorMessage-<?php echo esc_html( $idx ); ?>" style="color: red; display: none;"></span>
									<span id="destinationSuccessMessage-<?php echo esc_html( $idx ); ?>" style="color: green; display: none;"></span>
									<script>
									destinationW3wFields.push(document.getElementById('destination-w3w-number-<?php echo esc_html( $idx ); ?>'));
									destinationErrorMessages.push(document.getElementById("destinationErrorMessage-<?php echo esc_html( $idx ); ?>"));
									destinationSuccessMessages.push(document.getElementById("destinationSuccessMessage-<?php echo esc_html( $idx ); ?>"));
									destinationW3wFields["<?php echo esc_html( $idx ); ?>"].addEventListener('input', function() {
										console.log('destinationW3wField',destinationW3wFields["<?php echo esc_html( $idx ); ?>"]);
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = destinationW3wFields["<?php echo esc_html( $idx ); ?>"].value;
										if (regex.test(inputValue)) {
											destinationW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '2px solid green';
											destinationErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
											destinationSuccessMessages["<?php echo esc_html( $idx ); ?>"].textContent = "Valid Code";
											destinationSuccessMessages["<?php echo esc_html( $idx ); ?>"].style.display = "block";
										} else {
											destinationSuccessMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
											if (!(inputValue === "")) {
												destinationW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '2px solid red';
												destinationErrorMessages["<?php echo esc_html( $idx ); ?>"].textContent = "Please enter a valid what3word code";
												destinationErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "block";
											} else {
												destinationErrorMessages["<?php echo esc_html( $idx ); ?>"].style.display = "none";
												destinationW3wFields["<?php echo esc_html( $idx ); ?>"].style.border = '1px solid black';
											}
										}
									});
									</script>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-6">
					<div class="form-group col-12 container-card" id="payment-details" style="margin: 1em 2em">
						<h5>COD Details</h5>
						<div class="container">
							<?php
									$total_cash_retrieval = 0.0;
									foreach ( $curr_order->get_items() as $key => $item ) {
										if ( ! empty( $item['is_deposit'] ) ) {
											if ( 'cod' === $curr_order->get_payment_method() ) {
												$total_cash_retrieval += (float) $item['_deposit_full_amount_ex_tax'] + $item['total_tax'];
											} else {
												$total_cash_retrieval += max(
													0.0,
													( (float) $item['_deposit_full_amount_ex_tax'] -
													(float) $item['_deposit_deposit_amount_ex_tax'] +
													(float) $item['total_tax'] )
												);
											}
										} elseif ( 'cod' === $curr_order->get_payment_method() ) {
											$total_cash_retrieval += $item['total'] + $item['total_tax'];
										}
									}
									if ( 'cod' === $curr_order->get_payment_method() && $total_cash_retrieval > 0){
										// Add shipping charge
										$total_cash_retrieval += (float)$curr_order->get_shipping_total() + (float)$curr_order->get_shipping_tax();
										// Summing fees associated with the order
										$fees = $curr_order->get_fees(); // Retrieve all fees associated with the order
										foreach ($fees as $fee) {
											$total_fee_amount_with_tax = (float)$fee->get_total();
											$fee_tax = $fee->get_total_tax();
											$total_cash_retrieval += $total_fee_amount_with_tax + $fee_tax;
										}
									}
							?>
							<div class="row">
								<div class="col-12">
									<label for="select" class="label-font">COD Collection Mode <span
												class="required-text">*</span></label>
									<select class="custom-select " name="cod-collection-mode" required="true"
											id="select-cod-collection-mode" style="width: 100%">
										<?php 
										foreach ($payment_types as $payment)
										{
											?> <option value="<?php echo esc_attr($payment); ?>" <?php selected($payment, 'cash'); ?> > <?php echo esc_html( $payment ); ?></option> <?php
										}
										?>
									</select>
								</div>
							</div>

							<div class="row mt-3">
								<div class="col-12">
									<?php
									if ( 'cod' === $curr_order->get_payment_method() ) {
										?>
										<label for="cod-amount" class="label-font">COD Amount <span
													class="required-text">*</span></label>
										<input type="number" value="<?php echo esc_attr( sanitize_text_field( $total_cash_retrieval ) ); ?>" id="cod-amount"
											required="true" name="cod-amount"
											class="form-control   " >
									<?php } else { 
										$readonly = empty($payment_types) ? 'readonly' : '';
										?>
										
										<label for="cod-amount" class="label-font">COD Amount <span
													class="required-text">*</span></label>
										<input type="number" value="<?php echo esc_attr( $total_cash_retrieval ); ?>" id="cod-amount" required="true" name="cod-amount"
											class="form-control   " <?php echo esc_attr( $readonly ) ; ?>>
										<div class="peice-weight-error" style="color : red ; font-size : 10px;display:none">
												COD Amount is Required
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-6">
					<div class="form-group container-card" id="piece-details" style="margin: 1em 2em">
						<h5>Piece Details</h5>
						<?php if ( $club_multi_pieces_into_single ) { ?>
							<div class="container piece-details-div" id="piece-det">
								<?php
								$description    = array();
								$declared_value = 0;
								$total_weight   = 0;
								$order_items    = $curr_order->get_items();
								foreach ( $order_items as $key => $item ) {
									$product         = $item->get_product();
									$description[]   = sanitize_text_field( (int) $item['quantity'] . ' ' . $item['name'] );
									$declared_value += sanitize_text_field( $item['total'] + $item['total_tax'] );
									$total_weight   += sanitize_text_field( $product->get_weight() ? (float) ($product->get_weight() * $item['quantity']) : '1' );
								}
								// Add shipping charge
								$declared_value += (float)$curr_order->get_shipping_total() + (float)$curr_order->get_shipping_tax();
								// Summing fees associated with the order
								$fees = $curr_order->get_fees(); // Retrieve all fees associated with the order
								foreach ($fees as $fee) {
									$total_fee_amount_with_tax = (float)$fee->get_total();
									$fee_tax = $fee->get_total_tax();
									$declared_value += $total_fee_amount_with_tax + $fee_tax;
								}
								?>
								<div class="row mt-3" id="piece-detail-1">
									<div class="row">
										<div class="col-sm-6">
											<label for="textInput" class="label-font">Description<span
														class="required-text">*</span></label>
											<input type="text" name="description[]" required="true" id="description1"
												class="form-control    description-tag"
												value="<?php echo esc_attr( sanitize_text_field( implode( ', ', $description ) ) ); ?>">
											<div class="description1-error" style="color:red; font-size : 10px;display:none">
												Description is required
											</div>
										</div>
										<div class="col-sm-6">

											<label for="textInput" class="label-font">Weight(Kg)<span
														class="required-text">*</span></label>
											<input id="peice-weight"type="number" required="true" name="weight[]" oninput="check(this)"
												step="any" min="0" class="form-control" value="<?php echo esc_html( $total_weight ? $total_weight : '1' ); ?>">
										</div>
									</div>

									<div class="row mt-3">
										<div class="col-sm-2">
											<label for="textInput" class="label-font">Length<span class="required-text">*</span></label>
											<input type="number" name="length[]" required="true" oninput="this.value=Math.abs(this.value)"
												min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_length() ? $product->get_length() : '1' ); ?>">
										</div>
										<div class="col-sm-2">
											<label for="textInput" class="label-font">Breadth<span
														class="required-text">*</span></label>
											<input type="number" name="width[]" required="true" oninput="this.value=Math.abs(this.value)"
												min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_width() ? $product->get_width() : '1' ); ?>">
										</div>
										<div class="col-sm-2">
											<label for="textInput" class="label-font">Height <span
														class="required-text">*</span></label>
											<input type="number" required="true" name="height[]" oninput="this.value=Math.abs(this.value)"
												min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_height() ? $product->get_height() : '1' ); ?>">
										</div>
										<div class="col-sm-6">
											<label for="textInput" class="label-font">Declared Value <span
														class="required-text">*</span></label>
											<input type="number" name="declared-value[]" required="true" min="0" step="any"
												id="declared-value<?php echo esc_html( $order_id ); ?>"
												class="form-control" value="<?php echo esc_attr( sanitize_text_field( $declared_value ) ); ?>">
											<div class="declared-value1<?php echo esc_html( $order_id ); ?>-error" style="color : red ; font-size : 10px;display:none">
												Declared value required
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php } else { ?>
							<div class="container piece-details-div" id="piece-det">
								<?php
								$count       = 0;
								$order_items = $curr_order->get_items();
								foreach ( $order_items as $key => $item ) {
									++$count;
									$declared_value = 0.0;
									$product        = $item->get_product();
									$description    = sanitize_text_field( (int) $item['quantity'] . ' ' . $item['name'] );
									$declared_value = sanitize_text_field( (float) $item['total_tax'] );
									if ( ! empty( $item['is_deposit'] ) ) {
										$declared_value += (float) $item['_deposit_full_amount'];
									} else {
										$declared_value += (float) $item['total'];
									}
									?>
									<div class="row mt-3" id="piece-detail-<?php echo esc_html( $count ); ?>">
										<div class="row">
											<div class="col-sm-6">
												<label for="textInput" class="label-font">Description<span
															class="required-text">*</span></label>
												<input type="text" name="description[]" required="true" id="description<?php echo esc_html( $count ); ?>"
													class="form-control description-tag"
													value="<?php echo esc_attr( sanitize_text_field( $description ) ); ?>">
												<div class="description<?php echo esc_html( $count ); ?>-error" style="color:red; font-size : 10px;display:none">
													Description is required
												</div>
											</div>
											<div class="col-sm-6">
												<label for="textInput" class="label-font">Weight(Kg)<span
															class="required-text">*</span></label>
												<input type="number" required="true" name="weight[]" oninput="check(this)"
													step="any" min="0" class="form-control" value="<?php echo esc_html( $product->get_weight() ? (float) ($product->get_weight() * $item['quantity']) : '1' ); ?>">
												<div class="peice-weight-error" style="color : red ; font-size : 10px;display:none">
													Weight is Required
												</div>
											</div>
										</div>
										<div class="row mt-3">
											<div class="col-sm-2">
												<label for="textInput" class="label-font">Length<span class="required-text">*</span></label>
												<input type="number" name="length[]" required="true" oninput="this.value=Math.abs(this.value)"
													min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_length() ? $product->get_length() : '1' ); ?>">
											</div>
											<div class="col-sm-2">
												<label for="textInput" class="label-font">Breadth<span
															class="required-text">*</span></label>
												<input type="number" name="width[]" required="true" oninput="this.value=Math.abs(this.value)"
													min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_width() ? $product->get_width() : '1' ); ?>">
											</div>
											<div class="col-sm-2">
												<label for="textInput" class="label-font">Height <span
															class="required-text">*</span></label>
												<input type="number" required="true" name="height[]" oninput="this.value=Math.abs(this.value)"
													min="0" step="any" class="form-control" value="<?php echo esc_html( $product->get_height() ? $product->get_height() : '1' ); ?>">
											</div>
											<div class="col-sm-6">
												<label for="textInput" class="label-font">Declared Value <span
															class="required-text">*</span></label>
												<input type="number" name="declared-value[]" required="true" min="0" step="any"
													id="declared-value<?php echo esc_html( $count ); ?>"
													class="form-control" value="<?php echo esc_attr( sanitize_text_field( $declared_value ) ); ?>">
												<div class="declared-value1<?php echo esc_html( $count ); ?>-error" style="color : red ; font-size : 10px;display:none">
													Declared value required
												</div>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<div class="form-group container-card" id="order-details" style="padding: 2%; margin: 1em 2em">
						<!--                        <h5>Order Notes</h5>-->
						<div class="container">
							<div class="row">
								<div class="col-12">
									<label for="textInput" class="label-font">Customer Order Notes</label>

									<textarea required="true"
										id="customer-order-notes" name="notes"
										class="form-control" <?php echo esc_attr( shipsy_get_option( 'enable_customer_order_edit_option' ) === '1' ? '' : 'readonly' ); ?>><?php echo esc_textarea( sanitize_text_field( $customer_notes ) ); ?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<?php } ?>

<div class="container-fluid" style="margin-left: 0; width: 80%">
	<button type="submit" id="softdataSubmitButton" data-toggle="tooltip" title="Save"
			class="btnSubmit btnBlue" onclick="onBulkSyncOrderHandler('<?php echo esc_html( $order_ids_str ); ?>');">Sync</button>
</div>

<?php } elseif ( array_key_exists( 'error', $response ) ) { ?>
	<div class="alert alert-danger" role="alert"><?php echo esc_html( shipsy_parse_response_error( $response['error'] ) ); ?></div>

<?php } else { ?>
	<div class="alert alert-danger"
		role="alert"><?php echo esc_html( $all_addresses['error'] ?? $valid_service_types['error'] ); ?></div>
	<?php
}
?>

<style>
	/* Chrome, Safari, Edge, Opera */
	input::-webkit-outer-spin-button,
	input::-webkit-inner-spin-button {
		-webkit-appearance: none;
		margin: 0;
	}

	/* Firefox */
	input[type=number] {
		-moz-appearance: textfield;
	}
</style>
