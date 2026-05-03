<?php
/**
 * Coupon category admin/public
 *
 * @link      
 * @since 1.3.6     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}
if( ! class_exists ( 'Wt_Smart_Coupon_Category_Common' ) ){

	class Wt_Smart_Coupon_Category_Common {
		public $module_base='coupon_category';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;

        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            add_filter('init', array($this, 'register_coupon_category_taxonomy'));

            add_filter('wt_sc_admin_menu', array($this, 'add_admin_menu'));

            add_action('restrict_manage_posts', array($this, 'add_coupon_category_filter'));
            add_filter('manage_edit-shop_coupon_columns', array($this, 'add_coupon_category_column'));
        	add_filter('manage_shop_coupon_posts_custom_column', array($this, 'add_coupon_category_column_content'), 10, 2);

        }

        /**
         * Get Instance
         * @since 1.3.6
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Category_Common();
            }
            return self::$instance;
        }

        /**
		* Coupon category column head
		* @since 1.3.6
		* @param array $columns Columns list.
        */
        public function add_coupon_category_column($columns)
	    {
	        $columns['coupon_categories']=__('Categories', 'wt-smart-coupons-for-woocommerce-pro');
	        return $columns;
	    }

	    /**
		* Coupon category column content
		* @since 1.3.6
		* @param string 	$column    	Column name.
		* @param int    	$coupon_id 	Coupon ID.
        */
	    public function add_coupon_category_column_content($column, $coupon_id)
	    {
	        if('coupon_categories'!==$column)
	        {
	            return;
	        }

	        $categories=get_the_terms($coupon_id, 'shop_coupon_cat');

	        if(is_array($categories) && !empty($categories))
	        {
	        	$out=array();
	        	$cat_filter_link=admin_url('edit.php?post_type=shop_coupon&shop_coupon_cat=');
	            foreach($categories as $category)
	            {
	            	$out[]='<a href="'.esc_attr($cat_filter_link.$category->slug).'">'.esc_html($category->name).'</a>';
	            }
	            echo implode(', ', $out);
	        }else
	        {
	        	echo '--';
	        }
	    }

	    /**
		* Coupon category filter select box in coupon listing page
		* @since 1.3.6
		* @param string $post_type Post type.
        */
        public function add_coupon_category_filter($post_type)
        {
        	if('shop_coupon'!==$post_type)
        	{
	            return;
	        }
	        $selected_val=(isset($_GET['shop_coupon_cat']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_GET['shop_coupon_cat']) : '');
	        $args=array(
	            'show_count'         => true,
	            'hierarchical'       => true,
	            'show_uncategorized' => true,
	            'pad_counts'         => true,
	            'hide_empty'         => false,	            
	            'selected'           => $selected_val,
	            'show_option_none'   => __('Select category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'option_none_value'  => '',
	            'value_field'        => 'slug',
	            'taxonomy'           => 'shop_coupon_cat',
	            'name'               => 'shop_coupon_cat',
	            'orderby'            => 'name',
	            'class'              => 'dropdown_shop_coupon_cat',
	        );

	        wp_dropdown_categories($args);
        }

        /**
         * 	Admin menu
         *  @since 1.3.6
         *  @param array $menus Menu list.
         */
        public function add_admin_menu($menus)
        {
        	$out=array();
        	foreach($menus as $menu)
        	{
        		$out[]=$menu;
        		if($menu[0]=='submenu' && 'post-new.php?post_type=shop_coupon'==$menu[5])
        		{
        			$out[]=array(
		                'submenu',
		                WT_SC_PLUGIN_NAME,
		                __('Coupon category', 'wt-smart-coupons-for-woocommerce-pro'),
		                __('Coupon category', 'wt-smart-coupons-for-woocommerce-pro'),
		                'manage_woocommerce',
		                'edit-tags.php?taxonomy=shop_coupon_cat&post_type=shop_coupon',
		            );
        		}
        	}           
            return $out;
        }

        /**
         * 	@since 1.3.6
         * 	Register coupon category taxonomy
         */
        public function register_coupon_category_taxonomy()
        {
        	$labels = array(
	            'name'              => _x('Categories', 'Taxonomy General Name', 'wt-smart-coupons-for-woocommerce-pro'),
	            'singular_name'     => _x('Category', 'Taxonomy Singular Name', 'wt-smart-coupons-for-woocommerce-pro'),
	            'search_items'      => __('Search categories', 'wt-smart-coupons-for-woocommerce-pro'),	            
	            'all_items'         => __('All categories', 'wt-smart-coupons-for-woocommerce-pro'),
	            'parent_item'       => __('Parent category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'parent_item_colon' => __('Parent category:', 'wt-smart-coupons-for-woocommerce-pro'),
	            'edit_item'         => __('Edit category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'update_item'       => __('Update category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'add_new_item'      => __('Add new category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'new_item_name'     => __('New category name', 'wt-smart-coupons-for-woocommerce-pro'),
	            'menu_name'         => __('Categories', 'wt-smart-coupons-for-woocommerce-pro'),	            
	            'view_item'         => __('View category', 'wt-smart-coupons-for-woocommerce-pro'),
	            'popular_items'     => __('Popular categories', 'wt-smart-coupons-for-woocommerce-pro'),	            
	            'not_found'         => __('Not found', 'wt-smart-coupons-for-woocommerce-pro'),
	            'most_used'         => __('Most used', 'wt-smart-coupons-for-woocommerce-pro'),
	        );

	        $args = array(
	            'labels'            => $labels,
	            'label'             => $labels['singular_name'],
	            'hierarchical'      => true,
	            'public'            => false,
	            'show_ui'           => true,
	            'show_admin_column' => true,
	            'show_in_nav_menus' => false,
	            'show_tagcloud'     => false,
	            'show_in_rest'      => true,
	            'show_in_menu'      => true,
	            'capabilities'      => array(
		            'manage_terms' 	=> 'manage_woocommerce',
		            'edit_terms'   	=> 'manage_woocommerce',
		            'delete_terms' 	=> 'manage_woocommerce',
		            'assign_terms' 	=> 'manage_woocommerce',
		        ),            
	        );

	        register_taxonomy('shop_coupon_cat', array('shop_coupon'), $args);

        }

	}
	Wt_Smart_Coupon_Category_Common::get_instance();
}