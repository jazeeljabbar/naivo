var wt_sc_licence=(function( $ ) {
	'use strict';
	var wt_sc_licence=
	{
		status_checked:false,
		Set:function()
		{
			this.list_data();
			this.activation();
		},
		update_status_tab_icon:function()
		{			
			if($('.wt_sc_licence_table .status_td').length>0)
			{
				var status=true;
			}else
			{
				var status=false;
			}

			if(status)
			{
				$('[name="wt_sc_licence_product"] option').each(function(){
					var vl=$(this).val();
					var licence_tr=$('.wt_sc_licence_table .licence_tr[data-product="'+vl+'"]');
					if(licence_tr.length==0)
					{
						status=false;
					}
				});
			}

			if(status)
			{
				$('.wt_sc_licence_table .status_td').each(function(){
					var st=$(this).attr('data-status');
					if(st=='inactive' || st=='')
					{
						status=false;
					}
				});
			}

			var tab_icon_elm=$('.wt-sc-tab-head .nav-tab[href="#wt-licence"] .dashicons')
			if(status)
			{
				tab_icon_elm.replaceWith(wt_sc_licence_params.tab_icons['active']);
			}else
			{
				tab_icon_elm.replaceWith(wt_sc_licence_params.tab_icons['inactive']);	
			}
		},
		list_data:function()
		{
			$.ajax({
				url:wt_sc_licence_params.ajax_url,
				data:{'action': 'wt_sc_licence_manager_ajax', 'wt_sc_licence_manager_action': 'licence_list', '_wpnonce':wt_sc_licence_params.nonce},
				type:'post',
				dataType:"json",
				success:function(data)
				{
					if(data.status==true)
					{
						$('.wt_sc_licence_list_container').html(data.html);
						wt_sc_licence.update_status_tab_icon();
						wt_sc_licence.deactivation();
					}else
					{
						wt_sc_notify_msg.error(wt_sc_licence_params.msgs.unable_to_fetch);
					}
				},
				error:function()
				{
					wt_sc_notify_msg.error(wt_sc_licence_params.msgs.unable_to_fetch);
				}
			});
		},
		deactivation:function()
		{
			$('.wt_sc_licence_deactivate_btn').on('click', function(){
				if(confirm(wt_sc_licence_params.msgs.sure))
				{
					wt_sc_licence.do_deactivate($(this));
				}
			});
		},
		do_deactivate:function(btn)
		{
			var btn_txt_back=btn.html();
			btn.html(wt_sc_licence_params.msgs.please_wait).prop('disabled', true);
			var product=btn.attr('data-product');
			var action=btn.attr('data-action');
			$.ajax({
				url:wt_sc_licence_params.ajax_url,
				data:{'action': 'wt_sc_licence_manager_ajax', 'wt_sc_licence_manager_action': action, '_wpnonce':wt_sc_licence_params.nonce, 'wt_sc_licence_product':product},
				type:'post',
				dataType:"json",
				success:function(data)
				{
					if(data.status==true)
					{	
						wt_sc_notify_msg.success(data.msg);
						if(btn.parents('tbody').find('tr').length>1)
						{
							btn.parents('tr').remove();
						}else
						{
							wt_sc_licence.list_data();
						}
					}else
					{
						btn.html(btn_txt_back).prop('disabled', false);
						wt_sc_notify_msg.error(wt_sc_licence_params.msgs.error);
					}
				},
				error:function()
				{
					btn.html(btn_txt_back).prop('disabled', false);
					wt_sc_notify_msg.error(wt_sc_licence_params.msgs.error);
				}
			});
		},
		activation:function()
		{
			$('#wt_sc_licence_manager_form').on('submit', function(e){
				e.preventDefault();
				var this_form=$(this);
				var licence_key = this_form.find('[name="wt_sc_licence_key"]').val().trim();
				var licence_product = this_form.find('[name="wt_sc_licence_product"]').val().trim();
				
				if("" === licence_product)
				{
					wt_sc_notify_msg.error(wt_sc_licence_params.msgs.product_mandatory);
					return false;
				}

				if("" === licence_key)
				{
					wt_sc_notify_msg.error(wt_sc_licence_params.msgs.key_mandatory);
					return false;
				}
				
				var btn=this_form.find('.wt_sc_licence_activate_btn');
				var btn_txt_back=btn.html();
				btn.html(wt_sc_licence_params.msgs.please_wait).prop('disabled', true);
				$.ajax({
					url:wt_sc_licence_params.ajax_url,
					data:this_form.serialize(),
					type:'post',
					dataType:"json",
					success:function(data)
					{
						btn.html(btn_txt_back).prop('disabled', false);
						if(data.status==true)
						{
							this_form[0].reset();
							wt_sc_notify_msg.success(data.msg);
							window.location.reload(); /* To remove any existing messages if the license was activated. */
						}else
						{
							wt_sc_notify_msg.error(data.msg);
						}
					},
					error:function()
					{
						btn.html(btn_txt_back).prop('disabled', false);
						wt_sc_notify_msg.error(wt_sc_licence_params.msgs.error);
					}
				});
			});
		}
	}
	return wt_sc_licence;
	
})( jQuery );

jQuery(function() {			
	wt_sc_licence.Set();
});