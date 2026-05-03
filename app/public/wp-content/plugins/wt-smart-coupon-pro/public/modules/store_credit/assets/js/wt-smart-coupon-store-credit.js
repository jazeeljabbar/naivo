jQuery(function ($) {
    "use strict";

    /** design store credit */
   
    $('document').ready( function() {

        /**
         *  Enable date picker
         */
        $("#wt_smart_coupon_schedule_field").datepicker({
            minDate : 1,
            dateFormat: wt_sc_store_credit_params.store_credit_date_format,
            onSelect:function(dateText, inst)
            {
                $('[name="wt_smart_coupon_schedule_d"]').val(inst.selectedDay); 
                $('[name="wt_smart_coupon_schedule_m"]').val(parseInt(inst.selectedMonth)+1); 
                $('[name="wt_smart_coupon_schedule_y"]').val(inst.selectedYear);
            }
        });

        /**
         *  Toggle date picker field
         */
        $("#wt_smart_coupon_send_today").on("click", function(){
            if($(this).is(":checked")){
                $(".wt_smart_coupon_schedule_field_form_group").hide();
                $("#wt_smart_coupon_schedule_field").val("");
            }else{
                $(".wt_smart_coupon_schedule_field_form_group").show();
                $("#wt_smart_coupon_schedule_field").focus();
            }
        });
        if($("#wt_smart_coupon_send_today").is(":checked")){
            $(".wt_smart_coupon_schedule_field_form_group").hide();
        }
        

        /** 
        * Credit amount 
        */
        function wt_sc_set_credit_amount(credit_value)
        {
            credit_value=(credit_value=='' ? 0 : credit_value);
            $('#wt_user_credit_amount').val(credit_value); /* user input field for credit amount */
            $('#wt_credit_amount').val(credit_value); /* hidden input for credit amount */
            $('.wt_coupon-code-block .coupon_price span').text(credit_value); /* preview element */
        }
        
        /* Denomination */
        $('.wt_credit_denominations label').on('click', function(){
            var radio_input=$(this).siblings('input[name="credit_denominaton"]');
            radio_input.prop('checked', true);
            var credit_value=radio_input.val();
            wt_sc_set_credit_amount(credit_value);
        });

        /**
         *  Only single predefined. So set the predefined amount as default value.
         *  Since 2.4.0
         */
        if( 0 < jQuery( '#wbte_is_single_predefined' ).length ){
            jQuery( '.wt_credit_denominations label' ).trigger( 'click' );
        }

        /* Custom credit amount */
        $('#wt_user_credit_amount').on('change',function(e){
            var credit_value = $(this).val();
            
            $('.wt_sc_credit_denomination input[name="credit_denominaton"]').prop('checked', false);
            $('.wt_sc_credit_denomination input[name="credit_denominaton"]').filter(function(){
                return $(this).val()==credit_value;
            }).prop('checked', true);

            wt_sc_set_credit_amount(credit_value);
        });

        
        /* Gift card design change */   
        $('.wt_gift_coupn_designs li img').on('click',function()
        {
            var elm=$(this);
            var parent_li=$(this).parents('li');

            var image = elm.attr('src');
            var design = elm.attr('design');
            var top_bg_color = elm.attr('top_bg');
            var bottom_bg_color = elm.attr('bottom_bg');

            $('.wt_gift_coupn_designs li').removeClass('active');
            parent_li.addClass('active');

            $('.wt_gift_coupon_preview_caption').html(parent_li.find('.wt_sc_gift_card_caption_hidden').html());
            $('.wt_gift_coupon_preview_image img').attr({'src': image, 'alt': design});

            $('.wt_gift_coupon_preview_caption').css('background-color', top_bg_color);
            $('.coupon-message-block').css('background-color', bottom_bg_color);
            $('#wt_credit_coupon_image').val(design);

        });

        var template_img_found=false;
        var template_id = ($('[name="wt_credit_coupon_image"]').length ? $('[name="wt_credit_coupon_image"]').val().trim() : '');
        
        if("" !== template_id)
        {
            var template_img = $('.wt_gift_coupn_designs li img[design="'+template_id+'"]');
            
            if(template_img.length)
            {
                template_img.trigger('click');
                var template_img_found=true;
            }
        }
        if(!template_img_found)
        {
            $('.wt_gift_coupn_designs li:eq(0) img').trigger('click');
        }

        /* Gift card message preview */
        $('#wt_credit_coupon_send_to_message').on('keyup paste change input',function(){
            $('.coupon-message-block .coupon-message').text($(this).val());
        });

        /* Gift card message from name */
        $('#wt_credit_coupon_from').on('keyup paste change input',function(){
            $('.coupon-message-block .coupon-from span').text($(this).val());
        });

    });

});