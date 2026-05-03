<?php 
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    
    <div class="wt-sc-tab-container">
        <div class="wt-sc-tab-content" style="display:block;">
            <div class="wt-sc-plugin-toolbar top">
                <h3 class="wp-heading-inline">
                <?php _e('Abandoned cart coupons', 'wt-smart-coupons-for-woocommerce-pro');?>
                </h3>
            </div>  
            <p>
                <?php echo sprintf(__("To know more about Abandoned cart coupons, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/how-to-offer-discounts-for-abandoned-carts-in-woocommerce/" target="_blank">', '</a>');  ?>
            </p>
            <form method="post" class="wt_sc_settings_form">
                <input type="hidden" value="<?php echo esc_attr($this->module_base);?>" class="wt_sc_settings_base" />
                <?php
                // Set nonce:
                if (function_exists('wp_nonce_field'))
                {
                    wp_nonce_field(WT_SC_PLUGIN_NAME);
                }

                $select_field_data = array();
                $master_coupon = (isset($settings['abandonment_master_coupon']) ? $settings['abandonment_master_coupon']  : '');
                
                if('' !== $master_coupon) 
                {	            
                	$coupon = new WC_Coupon($master_coupon);
                    
                    if(0 < $coupon->get_id())
                    {
                        $discount_type = $coupon->get_discount_type();
                        $_title        = wp_kses_post( $coupon->get_code() . ' (' .__( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $discount_type . ')' );

                        if( 'wbte_sc_bogo' === $discount_type && $_coupon_id = $coupon->get_id() )
                        {
                            $_title = wp_kses_post( get_post_meta( $_coupon_id, 'wbte_sc_bogo_coupon_name', true ) . ' ( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' ) . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $_coupon_id . ' )' );
                        }

                        $select_field_data[ $master_coupon ] = $_title;
                    }
                }
                ?>                                
                <table class="wt-sc-form-table"> 
                	<?php 
                	Wt_Smart_Coupon_Admin::generate_form_field(array(
                        array(
                            'label'         =>  __("Enable abandoned cart coupon",'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "enable_abandonment_coupon",
                            'type'          =>  "radio",
                            'val_type'      =>  "boolean",
                            'radio_fields'  =>  array(
                                                    true    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                                    false   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                                ),
                            'help_text'     =>  __('Enable the option to create and assign coupons to customers automatically upon cart/checkout abandonment.', 'wt-smart-coupons-for-woocommerce-pro'),                          
                        ),
                        array(
                            'label'         =>  __("Associate a master coupon",'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "abandonment_master_coupon",
                            'type'          =>  "ajax_select",
                            'attr'          =>  'data-allow_clear="true" data-placeholder="'.esc_attr__( 'Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ).'" data-action="wt_json_search_coupons" data-security="'.esc_attr(wp_create_nonce('search-coupons')).'"',
                            'css_class'     =>  'wt-coupon-search',
                            'select_fields' =>  $select_field_data,
                            'help_text'     =>  __('The abandoned cart coupon will be created based on the underlying master coupon. The coupon configuration(discount percentage and other related rules) will be created based on the selected master coupon.', 'wt-smart-coupons-for-woocommerce-pro').'<br />'.__('Do not use same coupon as master coupon/gift coupon for different functionalities.', 'wt-smart-coupons-for-woocommerce-pro'),
                        ),
                        array(
                            'label'         =>  __("Idle time", 'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "cut_of_time",
                            'help_text'     =>  __('Minimum time(in mins) that the item/s should remain in cart for the customer to be eligible for the coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
                        ),
                        array(
                            'label'         =>  __("Email coupon interval", 'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "email_send_after",
                            'help_text'     =>  __('Specify the duration(in mins) after which the coupon will be mailed to the eligible customers.', 'wt-smart-coupons-for-woocommerce-pro'),
                        ),
                        array(
                            'label'         =>  __("Use master coupon code as is",'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "use_master_coupon_as_is",
                            'type'          =>  "radio",
                            'val_type'      =>  "boolean",
                            'radio_fields'  =>  array(
                                                    true    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                                    false   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                                ),
                            'help_text'     =>  __("When enabled the coupon code will be the same as the master coupon code. The email ids of the eligible customers will be added to 'Allowed emails' under Usage Restriction section of the master coupon. When unchecked a new coupon code will be generated for every eligible customer. These coupons will follow the same configuration as the master coupon, the difference being a unique coupon code. The coupon code can be formatted as per the prefix/suffix/length options from below. If not specified it will take the format as per the General Settings.", 'wt-smart-coupons-for-woocommerce-pro'),                          
                        	'form_toggler'  =>  array(
                                            	'type'      => 'parent',
                                            	'target'    => 'use_master_coupon_as_is',
                                        	),

                        ),
                        array(
                            'label'         =>  __("Prefix", 'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "abandonment_coupon_prefix",
                            'form_toggler'  =>  array(
                                                'type'      => 'child',
                                                'id'        => 'use_master_coupon_as_is',
                                                'val'       => 0,
                                                'level'     => 2,
                                            ),
                        ),
                        array(
                            'label'         =>  __("Suffix", 'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "abandonment_coupon_suffix",
                            'form_toggler'  =>  array(
                                                'type'      => 'child',
                                                'id'        => 'use_master_coupon_as_is',
                                                'val'       => 0,
                                                'level'     => 2,
                                            ),
                        ),
                        array(
                            'label'         =>  __("Length of coupon code", 'wt-smart-coupons-for-woocommerce-pro'),
                            'option_name'   =>  "abandonment_coupon_length",
                            'form_toggler'  =>  array(
                                                'type'      => 'child',
                                                'id'        => 'use_master_coupon_as_is',
                                                'val'       => 0,
                                                'level'     => 2,
                                            ),
                        ),
                    ), $this->module_id);

                	?>
                </table>
                <?php do_action('wt_smart_coupon_after_abandonment_coupon_settings_from'); ?>
                <?php
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Save", 'wt-smart-coupons-for-woocommerce-pro'));
                ?>
            </form>
        </div>
    </div>
</div>