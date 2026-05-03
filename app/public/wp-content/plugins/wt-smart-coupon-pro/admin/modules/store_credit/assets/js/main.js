(function( jQuery ) {
	'use strict';
	
	jQuery(function() {

		function wt_sc_email_preview_hide_show()
		{
			var preview_elm=jQuery('.wt_email_preview');
			var preview_btn_elm=jQuery('.wt_email_preview_hide_show');
			if(preview_elm.css('display')!='none')
			{
				preview_elm.hide();
				preview_btn_elm.html('['+wt_sc_store_credit_params.msgs.show_preview+']');
			}else{
				preview_elm.show();
				preview_btn_elm.html('['+wt_sc_store_credit_params.msgs.hide_preview+']');
			}
			wt_sc_load_email_preview();			
		}

		function wt_sc_load_email_preview(keyup_elm)
		{
			var preview_elm=jQuery('.wt_email_preview');
			var need_to_load=false;
			if(preview_elm.attr('data-loaded')=='0') /* not yet loaded */
			{
				need_to_load=true;
			}else
			{
				var is_extended=(jQuery('[name="enabled_extended_store_credit"]').is(':checked') ? '1' :'0');
				if(preview_elm.attr('data-loaded-type')!=is_extended)
				{
					need_to_load=true;
				}
			}
			if(need_to_load)
			{
				preview_elm.html('<div class="wt_sc_email_preview_loading">'+WTSmartCouponAdminOBJ.msgs.loading+'</div>');
				jQuery.ajax({
					url:WTSmartCouponAdminOBJ.ajaxurl,
					type:'POST',
					data:'action=wt_sc_store_credit_email_preview&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
					success:function(data)
					{
						preview_elm.html(data).attr({'data-loaded-type':is_extended, 'data-loaded':1});
						jQuery('.wt_sc_send_email_field').each(function(){
							wt_sc_set_email_preview_values(jQuery(this));
						});
					},
					error:function() 
					{
						preview_elm.attr({'data-loaded':0}).find('.wt_sc_email_preview_loading').html(wt_sc_store_credit_params.msgs.unable_to_load_preview);
					}
				});
			}
		}

		function wt_sc_set_email_preview_values(elm)
		{
			var vl=elm.val().trim();
			var name=elm.attr('name');
			if(name=='wt_sc_send_email_description')
			{
				jQuery('.wt_email_preview div.coupon-message').html(vl);
			}else if(name=='wt_sc_send_email_amount')
			{
				vl=(vl=="" ? 0 : vl);
				jQuery('.wt_email_preview div.coupon_price span').html(vl);
			}else
			{
				jQuery('.wt_email_preview div.wt_gift_coupon_preview_caption').html(vl);
			}
		}

		/* toggle preview */
		jQuery('.wt_email_preview_hide_show').on('click', function(){
			wt_sc_email_preview_hide_show();
		});
		wt_sc_email_preview_hide_show();


		/* load values to preview from fields */
		jQuery('.wt_sc_send_email_field').on('keyup', function(){
			wt_sc_set_email_preview_values(jQuery(this));
		});


		jQuery('.wt_sc_store_credit_mail_form').find('[required]').each(function(){
			jQuery(this).removeAttr('required').attr('data-settings-required', '');
		});
		jQuery('.wt_sc_store_credit_mail_form').on('submit', function(e){
			e.preventDefault();
			if(!wt_sc_settings_form.validate(jQuery(this)))
			{
				return false;
			}

			var data=jQuery(this).serialize();

			var submit_btn=jQuery(this).find('input[type="submit"]');
			var spinner=submit_btn.siblings('.spinner');
			spinner.css({'visibility':'visible'});
			submit_btn.css({'opacity':'.5','cursor':'default'}).prop('disabled',true);

			jQuery.ajax({
				url:WTSmartCouponAdminOBJ.ajaxurl,
				type:'POST',
				dataType:'json',
				data:data+'&action=wt_sc_store_credit_email&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
				success:function(data)
				{
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
					if(data.status==true)
					{
						wt_sc_notify_msg.success(data.msg, false);
					}else
					{
						wt_sc_notify_msg.error(data.msg, false);
					}
				},
				error:function() 
				{
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled', false);
					wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
				}
			});

		});


		
		wt_sc_gift_template_manage.Set();

		/* To remove amount validation in the Store credit settings page when no gift card product is assigned */
        function wt_sc_remove_store_credit_required_field(){
            jQuery(".wt_sc_form_toggle_input_holder input[name='denominations'], .wt_sc_form_toggle_input_holder input[name='minimum_store_credit_purchase'], .wt_sc_form_toggle_input_holder input[name='maximum_store_credit_purchase']").removeAttr('data-settings-required required');

            jQuery('label[for="denominations"] .wt_sc_required_field, label[for="minimum_store_credit_purchase"] .wt_sc_required_field, label[for="maximum_store_credit_purchase"] .wt_sc_required_field').text('');
        }

        if(!jQuery('#store_credit_purchase_product').val()){
            setTimeout(function(){
				wt_sc_remove_store_credit_required_field();
			},100);
        }

        jQuery('#store_credit_purchase_product').change(function() {
            var selectedProduct = jQuery(this).val();
            if(selectedProduct){
                jQuery(".wt_sc_form_toggle_input_holder input[name='denominations'] , .wt_sc_form_toggle_input_holder input[name='minimum_store_credit_purchase'], .wt_sc_form_toggle_input_holder input[name='maximum_store_credit_purchase']").attr('data-settings-required' , '');

                jQuery('label[for="denominations"] .wt_sc_required_field, label[for="minimum_store_credit_purchase"] .wt_sc_required_field, label[for="maximum_store_credit_purchase"] .wt_sc_required_field').text('*');

            }else{
                wt_sc_remove_store_credit_required_field();
            }
        });


	});

})(jQuery);


