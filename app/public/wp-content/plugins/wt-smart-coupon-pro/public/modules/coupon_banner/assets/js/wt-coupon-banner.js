(function( $ ) {
    'use strict';

    $('document').ready(function(){
        $('.wt_dismissable').on('click',function( e ) {
            e.preventDefault();
            e.stopPropagation();
            var item = $(this).parents('.wt_banner');
            item.hide();
        });
        

        $('.wt_apply_coupon_banner').on('click',function(e){
            e.preventDefault();
            var coupon = $(this).attr('data-coupon');
            var coupon_id = $(this).attr('data-id');
            var redirect = $(this).attr('data-redirect');
            if(typeof(redirect) != "undefined" && '' != redirect )
            {
                window.location.href = redirect;
                return;
            }
            var data = {
                'coupon_code'   : coupon,
                'coupon_id'     : coupon_id,
                '_wpnonce'      : WTSmartCouponOBJ.nonces.apply_coupon
            };
    
            $.ajax({
                type: "POST",
                async: true,
                url: WTSmartCouponOBJ.wc_ajax_url + 'apply_coupon_on_click',
                data: data,
                success: function ( response ) {
                    if($('.woocommerce-cart-form').length != 0 )
                    {
                        update_cart(true);  // need only for cart page
                    }
                    wt_unblock_node($( 'div.wt_coupon_wrapper'));
                    wt_unblock_node($("div.wt-mycoupons"));
                    wt_unblock_node($("div.wt_store_credit"));

                    $( '.woocommerce-error, .woocommerce-message, .woocommerce-info' ).remove();
                    show_notice( response );
                    $(document.body).trigger("update_checkout");
                    $( document.body ).trigger("applied_coupon");

                    $('html, body').animate({
                        scrollTop: $(".woocommerce").offset().top
                    }, 1000);
                }
            });
		});

        
    });
    var show_notice = function( html_element, $target ) {
        if ( ! $target ) {
            $target = $( '.woocommerce-notices-wrapper:first' ) || $( '.cart-empty' ).closest( '.woocommerce' ) || $( '.woocommerce-cart-form' );
        }
        $target.prepend( html_element );
    };


        /**
     * Function directly using from cart.js by woocommmerce
     * @param {bool} preserve_notices 
     */
    var update_cart = function( preserve_notices ) {
        var $form = $( '.woocommerce-cart-form' );
        wt_block_node( $form );
        wt_block_node( $( 'div.cart_totals' ) );
        
        // Make call to actual form post URL.
        $.ajax( {
            type:     $form.attr( 'method' ),
            url:      $form.attr( 'action' ),
            data:     $form.serialize(),
            dataType: 'html',
            success:  function( response ) {
                update_wc_div( response, preserve_notices );
            },
            complete: function() {
                wt_unblock_node( $form );
                wt_unblock_node( $( 'div.cart_totals' ) );
            }
        });
    }

    /**
     * function directley used from cart.js by wooocommerce
     * @param { jQuery object } node 
     */
    var wt_block_node = function( node ){
        node.addClass('processing');
        if(typeof $.fn.block!=='function')
        {
            return;
        }
        node.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }
    
    /**
     * function directley used from cart.js by wooocommerce
     * @param {jQuery object} $node 
     */
    var wt_unblock_node = function( $node ) {
		
        $node.removeClass( 'processing' );

        if('function' !== typeof $.fn.unblock)
        {
            return;
        }
        $node.unblock();
    };

    /**
     * 
     * @param {string} html_str 
     * @param {bool} preserve_notices 
     */
    var update_wc_div = function( html_str, preserve_notices ) {
		var $html       = $.parseHTML( html_str );
		var $new_form   = $( '.woocommerce-cart-form', $html );
		var $new_totals = $( '.cart_totals', $html );
		var $notices    = $( '.woocommerce-error, .woocommerce-message, .woocommerce-info', $html );

		// No form, cannot do this.
		if ( $( '.woocommerce-cart-form' ).length === 0 ) {
			window.location.href = window.location.href;
			return;
		}

		// Remove errors
		if ( ! preserve_notices ) {
			$( '.woocommerce-error, .woocommerce-message, .woocommerce-info' ).remove();
		}

		if ( $new_form.length === 0 ) {
			// If the checkout is also displayed on this page, trigger reload instead.
			if ( $( '.woocommerce-checkout' ).length ) {
				window.location.href = window.location.href;
				return;
			}

			// No items to display now! Replace all cart content.
			var $cart_html = $( '.cart-empty', $html ).closest( '.woocommerce' );
			$( '.woocommerce-cart-form__contents' ).closest( '.woocommerce' ).replaceWith( $cart_html );

			// Display errors
			if ( $notices.length > 0 ) {
				show_notice( $notices );
			}
		} else {
			// If the checkout is also displayed on this page, trigger update event.
			if ( $( '.woocommerce-checkout' ).length ) {
				$( document.body ).trigger( 'update_checkout' );
			}

			$( '.woocommerce-cart-form' ).replaceWith( $new_form );
			$( '.woocommerce-cart-form' ).find( ':input[name="update_cart"]' ).prop( 'disabled', true );

			if ( $notices.length > 0 ) {
				show_notice( $notices );
			}

			update_cart_totals_div( $new_totals );
		}

		$( document.body ).trigger( 'updated_wc_div' );
    };

    /**
     * Function directley using from woocmmerce cart.js
     * @param {string} html_str 
     */
    var update_cart_totals_div = function( html_str ) {
		$( '.cart_totals' ).replaceWith( html_str );
		$( document.body ).trigger( 'updated_cart_totals' );
    };
    
})( jQuery );



