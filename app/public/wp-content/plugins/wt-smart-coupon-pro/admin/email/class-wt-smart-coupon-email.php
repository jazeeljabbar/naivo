<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if( ! class_exists ( 'WT_smart_Coupon_Email' ) ) {
	class WT_smart_Coupon_Email extends WC_Email{

		
		function __construct() {

			$this->id             = 'wt_smart_coupon';
			$this->title          = __("You've got a coupon!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->description    = __('This email will be sent to specified customers while creating bulk coupons and upon coupon import(if notification is enabled).', 'wt-smart-coupons-for-woocommerce-pro' );
			$this->customer_email = true;

			$this->heading        = __("You've got a coupon!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->subject        = sprintf(_x("You've got a coupon!", 'default email subject for active emails sent to the customer', 'wt-smart-coupons-for-woocommerce-pro') );
			$this->template_html  = 'email/send-customer-coupon.php';
			$this->template_plain = 'email/plain/send-customer-coupon.php';
			$this->template_base = WT_SMARTCOUPON_MAIN_PATH . 'admin/partials/';

			// Triggers for this email
			add_action( 'wt_send_coupon_to_customer', array( $this, 'trigger' ),10,3);
			
			
			// We want all the parent's methods, with none of its properties, so call its parent's constructor
			WC_Email::__construct();
		}
		 
		function trigger( $coupon,$coupon_code,$email ) {
			$this->recipient = $email;
			$this->object = $coupon;
			if (!$this->is_enabled() || !$this->get_recipient()) {
				return;
			}

			$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

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
					'coupon'         => $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin' => false,
					'plain_text'    => true,
					'email'         => $this,
				),
				'',
				$this->template_base
			);
			return ob_get_clean();
		}
	}
}