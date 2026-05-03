/**
 *  Javascript section of banner styling
 * 	@since 1.3.5
 */	
(function( $ ) {
	//'use strict';
	$(function() {
		setTimeout(function(){
			jQuery('.wt_sc_accord:eq(0) .wt_sc_accord_hd').trigger('click'); /* open the first panel */
		}, 100);
		var banner_preview_elm=$('.wt_sc_coupon_banner_preview');
		$('.wt_sc_coupon_banner_right .wt_sc_color_picker').wpColorPicker({
			'change':function(event, ui) { 
				var selected_color=ui.color.toString();
				var input_elm=$(event.target);
				input_elm.val(selected_color);

				var element_class=input_elm.attr('data-element');
				var prop=input_elm.attr('data-prop');
				var target_elm=banner_preview_elm.find('.'+element_class);
				target_elm.css(prop, selected_color);
				if(prop=='border-color'){
					target_elm.css({'border':'solid 1px '+selected_color});
				}				
			}
		});

		/* read HTML data and populate the values */
		$('.wt_sc_coupon_banner_right .wt_sc_color_picker').each(function(){
			var input_elm=$(this);
			var element_class=input_elm.attr('data-element');
			var prop=input_elm.attr('data-prop');
			var target_elm=banner_preview_elm.find('.'+element_class);
			if(prop=='border-color')
			{
				var color_vl=target_elm[0].style.borderColor;
			}else{
				var color_vl=target_elm.css(prop);
			}
			
			var color_preview_elm=input_elm.parents('.wp-picker-container').find('.wp-color-result');
			if(typeof color_vl!='undefined' && wt_sc_get_alpha_value_from_color(color_vl)==0)
			{
				var color_string='transparent';
				var color_vl='';				
			}else
			{
				var color_string=Color(color_vl).toString();
				var color_vl=color_string;
			}

			/* set current color preview */
			color_preview_elm.css({'background-color':color_string});
			input_elm.val(color_vl).attr('data-default', color_string);
		});


		$('.wt_sc_on_keyup').each(function(){
			var input_elm=$(this);
			var element_class=input_elm.attr('data-element');
			var prop=input_elm.attr('data-prop');
			var target_elm=banner_preview_elm.find('.'+element_class);
			
			if(prop=='text'){
				var val=target_elm.text().trim();
			}else if(prop=='width')
			{
				var val=target_elm.css(prop);
				if(val=='100%')
				{
					val='';
				}else{
					val=parseInt(val);
				}
			}
			else
			{				
				var val=target_elm.css(prop);
				if(input_elm.parent('.wt_sc_inptgrp').length>0)
				{
					val=parseInt(val);
				}
			}
			input_elm.val(val);
		});

			var temp_elm=$('<div>'+banner_preview_elm[0].outerHTML+'</div>').appendTo('body'); /* add to a temp element for getting the actual visibility of the element. Reason: The preview elements are under JS tab */
			$('.wt_sc_banner_item_toggle').each(function(){
				var input_elm=$(this);
				var element_class=input_elm.attr('data-element');
				var prop=input_elm.attr('data-prop');
				var target_elm=temp_elm.find('.'+element_class);
				if(target_elm.is(':visible'))
				{
					input_elm.prop('checked', true);
				}else{
					input_elm.prop('checked', false);
				}
			});
			temp_elm.remove();
		


		/* binding action events */
		$('.wt_sc_on_keyup').on('keyup', function(){
			var input_elm=$(this);
			var val=input_elm.val();
			var element_class=input_elm.attr('data-element');
			var prop=input_elm.attr('data-prop');
			var target_elm=banner_preview_elm.find('.'+element_class);
			if(prop=='text'){
				target_elm.text(val);
			}else{
				var unit=input_elm.attr('data-unit');
				if(typeof unit!=='undefined'){
					val=val+unit;
				}
				target_elm.css(prop, val);
			}
		});

		/**
		 * 	Banner item type change
		 */
		$('.wt_sc_banner_display_type').on('change', function(){

			var type_classes=$(".wt_sc_banner_display_type option").map(function() {
		        return 'show_as_'+this.value;
		    }).get().join(" ");
		    $('.wt_banner').removeClass(type_classes);

		    var selected_type=$(this).val();
		    $('.wt_banner').addClass('show_as_'+selected_type);

		    $('.wt_banner').removeAttr('style');


		    var bg_color= $('.wt_sc_banner_bg_color').val().trim();
			var border_color=$('.wt_sc_banner_border_color').val().trim();
			if(bg_color)
			{
				$('.wt_banner').css("background-color", bg_color);
			} 
			if(border_color)
			{
				$('.wt_banner').css("border-color", border_color);
			}

			if(selected_type=='widget')
			{
				var height = $('.wt_sc_banner_height').val().trim();
				var width = $('.wt_sc_banner_width').val().trim();
				if(height!="" && height>0) {
					$('.wt_banner').css("height", height);
				}else{
					$('.wt_sc_banner_height').val(parseInt($('.wt_banner').css("height")));
				}
				if(width!="" && width>0) {
					$('.wt_banner').css("width", width);
				}else{
					$('.wt_sc_banner_width').val(parseInt($('.wt_banner').css("width")));
				}
			}

		});

		$('.wt_sc_banner_item_toggle').on('change', function(){
			var input_elm=$(this);
			var element_class=input_elm.attr('data-element');
			var target_elm=banner_preview_elm.find('.'+element_class);
			if(input_elm.is(':checked'))
			{
				target_elm.show();
			}else{
				target_elm.hide();
			}
		});

		$('.wt_sc_side_panel_minmax').on('click', function(){
			var open_status=$(this).attr('data-open');
			if(open_status==1){
				$('.wt_sc_coupon_banner_right').addClass('wt_sc_coupon_banner_right_full_screen');
				$('.wt_sc_coupon_banner_left').addClass('wt_sc_coupon_banner_left_full_screen');
				$(this).attr({'data-open':0, 'title':wt_sc_coupon_banner_params.msgs.click_maximize});
				$(this).find('.dashicons').removeClass('dashicons-arrow-right').addClass('dashicons-arrow-left');
			}else{
				$('.wt_sc_coupon_banner_right').removeClass('wt_sc_coupon_banner_right_full_screen');
				$('.wt_sc_coupon_banner_left').removeClass('wt_sc_coupon_banner_left_full_screen');
				$(this).attr({'data-open':1, 'title':wt_sc_coupon_banner_params.msgs.click_minimize});
				$(this).find('.dashicons').removeClass('dashicons-arrow-left').addClass('dashicons-arrow-right');
			}
		});

	});
})( jQuery );