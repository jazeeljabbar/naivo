
// Global import
const { registerCheckoutFilters } = window.wc.blocksCheckout;


const updateCartItemImage = ( defaultValue, extensions, args ) => {

	const cartitem_giftcard_image = args?.cart?.extensions?.wt_sc_blocks?.cartitem_giftcard_image;
    const cart_item_key = args?.cartItem?.key;

    if (cart_item_key && cartitem_giftcard_image && cartitem_giftcard_image[cart_item_key] ) {
        if(args.cartItem.images.length){
            args.cartItem.images[0].src= cartitem_giftcard_image[cart_item_key];
            args.cartItem.images[0].thumbnail= cartitem_giftcard_image[cart_item_key];
        }else{
            args.cartItem.images = [ {
                'id': 0, 
                'src': cartitem_giftcard_image[cart_item_key], 
                'thumbnail': cartitem_giftcard_image[cart_item_key],
                'srcset': '',
                'sizes': '',
                'name': '',
                'alt': '',
            }];
        }
    }

	return defaultValue;
}

registerCheckoutFilters( 'wt-sc-blocks-update-cart-item-image', {
    itemName: updateCartItemImage,
} );