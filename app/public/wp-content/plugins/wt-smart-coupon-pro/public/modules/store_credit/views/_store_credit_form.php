<?php
/**
 * Store credit purchase form HTML.
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wt_gift_coupon_setup_wrapper">
    <?php 
    $wt_credit_amount=(isset($_REQUEST['wt_credit_amount']) ? floatval($_REQUEST['wt_credit_amount']) : 0);
    ?>
    <input type="hidden" name="wt_credit_amount" id="wt_credit_amount" value="<?php echo esc_attr($wt_credit_amount);?>" />
    <?php
    $highest_denomination=0;
    if(''!=$settings['denominations'] && ('denominations_only'==$settings['display_option'] || 'denominations_and_user_specific'==$settings['display_option']))
    {
    ?>
        <div class="wt-form-item">
            <?php
            $denominations=$this->process_denomination_list($settings['denominations']);
            $highest_denomination=max($denominations);
            $is_single_predefined = ( 1 === count( $denominations ) );
            ?>
            <div class="radio-toolbar wt_credit_denominations">
                <?php
                
                $credit_denominaton=(isset($_REQUEST['credit_denominaton']) ? floatval($_REQUEST['credit_denominaton']) : 0);
                $i=0;
                
                foreach($denominations as $denomination)
                {
                    ?>
                    <span class="wt_sc_credit_denomination">
                        <input type="radio" id="denomination_<?php echo $i; ?>" name="credit_denominaton" value="<?php echo esc_attr($denomination); ?>" <?php checked($credit_denominaton, $denomination); ?>> <label class="denominaton_label" for="denomination_<?php echo $i; ?>"><?php echo wp_kses_post(wc_price($denomination)); ?></label>
                    </span>
                    <?php
                    $i++;
                }

                //only single predefined. So set the predefined amount as default value.
                if( $is_single_predefined ){
                   ?>
                        <input type="hidden" name="wbte_is_single_predefined" id="wbte_is_single_predefined" />
                   <?php
                }
                ?>
            </div>                      
        </div> 
    <?php
    }

    if('user_specific_only'==$settings['display_option'] || 'denominations_and_user_specific'==$settings['display_option'])
    {
        $currency_symbol = get_woocommerce_currency_symbol();
        $placeholder_text=__('Amount', 'wt-smart-coupons-for-woocommerce-pro');
        if(!empty($currency_symbol))
        {
            $placeholder_text.=' ('.$currency_symbol.')';
        }
        
        $min_purchase = $this->get_giftcard_min_max_price($settings);
        $max_purchase = $this->get_giftcard_min_max_price($settings, 'max');
        
        if($highest_denomination>$max_purchase)
        {
            $max_purchase=$highest_denomination;
        }
        $wt_user_credit_amount=(isset($_REQUEST['wt_user_credit_amount']) ? floatval($_REQUEST['wt_user_credit_amount']) : 0);
        
        /**
         *  @since 2.0.4 Added option to alter decimal value support in giftcard amount 
         */
        $allow_decimals=apply_filters('wt_sc_storecredit_giftcard_amount_allow_decimals', false);
        $step_attr_value=($allow_decimals ? .01 : 1);
        ?>
        <div class="wt-form-item">
            <input id="wt_user_credit_amount" class="wt_sc_store_credit_field" step="<?php esc_attr_e($step_attr_value);?>" type="number" min="<?php echo esc_attr($min_purchase);?>" max="<?php echo esc_attr($max_purchase);?>" name="wt_user_credit_amount" placeholder="<?php echo esc_attr($placeholder_text);?>" value="<?php echo esc_attr($wt_user_credit_amount);?>"/>
            <div class="credit_instruction">
                <?php
                    if($min_purchase>0)
                    {
                        echo __('Minimum','wt-smart-coupons-for-woocommerce-pro').": ".Wt_Smart_Coupon_Admin::get_formatted_price($min_purchase);
                        echo '<br/>';
                    }
                    if($max_purchase>0)
                    {
                        echo __('Maximum','wt-smart-coupons-for-woocommerce-pro').": ".Wt_Smart_Coupon_Admin::get_formatted_price($max_purchase);
                        echo '<br/>';
                    }
                ?>
            </div>
        </div>
        <?php   
    }
    ?>             
    <div class="wt-form-item">
        <?php
        $wt_credit_coupon_send_to=(isset($_REQUEST['wt_credit_coupon_send_to']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_credit_coupon_send_to'], 'email') : '');
        ?>
        <input type="email" name="wt_credit_coupon_send_to" class="wt_sc_store_credit_field" id="wt_credit_coupon_send_to" placeholder="<?php _e('Recipient email', 'wt-smart-coupons-for-woocommerce-pro'); ?>" value="<?php echo esc_attr($wt_credit_coupon_send_to);?>" required="required"/>
    </div>
    <div class="wt-form-item">
        <?php
        $wt_credit_coupon_send_to_message=(isset($_REQUEST['wt_credit_coupon_send_to_message']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_credit_coupon_send_to_message'], 'textarea') : '');
        ?>
        <textarea name="wt_credit_coupon_send_to_message" class="wt_sc_store_credit_field" id="wt_credit_coupon_send_to_message" placeholder="<?php _e('Message', 'wt-smart-coupons-for-woocommerce-pro'); ?>"><?php echo esc_html($wt_credit_coupon_send_to_message);?></textarea>
    </div>
    <?php 
    if(self::is_extended_store_credit_enabled())
    {
        $wt_credit_coupon_from=(isset($_REQUEST['wt_credit_coupon_from']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_credit_coupon_from']) : '');
    ?>
        <div class="wt-form-item">
            <input type="text" name="wt_credit_coupon_from" class="wt_sc_store_credit_field" id="wt_credit_coupon_from" placeholder="<?php _e('Sender name','wt-smart-coupons-for-woocommerce-pro'); ?>" value="<?php echo esc_attr($wt_credit_coupon_from);?>" />
        </div>
    <?php 
    }
    ?>
    <?php do_action('wt_smart_coupon_after_credit_gift_to_friend_form');  ?>
    <div class="wt-form-item">
        <?php 
        $wt_smart_coupon_send_today=(isset($_REQUEST['wt_smart_coupon_send_today']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_send_today'], 'absint') : 0);
        
        if(!isset($_REQUEST['wt_smart_coupon_schedule_field'])) /* The below one is a checkbox, So checking must be done with a non checkbox like inputs in the form. */
        {
            $wt_smart_coupon_send_today=1; /* First time loading, So set it as checked */
        }
        ?>
        <label class="checkbox">
            <input type="checkbox" class="input-checkbox" name="wt_smart_coupon_send_today" id="wt_smart_coupon_send_today" value="1" <?php checked(1, $wt_smart_coupon_send_today);?>> <?php _e('Send today', 'wt-smart-coupons-for-woocommerce-pro');?>
        </label>
    </div>
    <div class="wt-form-item wt_smart_coupon_schedule_field_form_group">
        <?php
        $wt_smart_coupon_schedule_field=(isset($_REQUEST['wt_smart_coupon_schedule_field']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_field']) : '');
        $wt_smart_coupon_schedule_d=(isset($_REQUEST['wt_smart_coupon_schedule_d']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_d']) : '');
        $wt_smart_coupon_schedule_m=(isset($_REQUEST['wt_smart_coupon_schedule_m']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_m']) : '');
        $wt_smart_coupon_schedule_y=(isset($_REQUEST['wt_smart_coupon_schedule_y']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_y']) : '');
        ?>
        <input type="hidden" name="wt_smart_coupon_schedule_d" value="<?php echo esc_attr($wt_smart_coupon_schedule_d);?>">
        <input type="hidden" name="wt_smart_coupon_schedule_m" value="<?php echo esc_attr($wt_smart_coupon_schedule_m);?>">
        <input type="hidden" name="wt_smart_coupon_schedule_y" value="<?php echo esc_attr($wt_smart_coupon_schedule_y);?>">

        <input type="text" class="wt_sc_store_credit_field" name="wt_smart_coupon_schedule_field" id="wt_smart_coupon_schedule_field" placeholder="<?php _e('Choose a date','wt-smart-coupons-for-woocommerce-pro'); ?>" value="<?php echo esc_attr($wt_smart_coupon_schedule_field);?>">
    </div>
    <?php
    if(self::is_extended_store_credit_enabled())
    {
        $wt_credit_coupon_image=(isset($_REQUEST['wt_credit_coupon_image']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_credit_coupon_image']) : 'general');
    ?>
        <input type="hidden" name="wt_credit_coupon_image"  id="wt_credit_coupon_image"  value="<?php echo esc_attr($wt_credit_coupon_image);?>" />
    <?php 
    }
    do_action('wt_smart_coupon_after_send_credit_form');  ?>
</div>