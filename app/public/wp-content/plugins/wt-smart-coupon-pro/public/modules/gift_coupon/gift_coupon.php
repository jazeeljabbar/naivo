<?php
/**
 * Gift coupon public
 *
 * @link       
 * @since 2.0.1   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}


use Automattic\WooCommerce\StoreApi\Exceptions\RouteException; // For adding `Exception` on API checkout validation.


if(! class_exists ( 'Wt_Smart_Coupon_Gift_Coupon' ) ) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Gift_Coupon_Public extends Wt_Smart_Coupon_Gift_Coupon
{
    public $module_base='gift_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        /* product page */
        add_action('woocommerce_single_product_summary', array($this, 'display_coupon_details'), 60,1 );
        add_action('wt_smart_coupon_gift_coupon_details', array($this,'display_coupon_details_for_variation'),10,2 );
        add_action('wp_ajax_wt_get_coupon_formatted_price',array($this, 'get_coupons_formatted_price'));
        add_action('wp_ajax_nopriv_wt_get_coupon_formatted_price',array($this, 'get_coupons_formatted_price'));

        /* checkout page */
        add_action('woocommerce_checkout_shipping', array($this, 'coupon_receiver_detail_form'), 20);
        add_action('woocommerce_checkout_process', array($this, 'validate_coupon_fields'));
        
        /* on processing order */
        add_action('woocommerce_order_status_changed', array($this, 'send_gift_coupon_email'), 10, 4);      
        add_action('woocommerce_checkout_update_order_meta', array($this, 'wt_smart_coupon_update_order_meta'));
        
        
        /** 
         *  Add block to the block list
         *  
         *  @since 2.3.0
         */
        add_filter( 'wt_sc_blocks_register', array( $this, 'register_blocks' ) );

        /**
         *  Validate gift coupon form on `block checkout`
         * 
         *  @since 2.3.0
         */ 
        add_action( 'wt_sc_blocks_validate_checkout_data', array( $this, 'api_checkout_validate_coupon_fields' ), 11, 3 ); 
    

        /**
         *  Save gift coupon form data on `block checkout`
         * 
         *  @since 2.3.0
         */ 
        add_action( 'wt_sc_blocks_save_checkout_data', array( $this, 'api_checkout_update_order_meta' ), 11, 3 );


        /**
         *  Set the non activated coupons as invalid.
         * 
         *  @since 2.3.0
         */
        add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_valid' ), 10, 2 );

        add_filter( 'wbte_sc_alter_blocks_data', array( $this, 'add_blocks_data' ) );

    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Gift_Coupon_Public();
        }
        return self::$instance;
    }


    /**
     * Display product coupons after product summary
     * @since 1.1.0
     */
    public function display_coupon_details()
    {
        global $post;
        $valid_coupons = 0;            
        $coupons = get_post_meta($post->ID,'_wt_product_coupon',true);
        echo '<ul class="available_coupons_with_product">';
        if($coupons)
        {
            $coupon_items = explode(',', $coupons);
            $coupon_items = $this->get_valid_coupons($coupon_items);           
            if(!empty($coupon_items))
            {
                foreach($coupon_items as $coupon_id )
                {
                    $coupon = new WC_Coupon( $coupon_id );

                    $expired_date = $coupon->get_date_expires();
                    $expire_text = '';
                    if($expired_date)
                    {
                        $expired_date = $expired_date->getOffsetTimestamp();
                        $expire_text = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts($expired_date, "expiry_date");
                    }

                    if($expire_text == __('Expired', 'wt-smart-coupons-for-woocommerce-pro'))
                    {
                        continue;
                    }

                    $valid_coupons ++ ;

                    if($valid_coupons ==1)
                    {
                        _e('You will get following coupon(s) when you buy this item:', 'wt-smart-coupons-for-woocommerce-pro');
                    }

                    $formatted_amount = $this->get_formatted_coupon_amount($coupon);
                    
                    if( $formatted_amount )
                    {
                        echo '<li>'; echo $formatted_amount;  echo '</li>';
                    }
                }
            }          
        }
        do_action('wt_smart_coupon_gift_coupon_details', $post, $valid_coupons);
        echo '</ul>';
    }    


    /**
     * Display gift coupon details for variable product
     * @since 1.2.4
     * @since 2.0.1 Added product exists checking
     */
    public function display_coupon_details_for_variation($post, $valid_coupons)
    {
        $product = wc_get_product($post->ID);
        if($product && $product->is_type('variable'))
        { 
        ?>
            <div class="wt_coupon_from_variation"></div>
            <script>
            jQuery(document).ready(function($) {              
               jQuery('input.variation_id').change( function(){
                    if( '' != $('input.variation_id').val())
                    {                      
                        var var_id = $('input.variation_id').val();
                        var data = {
                            'action'                    : 'wt_get_coupon_formatted_price',
                            'variation_id'              : var_id,
                            'valid_coupons'             : <?php echo $valid_coupons; ?>,
                            '_wpnonce'                  : '<?php echo wp_create_nonce( "wt_smart_coupon_gift_coupon" ); ?>'
                        };

                        
                        jQuery.ajax({
                            type: "POST",
                            url: WTSmartCouponOBJ.ajaxurl,
                            data: data,
                            success:function(response)
                            {
                                $('.wt_coupon_from_variation').html(response);
                            }
                        
                        });
                    }
                });
            });
            </script>
        <?php         
        }
    }

    /**
     * get valid coupons ( remove trashed and deleted ) from list of coupon ids
     * @since 1.2.1
     */
    public function get_valid_coupons( $coupon_ids )
    {
        if(!$coupon_ids || empty($coupon_ids))
        {
            return false;
        }

        $allowed_statuses = array('publish', 'draft');

        $return = array();
        if(is_array($coupon_ids))
        {
            foreach($coupon_ids as $coupon_id)
            {
                if(in_array(get_post_status($coupon_id), $allowed_statuses))
                {
                    $return[] = $coupon_id;
                }
            }
        }else
        {
            if(in_array(get_post_status($coupon_ids), $allowed_statuses))
            {
                $return[] = $coupon_ids;
            }
        }
        if(!empty($return))
        {
            return array_unique(array_filter($return));
        }
        return false;      
    }

    /**
     * Function to get formatted coupon amount
     * @since 1.1.0
     */
    public function get_formatted_coupon_amount($coupon)
    {
        if(!is_object( $coupon ) || !is_callable(array($coupon, 'get_id'))) {
           return false;
        }

        $coupon_id = $coupon->get_id(); 

        if(empty($coupon_id)){
            return false;
        }
        $discount_type               = $coupon->get_discount_type();
        $coupon_amount               = $coupon->get_amount();
        $is_free_shipping            = ($coupon->get_free_shipping() ? 'yes' : 'no');
        $product_ids                 = $coupon->get_product_ids();
        $excluded_product_ids        = $coupon->get_excluded_product_ids();
        $product_categories          = $coupon->get_product_categories();
        $excluded_product_categories = $coupon->get_excluded_product_categories();

        $attached_give_away_product  =  get_post_meta($coupon_id, '_wt_free_product_ids', true);

        $args = array(
            'discount_type'                 => $discount_type,
            'coupon_amount'                 => $coupon_amount,
            'is_free_shipping'              => $is_free_shipping,
            'product_ids'                   => $product_ids,
            'excluded_product_ids'          => $excluded_product_ids,
            'product_categories'            => $product_categories,
            'excluded_product_categories'   => $excluded_product_categories,
            'attached_give_away_product'    => $attached_give_away_product,
        );


        switch ($discount_type) {
            case 'fixed_cart':
            if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
                $discount_on_text = esc_html__( 'some products', 'wt-smart-coupons-for-woocommerce-pro' );
            } else {
                $discount_on_text = esc_html__( 'your entire purchase', 'wt-smart-coupons-for-woocommerce-pro' );
            }
            $amount = wc_price( $coupon_amount ) . esc_html__( ' discount on ', 'wt-smart-coupons-for-woocommerce-pro') . $discount_on_text;
            break;

        case 'fixed_product':
            if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
                $discount_on_text = esc_html__( 'some products', 'wt-smart-coupons-for-woocommerce-pro' );
            } else {
                $discount_on_text = esc_html__( 'all products', 'wt-smart-coupons-for-woocommerce-pro' );
            }
            $amount = wc_price( $coupon_amount ) . esc_html__( ' discount on ', 'wt-smart-coupons-for-woocommerce-pro') . $discount_on_text;
            break;

        case 'percent_product':
            if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
                $discount_on_text = esc_html__( 'some products', 'wt-smart-coupons-for-woocommerce-pro' );
            } else {
                $discount_on_text = esc_html__( 'all products','wt-smart-coupons-for-woocommerce-pro' );
            }
            $amount = $coupon_amount . '%' . esc_html__( ' discount on ', 'wt-smart-coupons-for-woocommerce-pro' ) . $discount_on_text;
            break;

        case 'percent':
            if ( ! empty( $product_ids ) || ! empty( $excluded_product_ids ) || ! empty( $product_categories ) || ! empty( $excluded_product_categories ) ) {
                $discount_on_text = esc_html__( 'some products', 'wt-smart-coupons-for-woocommerce-pro' );
            } else {
                $discount_on_text = esc_html__( 'your entire purchase', 'wt-smart-coupons-for-woocommerce-pro' );
            }
            $amount = $coupon_amount . '%' . esc_html__( ' discount on ', 'wt-smart-coupons-for-woocommerce-pro' ) . $discount_on_text;
            break;


        case 'wbte_sc_bogo':
            // If coupon is new BOGO coupon, then display the coupon name.
            $amount = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );
            break;

        default:
            $default_coupon_type = ( ! empty( $all_discount_types[ $discount_type ] ) ) ? $all_discount_types[ $discount_type ] : ucwords( str_replace( array( '_', '-' ), ' ', $discount_type ) );
            $coupon_amount       = apply_filters( 'wc_sc_coupon_amount', $coupon_amount, $coupon );
            $amount = sprintf( esc_html__( '%1$s coupon of %2$s', 'wt-smart-coupons-for-woocommerce-pro' ), $default_coupon_type, wc_price( $coupon_amount ) );
            $amount = apply_filters( 'wt_smart_coupon_description', $amount, $coupon );
            break;
        }

        if('yes' === $is_free_shipping && in_array( $discount_type, array( 'fixed_cart', 'fixed_product', 'percent_product', 'percent' ), true ) )
        {
            $amount = sprintf( esc_html__( '%s Free Shipping', 'wt-smart-coupons-for-woocommerce-pro' ), ( ( ! empty( $coupon_amount ) ) ? $amount . esc_html__( ' &', 'wt-smart-coupons-for-woocommerce-pro' ) : '' ) );
        }

        if($attached_give_away_product)
        {
            $attached_give_away_products  = explode( ',', $attached_give_away_product);
            if( !empty( $attached_give_away_products ) )
            {
                $product_title = '';
                foreach( $attached_give_away_products as $product_id )
                {
                    $product = wc_get_product( $product_id );
                    $product_link = get_permalink( $product_id );
                    if('' == $product_title )
                    {
                        $product_title = '<a href="'.$product_link.'">'.$product->get_title().'</a>';
                    }else
                    {
                        $product_title =  $product_title .','. '<a href="'.$product_link.'">'.$product->get_title().'</a>';
                    }
                }
                $amount = sprintf( esc_html__( '%s %s as a giveaway free product', 'wt-smart-coupons-for-woocommerce-pro' ), ( ( ! empty( $coupon_amount ) ) ? $amount . esc_html__( ' &', 'wt-smart-coupons-for-woocommerce-pro' ) : '' ),$product_title );
            }
        }
        return apply_filters('wt_smart_coupon_formatted_coupon_coupon', $amount, $args, $coupon);
    }

    /**
     * Formatted coupon price for variable product 
     * @since 1.2.4
     */
    public function get_coupons_formatted_price()
    {
        check_ajax_referer('wt_smart_coupon_gift_coupon', '_wpnonce' );
        $variation_id   = (isset( $_POST['variation_id']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['variation_id'], 'int') : 0);
        $valid_coupons  = (isset( $_POST['valid_coupons']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['valid_coupons'], 'int') : 0);
        $output = '';
        if(!empty($variation_id))
        {          
            $coupons = get_post_meta($variation_id , '_wt_product_coupon_for_variation', true );
            if($coupons)
            {
                $coupons = explode(',',$coupons);
            }else{
                $coupons=array();
            }
            $coupons = array_unique( array_filter($coupons) );
            if(is_array($coupons) && !empty($coupons))
            {
                foreach($coupons as $coupon_id)
                {
                    $coupon = new WC_Coupon( $coupon_id );

                    $expired_date = $coupon->get_date_expires();
                    $expire_text = '';
                    if( $expired_date ) {
                        $expired_date = $expired_date->getOffsetTimestamp();
                        $expire_text = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts($expired_date,  "expiry_date");
                    }
                    if( $expire_text == __('Expired', 'wt-smart-coupons-for-woocommerce-pro'))
                    {
                        continue;
                    }
                    $valid_coupons ++ ;

                    if($valid_coupons==1)
                    {
                        $output .= __('You will get following coupon(s) when you buy this item:', 'wt-smart-coupons-for-woocommerce-pro');
                    }

                    $formatted_amount = $this->get_formatted_coupon_amount($coupon);
                    
                    if( $formatted_amount )
                    {
                        $output .='<li>'.$formatted_amount.'</li>';
                    }
                }
            }
        }
        echo $output;
        exit();
    }

    /**
     * Form in checkout for specifying gift coupon receiver.
     * 
     *  @since 1.1.0
     *  @since 2.3.0    The checking section moved to separate function.
     */
    public function coupon_receiver_detail_form() { 
        
        if ( $this->is_cart_has_coupons_enabled_products() ) {

            if ( $this->is_show_gift_coupon_form() ) {
                ?>
                    <div class="wt_smart_coupon_send_coupon_wrap">
                        <h4><?php _e( 'Congrats! Unlocked gift coupon(s) with your order!', 'wt-smart-coupons-for-woocommerce-pro' ); ?></h4>
                        <p><?php _e( 'Claim your coupon(s) now!', 'wt-smart-coupons-for-woocommerce-pro' ) ?></p>
                        <ul>
                            <li class="wt_send_to_me"> 
                                <label> <input type="radio" value="wt_send_to_me" name="wt_coupon_to_do" id="wt_send_to_me" checked/>
                                    <?php _e( 'Send to me', 'wt-smart-coupons-for-woocommerce-pro' ) ?> </label>
                            </li>
                            <li class="gift_to_a_friend">
                                <label> <input type="radio" value="gift_to_a_friend"  name="wt_coupon_to_do" id="gift_to_a_friend" />
                                <?php _e( 'Gift to a friend', 'wt-smart-coupons-for-woocommerce-pro' ) ?> </label>
                            </li>
                        </ul>                  
                        <div class="gift_to_friend_form" style="display:none">
                            <div  class="wt-form-item">
                                <input type="email" name="wt_coupon_send_to" id="wt_coupon_send_to" placeholder="<?php _e( 'Coupon recipient email', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" />
                            </div>
                            <div  class="wt-form-item">
                                <textarea  name="wt_coupon_send_to_message" id="wt_coupon_send_to_message" placeholder="<?php _e( 'Message', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"></textarea>
                            </div>
                        </div>                  
                    </div>
                <?php
            } else {
                ?>
                    <input type="hidden" value="wt_send_to_me" name="wt_coupon_to_do" id="wt_send_to_me"/>
                <?php
            }
        }      
    }

    /**
     *  Validate coupon receiver field
     *  @since 1.1.0
     */
    public function validate_coupon_fields()
    {
        if(isset($_POST['wt_coupon_to_do']) && $_POST['wt_coupon_to_do']=='gift_to_a_friend')
        {
            $error=false;
            if(isset($_POST['wt_coupon_send_to']))
            {
                if(!is_email($_POST['wt_coupon_send_to']))
                {
                   $error=true; 
                }  
            }else{
                $error=true;
            }
            if($error)
            {
                wc_add_notice(__('Please enter email address to send coupon.', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
            }
        }
    }

    /**
     * Send coupon email on change order status into complete/process 
     * 
     * @since 1.1.0
     * @since 2.0.8   Added HPOS Compatibility
     * @since 2.1.0   Adding order note after email.
     * @since 2.3.0   Added a post meta to mark the coupon is send to the customer.
     */
    public function send_gift_coupon_email($order_id, $old_status, $new_status, $order)
    {
        $email_coupon_for_order_status = Wt_Smart_Coupon::get_option( 'email_gift_coupon_for_order_status', $this->module_id ); //if no value set, then it will be `completed` 

        if($new_status === $email_coupon_for_order_status)
        {
            $coupons = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupons');
            $coupons = maybe_unserialize($coupons);

            if(!empty($coupons))
            {
                WC()->mailer();
                do_action('wt_send_gift_coupon_to_customer', $order, $coupons); 

                /**
                 *  Add order note after sending email.
                 *  
                 *  @since 2.1.0
                 */
                $this->add_order_note_after_coupon_email($order, 'status_reached');

                if( $this->is_unique_giftcoupon_generate_enabled() ){

                    // Add a post meta to mark the coupon is send to the customer.
                    foreach ( $coupons as $coupon_id ) {
                        update_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon_activated', 1 );
                        
                        self::trigger_after_coupon_generated_meta_added( $coupon_id, '_wbte_sc_generated_gift_coupon_activated' );
                    } 

                }else{

                    $email = Wt_Smart_Coupon_Common::get_order_meta( $order_id, 'wt_coupon_send_to' );
                    foreach ( $coupons as $coupon_id ) {
                        self::set_coupon_email_restriction( $coupon_id, $email );
                        wp_update_post( array( 'ID' => $coupon_id, 'post_status' => 'publish' ) );
                    }

                }             
            }
            
        }
    }

    /**
     * Update order meta if any coupon attached with order item
     * 
     * @since 1.0.0
     * @since 2.0.8   Added HPOS Compatibility
     * @since 2.1.0   Updated to generate coupons based on cart item quantity
     */
    public function wt_smart_coupon_update_order_meta($order_id)
    {       
        if(isset($_POST['wt_coupon_to_do']))
        {
            $order = wc_get_order($order_id);
            $items = $order->get_items();
            $wt_coupons = array(); //master coupons to clone.
            $giveaway_obj = null;
        
            if(Wt_Smart_Coupon_Public::module_exists('giveaway_product'))
            {
                $giveaway_obj = Wt_Smart_Coupon_Giveaway_Product_Public::get_instance();
            }

            /**
             *  Loop through the order items to take the attached coupons
             */
            foreach($items as $item)
            {
                /* Skip giveaway items */
                if(!is_null($giveaway_obj) && $giveaway_obj->is_a_free_item($item))
                {
                    continue;
                }

                $product_coupons_attached = $variation_coupons_attached = array();
                $variation_id   = $item->get_variation_id();
                $product_id     = $item->get_product_id();
                $skip_parent_coupons = false; //skip parent product coupons if coupons present in the current variation. (Only applicable for variable products)

                if(0 < $variation_id) //variation
                {
                    $variation_coupons_attached = $this->get_coupons_attached($variation_id, '_wt_product_coupon_for_variation');
                    
                    if(!empty($variation_coupons_attached))
                    {
                        /**
                         *  Skip parent product coupons if coupons present in the current variation.
                         *  
                         *  @since 2.1.0
                         *  @param bool     $skip_parent_coupons    Skip parent coupons. Default: false  
                         *  @param int      $variation_id           Variation id 
                         *  @param int      $product_id             Product id 
                         */
                        $skip_parent_coupons = (bool) apply_filters('wt_sc_skip_parent_gift_coupons_when_variation_coupons_available', $skip_parent_coupons, $variation_id, $product_id);
                    }
                }

                if(!$skip_parent_coupons)
                {
                    $product_coupons_attached = $this->get_coupons_attached($product_id, '_wt_product_coupon');
                }

                $coupons_to_clone = array_unique(array_merge($product_coupons_attached, $variation_coupons_attached));

                if(!empty($coupons_to_clone))
                {
                    /**
                     *  This filter is usefull to control the number of coupons generating per cart item.
                     *  
                     *  @since 2.1.0
                     *  @param int      $quantity       Cart item quantity 
                     *  @param int      $variation_id   Variation id 
                     *  @param int      $product_id     Product id 
                     */
                    $quantity = (int) apply_filters('wt_sc_alter_gift_coupon_cart_item_quantity', $item->get_quantity(), $product_id, $variation_id);

                    $wt_coupons[$product_id. '-' . $variation_id] = array('coupons' => $coupons_to_clone, 'quantity' => $quantity);
                }
            }
            

            if(empty($wt_coupons)) //no coupons attached.
            {
                return;
            }

            $wt_coupon_to_do = sanitize_text_field($_POST['wt_coupon_to_do']);
            $from_email = sanitize_email($order->get_billing_email());

            /**
             * Auto generate coupons based on the gift items
             * @since 1.2.6
             */
            if('gift_to_a_friend' === $wt_coupon_to_do)
            {
                $coupon_email = (isset($_POST['wt_coupon_send_to']) ? sanitize_email($_POST['wt_coupon_send_to']) : '');
                
                if('' === $coupon_email) //not a valid email or email field is empty
                {
                    return;
                }

                $coupon_message = isset($_POST['wt_coupon_send_to_message']) ? sanitize_textarea_field($_POST['wt_coupon_send_to_message']) : '';      
                Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_coupon_send_to_message', $coupon_message);

            }else
            {
                $coupon_email = $from_email;
            }

            $generated_coupons = array();
            if( 'yes' === Wt_Smart_Coupon::get_option( 'generate_unique_gift_coupons', $this->module_id ) ){
                $generated_coupons = $this->generate_coupons( $wt_coupons, $coupon_email );
            }else{
                foreach( $wt_coupons as $coupon )
                {
                    if( !is_array( $coupon ) && 0 < absint( $coupon ) ) //for backward compatibility
                    {
                        $generated_coupons[] = $coupon;

                    }elseif( is_array( $coupon ) && isset( $coupon['coupons'] ) && is_array( $coupon['coupons'] ) && !empty( $coupon['coupons'] ) )
                    {
                        foreach( $coupon['coupons'] as $coupon_id )
                        {
                            $generated_coupons[] = $coupon_id;
                        }

                    }else
                    {
                        continue;
                    }
                }
            }


            Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_coupons', maybe_serialize($generated_coupons));
            Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_coupon_send_to', $coupon_email);
            Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_coupon_send_from', $from_email);

            do_action('wt_smart_coupon_after_random_gift_coupon_generated', $generated_coupons);
        }
    }

    /**
     * Helper function to get coupons attached for product
     * @param $product_ids - Product ids as array.
     * @since 1.1.0
     * @deprecated 2.1.0 In favor of Wt_Smart_Coupon_Gift_Coupon_Public::get_coupons_attached.
     */
    public function get_coupons_attached_for_products($product_ids, $variation_ids)
    {
        $coupons = array();

        if(is_array($product_ids))
        {
            foreach($product_ids as $product_id)
            {
                $coupon_ids = $this->get_coupons_attached($product_id, '_wt_product_coupon');
                $coupons = array_merge($coupons, $coupon_ids);
            }
        }

        if(is_array($variation_ids))
        {
            foreach($variation_ids as $variation_id)
            {
                $coupon_ids = $this->get_coupons_attached($variation_id, '_wt_product_coupon_for_variation');
                $coupons = array_merge($coupons, $coupon_ids);
            }
        }

        if(!empty($coupons))
        {           
            return array_filter($coupons);
        }

        return false;
    }


    /**
     *  Generate coupons from master coupons
     * 
     *  @since  1.2.6
     *  @since  2.1.0   Added option to generate coupons based on quantity.
     *  @since  2.3.0   Added new post meta for generated coupons to identify, it was a generated one.
     * 
     *  @param  array   $coupons_to_clone   Associative array of coupons based on cart item product and variation. Also accepts numeric array for backward compatibility.
     *  @param  string  $email              Email for setting restricition.
     *  @return int[]   Array of generated coupon ids.
     */
    public function generate_coupons($coupons_to_clone, $email)
    {
        $generated_coupons = array();

        foreach($coupons_to_clone as $coupon)
        {
            if(!is_array($coupon) && 0 < absint($coupon)) //for backward compatibility
            {
                $generated_coupon_id = Wt_Smart_Coupon_Admin::clone_coupon($coupon);
                
                if($generated_coupon_id)
                {   
                    self::set_coupon_email_restriction( $generated_coupon_id, $email );
                    $generated_coupons[] = $generated_coupon_id;

                    // Add a post meta to identify the coupon is a generated one.
                    add_post_meta( $generated_coupon_id, '_wbte_sc_generated_gift_coupon', 1 );
                   
                    self::trigger_after_coupon_generated_meta_added( $generated_coupon_id, '_wbte_sc_generated_gift_coupon' );
                }

            }elseif(is_array($coupon) && isset($coupon['coupons']) && is_array($coupon['coupons']) && !empty($coupon['coupons']))
            {
                $quantity = isset($coupon['quantity']) ? $coupon['quantity'] : 1;
                
                foreach($coupon['coupons'] as $master_coupon_id)
                {
                    for($i = 0; $i < $quantity; $i++) //generate coupons based on quantity
                    {
                        $generated_coupon_id = Wt_Smart_Coupon_Admin::clone_coupon($master_coupon_id); //clone the master coupon.

                        if($generated_coupon_id)
                        {   
                            self::set_coupon_email_restriction( $generated_coupon_id, $email );
                            $generated_coupons[] = $generated_coupon_id;

                            // Add a post meta to identify the coupon is a generated one.
                            add_post_meta( $generated_coupon_id, '_wbte_sc_generated_gift_coupon', 1 );
                            
                            self::trigger_after_coupon_generated_meta_added( $generated_coupon_id, '_wbte_sc_generated_gift_coupon' );

                        }
                    }
                }

            }else
            {
                continue;
            }
        }

        return $generated_coupons;
    }

    /**
     *  Get coupons attached to a product/variation
     *  
     *  @since  2.1.0
     *  @param  int         $product_id     Id of product/variation
     *  @param  string      $meta_key       The meta key to took value. Meta key is different for variation and product.
     *  @return array                       Array of coupon ids. Empty array if no coupons attached.
     */
    public function get_coupons_attached($product_id, $meta_key)
    {
        $coupons_attached = get_post_meta($product_id, $meta_key, true);
        return $coupons_attached && is_string($coupons_attached) ? explode(',', $coupons_attached) : array();
    }


    /** 
     *  Add block to the block list
     *  
     *  @since  2.3.0
     *  @param  array       $registered_blocks      Blocks data array
     *  @return array       $registered_blocks      Blocks data array
     */
    public function register_blocks( $registered_blocks ) {

        $registered_blocks['gift_coupon'] = array(
            'block_dir' => 'gift-coupon',
            'post_fields' => array( 'wt_coupon_to_do' => 'wt_send_to_me', 'wt_coupon_send_to' => '', 'wt_coupon_send_to_message' => '' ),
            'post_fields_schema' => array( 
                'wt_coupon_to_do'  => array(
                    'description' => __( 'What would you like to do with the coupon', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'type'        => array( 'string', 'null' ),
                    'readonly'    => true,
                ),
                'wt_coupon_send_to'  => array(
                    'description' => __( 'Email to send coupon', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'type'        => array( 'string', 'null' ),
                    'readonly'    => true,
                ),
                'wt_coupon_send_to_message'  => array(
                    'description' => __( 'Message while sending coupon', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'type'        => array( 'string', 'null' ),
                    'readonly'    => true,
                )
            ),
            'script_handles' => array( 'editor-js', 'editor-css', 'frontend-css', 'frontend-js' ),
        );

        return $registered_blocks;
    }

    
    /**
     *  Show gift coupons form in checkout if return true.
     * 
     *  @since  2.3.0
     *  @since  2.4.0   Checking of cart contains gift enabled products moved to new function 'is_cart_has_coupons_enabled_products'
     * 
     *  @return bool    False when "Hide 'Who to send?' box" enabled or by hook 'wt_smart_coupon_enable_gift_coupon_form'
     */
    public function is_show_gift_coupon_form() {

        return true === apply_filters( 'wt_smart_coupon_enable_gift_coupon_form', ( 'no' === Wt_Smart_Coupon::get_option( 'email_gift_coupon_to_buyer', $this->module_id ) ) );
       
    }

    /**
     *  Validate gift coupon form when checkout is done via API
     *  Hooked into: wt_sc_blocks_validate_checkout_data
     *  
     *  @since 2.3.0
     *  @param array        $data_arr   Plugin data array
     *  @param WC_order     $order      Order object
     *  @param array        $request    Array of request data
     */
    public function api_checkout_validate_coupon_fields( $data_arr, $order, $request ) {
        
        if( ( isset( $data_arr['wt_coupon_to_do'] ) && 'gift_to_a_friend' === $data_arr['wt_coupon_to_do'] )
            && ( ! isset( $data_arr['wt_coupon_send_to'] ) || ! is_email( $data_arr['wt_coupon_send_to'] ) )
        ) {

            throw new RouteException(
                'wt_sc_blocks_gift_coupon_email_missing',
                __( 'Please enter email address to send coupon.', 'wt-smart-coupons-for-woocommerce-pro' ),
                400
            ); 
        }
    }


    /**
     *  Save gift coupon form data when checkout is done via API
     *  
     *  @since 2.3.0 
     *  @param array        $data_arr   Plugin data array
     *  @param WC_order     $order      Order object
     *  @param array        $request    Array of request data
     */
    public function api_checkout_update_order_meta( $data_arr, $order, $request ) {
        
        // Order contains gift coupon
        if( ( isset( $data_arr['wt_coupon_to_do'] ) ) ) { 

            // Create POST data for below function to work.
            $_POST['wt_coupon_to_do'] = $data_arr['wt_coupon_to_do'];
            $_POST['wt_coupon_send_to'] = $data_arr['wt_coupon_send_to'];
            $_POST['wt_coupon_send_to_message'] = $data_arr['wt_coupon_send_to_message'];

            
            $this->wt_smart_coupon_update_order_meta( $order->get_id() );
        }
    }


    /**
     *  Set the generated and not activated gift coupon as invalid.
     * 
     *  @since  2.3.0
     *  @param  bool        $valid              Is coupon valid.
     *  @param  WC_Coupon   $coupon             Coupon object.
     *  @return bool        Is coupon valid.
     */
    public function is_valid( $valid, $coupon ) {
        if ( ! $valid ) {
            return $valid;
        }

        $coupon_id = $coupon->get_id();

        // This is a generated gift coupon but not activated. So set as invalid coupon.
        if ( '1' === get_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon', true ) && '1' !== get_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon_activated', true ) ) { 
            return false;
        }

        return $valid;
    }

    /**
     *  Is cart has coupon enabled products.
     * 
     *  @since  2.4.0
     *  @return bool    True when cart contains gift enabled products.
     */
    public function is_cart_has_coupons_enabled_products(){

        $cart = ( ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null );

        if ( is_null( $cart ) ) { // Cart object is not available
            return false;
        }

        $free_coupons = array();    
        
        foreach( WC()->cart->cart_contents as $product ) {
            
            $coupons_from_variation = array();
            if ( empty( $product['product_id'] ) ) {
                $product['product_id'] = ( ! empty( $product['variation_id'] ) ) ? wp_get_post_parent_id( $product['variation_id'] ) : 0;
            }
            
            if ( isset( $product['variation_id'] ) && absint( $product['variation_id'] ) > 0 ) {
                $coupons_from_variation =   get_post_meta( absint( $product['variation_id'] ) , '_wt_product_coupon_for_variation', true );          
                $coupons_from_variation = $coupons_from_variation ? explode( ',', $coupons_from_variation ) : array();
            }

            if ( empty( $product['product_id'] ) ) {
                continue;
            }
            
            $coupon_ids = get_post_meta( $product['product_id'] , '_wt_product_coupon', true );   
            $coupon_ids = $coupon_ids ? explode( ',', $coupon_ids ) : array();     
            $coupon_ids = array_merge( $coupons_from_variation, $coupon_ids );    
            $free_coupons = $coupon_ids = $this->get_valid_coupons( $coupon_ids );
            
            if ( ! empty( $free_coupons ) ) {
                break;
            }           
        }     

        return ( ! empty( $free_coupons ) );
    }

    /**
	 * Add display gift coupon form status to block.
	 * Hooked into: wbte_sc_alter_blocks_data
	 *
	 * @since 3.1.0
	 * @param  array $block_data Block data array.
	 * @return array             Block data array with gift coupon display status.
	 */
    public function add_blocks_data( $block_data ) {

        $block_data['is_display_gift_coupon'] = $this->is_show_gift_coupon_form() && $this->is_cart_has_coupons_enabled_products();
        return $block_data;
    }

}
Wt_Smart_Coupon_Gift_Coupon_Public::get_instance();