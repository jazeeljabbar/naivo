(function( $ ) {
	'use strict';
	
	$(function() {

		var wt_sc_notifications = {
			Set:function()
			{
				this.set_edit();
				this.do_edit();
				this.set_view();
				this.set_available_value_selection();
			},
			set_view:function()
			{
				$('.wt_sc_notif_view_btn').on('click', function(){ 
					
					var tr_elm = $(this).parents('tr');

					var data_elm = tr_elm.find('.wt_sc_notif_data_div');
					var status = parseInt(data_elm.attr('data-status'));
					var msg_desc = wt_sc_notifications.get_description(data_elm);
					var avl_values = wt_sc_notifications.get_avl_values(data_elm);
					var avl_filters = wt_sc_notifications.get_avl_filters(data_elm); 
					var cus_msg = wt_sc_notifications.get_cus_messsage(tr_elm);
					var def_msg = wt_sc_notifications.get_def_messsage(tr_elm);

					var view_popup = $('.wt_sc_popup.wt_sc_notif_view_popup');
					view_popup.find('.wt_sc_notif_cus_message_dv').html(cus_msg);
					view_popup.find('.wt_sc_notif_def_message_dv').html(def_msg);
					view_popup.find('.wt_sc_notif_msg_desc').html(msg_desc);
					view_popup.find('.wt_sc_notif_avail_values').html(avl_values);
					view_popup.find('.wt_sc_notif_avail_filters').html(avl_filters);

					wt_sc_notifications.set_non_existing_messages(view_popup);
				});
			},
			set_edit:function()
			{
				$('.wt_sc_notif_edit_btn').on('click', function(){ 
					var tr_elm = $(this).parents('tr');
					var msg_key = tr_elm.find('.wt_sc_notif_message_key').val();
					var data_elm = tr_elm.find('.wt_sc_notif_data_div');
					var status = parseInt(data_elm.attr('data-status'));
					var msg_desc = wt_sc_notifications.get_description(data_elm);
					var avl_values = wt_sc_notifications.get_avl_values(data_elm);
					var msg = wt_sc_notifications.get_cus_messsage(tr_elm);
					var status_locked = parseInt(wt_sc_notifications.get_status_locked(tr_elm));

					var edit_popup = $('.wt_sc_popup.wt_sc_notif_edit_form');
					edit_popup.find('.wt_sc_notif_msg_desc').html(msg_desc);
					edit_popup.find('.wt_sc_notif_avail_values').html(avl_values);
					edit_popup.find('.wt_sc_notif_msg_editor').val(msg);
					edit_popup.find('[name="wt_sc_notif_message_key"]').val(msg_key);

					if(1 === status)
					{
						edit_popup.find('[name="wt_sc_notif_message_status"]').prop('checked', true);
					}else
					{
						edit_popup.find('[name="wt_sc_notif_message_status"]').prop('checked', false);
					}

					if(1 === status_locked)
					{
						edit_popup.find('[name="wt_sc_notif_message_status"]').prop('disabled', true);
						edit_popup.find('.wt_sc_notif_status_locked_msg').html(wt_sc_notifications.get_status_locked_messsage(tr_elm));
					}else
					{
						edit_popup.find('[name="wt_sc_notif_message_status"]').prop('disabled', false);
						edit_popup.find('.wt_sc_notif_status_locked_msg').html('');
					}

					wt_sc_notifications.set_non_existing_messages(edit_popup);
				});
			},
			set_non_existing_messages:function(parent_elm)
			{
				this.set_set_non_existing_message(parent_elm, '.wt_sc_notif_avail_values', wt_sc_notifications_params.msgs.no_avail_values);
				this.set_set_non_existing_message(parent_elm, '.wt_sc_notif_avail_filters', wt_sc_notifications_params.msgs.no_avail_filters);
				this.set_set_non_existing_message(parent_elm, '.wt_sc_notif_cus_message_dv', wt_sc_notifications_params.msgs.no_cus_message);					
			},
			set_set_non_existing_message:function(parent_elm, class_name, msg)
			{
				if(0 < parent_elm.find(class_name).length && "" === parent_elm.find(class_name).html().trim())
				{
					parent_elm.find(class_name).html('<span class="wt_sc_nothing_to_show">'+msg+'</span>');
				}
			},
			do_edit:function()
			{
				$('.wt_sc_notif_message_btn').on('click', function(){
					
					var edit_popup = $('.wt_sc_popup.wt_sc_notif_edit_form');
					var msg_key = edit_popup.find('[name="wt_sc_notif_message_key"]').val();
					var msg = edit_popup.find('.wt_sc_notif_msg_editor').val();
					var status = (edit_popup.find('[name="wt_sc_notif_message_status"]').is(':checked') ? 1 : 0);
					var submit_btn = $(this);

					$('.spinner').css({'visibility': 'visible'});
					submit_btn.css({'opacity':'.5','cursor':'default'}).prop('disabled',true);

					$.ajax({
						url: WTSmartCouponAdminOBJ.ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {'action': 'wt_sc_notification_save', 'wt_sc_notif_msg_key':msg_key, 'wt_sc_notif_msg':msg, 'wt_sc_notif_status':status, '_wpnonce': WTSmartCouponAdminOBJ.nonce},
						success:function(data)
						{
							/** populate the new data to the table */
							if(data.status)
							{
								wt_sc_notify_msg.success(data.msg);
								wt_sc_popup.hidePopup();
								wt_sc_notifications.update_after_save();
							}else
							{
								wt_sc_notify_msg.error(data.msg);
							}							
						},
						error:function()
						{
							wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
						},
						complete:function()
						{
							submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
							$('.spinner').css({'visibility': 'hidden'});
						}
					});
				});
			},
			update_after_save:function()
			{
				var edit_popup = $('.wt_sc_popup.wt_sc_notif_edit_form');
				var msg_key = edit_popup.find('[name="wt_sc_notif_message_key"]').val();
				var msg = edit_popup.find('.wt_sc_notif_msg_editor').val().trim();
				var status = (edit_popup.find('[name="wt_sc_notif_message_status"]').is(':checked') ? 1 : 0);

				var tr_elm = $('.wt_sc_notif_list_tb tr').has('.wt_sc_notif_message_key[value="'+msg_key+'"]');
				var data_elm = tr_elm.find('.wt_sc_notif_data_div');

				/* Msg */
				tr_elm.find('.wt_sc_notif_cus_message_dv').html(msg);

				
				/* Message status */
				var status_td_html = '';

				if("" !== msg && 1 === status)
				{
					status_td_html= wt_sc_notifications_params.custom;

				}else if("" === msg && 1 === status)
				{
					status_td_html= wt_sc_notifications_params.default;

				}else if(0 === status)
				{
					status_td_html= wt_sc_notifications_params.hidden;
				}

				tr_elm.find('.wt_sc_notif_status_td').html(status_td_html);
				data_elm.attr({'data-status': status});

			},
			get_description:function(data_elm)
			{
				return data_elm.find('.wt_sc_notif_message_desc').html();
			},
			get_avl_values:function(data_elm)
			{
				return data_elm.find('.wt_sc_notif_avail_placeholders').html();
			},
			get_avl_filters:function(data_elm)
			{
				return data_elm.find('.wt_sc_notif_avail_filters').html();
			},
			get_cus_messsage:function(tr_elm)
			{
				return tr_elm.find('.wt_sc_notif_cus_message_dv').html().trim();
			},
			get_def_messsage:function(tr_elm)
			{
				return tr_elm.find('.wt_sc_notif_def_message_dv').html().trim();
			},
			get_status_locked_messsage:function(tr_elm)
			{
				return tr_elm.find('.wt_sc_notif_status_locked_msg').html().trim();
			},
			get_status_locked:function(tr_elm)
			{
				return tr_elm.find('.wt_sc_notif_status_locked').val();
			},
			set_available_value_selection:function()
			{
				$(document).on('click', '.wt_sc_popup.wt_sc_notif_edit_form .wt_sc_notif_avail_values .wt_sc_notif_avail_value_name', function(){
					wt_sc_notifications.insert_text($('.wt_sc_popup.wt_sc_notif_edit_form').find('.wt_sc_notif_msg_editor')[0], $(this).text().trim());
				});
			},
			insert_text:function(textarea, text)
			{			
				// Save the current scroll position
				const scrollTop = textarea.scrollTop;
				const scrollLeft = textarea.scrollLeft;

				if (textarea.selectionStart || textarea.selectionStart === 0) {
				// For modern browsers
				const startPos = textarea.selectionStart;
				const endPos = textarea.selectionEnd;

				// Insert the custom text at the caret position
				textarea.value = textarea.value.substring(0, startPos) + text + textarea.value.substring(endPos);

				// Move the caret position to after the newly inserted text
				textarea.setSelectionRange(startPos + text.length, startPos + text.length);

				} else if (document.selection) {
				// For older versions of Internet Explorer
				textarea.focus();
				const range = document.selection.createRange();
				range.text = text;
				range.select();
				}

				// Restore the previous scroll position
				textarea.scrollTop = scrollTop;
				textarea.scrollLeft = scrollLeft;

				// Set focus back to the textarea
				textarea.focus();

			}
		}

		wt_sc_notifications.Set();
	});

})(jQuery);