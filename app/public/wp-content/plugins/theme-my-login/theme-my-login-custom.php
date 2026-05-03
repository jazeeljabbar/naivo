<?php 
function add_tml_registration_form_fields() {
	tml_add_form_field( 'register', 'first_name', array(
		'type'     => 'text',
		'label'    => 'First Name',
		'value'    => tml_get_request_value( 'first_name', 'post' ),
		'id'       => 'first_name',
		'priority' => 15,
	) );
	tml_add_form_field( 'register', 'last_name', array(
		'type'     => 'text',
		'label'    => 'Last Name',
		'value'    => tml_get_request_value( 'last_name', 'post' ),
		'id'       => 'last_name',
		'priority' => 15,
	) );
}
add_action( 'init', 'add_tml_registration_form_fields' );

function validate_tml_registration_form_fields( $errors ) {
	if ( empty( $_POST['first_name'] ) ) {
		$errors->add( 'empty_first_name', '<strong>ERROR</strong>: Please enter your first name.' );
	}
	if ( empty( $_POST['last_name'] ) ) {
		$errors->add( 'empty_last_name', '<strong>ERROR</strong>: Please enter your last name.' );
	}
	return $errors;
}
add_filter( 'registration_errors', 'validate_tml_registration_form_fields' );

function save_tml_registration_form_fields( $user_id ) {
	if ( isset( $_POST['first_name'] ) ) {
		update_user_meta( $user_id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
	}
	if ( isset( $_POST['last_name'] ) ) {
		update_user_meta( $user_id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
	}
}
add_action( 'user_register', 'save_tml_registration_form_fields' );
?>