(function( $ ) {
	'use strict';


	function load_coupon_selector() {
		$( '.wt-coupon-search' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: jQuery( this ).data( 'minimum_input_length' ) ? jQuery( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
					url:WTSmartCouponAdminOBJ.ajaxurl,
					contentType: "application/json; charset=utf-8",
					dataType:    'json',
					quietMillis: 300,
					data: function( params ) {
						return {
							term: params.term,
							post_id: jQuery(this).data('postid'),
							action: jQuery(this).data('action') || 'wt_json_search_coupons',
							_wpnonce: jQuery(this).data('security'),
							no_coupon_type: jQuery(this).data('no_coupon_type') || 0,
							input_name: jQuery(this).attr('name'),
						};
					},
					processResults: function( data ) {
						var terms = [];
						if ( data ) {
							jQuery.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							});
						}
						return { results: terms };
					},
					cache: false
				}
			};
			
	
			jQuery( this ).selectWoo( select2_args );
	
	
		});
	}

	$(document).ready(function() {
		$( '#woocommerce-product-data' ).on('woocommerce_variations_loaded',function(){
			load_coupon_selector();
		});

	});
	
	
	$(document).ready(function() {
		
		$('#wt_smart_coupon_upload').on('change',function( ){
			$('.wt-file-container-label').html('File selected').addClass('selected');
		});

		load_coupon_selector();
		
		if ("undefined" === typeof getEnhancedSelectFormatString) {
			function getEnhancedSelectFormatString() {
				var formatString = {
					noResults: function() {
						return wc_enhanced_select_params.i18n_no_matches;
					},
					errorLoading: function() {
						return wc_enhanced_select_params.i18n_ajax_error;
					},
					inputTooShort: function( args ) {
						var remainingChars = args.minimum - args.input.length;

						if ( 1 === remainingChars ) {
							return wc_enhanced_select_params.i18n_input_too_short_1;
						}

						return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
					},
					inputTooLong: function( args ) {
						var overChars = args.input.length - args.maximum;

						if ( 1 === overChars ) {
							return wc_enhanced_select_params.i18n_input_too_long_1;
						}

						return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
					},
					maximumSelected: function( args ) {
						if ( 1 === args.maximum ) {
							return wc_enhanced_select_params.i18n_selection_too_long_1;
						}

						return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
					},
					loadingMore: function() {
						return wc_enhanced_select_params.i18n_load_more;
					},
					searching: function() {
						return wc_enhanced_select_params.i18n_searching;
					}
				};

				var language = { 'language' : formatString };

				return language;
			}
		}


		$('.wt_colorpick').wpColorPicker({
			'change':function(event, ui) { 
				var selected_color=ui.color.toString();
				
				var target_elm=$(event.target);
				target_elm.val(selected_color);
			 }
		});


		/**
		 * Open modal
		 */
		$('.wt_modal_btn').on('click',function( e ){
			e.preventDefault();
			var target = $(this).attr('target');
			$(target).show();
			$('body').append('<div class="wt_modal_overlay"></div>');
			$('body').addClass('wt-modal-open');
		});

	});

	// Implement Subtab for admin screen.
	jQuery(document).ready(function(){

		jQuery('.wt_sub_tab li a').on('click', function( e ) {
			e.preventDefault();
			if( $(this).parent('li').hasClass('active') ) {
				return;//nothing to do;
			}
			var target=$(this).attr('href');
			var parent = $(this).parents('.wt_sub_tab');
			var container = $('.wt_sub_tab_container');
			$('.wt_sub_tab li').removeClass('active');
			$(this).parent('li').addClass('active');
			container.find('.wt_sub_tab_content').hide().removeClass('active');
			container.find(target).fadeIn().addClass('active');
		});
	});


	
	
	
	//Combo coupon
	$('document').ready(function() {
		// Insert Combo coupon HTML
		var element_individual_use_only = $("#woocommerce-coupon-data .form-field:has('[name=\"individual_use\"]')");
		if (element_individual_use_only.length) {
			$("#woocommerce-coupon-data .wt_combo_coupon_fields").detach().insertAfter( element_individual_use_only );

		}
		
		$('input[name=\"individual_use\"]').on('change',function(){
			if( $(this).is(":checked") ) {
				$('.wt_combo_coupon_fields').hide();
			} else {
				$('.wt_combo_coupon_fields').show();
			}
			
		});

	});



	// Limit  max discount
	$('document').ready(function() {
		$('#discount_type').on('change',function(){
			var type = $(this).val();
			
			if( 'percent' === type || 'fixed_product' === type )
			{
				$('#wt_max_discount').show();
			}else
			{
				$('#wt_max_discount').hide();
			}
		});
	});


	/** signup coupon */
	$('document').ready(function() {
		$('#_wt_use_master_coupon_as_is').on('change',function(){
			if( $(this).is(":checked") ) {
				$('#_wt_signup_coupon_prefix, #_wt_signup_coupon_suffix, #_wt_signup_coupon_length').prop("disabled", true);
				$('#signup_coupon_table .wt_coupon_format').addClass('wt_disabled_form_item');
			} else {
				$('#_wt_signup_coupon_prefix, #_wt_signup_coupon_suffix, #_wt_signup_coupon_length').prop("disabled", false);
				$('#signup_coupon_table .wt_coupon_format').removeClass('wt_disabled_form_item');				
			}
		});
	});
	

	/** abandonment coupon */
	$('document').ready(function() {
		$('#_wt_use_master_coupon_as_is_abandonment').on('change',function(){
			if( $(this).is(":checked") ) {
				$('#_wt_abandonment_coupon_prefix, #_wt_abandonment_coupon_suffix, #_wt_abandonment_coupon_length').prop("disabled", true);
				$('#abandonment_coupon_table .wt_coupon_format').addClass('wt_disabled_form_item');
			} else {
				$('#_wt_abandonment_coupon_prefix, #_wt_abandonment_coupon_suffix, #_wt_abandonment_coupon_length').prop("disabled", false);
				$('#abandonment_coupon_table .wt_coupon_format').removeClass('wt_disabled_form_item');
			}
		});
	});


	

	/** create accordian */
	$('document').ready(function() {
		var acc = document.getElementsByClassName("accordion-title");
		var i;

		for (i = 0; i < acc.length; i++) {
			acc[i].addEventListener("click", function( e ) {
				e.preventDefault();
				$('.accordion-panel').hide();
				if( $(this).hasClass('active') ) {
					$('.accordion-title').removeClass('active');	
					return;
				}
				$('.accordion-title').removeClass('active');
				$(this).addClass('active');
				$(this).parents('.panel-item').children('.accordion-panel').show();
			});
		}
	});
	

	/**
	 *  Tab view
	 * 	@since 1.3.5
	 */
	var wt_sc_tab_view=
    {
    	Set:function()
    	{
    		this.subTab();
    		var wt_sc_nav_tab=$('.wt-sc-tab-head .nav-tab');
		 	if(wt_sc_nav_tab.length>0)
		 	{
			 	wt_sc_nav_tab.on('click',function(){
			 		var wt_sc_tab_hash=$(this).attr('href');
			 		wt_sc_nav_tab.removeClass('nav-tab-active');
			 		$(this).addClass('nav-tab-active');
			 		wt_sc_tab_hash= ('#' === wt_sc_tab_hash.charAt(0) ? wt_sc_tab_hash.substring(1) : wt_sc_tab_hash);
			 		var wt_sc_tab_elm=$('div[data-id="'+wt_sc_tab_hash+'"]');
			 		$('.wt-sc-tab-content').hide();
			 		if(wt_sc_tab_elm.length>0 && wt_sc_tab_elm.is(':hidden'))
			 		{	 		
			 			wt_sc_tab_elm.fadeIn();
			 		}
			 	});
			 	$(window).on('hashchange', function (e) {
				    var location_hash=window.location.hash;
				 	
				 	if("" !== location_hash)
				 	{
				    	wt_sc_tab_view.showTab(location_hash);
				    }
				}).trigger('hashchange');

			 	var location_hash=window.location.hash;
			 	
			 	if("" !== location_hash)
			 	{
			 		wt_sc_tab_view.showTab(location_hash);
			 	}else
			 	{
			 		wt_sc_nav_tab.eq(0).trigger('click');
			 	}		 	
			}
    	},
    	showTab:function(location_hash)
    	{
    		var wt_sc_tab_hash= ('#' === location_hash.charAt(0) ? location_hash.substring(1) : location_hash);
	 		
	 		if("" !== wt_sc_tab_hash)
	 		{
	 			var wt_sc_tab_hash_arr=wt_sc_tab_hash.split('#');
	 			wt_sc_tab_hash=wt_sc_tab_hash_arr[0];
	 			var wt_sc_tab_elm=$('div[data-id="'+wt_sc_tab_hash+'"]');
		 		if(wt_sc_tab_elm.length>0 && wt_sc_tab_elm.is(':hidden'))
		 		{	 			
		 			$('a[href="#'+wt_sc_tab_hash+'"]').trigger('click');
		 			if(wt_sc_tab_hash_arr.length>1)
			 		{
			 			var wt_sc_sub_tab_link=wt_sc_tab_elm.find('.wt_sc_sub_tab');
			 			if(wt_sc_sub_tab_link.length>0) /* subtab exists  */
			 			{
			 				var wt_sc_sub_tab=wt_sc_sub_tab_link.find('li[data-target='+wt_sc_tab_hash_arr[1]+']');
			 				wt_sc_sub_tab.trigger('click');
			 			}
			 		}
		 		}
	 		}
    	},
    	subTab:function()
    	{
    		$('.wt_sc_sub_tab li').on('click',function(){
				var trgt=$(this).attr('data-target');
				var prnt=$(this).parent('.wt_sc_sub_tab');
				var ctnr=prnt.siblings('.wt_sc_sub_tab_container');
				prnt.find('li a').css({'color':'#0073aa','cursor':'pointer', 'font-weight':'normal'});
				$(this).find('a').css({'color':'#000','cursor':'default', 'font-weight':'500'});
				ctnr.find('.wt_sc_sub_tab_content').hide();
				ctnr.find('.wt_sc_sub_tab_content[data-id="'+trgt+'"]').fadeIn();
			});
			$('.wt_sc_sub_tab').each(function(){
				var elm=$(this).children('li').eq(0);
				elm.trigger('click');
			});
			$('.wt_sc_sub_tab_trigger').on('click', function(){
				var trgt=$(this).attr('data-target');
				$('.wt_sc_sub_tab li[data-target="'+trgt+'"]').trigger('click');
			});
    	}
    }

    $('document').ready(function() {

    	/**
    	 *  Copy to clipboard
    	 * 	@since 1.3.6
    	 */
    	$(document).on('click', '.wt_sc_copy_to_clipboard', function(){
    		var target_class=$(this).attr('data-target');
    		var target_elm=$('.'+target_class);

    		if(target_elm.length && "" !== target_elm.text().trim())
    		{
    			navigator.clipboard.writeText(target_elm.text().trim());
    			wt_sc_notify_msg.success(WTSmartCouponAdminOBJ.msgs.copied);
    		}
    	});

    	$('.wt-sc-form-preview-popover').on('click', function(){
    		var img_url=$(this).attr('data-url');
    		$('.wt_sc_image_preview .wt_sc_popup_body').html('<img src="'+img_url+'" />');
    		var img_width=parseInt($(this).attr('data-width'));
    		if(!isNaN(img_width))
    		{
    			$('.wt_sc_image_preview').width(img_width+15);
    			$('.wt_sc_image_preview .wt_sc_popup_body img').width(img_width);
    		}
    		
    		var title=$(this).attr('data-title');
    		title=('undefined' !== typeof title ? title : '');
    		$('.wt_sc_image_preview .wt_sc_popup_title').html(title);

    		wt_sc_popup.showPopup($('.wt_sc_image_preview'));
    		
    	});
    	$(".wt-sc-tips").tipTip({'attribute': 'data-wt-sc-tip'});

    	$('.wt_sc_color_picker_field').wpColorPicker({});
    	
    	wt_sc_tab_view.Set();
    	wt_sc_popup.Set();
    	wt_sc_accord.Set();
    	wt_sc_form_toggler.Set();
    	wt_sc_settings_form.Set();
    	wt_sc_file_attacher.Set();
    	wt_sc_custom_and_preset.Set();
    	wt_sc_coupon_edit_meta_item_table.Set();
    	wt_sc_conditional_help_text.Set();
    	wt_sc_field_group.Set();
    });

	/** 
	 * Show the old BOGO disabled notice if the coupon type chosen is old BOGO, after the new BOGO is activated. 
	 * If new BOGO not activated, show the switch to new BOGO notice.
	 * */
	$( document ).ready( function() {
		
		if( WTSmartCouponAdminOBJ.is_new_bogo_activated ){
			const notice_elm = '<div class="notice notice-info notice-alt inline wbte_sc_old_bogo_disabled_notice"><p>'+ WTSmartCouponAdminOBJ.msgs.old_bogo_disabled +'</p></div>';
			$( '#discount_type' ).on( 'change', function () {
				if( 'wt_sc_bogo' === $( this ).val() ){
					if( 0 === $( '.wbte_sc_old_bogo_disabled_notice' ).length ){
						$( '#misc-publishing-actions' ).append( notice_elm );
					}
				}else{
					$( '.wbte_sc_old_bogo_disabled_notice' ).remove();
				}
			});
		}else{
			const switchNewBogoNotice = `<div class="wbte_sc_switch_new_bogo_notice"><p>${ WTSmartCouponAdminOBJ.msgs.switch_new_bogo }</p><button class="wbte_sc_button-shadow wbte_sc_button wbte_sc_button-filled wbte_sc_button-small wbte_sc_switch_new_bogo_notice_btn">${ WTSmartCouponAdminOBJ.msgs.update_now }</button></div>`;
			$( '#discount_type' ).on( 'change', function () {
				if( 'wt_sc_bogo' === $( this ).val() ){
					if( 0 === $( '.wbte_sc_switch_new_bogo_notice' ).length ){
						$( '.form-field.discount_type_field' ).after( switchNewBogoNotice );
					}
				}else{
					$( '.wbte_sc_switch_new_bogo_notice' ).remove();
				}
			});
		}

		$( document ).on( 'click', '.wbte_sc_switch_new_bogo_notice_btn', function(e) {
            e.preventDefault();
            window.location.href = 'admin.php?page=wt-smart-coupon-for-woo_bogo';
        });
	} );

})( jQuery );


