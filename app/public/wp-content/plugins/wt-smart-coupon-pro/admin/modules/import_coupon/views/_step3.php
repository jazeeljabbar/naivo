<div class="meta-box-sortables ui-sortable">
    <div id="wt_import_coupon_top" class="postbox ">
        <div class="wt_import_coupon_content">
            <div class="wt_settings_section wt_section_title">
                <h2><?php _e('Import complete:', 'wt-smart-coupons-for-woocommerce-pro'); ?></h2>
                <ul class="import-result"></ul>
            </div>
            <table id="wt_smar_coupon_import_progress" class="widefat_importer widefat">
                <thead>
                    <tr>
                        <th class="status">&nbsp;</th>
                        <th class="row"><?php _e( 'Row', 'wt-smart-coupons-for-woocommerce-pro' ); ?></th>
                        <th><?php _e( 'Coupon id', 'wt-smart-coupons-for-woocommerce-pro' ); ?></th>
                        <th><?php _e( 'Coupon code', 'wt-smart-coupons-for-woocommerce-pro' ); ?></th>
                        <th class="reason"><?php _e( 'Status message', 'wt-smart-coupons-for-woocommerce-pro' ); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="wt-importer-loading loading">
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
var wt_sc_import_file_id = <?php echo esc_html( absint( $this->id ) ); ?>;
var wt_sc_import_file='<?php echo esc_html( $file ); ?>';
var wt_sc_import_header='<?php echo json_encode($header); ?>';
var wt_sc_import_map_head='<?php echo json_encode($map_head); ?>';
var wt_sc_import_position_array=JSON.parse('<?php echo json_encode($position_array); ?>');
var wt_sc_import_file_handle='<?php echo esc_html($handle); ?>';
var wt_sc_email_on_import='<?php  echo esc_html($this->email_coupon_on_import); ?>';
var wt_sc_import_nonce='<?php echo esc_html(wp_create_nonce("wt_smart_coupons_import_nonce"));?>';
var wt_sc_import_count=parseInt(<?php echo esc_html($import_count); ?>);
var wt_sc_done_count=0;

var wt_sc_completed_msg='<?php esc_html_e("Import completed!", "wt-smart-coupons-for-woocommerce-pro"); ?>';
var wt_sc_go_to_msg='<?php esc_html_e("Go to 'Import coupon'", "wt-smart-coupons-for-woocommerce-pro"); ?>';
var wbte_sc_go_to_all_coupons = '<?php esc_html_e( "Go to Coupons", "wt-smart-coupons-for-woocommerce-pro" ); ?>';
var wbte_sc_all_coupons_url = '<?php echo esc_attr( admin_url( 'edit.php?post_type=shop_coupon' ) ); ?>';
var wt_sc_import_home_url='<?php echo esc_attr(admin_url('admin.php?page='.$this->module_id)); ?>';
var wt_sc_row_parsed=0;
jQuery(document).ready(function($)
{
    var parse_rows = wt_sc_import_position_array.shift();
    wt_sc_import_rows(parse_rows[0], parse_rows[1]);
});
function wt_sc_import_done()
{
    jQuery('.wt-importer-loading').removeClass('loading');
    jQuery( '.wt-importer-loading td' ).append( wt_sc_completed_msg + '<br /><a style="font-size:12px; font-weight:normal; text-decoration:underline; cursor:pointer;" href="' + wt_sc_import_home_url + '">' + wt_sc_go_to_msg + '</a> | <a style="font-size:12px; font-weight:normal; text-decoration:underline; cursor:pointer;" href="' + wbte_sc_all_coupons_url + '">' + wbte_sc_go_to_all_coupons + '</a>' );

    jQuery.ajax({
        url         : WTSmartCouponAdminOBJ.ajaxurl,
        data        : { action : 'wbte_import_finished', wt_import_id: wt_sc_import_file_id, _wpnonce: wt_sc_import_nonce },
        type        : 'POST'
    });
}
function wt_sc_import_rows(start_pos, end_pos)
{
    var data = {
        action              :   'wt_import_csv_coupon_rows',
        file                :   wt_sc_import_file,
        header              :   wt_sc_import_header,
        map_head            :   wt_sc_import_map_head,
        handle              :   wt_sc_import_file_handle,
        start_position      :   start_pos,
        end_position        :   end_pos,
        email_on_import     :   wt_sc_email_on_import,
        _wpnonce            :   wt_sc_import_nonce,
        row_parsed          :   wt_sc_row_parsed,
       
    };
    return jQuery.ajax({
        url         : WTSmartCouponAdminOBJ.ajaxurl,
        data        : data,
        dataType    : 'JSON',
        type        : 'POST',
        success     : function(results)
        {
            if(results)
            {
                jQuery.each(results,function( index, result ) {
                    if( result.error == true ) {
                        jQuery('#wt_smar_coupon_import_progress tbody').append( '<tr id="row-' + result.row + '" ><td><mark class="result wt-fail " title="' + result.status + '"> <span class="dashicons dashicons-no-alt"></span></mark></td><td class="row">' + result.row + '</td><td> - </td><td>' + result.coupon_name + '</td><td class="reason">' + result.status + '</td></tr>' );

                    } else {
                        jQuery('#wt_smar_coupon_import_progress tbody').append( '<tr id="row-' + result.row + '" ><td><mark class="result wt-success" title="' + result.status + '">  <span class="dashicons dashicons-yes"></span> </mark></td><td class="row">' + result.row + '</td><td>' + result.coupon_id+ '</td><td>' + result.coupon_name + '</td><td class="reason">' + result.status + '</td></tr>' );
                    }
                    wt_sc_row_parsed=result.row;
                });
                wt_sc_done_count++;

                if(wt_sc_done_count == wt_sc_import_count )
                {
                    wt_sc_import_done();
                }else
                {
                    parse_rows = wt_sc_import_position_array.shift();
                    wt_sc_row_parsed++;
                    wt_sc_import_rows(parse_rows[0], parse_rows[1]);
                }
            }
        }
    });
}  
</script>