<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<style type="text/css">
.wt_smart_coupon_import_table { width:auto;}
.wt_smart_coupon_import_table thead th{ font-weight:bold; background:#efefef; }
.wt_smart_coupon_import_table tbody td{ vertical-align:middle; border-bottom:solid 1px #efefef;}
</style>
<div id="wt_import_coupon_top-step-2" class="meta-box-sortables ui-sortable">
    <div id="wt_import_coupon_top" class="postbox ">
        <div class="wt_import_coupon_content">
            <div class="wt_settings_section wt_section_title">
                <h2><?php _e('Map coupon columns for import','wt-smart-coupons-for-woocommerce-pro'); ?></h2>
                <p><?php _e('Map the basic columns against your CSV column names respectively.','wt-smart-coupons-for-woocommerce-pro'); ?></p>
            </div>
            <form enctype="multipart/form-data" id="import-coupon" class="form import-coupon" action="admin.php?page=<?php echo esc_attr($this->module_id);?>&step=2" method="POST">
                <?php wp_nonce_field( 'wt_import_smart_coupon_step_2', '_wpnonce' ); ?>
                <input name="wt_import_id" type="hidden" value="<?php echo esc_attr($this->id); ?>" />
                <input name="email_coupon_on_import" type="hidden" value="<?php echo esc_attr($this->email_coupon_on_import); ?>" />
                <table class="widefat wt_smart_coupon_import_table">   
                    <thead>
                        <tr>
                            <th><?php _e( 'Coupon field', 'wt-smart-coupons-for-woocommerce-pro' ); ?></th>
                            <th><?php _e( 'Map column to', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach($row as $key=> $value ) 
                        { 
                            $key=$this->remove_utf8_bom($key);
                            ?>
                            <tr>                         
                                <td><code><?php echo esc_html($key ); ?></code></td>
                                <td>
                                    <select name="mapto[<?php echo esc_attr($key); ?>]">
                                        <option value=""><?php echo __('select mapping coloumn','wt-smart-coupons-for-woocommerce-pro') ?></option>
                                        <?php 
                                        foreach($raw_headers as $raw_header)
                                        {
                                            echo '<option '.selected($raw_header, $key).'>'.esc_html($raw_header).'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <?php 
                        }  
                        ?>                        
                    </tbody>                   
                </table>            
                <?php
                $back_btn='<a class="button button-primary" href="'.admin_url('admin.php?page='.$this->module_id).'" style="float:right; margin-right:10px;">'.__("Back", 'wt-smart-coupons-for-woocommerce-pro').'</a>';
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Import coupons", 'wt-smart-coupons-for-woocommerce-pro'), '', $back_btn);
                ?>
            </form>
        </div>
    </div>
</div>