var wt_sc_settings_form=
{
	Set:function()
	{
		jQuery('.wt_sc_settings_form').find('[required]').each(function(){
			jQuery(this).removeAttr('required').attr('data-settings-required','');
		});
		jQuery('.wt_sc_settings_form').on('submit', function(e){
			e.preventDefault();
			if(!wt_sc_settings_form.validate(jQuery(this)))
			{
				return false;
			}

			var settings_base=jQuery(this).find('.wt_sc_settings_base').val();
			var data=jQuery(this).serialize();

			var submit_btn=jQuery(this).find('input[type="submit"]');
			var spinner=submit_btn.siblings('.spinner');
			spinner.css({'visibility':'visible'});
			submit_btn.css({'opacity':'.5','cursor':'default'}).prop('disabled',true);	
			var prg_elm = wt_sc_notify_msg.progress( WTSmartCouponAdminOBJ.msgs.saving );

			jQuery.ajax({
				url:WTSmartCouponAdminOBJ.ajaxurl,
				type:'POST',
				dataType:'json',
				data:data+'&wt_sc_settings_base='+settings_base+'&action=wt_sc_save_settings&_wpnonce='+WTSmartCouponAdminOBJ.nonce,
				success:function( data ) {
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
					if( true === data.status ) {
						wt_sc_notify_msg.progress_complete( prg_elm, data.msg );
					}else {
						wt_sc_notify_msg.progress_error( prg_elm, data.msg, false );
					}
				},
				error:function() {
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
					wt_sc_notify_msg.progress_error( prg_elm, WTSmartCouponAdminOBJ.msgs.settings_error, false );
				}
			});
		});
	},
	validate:function(form_elm)
	{
		var is_valid=true;
		form_elm.find('[data-settings-required]').each(function(){
			var elm=jQuery(this);
			if("" === elm.val().trim() && elm.is(':visible'))
			{
				var required_msg=elm.attr('data-required-msg');
				if(typeof required_msg=='undefined')
				{
					var prnt=elm.parents('tr');
					var label=prnt.find('th label');				
					var temp_elm=jQuery('<div />').html(label.html());
					temp_elm.find('.wt_sc_required_field').remove();
					required_msg='<b><i>'+temp_elm.text()+'</i></b>'+WTSmartCouponAdminOBJ.msgs.is_required;
				}

				wt_sc_notify_msg.error(required_msg);
				is_valid=false;
				return false;
			}			
		});
		return is_valid;
	}
}

