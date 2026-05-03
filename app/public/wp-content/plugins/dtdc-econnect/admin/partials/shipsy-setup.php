<?php
/**
 * Shipsy setup page.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/partials
 */

/** Shipsy setup page. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
$response = shipsy_get_addresses();
if ( array_key_exists( 'data', $response ) && ! empty( $response['data'] ) ) {
	$all_addresses              = $response['data'];
	$forward_address            = ( array_key_exists( 'forwardAddress', $all_addresses ) ) ? $all_addresses['forwardAddress'] : array();
	$reverse_address            = ( array_key_exists( 'reverseAddress', $all_addresses ) ) ? $all_addresses['reverseAddress'] : array();
	$exceptional_return_address = ( array_key_exists( 'exceptionalReturnAddress', $all_addresses ) ) ? $all_addresses['exceptionalReturnAddress'] : array();
	$return_address             = ( array_key_exists( 'returnAddress', $all_addresses ) ) ? $all_addresses['returnAddress'] : array();

	?>

<div class="container-fluid">
	<div class="pb-2 mt-4 mb-2 border-bottom">
		<h3>Setup</h3>
	</div>
</div>

<div class="container-fluid">
		<div class="main-container-card" style="margin-right: 2em;">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data"
				id="setup-form-module" class="form-horizontal" onsubmit="return setupSubmitValidationInterceptor(this)">
				<input type="hidden" name="action" value="on_setup_submit"/>

				<div class="form-group container-card" id="forward-address">
					<h4>Forward Details</h4>
					<div class="container" style="margin-left : 0px">
						<div class="row">
							<div class="col-sm-4">
								<label for="forward-name" class="label-font">Name <span
										class="required-text">*</span></label>
								<input type="text" required="true" id="forward-name" name="forward-name"
									class="form-control"
									value="<?php echo esc_attr( $forward_address['name'] ?? '' ); ?>" required>
								<div class="nameErrorText" style="color : red ; font-size : 10px;display:none">Name is
									required
								</div>
							</div>
							<div class="col-sm-4">
								<label for="forward-phone" class="label-font">Phone Number <span
									class="required-text">*</span></label>
								<input type="tel" required="true" id="forward-phone"
									name="forward-phone" class="form-control  "
									value="<?php echo esc_attr( $forward_address['phone'] ?? '' ); ?>">
								<div class="forward-phone-error" style="color : red ; font-size : 10px;display:none">Phone
									Number is required
								</div>
							</div>
							<div class="col-sm-4">
								<label for="forward-alt-phone" class="label-font">Alternate Phone Number</label>
								<input type="tel" id="forward-alt-phone"
									name="forward-alt-phone" class="form-control  "
									value="<?php echo esc_attr( $forward_address['alternate_phone'] ?? '' ); ?>">
								<div class="forward-alt-phone-error" style="color : red ; font-size : 10px;display:none">
									Invalid value for Alternate Phone Number
								</div>
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-6">
								<label for="forward-line-1" class="label-font">Address Line 1 <span
							class="required-text">*</span></label>
								<input type="text" required="true" id="forward-line-1" name="forward-line-1"
									class="form-control  " value="<?php echo esc_attr( $forward_address['address_line_1'] ?? '' ); ?>">
								<div class="forward-line-1-error" style="color : red ; font-size : 10px;display:none">
									Address is required
								</div>
							</div>
							<div class="col-sm-6">
								<label for="forward-line-2" class="label-font">Address Line 2</label>
								<input type="text" id="forward-line-2" name="forward-line-2"
									class="form-control  "  value="<?php echo esc_attr( $forward_address['address_line_2'] ?? '' ); ?>">
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-3">
								<label for="forward-city" class="label-font">City</label>
								<input type="text" id="forward-city" name="forward-city"
									class="form-control  " value="<?php echo esc_attr( $forward_address['city'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="forward-state" class="label-font">State</label>
								<input type="text" id="forward-state" name="forward-state"
									class="form-control  " value="<?php echo esc_attr( $forward_address['state'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="forward-country" class="label-font">Country <span
										class="required-text">*</span></label>
								<input type="text" required="true" id="forward-country" name="forward-country"
										class="form-control  " value="<?php echo esc_attr( $forward_address['country'] ?? '' ); ?>">
								<div class="forward-country-error" style="color : red ; font-size : 10px;display:none">
									Country is required
								</div>
							</div>
							<div class="col-sm-3">
								<label for="forward-pincode" class="label-font">Pincode</label>
								<input type="text" id="forward-pincode"
									name="forward-pincode"  class="form-control  "
									value="<?php echo esc_attr( $forward_address['pincode'] ?? '' ); ?>">
							</div>
						</div>

						<div class="row mt-4">
							<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
							<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="forward-w3w-number" id="forward-w3w-number" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo esc_attr( $forward_address['w3w_code'] ?? '' ); ?>">
									<span id="errorMessage" style="color: red; display: none;"></span>
									<span id="successMessage" style="color: green; display: none;"></span>
									<script>
									const myField = document.getElementById('forward-w3w-number');
									const errorMessage = document.getElementById("errorMessage");
									myField.addEventListener('input', function() {
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = myField.value;
										if (regex.test(inputValue)) {
											myField.style.border = '2px solid green';
											errorMessage.style.display = "none";
											successMessage.textContent = "Valid Code";
											successMessage.style.display = "block";
										} else {
											successMessage.style.display = "none";
											if (!(inputValue === "")) {
												myField.style.border = '2px solid red';
												errorMessage.textContent = "Please enter a valid what3word code";
												errorMessage.style.display = "block";
											} else {
												errorMessage.style.display = "none";
												myField.style.border = '1px solid black';
											}
										}
									});
									</script>
								</div>
							</div>
					</div>
				</div>

				<div class="form-group container-card" id="reverse-address">
					<h4>Reverse Details</h4>

					<div class="block form-group" style="margin: 0 0 1em 0; float left">
						<label for="useForwardCheck" style="width: 100%">
							<input type="checkbox"
								name="useForwardCheck" value="true" id="useForwardCheck">
							Forward Address for Reverse.
						</label>
					</div>

					<div class="container-fluid" style="margin-left : 0px">
						<div class="row">
							<div class="col-sm-4">
								<label for="reverse-name" class="label-font">Name <span class="required-text">*</span></label>
								<input type="text" required="true" id="reverse-name" name="reverse-name" class="form-control  "
									value="<?php echo esc_attr( $reverse_address['name'] ?? '' ); ?>">
								<div class="reverse-name-error" style="color : red ; font-size : 10px;display:none">Name is
									required
								</div>
							</div>
							<div class="col-sm-4">

								<label for="reverse-phone" class="label-font">Phone Number <span
									class="required-text">*</span></label>
								<input type="tel" required="true" id="reverse-phone"
									name="reverse-phone" class="form-control  "
									value="<?php echo esc_attr( $reverse_address['phone'] ?? '' ); ?>">
								<div class="reverse-phone-error" style="color : red ; font-size : 10px;display:none">Phone
									number is required
								</div>
							</div>
							<div class="col-sm-4">
								<label for="reverse-alt-phone" class="label-font">Alternate Phone Number</label>
								<input type="tel" id="reverse-alt-phone"
									name="reverse-alt-phone" class="form-control  "
									value="<?php echo esc_attr( $reverse_address['alternate_phone'] ?? '' ); ?>">
								<div class="reverse-alt-phone-error" style="color : red ; font-size : 10px;display:none">
									Invalid value for Alternate Phone Number
								</div>
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-6">
								<label for="reverse-line-1" class="label-font">Address Line 1 <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="reverse-line-1" name="reverse-line-1"
									class="form-control  "
									value="<?php echo esc_attr( $reverse_address['address_line_1'] ?? '' ); ?>">
								<div class="reverse-line-1-error" style="color : red ; font-size : 10px;display:none">
									Address is required
								</div>

							</div>
							<div class="col-sm-6">
								<label for="reverse-line-2" class="label-font">Address Line 2</label>
								<input type="text" id="reverse-line-2" name="reverse-line-2"
									class="form-control  "
									value="<?php echo esc_attr( $reverse_address['address_line_2'] ?? '' ); ?>">
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-3">
								<label for="reverse-city" class="label-font">City</label>
								<input type="text" id="reverse-city" name="reverse-city"
									class="form-control  " value="<?php echo esc_attr( $reverse_address['city'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="reverse-state" class="label-font"> State</label>
								<input type="text" id="reverse-state" name="reverse-state"
									class="form-control  " value="<?php echo esc_attr( $reverse_address['state'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="reverse-country" class="label-font">Country <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="reverse-country" name="reverse-country"
									class="form-control  "
									value="<?php echo esc_attr( $reverse_address['country'] ?? '' ); ?>">
								<div class="reverse-country-error" style="color : red ; font-size : 10px;display:none">
									Country is required
								</div>

							</div>
							<div class="col-sm-3">
								<label for="reverse-pincode" class="label-font"> Pincode</label>
								<input type="number" id="reverse-pincode" name="reverse-pincode"
									class="form-control  " value="<?php echo esc_attr( $reverse_address['pincode'] ?? '' ); ?>">
							</div>
						</div>

						<div class="row mt-4">
							<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
							<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="reverse-w3w-number" id="reverse-w3w-number" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo esc_attr( $reverse_address['w3w_code'] ?? '' ); ?>">
									<span id="errorMessage2" style="color: red; display: none;"></span>
									<span id="successMessage2" style="color: green; display: none;"></span>
									<script>
									const myField2 = document.getElementById('reverse-w3w-number');
									const errorMessage2 = document.getElementById("errorMessage2");
									myField2.addEventListener('input', function() {
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = myField2.value;
										if (regex.test(inputValue)) {
											myField2.style.border = '2px solid green';
											errorMessage2.style.display = "none";
											successMessage2.textContent = "Valid Code";
											successMessage2.style.display = "block";
										} else {
											successMessage2.style.display = "none";
											if (!(inputValue === "")) {
												myField2.style.border = '2px solid red';
												errorMessage2.textContent = "Please enter a valid what3word code";
												errorMessage2.style.display = "block";
											} else {
												errorMessage2.style.display = "none";
												myField2.style.border = '1px solid black';
											}
										}
									});
									</script>
								</div>
							</div>
					</div>
				</div>

				
				<div class="form-group container-card" id="return-address">
					<h4>Return Details</h4>
					<div class="container-fluid" style="margin-left : 0px">
						<div class="row">
							<div class="col-sm-4">
								<label for="return-name" class="label-font">Name <span
										class="required-text">*</span></label>
								<input type="text" required="true" id="return-name" name="return-name"
								class="form-control" value="<?php echo esc_attr( $return_address['name'] ?? '' ); ?>">
								<div class="return-name-error" style="color : red ; font-size : 10px;display:none">Name is
								required
								</div>
							</div>
							<div class="col-sm-4">
								<label for="return-phone" class="label-font">Phone Number <span
									class="required-text">*</span></label>
								<input type="tel" required="true" id="return-phone"
									name="return-phone" class="form-control  "
									value="<?php echo esc_attr( $return_address['phone'] ?? '' ); ?>">
								<div class="return-phone-error" style="color : red ; font-size : 10px;display:none">Phone
									number is required
								</div>

							</div>
							<div class="col-sm-4">
								<label for="return-alt-number" class="label-font">Alternate Phone Number</label>
								<input type="tel" id="return-alt-phone" name="return-alt-phone"
									class="form-control  " value="<?php echo esc_attr( $return_address['alternate_phone'] ?? '' ); ?>">
								<div class="return-alt-phone-error" style="color : red ; font-size : 10px;display:none">
									Invalid value for Alternate Phone Number
								</div>
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-6">
								<label for="return-line-1" class="label-font">Address Line 1 <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="return-line-1" name="return-line-1"
									class="form-control  "
									value="<?php echo esc_attr( $return_address['address_line_1'] ?? '' ); ?>">
								<div class="return-line-1-error" style="color : red ; font-size : 10px;display:none">
									Address is required
								</div>
							</div>
							<div class="col-sm-6">
								<label for="return-line-2" class="label-font">Address Line 2</label>
								<input type="text" id="return-line-2" name="return-line-2"
									class="form-control  "
									value="<?php echo esc_attr( $return_address['address_line_2'] ?? '' ); ?>">
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-3">
								<label for="return-city" class="label-font">City</label>
								<input type="text" id="return-city" name="return-city"
									class="form-control  " value="<?php echo esc_attr( $return_address['city'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="return-state" class="label-font">State</label>
								<input type="text" id="return-state" name="return-state"
									class="form-control  " value="<?php echo esc_attr( $return_address['state'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="return-country" class="label-font">Country <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="return-country" name="return-country"
									class="form-control  "
									value="<?php echo esc_attr( $return_address['country'] ?? '' ); ?>">
								<div class="return-country-error" style="color : red ; font-size : 10px;display:none">
									Country is required
								</div>
							</div>
							<div class="col-sm-3">
								<label for="return-pincode" class="label-font">Pincode</label>
								<input type="text" id="return-pincode" name="return-pincode"
									class="form-control  "  value="<?php echo esc_attr( $return_address['pincode'] ?? '' ); ?>">
							</div>
						</div>

						<div class="row mt-4">
							<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
							<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="return-w3w-number" id="return-w3w-number" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo esc_attr( $return_address['w3w_code'] ?? '' ); ?>">
									<span id="errorMessage3" style="color: red; display: none;"></span>
									<span id="successMessage3" style="color: green; display: none;"></span>
									<script>
									const myField3 = document.getElementById('return-w3w-number');
									const errorMessage3 = document.getElementById("errorMessage3");
									myField3.addEventListener('input', function() {
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = myField3.value;
										if (regex.test(inputValue)) {
											myField3.style.border = '2px solid green';
											errorMessage3.style.display = "none";
											successMessage3.textContent = "Valid Code";
											successMessage3.style.display = "block";
										} else {
											successMessage3.style.display = "none";
											if (!(inputValue === "")) {
												myField3.style.border = '2px solid red';
												errorMessage3.textContent = "Please enter a valid what3word code";
												errorMessage3.style.display = "block";
											} else {
												errorMessage3.style.display = "none";
												myField3.style.border = '1px solid black';
											}
										}
									});
									</script>
								</div>
						</div>
					</div>
				</div>
			
				

				<div class="form-group container-card" id="exp-reverse-address">
					<h4>Exceptional Return Details</h4>
					<div class="container-fluid" style="margin-left : 0px">
						<div class="row">
							<div class="col-sm-4">
								<label for="exp-return-name" class="label-font">Name <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="exp-return-name" name="exp-return-name"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['name'] ?? '' ); ?>">
								<div class="exp-return-name-error" style="color : red ; font-size : 10px;display:none">Name is
									required
								</div>
							</div>
							<div class="col-sm-4">
								<label for="exp-return-phone" class="label-font">Phone Number <span
									class="required-text">*</span></label>
								<input type="tel" required="true" id="exp-return-phone"
									name="exp-return-phone" class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['phone'] ?? '' ); ?>">
								<div class="exp-return-phone-error" style="color : red ; font-size : 10px;display:none">Phone
									number is required
								</div>

							</div>
							<div class="col-sm-4">
								<label for="exp-return-alt-number" class="label-font">Alternate Phone Number</label>
								<input type="tel" id="exp-return-alt-phone"
									name="exp-return-alt-phone" class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['alternate_phone'] ?? '' ); ?>">
								<div class="exp-return-alt-phone-error" style="color : red ; font-size : 10px;display:none">
									Invalid value for Alternate Phone Number
								</div>
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-6">
								<label for="exp-return-line-1" class="label-font">Address Line 1 <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="exp-return-line-1" name="exp-return-line-1"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['address_line_1'] ?? '' ); ?>">
								<div class="exp-return-line-1-error" style="color : red ; font-size : 10px;display:none">
									Address is required
								</div>
							</div>
							<div class="col-sm-6">
								<label for="exp-return-line-2" class="label-font">Address Line 2</label>
								<input type="text" id="exp-return-line-2" name="exp-return-line-2"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['address_line_2'] ?? '' ); ?>">
							</div>
						</div>
						<div class="row mt-4">
							<div class="col-sm-3">
								<label for="exp-return-city" class="label-font">City</label>
								<input type="text" id="exp-return-city" name="exp-return-city"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['city'] ?? '' ); ?>">

							</div>
							<div class="col-sm-3">
								<label for="exp-return-state" class="label-font">State</label>
								<input type="text" id="exp-return-state" name="exp-return-state"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['state'] ?? '' ); ?>">
							</div>
							<div class="col-sm-3">
								<label for="exp-return-country" class="label-font">Country <span
									class="required-text">*</span></label>
								<input type="text" required="true" id="exp-return-country" name="exp-return-country"
									class="form-control  "
									value="<?php echo esc_attr( $exceptional_return_address['country'] ?? '' ); ?>">
								<div class="exp-return-country-error" style="color : red ; font-size : 10px;display:none">
									Country is required
								</div>
							</div>
							<div class="col-sm-3">
								<label for="exp-return-pincode" class="label-font">Pincode</label>
								<input type="text" id="exp-return-pincode" name="exp-return-pincode"
									class="form-control  " value="<?php echo esc_attr( $exceptional_return_address['pincode'] ?? '' ); ?>">
							</div>
						</div>

						<div class="row mt-4">
							<div class="col-sm-3" <?php echo esc_attr( shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? '' : 'hidden' ); ?>>
							<label for="textInput" class="label-font">What3word Code</label>
									<input type="text" name="exp-return-w3w-number" id="exp-return-w3w-number" class="form-control" 
									placeholder="fizzle.clip.quota"
									value="<?php echo esc_attr( $exceptional_return_address['w3w_code'] ?? '' ); ?>">
									<span id="errorMessage4" style="color: red; display: none;"></span>
									<span id="successMessage4" style="color: green; display: none;"></span>
									<script>
									const myField4 = document.getElementById('exp-return-w3w-number');
									const errorMessage4 = document.getElementById("errorMessage4");
									myField4.addEventListener('input', function() {
										const regex = /^\/{0,}[^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}[.｡。･・︒។։။۔።।][^0-9`~!@#$%^&*()+\-_=[{\]}\\|'<,.>?/";:£§º©®\s]{1,}$/;
										const inputValue = myField4.value;
										if (regex.test(inputValue)) {
											myField4.style.border = '2px solid green';
											errorMessage4.style.display = "none";
											successMessage4.textContent = "Valid Code";
											successMessage4.style.display = "block";
										} else {
											successMessage4.style.display = "none";
											if (!(inputValue === "")) {
												myField4.style.border = '2px solid red';
												errorMessage4.textContent = "Please enter a valid what3word code";
												errorMessage4.style.display = "block";
											} else {
												errorMessage4.style.display = "none";
												myField4.style.border = '1px solid black';
											}
										}
									});
									</script>
								</div>
							</div>
					</div>
				</div>
			</form>
		</div>
</div>

<div class="container-fluid" style="margin-left: 0; width: 80%">
	<button type="submit" class="btnSubmit btnBlue"
		name="Submit" id="setupSubmitButton"
		form="setup-form-module"
	>Submit</button>
</div>

	<?php
} elseif ( array_key_exists( 'error', $response ) ) {
	?>
		<div class="alert alert-danger" role="alert"><?php echo esc_html( shipsy_parse_response_error( $response['error'] ) ); ?></div>
	<?php
}
?>
