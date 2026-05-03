<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if(!class_exists('WT_smart_Coupon_Gift'))
{
	class WT_smart_Coupon_Gift extends WC_Email
	{
	
		public function __construct()
		{

			$this->id             = 'wt_smart_coupon_gift';
			$this->title          = __("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->description    = __('This email will be sent to customers upon completing an order containing a product with a associated gift coupon.', 'wt-smart-coupons-for-woocommerce-pro' );
			$this->customer_email = true;

			$this->heading        = __("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro' );
			$this->subject        = sprintf(_x("You've got a gift!", 'default email subject for active emails sent to the customer', 'wt-smart-coupons-for-woocommerce-pro') );
			$this->template_html  = 'email/send-gift-coupon.php';
			$this->template_plain = 'email/plain/send-gift-coupon.php';
			$this->template_base = dirname(dirname(__FILE__)) . '/';

			// Triggers for this email
			add_action('wt_send_gift_coupon_to_customer', array( $this, 'trigger' ),9,2);
			

			// We want all the parent's methods, with none of its properties, so call its parent's constructor
			WC_Email::__construct();
		}

		
		/**
		 * 	Trigger the mail
		 * 
		 * 	@since 2.0.8 Added HPOS Compatibility
		 */
		function trigger( $order,$coupons ) {
            $this->recipient = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupon_send_to');
            
            
            $this->object = $order;
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
					'order'       	=> $this->object,
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
					'order'       	=> $this->object,
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