/**
 *  Popup creator
 * 	@since 1.3.5
 */
var wt_sc_popup={
	Set:function()
	{		
		jQuery('body').prepend('<div class="wt_sc_cst_overlay"></div>');
		this.regPopupOpen();
		this.regPopupClose();
	},
	regPopupOpen:function()
	{
		jQuery('[data-wt_sc_popup]').on('click',function(){
			var elm_class=jQuery(this).attr('data-wt_sc_popup');
			var elm=jQuery('.'+elm_class);
			if(elm.length)
			{
				wt_sc_popup.showPopup(elm);
			}
		});
	},
	showPopup:function(popup_elm)
	{
		var pw=popup_elm.outerWidth();
		var wh=jQuery(window).height();
		var ph=wh-150;
		popup_elm.css({'margin-left':((pw/2)*-1),'display':'block','top':'20px'}).animate({'top':'50px'});
		popup_elm.find('.wt_sc_popup_body').css({'max-height':ph+'px','overflow':'auto'});
		jQuery('.wt_sc_cst_overlay').show();
	},
	hidePopup:function()
	{
		jQuery('.wt_sc_popup_close').trigger('click');
	},
	regPopupClose:function(popup_elm)
	{
		jQuery(document).on('keyup', function(e){
			if('Escape' === e.key)
			{
				wt_sc_popup.hidePopup();
			}
		});
		jQuery('.wt_sc_popup_close, .wt_sc_popup_cancel, .wt_sc_cst_overlay').off('click').on('click', function(){
			jQuery('.wt_sc_cst_overlay, .wt_sc_popup').hide();
		});
	}
}

