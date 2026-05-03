<?php
/**
 * 	Notification settings tab content
 * 
 *  @since 2.0.8
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}

?>
<style>
.wt_sc_notif_list_tb{ margin-bottom:25px; }
.wt_sc_notif_edit_form .wt_sc_popup_body, .wt_sc_notif_view_popup .wt_sc_popup_body{ text-align:left; padding:20px 15px; }
.wt_sc_notif_row{ float:left; width:100%; margin-top:10px; box-sizing:border-box; }
.wt_sc_notif_row_label{ float:left; width:100%; margin-bottom:5px; font-weight:bold; text-align:left; }
.wt_sc_notif_row textarea{ min-height:150px; }
.wt_sc_notif_message_dv{ display:inline-block; }
.wt_sc_notif_def_badge{ color:#fff; background:#999; }
.wt_sc_notif_cus_badge{ color:#fff; background:#4bb3e8; }
.wt_sc_notif_hid_badge{ color:#fff; background:#f7bc62; }
.wt_sc_notif_avail_values{ float:left; width:100%;}
.wt_sc_notif_avail_value_item{ float:left; width:100%; margin-bottom:5px;}
.wt_sc_notif_avail_value_name{ display:inline-block; background:#ccc; color:#000; padding:2px 5px; }
.wt_sc_notif_avail_value_desc{ display:inline-block; margin-left:5px; padding:2px 5px; }
.wt_sc_notif_msg_desc{ margin:0px; padding:0px; }
.wt_sc_notif_data_div{ display:none; }
.wt_sc_popup.wt_sc_notif_edit_form .wt_sc_notif_avail_values .wt_sc_notif_avail_value_name{ cursor:pointer; }

.wt_sc_nothing_to_show{ display:inline-block; border:solid 1px #f7e7c3; padding:1px 3px; font-size:12px; background:#fff3cd; color:#694e03; }
.wt_sc_notif_status_locked_msg{ display:inline-block; margin-left:5px; font-style:italic; }
.wt_sc_notif_type_success{ background:#dff0d8; color:#65969a; }
.wt_sc_notif_type_warning{ background:#fcf8e3; color:#a39269; }
.wt_sc_notif_type_info{ background:#d9edf7; color:#7c8aac; }
</style>

<!-- Message edit popup -->
<div class="wt_sc_notif_edit_form wt_sc_popup" style="width:900px;">
    <div class="wt_sc_popup_hd">
        <div class="wt_sc_popup_title"><?php _e('Customize the message', 'wt-smart-coupons-for-woocommerce-pro');?></div>
        <div class="wt_sc_popup_close">X</div>
    </div>
    <div class="wt_sc_popup_body">
        <form>
        	<div class="wt_sc_notif_row" style="margin-bottom:15px;">
        		<label class="wt_sc_notif_row_label">
        			<?php _e('Message description', 'wt-smart-coupons-for-woocommerce-pro');?>
        		</label>
        		<p class="wt_sc_notif_msg_desc"><!-- About message will come here --></p>
        	</div>
        	<div class="wt_sc_notif_row">
        		<label class="wt_sc_notif_row_label">
        			<?php _e('Available placeholders', 'wt-smart-coupons-for-woocommerce-pro');?>
        		</label>
        		<div class="wt_sc_notif_avail_values"><!-- Available values will come here --></div>
        	</div>
        	<div class="wt_sc_notif_row">
        		<label class="wt_sc_notif_row_label">
        			<?php _e('Message', 'wt-smart-coupons-for-woocommerce-pro');?>
        		</label>
        		<textarea class="wt_sc_textarea wt_sc_notif_msg_editor" placeholder="<?php esc_attr_e('Custom message', 'wt-smart-coupons-for-woocommerce-pro');?>"></textarea>
        	</div>
        	<div class="wt_sc_notif_row">
        		<label class="wt_sc_notif_row_label">
        			<?php _e('Show/Hide', 'wt-smart-coupons-for-woocommerce-pro');?>
        		</label>
        		<label class="wt_sc_switch">
                    <input type="hidden" name="wt_sc_notif_message_status_hidden" value="1">
                    <input type="checkbox" name="wt_sc_notif_message_status" value="1" class="wt_sc_slide_switch">                                   
                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                </label>
                <span class="wt_sc_notif_status_locked_msg"></span>
        	</div>
        	<div class="wt_sc_notif_row">
        		<input type="hidden" name="wt_sc_notif_message_key" value="">
        		<button type="button" class="button button-primary wt_sc_notif_message_btn" style="float:right;"><?php _e('Save', 'wt-smart-coupons-for-woocommerce-pro');?></button>
        		<span class="spinner" style="margin-top:6px;"></span>
        	</div>
        </form>
    </div>
</div>
<!-- Message edit popup -->


<!-- Message view popup -->
<div class="wt_sc_notif_view_popup wt_sc_popup" style="width:900px;">
    <div class="wt_sc_popup_hd">
        <div class="wt_sc_popup_title"><?php _e('Message info', 'wt-smart-coupons-for-woocommerce-pro');?></div>
        <div class="wt_sc_popup_close">X</div>
    </div>
    <div class="wt_sc_popup_body">
    	<label class="wt_sc_notif_row_label">
			<?php _e('Message description', 'wt-smart-coupons-for-woocommerce-pro');?>
		</label>   
        <p class="wt_sc_notif_msg_desc" style="margin-bottom:15px;">><!-- About message will come here --></p>

        <div class="wt_sc_notif_row">
        	<label class="wt_sc_notif_row_label">
        		<?php _e('Custom message', 'wt-smart-coupons-for-woocommerce-pro');?>      			
        	</label>
        	<div class="wt_sc_notif_cus_message_dv"></div>
        </div>
        <div class="wt_sc_notif_row">
        	<label class="wt_sc_notif_row_label">
        		<?php _e('Default message', 'wt-smart-coupons-for-woocommerce-pro');?>      			
        	</label>
        	<div class="wt_sc_notif_def_message_dv"></div>
        </div>
        <div class="wt_sc_notif_row">
    		<label class="wt_sc_notif_row_label">
    			<?php _e('Available placeholders', 'wt-smart-coupons-for-woocommerce-pro');?>
    		</label>
    		<div class="wt_sc_notif_avail_values"></div>
    	</div>
    	<div class="wt_sc_notif_row">
    		<label class="wt_sc_notif_row_label">
    			<?php _e('Available filters for advanced customization', 'wt-smart-coupons-for-woocommerce-pro');?>
    		</label>
    		<div class="wt_sc_notif_avail_filters"></div>
    	</div>
    </div>
</div>
<!-- Message view popup -->


<div class="wt-sc-inner-content">
	<p>
		<?php _e("Use the configuration panel to customize the coupon related messages. Default text will be overridden when custom text exists.", 'wt-smart-coupons-for-woocommerce-pro');?>
		<br />
		<?php _e("Enable/Disable the status to hide/show messages.", 'wt-smart-coupons-for-woocommerce-pro');?>
    </p>

    <table class="wp-list-table widefat fixed striped wt_sc_notif_list_tb">
    	<thead>
			<tr>
				<th style="width:30px;">
					<?php _e("No.", 'wt-smart-coupons-for-woocommerce-pro'); ?>
				</th>			
				<th><?php _e("Default message", 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
				<th><?php _e("Custom message", 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
				<th style="width:100px; text-align:center;">
					<?php _e("State", 'wt-smart-coupons-for-woocommerce-pro'); ?>
					<?php echo Wt_Smart_Coupon_Admin::set_tooltip('notifications_status', $view_params['module_id']);?>
				</th>
				<th style="width:50px; text-align:center;"><?php _e("Type", 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
				<th style="width:150px; text-align:center;">
					<?php _e("Actions", 'wt-smart-coupons-for-woocommerce-pro'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$i = 0;

			$message_list = (isset($view_params['message_list']) && is_array($view_params['message_list']) ? $view_params['message_list'] : array());
			$state_icons = (isset($view_params['state_icons']) && is_array($view_params['state_icons']) ? $view_params['state_icons'] : array());

			foreach($message_list as $message_key => $message_item)
			{
				$i++;
				?>
				<tr>
					<td>
						<input type="hidden" value="<?php echo esc_attr($message_key);?>" class="wt_sc_notif_message_key">
						<input type="hidden" value="<?php echo esc_attr(isset($message_item['status_locked']) ? 1 : 0);?>" class="wt_sc_notif_status_locked">
						<?php echo $i;?>						
					</td>
					<td>
						<div class="wt_sc_notif_def_message_dv"><?php echo wp_kses_post($message_item['message']); ?></div>
					</td>
					<td>
						<div class="wt_sc_notif_cus_message_dv"><?php echo wp_kses_post(isset($message_item['custom_message']) ? $message_item['custom_message'] : ''); ?></div>
					</td>
					<td style="text-align:center;" class="wt_sc_notif_status_td">
						<?php 
						if(isset($message_item['custom_message']) && 1 === absint($message_item['status']))
						{
							echo wp_kses_post($state_icons['custom']);

						}elseif(!isset($message_item['custom_message']) && 1 === absint($message_item['status']))
						{
							echo wp_kses_post($state_icons['default']);
							
						}elseif(0 === absint($message_item['status']))
						{
							echo wp_kses_post($state_icons['hidden']);
						} 
						?>
					</td>
					<td style="text-align:center;">
						<span class="wt_sc_badge wt_sc_notif_type_<?php echo esc_attr($message_item['group']); ?>"><?php esc_html_e($message_item['group'], 'wt-smart-coupons-for-woocommerce-pro'); ?></span>
					</td>
					<td style="text-align:center;">
						<!-- data for popup -->
						<div class="wt_sc_notif_data_div" data-status="<?php echo esc_attr( absint( $message_item['status'] ) ); ?>">
			
							<div class="wt_sc_notif_message_desc">
								<?php echo wp_kses_post($message_item['description']); ?>
							</div>

							<div class="wt_sc_notif_avail_values wt_sc_notif_avail_filters">
				    			<?php 
				    			foreach($message_item['available_filters'] as $available_filter => $available_filter_data)
				    			{
				    				?>
				    				<div class="wt_sc_notif_avail_value_item">
					    				<span class="wt_sc_notif_avail_value_name"><?php echo esc_html($available_filter);?></span>: <span class="wt_sc_notif_avail_value_desc"><?php echo wp_kses_post($available_filter_data);?></span>
					    			</div>
				    				<?php
				    			}
				    			?>				    			
				    		</div>
				    		<div class="wt_sc_notif_avail_values wt_sc_notif_avail_placeholders">
				    			<?php 
				    			foreach($message_item['supported_placeholders'] as $available_placeholder => $available_placeholder_data)
				    			{
				    				?>
				    				<div class="wt_sc_notif_avail_value_item">
					    				<span class="wt_sc_notif_avail_value_name" title="<?php esc_attr_e("Click to insert", 'wt-smart-coupons-for-woocommerce-pro');?>">{<?php echo esc_html($available_placeholder);?>}</span>: <span class="wt_sc_notif_avail_value_desc"><?php echo wp_kses_post($available_placeholder_data);?></span>
					    			</div>
				    				<?php
				    			}
				    			?>				    			
				    		</div>
				    		<div class="wt_sc_notif_status_locked_msg">
				    			<?php echo wp_kses_post(isset($message_item['status_locked']) ? $message_item['status_locked'] : '');?>
				    		</div>
						</div>
						<!-- data for popup -->

						<button type="button" class="button button-secondary wt_sc_notif_view_btn" data-wt_sc_popup="wt_sc_notif_view_popup"><?php esc_html_e('View', 'wt-smart-coupons-for-woocommerce-pro') ?></button>
						<button type="button" class="button button-secondary wt_sc_notif_edit_btn" data-wt_sc_popup="wt_sc_notif_edit_form"><?php esc_html_e('Edit', 'wt-smart-coupons-for-woocommerce-pro') ?></button>
					</td>
				</tr>
				<?php	
			}
			?>
		</tbody>
    </table>
</div>