function wt_banner_timer_set_value(val, val_elm)
{
    val = val.toString();
    var val_ln=val.length;
    if(val_ln==1) //lesser than 10
    {
        val='0'+val;
        val_ln++;
    }
    
    for(var i=0; i<val_ln; i++)
    {
        if(val_elm.find('span:eq('+i+')').length)
        {
            val_elm.find('span:eq('+i+')').html(val.charAt(i));
        }else
        {
            val_elm.find('span:eq('+(i-1)+')').clone().insertAfter(val_elm.find('span:eq('+(i-1)+')')).html(val.charAt(i));
        }
    }

    if(val_elm.find('span').length>val_ln) /* unused span exists. */
    {
        val_elm.find('span:gt('+(val_ln-1)+')').remove();
    }
}
function wt_banner_timer(expiry_date, elm)
{

    var expiry_date_seconds = new Date(expiry_date).getTime();

    var banner_timer_interval = setInterval(function()
    {
        var now = new Date().getTime();
        var distance = (expiry_date_seconds - now);
        
        if(0 > distance)
        {
            clearInterval(banner_timer_interval);
            /* Show expire text when coupon expired if action selected is display_text else hide the banner */  
            if('display_text' === WTSmartCouponBannerOBJ.banner_settings_expire_action)
            {
                var exp_text = ('' === WTSmartCouponBannerOBJ.banner_settings_expire_text ? WTSmartCouponBannerOBJ.banner_expired_text : WTSmartCouponBannerOBJ.banner_settings_expire_text);
                elm.find('.banner-coupon-timer').html(exp_text);
            }else{
                jQuery(elm).hide();
            }
        }

        /**
         *  Days
         */
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var days_elm=elm.find('.banner-coupon-timer .wt_timer.timer-day');
        wt_banner_timer_set_value(days, days_elm);
        
        /**
         *  Hours
         */
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var hours_elm=elm.find('.banner-coupon-timer .wt_timer.timer-hours');
        wt_banner_timer_set_value(hours, hours_elm);

        /**
         *  Minutes
         */
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var minutes_elm=elm.find('.banner-coupon-timer .wt_timer.timer-minutes');
        wt_banner_timer_set_value(minutes, minutes_elm);

        /**
         *  Seconds
         */
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        var seconds_elm=elm.find('.banner-coupon-timer .wt_timer.timer-seconds');
        wt_banner_timer_set_value(seconds, seconds_elm);

    }, 1000);
}