/**
 *  Toast notification
 * 	@since 1.3.5
 */
var wt_sc_notify_msg = {
	error:function(message, auto_close)
	{
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var er_elm=jQuery('<div class="wt_sc_notify_msg wt_sc_notify_msg_error">'+message+'</div>');				
		this.setNotify(er_elm, auto_close);
	},
	success:function(message, auto_close)
	{
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var suss_elm = jQuery('<div class="wt_sc_notify_msg wt_sc_notify_msg_success"><span class="dashicons dashicons-yes-alt"></span>&emsp;'+message+'</div>');				
		this.setNotify(suss_elm, auto_close);
	},
	progress:function( message ) {
		var prog_elm = jQuery('<div class="wt_sc_notify_msg wt_sc_notify_msg_progress"><span class="spinner"></span> ' + message + '</div>');				
		this.setNotify(prog_elm, false, true);
		return prog_elm;
	},
	progress_complete:function( elm, message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		elm.removeClass('wt_sc_notify_msg_progress').addClass('wt_sc_notify_msg_success');
		elm.html('<span class="dashicons dashicons-yes-alt" style="color:green;"></span> ' + message);				
		this.setNotify(elm, auto_close);
	},
	progress_error:function( elm, message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		elm.removeClass('wt_sc_notify_msg_progress').addClass('wt_sc_notify_msg_error');
		elm.html('<span class="dashicons dashicons-dismiss" style="color:red;"></span> ' + message);				
		this.setNotify(elm, auto_close);
	},
	setNotify:function(elm, auto_close, is_static)
	{
		jQuery('body').append(elm);
		elm.stop(true, true).animate({'opacity':1, 'top':'50px'}, 1000);
		if(is_static) { return; }
		
		elm.on('click',function(){
			wt_sc_notify_msg.fadeOut(elm);
		});
		
		if(auto_close)
		{
			setTimeout(function(){
				wt_sc_notify_msg.fadeOut(elm);
			},5000);
		}else
		{
			jQuery('body').on('click',function(){
				wt_sc_notify_msg.fadeOut(elm);
			});
		}
	},
	fadeOut:function(elm)
	{
		elm.animate({'opacity':0,'top':'100px'},1000,function(){
			elm.remove();
		});
	}
}

/**
 *  Accordian
 * 	@since 1.3.5
 */
