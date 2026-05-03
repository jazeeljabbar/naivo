<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style type="text/css">
.wt-button-wrapper{float:left; width:98%; background:#fafafa; padding:15px; box-sizing:border-box; box-shadow:0px 2px 2px #ccc; border:solid 1px #c3c4c7; border-top:none; }
#woocommerce-coupon-data{ margin-bottom:0px; border-bottom:none; }
#create_bulk_coupon{ float:right; }
.select2-search__field::placeholder{ font-size:14px; color:#656a71;}
.wc-wp-version-gte-53 .select2-container .select2-selection--multiple{ min-height:50px; }
.woocommerce_options_panel p.form-field, .woocommerce_options_panel fieldset.form-field{ padding: 5px 20px 5px 212px !important; }
.woocommerce_options_panel label, .woocommerce_options_panel legend{ width:200px; margin: 0 0 0 -200px; }
#wt_nth_order_coupon .form-field{ padding: 5px 20px 5px 212px !important; }
</style>
<form id="generate-coupon" class="form bulk-generate-coupon" action="admin.php?page=<?php echo esc_attr($this->module_id);?>" method="POST">                
    <div id="normal-sortables-4" class="meta-box-sortables ui-sortable">
    <div id="wt_bulk_create_top" class="postbox ">
        <div class="wt_bulk_create_top_content">
            <div class="wt_settings_section" style="padding:0px;">
                <h2><?php _e('Bulk Generate Coupon','wt-smart-coupons-for-woocommerce-pro'); ?></h2>
                <p> <?php _e('Store owners can use this option to create coupon in bulk with unique codes and a preset criteria. You can add these coupons to your store, share to customers directly by mail or simply export into a CSV for a later import.','wt-smart-coupons-for-woocommerce-pro'); ?>
                    <?php echo sprintf(__("To know more, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/bulk-generate-coupons-using-smart-coupon-for-woocommerce/" target="_blank">', '</a>');  ?>
                </p>
                <p><?php _e(' Specified number of coupons are generated as per the matching criteria from the Coupon data section below.','wt-smart-coupons-for-woocommerce-pro'); ?></p>
            </div>         
            <div class="generate-coupon-wrapper">
                <div class="wt_bulk_section_title">
                    <?php _e('Action','wt-smart-coupons-for-woocommerce-pro') ?>
                </div>
                <?php wp_nonce_field(  'wt_bulk_generate_coupon' ); ?>
                <table>
                    <tr class="form-group">
                        <td><label> <?php _e('No of coupons to be generated','wt-smart-coupons-for-woocommerce-pro'); ?></label></td>
                        <td><input min="1" step="1" type="number" class="form-item" name="_wt_no_of_coupons" id="_wt_no_of_coupons"  placeholder="0" /></td>
                    </tr>
                    <tr class="form-group">
                        <td><label><?php _e('Generate coupons and,', 'wt-smart-coupons-for-woocommerce-pro') ?></label></td>
                        <td>
                            <p><label><input type="radio" name="wt_generate_coupon_and" value="add_to_store" checked /><?php _e('Add to Store','wt-smart-coupons-for-woocommerce-pro') ?></label></p>
                            <p><label><input type="radio" name="wt_generate_coupon_and" value="export_as_csv_store"/><?php _e('Export as CSV','wt-smart-coupons-for-woocommerce-pro'); ?></label></p>
                            <p><label><input type="radio" name="wt_generate_coupon_and" value="email_to_recipients"/><?php _e('Email to the recipients','wt-smart-coupons-for-woocommerce-pro') ?></label></p>
                        </td>
                    </tr>
                </table>
                <div class="bulk-generate-desc wt_sc_conditional_help_text" data-sc-help-condition="[wt_generate_coupon_and=email_to_recipients]">  
                    <p><?php _e('Email recipients option works in combination with allowed emails. If email restriction is applied under allowed emails option, the application generates only enough no. of coupons depending on whichever is the lowest value, either the coupon number or the number of emails.', 'wt-smart-coupons-for-woocommerce-pro') ?></p>                   
                </div>
            </div>
        </div>
    </div>
    </div>
    <div id="wt-coupon-meta-box" class="postbox-container">
        <div id="normal-sortables-5" class="meta-box-sortables ui-sortable">
            <div id="woocommerce-coupon-data" class="postbox ">
                <h2 class="hndle ui-sortable-handle"><span><?php _e('Coupon data','wt-smart-coupons-for-woocommerce-pro'); ?></span></h2>
                <div class="inside">
                    <?php WC_Meta_Box_Coupon_Data::output( $post ); ?>
                </div>           
            </div>
        </div>
    </div>
    <div class="wt-button-wrapper">       
        <input class="button button-primary button-large" type="submit" name="create_bulk_coupon" id="create_bulk_coupon" value="<?php esc_attr_e('Generate coupon', 'wt-smart-coupons-for-woocommerce-pro'); ?>" />
    </div>
</form>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('#generate-coupon').on('submit', function(event){
            var wt_no_of_coupons=jQuery('input#_wt_no_of_coupons').val().trim();
            if(wt_no_of_coupons=="" || wt_no_of_coupons==0)
            {
                event.preventDefault();
                wt_sc_notify_msg.error("<?php _e('Please enter a valid value for Number of Coupons to Generate', 'wt-smart-coupons-for-woocommerce-pro'); ?>");
                jQuery('html, body').animate({scrollTop: 0}, 'slow');
                jQuery('input#_wt_no_of_coupons').focus();
                return false;
            }
        });
    });
</script>