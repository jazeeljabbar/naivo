<?php
/**
 * Store credit purchase as gift card or coupon.
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Store_Credit_Purchase')) 
{
    return;
}

class Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product_Page extends Wt_Smart_Coupon_Store_Credit_Purchase
{
    private static $instance = null;
    private static $to_remove_blocks = array(); // Blocks to be removed while preparing gift card product page on a block theme
    
    public function __construct()
    {
        parent::init_vars();

        add_action( 'init', array( $this, 'init' ) );

        /**
         *  Remove `Add to cart` button from shop page (Product list page) for store credit purchase product.
         */
        add_action('woocommerce_after_shop_loop_item', array($this, 'remove_add_to_cart_button_from_shop_page'));

        /**
         * Remove product price HTML for store credit purchase product.
         */
        add_filter('woocommerce_get_price_html', array($this, 'remove_price_html_for_store_credit'), 10, 2);

        
        /**
         *  Store credit purchase form
         */
        add_action('woocommerce_before_add_to_cart_button', array($this, 'set_store_credit_purchase_form'), 10);

        /**
         *  Scripts and styles for store credit purchase form
         */
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'add_custom_inline_css' ) );

        
        /**
         *  Disable quantity selection for gift card product
         *  @since 2.0.8
         */
        add_filter('woocommerce_quantity_input_args', array($this, 'disable_quantity_selection'), 10, 2);


        /**
         *  Remove extra add to cart button in some themes
         *  Themes: Astra, Ocean, Botiga
         *  
         *  @since 2.1.2
         */
        add_filter('astra_woo_single_product_structure', array($this, 'remove_extra_add_to_cart'), 10, 1);
        add_filter('ocean_woo_summary_elements_positioning', array($this, 'remove_extra_add_to_cart'), 10, 1);
        add_filter('botiga_default_single_product_components', array($this, 'remove_extra_add_to_cart'), 10, 1);
        add_filter('botiga_single_product_elements', array($this, 'remove_extra_add_to_cart'), 10, 1);


        /**
         *  Add theme specific CSS class for product page template main `div`
         * 
         *  @since 2.2.0
         */
        add_filter( 'wt_sc_add_gift_card_product_page_css_class', array( $this, 'add_theme_specific_css_class' ) );
    }

    /**
     * Initializes the store credit product settings for block and non-block themes.
     * Handles block removal, breadcrumb customization, and page design rendering.
     * 
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init(){

        $enabled_customizing_store_credit = self::is_extended_store_credit_enabled();

        if ( $enabled_customizing_store_credit ) { // Template enabled.
            
            $product_page_design_hook = $this->get_product_page_design_hook();
            
            /**
             *  Gift card product page content (Only when templates are enabled) 
             *  @since 2.1.2    Added compatibility for block themes
             */
            if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) { // Block theme
                
                self::$to_remove_blocks = $this->get_blocks_to_remove();

                // Remove unwanted blocks.
                add_filter( 'pre_render_block', array( $this, 'remove_unwanted_single_product_page_blocks' ) , 11, 2 ); 
                
                // Add gift card product page design.
                add_filter( 'render_block', array( $this, 'block_theme_single_product_page' ) , 10, 2 );

                // Add compatibility for block theme with legacy template.
                add_action( 'template_redirect', function() use( $product_page_design_hook ) {
                    
                    // In some block themes they keep legacy template sections.
                    if ( apply_filters( 'woocommerce_disable_compatibility_layer', false ) ) {
                        add_action( $product_page_design_hook, array( $this, 'shop_single_page_design' ), 11, 0 );
                    }

                }, 11 ); // Priority must be greater than 10 to get value from WC.
                

            } else { // Non block theme.
                
                add_filter( 'woocommerce_get_breadcrumb', array( $this, 'remove_breadcrumb' ), 20, 2 );
                add_action( $product_page_design_hook, array( $this, 'shop_single_page_design' ), 11, 0 );
            }
        }
    }


    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product_Page();
        }
        return self::$instance;
    }

    /**
     *  Remove `Add to cart` button from shop page for store credit purchase product.
     */
    public function remove_add_to_cart_button_from_shop_page()
    {
        global $product, $woocommerce;
        if($this->is_product_is_store_credit_purchase($product->get_id()))
        {
            $js=" jQuery('a[data-product_id=\"".  $product->get_id() ."\"]').remove(); ";

            if(version_compare( WC()->version, '2.0.20', '>=' ))
            {
                wc_enqueue_js($js);
            }else
            {
                $woocommerce->add_inline_js($js);
            }
            ?>
                <a href="<?php echo esc_attr(the_permalink()); ?>" class="button"><?php echo  __('Select options', 'wt-smart-coupons-for-woocommerce-pro'); ?></a>
            <?php
        }
    }

    /**
     * Remove product price HTML for store credit purchase product.
     */
    public function remove_price_html_for_store_credit($price = null, $product = null)
    {
        if(is_object($product) && $this->is_product_is_store_credit_purchase($product->get_id()))
        {
            return '';
        }
        return $price;
    }

    public function set_store_credit_purchase_form()
    {
        global $product;
        if(!$this->is_product_is_store_credit_purchase($product->get_id()))
        {
            return;
        }
        $settings = self::get_store_credit_settings();
        include_once $this->module_path."views/_store_credit_form.php";
    }

    /**
     * Add required scripts/styles
     */
    public function enqueue_scripts()
    {
        if(is_product())
        {
            global $post;         
            if($this->is_product_is_store_credit_purchase(get_the_ID()))
            {
                wp_register_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css');
                wp_enqueue_style('jquery-ui-css');

                wp_enqueue_style('wt-smart-coupon-store-credit', $this->module_url.'assets/css/wt-smart-coupon-store-credit.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all');

                wp_enqueue_script('wt-smart-coupon-store-credit', $this->module_url.'assets/js/wt-smart-coupon-store-credit.js', array('jquery', 'jquery-ui-datepicker'), WEBTOFFEE_SMARTCOUPON_VERSION, false);              
                
                $params=array(
                    'store_credit_date_format'=>$this->get_schedule_date_format(),
                );
                wp_localize_script('wt-smart-coupon-store-credit', 'wt_sc_store_credit_params', $params);
            }
        }
       
    }

    /**
     * Credit coupon single page content and design
     */
    public function shop_single_page_design()
    {
        global $product; 
        if(!$this->is_product_is_store_credit_purchase($product->get_id()))
        {
            return;
        }

        $this->remove_unwanted_product_page_hooks();
        $this->print_gift_card_product_page_templates_section();
    }

    /**
     *  Get available templates. Removes hidden templates
     */
    public function get_visible_templates()
    {
        $templates=self::get_gift_card_templates();
        $hidden_template_list=self::get_hidden_templates();
        foreach($hidden_template_list as $template_id)
        {
            unset($templates[$template_id]);
        } 

        if(self::is_display_templates_by_category()) /* Category wise display */
        {
            $categories=array_column($templates, 'category');
            if(count($categories)!=count($templates)) /* keys not matching */
            {
                $categories=array();
                $general_cat_title=__('General', "wt-smart-coupons-for-woocommerce-pro");
                foreach($templates as $template_k=>$template_v)
                {
                    if(isset($template_v['category']))
                    {
                        $categories[]=$template_v['category'];
                    }else
                    {
                        $templates[$template_k]['category']=$general_cat_title;
                        $categories[]=$general_cat_title;
                    }
                }
            }

            $array_keys = array_keys($templates);           
            array_multisort($categories, SORT_ASC, $templates, $array_keys);

            $templates = array_combine($array_keys, $templates);           
        }

        return apply_filters('wt_sc_alter_store_credit_visible_templates', $templates);
    }

    /**
     * Remove unwanted hooks from product page
     */
    public function remove_unwanted_product_page_hooks()
    {
        remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
        remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
        
        remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
        
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
        
        remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
        remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
        remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
        
        remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
        remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
        
        remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
        remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
        remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

        $theme = wp_get_theme();

        if ( 'Woostify' === $theme->name || 'Woostify' === $theme->parent_theme ) {
            remove_action( 'woocommerce_before_single_product_summary', 'woostify_single_product_gallery_image_slide', 30 );
            remove_action( 'woocommerce_before_single_product_summary', 'woostify_single_product_gallery_thumb_slide', 40 );
            remove_action( 'woocommerce_single_product_summary', 'custom_template_single_title', 5 );
        }

        /**
         *  Hook to remove unwanted product page hooks
         */
        do_action( 'wt_sc_remove_unwanted_product_page_hooks' );

        add_action('wt_gift_coupon_setup_form', 'woocommerce_template_single_add_to_cart',9999 );
    }


    /**
     *  Disable quantity selection for gift card product
     *  
     *  @since  2.0.8
     *  @param  array       $args       Array of arguments. Including `min_value`, `max_value` etc
     *  @param  WC_Product  $product    Product object
     *  @return array       Processed arguments array            
     */
    function disable_quantity_selection($args, $product)
    {
        if($this->is_product_is_store_credit_purchase($product->get_id()))
        {   
            $args['min_value'] = 1;
            $args['max_value'] = 1;
        }

        return $args;  
    }


    /**
     *  Print gift card product page templates section
     * 
     *  @since 2.1.2
     */
    public function print_gift_card_product_page_templates_section() {
        
        $templates = $this->get_visible_templates();
        
        if ( empty( $templates ) ) {
            return;
        }

        $currency_symbol    = get_woocommerce_currency_symbol();
        $currency_positon   = get_option('woocommerce_currency_pos');

        wc_get_template( 'gift-card.php', array(
            'templates'             =>  $templates,
            'currency_positon'      =>  $currency_positon,
            'currency_symbol'       =>  $currency_symbol,
            'templates_by_category' =>  self::is_display_templates_by_category(),
            
        ), '', $this->module_path . 'templates/' );
    }


    /**
     *  The list of blocks to be removed while preparing the gift card product page
     * 
     *  @since  2.1.2
     *  @return string[]    Name of the blocks 
     */
    private function get_blocks_to_remove() {

        // The blocks to be removed
        $to_remove_blocks = array( 
            'woocommerce/product-image-gallery', 
            'core/post-title', 
            'woocommerce/product-rating', 
            'woocommerce/product-price', 
            'core/post-excerpt', 
            'woocommerce/add-to-cart-form', 
            'woocommerce/product-meta', 
            'woocommerce/product-sku', 
            'core/post-terms', 
            'woocommerce/related-products', 
        );

        /**
         *  While setting up the product page for gift card. We need to remove some default product page blocks.
         *  This filter allows to customize which blocks to remove
         * 
         *  @since  2.1.2
         *  @param  string[]    $to_remove_blocks   Name of the blocks 
         */
        return apply_filters( 'wt_sc_single_product_page_blocks_to_remove', $to_remove_blocks );
    }


    /**
     *  Remove unwanted product page hooks while setting up the `Gift card product page` on a block enabled theme.
     *  
     *  @since  2.1.2
     *  @param  string|null   $pre_render   The pre-rendered content. Default null.
     *  @param  array         $parsed_block The block being rendered.
     *  @return string|null   If the block is in the `To remove` list, then return an empty string.
     */
    public function remove_unwanted_single_product_page_blocks( $pre_render, $parsed_block ) {

        if ( is_product() 
            && ! is_null( $product = self::get_product_object() ) // Single product page
            && $this->is_product_is_store_credit_purchase( $product->get_id() ) // Gift card product 
        )  {          

            if ( in_array( $parsed_block['blockName'], self::$to_remove_blocks ) ) {
                $pre_render = '';
            }
        }       

        return $pre_render;
    }


    /**
     *  Prepare gift card product page on a block enabled theme.
     * 
     *  @since  2.1.2
     *  @param  string   $block_content The block content.
     *  @param  array    $block         The full block, including name and attributes.
     *  @return string   $block_content The block content.
     */
    public function block_theme_single_product_page( $block_content, $block ) {

        if ( 'woocommerce/product-details' === $block['blockName'] ) { // Single product page product details block

            $product = self::get_product_object();
            if ( is_null( $product ) ) { // Unable to get the product object
                return $block_content; 
            }

            // Not a gift card product 
            if ( ! $this->is_product_is_store_credit_purchase( $product->get_id() ) ) {
                return $block_content;
            }
            

            // Re-add `Add to cart`
            add_action( 'wt_gift_coupon_setup_form', 'woocommerce_template_single_add_to_cart', 9999 );

            ob_start();
            $this->print_gift_card_product_page_templates_section();    
            $block_content = ob_get_clean();
        }

        return $block_content;
    }


    /**
     *  Remove extra add to cart button in some themes
     *  Themes: Astra, Ocean, Botiga
     *  
     *  @since  2.1.2
     *  @param  string[]        $product_sections   Array of single product page sections
     *  @return string[]        $product_sections   Empty array, if the current product is a gift card product. Otherwise return the same array in the argument.
     */
    public function remove_extra_add_to_cart($product_sections)
    {
        global $product, $post;
        
        if(!is_object($product))
        {
            if(is_product()) //in some themes the $product object is not ready
            {
                $product = wc_get_product($post->ID);
            }else
            {
                return $product_sections;
            }
        }

        if(!method_exists($product, 'get_id')) //invalid product object
        {
            return $product_sections;
        }

        if ( ! $this->is_product_is_store_credit_purchase( $product->get_id() ) 
        || empty( $this->get_visible_templates() ) ) { // Not a gift card product or templates not enabled      
            return $product_sections;
        }

        return array();
    }

    
    /**
     *  To remove breadcrumb in Gift card product page
     * 
     *  @since  2.1.2
     *  @param array            $crumbs         Breadcrumb array
     *  @param WC_Breadcrumb    $breadcrumb     Breadcrumb object
     *  @return array           $crumbs         Breadcrumb array
     */
    public function remove_breadcrumb( $crumbs, $breadcrumb ) {

        if ( ! is_product() ) {
            return $crumbs;
        }
        
        global $product, $post;

        $product_id = is_object( $product ) ? $product->get_id() : $post->ID; 
        
        return ( $this->is_product_is_store_credit_purchase( $product_id ) ? array() : $crumbs );
    }

    
    /**
     *  Get the hook to inject gift card product page
     * 
     *  @since  2.1.2
     *  @return string  Hook name
     */
    public function get_product_page_design_hook() {
        
        $hook = 'woocommerce_before_single_product_summary';
        $theme = wp_get_theme();

        if ( 'Porto' === $theme->name || 'Porto' === $theme->parent_theme ) {
            $hook = 'woocommerce_before_single_product';
        }

        /**
         *  Alter the hook name to inject product page
         *
         *  @since  2.1.2
         *  @param  string  Hook name 
         */
        return apply_filters( 'wt_sc_gift_product_page_design_hook', $hook );
    }


    /**
     *  Add custom inline CSS to avoid conflict with some themes
     *  Hooked into: wp_footer
     * 
     *  @since  2.1.2
     */
    public function add_custom_inline_css() {
        if ( is_product() && $this->is_product_is_store_credit_purchase( get_the_ID() ) ) {
            $theme = wp_get_theme();
            ?>
            <style type="text/css">
                <?php 
                if ( 'Blockpress' === $theme->name || 'Blockpress' === $theme->parent_theme ) {
                   ?>
                   footer.wp-block-template-part{ clear:both; }
                   .wt_customise_gift_coupon_wrapper{ float:none; }
                   .wt_gift_coupon_setup{ margin-bottom:30px; }
                   <?php 
                } elseif ( 'Moog' === $theme->name || 'Moog' === $theme->parent_theme ) {
                    ?>
                    .wt_customise_gift_coupon_wrapper{ float:none; }
                    <?php
                }

                /**
                 *  Add custom inline CSS to gift card product page
                 *
                 *  @since  2.1.2
                 */
                do_action( 'wt_sc_giftcard_product_page_custom_css' );
                ?>  
            </style>
            <?php
        }
    }

    /**
     *  Add theme specific CSS class for product page template main `div`
     *  Hooked into `wt_sc_add_gift_card_product_page_css_class`
     * 
     *  @since  2.2.0
     *  @param  string   $css_class    CSS class name
     *  @return string   $css_class    CSS class name
     */
    public function add_theme_specific_css_class( $css_class ) {
        
        $theme = wp_get_theme();
        $alignwide_required_themes = array( 'Twenty Twenty-Two', 'Twenty Twenty-Three', 'Twenty Twenty-Four', 'Moog', 'Saryu', 'Blockpress' );
        
        /**
         *  Filter to alter alignwide CSS required themes
         *  
         *  @since 2.2.0
         *  @param string[]  Theme names
         */
        $alignwide_required_themes = apply_filters( 'wt_sc_alignwide_css_required_themes', $alignwide_required_themes );

        if ( in_array( $theme->name, $alignwide_required_themes ) || in_array( $theme->parent_theme, $alignwide_required_themes ) ) {
            $css_class .= 'alignwide';
        }

        return $css_class;
    }
}