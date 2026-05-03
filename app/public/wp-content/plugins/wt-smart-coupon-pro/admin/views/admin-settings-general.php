<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
/**
 *  @since 1.3.5
 */
?>
<div class="wt-sc-tab-content" data-id="<?php echo $target_id;?>">
	
    <p><?php _e("Configure options from the settings below to suit your business needs.", 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('Coupon code format', 'wt-smart-coupons-for-woocommerce-pro'); 
        echo self::set_tooltip('coupon_format');
        ?>
        <a style="margin:0px 25px;" class="wt-sc-form-preview-popover" data-title="<?php esc_attr_e('Coupon format', 'wt-smart-coupons-for-woocommerce-pro'); ?>" data-width="760" data-url="<?php echo esc_attr(WT_SMARTCOUPON_URL.'admin/assets/images/coupon-format.png');?>">[<?php _e('Sample format', 'wt-smart-coupons-for-woocommerce-pro'); ?>]</a>
    </h3>
    <table class="wt-sc-form-table">
        <?php
        self::generate_form_field(array(
            array(
                'label'         =>  __("Prefix",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "wt_coupon_prefix",
                'help_text'     =>  __("Specify a prefix that will appear at the beginning of the coupon code.", 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Suffix",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "wt_coupon_suffix",
                'help_text'     =>  __("Specify a suffix that will appear at the end of the coupon code.", 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __( "The coupon code length will be exclusive of prefix and suffix.",'wt-smart-coupons-for-woocommerce-pro' ),
                'option_name'   =>  "wt_coupon_length",
            ),
        ));
        ?>
    </table>
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('My coupons page', 'wt-smart-coupons-for-woocommerce-pro'); 
        ?>           
    </h3>
    <table class="wt-sc-form-table">
        <?php
        self::generate_form_field(array(
            array(
                'label'         =>  __( "Enable My coupons page", 'wt-smart-coupons-for-woocommerce-pro' ),
                'option_name'   =>  "wbte_sc_enable_coupons_page",
                'type'          =>  "checkbox",
                'field_vl'      =>  'yes',
                'form_toggler'  =>  array(
                    'type'      => 'parent',
                    'target'    => 'wbte_sc_enable_coupons_page',
                ),
            ),
            array(
                'label'         =>  __("URL endpoint",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "wt_account_endpoint",
                'form_toggler'  =>  array(
                    'type'      => 'child',
                    'id'        => 'wbte_sc_enable_coupons_page',
                    'val'       => 'yes',
                    'level'     => 2,
                    'check'     => 'true',
                ),
                'css_class'     => 'wbte_account_endpoint_input_class',
                'td_additional_class' => 'wbte_sc_account_endpoint_td_class',
                'before_form_field' => '<span class="wbte_sc_account_endpoint_site_url">' . __( 'yoursite.com/my-account/', 'wt-smart-coupons-for-woocommerce-pro' ) . '</span>'
            ),
            array(
                'label'         =>  __("Page title",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "wt_endpoint_title",
                'form_toggler'  =>  array(
                    'type'      => 'child',
                    'id'        => 'wbte_sc_enable_coupons_page',
                    'val'       => 'yes',
                    'level'     => 2,
                    'check'     => 'true',
                ),
            ),
            array(
                'label'         =>  __( 'Additionally display', 'wt-smart-coupons-for-woocommerce-pro' ),
                'option_name'   =>  'wbte_sc_coupons_page_additional_display',
                'type'          =>  'checkbox_list',
                'checkbox_fields'  =>  array(
                    'used_coupons'    => __( 'Used coupons', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'expired_coupons'     => __( 'Expired coupons', 'wt-smart-coupons-for-woocommerce-pro' ),               
                ),
                'form_toggler'  =>  array(
                    'type'      => 'child',
                    'id'        => 'wbte_sc_enable_coupons_page',
                    'val'       => 'yes',
                    'level'     => 2,
                    'check'     => 'true',
                ),
            ),
        ));
        ?>
    </table>
    <?php 
        /**
         *  Option to add settings fields after `My coupons page` settings
         *  
         *  @since 2.3.0
         */
        do_action( 'wbte_sc_after_my_coupons_page_settings' ); 
    ?>
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('Additional settings', 'wt-smart-coupons-for-woocommerce-pro');
        ?>           
    </h3>
    <table class="wt-sc-form-table">
        <?php
        
        self::generate_form_field(array(
            array(
                'label'         =>  __("Display coupons for eligible cart items", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "only_display_cart_valid_coupons",
                'type'          =>  "checkbox",
                'checkbox_label'   =>  __("When enabled, coupons are shown to users only if their cart contains eligible items. Applicable only in cart and checkout pages.", 'wt-smart-coupons-for-woocommerce-pro'),
                'field_vl'      =>  'yes',
            ),
        ));
        ?>
    </table>
    
    <?php //do_action('wt_smart_coupon_general_settings') ?>
    <?php 
    include "admin-settings-save-button.php";
    ?>
</div>