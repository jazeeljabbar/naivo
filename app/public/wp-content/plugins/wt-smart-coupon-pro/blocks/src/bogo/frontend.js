import { useDispatch } from '@wordpress/data';
import metadata from './block.json';
import { useState } from '@wordpress/element';

const { registerCheckoutFilters, extensionCartUpdate, registerCheckoutBlock } = window.wc.blocksCheckout;

// Declare some global variables.
var wbte_giveaway_eligible_message_timeout = null;
var wbte_isFirefox = typeof InstallTrigger !== 'undefined';
var wbte_giveaway_products_timeout = null;
var wbte_cart_obj = null;
var auto_bogo_coupons  = [];

// Global variables for changing auto BOGO coupon code to title.
var wbte_sc_bogo_title_change_timeout = null;

const modifyItemName = ( defaultValue, extensions, args ) => {

    wbte_cart_obj = args?.cart;
    
    const giveawayText = args?.cart?.extensions?.wt_sc_blocks?.cartitem_bogo_text;

    const giveawayCheapExpText = args?.cart?.extensions?.wt_sc_blocks?.cartitem_bogo_cheap_exp_text;

    const cartItemKey = args?.cartItem?.key;

    if ( cartItemKey && giveawayText && giveawayText[ cartItemKey ] ) {
        // Update quantity limits
        args.cartItem.quantity_limits.maximum = args.cartItem.quantity;
        args.cartItem.quantity_limits.minimum = args.cartItem.quantity;

        // Modify the defaultValue (item name) by adding giveaway text
        defaultValue += giveawayText[ cartItemKey ];

    }

    if ( cartItemKey && giveawayCheapExpText && giveawayCheapExpText[ cartItemKey ] ) {

        defaultValue += giveawayCheapExpText[ cartItemKey ];
    }

    jQuery(document).ready(function($) {

        // Remove the text-decoration from the product name for cheap/expensive BOGO giveaway products.
        $('.wc-block-components-product-name').has('.wbte_sc_bogo_cheap_exp_cart_item, .wbte_sc_bogo_bxgx_product_under_msg').css('text-decoration', 'none');
    });

    //Show giveaway available message.
    const { createInfoNotice, removeNotice, removeAllNotices } = useDispatch( 'core/notices' );
    const context = 'wc/cart';
    const msg_id = 'wbte-giveaway-eligible-msg';
    const giveaway_eligible_message = args?.cart?.extensions?.wt_sc_blocks?.bogo_eligible_message;

    clearTimeout( wbte_giveaway_eligible_message_timeout );

    wbte_giveaway_eligible_message_timeout = setTimeout( function(){
        if( giveaway_eligible_message ){
            createInfoNotice( giveaway_eligible_message, { id: msg_id, type: 'default', isDismissible: false , context } );
        }else{
            removeNotice( msg_id, context );
        }      
    }, 10 );

    return defaultValue;
};

registerCheckoutFilters('wt-sc-bogo-blocks-update-cart', {
    itemName: modifyItemName,
});

document.addEventListener('wbte_sc_checkout_value_updated', function(e){ 
    extensionCartUpdate( {
        namespace: 'wbte-sc-blocks-update-checkout',
        data: {},
    } );
});

// Webkit browsers (other than Firefox) requires an extra refresh to show the giveaway products.
if ( 'undefined' !== typeof WTSmartCouponOBJ && ! wbte_isFirefox && "1" === WTSmartCouponOBJ.is_cart ) {
    
    setTimeout(function(){      
        if ( wbte_cart_obj ) {

            let html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;
            let temp_elm = document.createElement("div");
            temp_elm.innerHTML = html;
            let text = temp_elm.textContent || temp_elm.innerText || "";

            // Only do the refresh when giveaway product HTML exists.
            if ( text.trim() ) { 
                extensionCartUpdate( {
                    namespace: 'wbte-sc-blocks-update-checkout',
                    data: {},
                } );
            }
        }
    }, 100);
}

/** 
 *  Giveaway products block
 */
const Block = ({ children, checkoutExtensionData, cart }) => {
    
    const [productsHtml, setProductsHtml] = useState('');
    
    if ( wbte_cart_obj ) {

        clearTimeout( wbte_giveaway_products_timeout );

        wbte_giveaway_products_timeout = setTimeout(function(){
            const giveaway_products_html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;
            setProductsHtml( giveaway_products_html );
        }, 10);
    }

    return (
        <div
          dangerouslySetInnerHTML={{__html: productsHtml}}
        />
      );
}

registerCheckoutBlock( {
    metadata,
    component: Block
} );

const hideApplyRemoveCouponNotice = ( defaultValue, extensions, args ) => {

    const bogoCouponCodes = auto_bogo_coupons ? Object.keys( auto_bogo_coupons ) : [];

    if ( bogoCouponCodes.includes( args?.couponCode ) ) {

        /** Hiding notice because, notice contain coupon code which is not required for BOGO coupons. */
        return false;
    }

    return defaultValue;
};

/**
 * Hide remove bogo coupon notice for BOGO coupons.
 */
registerCheckoutFilters( 'wt-sc-bogo-blocks-disable-remove-notice', {
    showRemoveCouponNotice: hideApplyRemoveCouponNotice,
} );

/**
 * Hide apply bogo coupon notice for BOGO coupons.
 */
registerCheckoutFilters( 'wt-sc-bogo-blocks-disable-apply-notice', {
    showApplyCouponNotice: hideApplyRemoveCouponNotice,
} );


/**
 * BOGO offer title instead of coupon code for auto BOGO coupons.
 * Hide the fee line items for BOGO coupons.
 */
const modifyCartItemClass = ( defaultValue, extensions, args ) => {
    
    let auto_bogo_coupons = args?.cart?.extensions?.wt_sc_blocks?.auto_bogo_coupons;

    clearTimeout( wbte_sc_bogo_title_change_timeout );
    wbte_sc_bogo_title_change_timeout = setTimeout( function() {
        
        // Change the coupon code to title for BOGO coupons.
        if( 'object' === typeof auto_bogo_coupons ){
            jQuery('.wc-block-components-totals-discount__coupon-list-item').each( function() {
                var coupon_code = jQuery(this).find('.wc-block-components-chip__text').text().trim();
                if( 'undefined' !== typeof auto_bogo_coupons[coupon_code] ){
                    jQuery(this).find('.wc-block-components-chip__text').text(auto_bogo_coupons[coupon_code]);
                }
            });
        }
        
    }, 500 );

    // Return the default value, because we are not altering any value here.
    return defaultValue;
};

registerCheckoutFilters( 'wt-sc-bogo-blocks-code-alter', {
    cartItemClass: modifyCartItemClass,
} );
