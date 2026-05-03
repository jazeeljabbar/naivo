<?php

/**
 * Store credit use on order.
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Store_Credit_Public') || !class_exists('WC_Query')) 
{
    return;
}

class Wt_Smart_Coupon_Store_Credit_Display  extends WC_Query 
{
    private static $instance = null;

    public $list_endpoint_key = ''; // store credit list page
    public $list_endpoint= 'store-credit'; 
    
    public $detail_endpoint_key = ''; //store credit detail page
    public $detail_endpoint= 'view-store-credit'; 

    protected $endpoint_title;

    public function __construct()
    {
        
        add_action( 'init', array( $this, 'init' ) );

        add_action('init', array($this, 'add_endpoints'));

        if(!is_admin())
        {
            add_filter('query_vars', array($this, 'add_query_vars'), 0);

            add_filter('woocommerce_account_menu_items', array($this, 'store_credit_menu'));

            add_filter( 'woocommerce_endpoint_'.$this->list_endpoint.'_title', array( $this, 'get_current_endpoint_title' ), 10, 3 );
            add_filter( 'woocommerce_endpoint_'.$this->detail_endpoint.'_title', array( $this, 'get_current_endpoint_title' ), 10, 3 );
        }

        // Change the page title to store credit.
        add_filter('the_title', array($this, 'endpoint_title'));

        
        add_filter('wt_coupon_added_email_restriction_to_the_user', array($this, 'exclude_store_credit_from_user_specific_coupons'), 10, 2);            
        add_filter('wt_smart_coupon_used_coupons', array($this, 'exclude_store_credit_from_user_specific_coupons'), 10, 2);
        add_action('wt_my_store_credit', array($this, 'display_store_credit_in_my_account'), 10, 1);
        add_action('wt_my_store_credit', array($this, 'display_expired_store_credit'), 12, 1);

        add_action('wt_my_store_credit', array($this, 'display_used_store_credit'), 11, 1);
        add_action('wt_store_credit_history', array($this, 'display_credit_history_table'), 10, 1);

        add_filter('wc_get_template', array($this, 'add_view_subscription_template'), 10, 5);
        
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Display();
        }
        return self::$instance;
    }

    /**
     * Initializes the store credit endpoints and titles.
     * 
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init(){

        $store_credit_myaccount_general_settings = Wt_Smart_Coupon::get_settings();

        $this->list_endpoint_key = $store_credit_myaccount_general_settings[ 'wbte_account_storecredit_endpoint']; // store credit list page
        $this->detail_endpoint_key = 'wt-view-store-credit'; //store credit detail page 

        $this->endpoint_title = $store_credit_myaccount_general_settings[ 'wbte_account_storecredit_page_title'];

        $this->query_vars = array(
            'store-credit'      => $this->list_endpoint_key,
            'view-store-credit' => $this->detail_endpoint_key,        
        );   

        if( ! is_admin() ){
            // Store credit page contents in My account page
            add_action( 'woocommerce_account_'.$this->list_endpoint_key.'_endpoint', array( $this, 'list_page_content' ) );
            add_action( 'woocommerce_account_'.$this->detail_endpoint_key.'_endpoint', array( $this, 'detail_page_content' ) );
        }
    }

    public function store_credit_menu($items)
    {
        if( wc_string_to_bool( Wt_Smart_Coupon::get_option( 'wbte_sc_enable_myaccount_storecredit_page' ) ) ){
            $logout = null;

            if(isset($items['customer-logout']))
            {
                $logout = $items['customer-logout'];
                unset($items['customer-logout']);
            }
        
            $items[$this->list_endpoint_key] = __( $this->endpoint_title, 'wt-smart-coupons-for-woocommerce-pro' );

            if(!is_null($logout))
            {
                $items['customer-logout'] = $logout;
            }
        }
        

        return $items;
    }

    public function endpoint_title($title)
    {
        if (in_the_loop() && is_account_page() && is_main_query() && is_page())
        {
            global $wp;
            foreach($this->query_vars as $key => $query_var)
            {
                $is_coupon_query=apply_filters('is_wt_smart_coupon_query', isset($wp->query_vars[$query_var]), $query_var);
                if($is_coupon_query)
                {
                    $title = $this->get_endpoint_title($key);
                    remove_filter('the_title', array($this, __FUNCTION__), 11);
                }
            }
        }
        return $title;
    }

    public function detail_page_content()
    {             
        include(dirname(dirname(__FILE__)).'/views/my-account/view-store-credit.php');
    }

    public function list_page_content()
    {
        include(dirname(dirname(__FILE__)).'/views/my-account/my-store-credit.php');
    }

    public function get_current_endpoint_title($title, $endpoint, $action="")
    {
        global $wp;
        switch ($endpoint)
        {
            case $this->detail_endpoint:
                $coupon_id = $wp->query_vars['wt-view-store-credit'];
                $title = ( $coupon_id ? sprintf( __( 'Store Credit #%s', 'wt-smart-coupons-for-woocommerce-pro' ), $coupon_id ) : '' );
                break;
            case $this->list_endpoint:
                    $title = __( $this->endpoint_title, 'wt-smart-coupons-for-woocommerce-pro' );
                break;
            default:
                $title = '';
                break;
        }
        return $title;
    }

    /**
     * Exclude store credit from user specific coupons.
     */
    public function exclude_store_credit_from_user_specific_coupons($coupons, $user)
    {
        if(!empty($coupons))
        {
            foreach( $coupons as $key => $coupon ){
                $coupon_obj = new WC_coupon( $coupon);
                if( Wt_Smart_Coupon_Store_Credit::is_store_credit( $coupon_obj ) ) {
                    unset($coupons[$key] );
                }
            }
        }
        return $coupons;
    }

    /**
     * Display store credits available in my account
     */
    public function display_store_credit_in_my_account($user)
    {
        $store_credits = $this->get_store_credit_for_a_user($user, true);
        
        echo '<div class="wt_store_credit_main">';
        echo '<h4>';
            _e("Store Credits", "wt-smart-coupons-for-woocommerce-pro"); 
        echo '</h4>';          
        if(!empty($store_credits))
        {
            echo '<div class="wt_coupon_wrapper wt_store_credit">';
            //inject CSS for coupon block
            Wt_Smart_Coupon_Public::print_coupon_default_css();

            $e = 0;

            foreach( $store_credits as $coupon_id )
            {
                $coupon_obj = new WC_Coupon( $coupon_id );
                $is_activated = get_post_meta($coupon_id, '_wt_smart_coupon_credit_activated', true);    

                $expiry_date = Wt_Smart_Coupon_Public::get_coupon_expires($coupon_obj);                                        
                
                if( (!empty($is_activated) && !$is_activated) || (!empty($expiry_date) && !(current_time('timestamp') < $expiry_date)))
                {
                    continue;
                }
                
                $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon_obj);
                $coupon_data['display_on_page'] = 'my_account';

                if(0 === $e) //to print coupon CSS in first template, to avoid same multiple CSS blocks
                {
                    echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "available_coupon", true);
                }else
                {
                    echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data);
                }

                $e++;
            }
            if(0 === $e)
            {
                _e("Sorry, you don't have any available Store Credits.", "wt-smart-coupons-for-woocommerce-pro");

            }
            echo '</div>';
        }else
        {
            _e("Sorry, you don't have any available Store Credits.", "wt-smart-coupons-for-woocommerce-pro");
        }

        echo '</div>';     
    }

    /**
     * Get store credit for a user.
     * 
     * @since 2.1.1  Modified the queries for available, used and expired credits
     *               Added a filter to alter the number of post ids to be fetched
     */
    public function get_store_credit_for_a_user( $user, $available = false, $used = false )
    {
        if( !$user  ) {
            $user = wp_get_current_user();
        }
        $email = $user->user_email;
        $user_credits = array();

        $limit = apply_filters('wt_sc_limit_store_credit_coupon_display', 10);

        if($user && $email)
        {
            $args = array (
                'post_type' => 'shop_coupon',
                'posts_per_page' => $limit,
                'meta_query' => array (
                    array (
                        'key' => 'customer_email',
                        'value' => $email,
                        'compare' => 'LIKE'
                    ),
                    array (
                        'key' => 'discount_type',
                        'value' => 'store_credit',
                        'compare' => 'LIKE'
                    ),
                ),
            ); 

            if($available)
            {
                $args['meta_query'][] =  array(
                    'key' => 'coupon_amount',
                    'value' => '0',
                    'compare' => '>',
                    'type' => 'DECIMAL',
                );

            }
            if($used) // used
            {
                $args['meta_query'][] =  array(
                    'key' => 'coupon_amount',
                    'value' => '0',
                    'compare' => '=',
                    'type' => 'DECIMAL',
                );
            }
            
            $the_query = new WP_Query($args);
            
            if($the_query->have_posts())
            {
                while($the_query->have_posts())
                {
                    $the_query->the_post();

                    $user_credits[] = get_the_ID();
                }
            }
        }

        return $user_credits;
    }

    /**
     * Display expired coupon in my store credit 
     */
    public function display_expired_store_credit($user)
    {
        if( in_array( 'expired_coupons', Wt_Smart_Coupon::get_option( 'wbte_account_storecredit_additional_display' ) ) ){
            $store_credits = $this->get_store_credit_for_a_user( $user );
            echo '<div class="wt_store_credit_main">';
            echo '<h4>';
                _e("Expired Credits","wt-smart-coupons-for-woocommerce-pro"); 
            echo '</h4>';
                
            if( !empty( $store_credits ) )
            {
                echo '<div class="wt_coupon_wrapper wt_store_credit">';
                
                //inject CSS for coupon block
                Wt_Smart_Coupon_Public::print_coupon_default_css();

                $e = 0;

                foreach( $store_credits as $coupon_id )
                {
                    $coupon_obj = new WC_Coupon( $coupon_id );
                    $expiry_date = Wt_Smart_Coupon_Public::get_coupon_expires($coupon_obj);                 
                    
                    if( !empty($expiry_date) && !(current_time('timestamp') < $expiry_date) )
                    {
                        $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon_obj );
                        $coupon_data['display_on_page'] = 'my_account';
                        
                        if(0 === $e) //to print coupon CSS in first template, to avoid same multiple CSS blocks
                        {
                            echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "expired_coupon", true);
                        }else
                        {
                            echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "expired_coupon");
                        }

                        $e++;
                    }

                }
                if(0 === $e)
                {
                    _e("Sorry, you don't have any Expired Store Credits.", "wt-smart-coupons-for-woocommerce-pro");

                }
                echo '</div>';    
            }else
            {
                _e("Sorry, you don't have any Expired Store Credits.", "wt-smart-coupons-for-woocommerce-pro");

            }
            echo '</div>';
        } 
    }

    /**
     * Display the Store Credit history
     */
    public function display_credit_history_table( $coupon )
    {
        if(  ! is_object( $coupon ) ) {
            $coupon  = new WC_coupon( $coupon ); 
        }
        
        if ( ! is_object( $coupon ) ||  !is_a( $coupon, 'WC_Coupon' ) || ! Wt_Smart_Coupon_Store_Credit::is_store_credit( $coupon ) )
        {
            return false;
        }

        $coupon_id =  $coupon->get_id();
        $credithistories = get_post_meta( $coupon_id, 'wt_credit_history', true );

        $credit_history='<div class="wt_back_to_store_credit"><a class="woocommerce-button button wt_back_to_store_credit_btn" href="'.esc_attr(get_permalink(get_option('woocommerce_myaccount_page_id')).$this->list_endpoint_key).'">'.__('Back to My store credits', 'wt-smart-coupons-for-woocommerce-pro').'</a></div>';

        $credit_history .='<table class="woocommerce-table shop_table shop_table_responsive wt_store_credit_history">
                <tr>
                    <th>'.__('Date','wt-smart-coupons-for-woocommerce-pro').'</th>
                    <th>'.__('Order ID','wt-smart-coupons-for-woocommerce-pro').'</th>
                    <th>'.__('Credit / Debit','wt-smart-coupons-for-woocommerce-pro').'</th>
                    <th>'.__('Balance','wt-smart-coupons-for-woocommerce-pro').'</th>
                </tr>';
                $created_on = $coupon->get_date_created();
                $date_created =  $created_on->date( 'Y-m-d H:i') ;
                $coupon_initial_amount = get_post_meta( $coupon_id,'_wt_smart_coupon_initial_credit', true );
                if( ! $coupon_initial_amount ) {
                    $coupon_initial_amount = $this->get_credit_initial_amount( $coupon );
                }
                $credit_history .='<tr>
                    <td>'.$date_created.' </td>
                    <td>'.__('Recieved Credit','wt-smart-coupons-for-woocommerce-pro').'</td>
                    <td>'.'<span class="wt-credited">'.Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_initial_amount )  .'</span></td>
                    <td>'.Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_initial_amount ).' </td>
                </tr>';
                
        if( !empty( $credithistories ) )
        {
            foreach( $credithistories as $date => $credithistory )
            {
                $order = isset( $credithistory['order'] )? '#'.$credithistory['order'] : '-';
                $credit_used = ( isset( $credithistory['credit_used'] ) && $credithistory['credit_used'] !='-' )? '<span class="wt-debited">'. Wt_Smart_Coupon_Admin::get_formatted_price( $credithistory['credit_used'] ).'</span>' : '';
                if( $credit_used == '' ) {
                    $credit_used = isset( $credithistory['reimbursed'] )? '<span class="wt-credited">'. Wt_Smart_Coupon_Admin::get_formatted_price( $credithistory['reimbursed'] ).'</span>' : '-';

                }
                $updated_credit = isset( $credithistory['updated_credit'] )? Wt_Smart_Coupon_Admin::get_formatted_price( $credithistory['updated_credit'] ) : '-';
                $credit_history .='<tr>
                    <td>'.date_i18n('Y-m-d H:i ',intval(trim( $date, "'") ) ).'</td>
                    <td>' . $order.'</td>
                    <td>'.$credit_used.'</td>
                    <td>'.$updated_credit.'</td>
                </tr>';
            }

        }else
        {
            // this credit not yet used.
        }
        $credit_history .='</table>';

        echo $credit_history;
    }

    /**
     * Helper function to get the credit initial amount and set the value into meta
     */
    public function get_credit_initial_amount( $coupon )
    {
        $coupon_id =  $coupon->get_id();
        $credit_balance = $coupon->get_amount();
        $credithistories = get_post_meta( $coupon_id, 'wt_credit_history', true);
        if(!empty( $credithistories ))
        {
            $credit_used_so_far  = 0;
            foreach( $credithistories as $credit_history )
            {
                if( is_numeric($credit_history['credit_used'] ) ) {
                    $credit_used_so_far += $credit_history['credit_used'];
                }
            }
            $initial_amount = $credit_used_so_far + $credit_balance;
        } else {
            $initial_amount = $credit_balance;
        }
        update_post_meta($coupon_id, '_wt_smart_coupon_initial_credit', $initial_amount );
        return $initial_amount;
    }

    public function add_view_subscription_template($located_template, $template_name, $args, $template_path, $default_path)
    {            
        global $wp;
        if ('myaccount/my-account.php' == $template_name && !empty($wp->query_vars['view-store-credit']) )
        { 
           $located_template = wc_locate_template('views/my-account/view-store-credit.php', $template_path,'/');
            
        }     
        return $located_template;
    }

    public static function install()
    {
        flush_rewrite_rules();
    }

    /**
     *  Display used store credits  
     * 
     *  @since 2.1.1 
     */
    public function display_used_store_credit($user)
    {
        if( in_array( 'used_coupons', Wt_Smart_Coupon::get_option( 'wbte_account_storecredit_additional_display' ) ) ){
            $store_credits = $this->get_store_credit_for_a_user( $user , false, true);     
           
            echo '<div class="wt_store_credit_main">';
            echo '<h4>';
                _e("Used Credits","wt-smart-coupons-for-woocommerce-pro"); 
            echo '</h4>';
            if( !empty( $store_credits ) )
            {    
                echo '<div class="wt_coupon_wrapper wt_store_credit">';
                //inject CSS for coupon block
                Wt_Smart_Coupon_Public::print_coupon_default_css();

                $e = 0;

                foreach( $store_credits as $coupon_id )
                {
                    $coupon_obj = new WC_Coupon( $coupon_id );

                    $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon_obj );
                    $coupon_data['display_on_page'] = 'my_account';
                    
                    if(0 === $e) //to print coupon CSS in first template, to avoid same multiple CSS blocks
                    {   
                        echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "used_coupon", true);
                    }else
                    {
                        echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "used_coupon");
                    }

                    $e++;
                }
                if(0 === $e)
                {
                    _e("Sorry, you don't have any Used Store Credits.", "wt-smart-coupons-for-woocommerce-pro");

                }
                echo '</div>';   
            } else
            {
                _e("Sorry, you don't have any Used Store Credits.", "wt-smart-coupons-for-woocommerce-pro");
            }
            echo '</div>';  
        }   
    }

}