/**
 *  Gift card template manage. Add, Delete, Visibility
 */
var wt_sc_gift_template_manage={

	onProgress:false,
	delete_request_queue:[],
	Set:function()
	{
		this.delete_template();
		this.load_template();
		this.control_template_visibility();
		this.add_new_template();
		this.check_all_template();
	},
	loader_for_delete:function(btn_elm)
	{
		var template_box=btn_elm.parents('.wt_sc_giftcard_template_box');
		btn_elm.removeClass('dashicons-trash').addClass('dashicons-hourglass').prop('disabled', true);
		template_box.css({'opacity':.2}).find('.wt_sc_checkbox_container').hide();
	},
	do_delete:function(btn_elm)
	{
		this.onProgress=true;
		var template_box=btn_elm.parents('.wt_sc_giftcard_template_box');
		var template_id=template_box.find('.wt_sc_visible_template_checkbox').val();
		jQuery.ajax({
			url:WTSmartCouponAdminOBJ.ajaxurl,
			type:'POST',
			dataType:'json',
			data:'wt_sc_store_credit_delete_template_id='+template_id+'&action=wt_sc_store_credit_delete_giftcard_template&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
			success:function(data)
			{
				wt_sc_gift_template_manage.onProgress=false;
				if(data.status===true)
				{
					template_box.remove();
					wt_sc_notify_msg.success(data.msg, false);
					if(data.visible_count_update_needed===true)
					{
						var visible_count=parseInt(jQuery('.wt_sc_giftcard_visible_template_count').text())-1;
						jQuery('.wt_sc_giftcard_visible_template_count').text(visible_count);
					}
					wt_sc_gift_template_manage.setup_checkall_checkbox_state();
					wt_sc_gift_template_manage.change_checkall_when_individual_change();
				}else
				{
					wt_sc_notify_msg.error(data.msg, false);
					btn_elm.removeClass('dashicons-hourglass').addClass('dashicons-trash').prop('disabled', false);
					template_box.css({'opacity':1}).find('.wt_sc_checkbox_container').show();
				}

				if(wt_sc_gift_template_manage.delete_request_queue.length>0)
				{
					var btn_elm = wt_sc_gift_template_manage.delete_request_queue.shift();
					wt_sc_gift_template_manage.do_delete(btn_elm);
				}
			},
			error:function() 
			{
				wt_sc_gift_template_manage.onProgress=false;
				wt_sc_gift_template_manage.delete_request_queue=[]; /* clear the queue */

				wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
				btn_elm.removeClass('dashicons-hourglass').addClass('dashicons-trash').prop('disabled', false);
				template_box.css({'opacity':1}).find('.wt_sc_checkbox_container').show();
			}
		});
	},
	
	/** 
	 * Template delete action 
	 */
	delete_template:function()
	{
		jQuery(document).on('click', '.wt_sc_img_delete_btn', function(){

			if(confirm(WTSmartCouponAdminOBJ.msgs.are_you_sure_to_delete))
			{
				var btn_elm=jQuery(this);
				if(wt_sc_gift_template_manage.onProgress) /* current request on progress so add it to queue */
				{
					wt_sc_gift_template_manage.delete_request_queue.push(btn_elm);
					wt_sc_gift_template_manage.loader_for_delete(btn_elm);
					return false;
				}

				wt_sc_gift_template_manage.loader_for_delete(btn_elm);
				wt_sc_gift_template_manage.do_delete(btn_elm);

			}
		})
	},

	/**
	 *  Load template list
	 */
	load_template:function()
	{
		jQuery('.wt_sc_giftcard_template_main').html(WTSmartCouponAdminOBJ.msgs.loading);
		var submit_btn=jQuery('.wt-sc-gift-template-container [name="wt_sc_update_admin_settings_form"]');
		submit_btn.hide();
		jQuery.ajax({
			url:WTSmartCouponAdminOBJ.ajaxurl,
			type:'POST',
			data:'action=wt_sc_store_credit_show_giftcard_templates&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
			success:function(data)
			{
				submit_btn.show();
				jQuery('.wt_sc_giftcard_template_main').html(data);
				wt_sc_gift_template_manage.setup_checkall_checkbox_state();
				wt_sc_gift_template_manage.change_checkall_when_individual_change();
			},
			error:function() 
			{
				jQuery('.wt_sc_giftcard_template_main').html(WTSmartCouponAdminOBJ.msgs.unable_to_load_templates);
			}
		});
	},

	/** 
	 * Hide/Show templates submit action 
	 */
	control_template_visibility:function()
	{
		var submit_btn=jQuery('.wt-sc-gift-template-container [name="wt_sc_update_admin_settings_form"]');

		submit_btn.on('click', function(){

			var form_data=jQuery('.wt_sc_store_credit_hide_giftcard_template_form').serialize();
			var html_bck=submit_btn.html();
			submit_btn.html(WTSmartCouponAdminOBJ.msgs.please_wait).prop('disabled', true);
			jQuery.ajax({
				url:WTSmartCouponAdminOBJ.ajaxurl,
				type:'POST',
				dataType:'json',
				data:form_data+'&action=wt_sc_store_credit_hide_giftcard_templates&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
				success:function(data)
				{
					submit_btn.html(html_bck).prop('disabled', false);
					if(data.status===true)
					{
						jQuery('.wt_sc_giftcard_visible_template_count').html(data.total_visible);
						wt_sc_notify_msg.success(data.msg);
					}else
					{
						wt_sc_notify_msg.error(data.msg, false);
					}
				},
				error:function() 
				{
					submit_btn.html(html_bck).prop('disabled', false);
					jQuery('.wt_sc_giftcard_template_main').html(WTSmartCouponAdminOBJ.msgs.error);
				}
			});
		});
	},

	add_new_template:function()
	{

		/* Show `Add new template` popup */
		jQuery(document).on('click', '.wt_sc_giftcard_add_new_template_btnbox', function(){
			wt_sc_popup.showPopup(jQuery('.wt_sc_giftcard_add_new_template_popup'));
		});


		/**
		 * Add new template submit button 
		 * 
		 */
		jQuery('.wt_sc_giftcard_add_new_template_submitbtn').on('click', function(){
			
			var btn_elm=jQuery(this);
			if(jQuery('[name="wt_sc_choose_gift_card_template"]').val().trim()=="")
			{
				wt_sc_notify_msg.error(wt_sc_store_credit_params.msgs.please_choose_image);
				return false;
			}
			if(jQuery('[name="wt_sc_choose_gift_card_template_category"]').val().trim()=="")
			{
				wt_sc_notify_msg.error(wt_sc_store_credit_params.msgs.please_choose_category);
				return false;
			}
			if(jQuery('[name="wt_sc_choose_gift_card_template_top_bg"]').val().trim()=="")
			{
				wt_sc_notify_msg.error(wt_sc_store_credit_params.msgs.please_choose_top_bg);
				return false;
			}
			if(jQuery('[name="wt_sc_choose_gift_card_template_bottom_bg"]').val().trim()=="")
			{
				wt_sc_notify_msg.error(wt_sc_store_credit_params.msgs.please_choose_bottom_bg);
				return false;
			}

			var form_data=jQuery('.wt_sc_store_credit_giftcard_template_form').serialize();
			
			var html_bck=btn_elm.html();
			btn_elm.html(WTSmartCouponAdminOBJ.msgs.please_wait).prop('disabled', true);
			jQuery.ajax({
				url:WTSmartCouponAdminOBJ.ajaxurl,
				type:'POST',
				dataType:'json',
				data:form_data+'&action=wt_sc_store_credit_add_giftcard_template&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
				success:function(data)
				{
					btn_elm.html(html_bck).prop('disabled', false);
					if(data.status===true)
					{
						jQuery('.wt_sc_store_credit_hide_giftcard_template_form').append(data.gift_card_template_html);
						var visible_count=parseInt(jQuery('.wt_sc_giftcard_visible_template_count').text())+1;
						jQuery('.wt_sc_giftcard_visible_template_count').text(visible_count);
						wt_sc_notify_msg.success(data.msg);
						wt_sc_popup.hidePopup();
						wt_sc_gift_template_manage.setup_checkall_checkbox_state();
						wt_sc_gift_template_manage.change_checkall_when_individual_change();
					}else
					{
						wt_sc_notify_msg.error(data.msg, false);
					}
				},
				error:function() 
				{
					btn_elm.html(html_bck).prop('disabled', false);
					wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
				}
			});

			return false;
		});
	},	

	/** 
	 * Template check all option 
	 * @since 2.4.0
	 */
	check_all_template:function(){

		jQuery( '.wbte_sc_storecredit_check_all' ).on( 'click', function(){
	        	
			var target_elms = jQuery('.wt_sc_giftcard_template_box' );
	
			if( jQuery( this ).is( ':checked' ) )
			{
				target_elms.find( '.wt_sc_visible_template_checkbox' ).prop( 'checked', true );
			}else{
				target_elms.find( '.wt_sc_visible_template_checkbox' ).prop( 'checked', false );
			}
		} );

	},

	/** 
	 * Template set check all state 
	 * @since 2.4.0
	 */
	setup_checkall_checkbox_state:function(){

		var target_elms = jQuery( '.wt_sc_giftcard_template_box' );
		
		if( target_elms.find( '.wt_sc_visible_template_checkbox:checked' ).length ===  target_elms.find( '.wt_sc_visible_template_checkbox' ).length )
		{
			jQuery( '.wbte_sc_storecredit_check_all' ).prop( 'checked', true );
		}else{
			jQuery( '.wbte_sc_storecredit_check_all' ).prop( 'checked', false );
		}
	},

	/** 
	 * Change checkall checkbox value when individual template select or deselect
	 * @since 2.4.0
	 */
	change_checkall_when_individual_change:function(){

		jQuery( '.wt_sc_visible_template_checkbox' ).change(function(){
			wt_sc_gift_template_manage.setup_checkall_checkbox_state();
		});
	}
	
};