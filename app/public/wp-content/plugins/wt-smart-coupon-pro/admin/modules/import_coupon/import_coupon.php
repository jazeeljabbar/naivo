<?php
/**
 * Import coupon
 *
 * @link       
 * @since 1.3.6   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}


class Wt_Smart_Coupon_Import_Coupon_Admin
{
    public $module_base='import_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    protected $id,$coupon_post_fields = array();
    protected $coupon_posts_headers = array();
    protected $email_coupon_on_import;
    protected $header,$map_head;
    protected $row_parsed=0;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        $this->coupon_post_fields = array(
            'post_title', 'post_excerpt', 'post_status', 'post_parent', 'menu_order', 'post_date'
        );

        $this->coupon_posts_headers = array( 
            'post_title','post_excerpt','post_status','post_parent','menu_order','post_date'
        );

        $this->email_coupon_on_import =(isset($_POST['email_coupon_on_import']) ? absint($_POST['email_coupon_on_import']) : 0 );
        
        add_filter('wt_sc_admin_menu', array($this, 'add_admin_pages'));
        add_action('wp_ajax_wt_import_csv_coupon_rows', array($this, 'import_start'),10);
        
        /**
         *  This is to delete the import CSV file after successfull import.
         * 
         *  @since 2.4.0
         */
        add_action( 'wp_ajax_wbte_import_finished', array( $this, 'import_finished') );
    }

    /**
     * Get Instance
     * @since 1.3.6
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Import_Coupon_Admin();
        }
        return self::$instance;
    }

    /**
     *  Admin page
     */
    public function add_admin_pages($menus)
    {
        $menus[]=array(
            'submenu',
            WT_SC_PLUGIN_NAME,
            __('Import coupons', 'wt-smart-coupons-for-woocommerce-pro'),
            __('Import coupons', 'wt-smart-coupons-for-woocommerce-pro'),
            'manage_woocommerce',
            $this->module_id,
            array($this, 'import_coupon_page_content')
        );
        return $menus;
    }


    /**
     * Display import coupon page content
     */
    public function import_coupon_page_content()
    {
        $step = (isset($_GET['step']) ? absint($_GET['step']) : 0);
        switch( $step )
        {
            case 0:  /* step 1 */
                $this->display_import_form();
                break;
            case 1: /* step 2 */
                if(!Wt_Smart_Coupon_Security_Helper::check_write_access( 'smart_coupons', 'wt_import_smart_coupon' ) )
                {
                    wp_die(__('You do not have sufficient permission to perform this operation.', 'wt-smart-coupons-for-woocommerce-pro'), __('Unauthorized !!!.', 'wt-smart-coupons-for-woocommerce-pro'), array('link_text'=>__("Go to 'Import coupons'", "wt-smart-coupons-for-woocommerce-pro"), 'link_url'=>admin_url('admin.php?page='.$this->module_id) ) );
                }
                if($this->handle_import_upload())
                {
                    $this->render_csv_row();
                }
                break;
            
            case 2:
                if(!Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_import_smart_coupon_step_2'))
                {
                    wp_die(__('You do not have sufficient permission to perform this operation.', 'wt-smart-coupons-for-woocommerce-pro'), __('Unauthorized !!!.', 'wt-smart-coupons-for-woocommerce-pro'), array('link_text'=>__("Go to 'Import coupons'", "wt-smart-coupons-for-woocommerce-pro"), 'link_url'=>admin_url('admin.php?page='.$this->module_id) ) );
                }
                $this->import_coupon_from_csv();
        }
    }

    /**
     * Display the import form (Step 1)
     */
    public function display_import_form()
    {
        $bytes=apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size_in_mb = $bytes = number_format($bytes / 1048576, 2).' MB'; 
        $sample_file_url=plugin_dir_url(__FILE__).'data/wt_smart_coupon_sample.csv';
        include plugin_dir_path( __FILE__ ).'views/_step1.php';
    }

    /**
     * Render the uploaded CSV for mapping (Step 2)
     */
    public function render_csv_row()
    {
        $j = 0;
        $coupon_posts_headers = $this->coupon_posts_headers;
        $default_coupon_meta_fields=array(
            '_wt_sc_shipping_methods',
            '_wt_sc_payment_methods',
            '_wt_sc_user_roles',
            '_wt_sc_exclude_user_roles',
            '_wt_category_condition',
            '_wt_product_condition',
            '_wt_free_product_ids',
            '_wt_min_matching_product_qty',
            '_wt_max_matching_product_qty',
            '_wt_min_matching_product_subtotal',
            '_wt_max_matching_product_subtotal',
            'discount_type',
            'coupon_amount',
            'individual_use',
            'product_ids',
            'exclude_product_ids',
            '_wt_valid_for_number',
            'minimum_amount',
            'maximum_amount',
            'customer_email',
            'usage_limit',
            'limit_usage_to_x_items',
            'usage_limit_per_user',
            'expiry_date',
            '_wt_coupon_expiry_time',
            '_wt_need_check_location_in',
            '_wt_coupon_available_location',
            '_wt_coupon_start_date',
            '_wt_coupon_start_time',
            'wt_apply_discount_before_tax_calculation',
            '_wt_sc_coupon_applied_message', /**  @since 2.0.8 */
 
        );
        $default_coupon_meta_fields = apply_filters('wt_smart_coupon_default_meta_fields', $default_coupon_meta_fields);

        $coupon_meta_headers = $this->get_possible_meta_for_coupon();
        $coupon_meta_headers = array_unique( array_merge($default_coupon_meta_fields, $coupon_meta_headers ));

        $coupon_heades = array_merge($coupon_posts_headers, $coupon_meta_headers);
        

        $file = get_attached_file($this->id);
        
        // Set locale
        $enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
        if ( $enc ) setlocale( LC_ALL, 'en_US.' . $enc );
        @ini_set( 'auto_detect_line_endings', true );

        if ( ( $handle = @fopen( $file, "r" ) ) !== FALSE )
        {
            $row = $raw_headers = array();
            $header = fgetcsv( $handle, 0 ); //gets header of the file
            $this->header =  $header;
            
            while ( ( $postmeta = fgetcsv( $handle, 0 ) ) !== FALSE )
            {
                foreach ( $header as $key => $heading )
                {
                    $key = remove_accents($key);
                    
                    if (!$heading)
                    {
                        continue;
                    }

                    $s_heading = remove_accents($heading);
                    $row[$s_heading] = ( isset($postmeta[$key]) ) ? $this->format_data_from_csv($postmeta[$key], $enc) : '';
                    $raw_headers[$s_heading] = $s_heading;
                }
                break;
            }

            fclose( $handle );
        }        
        
        include plugin_dir_path( __FILE__ ).'views/_step2.php';
    }

    /**
     *  Import coupons from CSV (Step 3: Final)
     *  @since 2.0.8 [Bug fix] Compatibility issue fix for PHP version 8.0 or greater.
     */
    public function import_coupon_from_csv()
    {
        global $wpdb;
        $this->id  = (isset( $_POST['wt_import_id']) )?  Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['wt_import_id'], 'int' ) : '';
        
        if(!$this->id)
        {
            echo '<p><strong>'.__( 'The file does not exist, please try again.', 'wt-smart-coupons-for-woocommerce-pro') . '</strong><br />';
            return;
        }
        $file = get_attached_file( $this->id );
        $enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
        if ( $enc ) setlocale( LC_ALL, 'en_US.' . $enc );
        @ini_set('auto_detect_line_endings', true);

        if(($handle = @fopen( $file, "r" ) ) !== FALSE )
        {
            $header = fgetcsv($handle, 0);
            if( isset( $_POST['mapto'] ) && !empty( $_POST['mapto'] ))
            {
                $map_head=(Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['mapto'], 'text_arr' ) );
            }

            $minimum_header = array('post_title', 'discount_type'); 

            $coupon_data = array(
                'post_type'=>'shop_coupon'
            );
            $coupon_meta_data = array();
            $coupon_post_fields = $this->coupon_post_fields;

            if(count(array_intersect($coupon_post_fields, $header)) != count($coupon_post_fields) )
            {
                $params = array(
                    'show_import_message' => true,
                    'headers_match'       => 'headers_doesnt_match'
                );
                $redirect_url = add_query_arg($params, admin_url('admin.php?page='.$this->module_id));
            }

            $imported =0;
            $skipped =0;
            $duplicate =0;

            /* prepare csv position array based on batch */
            $count = 1;
            $position_array = array();
            $position = 0;
            $limit  = apply_filters('wt_smart_coupon_import_batch_size', 10);
            $delimiter = apply_filters('wt_smart_coupon_import_delimiter',  "," );
            $import_count = 0;
            $last_position = 0;

            while(($data = fgetcsv($handle, 0, $delimiter, '"', '"')) !== FALSE)
            {
                if($count >= $limit)
                {
                    $previous_position = $position;
                    $position = ftell($handle);
                    $count = 0;
                    $import_count++;
                    $position_array[] = array($previous_position, $position);
                }

                $last_position = ftell($handle);

                $count++;
            }

            if($count > 0)
            {
                $position_array[] = array($position, $last_position);
                $import_count++;
            }

            fclose($handle);

            include plugin_dir_path( __FILE__ ).'views/_step3.php';
        }else
        {
            echo '<p><strong>' . __('The file does not exist, please try again.', 'wt-smart-coupons-for-woocommerce-pro') . '</strong><br />';
            return;
        }
    }    
    
    private function is_json($string)
    {
        return is_object(json_decode($string));
    }
    
    /**
     *  @since 2.0.4
     *  Process the concatenated string and convert it to array.
     *  Eg: A comma seperated string will convert to array and trim each value and also removes the duplicates
     */
    private function process_concatenated_string($value, $glue=",")
    {
        return array_filter(array_map('trim', explode($glue, strval($value))));
    } 

    private function process_date_column_value($value)
    {
        if(is_array($value))
        {
            $out=array();
            foreach($value as $ky=>$vl)
            {
                $out[$ky]=$this->process_date_column_value($vl);
            }
            $value=$out;
        }else
        {
            if(is_string($value))
            {
                $value=str_replace('/', '-', $value);

                $temp=str_replace(' ', '-', $value);
                if($temp!=$value && absint($temp)==$temp) /* space exists and integer format date. Integer check is for skipping string format date  */
                {
                   $value=$temp; 
                }
            }
        }

        return $value;
    }       

    /**
     * Ajax callback for importing coupon batch
     * @since 2.0.1 [Bug fix] PHP 8 compatibility issue
     * @since 2.0.4 Array type meta compatibility improved. 
     *              Added filter to alter array type meta list
     *              Added support for coupon categories.
     */
    function import_start()
    {
        if(!Wt_Smart_Coupon_Security_Helper::check_write_access( 'smart_coupons', 'wt_smart_coupons_import_nonce' ) ) {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }
        $file               = ( isset( $_POST['file'] ) )? stripslashes( $_POST['file'] ) : $this->id;
        $handle             = ( isset( $_POST['handle'] ) )? Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['handle']) : '';
        $header             = ( isset( $_POST['header'] ) )? json_decode(stripslashes( $_POST['header'] ))   :  array(); 
        $map_head           = ( isset( $_POST['map_head'] ) )?  json_decode(stripslashes( $_POST['map_head'] )) :  array(); 
        $start_position     = ( isset( $_POST['start_position'] ) )? Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['start_position'], 'int' ) : 0;
        $end_position       = ( isset( $_POST['end_position'] ) )? Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['end_position'], 'int' ) : '';
        $flag               = ( isset( $_POST['flag'] ) )? Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['flag'] ) : false;
        $email_on_import    = ( isset( $_POST['email_on_import'] ) &&  $_POST['email_on_import']) ?  true : false;
        $this->row_parsed    = ( isset( $_POST['row_parsed'] ) ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['row_parsed'], 'absint' ) : 0;

        $map_head = (array) $map_head;

        $enc = mb_detect_encoding( $file, 'UTF-8, ISO-8859-1', true );
        if ( $enc ) setlocale( LC_ALL, 'en_US.' . $enc );
        @ini_set( 'auto_detect_line_endings', true );

        if(($handle = @fopen( $file, "r" ) ) !== FALSE ){
      
            $coupon_data = array(
                'post_type'=>'shop_coupon'
            );
            $coupon_meta_data = array();
            try {
                fseek($handle,$start_position ) ;
            }
            catch(Exception $e) {
                echo 'Message: ' .$e->getMessage();
                die();
            }

            $error = false;
            $responce = array();
            $delimiter = apply_filters( 'wt_smart_coupon_import_delimiter',  "," );

            while (($data = fgetcsv($handle, 0, $delimiter,'"','"' )) !== FALSE)
            {
                if( empty($data) ) {
                    echo 'empty rows';
                    continue;
                }

                if( $this->row_parsed == 0 ) { // Bypass the header
                    $this->row_parsed++;
                    continue;
                }

                $num = count($data);
                $i = 0;
                foreach( $data as $data_item )
                {
                    if(!in_array( $header[ $i ] , $map_head ) )
                    {
                        $i++;
                        continue;
                    }
    
                    $matched_item = array_search($header[ $i++ ] , $map_head);

                    if(in_array($matched_item, $this->coupon_post_fields))
                    {
                        $coupon_data[ $matched_item ]=$data_item;
                    }else
                    {
                        $coupon_meta_data[ $matched_item ] = $data_item;
                    }
                }
                if( ! isset( $coupon_data['post_status'] ) || '' == $coupon_data['post_status']  ) {
                    $coupon_data['post_status'] = 'publish';
                }
                if( ! $error && ( !isset( $coupon_data['post_title']  )) || trim( $coupon_data['post_title'] ) =='' ) {
                    $responce[] = array(
                        'row'   => $this->row_parsed,
                        'error' => true,
                        'coupon_name' => $coupon_data['post_title'],
                        'status'    => __('Coupon title empty', 'wt-smart-coupons-for-woocommerce-pro')
                    );
                    $error = true;
                }
                if(!$error && Wt_Smart_Coupon_Common::is_coupon_exists($coupon_data['post_title']))
                {
                    $error = true;
                    $responce[] = array(
                        'row'=> $this->row_parsed,
                        'error' => true,
                        'coupon_name' => $coupon_data['post_title'],
                        'status'    => __('Coupon already exists', 'wt-smart-coupons-for-woocommerce-pro')
                    );
                }
                
                if(!$error)
                {    
                    /**
                     *  @since 2.0.4
                     *  Alter coupon data before inserting
                     */
                    $coupon_data=apply_filters('wt_sc_import_alter_coupon_data',  $coupon_data);

                    $coupon_id  = wp_insert_post($coupon_data, true);

                    if(!is_wp_error($coupon_id))
                    {

                        /**
                         *  Alter coupon meta data before inserting 
                         * 
                         *  @since  2.0.4
                         *  @since  2.3.0   Added coupon id as argument.
                         * 
                         *  @param  array   $coupon_meta_data   Associative array of coupon meta data.
                         *  @param  int     $coupon_id          Id of coupon. 
                         */
                        $coupon_meta_data=apply_filters( 'wt_sc_import_alter_coupon_meta_data',  $coupon_meta_data, $coupon_id );

                        /**
                         *  @since 2.0.4
                         * 
                         *  Process coupon categories
                         *  Allowed column names: 
                         *      coupon_category,  tax:shop_coupon_cat
                         *      If multiple columns exists, will take the first non empty column in the above column order 
                         *  Allowed formats:
                         *      Inherited categories: Cat A > Cat B (Cat B is a child of Cat A)
                         *      Multiple categories: Cat A | Cat C (Cat A and Cat C)
                         *      Multiple categories including inherited: Cat A > Cat B | Cat C 
                         */
                        $coupon_category_column_name_arr=array('coupon_category', 'tax:shop_coupon_cat');
                        $coupon_meta_data_keys=array_keys($coupon_meta_data);
                        if(!empty(array_intersect($coupon_meta_data_keys, $coupon_category_column_name_arr)))
                        {                       
                            $coupon_category='';
                            foreach($coupon_category_column_name_arr as $column_name)
                            {
                                /**
                                 *  Checks value exists, non empty, and not already found
                                 */
                                if(isset($coupon_meta_data[$column_name]) && trim($coupon_meta_data[$column_name])!="" && $coupon_category=="")
                                {
                                    $coupon_category=$coupon_meta_data[$column_name];
                                }

                                unset($coupon_meta_data[$column_name]); /* remove from meta data array */
                            }

                            if(""!=$coupon_category) /* category value exists */
                            {
                                /* check for multiple coupon categories */
                                $coupon_category_arr=$this->process_concatenated_string($coupon_category, "|");

                                $new_category_arr=array();
                                foreach($coupon_category_arr as $category)
                                {
                                    /* check for inherited categories */
                                    $category_arr=$this->process_concatenated_string($category, ">");
                                    $parent_id=0;
                                    foreach($category_arr as $_category)
                                    {
                                        $term=term_exists($_category, 'shop_coupon_cat');
                                        if(is_array($term)) /* term exists */
                                        {
                                            $term_id=$term['term_id'];
                                        }else
                                        {
                                            $term=wp_insert_term($_category, 'shop_coupon_cat', array('parent'=>$parent_id));
                                            if(is_wp_error($term))
                                            {
                                                break; // Unable to create term, so break the loop
                                            }
                                            $term_id=$term['term_id'];
                                        }

                                        $parent_id=$term_id; /* parent id for next item, if exists */   
                                    }

                                    $new_category_arr[]=$term_id; /* using the last term id, this will add as coupon category */
                                }

                                if(!empty($new_category_arr))
                                {
                                    wp_set_post_terms($coupon_id, $new_category_arr, 'shop_coupon_cat');
                                }
                            }
                        }
                        /* coupon category processing: end */

                        $status =__('Import success', 'wt-smart-coupons-for-woocommerce-pro');
                        update_post_meta( $coupon_id, 'wt_bulk_generated_coupon', true );
                        update_post_meta( $coupon_id, 'wt_bulk_generated_coupon_csv_import', true );

                        $coupon_obj = new WC_Coupon( $coupon_id );
                        
                        /**
                         *  @since 2.0.4 Array type column list
                         */
                        $convert_to_array_before_saving = array( 'product_categories', 'exclude_product_categories', 'customer_email', 'wt_order_Status_need_to_count','_wt_sc_product_tags','_wt_sc_product_attributes','_wt_sc_coupon_on_selected_days','_wt_sc_nth_order_products', 'product_brands', 'exclude_product_brands' ); /* these meta's must be converetd to array before saving */
                        $convert_to_array_before_saving = apply_filters('wt_sc_import_alter_array_type_meta_list', $convert_to_array_before_saving);

                        /**
                         *  @since 2.0.4 Date type column list
                         */
                        $date_columns=array('_wt_coupon_start_date', 'expiry_date'); /* these meta's must be processed as date before saving */
                        $date_columns=apply_filters('wt_sc_import_alter_date_type_meta_list', $date_columns);

                        /**
                         *  @since 3.2.0 Cast to integer before saving.
                         */
                        $cast_to_integer_before_saving = array( 'product_brands', 'exclude_product_brands' );
                        $cast_to_integer_before_saving = apply_filters( 'wbte_sc_import_alter_array_type_cast_to_integer_meta_list', $cast_to_integer_before_saving );
                        
                        foreach($coupon_meta_data as $meta_key => $meta_value)
                        {                         
                            /**
                             *  Some meta required array as meta value
                             */
                            if(in_array($meta_key, $convert_to_array_before_saving))
                            {
                                if(is_serialized($meta_value))
                                {
                                    if(is_serialized_string($meta_value))
                                    {
                                        $meta_value=maybe_unserialize($meta_value);
                                    }
                                }elseif($this->is_json($meta_value))
                                {
                                    $meta_value=json_decode($meta_value);
                                }else
                                {
                                   $meta_value = $this->process_concatenated_string($meta_value); 
                                }

                                if(!is_array($meta_value))
                                {
                                    $meta_value = array();
                                }

                                /**
                                 *  @since 3.2.0 Cast to integer before saving.
                                 */
                                if( in_array( $meta_key, $cast_to_integer_before_saving ) && !empty( $meta_value ) ) {
                                    $meta_value = array_map( 'intval', $meta_value );
                                }
                                
                                $meta_value=(empty($meta_value) ? '' : $meta_value);                    
                            }
                            
                            /**
                             *  Date columns must be processed before saving
                             */
                            if(in_array($meta_key, $date_columns))
                            {
                                $meta_value = $this->process_date_column_value($meta_value);
                            }

                            if($meta_value)
                            {
                                update_post_meta($coupon_id, $meta_key, $meta_value);
                            }

                            /** 
                             * fix #6586
                             * Meta key timestamp need to update */
                            if( '_wt_coupon_start_date' === $meta_key  && '' !== $meta_value ) {

                                /**
                                 *  @since 2.0.1 Saving time in GMT
                                 */
                                $timestamp=Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($meta_value)->getTimestamp();
                                update_post_meta($coupon_id, '_wt_coupon_start_date_timestamp', $timestamp);
                            }
                            if( 'coupon_amount' === $meta_key && '' !== $meta_value ) {
                               $coupon_obj->set_amount((float)$meta_value);
                            }
                            
                            if( '_wt_make_auto_coupon' === $meta_key && '' !== $meta_value ) {
                               update_post_meta( $coupon_id, '_wt_make_auto_coupon', $this->wt_wc_string_to_bool( $meta_value ) );
                            }                                                               
                            $coupon_obj = new WC_Coupon( $coupon_id ); // Re instantiate the coupon object to get the updated meta data.
                            if($email_on_import && 'customer_email' === $meta_key && !empty($meta_value))
                            {
                                WC()->mailer();
                                foreach($meta_value as $email)
                                {
                                    do_action('wt_send_coupon_to_customer', $coupon_obj, $coupon_obj->get_code(), $email);                       
                                }
                                $status =__('Import success & coupon emailed', 'wt-smart-coupons-for-woocommerce-pro');                                
                            }
                        }
                        if( $coupon_obj->is_type('store_credit')){
                            update_post_meta( $coupon_id, '_wt_smart_coupon_credit_activated', true );
                        }

                        /**
                         *  After a single coupon was imported from CSV.
                         * 
                         *  @since 2.3.0
                         * 
                         *  @param  int     $coupon_id          Id of coupon.
                         *  @param  array   $coupon_data        Associative array of coupon data.
                         *  @param  array   $coupon_meta_data   Associative array of coupon meta data.
                         */
                        do_action( 'wbte_sc_after_coupon_imported', $coupon_id, $coupon_data, $coupon_meta_data );

                        $responce[] = array(
                            'row'=> $this->row_parsed,
                            'error' => false,
                            'coupon_id' => $coupon_id,
                            'coupon_name' => $coupon_data['post_title'],
                            'status'    => $status
                        );                     
                        
                    }else
                    {                      
                        $wp_error = $coupon_id; //unable to create coupon

                        $response_out = array(
                            'row'=> $this->row_parsed,
                            'error' => true,
                            'coupon_name' => $coupon_data['post_title'],
                            'status'    => __('Skipped','wt-smart-coupons-for-woocommerce-pro')
                        );       


                        if($wp_error->has_errors())
                        {
                            $response_out['status'] = implode(", ", $wp_error->get_error_messages());
                        }

                        $responce[] = $response_out;
                    }
                } 

                $position = ftell($handle);
                if ( '' != $end_position && $position >= $end_position ) {
                    break;
                }

                unset($coupon_data);
                unset($coupon_meta_data);
                $coupon_data = array(
                    'post_type'=>'shop_coupon'
                );
                $coupon_meta_data = array();
                $error = false;
                $this->row_parsed++;
                
            }

            fclose( $handle );
            echo json_encode( $responce );
        }
        die();
    }


    /**
     * Handle CSV upload
     */
    public function handle_import_upload()
    {
        if(!empty($_FILES['import']))
        {
            $file = wp_import_handle_upload();
            if(isset($file['error']))
            {
                echo '<p><strong>' . __( 'Sorry, there has been an error.', 'wt-smart-coupons-for-woocommerce-pro' ) . '</strong><br />';
                echo esc_html( $file['error'] ) . '</p>';
                return false;
            }
            $this->id = (int) $file['id'];
        }
        if(!$this->id)
        {
            echo '<p><strong>' . __( 'Sorry, Something went wrong!, Please try again.', 'wt-smart-coupons-for-woocommerce-pro') . '</strong><br />';
            return false;
        } 
        return true;
    }

    /**
     * expiry_date format
     *
     * @param  string $expiry_date
     * @param bool $as_timestamp (default: false)
     * @return string|int
     */
    protected function get_coupon_expiry_date( $expiry_date, $as_timestamp = false )
    {
        if('' !=$expiry_date )
        {
            if( $as_timestamp )
            {
                return strtotime( $expiry_date );
            }
            return date('Y-m-d', strtotime( $expiry_date ) );
        }
        return '';
    }
    
    public function wt_wc_string_to_bool( $string ) {
        return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
    }


    /**
     * Return all meta key for coupon
     */
    public function get_possible_meta_for_coupon()
    {
        global $wpdb;
        $query = "SELECT DISTINCT meta_key FROM $wpdb->posts post INNER JOIN $wpdb->postmeta meta ON post.ID = meta.post_id WHERE post_type='shop_coupon' AND meta.meta_key != '_edit_lock' AND meta.meta_key != '_edit_last' ";
        $meta_results = $wpdb->get_results($query, ARRAY_A);
        return (!is_array($meta_results) ? array() : array_column($meta_results, 'meta_key'));
    }


    public function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * Format the CSV data
     */
    public function format_data_from_csv( $data, $enc ) {
        return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );
    }


    /**
     *  Remove the imported csv file after import.
     *  Ajax callback.
     *  
     *  @since 2.4.0
     */
    public function import_finished() {

        // Nonce verification.
        $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
        $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_import_nonce' ) || !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {           
            echo esc_html__( 'Access denied', 'wt-smart-coupons-for-woocommerce-pro' );
            exit();
        }

        $file_id  = ( isset( $_POST['wt_import_id'] ) ?  absint( wp_unslash( $_POST['wt_import_id'] ) ) : 0 );

        if ( $file_id ) {
            wp_delete_attachment( $file_id );
        }

        exit();
    }
}
Wt_Smart_Coupon_Import_Coupon_Admin::get_instance();