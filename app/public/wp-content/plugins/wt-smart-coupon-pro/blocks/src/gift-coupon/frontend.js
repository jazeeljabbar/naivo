import metadata from './block.json';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { WtScBlocksGiftCouponForm } from './form.tsx';

/** Global import */
const { registerCheckoutBlock, registerCheckoutFilters } = wc.blocksCheckout;
var is_display_gift_coupon = false;
var gift_coupon_checked = false;

const Block = ({ children, checkoutExtensionData }) => {
    const [ attributes, setAttributes ] = useState({
        'wt_coupon_to_do': 'wt_send_to_me',
        'wt_coupon_send_to': '',
        'wt_coupon_send_to_message': ''
    });
    const { setExtensionData } = checkoutExtensionData;

    useEffect(() => {
        if ( gift_coupon_checked ) {
            setExtensionData( 'wt_sc_blocks', 'wt_coupon_to_do', attributes.wt_coupon_to_do );
            setExtensionData( 'wt_sc_blocks', 'wt_coupon_send_to', attributes.wt_coupon_send_to );
            setExtensionData( 'wt_sc_blocks', 'wt_coupon_send_to_message', attributes.wt_coupon_send_to_message );
        }
    }, [ attributes, gift_coupon_checked ] );

    /** Set up an interval to check `gift_coupon_checked` state and clear it once checked. */
    useEffect( () => {
        const intervalId = setInterval( () => {
            if ( gift_coupon_checked ) {
                clearInterval( intervalId );
            }
        }, 50 );

        return () => clearInterval( intervalId ); /** Clean up the interval on unmount. */
    }, []);

    /** Move the gift coupon block after specified elements when conditions are met. */
    useEffect( () => {
        if ( is_display_gift_coupon && gift_coupon_checked ) {
            const moveGiftCouponBlock = () => {
                let targetElm = document.getElementById('payment-method')
                    || document.getElementById('shipping-option')
                    || document.getElementById('shipping-fields')
                    || document.getElementById('contact-fields');

                const giftCouponBlock = document.getElementById('wt-sc-gift-coupon-block');

                if ( targetElm && giftCouponBlock ) {
                    targetElm.after( giftCouponBlock );
                }
            };

            /** Call the function to move the element */
            moveGiftCouponBlock();
        }
    }, [ is_display_gift_coupon, gift_coupon_checked ] ); /** Run when these dependencies change. */

    return (
        is_display_gift_coupon ? (
            <fieldset
                className='wt-sc-gift-coupon-block wc-block-components-checkout-step wc-block-components-checkout-step--with-step-number'
                id='wt-sc-gift-coupon-block'
            >
                <WtScBlocksGiftCouponForm attributes={attributes} setAttributes={setAttributes} />
            </fieldset>
        ) : ''
    );
};

const options = {
    metadata,
    component: Block
};

registerCheckoutBlock(options);

const isDisplayGiftCouponCheck = (defaultValue, extensions, args) => {
    is_display_gift_coupon = args?.cart?.extensions?.wt_sc_blocks?.is_display_gift_coupon;
    gift_coupon_checked = true;

    /** Return the default value, because we are not altering any value here. */
    return defaultValue;
};

registerCheckoutFilters('wt-sc-check-is-display-gift-coupon', {
    itemName: isDisplayGiftCouponCheck,
});
