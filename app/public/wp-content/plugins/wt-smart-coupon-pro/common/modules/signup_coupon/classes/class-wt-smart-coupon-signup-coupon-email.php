<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if( ! class_exists ( 'WT_smart_Coupon_Signup_Coupon_Email' ) ) {
	class WT_smart_Coupon_Signup_Coupon_Email extends WC_Email{

		
		function __construct() {

			$this->id             = 'wt_smart_coupon_signup_coupon_email';
			$this->title          = __("Welcome aboard! You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->description    = __('This email will be sent to customers when they register on website.', 'wt-smart-coupons-for-woocommerce-pro' );
			$this->customer_email = true;

			$this->heading        = __("Welcome aboard! You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->subject        = sprintf(_x("Welcome aboard! You've got a gift!", 'default email subject for active emails sent to the customer', 'wt-smart-coupons-for-woocommerce-pro') );
			$this->template_html  = 'email/send-signup-coupon.php';
			$this->template_plain = 'email/plain/send-signup-coupon.php';
			$this->template_base  = dirname(dirname(__FILE__)) . '/';

			// Triggers for this email
			add_action( 'wt_signup_coupon_created', array( $this, 'trigger' ),9,3);
			// We want all the parent's methods, with none of its properties, so call its parent's constructor
			WC_Email::__construct();
		}

		
		function trigger(  $randon_coupon,$user,$coupon_obj ) {
			$this->recipient = $user->user_email;
            
            $this->object = $coupon_obj;
			if (!$this->is_enabled() || !$this->get_recipient()) {
				return;
			}
			
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		
		
		
		function get_content_html() {
			ob_start();
			wc_get_template(
				$this->template_html,
				array(
					'coupon'       	=> $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				),
				'',
				$this->template_base
			);
			return ob_get_clean();
		}

		
		function get_content_plain() {
			ob_start();
			wc_get_template(
				$this->template_plain,
				array(
					'coupon'       	=> $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin' => false,
					'plain_text'    => false,
					'email'         => $this,
				),
				'',
				$this->template_base
			);
			return ob_get_clean();
		}
	}
}