var wt_sc_accord=
{
	Set:function()
	{
		jQuery('.wt_sc_accord .wt_sc_accord_hd').on('click', function(e){ 
			e.stopPropagation();
			if('wt_sc_accord_hd' === e.target.className || 'dashicons dashicons-arrow-right' === e.target.className || 'dashicons dashicons-arrow-down' === e.target.className)
			{ 
				var elm=jQuery(this);
				var prnt_dv=elm.parents('.wt_sc_accord');
				var cnt_dv=prnt_dv.find('.wt_sc_accord_content');

				if(1 === parseInt(prnt_dv.attr('data-disabled')))
				{
					cnt_dv.hide();
					return false;
				}				
				if(cnt_dv.is(':visible'))
				{
					elm.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
					cnt_dv.hide();
				}else
				{
					/* hide all others */
					jQuery('.wt_sc_accord_hd .dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
					jQuery('.wt_sc_accord_content').hide();

					/* then show the current one */
					elm.find('.dashicons').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
					cnt_dv.show();
				}
			}
		});
	}
}


/**
 *  Form toggler
 * 	@since 1.3.5
 * 	@since 2.0.2 [Bug fix] Unable to submit the form when a required element is hidden.
 */
var wt_sc_form_toggler=
{
	Set:function()
	{
		this.runToggler();
		jQuery('select.wt_sc_form_toggle').on('change', function(){
			wt_sc_form_toggler.toggle(jQuery(this));
		});
		jQuery('input[type="radio"].wt_sc_form_toggle').on('click',function(){
			if(jQuery(this).is(':checked'))
			{
				wt_sc_form_toggler.toggle(jQuery(this));
			}
		});
		jQuery('input[type="checkbox"].wt_sc_form_toggle').on('click',function(){
			wt_sc_form_toggler.toggle(jQuery(this),1);
		});
	},
	runToggler:function(prnt)
	{
		prnt=prnt ? prnt : jQuery('body');
		prnt.find('select.wt_sc_form_toggle').each(function(){
			wt_sc_form_toggler.toggle(jQuery(this));
		});
		prnt.find('input[type="radio"].wt_sc_form_toggle, input[type="checkbox"].wt_sc_form_toggle').each(function(){
			if(jQuery(this).is(':checked'))
			{
				wt_sc_form_toggler.toggle(jQuery(this));
			}
		});
		prnt.find('input[type="checkbox"].wt_sc_form_toggle').each(function(){
			wt_sc_form_toggler.toggle(jQuery(this),1);
		});
	},
	toggle:function(elm,checkbox)
	{
		var vl=elm.val();
		var trgt=elm.attr('wt_sc_form_toggle-target');
		jQuery('[wt_sc_form_toggle-id="'+trgt+'"]').hide().addClass('wt_sc_form_toggle_hidden');
		
		jQuery('[wt_sc_form_toggle-id="'+trgt+'"] [data-settings-required], [wt_sc_form_toggle-id="'+trgt+'"] [required]').each(function(){		
			var td_elm=jQuery(this).parents('td');
			if(td_elm.length>0)
			{
				var clone_elm=jQuery(this).clone();
				td_elm.data('w_sc_input_elm', clone_elm).addClass('wt_sc_form_toggle_input_holder');
				jQuery(this).remove();
			}
		});

		if('none' !== elm.css('display')) /* if parent is visible. `:visible` method. it will not work on JS tabview */
		{
			var elms=this.getElms(elm, trgt, vl, checkbox);
			elms.show().removeClass('wt_sc_form_toggle_hidden').find('th label').css({'margin-left':'0px'})
			elms.each(function(){
				var lvl=jQuery(this).attr('wt_sc_form_toggle-level');
				var mrgin=15;
				if (typeof lvl!== typeof undefined && lvl!== false) {
				    mrgin=lvl*mrgin;
				}
				if(jQuery(this).find('.wt_sc_form_toggle_input_holder').length)
				{
					jQuery(this).find('.wt_sc_form_toggle_input_holder').prepend(jQuery(this).find('.wt_sc_form_toggle_input_holder').data('w_sc_input_elm'))
				}
				jQuery(this).find('th label').animate({'margin-left':mrgin+'px'});
			});
		}

		/* in case of greater than 1 level */
		jQuery('[wt_sc_form_toggle-id="'+trgt+'"]').each(function(){
			wt_sc_form_toggler.runToggler(jQuery(this));
		});
	},
	getElms:function(elm, trgt, vl, checkbox)
	{		
		return jQuery('[wt_sc_form_toggle-id="'+trgt+'"]').filter(function(){
				var toggle_val=jQuery(this).attr('wt_sc_form_toggle-val');

				if(toggle_val === vl)
				{
					if(checkbox)
					{
						if(elm.is(':checked'))
						{
							if(jQuery(this).attr('wt_sc_form_toggle-check')=='true')
							{
								return true;
							}else
							{
								return false;
							}
						}else
						{
							if(jQuery(this).attr('wt_sc_form_toggle-check')=='false')
							{
								return true;
							}else
							{
								return false;
							}
						}
					}else
					{
						return true;
					}
				}else if(-1 !== toggle_val.indexOf("||"))
				{
					var val_arr=toggle_val.split("||");

					if(-1 !== jQuery.inArray(vl, val_arr))
					{
						return true;
					}else
					{
						return false;
					}
				}else
				{
					return false;
				}
			});
	}
}

/**
*	@author BraadMartin
*	@link https://github.com/BraadMartin/components/tree/master/alpha-color-picker
*/
function wt_sc_get_alpha_value_from_color(value)
{
	var alphaVal;

	// Remove all spaces from the passed in value to help our RGBa regex.
	value = value.replace( / /g, '' );

	if ( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ ) ) {
		alphaVal = parseFloat( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ )[1] ).toFixed(2) * 100;
		alphaVal = parseInt( alphaVal );
	} else {
		alphaVal = 100;
	}

	return alphaVal;
}

/**
 *  Conditional help text
 * 	@since 2.0.0
 */
