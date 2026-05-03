<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt_order_credit_coupons">
    <div class="wt-smartcoupon-store-credits">
        <?php
        foreach($order_items as $order_item)
        {
            $coupons_generated = $order_item->get_meta('wt_credit_coupon_generated');
            
            if(empty($coupons_generated) || !is_array($coupons_generated)) //not a gift cart order item
            {
                continue;
            }

            $coupon_template_details = $order_item->get_meta('wt_credit_coupon_template_details');
            $coupon_template_details = (!empty($coupon_template_details) && is_array($coupon_template_details) ? $coupon_template_details : array());

            foreach($coupons_generated as $generated_coupon)
            {
                $coupon_id = $generated_coupon['coupon_id'];
                $coupon_obj = new WC_Coupon($coupon_id);

                if(!$coupon_obj)
                {
                    continue;
                }

                $wt_store_credit_schedule = 0;

                if(isset($coupon_template_details[$coupon_id]) && is_array($coupon_template_details[$coupon_id])) //Giftcard details. The below variables are declared in the parent method just overriding if template data exists.
                {
                    $coupon_template_data = $coupon_template_details[$coupon_id];
 
                    $wt_store_credit_schedule   =   ( isset( $coupon_template_data['wt_smart_coupon_schedule'] ) ? (int) $coupon_template_data['wt_smart_coupon_schedule'] : 0);
                    $wt_store_credit_send_from  =   ( isset( $coupon_template_data['wt_credit_coupon_from'] ) ? $coupon_template_data['wt_credit_coupon_from'] : '');
                    $wt_store_credit_send_to    =   ( isset( $coupon_template_data['wt_credit_coupon_send_to'] ) ? $coupon_template_data['wt_credit_coupon_send_to'] : '');
                    $wt_store_credit_message    =   ( isset( $coupon_template_data['wt_credit_coupon_send_to_message'] ) ? $coupon_template_data['wt_credit_coupon_send_to_message'] : '');
                    $wt_store_credit_template   =   ( isset( $coupon_template_data['wt_smart_coupon_template_image'] ) ? $coupon_template_data['wt_smart_coupon_template_image'] : '');                   
                
                    $wt_store_credit_schedule = $wt_store_credit_schedule>0 ? Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($wt_store_credit_schedule)->getOffsetTimestamp() : 0;
                }
                
                $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon_obj);
                $coupon_data['coupon_amount'] = Wt_Smart_Coupon_Admin::get_formatted_price($generated_coupon['credited_amount']);
                $coupon_data['display_on_page'] = 'credit_meta';
                $send_action_type = 'send'; /** @since 2.1.0 this is to set customized order notes */
                
                if($this->is_generated_coupon_activated($coupon_id))
                {
                    $status_text = __('Sent', 'wt-smart-coupons-for-woocommerce-pro');
                    $send_button_text = __('Resend', 'wt-smart-coupons-for-woocommerce-pro');
                    $send_action_type = 'resend';

                }else
                {
                    if($wt_store_credit_schedule>0 && $wt_store_credit_schedule>time()) //future date
                    {
                        $status_text = __('Scheduled', 'wt-smart-coupons-for-woocommerce-pro');
                        $send_button_text = __('Force send', 'wt-smart-coupons-for-woocommerce-pro');
                        $send_action_type = 'force_send';

                    }else
                    {
                        if(!in_array($order->get_status(), self::get_order_status_for_gift_card_email($order)))
                        {
                            $status_text = __('Awaiting order status update', 'wt-smart-coupons-for-woocommerce-pro');
                            $send_button_text = __('Force send', 'wt-smart-coupons-for-woocommerce-pro');
                            $send_action_type = 'force_send';

                        }else
                        {
                            $status_text = __('Unknown', 'wt-smart-coupons-for-woocommerce-pro');
                            $send_button_text = __('Send', 'wt-smart-coupons-for-woocommerce-pro');
                            $send_action_type = 'send';
                        }
                    }
                }

                $coupon_edit_url = add_query_arg( array( 'post' => $coupon_id, 'action' => 'edit', ), admin_url( 'post.php' ) );
                
                ?>
                <div class="wt-smartcoupon-store-credit-item"> 
                    <div class="coupon_meta">
                        
                        <span><b><?php _e('Coupon amount: ', 'wt-smart-coupons-for-woocommerce-pro');?> </b> <?php echo esc_html($coupon_data['coupon_amount']);?> </span>
                        <span><b><?php _e('Coupon code: ', 'wt-smart-coupons-for-woocommerce-pro');?> </b> <a href="<?php echo esc_attr($coupon_edit_url);?>"><?php echo esc_html($coupon_obj->get_code());?></a> </span>

                        <?php
                        if($coupon_data['coupon_expires'])
                        {
                            ?>
                            <span><b><?php _e('Expiry: ', 'wt-smart-coupons-for-woocommerce-pro');?> </b> <?php echo esc_html(Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts($coupon_data['coupon_expires'], "expiry_date"));?> </span>
                            <?php
                        }

                        if("" !== $wt_store_credit_send_from)
                        {
                            ?>
                            <span><b><?php _e('From: ','wt-smart-coupons-for-woocommerce-pro');?> </b> <?php echo esc_html($wt_store_credit_send_from);?> </span>
                            <?php 
                        }
                        ?>                      
                        <span><b><?php _e('To: ','wt-smart-coupons-for-woocommerce-pro');?> </b><?php echo esc_html($wt_store_credit_send_to);?> </span>                       
                        <?php

                        if("" !== $wt_store_credit_message)
                        {
                            ?>
                            <span><b><?php _e('Message: ','wt-smart-coupons-for-woocommerce-pro');?> </b><?php echo esc_html($wt_store_credit_message);?> </span>
                            <?php 
                        }

                        if($wt_store_credit_schedule > 0)
                        {
                            ?>
                            <span><b><?php _e('Scheduled: ','wt-smart-coupons-for-woocommerce-pro');?> </b><?php echo esc_html(Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($wt_store_credit_schedule)->date_i18n(wc_date_format()));?> </span>
                            <?php
                        }

                        ?>
                        <span><b><?php _e('Status: ','wt-smart-coupons-for-woocommerce-pro');?> </b><?php echo esc_html($status_text);?> </span>
                        <?php

                        /**
                         *  Last sent date
                         *  
                         *  @since 2.1.0
                         */
                        $last_send_date_gmt = get_post_meta($coupon_id, '_wt_sc_send_date_gmt', true);

                        if($last_send_date_gmt && ($last_send_date = get_date_from_gmt($last_send_date_gmt, wc_date_format() . ' ' . wc_time_format())))
                        {
                            ?>
                            <span><b><?php _e('Last sent on: ','wt-smart-coupons-for-woocommerce-pro');?> </b><?php echo esc_html($last_send_date);?> </span>
                            <?php
                        }

                        

                        if("" !== $wt_store_credit_template)
                        {
                            $template_data=self::get_gift_card_template($wt_store_credit_template);
                            if(isset($template_data['image_url']) && "" !== $template_data['image_url'])
                            {
                                ?>
                                    <br />
                                    <span><img src="<?php echo esc_attr($template_data['image_url']);?>" width="200"></span>
                                    <br />
                                <?php 
                            }
                        }
                        ?>
                    </div>
                    <div class="wt-send-coupon">
                        <button order-id="<?php esc_attr_e($order_id);?>" coupon-id="<?php esc_attr_e($coupon_id);?>" class="btn wt-btn-resend-store-credit button-primary button-large" type="button" data-resend-text="<?php esc_attr_e('Resend', 'wt-smart-coupons-for-woocommerce-pro'); ?>" data-action-type="<?php echo esc_attr($send_action_type);?>"><?php esc_html_e($send_button_text);?></button>
                        <div class="wt-send-status"></div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>
<script type="text/javascript">
jQuery('document').ready(function(){

    jQuery(document).on('click', '.wt-btn-resend-store-credit', function(e){
        
        if(!confirm(WTSmartCouponAdminOBJ.msgs.are_you_sure))
        {
            return false;
        }

        e.preventDefault();
        var elm=jQuery(this);
        var metabox_elm = jQuery('#wt-coupons-in-order');

        var data = {
            'action'        : 'wt_resend_store_credit_coupon',
            '_wpnonce'      : WTSmartCouponAdminOBJ.nonce,
            '_wt_order_id'  : elm.attr('order-id'),
            '_wt_coupon_id' : elm.attr('coupon-id'),
            'action_type'   : elm.attr('data-action-type'), /** @since 2.1.0 to set customized order notes */
        };
        var html_bck=elm.html();
        elm.html(WTSmartCouponAdminOBJ.msgs.please_wait).prop('disabled', true);

        wt_block_node(metabox_elm);

        jQuery.ajax({
            type: "POST",
            url: WTSmartCouponAdminOBJ.ajaxurl,
            data: data,
            dataType: 'json',
            success:function(data)
            {
                wt_unblock_node(metabox_elm);
                elm.html(html_bck).prop('disabled', false);

                if(data.status)
                {
                    wt_sc_notify_msg.success(data.msg);
                    elm.html(elm.attr('data-resend-text'));

                    /** @since 2.1.0 Reload the page via ajax to show updated details */
                    wt_block_node(metabox_elm);
                    jQuery.get('', function(data){
                        
                        wt_unblock_node(metabox_elm);
                        let temp_elm = jQuery('<div>').html(data);
                        let order_coupon_temp_elm = temp_elm.find('#wt-coupons-in-order .wt_order_credit_coupons');
                        let order_notes_temp_elm = temp_elm.find('#woocommerce-order-notes .inside .order_notes');
                        
                        if(order_coupon_temp_elm.length)
                        {
                            metabox_elm.find('.wt_order_credit_coupons').html(order_coupon_temp_elm.html());
                        }

                        if(order_notes_temp_elm.length)
                        {
                            jQuery('#woocommerce-order-notes .inside .order_notes').html(order_notes_temp_elm.html());
                        }
                    });

                }else
                {
                    wt_sc_notify_msg.error(data.msg);
                }
            },
            error:function()
            {
                wt_unblock_node(metabox_elm);
                elm.html(html_bck).prop('disabled', false);
                wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
            },
        });

    });
});
</script>