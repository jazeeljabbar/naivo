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
                <?php _e('Signup coupons', 'wt-smart-coupons-for-woocommerce-pro');?>
                </h3>
            </div>
            <p>
                <?php echo sprintf(__("To know more about Signup coupons, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/how-to-provide-woocommerce-signup-discount-for-customers/" target="_blank">', '</a>');  ?>
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
                $master_coupon = (isset($settings['wt_signup_master_coupon']) ? $settings['wt_signup_master_coupon']  : '');
                
                if('' !== $master_coupon) 
                {               
                    $coupon = new WC_Coupon($master_coupon);
                    
                    if(0 < $coupon->get_id())
                    {
                        $discount_type = $coupon->get_discount_type();
                        $_title        = wp_kses_post( $coupon->get_code() . ' (' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $discount_type . ')' );

                        if( 'wbte_sc_bogo' === $discount_type && $_coupon_id = $coupon->get_id() )
                        {
                            $_title = wp_kses_post( get_post_meta( $_coupon_id, 'wbte_sc_bogo_coupon_name', true ) . ' ( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' ) . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $_coupon_id . ' )' );
                        }

                        $select_field_data[ $master_coupon ] = $_title;
                    }
                }
                ?>                 
                <table class="wt-sc-form-table">
                    <tbody>
                        <?php 
                            Wt_Smart_Coupon_Admin::generate_form_field(array(
                                array(
                                    'label'         =>  __("Enable signup coupon",'wt-smart-coupons-for-woocommerce-pro'),
                                    'option_name'   =>  "enable_signup_coupon",
                                    'type'          =>  "radio",
                                    'val_type'      =>  "boolean",
                                    'radio_fields'  =>  array(
                                                            true    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                                            false   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                                        ),
                                    'help_text'     =>  __('Signup coupon inherits settings from the chosen master coupon. Avoid using the same coupon for different purposes.', 'wt-smart-coupons-for-woocommerce-pro'),                          
                                ),
                                array(
                                    'label'         =>  __("Associate a master coupon",'wt-smart-coupons-for-woocommerce-pro'),
                                    'option_name'   =>  "wt_signup_master_coupon",
                                    'type'          =>  "ajax_select",
                                    'attr'          =>  'data-allow_clear="true" data-placeholder="'.esc_attr__( 'Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ).'" data-action="wt_json_search_coupons" data-security="'.esc_attr(wp_create_nonce('search-coupons')).'"',
                                    'css_class'     =>  'wt-coupon-search',
                                    'select_fields' =>  $select_field_data,
                                    'help_text'     =>  __('The signup coupon will be created based on the underlying master coupon. The coupon configuration(discount percentage and other related rules) will be created based on the selected master coupon.', 'wt-smart-coupons-for-woocommerce-pro').'<br />'.__('Do not use same coupon as master coupon/gift coupon for different functionalities.', 'wt-smart-coupons-for-woocommerce-pro'),
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
                                    'help_text'     =>  __("When enabled, the signup coupon shares the master coupon code and adds users' emails to 'Allowed emails' in Usage Restrictions. When unchecked, each signup generates a new coupon with the same settings as the master coupon but a unique code.", 'wt-smart-coupons-for-woocommerce-pro'),                          
                                    'form_toggler'  =>  array(
                                                        'type'      => 'parent',
                                                        'target'    => 'use_master_coupon_as_is',
                                                    ),

                                ),
                                array(
                                    'label'         =>  __("Prefix", 'wt-smart-coupons-for-woocommerce-pro'),
                                    'option_name'   =>  "signup_coupon_prefix",
                                    'form_toggler'  =>  array(
                                                        'type'      => 'child',
                                                        'id'        => 'use_master_coupon_as_is',
                                                        'val'       => 0,
                                                        'level'     => 2,
                                                    ),
                                ),
                                array(
                                    'label'         =>  __("Suffix", 'wt-smart-coupons-for-woocommerce-pro'),
                                    'option_name'   =>  "signup_coupon_suffix",
                                    'form_toggler'  =>  array(
                                                        'type'      => 'child',
                                                        'id'        => 'use_master_coupon_as_is',
                                                        'val'       => 0,
                                                        'level'     => 2,
                                                    ),
                                ),
                                array(
                                    'label'         =>  __("Length of coupon code", 'wt-smart-coupons-for-woocommerce-pro'),
                                    'option_name'   =>  "signup_coupon_length",
                                    'form_toggler'  =>  array(
                                                        'type'      => 'child',
                                                        'id'        => 'use_master_coupon_as_is',
                                                        'val'       => 0,
                                                        'level'     => 2,
                                                    ),
                                ),
                            ), $this->module_id);
                        ?>
                    </tbody>
                </table>
                <?php do_action('wt_smart_coupon_after_signup_coupon_settings_from'); ?>
                <?php
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Save", 'wt-smart-coupons-for-woocommerce-pro'));
                ?>
            </form>
        </div>
    </div>
</div>