var wt_sc_conditional_help_text=
{
	Set:function(prnt)
	{
		prnt=prnt ? prnt : jQuery('body');
		const regex = /\[(.*?)\]/gm;
		let m;
		prnt.find('.wt_sc_conditional_help_text').each(function()
		{
			var help_text_elm=jQuery(this);
			var this_condition=jQuery(this).attr('data-sc-help-condition');
			if("" !== this_condition)
			{
				var condition_conf=new Array();
				var field_arr=new Array();
				while ((m = regex.exec(this_condition)) !== null)
				{
					/* This is necessary to avoid infinite loops with zero-width matches */
				    if(m.index === regex.lastIndex)
				    {
				        regex.lastIndex++;
				    }
				    condition_conf.push(m[1]);
				    condition_arr=m[1].split('=');
				    if(condition_arr.length>1) /* field value pair */
				    {
				    	field_arr.push(condition_arr[0]);
				    }
				}
				if(field_arr.length>0)
				{					
					var callback_fn=function()
					{
						var is_hide=true;
						var previous_type='';
						for(var c_i=0; c_i<condition_conf.length; c_i++)
						{
							var cr_conf=condition_conf[c_i]; /* conf */
							var conf_arr=cr_conf.split('=');
							if(conf_arr.length>1) /* field value pair */
							{
								if('field' !== previous_type)
								{
									previous_type='field';
									var elm=jQuery('[name="'+conf_arr[0]+'"]');
									var vl='';
									if('input' === elm.prop('nodeName').toLowerCase() && 'radio' === elm.attr('type'))
									{
										vl=jQuery('[name="'+conf_arr[0]+'"]:checked').val();
									}
									else if('input' === elm.prop('nodeName').toLowerCase() && 'checkbox' === elm.attr('type'))
									{
										if(elm.is(':checked'))
										{
											vl=elm.val();
										}
									}else
									{
										vl=elm.val();
									}

									var check_val_arr = conf_arr[1].split('|');
									
									is_hide = (-1 !== jQuery.inArray(vl, check_val_arr) ? false : true);

								}
							}else /* glue */
							{
								if('glue' !== previous_type)
								{
									previous_type='glue';
									if('OR' === conf_arr[0])
									{
										if(false === is_hide) /* one previous condition is okay, then stop the loop */
										{
											break;
										}

									}else if('AND' === conf_arr[0])
									{
										if(true === is_hide && c_i>0) /* one previous condition is not okay,  then stop the loop */
										{
											break;
										} 
									}
								}
							}
						}
						if(is_hide)
						{
							help_text_elm.hide();
						}else
						{
							help_text_elm.css({'display':'inline-block', 'opacity':0}).animate({'opacity':1}, 1000);
						}
					}
					callback_fn();
					for(var f_i=0; f_i<field_arr.length; f_i++)
					{
						var elm=jQuery('[name="'+field_arr[f_i]+'"]');
						
						if('radio' === elm.prop('nodeName') || 'checkbox' === elm.prop('nodeName'))
						{
							elm.on('click', callback_fn);
						}else
						{
							elm.on('change', callback_fn);
						}
					}
				}
			}
		});
	}
}

/**
 *  File attacher input
 * 	@since 2.0.0
 */
var wt_sc_file_attacher={

	Set:function()
	{
		var file_frame;
		jQuery(".wt_sc_file_attacher").on('click',function(event){
			event.preventDefault();
			if(jQuery(this).data('file_frame'))
			{
				
			}else
			{
				var wt_sc_media_options={
					multiple: false
				};
				if(typeof jQuery( this ).attr('data-uploader_title')!='undefined')
				{
					wt_sc_media_options['title']=jQuery(this).attr('data-uploader_title');
				}
				if(typeof jQuery( this ).attr('data-uploader_button_text')!='undefined')
				{
					wt_sc_media_options['button']={};
					wt_sc_media_options['button']['text']=jQuery(this).attr('data-uploader_button_text');
				}
				if(typeof jQuery( this ).attr('data-allowed_file_types')!='undefined')
				{
					wt_sc_media_options['library']={};
					wt_sc_media_options['library']['type']=jQuery(this).attr('data-allowed_file_types').split("|");
				}

				/* Create the media frame. */
				var file_frame = wp.media.frames.file_frame = wp.media(wt_sc_media_options);
				jQuery(this).data('file_frame',file_frame);
				var wt_sc_file_target=jQuery(this).attr('wt_sc_file_attacher_target');
				var wt_sc_file_preview=jQuery(this).parent('.wt_sc_file_attacher_dv').siblings('.wt_sc_image_preview_small');
				var elm=jQuery(this);

				/* When an image is selected, run a callback. */
				jQuery(this).data('file_frame').on('select', function() {
					/* We set multiple to false so only get one image from the uploader */
					var attachment =file_frame.state().get('selection').first().toJSON();
					
					jQuery(wt_sc_file_target).val(attachment.url);
					if(wt_sc_file_preview.length>0)
					{
						wt_sc_file_preview.attr('src', attachment.url);
					}
				});
				/* Finally, open the modal	*/			
			}
			jQuery(this).data('file_frame').open();
		});
		function wt_sc_update_preview_img(wt_sc_file_target,wt_sc_file_preview)
		{
			if(jQuery(wt_sc_file_target).val()=="")
			{ 
				wt_sc_file_preview.attr('src',WTSmartCouponAdminOBJ.no_image);
			}else
			{
				wt_sc_file_preview.attr('src',jQuery(wt_sc_file_target).val());
			}
		}
		jQuery(".wt_sc_file_attacher").each(function(){
			var wt_sc_file_target=jQuery(this).attr('wt_sc_file_attacher_target');
			var wt_sc_file_preview=jQuery(this).parent('.wt_sc_file_attacher_dv').siblings('.wt_sc_image_preview_small');
			if(wt_sc_file_preview.length>0)
			{ 
				wt_sc_update_preview_img(wt_sc_file_target,wt_sc_file_preview);
				jQuery(wt_sc_file_target).change(function(){
					wt_sc_update_preview_img(wt_sc_file_target,wt_sc_file_preview);
				});
			}
		});
	}
}

