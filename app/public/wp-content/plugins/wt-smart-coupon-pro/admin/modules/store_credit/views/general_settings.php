<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt-sc-tab-content" data-id="<?php echo $target_id;?>">	
    <p><?php _e('Your customers can purchase store credits like any other product in your store. They can gift credit to someone that can be redeemed for multiple purchases until the credit expires.','wt-smart-coupons-for-woocommerce-pro'); ?>
         <?php echo sprintf(__("To know more, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/store-credits-as-gift-cards-refunds-in-woocommerce/" target="_blank">', '</a>');  ?>
    </p>
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('Purchase settings', 'wt-smart-coupons-for-woocommerce-pro'); ?>
    </h3>
    <table class="wt-sc-form-table">
        <?php
        $select_field_data=array();
        $store_credit_product = (isset($store_credit_settings['store_credit_purchase_product']) ? $store_credit_settings['store_credit_purchase_product']  : '');
        if(isset($store_credit_product) && ''!= $store_credit_product) 
        {
            $product = wc_get_product($store_credit_product);
            if(is_object($product))
            {
                $select_field_data[$store_credit_product]=wp_kses_post($product->get_formatted_name());
            }
        }

        Wt_Smart_Coupon_Admin::generate_form_field(array(
            array(
                'label'         =>  __("Use templates for store credits",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "enabled_extended_store_credit",
                'type'          =>  "radio",
                'val_type'      =>  "boolean",
                'radio_fields'  =>  array(
                                        true    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                        false   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                    ),
                'help_text'     =>  __('Choose yes to override the default coupon layout with templates. The same template will be used for email also.','wt-smart-coupons-for-woocommerce-pro'),
                'after_form_field'     => '<a style="margin:0px 5px; font-size:100%;" class="wt-sc-form-preview-popover" data-title="'.esc_attr('Templates enabled', 'wt-smart-coupons-for-woocommerce-pro').'" data-width="1300" data-url="'.esc_attr($img_path.'templates_enabled.png').'">['.__('Templates enabled', 'wt-smart-coupons-for-woocommerce-pro').']</a>
                                           <a style="margin:0px 5px; font-size:100%;" class="wt-sc-form-preview-popover" data-title="'.esc_attr('Templates disabled', 'wt-smart-coupons-for-woocommerce-pro').'" data-width="1300" data-url="'.esc_attr($img_path.'templates_disabled.png').'">['.__('Templates disabled', 'wt-smart-coupons-for-woocommerce-pro').']</a>',
                'form_toggler'  =>  array(
                                        'type'      => 'parent',
                                        'target'    => 'enabled_extended_store_credit',
                                    ),                           
            ),
            array(
                'label'         =>  __("Display templates by category.",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "display_templates_by_category",
                'type'          =>  "radio",
                'val_type'      =>  "boolean",
                'radio_fields'  =>  array(
                                        true    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                        false   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                    ),
                'help_text'     =>  __('Display templates by category on the purchase page.','wt-smart-coupons-for-woocommerce-pro'),
                'form_toggler'  =>  array(
                                        'type'      => 'child',
                                        'id'        => 'enabled_extended_store_credit',
                                        'val'       => true,
                                        'level'     => 2,
                                    ),
            ),
            array(
                'label'         =>  __("Associate a product",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "store_credit_purchase_product",
                'type'          =>  "ajax_select",
                'attr'          =>  'data-placeholder="'.esc_attr__( 'Search for a product...', 'wt-smart-coupons-for-woocommerce-pro' ).'" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true"',
                'css_class'     =>  'wc-product-search',
                'select_fields' =>  $select_field_data,
                'help_text'     =>  __('To make store credit available for purchase as other products, create a zero priced product and associate it here.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Credit purchase options", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "display_option",
                'type'          =>  "select",
                'select_fields' =>  array(
                                        'denominations_only'                => __('Predefined only', 'wt-smart-coupons-for-woocommerce-pro'),
                                        'user_specific_only'                => __('Custom only', 'wt-smart-coupons-for-woocommerce-pro'),
                                        'denominations_and_user_specific'   => __('Custom and predefined', 'wt-smart-coupons-for-woocommerce-pro'),
                                    ),
                'form_toggler'  =>  array(
                                        'type'      => 'parent',
                                        'target'    => 'wt_purchase_options',
                                    ),
                'help_text'     =>  __('Assign and setup credit amount for store credit. Predefined will allow admins to define a specific set of denomination values. With custom, the customer can input the required credit amount for the store credit. Separate values by comma.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Set amount", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "denominations",
                'form_toggler'  =>  array(
                                        'type'      => 'child',
                                        'id'        => 'wt_purchase_options',
                                        'val'       => 'denominations_only||denominations_and_user_specific',
                                        'level'     => 2,
                                    ),
                'mandatory'     =>  true,
                'attr'          =>  'placeholder="100,200,300"',
                'required_msg'  =>  __("Amount is required.", 'wt-smart-coupons-for-woocommerce-pro'),
                'help_text'     =>  __('Specify the predefined denomination values that must appear at the user end while purchasing store credit. Each values separated by comma.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Minimum amount", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "minimum_store_credit_purchase",
                'type'          =>  "number",
                'attr'          =>  'min="1"',
                'mandatory'     =>  true,
                'form_toggler'  =>  array(
                                        'type'      => 'child',
                                        'id'        => 'wt_purchase_options',
                                        'val'       => 'user_specific_only||denominations_and_user_specific',
                                        'level'     => 2,
                                    )
            ),
            array(
                'label'         =>  __("Maximum amount", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "maximum_store_credit_purchase",
                'type'          =>  "number",
                'attr'          =>  'min="1"',
                'mandatory'     =>  true,
                'form_toggler'  =>  array(
                                        'type'      => 'child',
                                        'id'        => 'wt_purchase_options',
                                        'val'       => 'user_specific_only||denominations_and_user_specific',
                                        'level'     => 2,
                                    ),
                'help_text'     =>__('If the selected value of "Credit purchase options" is "Custom and predefined" then the maximum amount will be the highest value in between "Maximum amount" and "Denominations".', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Email store credit for order status", 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "send_purchased_credit_on_order_status",
                'type'          =>  "select",
                'select_fields' =>  Wt_Smart_Coupon_Admin::success_order_statuses(),
                'help_text'     =>  __('Emails gift card to the concerned person only for chosen order statuses. E.g. To send gift cards only for completed orders, select completed.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __('Calculate order total tax', 'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "apply_store_credit_before_tax",
                'type'          =>  "radio",
                'val_type'      =>  "boolean",
                'radio_fields'  =>  array(
                                        true   => __('After applying store credit discount', 'wt-smart-coupons-for-woocommerce-pro'),
                                        false  => __('Before applying store credit discount', 'wt-smart-coupons-for-woocommerce-pro')
                                    ),
                'help_text'     =>  __('This option will affect the total tax amount of the order','wt-smart-coupons-for-woocommerce-pro'),
            ),
        ), $this->module_id);
        ?>
    </table>
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('Coupon code format', 'wt-smart-coupons-for-woocommerce-pro'); 
        echo Wt_Smart_Coupon_Admin::set_tooltip('coupon_format');
        ?>
        <a style="margin:0px 25px;" class="wt-sc-form-preview-popover" data-title="<?php esc_attr_e('Coupon format', 'wt-smart-coupons-for-woocommerce-pro'); ?>" data-width="760" data-url="<?php echo esc_attr(WT_SMARTCOUPON_URL.'admin/assets/images/coupon-format.png');?>">[<?php _e('Sample format', 'wt-smart-coupons-for-woocommerce-pro'); ?>]</a>        
    </h3>
    <table class="wt-sc-form-table">
        <?php
        Wt_Smart_Coupon_Admin::generate_form_field(array(
            array(
                'label'         =>  __("Prefix",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "store_credit_coupon_prefix",
                'help_text'     =>  __("Specify a prefix that will appear at the beginning of the coupon code.", 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Suffix",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "store_credit_coupon_suffix",
                'help_text'     =>  __("Specify a suffix that will appear at the end of the coupon code.", 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            array(
                'label'         =>  __("Length of the coupon code",'wt-smart-coupons-for-woocommerce-pro'),
                'option_name'   =>  "store_credit_coupon_length",
                'help_text'     =>  __( "The coupon code length will be exclusive of prefix and suffix.", 'wt-smart-coupons-for-woocommerce-pro' ),
            ),
        ), $this->module_id);
        ?>
    </table>
    <?php
    Wt_Smart_Coupon_Admin::add_settings_footer(__("Save", 'wt-smart-coupons-for-woocommerce-pro'));
    ?>
</div>