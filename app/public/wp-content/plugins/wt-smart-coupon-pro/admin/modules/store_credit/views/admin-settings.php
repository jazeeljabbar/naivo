<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
$tab_items=array(
    "wt-sc-general"=>__("General settings", 'wt-smart-coupons-for-woocommerce-pro'), 
    "wt-sc-email"=>__("Email store credit", 'wt-smart-coupons-for-woocommerce-pro'),
    "wt-sc-gift-card-template"=>__("Gift card templates", 'wt-smart-coupons-for-woocommerce-pro'),
);
Wt_Smart_Coupon_Admin::img_preview_popup_html();
?>
<div class="wrap">
    <h2 class="wp-heading-inline">
    <?php _e('Store credit', 'wt-smart-coupons-for-woocommerce-pro');?>
    </h2>
    <div class="nav-tab-wrapper wp-clearfix wt-sc-tab-head">
    <?php Wt_Smart_Coupon::generate_settings_tabhead($tab_items,'module'); ?>
    </div>
    <div class="wt-sc-tab-container">
        
        <?php
        //inside the settings form
        $setting_views_a=array(
            'wt-sc-general'=>'general_settings.php',                                                                                                 
        );

        //outside the settings form
        $setting_views_b=array(                    
            'wt-sc-email'=>'email_form.php', 
            'wt-sc-gift-card-template'=>'gift_card_template.php',   
        );
        ?>
        <form method="post" class="wt_sc_settings_form">
            <input type="hidden" value="<?php echo esc_attr($this->module_base);?>" class="wt_sc_settings_base" />
            <?php
            // Set nonce:
            if (function_exists('wp_nonce_field'))
            {
                wp_nonce_field(WT_SC_PLUGIN_NAME);
            }
            foreach ($setting_views_a as $target_id=>$value) 
            {
                $settings_view=plugin_dir_path( __FILE__ ).$value;
                if(file_exists($settings_view))
                {
                    include $settings_view;
                }
            }
            ?>
            <?php 
            //settings form fields
            do_action('wt_sc_module_settings_form', array(
                'module_id'=>$this->module_base
            ));?>
        </form>
        <?php
        foreach ($setting_views_b as $target_id=>$value) 
        {
            $settings_view=plugin_dir_path( __FILE__ ).$value;
            if(file_exists($settings_view))
            {
                include $settings_view;
            }
        }
        ?>
        <?php do_action('wt_sc_module_out_settings_form', array(
            'module_id'=>$this->module_base
        ));?>
    </div>
</div>