var wt_sc_custom_and_preset=
{
	Set:function()
	{
		jQuery('.wt_sc_custom_and_preset').each(function(){
			wt_sc_custom_and_preset.toggler(jQuery(this), jQuery(this).siblings('.wt_sc_custom_and_preset_text'), jQuery(this).attr('data-custom-trigger-val'));
		});
	},
	toggler:function(preset_elm, custom_elm, custom_val) /* Toggle between custom and preset value */
	{
		this.do_toggle(preset_elm, custom_elm, custom_val);
		preset_elm.unbind('change').change(function(){
			wt_sc_custom_and_preset.do_toggle(preset_elm, custom_elm, custom_val);
		});
	},
	do_toggle:function(preset_elm, custom_elm, custom_val)
	{
		if(preset_elm.val()==custom_val)
		{
			custom_elm.prop('readonly', false).css({'background':'#ffffff'}).focus().val('');
		}else
		{
			custom_elm.prop('readonly', true).css({'background':'#efefef'}).val(preset_elm.find('option:selected').val());
		}
	},
	delimiter_toggler:function() /* function for delimiter toggle */
	{
		this.toggler(jQuery('.wt_sc_delimiter_preset'), jQuery('.wt_sc_custom_delimiter'), 'other');
	},
	date_format_toggler:function() /* function for date format toggle */
	{
		this.toggler(jQuery('.wt_sc_date_format_preset'), jQuery('.wt_sc_custom_date_format'), 'other');
	}
}

/**
 *  @since 2.0.4
 * 	Coupon edit page product/category table
 */
var wt_sc_coupon_edit_meta_item_table=
{
	Set:function()
	{
		this.set_add_row();
		this.set_remove_row();
		this.reg_multi_select(jQuery('.wt_sc_product_search'));
		this.reg_multi_select(jQuery('.wt_sc_category_search'));
	},

	/**
	 * 	Add form index to fields in the table row
	 */
	set_table_form_field_index:function(table_elm)
	{
		table_elm.find('tbody tr').each(function(ind, elm){
			
			jQuery(elm).find('input, select').each(function(){
				var new_name = jQuery(this).attr('name').replace(/[0-9]/g, ind);
				jQuery(this).attr('name', new_name);
			});

		});
	},
	set_add_row:function()
	{
		jQuery('.wt_sc_meta_item_tb_add_row').on('click', function(){
			var tb=jQuery(this).parents('table');
			if(parseInt(tb.parent('.wt_sc_coupon_fieldset').attr('data-disabled'))===1)
			{
				return false;
			}
			var first_row=tb.find('tbody tr:eq(0)');
			first_row.find('.wt_sc_select2').select2("destroy"); /* destroy select2 before cloning */
			var new_row=first_row.clone().insertBefore(jQuery(this).parents('tr')); /* clone and insert before the add button row */
			
			/* reset all values to default */		
			new_row.find('input, select').each(function(){
				jQuery(this).val(jQuery(this).attr('data-default-val'));
			});
			
			/* enable select2 */
			wt_sc_coupon_edit_meta_item_table.reg_multi_select(first_row.find('.wt_sc_select2')); 
			wt_sc_coupon_edit_meta_item_table.reg_multi_select(new_row.find('.wt_sc_select2'));

			tb.find('.wt_sc_meta_item_tb_delete_row').css({'opacity':1, 'cursor':'pointer'}); /* enable row delete function */
			
			wt_sc_coupon_edit_meta_item_table.set_table_form_field_index(tb);
		});
	},
	set_remove_row:function()
	{
		jQuery(document).on('click', '.wt_sc_meta_item_tb_delete_row', function(){
			var tb=jQuery(this).parents('table');
			if(parseInt(tb.parent('.wt_sc_coupon_fieldset').attr('data-disabled'))===1)
			{
				return false;
			}
			if(tb.children('tbody').find('tr').length<=2)
			{
				jQuery(this).parents('tr').find('input, select').each(function(){
					jQuery(this).val(jQuery(this).attr('data-default-val'));
				});
				wt_sc_coupon_edit_meta_item_table.reg_multi_select(jQuery(this).parents('tr').find('.wt_sc_select2'));

				tb.find('.wt_sc_meta_item_tb_delete_row').css({'opacity':.5, 'cursor':'not-allowed'});
				
				wt_sc_coupon_edit_meta_item_table.clear_parent_elm_val(tb.find('.wt_sc_select2:eq(0)'));
				return false;
			}
			
			var row=jQuery(this).parents('tr');
			row.remove();

			wt_sc_coupon_edit_meta_item_table.set_table_form_field_index(tb);

			wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm(tb.find('.wt_sc_select2:eq(0)'));
		});
	},
	display_result:function(self, select2_args)
	{
		jQuery( self ).selectWoo( select2_args ).addClass( 'enhanced' );

		jQuery(self).on("change", function (e) {
			wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm(jQuery(self)); 
		});

		if(jQuery(self).data('sortable')) {
			var $select = jQuery(self);
			var $list   = jQuery( self ).next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

			$list.sortable({
				placeholder : 'ui-state-highlight select2-selection__choice',
				forcePlaceholderSize: true,
				items       : 'li:not(.select2-search__field)',
				tolerance   : 'pointer',
				stop: function() {
					jQuery( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
						var id     = jQuery( this ).data( 'data' ).id;
						var option = $select.find( 'option[value="' + id + '"]' )[0];
						$select.prepend( option );
					} );
				}
			});
		// Keep multiselects ordered alphabetically if they are not sortable.
		} else if ( jQuery( self ).prop( 'multiple' ) ) {
			jQuery( self ).on( 'change', function(){
				var $children = jQuery( self ).children();
				$children.sort(function(a, b){
					var atext = a.text.toLowerCase();
					var btext = b.text.toLowerCase();

					if ( atext > btext ) {
						return 1;
					}
					if ( atext < btext ) {
						return -1;
					}
					return 0;
				});
				jQuery( self ).html( $children );
			});
		}
	},
	reg_multi_select:function(elms)
	{
		if(elms.hasClass('wt_sc_product_search'))
		{
			this.reg_product_search(elms);

		}else if(elms.hasClass('wt_sc_category_search'))
		{
			this.reg_category_search(elms);
		}
	},
	reg_category_search:function(elms)
	{
		elms.each( function() {
			var select2_args = {
				allowClear        : jQuery( this ).data( 'allow_clear' ) ? true : false,
				placeholder       : jQuery( this ).data( 'placeholder' ),
				minimumInputLength: jQuery( this ).data( 'minimum_input_length' ) ? jQuery( this ).data( 'minimum_input_length' ) : 3,
				escapeMarkup      : function( m ) {
					return m;
				},
				ajax: {
					url:         wc_enhanced_select_params.ajax_url,
					dataType:    'json',
					delay:       250,
					data:function( params ) {
						return {
							term:     params.term,
							action:   'woocommerce_json_search_categories',
							security: WTSmartCouponAdminOBJ.search_categories_nonce,
						};
					},
					processResults: function( data ) {
						var terms = [];
						if ( data ) {
							jQuery.each( data, function( id, term ) {
								terms.push({
									id:   id,
									text: term.name
								});
							});
						}
						return {
							results: terms
						};
					},
					cache: true
				}
			};

			jQuery(this).selectWoo(select2_args).addClass('enhanced');

			jQuery(this).on("change", function (e) { 
				wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm(jQuery(this)); 
			});
		});
	},
	reg_product_search:function(elms)
	{
		// Ajax product search box
		elms.each( function() {
			var select2_args = {
				allowClear:  jQuery( this ).data( 'allow_clear' ) ? true : false,
				placeholder: jQuery( this ).data( 'placeholder' ),
				minimumInputLength: jQuery( this ).data( 'minimum_input_length' ) ? jQuery( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
					url:         wc_enhanced_select_params.ajax_url,
					dataType:    'json',
					delay:       250,
					data:        function( params ) {
						return {
							term         : params.term,
							action       : jQuery( this ).data( 'action' ) || 'woocommerce_json_search_products_and_variations',
							security     : WTSmartCouponAdminOBJ.search_products_nonce,
							exclude      : jQuery( this ).data( 'exclude' ),
							exclude_type : jQuery( this ).data( 'exclude_type' ),
							include      : jQuery( this ).data( 'include' ),
							limit        : jQuery( this ).data( 'limit' ),
							display_stock: jQuery( this ).data( 'display_stock' )
						};
					},
					processResults: function( data ) {
						var terms = [];
						if ( data ) {
							jQuery.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							});
						}
						return {
							results: terms
						};
					},
					cache: true
				}
			};

			wt_sc_coupon_edit_meta_item_table.display_result( this, select2_args );
		});
	},

	clear_parent_elm_val:function(sele_elm)
	{
		var parent_elm=sele_elm.parents('.wt_sc_coupon_fieldset').data('parent-select');
		if(typeof parent_elm!='undefined' && parent_elm.length)
		{
			parent_elm.val(null).trigger('change');
		}
	},

	/**
	 * 	Add/remove the product ids to the parent woocommerce default field
	 */
	set_val_to_parent_elm:function(sele_elm)
	{
		var parent_elm=sele_elm.parents('.wt_sc_coupon_fieldset').data('parent-select');
		if(typeof parent_elm!='undefined' && parent_elm.length)
		{
			parent_elm.val(null).trigger('change');
			sele_elm.parents('.wt_sc_coupon_meta_item_table').find('.wt_sc_select2').each(function(){
				var selected_opt=jQuery(this).find(':selected');
				if(selected_opt.length)
				{
					var opt=new Option(selected_opt.text(), selected_opt.val(), true, true);
					parent_elm.append(opt).trigger('change');
				}
			});
		}		
	},

}

