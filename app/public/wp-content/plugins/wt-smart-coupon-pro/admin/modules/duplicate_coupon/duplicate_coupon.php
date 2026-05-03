<?php
/**
 * Duplicate coupon
 *
 * @link       
 * @since 2.0.1  
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}


class Wt_Smart_Coupon_Duplicate_Coupon_Admin
{
    public $module_base='duplicate_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('admin_action_wt_duplicate_post_as_draft', array($this, 'wt_duplicate_post_as_draft'));
        add_filter('post_row_actions', array($this, 'wt_duplicate_post_link'), 10, 2);
    }

    /**
     * Get Instance
     * @since 2.0.1
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Duplicate_Coupon_Admin();
        }
        return self::$instance;
    }

    public function wt_duplicate_post_as_draft()
    {
        global $wpdb;
        if(!current_user_can('edit_posts')) 
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }
        if(!( isset($_GET['post']) || isset($_POST['post']) || ( isset($_REQUEST['action']) && 'wt_duplicate_post_as_draft' == $_REQUEST['action'])))
        {
            wp_die( __('No post to duplicate has been supplied!', 'wt-smart-coupons-for-woocommerce-pro'));
        }

        if(!isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__)))
        {
            return;
        }

        $post_id=(isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']));

        $post=get_post($post_id);

        if(isset($post) && $post != null) 
        {               
            $current_user = wp_get_current_user();
            $new_post_author = $current_user->ID;

            $maybe_post_title = $post->post_title;
            $p_title = $maybe_post_title;
            $counter = 1;

            while(post_exists($p_title))
            {
                $p_title = $maybe_post_title.$counter;
                $counter++;
            }    
            
            /*
            * new post data array
            */
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $new_post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post->post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => apply_filters('wt_smartcoupon_default_duplicate_coupon_status', 'publish'),
                'post_title' => $p_title,
                'post_type' => $post->post_type,
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order
            );
            
            $new_post_id = wp_insert_post($args);


            $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
            }

            $post_meta_data = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $post_id));
            if(!empty($post_meta_data))
            {
                $sql_query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ";
                $placeholders_arr=array();
                $values_arr=array();
                foreach($post_meta_data as $meta_info)
                {
                    /**
                     *  @since 2.0.5 [Bug fix] null value metas converted to string
                     */
                    if(is_null($meta_info->meta_value))
                    {
                        continue;
                    }

                    $meta_key=$meta_info->meta_key;
                    if('_wp_old_slug' === $meta_key || 'wt_credit_history' === $meta_key || '_wt_smart_coupon_initial_credit' === $meta_key)
                    {
                        continue;
                    }
                    
                    /**
                     * @since 2.0.8
                     * 
                     * [Bug fix] usage count for user is cloned for new coupons
                     * 
                     */
                    if( 'usage_count' === $meta_key || '_used_by' === $meta_key )
                    {
                        $meta_info->meta_value = 0;
                    }elseif( '_wbte_sc_auto_coupon_priority' === $meta_key ) { // [Fix] Priority is duplicating same as the parent.  
                        $meta_info->meta_value = $new_post_id;
                    }

                    $placeholders_arr[]='(%d, %s, %s)';
                    array_push($values_arr, $new_post_id, $meta_key, $meta_info->meta_value);
                }
                
                $sql_query.= implode(", ", $placeholders_arr);
                $sql_query=$wpdb->prepare($sql_query, $values_arr);
                $wpdb->query($sql_query);
            }
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit();
        }else
        {
            wp_die(__('Post creation failed, could not find original post: ', 'wt-smart-coupons-for-woocommerce-pro') . $post_id);
        }
    }

    
    /**
    *   Add the duplicate link to action list for post_row_actions
    */
    public function wt_duplicate_post_link($actions, $post)
    {

        if(current_user_can('edit_posts'))
        {
            if((isset($_GET['post_type'])) && ($_GET['post_type'] == 'shop_coupon'))
            {
                $href_text = __('Duplicate', 'wt-smart-coupons-for-woocommerce-pro');
                $href_title = __('Duplicate this item', 'wt-smart-coupons-for-woocommerce-pro');
                $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=wt_duplicate_post_as_draft&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce') . '" title="' . $href_title . '" rel="permalink">' . $href_text . '</a>';
            }
        }
        return $actions;
    }
}

Wt_Smart_Coupon_Duplicate_Coupon_Admin::get_instance();