<?php
/**
 * Request a feature
 *
 * @link
 * @since 2.1.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functionality of request feature module.
 */
class Wt_Smart_Coupon_Request_feature {

	/**
	 *  Module base
	 *
	 *  @var string $module_base module base
	 */
	public $module_base = 'request_feature';

	/**
	 *  Module id
	 *
	 *  @var string $module_id module id
	 */
	public $module_id = '';

	/**
	 *  Module static id
	 *
	 *  @var string $module_id_static module static id
	 */
	public static $module_id_static = '';

	/**
	 *  Class instance
	 *
	 *  @var object $instance class instance
	 */
	private static $instance = null;

	/**
	 *  End point
	 *
	 *  @var string $end_point Request feature submit endpoint
	 */
	private $end_point = 'https://feedback.webtoffee.com/wp-json/feature-suggestion/v1';

	/**
	 * Constructor function of the class
	 * Add Request feature button on settings screen
	 */
	public function __construct() {
		/**
		 *  Add request a feature button on top of settings screen
		 *
		 *  @since 2.1.0
		 */
		add_action( 'wt_sc_plugin_before_settings_tab', array( $this, 'add_feature_btn' ) );

		/**
		 *  Add CSS/JS for button and popup form
		 *
		 *  @since 2.1.0
		 */
		add_action( 'admin_print_scripts', array( $this, 'add_feature_btn_js_css' ), 30 );

		/**
		 *  Ajax callback to send the suggestion
		 *
		 *  @since 2.1.0
		 */
		add_action( 'wp_ajax_wt_sc_request_a_feature', array( $this, 'send_suggestion' ) );
	}

	/**
	 *  Get Instance
	 *
	 *  @since 2.1.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wt_Smart_Coupon_Request_feature();
		}
		return self::$instance;
	}


	/**
	 *  Is show button
	 *
	 *  @since 2.1.0
	 *  @since 3.0.0 - Added BOGO page in condition check.
	 *  @return bool
	 */
	public function is_show_request_feature() {
		return isset( $_GET['page'] ) && ( WT_SC_PLUGIN_NAME === $_GET['page'] || WT_SC_PLUGIN_NAME . '_bogo' === $_GET['page'] );
	}


	/**
	 *  Send ajax form data to WebToffee server
	 *  Hooked into: wp_ajax_wt_sc_request_a_feature
	 *
	 *  @since 2.1.0
	 */
	public function send_suggestion() {
		$out = array(
			'status' => false,
			'msg'    => __( 'Error', 'wt-smart-coupons-for-woocommerce-pro' ),
		);

		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );

