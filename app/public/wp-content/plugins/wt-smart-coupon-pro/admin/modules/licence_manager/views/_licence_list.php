<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}	
?>
<table class="wp-list-table widefat fixed striped wt_sc_licence_table">
	<thead>
		<tr>
			<th><?php _e('Licence key', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
			<th style="width:150px;"><?php _e('Email', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
			<th style="width:100px;"><?php _e('Status', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
			<th><?php _e('Product', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
			<th style="width:150px;"><?php _e('Actions', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if(count($licence_data_arr)>0)
		{
			$i=0;
			foreach ($licence_data_arr as $product_slug=>$licence_data)
			{
				if(!is_array($licence_data)) {
					continue;
				}

				$i++;
				?>
				<tr class="licence_tr" data-product="<?php echo $product_slug; ?>">
					<td>
						<?php echo $this->mask_licence_key($licence_data['key']); ?>						
					</td>
					<td><?php echo $licence_data['email']; ?></td>
					<td class="status_td" data-status="<?php echo $licence_data['status'];?>"><?php echo $this->get_status_label($licence_data['status']); ?></td>
					<td>
						<?php
						echo $licence_data['products'];
						?>	
					</td>
					<td class="action_td">
						<?php 
						$button_label=( 'active' === $licence_data['status'] ? 'Deactivate' : 'Delete');
						$button_action=( 'active' === $licence_data['status'] ? 'deactivate' : 'delete');
						?>
						<button type="button" class="button button-secondary wt_sc_licence_deactivate_btn" data-product="<?php echo $product_slug; ?>" data-action="<?php echo $button_action;?>"><?php _e($button_label, 'wt-smart-coupons-for-woocommerce-pro');?></button>				
					</td>
				</tr>
				<?php
			}
		}else
		{
			?>
			<tr>
				<td colspan="5" style="text-align:center;"><?php _e("No Licence details found.", 'wt-smart-coupons-for-woocommerce-pro'); ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>