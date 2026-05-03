import { ValidatedTextInput } from '@woocommerce/blocks-checkout';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { WtScBlocksGiftCouponForm } from './form.tsx';
import './editor.scss';

setTimeout(function(){
    if(document.querySelector('[data-type="woocommerce/checkout-payment-block"]') && document.querySelector('[data-type="wt-sc-blocks/gift-coupon"]')) {
        document.querySelector('[data-type="woocommerce/checkout-payment-block"]').after(document.querySelector('[data-type="wt-sc-blocks/gift-coupon"]'));
    }
}, 1000);

export const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    
    return (
        <div {...blockProps}>
            <fieldset className={ 'wt-sc-gift-coupon-block wc-block-components-checkout-step wc-block-components-checkout-step--with-step-number' } id={ 'wt-sc-gift-coupon-block' }>          
                <WtScBlocksGiftCouponForm attributes={ attributes } setAttributes={ setAttributes } />
            </fieldset>
        </div>
    )
};