/**
 * function copied from cart.js by wooocommerce
 * @param { jQuery object } node 
 */
var wt_block_node = function( node ) {
    node.addClass( 'processing' ).block( {
        message: null,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        }
    } );
}

/**
 * function copied from cart.js by wooocommerce
 * @param {jQuery object} $node 
 */
var wt_unblock_node = function( node ) {
	node.removeClass( 'processing' ).unblock();
};


/**
 *  Settings field groups
 * 	@since 2.1.1
 */
var wt_sc_field_group =
{
	Set:function()
	{
		jQuery('.wt_sc_field_group_hd .wt_sc_field_group_toggle_btn').each(function(){
			var group_id = jQuery(this).attr('data-id');
			var group_content_dv = jQuery('.wt_sc_field_group_content[data-field-group="'+group_id+'"]');
			var visibility = parseInt(jQuery(this).attr('data-visibility'));
			var group_content_in = group_content_dv.find('table').length ? group_content_dv.find('table') : group_content_dv;
			jQuery('.wt_sc_field_group_children[data-field-group="'+group_id+'"]').appendTo(group_content_in);

			if(1 === visibility)
			{
				group_content_dv.show();
			}
		});

		jQuery('.wt_sc_field_group_hd').off('click').on('click', function(){
			
			var toggle_btn = jQuery(this).find('.wt_sc_field_group_toggle_btn');
			var group_id = toggle_btn.attr('data-id');
			var visibility = parseInt(toggle_btn.attr('data-visibility'));
			var group_content_dv = jQuery('.wt_sc_field_group_content[data-field-group="'+group_id+'"]');
			
			if(1 === visibility)
			{
				toggle_btn.attr('data-visibility', 0);
				toggle_btn.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
				group_content_dv.hide();
			}else
			{
				toggle_btn.attr('data-visibility', 1);
				toggle_btn.find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
				group_content_dv.show();
			}
		});
	}
}