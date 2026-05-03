<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

?>
<div id="message">
    <p></p>
</div>
<div id="normal-sortables-1" class="meta-box-sortables ui-sortable">
    <div id="wt_import_coupon_top" class="postbox ">
        <div class="wt_import_coupon_content">
            <div class="wt_settings_section wt_section_title">
                <h2><?php _e('Import coupons','wt-smart-coupons-for-woocommerce-pro'); ?></h2>
                <p>
                    <?php _e('This section allows you to import coupons from a CSV(UTF-8 format) file into your store.','wt-smart-coupons-for-woocommerce-pro'); ?>
                    <?php echo sprintf(__("To know more, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/import-coupons-in-bulk-using-a-csv-file-smart-coupon-for-woocommerce/" target="_blank">', '</a>');  ?>
                </p>
            </div>
            <p class="import-instructions"> 
                <?php 
                echo esc_html__('For a clean import the CSV must include the header and adhere to the format as indicated in our ','wt-smart-coupons-for-woocommerce-pro');
                echo  '<a href="'.esc_url($sample_file_url).'">'.esc_html__('sample file.','wt-smart-coupons-for-woocommerce-pro');
                echo '</a>';
                _e(' Columns <i>post_title</i> and <i>discount_type</i> are mandatory for the import. Duplicate coupons will be skipped during import.','wt-smart-coupons-for-woocommerce-pro'); ?>
            </p>
            <form enctype="multipart/form-data" id="import-coupon" class="form import-coupon" action="admin.php?page=<?php echo esc_attr($this->module_id);?>&step=1" method="POST">
                <?php wp_nonce_field(  'wt_import_smart_coupon', '_wpnonce'); ?>                           
                <div class="wt-import-input-file-container">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td scope="row" class="titledesc"><?php _e('Upload the CSV file','wt-smart-coupons-for-woocommerce-pro') ?></td>
                                <td>
                                    <label for="upload" class="button button-hero sc-file-container">
                                        <span class="wt-file-container-label"><?php _e('Upload','wt-smart-coupons-for-woocommerce-pro'); ?>  <span class="dashicons dashicons-upload"></span></span>
                                        <input type="file" id="wt_smart_coupon_upload" name="import" accept=".csv" size="25" required="">                                       
                                    </label>
                                    <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>">
                                    <p><small><?php _e('Maximum file size:','wt-smart-coupons-for-woocommerce-pro');  ?> <?php  echo $size_in_mb; ?></small></p>
                                </td>
                            </tr>
                            <tr class="email-coupon-on-import-1">
                                <td scope="row" class="titledesc"><?php _e('Email coupon to users upon Import','wt-smart-coupons-for-woocommerce-pro'); ?></td>
                                <td><input type="checkbox" name="email_coupon_on_import" id="email_coupon_on_import" value="1" /></td>
                            </tr>
                        </tbody>
                    </table>                  
                </div>          
                <?php
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Next: Map columns for import", 'wt-smart-coupons-for-woocommerce-pro'));
                ?>
            </form>
        </div>
    </div>
</div>