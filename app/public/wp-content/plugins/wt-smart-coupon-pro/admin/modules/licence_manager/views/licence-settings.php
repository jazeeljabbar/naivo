<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
$products=(isset($view_params['products']) ? $view_params['products'] : array());
?>
<style type="text/css">
.wt_sc_licence_container{ padding-bottom:20px; }
.wt_sc_licence_form_table td{ padding-bottom:20px; width:200px; }
.wt_sc_licence_form_table input[type="text"], .wt_sc_licence_form_table select{ width:100%; display:block; border:solid 1px #ccd0d4;}
.wt_sc_licence_form_table label{ width:100%; display:block; font-weight:bold; }
.wt_sc_licence_table{ margin-bottom:20px; }
.wt_sc_licence_form_table{ width:auto; }
</style>
<div class="wt-sc-tab-content wt_sc_licence_container" data-id="<?php echo $target_id;?>">
	<h3><span><?php _e('Activate new licence', 'wt-smart-coupons-for-woocommerce-pro');?></span></h3>
	<form method="post" id="wt_sc_licence_manager_form">
		<?php
        // Set nonce:
        if (function_exists('wp_nonce_field'))
        {
            wp_nonce_field(WT_SC_PLUGIN_ID);
        }
        ?>
        <input type="hidden" name="wt_sc_licence_manager_action" value="activate">
        <input type="hidden" name="action" value="wt_sc_licence_manager_ajax">
        <table class="wp-list-table widefat fixed striped wt_sc_licence_form_table">
        	<tr>
				<td style="width:350px;">
					<label><?php _e('Product:', 'wt-smart-coupons-for-woocommerce-pro');?></label>
					<select name="wt_sc_licence_product">
						<?php
						if(is_array($products))
						{
							foreach ($products as $product_slug=>$product)
							{
								?>
								<option value="<?php echo $product_slug;?>">
									<?php echo $product['product_display_name'];?>
								</option>
								<?php
							}
						}
						?>
					</select>
				</td>
				<td>
					<label><?php _e('Licence key:', 'wt-smart-coupons-for-woocommerce-pro');?></label>
					<input type="text" name="wt_sc_licence_key">
				</td>
				<td>
					<label><?php _e('Email:', 'wt-smart-coupons-for-woocommerce-pro');?></label>
					<input type="text" name="wt_sc_licence_email">
				</td>
				<td style="width:100px;">
					<label>&nbsp;</label>
					<button class="button button-primary wt_sc_licence_activate_btn"><?php _e('Activate', 'wt-smart-coupons-for-woocommerce-pro');?></button>
				</td>
			</tr>
        </table>
	</form>
	<h3><span><?php _e('Licence details', 'wt-smart-coupons-for-woocommerce-pro');?></span></h3>
	<div class="wt_sc_licence_list_container">
		
	</div>
</div>