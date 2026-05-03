<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt-sc-tab-content" data-id="<?php echo $target_id;?>">
	<h3><?php _e('Debug','wt-smart-coupons-for-woocommerce-pro');?></h3>
	<p><?php _e('Caution: Settings here are only for advanced users.','wt-smart-coupons-for-woocommerce-pro');?></p>
	<form method="post" style="border-bottom:dashed 1px #ccc;">
		<?php
	    // Set nonce:
	    if(function_exists('wp_nonce_field'))
	    {
	        wp_nonce_field(WT_SC_PLUGIN_NAME);
	    }
	    ?>
		<table class="wt-sc-form-table">
			<?php
	        $wt_sc_public_modules=get_option('wt_sc_public_modules');
	        if($wt_sc_public_modules===false)
	        {
	            $wt_sc_public_modules=array();
	        }
	        ?>
	        <tr valign="top">
	            <th scope="row">Public modules</th>
	            <td>
	                <?php
	                foreach($wt_sc_public_modules as $k=>$v)
	                {
	                	$is_mu = in_array($k, Wt_Smart_Coupon_Public::$mu_modules) ? true : false;	                    
	                    echo '<input type="checkbox" name="wt_sc_public_modules['.$k.']" value="1" '.(1 === $v ? 'checked' : '').' '.($is_mu ? 'disabled="disabled"' : '').' /> ';
	                    echo $k;
	                    echo ($is_mu ? '<span style="display:inline-block; margin-left:15px; font-style:italic; color:#ccc;">'.esc_html__('Must use module', 'wt-smart-coupons-for-woocommerce-pro').'</span>' : '');
	                    echo '<br />';
	                }
	                ?>
	            </td>
	        </tr>
			<?php
	        $wt_sc_common_modules=get_option('wt_sc_common_modules');
	        if($wt_sc_common_modules===false)
	        {
	            $wt_sc_common_modules=array();
	        }
	        ?>
	        <tr valign="top">
	            <th scope="row">Common modules</th>
	            <td>
	                <?php
	                foreach($wt_sc_common_modules as $k=>$v)
	                {
	                    $is_mu = in_array($k, Wt_Smart_Coupon_Common::$mu_modules) ? true : false;	                    
	                    echo '<input type="checkbox" name="wt_sc_common_modules['.$k.']" value="1" '.(1 === $v ? 'checked' : '').' '.($is_mu ? 'disabled="disabled"' : '').' /> ';
	                    echo $k;
	                    echo ($is_mu ? '<span style="display:inline-block; margin-left:15px; font-style:italic; color:#ccc;">'.esc_html__('Must use module', 'wt-smart-coupons-for-woocommerce-pro').'</span>' : '');
	                    echo '<br />';
	                }
	                ?>
	            </td>
	        </tr>
	        <?php
	        $wt_sc_admin_modules=get_option('wt_sc_admin_modules');
	        if($wt_sc_admin_modules===false)
	        {
	            $wt_sc_admin_modules=array();
	        }
	        ?>
	        <tr valign="top">
	            <th scope="row">Admin modules</th>
	            <td>
	                <?php
	                foreach($wt_sc_admin_modules as $k=>$v)
	                {            
	                	$is_mu = in_array($k, Wt_Smart_Coupon_Admin::$mu_modules) ? true : false;	                    
	                    echo '<input type="checkbox" name="wt_sc_admin_modules['.$k.']" value="1" '.(1 === $v ? 'checked' : '').' '.($is_mu ? 'disabled="disabled"' : '').' /> ';
	                    echo $k;
	                    echo ($is_mu ? '<span style="display:inline-block; margin-left:15px; font-style:italic; color:#ccc;">'.esc_html__('Must use module', 'wt-smart-coupons-for-woocommerce-pro').'</span>' : '');
	                    echo '<br />';
	                }
	                ?>
	            </td>
	        </tr>
	        <tr valign="top">
	            <th scope="row">&nbsp;</th>
	            <td>
	                <input type="submit" name="wt_sc_admin_modules_btn" value="Save" class="button-primary">
	            </td>
	        </tr>	
		</table>
	</form>
<?php
//advanced settings form fields for module
do_action('wt_sc_module_settings_debug');
?>
</div>