		if ( '' !== $nonce && wp_verify_nonce( $nonce, WT_SC_PLUGIN_NAME ) ) {
			$er_msg         = '';
			$msg            = isset( $_POST['wt_sc_request_a_feature_msg'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wt_sc_request_a_feature_msg'] ) ) : '';
			$take_email     = isset( $_POST['wt_sc_request_a_feature_take_email'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_sc_request_a_feature_take_email'] ) ) : 'no';
			$email          = isset( $_POST['wt_sc_request_a_feature_email'] ) ? sanitize_email( wp_unslash( $_POST['wt_sc_request_a_feature_email'] ) ) : '';
			$additional_msg = isset( $_POST['wt_sc_request_a_feature_business_purpose'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wt_sc_request_a_feature_business_purpose'] ) ) : '';

			if ( '' === $msg ) {
				$er_msg = esc_html__( 'Please enter your message.', 'wt-smart-coupons-for-woocommerce-pro' );
			}

			if ( '' === $er_msg && 'yes' === $take_email && '' === $email ) {
				$er_msg = esc_html__( 'We need your email address to contact you back.', 'wt-smart-coupons-for-woocommerce-pro' );
			}

			if ( '' === $er_msg ) {

				$data = array(
					'msg'            => $msg, // message from user.
					'user_email'     => $email, // user email, if given.
					'additional_msg' => $additional_msg, // additional message from user @since 3.2.0 .
					'plugin_version' => WEBTOFFEE_SMARTCOUPON_VERSION,
					'plugin'         => 'smart_coupon',
				);

				// Write an action/hook here in webtoffe to recieve the data.
				$resp = wp_remote_retrieve_body(
					wp_remote_post(
						$this->end_point,
						array(
							'method'      => 'POST',
							'timeout'     => 45,
							'redirection' => 5,
							'httpversion' => '1.0',
							'blocking'    => false,
							'body'        => $data,
							'cookies'     => array(),
						)
					)
				);

				if ( ! is_wp_error( $resp ) ) {
					$out['status'] = true;
					$out['msg']    = __( 'Success', 'wt-smart-coupons-for-woocommerce-pro' );
				}
			} else {
				$out['msg'] = $er_msg;
			}
		}

		echo wp_json_encode( $out );
		exit();
	}

	/**
	 *  Add the button on settings screen.
	 *  Hooked into: wt_sc_plugin_before_settings_tab
	 *
	 *  @since 2.1.0
	 */
	public function add_feature_btn() {
		if ( $this->is_show_request_feature() ) {
			$this->add_feature_request_form();
			?>

			<!-- Button -->
			<button class="wt_sc_request_a_feature_btn button-secondary" data-wt_sc_popup="wt_sc_request_a_feature_popup">
				<?php esc_html_e( 'Request a feature', 'wt-smart-coupons-for-woocommerce-pro' ); ?>        
			</button>
			<?php
		}
	}


	/**
	 *  Add CSS and JS for form and button
	 *  Hooked into: admin_print_scripts
	 *
	 *  @since 2.1.0
	 */
	public function add_feature_btn_js_css() {
		if ( $this->is_show_request_feature() ) {

			?>
			<style type="text/css">
				button.wt_sc_request_a_feature_btn{ float:right; color:#3157a6; border:solid 1px #3157a6; }
				.wt_sc_request_a_feature_popup{ background:#f5fafe; border-radius:10px; }
				.wt_sc_request_a_feature_popup .wt_sc_popup_hd{ height:auto; background:transparent; }
				.wt_sc_request_a_feature_popup .wt_sc_popup_title{ width:100%; text-align:center; color:#32373E; font-size:18px; padding-top:40px; padding-bottom:10px; font-weight:600; line-height:30px; }
				.wt_sc_request_a_feature_popup .wt_sc_popup_title_caption{ width:100%; text-align:center; color:#32373E; font-size:14px; padding-top:0px; font-weight:400; line-height:14px; }
				.wt_sc_request_a_feature_popup .wt_sc_popup_close{ position:absolute; width:20px; height:20px; line-height:20px; text-align:center; margin-top:15px; right:10px; top:0px; background:none; color:#32373e; border:solid 2px #32373e; border-radius:20px; }
				.wt_sc_request_a_feature_popup .wt_sc_popup_body{text-align:left; padding:10px 30px; padding-bottom:30px;}
				.wt_sc_request_a_feature_popup .form_label, .wt_sc_request_a_feature_popup .form_label_caption{ display:block; color:#32373E; font-size:14px; width:100%;}
				.wt_sc_request_a_feature_popup .form_label_caption{ color:#6d7277;}
				.wt_sc_request_a_feature_popup .form_label{ font-weight:600; margin-top:20px;}
				.wt_sc_request_a_feature_popup textarea{ border-radius:6px; background:#fff; border:solid 1px #C7CBD1; width:100%; height:75px; margin-top:5px; }
				input.wt_sc_request_a_feature_input{ border-radius:6px; background:#fff; border:solid 1px #C7CBD1; width:100%; max-width:400px; height:40px; margin-top:5px; }
				.wt_sc_request_a_feature_popup form ::placeholder{ color:#C7CBD1; font-size:13px; }
				.wt_sc_request_a_feature_checkbox_container, .wt_sc_request_a_feature_email_container, .wt_sc_request_a_feature_btn_box{ width:100%; margin-top:7px; color:#32373E; }
				.wt_sc_request_a_feature_checkbox_container{ display:flex; align-items:end; }
				.wt_sc_request_a_feature_email_container{ display:none; }
				.wt_sc_request_a_feature_btn_box{ margin-top:25px; padding-bottom:30px; }
				.wt_sc_request_a_feature_btn_box button{ float:right; height:40px; font-size:14px; padding:0px 30px !important; border:solid 1px #3157a6 !important; }
				.wt_sc_request_a_feature_btn_box button.button-secondary{ background:none; color:#3157a6; }
				.wt_sc_request_a_feature_btn_box button.button-primary{ background:#3157a6; margin-left:10px !important; margin-bottom:5px; }
			</style>

			<script type="text/javascript">
				
				jQuery(document).ready(function(){ 
					
					/* Email field toggling */
					jQuery('#wt_sc_request_a_feature_take_email').on('click', function(){
						if(jQuery(this).is(':checked'))
						{
							jQuery('.wt_sc_request_a_feature_email_container').slideDown('fast');
							jQuery('[name="wt_sc_request_a_feature_email"]').trigger('focus');
						}else{
							jQuery('.wt_sc_request_a_feature_email_container').slideUp('fast');
						}
					});

					/* Reset the form and hide the email field on page load */
					jQuery('.wt_sc_request_a_feature_btn').on('click', function(){
						jQuery('.wt_sc_request_a_feature_popup form')[0].reset();
						jQuery('.wt_sc_request_a_feature_email_container').hide();
					});

					/* form submit */
					jQuery('.wt_sc_request_a_feature_popup form').on('submit', function(e){
						
						e.preventDefault();

						/* Validation */
						if("" === jQuery('[name="wt_sc_request_a_feature_msg"]').val().trim())
						{
							wt_sc_notify_msg.error('<?php esc_html_e( 'Please enter your message.', 'wt-smart-coupons-for-woocommerce-pro' ); ?>', false);
							jQuery('[name="wt_sc_request_a_feature_msg"]').trigger('focus');
							return false;
						}

						if(jQuery('#wt_sc_request_a_feature_take_email').is(':checked') && "" === jQuery('[name="wt_sc_request_a_feature_email"]').val().trim())
						{
							wt_sc_notify_msg.error('<?php esc_html_e( 'We need your email address to contact you back.', 'wt-smart-coupons-for-woocommerce-pro' ); ?>', false);
							jQuery('[name="wt_sc_request_a_feature_email"]').trigger('focus');
							return false;
						}


						/* Ajax request */
						var btn_html_bckup = jQuery('[name="wt_sc_request_a_feature_sbmt_btn"]').text();
						jQuery('[name="wt_sc_request_a_feature_sbmt_btn"]').prop({'disabled': true}).text('<?php esc_html_e( 'Sending...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>');

						jQuery.ajax({
							url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
							type: 'POST',
							data: jQuery(this).serialize(),
							dataType: 'json',
							success: function(data)
							{
								if(data.status)
								{
									wt_sc_notify_msg.success('<?php esc_html_e( 'Thank you for you valuable suggestion.', 'wt-smart-coupons-for-woocommerce-pro' ); ?>'); 
									wt_sc_popup.hidePopup();

								}else{
									wt_sc_notify_msg.error(data.msg, true);
								}
							},
							error: function()
							{
								wt_sc_notify_msg.error('<?php esc_html_e( 'Unable to submit your suggestion. Please try again later.', 'wt-smart-coupons-for-woocommerce-pro' ); ?>', false); 
							},
							complete: function()
							{
								jQuery('[name="wt_sc_request_a_feature_sbmt_btn"]').prop({'disabled': false}).text(btn_html_bckup);
							}
						});

					});


					/* Cancel button */
					jQuery('[name="wt_sc_request_a_feature_cancel_btn"]').on('click', function(){
						wt_sc_popup.hidePopup();
					});

				});
			</script>
			<?php
		}
	}

	/**
	 * Request feature form moved to new function.
	 *
	 * @since 3.0.0
	 */
	public function add_feature_request_form() {

		if ( $this->is_show_request_feature() ) {
			?>

			<!-- Popup form -->
			<div class="wt_sc_request_a_feature_popup wt_sc_popup" style="width:95%; max-width:722px;">
					
				<!-- Popup head -->
				<div class="wt_sc_popup_hd">
					<div class="wt_sc_popup_title">
						<?php esc_html_e( 'Missing a feature?', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
						<div class="wt_sc_popup_title_caption"><?php esc_html_e( 'Drop a message to let us know!', 'wt-smart-coupons-for-woocommerce-pro' ); ?></div>     
					</div>
					<div class="wt_sc_popup_close">X</div>
				</div>
				
				<!-- Popup body -->
				<div class="wt_sc_popup_body">
					<form method="post">
						
						<!-- Nonce and action fields -->
						<?php wp_nonce_field( WT_SC_PLUGIN_NAME ); ?>
						<input type="hidden" name="action" value="wt_sc_request_a_feature">
						
						<!-- Message field -->
						<label class="form_label"><?php esc_html_e( 'What feature would you like to see added?', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
						<span class="form_label_caption"><?php esc_html_e( 'The more details you share, the better.', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
						<textarea name="wt_sc_request_a_feature_msg" placeholder="<?php esc_attr_e( 'I would like...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"></textarea>

						<label class="form_label"><?php esc_html_e( 'What specific challenges or goals would this feature address for your business?', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
						<span class="form_label_caption"><?php esc_html_e( 'Help us create features that matter most to you!', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
						<textarea name="wt_sc_request_a_feature_business_purpose" placeholder="<?php esc_attr_e( 'This feature would help...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"></textarea>

						<div class="wt_sc_request_a_feature_checkbox_container">
							<input type="checkbox" name="wt_sc_request_a_feature_take_email" id="wt_sc_request_a_feature_take_email" value="yes"> <label for="wt_sc_request_a_feature_take_email"><?php esc_html_e( 'Webtoffee can contact me about this feedback.', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
						</div>

						<!-- Email field -->
						<div class="wt_sc_request_a_feature_email_container">
							<label class="form_label"><?php esc_html_e( 'Enter your email address.', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
							<input type="email" name="wt_sc_request_a_feature_email" class="wt_sc_request_a_feature_input" placeholder="<?php esc_attr_e( 'Enter email address', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
						</div>

						<!-- Buttons -->
						<div class="wt_sc_request_a_feature_btn_box">
							<button type="submit" name="wt_sc_request_a_feature_sbmt_btn" class="button-primary"><?php esc_html_e( 'Send feature request', 'wt-smart-coupons-for-woocommerce-pro' ); ?></button>
							<button type="button" name="wt_sc_request_a_feature_cancel_btn" class="button-secondary"><?php esc_html_e( 'Cancel', 'wt-smart-coupons-for-woocommerce-pro' ); ?></button>
						</div>
					</form> 
				</div>
			</div>

			<?php
		}
	}
}

Wt_Smart_Coupon_Request_feature::get_instance();