<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<style type="text/css">
.wt_email_preview{width:100%; height:auto; display:none;}
.wt_email_preview_hide_show{ color:#2b77b4; font-size:80%; margin-left:30px; display:inline-block; cursor:pointer;}
.wt_sc_email_preview_loading{ width:100%; height:100px; line-height:100px; text-align:center; }
</style>
<div class="wt-sc-tab-content" data-id="<?php echo $target_id;?>">
    <p><?php _e('Manually email store credits of preferred value to customers of your choice. You can use it as a means to offer refunds in case of a product return or exchange.', 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
    <?php
    $email_settings_link = admin_url('admin.php?page=wc-settings&tab=email&section=wt_smart_coupon_store_credit_email');
    ?>
    <p> 
        <?php _e('To manage your store credit email settings use the ', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
        <a class="manage-store-credit" target="_blank" href="<?php echo esc_attr($email_settings_link); ?>">
            <?php _e(  'Manage store credit email settings', 'wt-smart-coupons-for-woocommerce-pro' );?>
        </a>
    </p>
    <form method="post" class="wt_sc_store_credit_mail_form">
        <?php
        // Set nonce:
        if (function_exists('wp_nonce_field'))
        {
            wp_nonce_field(WT_SC_PLUGIN_NAME);
        }
        ?>
        <table class="wt-sc-form-table">
            <?php
            Wt_Smart_Coupon_Admin::generate_form_field(array(
                array(
                    'label'         =>  __("Email address(s)", 'wt-smart-coupons-for-woocommerce-pro'),
                    'option_name'   =>  "wt_sc_send_email_address",
                    'mandatory'     =>  true,
                    'help_text'     =>  __('Input multiple email addresses separated by commas.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                array(
                    'label'         =>  __("Amount", 'wt-smart-coupons-for-woocommerce-pro'),
                    'option_name'   =>  "wt_sc_send_email_amount",
                    'css_class'     =>  "wt_sc_send_email_field",
                    'mandatory'     =>  true,
                    'type'          =>  "number",
                    'attr'          =>  'step=".01"',
                    'help_text'     =>  __('Enter the store credit amount.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                array(
                    'label'         =>  __("Caption", 'wt-smart-coupons-for-woocommerce-pro'),
                    'option_name'   =>  "wt_sc_send_email_caption",
                    'css_class'     =>  "wt_sc_send_email_field",
                    'help_text'     =>  __('Caption will appear at the top of the gift card template.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                array(
                    'label'         =>  __("Description", 'wt-smart-coupons-for-woocommerce-pro'),
                    'option_name'   =>  "wt_sc_send_email_description",
                    'css_class'     =>  "wt_sc_send_email_field",
                    'type'          =>  "textarea",
                    'help_text'     =>  __('Description will appear at the bottom of the gift card template.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                array(
                    'label'         =>  __("Individual use only", 'wt-smart-coupons-for-woocommerce-pro'),
                    'option_name'   =>  "wt_sc_send_email_individual",
                    'type'          =>  "radio",
                    'radio_fields'  =>  array(
                                            1    =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                            0   =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                        ),
                    'help_text'     =>  __('Enable this if the store credit voucher cannot be used along with other coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
            ), $this->module_id);
            ?>
        </table>
        <h3 class="wt-sc-form-settings-group-heading">
            <?php _e('Email preview', 'wt-smart-coupons-for-woocommerce-pro'); 
            echo Wt_Smart_Coupon_Admin::set_tooltip('send_store_credit_email_preview', $this->module_id);
            ?> <span class="wt_email_preview_hide_show"></span>
        </h3>
        <div class="wt_email_preview" data-loaded="0" data-loaded-type="0"></div>
        <?php
        Wt_Smart_Coupon_Admin::add_settings_footer(__("Send email", 'wt-smart-coupons-for-woocommerce-pro'));
        ?>
    </form>
</div>