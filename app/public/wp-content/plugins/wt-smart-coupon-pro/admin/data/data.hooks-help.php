<?php 
/**
 * 	Hooks and example array
 * 	
 * 	@since 2.1.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$hooks_category_labels = array(
	'coupon_style' => __('Coupon style', 'wt-smart-coupons-for-woocommerce-pro'),
	'coupon_clone' => __('Coupon clone', 'wt-smart-coupons-for-woocommerce-pro'),
	'coupon_message' => __('Coupon message', 'wt-smart-coupons-for-woocommerce-pro'),
	'my_account' => __('My account', 'wt-smart-coupons-for-woocommerce-pro'),
	'gift_card' => __('Gift card page', 'wt-smart-coupons-for-woocommerce-pro'),
	'checkout' => __('Checkout page', 'wt-smart-coupons-for-woocommerce-pro'),
	'others' => __('Others', 'wt-smart-coupons-for-woocommerce-pro'),
);
$wf_filters_help_doc_lists=array(
	/**
	 *  Coupon style
	 */
	'coupon_style' => array(
		'wt_sc_alter_coupon_template_html' => array(
			'title' => __('Alter coupon template HTML.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Alter the coupon block HTML before printing.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_sc_alter_coupon_template_html", "wt_sc_customize_coupon_html", 10, 4 );
function wt_sc_customize_coupon_html( $html, $coupon_style, $coupon_type, $coupon ) {

	return \'<div class="wt_sc_single_coupon [wt_sc_single_coupon_class]" data-id="[wt_sc_coupon_id]" title="[wt_sc_single_coupon_title]">
    			<div class="wt_sc_coupon_content wt-coupon-content">
			        <div class="wt-coupon-amount">
			            <span class="wt_sc_coupon_amount amount">[wt_sc_coupon_amount]</span>
			        </div>
			        <div class="wt_sc_coupon_code wt-coupon-code"> 
			            <code>[wt_sc_coupon_code]</code>
			        </div>
    			</div>
		</div>\';
}',
		),
		'wt_sc_alter_coupon_html_placeholder_values' => array(
			'title' => __('Add value to custom placeholder.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('If you want to add any custom dynamic values to the coupon block. Add a placeholder to the coupon template first and assign the value to that placeholder via this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_sc_alter_coupon_html_placeholder_values", "wt_sc_add_value_to_custom_placeholder" );
function wt_sc_add_value_to_custom_placeholder( $find_replace, $coupon, $coupon_type ) {

	// You have to add the [wt_sc_custom_placeholder] in the coupon template first.
	$find_replace["[wt_sc_custom_placeholder]"] = $coupon->get_code();

	return $find_replace;
}',
		),
		'wt_sc_alter_coupon_default_css' => array(
			'title' => __('Alter the coupon default style.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('If you want to customize the style, you can use this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_sc_alter_coupon_default_css", "wt_sc_change_coupon_bg" );
function wt_sc_change_coupon_bg( $coupon_css ) {

	$coupon_css .= ".wt_sc_single_coupon{ background:red !important; }"; // change the background color to red.
	return $coupon_css;
}',
		),
		'wt_sc_alter_coupon_title_text' => array(
			'title' => __('Alter the coupon title.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Alter the coupon title in the coupon block.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_sc_alter_coupon_title_text", "wt_sc_alter_coupon_title_text", 10, 2 );
function wt_sc_alter_coupon_title_text( $label, $coupon ) {
	
	return $coupon->is_type( "wt_sc_bogo" ) ? __( "Buy one get one", "wt-smart-coupons-for-woocommerce-pro" ) : $label; // Change the label in the coupon block when the coupon type is BOGO. 
}',
		),
		'wt_smart_coupon_show_start_date' => array(
			'title' => __('Show start date and time in coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, only the expiry date and time shown in the coupon If it had, this hook would help to show the starting date and time of the coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_show_start_date", "__return_true" );}',
		),
		'wt_sc_alter_coupon_start_expiry_date_text' => array(
			'title' => __('Change the text of the coupon\'s start and expire dates.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, "Start on" and "Expires on" will be the text if a coupon has a starting date and an expiration date, it can be changed by this hook.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_coupon_start_expiry_date_text", "wt_sc_change_coupon_start_expiry_date_text", 10, 3 );

function wt_sc_change_coupon_start_expiry_date_text( $date_text, $date, $type ){
	
	return ("start_date" === $type ? __("Coupon starts on ", "wt-smart-coupons-for-woocommerce-pro") : __("Coupon expires on ", "wt-smart-coupons-for-woocommerce-pro")). esc_html(date_i18n(get_option("date_format", "F j, Y"), $date));
}',
		),
	),

	/**
	 *  Coupon clone
	 */
	'coupon_clone' => array(
		'wt_smart_coupon_meta_no_need_to_clone' => array(
			'title' => __('Skip the meta while duplicating.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Which meta keys to be skipped while cloning.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_smart_coupon_meta_no_need_to_clone", "wt_sc_skip_storecredit_activated_meta_key_while_cloning" );
function wt_sc_skip_storecredit_activated_meta_key_while_cloning( $meta_keys ) {

	$meta_keys[] = "_wt_smart_coupon_credit_activated";
	$meta_keys[] = "_wt_sc_send_the_generated_credit";

	return $meta_keys;
}',
		),
		'wt_smartcoupon_default_duplicate_coupon_status' => array(
			'title' => __('Set the cloned coupon status', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Set the cloned coupon status.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => 'add_filter( "wt_smartcoupon_default_duplicate_coupon_status", "wt_sc_set_cloned_coupon_status_to_draft" );
function wt_sc_set_cloned_coupon_status_to_draft( $status ) {

	return "draft"; // Set the coupon status to draft.
}',
		),
	),

	/**
	 *  Coupon message
	 */
	'coupon_message' => array(
		'wt_smart_coupon_auto_coupon_message' => array(
			'title' => __('Change auto coupon message.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the coupon applied message when the coupon is applied automatically.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_auto_coupon_message", "wt_sc_change_auto_coupon_message", 10, 1 );

function wt_sc_change_auto_coupon_message($coupon){
	
	return __("You got a coupon", "wt-smart-coupons-for-woocommerce-pro") ; 
}',
		),
		'wt_sc_alter_quantity_restriction_messages' => array(
			'title' => __('Change quantity restriction message', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the minimum and maximum quantity validation messages when "Product/Category restrictions" is disabled. If “Individual quantity restriction” is disabled, the message in if($is_global) will appear.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_quantity_restriction_messages", "wt_sc_change_quantity_restriction_messages", 10, 5 );

function wt_sc_change_quantity_restriction_messages( $out, $coupon_code, $qty, $is_global, $type ){
	
	if("min" === $type)
		{
			if($is_global)
			{
				return sprintf(__("Coupon valid for %s items, ensure cart has required quantity to redeem.", "wt-smart-coupons-for-woocommerce-pro"), $qty);
			}else
			{
				return sprintf(__("Coupon requires %s minimum eligible items per product; add more to redeem.", "wt-smart-coupons-for-woocommerce-pro"), $qty);
			}
		}else
		{
			if($is_global)
			{
				return sprintf(__("This coupon can be applied to a maximum of %s eligible products.", "wt-smart-coupons-for-woocommerce-pro"), $qty);
			}else
			{
				return sprintf(__("Each eligible item has a maximum allowable quantity of %s.", "wt-smart-coupons-for-woocommerce-pro"), $qty);
			}
		}
}',
		),
		'wt_smartcoupon_give_away_message' => array(
			'title' => __('Change message for choosing giveaway products', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('When a giveaway product is a variable product, a message with a product variation will show to choose. This hook will help to change the title.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smartcoupon_give_away_message", "wt_sc_change_giveaway_message", 10, 3 );

function wt_sc_change_giveaway_message($message_html, $coupon_code, $coupon_id){
	return \'<h4 class="giveaway-title">\'. __("Congratulations! Choose your freebie:", "wt-smart-coupons-for-woocommerce-pro") .\'<span class="coupon-code">[ \'.$coupon_code.\' ]</span></h4>\';
}',
		),
		'wt_smart_coupon_url_coupon_message' => array(
			'title' => __('Change url coupon message.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the coupon applied message when the coupon is applied by url.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter("wt_smart_coupon_url_coupon_message", "wt_sc_change_url_coupon_message");

function wt_sc_change_url_coupon_message($msg){
	
	if(0 < WC()->cart->get_cart_contents_count())
	{
		return __("You got a coupon","wt-smart-coupons-for-woocommerce-pro");
	}else{
		$shop_page_url  = get_page_link(get_option("woocommerce_shop_page_id"));               
		return sprintf(__("Your cart is empty! Add %sproducts%s to avail the offer.", "wt-smart-coupons-for-woocommerce-pro"), \'<a href="\'.esc_attr($shop_page_url).\'">\', \'</a>\');
	}
}',
		),
	),

	/**
	 *  My account
	 */
	'my_account' => array(
		'wt_sc_alter_myaccount_no_available_coupons_msg' => array(
			'title' => __('Message when no coupons available to show in my account coupons page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the no coupons available message, use this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_myaccount_no_available_coupons_msg", "wt_sc_change_no_coupon_available_msg" );
function wt_sc_change_no_coupon_available_msg( $msg ) {
	
	return __( "No coupons found.", "wt-smart-coupons-for-woocommerce-pro" ); // Add your custom message.
}'
		),
		'wt_sc_alter_myaccount_no_used_coupons_msg' => array(
			'title' => __('Message when no used coupons available to show in my account coupons page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the no used coupons available message, use this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_myaccount_no_used_coupons_msg", "wt_sc_change_no_used_coupon_msg" );
function wt_sc_change_no_used_coupon_msg( $msg ) {
	
	return __( "No used coupons found.", "wt-smart-coupons-for-woocommerce-pro" ); // Add your custom message.
}'
		),
		'wt_sc_alter_myaccount_no_expired_coupons_msg' => array(
			'title' => __('Message when no expired coupons available to show in my account coupons page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('To change the no expired coupons available message, use this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_myaccount_no_expired_coupons_msg", "wt_sc_change_no_expired_coupon_msg" );
function wt_sc_change_no_expired_coupon_msg( $msg ) {
	
	return __( "No expired coupons found.", "wt-smart-coupons-for-woocommerce-pro" ); // Add your custom message.
}'
		),
		'wt_sc_alter_available_coupons_sort_order' => array(
			'title' => __('Change the default sort order for my account coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default the sort order is `Latest last`. You can use this filter to alter the sort order.', 'wt-smart-coupons-for-woocommerce-pro') . '<br />' .
							__('Applicable values: ', 'wt-smart-coupons-for-woocommerce-pro') . 'created_date:desc, created_date:asc, amount:desc, amount:asc',
			'example' => '
add_filter( "wt_sc_alter_available_coupons_sort_order", "wt_sc_change_my_coupons_default_order_latest_first" );
function wt_sc_change_my_coupons_default_order_latest_first( $default_order ) {
	
	return "created_date:desc";
}'
		),
		'wt_sc_my_account_available_coupons_per_page' => array(
			'title' => __('Change my account coupons display count per page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default maximum 20 coupons will display in the my account page. You can change the count by using this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_my_account_available_coupons_per_page", "wt_sc_increase_my_account_coupons_count" );
function wt_sc_increase_my_account_coupons_count( $count ) {

	return 50; // Increase the count to 50
}'
		),
		'wt_sc_my_account_expired_coupons_per_page' => array(
			'title' => __('Change my account expired coupons display count.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default maximum 50 expired coupons will display in the my account page. You can change the count by using this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_my_account_expired_coupons_per_page", "wt_sc_change_my_account_expired_coupons_count" );
function wt_sc_change_my_account_expired_coupons_count( $count ) {

	return 100; // Change the count to 100
}'
		),
		'wt_before_my_store_credit' => array(
			'title' => __('Add a heading before Store credits in my account.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('You can add a custom heading before Store Credits in My Store Credits.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_action( "wt_before_my_store_credit", "wt_sc_add_heading_before_store_credit" );
function wt_sc_add_heading_before_store_credit(){

	echo "<p>".__( "Click coupons to apply", "wt-smart-coupons-for-woocommerce-pro" )."</p>";
}'
		),
		'wt_smart_coupon_before_my_account_coupons' => array(
			'title' => __('Add heading before My coupons in my account.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('You can add a custom heading before my coupons in my account.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_action( "wt_smart_coupon_before_my_account_coupons", "wt_sc_add_heading_before_my_account_coupons" );
function wt_sc_add_heading_before_my_account_coupons(){

	echo "<p>".__( "Click on Available Coupons to apply", "wt-smart-coupons-for-woocommerce-pro" )."</p>";
}'
		),
	), 

	/**
	 *  Gift card
	 */
	'gift_card' => array(
		'wt_smart_coupon_store_credit_date_format' => array(
			'title' => __('Change smart coupon gift card form date format.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Default format is mm/dd/yy. You can change the format by using this filter. Note: Please ensure that the gift card email scheduling works properly after the change.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_store_credit_date_format", "wt_sc_change_store_credit_form_date_format", 10, 2 );
function wt_sc_change_store_credit_form_date_format( $format ) {
	
	return "dd/MM/yy"; // Use valid formats to avoid scheduling errors
}'
		),
		'wt_sc_storecredit_giftcard_amount_allow_decimals' => array(
			'title' => __('Allow decimals as gift card amount.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, only full numbers are allowed as gift card amount. Use this filter to allow decimal values as gift card amount.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_storecredit_giftcard_amount_allow_decimals", "wt_sc_allow_decimals_in_giftcard_amount");
function wt_sc_allow_decimals_in_giftcard_amount( $allow ) {
	
	return true; // True to allow decimals.
}'
		),
	),

	/**
	 *  Checkout page
	 */
	'checkout' => array(
		'wt_sc_checkout_available_coupons_per_page' => array(
			'title' => __('Change checkout coupons display count.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default maximum 20 coupons will display in the checkout page. You can change the count by using this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_checkout_available_coupons_per_page", "wt_sc_increase_checkout_coupons_count" );
function wt_sc_increase_checkout_coupons_count( $count ) {

	return 50; // Increase the count to 50
}'
		),
		'wt_smart_coupon_before_checkout_coupons' => array(
			'title' => __('Add a heading before coupons on the checkout page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('You can add a custom heading before coupons on the checkout page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_action( "wt_smart_coupon_before_checkout_coupons", "wt_sc_add_heading_before_checkout_coupon" );
function wt_sc_add_heading_before_checkout_coupon(){

	echo "<p>".__( "Click coupons to apply", "wt-smart-coupons-for-woocommerce-pro" )."</p>";
}'
		),
		'wt_smart_coupon_enable_gift_coupon_form' => array(
			'title' => __('Disable the gift coupon form on the checkout page.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('If a gift coupon is added to a product, a form will appear at checkout if the product is in the cart, asking whether to get the coupon by yourself or gift it to someone. This hook helps to disable the form and send coupons to those who make purchases.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_enable_gift_coupon_form", "__return_false" );'
		),
	),


	/**
	 *  Others
	 */
	'others'=>array(
		'wt_allowed_characters_for_random_coupon' => array(
			'title' => __('Alter the characters allowed in coupon code.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('Alter the characters allowed when generating random coupon code.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_allowed_characters_for_random_coupon", "wt_sc_only_alphabets_in_coupon_code" );

function wt_sc_only_alphabets_in_coupon_code( $allowed_chars ) {
	
	return "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; // alphabet characters
}'
		),
		'wt_smart_coupon_import_delimiter' => array(
			'title' => __('Change the default import delimiter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('If the imported file have a delimiter other than comma. Then use this filter to alter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_import_delimiter", "wt_sc_change_delimiter_to_semi_colon" );
function wt_sc_change_delimiter_to_semi_colon( $delimiter ) {

	return ";";
}'
		),
		'wt_sc_cart_available_coupons_per_page' => array(
			'title' => __('Change cart coupons display count.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default maximum 20 coupons will display in the cart page. You can change the count by using this filter.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_cart_available_coupons_per_page", "wt_sc_increase_cart_coupons_count" );
function wt_sc_increase_cart_coupons_count( $count ) {

	return 50; // Increase the count to 50
}'
		),
		'wt_smartcoupon_max_auto_coupons_limit' => array(
			'title' => __('Change auto coupon apply count.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, a maximum of 5 coupons will be applied automatically. You can change the count by using this filter. Note: Setting a high number may affect the performance of the website.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smartcoupon_max_auto_coupons_limit", "wt_sc_increase_auto_coupons_limit" );
function wt_sc_increase_auto_coupons_limit( $count ) {

	return 10; // Increase the count to 10
}'
		),
		'wt_sc_alter_abandonment_email_button_style' => array(
			'title' => __('Change "Go to your cart" button style in Abandonment email.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('A "Go to your cart" button will be in the abandonment cart mail. The user can redirect to the cart from the mail by clicking this button.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_alter_abandonment_email_button_style", "wt_sc_change_abandonment_email_button_style", 10, 2 );
function wt_sc_change_abandonment_email_button_style( $style, $coupon ){
	
	return "background:#99ff99; border:none; color:#000; text-decoration:none; padding:10px; text-align:center; font-weight: bold;";
}'
		),
		'wt_sc_enable_pagination_in_user_available_coupons' => array(
			'title' => __('Remove pagination after available coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, a next and prev button will be available after the available coupons. The count of available coupons will be basically 20, but it can be changed with the help of hooks.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_sc_enable_pagination_in_user_available_coupons", "__return_false" );'
		),
		'wt_smart_coupon_delete_store_credit_after_use' => array(
			'title' => __('Delete fully used store credit coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
			'description' => __('By default, a fully used store credit is shown in the Used Credits section. By using this code, you can delete a fully used store credit.', 'wt-smart-coupons-for-woocommerce-pro'),
			'example' => '
add_filter( "wt_smart_coupon_delete_store_credit_after_use", "__return_true" );'
		),
		
	),
);