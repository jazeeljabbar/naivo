<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

if(is_array($args))
{
	foreach ($args as $key => $value)
	{
		$tr_id=(isset($value['tr_id']) ? ' id="'.$value['tr_id'].'" ' : '');
		$tr_class=(isset($value['tr_class']) ? $value['tr_class'] : '');

		$type=(isset($value['type']) ? $value['type'] : 'text');
		$field_group_attr=(isset($value['field_group']) ? ' data-field-group="'.esc_attr($value['field_group']).'" ' : '');
		$tr_class.=(isset($value['field_group']) ? ' wt_sc_field_group_children ' : ''); //add an extra class to tr when field grouping enabled

		$after_form_field_html=(isset($value['after_form_field_html']) ? $value['after_form_field_html'] : ''); /* after form field `td` */
		$after_form_field=(isset($value['after_form_field']) ? $value['after_form_field'] : ''); /* after form field */
		$before_form_field=(isset($value['before_form_field']) ? $value['before_form_field'] : '');

		$td_additional_class = isset( $value['td_additional_class'] ) ? ' class="' . $value['td_additional_class'] . '" ' : '' ;

		/** 
		*	conditional help texts 
		*	!!Important: Using OR mixed with AND then add OR conditions first.
		*/
		$conditional_help_html='';
		if(isset($value['help_text_conditional']) && is_array($value['help_text_conditional']))
		{		
			foreach ($value['help_text_conditional'] as $help_text_config)
			{
				if(is_array($help_text_config))
				{
					$condition_attr='';
					if(is_array($help_text_config['condition']))
					{
						$previous_type=''; /* this for avoiding fields without glue */
						foreach ($help_text_config['condition'] as $condition)
						{
							if(is_array($condition))
							{
								if($previous_type!='field')
								{
									$condition_attr.='['.$condition['field'].'='.$condition['value'].']';
									$previous_type='field';
								}
							}else
							{
								if(is_string($condition))
								{
									$condition=strtoupper($condition);
									if(($condition=='AND' || $condition=='OR') && $previous_type!='glue')
									{
										$condition_attr.='['.$condition.']';
										$previous_type='glue';
									}
								}
							}
						}
					}			
					$conditional_help_html.='<span class="wt_sc_form_help wt_sc_conditional_help_text" data-sc-help-condition="'.esc_attr($condition_attr).'">'.wp_kses_post($help_text_config['help_text']).'</span>';
				}	
			}
		}

		if($type=='field_group_head') //heading for field group
		{
			$visibility=(isset($value['show_on_default']) ? $value['show_on_default'] : 0);
		?>
			<tr <?php echo $tr_id.$field_group_attr;?> class="<?php echo esc_attr($tr_class);?>">
				<td colspan="3" class="wt_sc_field_group">
					<div class="wt_sc_field_group_hd">
						<?php echo isset($value['head']) ? $value['head'] : ''; ?>
						<div class="wt_sc_field_group_toggle_btn" data-id="<?php echo esc_attr(isset($value['group_id']) ? $value['group_id'] : ''); ?>" data-visibility="<?php echo esc_attr($visibility); ?>"><span class="dashicons dashicons-arrow-<?php echo ($visibility==1 ? 'down' : 'right'); ?>"></span></div>
					</div>
					<div class="wt_sc_field_group_content">
						<table></table>
					</div>
				</td>
			</tr>
		<?php
		}else
		{

			if(isset($value['field_name']))
			{
				$field_name=$value['field_name'];
			}else{
				if(isset($value['parent_option']))
				{
					$field_name=$value['parent_option'].'['.$value['option_name'].']';
				}else{
					$field_name=$value['option_name'];
				}
			}

			$field_id=isset($value['field_id']) ? $value['field_id'] : $field_name;

			$form_toggler_p_class="";
			$form_toggler_register="";
			$form_toggler_child="";
			if(isset($value['form_toggler']))
			{
				if($value['form_toggler']['type']=='parent')
				{
					$form_toggler_p_class="wt_sc_form_toggle";
					$form_toggler_register=' wt_sc_form_toggle-target="'.esc_attr($value['form_toggler']['target']).'"';
				}
				elseif($value['form_toggler']['type']=='child')
				{
					$form_toggler_child=' wt_sc_form_toggle-id="'.esc_attr($value['form_toggler']['id']).'" wt_sc_form_toggle-val="'.esc_attr($value['form_toggler']['val']).'" '.(isset($value['form_toggler']['check']) ? 'wt_sc_form_toggle-check="'.esc_attr($value['form_toggler']['check']).'"' : '').(isset($value['form_toggler']['level']) ? ' wt_sc_form_toggle-level="'.esc_attr($value['form_toggler']['level']).'"' : '');	
				}else
				{
					$form_toggler_child=' wt_sc_form_toggle-id="'.esc_attr($value['form_toggler']['id']).'" wt_sc_form_toggle-val="'.esc_attr($value['form_toggler']['val']).'" '.(isset($value['form_toggler']['check']) ? 'wt_sc_form_toggle-check="'.esc_attr($value['form_toggler']['check']).'"' : '').(isset($value['form_toggler']['level']) ? ' wt_sc_form_toggle-level="'.esc_attr($value['form_toggler']['level']).'"' : '');	
					$form_toggler_p_class="wt_sc_form_toggle";
					$form_toggler_register=' wt_sc_form_toggle-target="'.esc_attr($value['form_toggler']['target']).'"';				
				}
				
			}

			$fld_attr=(isset($value['attr']) ? $value['attr'] : '');
			$css_class=(isset($value['css_class']) ? esc_attr($value['css_class']) : '');
			$field_only=(isset($value['field_only']) ? $value['field_only'] : false);
			$non_field=(isset($value['non_field']) ? $value['non_field'] : false);
			$mandatory=(boolean) (isset($value['mandatory']) ? $value['mandatory'] : false);
			if($mandatory)
			{
				$fld_attr.=' required="required"';
				$required_msg=(isset($value['required_msg']) ? $value['required_msg'] : '');
				if(""!=$required_msg)
				{
					$fld_attr.=' data-required-msg="'.esc_attr($required_msg).'"';
				}
			}
			$field_name=esc_attr($field_name);
			$field_id=esc_attr($field_id);


			if('custom_preset' === $type)
			{
				$type='select';
				$after_form_field.='<input type="text" class="wt_sc_custom_and_preset_text" name="'.$field_name.'" value="" />';
				$field_name=$field_name.'_preset';
				$css_class.=' wt_sc_custom_and_preset';
				$fld_attr.=' data-custom-trigger-val="'.esc_attr(isset($value['trigger_val']) ? $value['trigger_val'] : '').'"';
			}


			if(false === $field_only)
			{
				$tooltip_html=self::set_tooltip($field_name, $base);
				?>
				<tr valign="top" <?php echo $tr_id.$field_group_attr;?> <?php echo $form_toggler_child; ?> class="<?php echo $tr_class;?>">
			        <th scope="row" >
			        	<label for="<?php echo $field_name;?>" style="margin-left:10px;">
			        		<?php echo isset($value['label']) ? $value['label'] : ''; ?><?php echo ($mandatory ? '<span class="wt_sc_required_field">*</span>' : ''); ?><?php echo $tooltip_html;?>	
			        	</label>
			        </th>
			        <td <?php echo $td_additional_class; ?> >
				<?php
			}
			
			if(true === $non_field) // not form field type. Eg: plain text
			{
				if('plaintext' === $type)
				{
					echo (isset($value['text']) ? $value['text'] : '');
				}
			}else
			{
				echo $before_form_field;

				$parent_option=(isset($value['parent_option']) ? $value['parent_option'] : '');
        		
        		if("" !== $parent_option)
        		{
        			$vl=Wt_Smart_Coupon::get_option($parent_option, $base);
        			$vl=(isset($vl[$value['option_name']]) ? $vl[$value['option_name']] : '');
        		}else{
        			$vl=Wt_Smart_Coupon::get_option($value['option_name'], $base);
        		}

        		$vl=is_string($vl) ? stripslashes($vl) : $vl;
	        	
	        	if('text' === $type || 'number' === $type || 'password' === $type)
				{
	        	?>
	            	<input type="<?php echo esc_attr($type); ?>" <?php echo $fld_attr;?> class="<?php echo $css_class;?>" name="<?php echo $field_name;?>" value="<?php echo esc_attr($vl);?>" />
	            <?php
	        	}
	        	elseif('textarea' === $type)
				{
					?>
	            		<textarea <?php echo $fld_attr;?> class="<?php echo $css_class;?>" name="<?php echo $field_name;?>"><?php echo esc_textarea($vl);?></textarea>
	            	<?php
				}elseif('checkbox' === $type) //checkbox
				{
					$field_vl = isset($value['field_vl']) ? $value['field_vl'] : "1";
					$checkbox_label = isset($value['checkbox_label']) ? $value['checkbox_label'] : "";
					?>
						<input class="<?php echo $css_class;?> <?php echo $form_toggler_p_class;?>" type="checkbox" value="<?php echo esc_attr($field_vl);?>" id="<?php echo $field_id;?>" name="<?php echo $field_name;?>" <?php echo ($field_vl==$vl ? ' checked="checked"' : '') ?> <?php echo $form_toggler_register;?> <?php echo $fld_attr;?>> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					if($checkbox_label)
					{
						?>
						<label for="<?php echo $field_id;?>"><?php echo wp_kses_post($checkbox_label);?></label> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
					}
				}
				elseif( 'checkbox_list' === $type ) //checkbox list
				{
					$checkbox_fields = isset( $value['checkbox_fields'] ) ? $value['checkbox_fields'] : array();
					
					foreach ( $checkbox_fields as $checkbox_vl => $checkbox_label ) 
					{
						?>
						<span class="wbte_sc_checkbox_list_item"><input type="checkbox" id="<?php echo esc_attr( $field_id . '_' . $checkbox_vl ); ?>" name="<?php echo esc_attr( $field_name ); ?>[]" class="<?php echo esc_attr( $css_class ); ?> <?php echo esc_attr( $form_toggler_p_class ); ?>" <?php echo esc_attr( $form_toggler_register ); ?> value="<?php echo esc_attr( $checkbox_vl ); ?>" <?php echo in_array( $checkbox_vl, $vl ) ? ' checked="checked"' : ''; ?> <?php echo esc_attr( $fld_attr ); ?> /> <label for="<?php echo esc_attr( $field_id . '_' . $checkbox_vl ); ?>"><?php echo esc_html( $checkbox_label ); ?></label> </span>
						&nbsp;&nbsp;
						<?php
					}
					
				}
				elseif('radio' === $type) //radio button
				{
					$radio_fields=isset($value['radio_fields']) ? $value['radio_fields'] : array();
					
					if(isset($value['val_type']) && 'boolean' === $value['val_type'] && is_string($vl))
					{
						$vl = wc_string_to_bool($vl);
					}

					foreach ($radio_fields as $rad_vl=>$rad_label) 
					{
					?>
						<span class="wt_sc_radio_list_item"><input type="radio" id="<?php echo esc_attr($field_id.'_'.$rad_vl);?>" name="<?php echo $field_name;?>" class="<?php echo $css_class;?> <?php echo $form_toggler_p_class;?>" <?php echo $form_toggler_register;?> value="<?php echo esc_attr($rad_vl);?>" <?php echo ($vl==$rad_vl) ? ' checked="checked"' : ''; ?> <?php echo $fld_attr;?> /> <?php echo esc_html($rad_label); ?> </span>
						&nbsp;&nbsp;
					<?php
					}
					
				}elseif('uploader' === $type) //uploader
				{
					$upload_btn_attr=(isset($value['uploader_title']) ? ' data-uploader_title="'.esc_attr($value['uploader_title']).'"' : '');
					$upload_btn_attr.=(isset($value['uploader_button_text']) ? ' data-uploader_button_text="'.esc_attr($value['uploader_button_text']).'"' : '');
					$upload_btn_attr.=(isset($value['allowed_file_types']) ? ' data-allowed_file_types="'.esc_attr($value['allowed_file_types']).'"' : '');
					?>
					<div class="wt_sc_file_attacher_dv">
			            <input id="<?php echo $field_id; ?>"  type="text" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($vl); ?>" <?php echo $fld_attr;?>/>
						
						<input type="button" name="upload_image" class="wt_sc_button button button-primary wt_sc_file_attacher" wt_sc_file_attacher_target="#<?php echo $field_name; ?>" value="<?php _e('Upload','wt-smart-coupons-for-woocommerce-pro'); ?>" <?php echo $upload_btn_attr;?> />
					</div>
					<img class="wt_sc_image_preview_small" src="<?php echo esc_attr($vl ? $vl : Wt_Smart_Coupon::$no_image); ?>" />
					<?php
				}elseif('select' === $type || 'ajax_select' === $type) //select
				{
					$select_fields=isset($value['select_fields']) ? $value['select_fields'] : array();
					?>
					<select name="<?php echo $field_name;?>" id="<?php echo $field_id;?>" class="<?php echo $css_class;?> <?php echo $form_toggler_p_class;?>" <?php echo $form_toggler_register;?> <?php echo $fld_attr;?>>
					<?php
					foreach ($select_fields as $sel_vl=>$sel_label) 
					{
						$selected_attr='';
						if((is_array($vl) && in_array($sel_vl, $vl)) || (is_string($vl) && $vl==$sel_vl))
						{
							$selected_attr=' selected="selected"';
						}
					?>
						<option value="<?php echo esc_attr($sel_vl);?>" <?php echo $selected_attr; ?>><?php echo esc_html($sel_label); ?></option>
					<?php
					}
					?>
					</select>
					<?php
				}elseif('multi_select' === $type)
				{
					$sele_vals=(isset($value['select_fields']) && is_array($value['select_fields']) ? $value['select_fields'] : array());
					$vl=(is_array($vl) ? $vl : array($vl));
					?>
					<div class="wt_sc_select_multi">
						<select multiple="multiple" name="<?php echo esc_attr($field_name);?>[]" id="<?php echo esc_attr($field_id);?>" class="wc-enhanced-select <?php echo $css_class;?> <?php echo $form_toggler_p_class;?>" <?php echo $form_toggler_register;?> <?php echo $fld_attr;?>>
							<?php
							foreach($sele_vals as $sele_val=>$sele_lbl) 
							{
							?>
	                      		<option value="<?php echo esc_attr($sele_val);?>" <?php echo (in_array($sele_val,$vl) ? 'selected' : ''); ?>> <?php echo esc_html($sele_lbl);?> </option>
	                   		<?php
	                    	}
	                   		?>
                   		</select>
                   	</div>
                   	<?php
				}elseif('color' === $type)
				{
					?>
					<div class="wt_sc_coupon_color_form_element <?php echo $css_class;?> <?php echo $form_toggler_p_class;?>" <?php echo $form_toggler_register;?> <?php echo $fld_attr;?>>
                        <input name="<?php echo esc_attr($field_name);?>" id="<?php echo esc_attr($field_id);?>" value="<?php echo esc_attr($vl);?>" class="wt_sc_color_picker_field"/>
                    </div>
					<?php
				}

				if('multi_select' === $type || 'ajax_select' === $type || 'checkbox' === $type || 'checkbox_list' === $type )
				{
					$hidden_filed_name = isset($value['parent_option']) ? $value['parent_option'].'['.$value['option_name'].'_hidden]' : $field_name.'_hidden';
					?>
					<input type="hidden" name="<?php echo esc_attr($hidden_filed_name);?>" value="1" />
					<?php 
				}

				echo $after_form_field;
			}
			if(isset($value['help_text']))
			{
            ?>
            	<span class="wt_sc_form_help"><?php echo wp_kses_post($value['help_text']); ?></span>
            <?php
        	}
        	
        	echo $conditional_help_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        	
        	if(false === $field_only)
			{
        	?>
			        </td>
			        <td>
			        	<?php echo $after_form_field_html;?>
			        </td>
			    </tr>
    		<?php
    		}
    	}
	}
}