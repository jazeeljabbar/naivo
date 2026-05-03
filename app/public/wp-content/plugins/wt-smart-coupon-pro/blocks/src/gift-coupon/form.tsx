import { useCallback, useState } from '@wordpress/element';
import { __, _n } from '@wordpress/i18n';
import { RadioControl, Textarea } from '@woocommerce/blocks-components';
import { ValidatedTextInput } from '@woocommerce/blocks-checkout';


export const WtScBlocksGiftCouponForm = ({
    attributes,
    setAttributes,
}) => {

    /* Declare field values */
    const [ giftSend, setGiftSend ] = useState( 'wt_send_to_me' );
    const [ giftSendEmail, setGiftSendEmail ] = useState('');
    const [ giftSendMsg, setGiftSendMsg ] = useState('');


    /* Set Gift send option */
    const onGiftSendChange = useCallback(
        ( value ) => {           
            
            setGiftSend( value );
            setAttributes( {
                ...attributes,
                wt_coupon_to_do: value
            } );
            
        },
        [ setGiftSend, setAttributes, attributes ]
    );

    
    /* Set Gift email */
    const onGiftSendEmailChange = useCallback(
        ( value ) => {           
            
            setGiftSendEmail( value );
            setAttributes( {
                ...attributes,
                wt_coupon_send_to: value
            } );
            
        },
        [ setGiftSendEmail, setAttributes, attributes ]
    );

    
    /* Set Gift message */
    const onGiftSendMsgChange = useCallback(
        ( value ) => {           
            
            setGiftSendMsg( value );
            setAttributes( {
                ...attributes,
                wt_coupon_send_to_message: value
            } );
            
        },
        [ setGiftSendMsg, setAttributes, attributes ]
    );

	return ( 
		<div className={'wt_smart_coupon_send_coupon_wrap'}>
            <legend className={"screen-reader-text"}>{ __('Congrats! Unlocked gift coupon(s) with your order!', 'wt-smart-coupons-for-woocommerce-pro') }</legend>
			<div className={"wc-block-components-checkout-step__heading"}>
                <h2 className={"wc-block-components-title wc-block-components-checkout-step__title"}>{ __('Congrats! Unlocked gift coupon(s) with your order!', 'wt-smart-coupons-for-woocommerce-pro') }</h2>
            </div>
            <div className={ 'wc-block-components-checkout-step__container' }>
                <div className={ 'wc-block-components-checkout-step__content'}>
                    <p>{ __('Claim your coupon(s) now!','wt-smart-coupons-for-woocommerce-pro') }</p>
                    <RadioControl
                        selected= { giftSend }
                        options={ [
                            { label: __('Send to me', 'wt-smart-coupons-for-woocommerce-pro'), value: 'wt_send_to_me' },
                            { label: __('Gift to a friend','wt-smart-coupons-for-woocommerce-pro' ), value: 'gift_to_a_friend' },
                        ] }
                        onChange={ onGiftSendChange }
                    />
                    {'gift_to_a_friend' === giftSend && 
                        (<div className={'gift_to_friend_form'}>
                            <div className={'wt-form-item'}>
                                <ValidatedTextInput
                                    label={__( 'Coupon recipient email', 'wt-smart-coupons-for-woocommerce-pro')}
                                    type={'email'}
                                    name={'wt_coupon_send_to'}
                                    id={'wt_coupon_send_to'}
                                    value={giftSendEmail}
                                    onChange={ onGiftSendEmailChange }
                                />
                            </div>
                            <div className={'wt-form-item'}>
                                <Textarea
                                    onTextChange={ onGiftSendMsgChange }
                                    placeholder={ __( 'Message', 'wt-smart-coupons-for-woocommerce-pro' ) }
                                    value={ giftSendMsg }
                                />                   
                            </div>
                        </div>) }
                </div>
            </div>
		</div> 
	);
}