<?php
/* Custom functions code goes here. */
// Disable Gutenberg on the back end.
add_filter( 'use_block_editor_for_post', '__return_false' );

// Disable Gutenberg for widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );


/* Custom functions code goes here. */
// Disable Gutenberg on the back end.
add_filter( 'use_block_editor_for_post', '__return_false' );

// Disable Gutenberg for widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );

/**
 * Enqueue custom shop scripts and styles for Add to Cart AJAX flow
 */
function naivo_enqueue_shop_assets() {
    if ( ! function_exists( 'is_woocommerce' ) ) return;

    // Load on all pages so the side cart + badge update works everywhere
    wp_enqueue_style(
        'naivo-custom-shop',
        get_stylesheet_directory_uri() . '/css/custom-shop.css',
        array(),
        filemtime( get_stylesheet_directory() . '/css/custom-shop.css' )
    );

    // Cart AJAX handler — depends on jQuery + WooCommerce's add-to-cart script
    wp_enqueue_script(
        'naivo-cart-ajax',
        get_stylesheet_directory_uri() . '/js/cart-ajax.js',
        array( 'jquery' ),
        filemtime( get_stylesheet_directory() . '/js/cart-ajax.js' ),
        true
    );

    // Checkout UI restructuring — only on checkout page
    if ( is_checkout() && ! is_order_received_page() ) {
        wp_enqueue_script(
            'naivo-checkout-ui',
            get_stylesheet_directory_uri() . '/js/checkout-ui.js',
            array( 'jquery' ),
            filemtime( get_stylesheet_directory() . '/js/checkout-ui.js' ),
            true
        );
    }

    // Shop filter JS — only on shop/archive pages
    // Note: Enqueued later in enqueue_custom_shop_assets with localization
    if ( is_shop() || is_product_taxonomy() ) {
        // Handle moved to enqueue_custom_shop_assets for consistency
    }
}
add_action( 'wp_enqueue_scripts', 'naivo_enqueue_shop_assets' );

/**
 * Enqueue Google Fonts (Manrope) for sidebar cart Figma match
 */
function naivo_enqueue_google_fonts() {
    wp_enqueue_style(
        'naivo-google-fonts-manrope',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'naivo_enqueue_google_fonts' );

/**
 * Customize the sidebar cart header title for Figma match
 * The "woo-cart-all-in-one" plugin uses 'sc_header_title' option.
 * We filter it to show "Your Cart" (the JS will add the product count dynamically).
 */
add_filter( 'option_woo_cart_all_in_one_params', 'naivo_customize_sidebar_cart_options' );
function naivo_customize_sidebar_cart_options( $options ) {
    if ( is_array( $options ) ) {
        // Set header title to plain "Your Cart" — JS will append "(X Products)"
        $options['sc_header_title'] = 'Your Cart';
        // Set footer message with note
        $options['sc_footer_message'] = '<strong>Note:</strong> &nbsp; Shipping charges, Taxes, Discounts will be applied on checkout {product_plus}';
        // Set footer button to checkout
        $options['sc_footer_button'] = 'checkout';
        // Set footer total text
        $options['sc_footer_cart_total_text'] = 'SUB TOTAL';
    }
    return $options;
}

/**
 * Override WC's AJAX add_to_cart handler to support variable products.
 * WC's default handler ignores the variation_id POST param — it only detects
 * variation_id if the product_id itself is a variation. We hook in early
 * (priority 1) and handle variable products ourselves.
 */
add_action( 'wc_ajax_add_to_cart', 'naivo_handle_variable_add_to_cart', 1 );
function naivo_handle_variable_add_to_cart() {
    if ( ! isset( $_POST['product_id'] ) ) {
        return; // Let WC handle it
    }

    $product_id   = absint( $_POST['product_id'] );
    $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

    // Only intercept if this is a variable product with a variation_id
    if ( ! $variation_id ) {
        return; // Let WC's default handler run
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return; // Not a variable product, let WC handle it
    }

    ob_start();

    $quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );

    // Build variation attributes from POST data
    $variation = array();
    foreach ( $_POST as $key => $value ) {
        if ( strpos( $key, 'attribute_' ) === 0 ) {
            $variation[ sanitize_title( $key ) ] = sanitize_text_field( $value );
        }
    }

    // If no attributes were passed, get them from the variation object
    if ( empty( $variation ) ) {
        $variation_obj = wc_get_product( $variation_id );
        if ( $variation_obj ) {
            $variation = $variation_obj->get_variation_attributes();
        }
    }

    $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation );
    $product_status    = get_post_status( $product_id );

    if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) && 'publish' === $product_status ) {
        do_action( 'woocommerce_ajax_added_to_cart', $product_id );
        WC_AJAX::get_refreshed_fragments();
    } else {
        $data = array(
            'error'       => true,
            'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id ),
        );
        wp_send_json( $data );
    }

    wp_die();
}

/**
 * AJAX endpoint: return available attribute options for a product.
 * Called when the mini-cart builds editable variation dropdowns.
 *
 * Request params:
 *   product_id  – parent (variable) product ID
 *
 * Response: JSON {
 *   attributes: { "pa_weight": { label: "Weight", options: ["250g","500g",...] }, ... },
 *   variations: [ { variation_id, attributes: { "attribute_pa_weight": "250g", ... }, price_html, ... } ]
 * }
 */
add_action( 'wp_ajax_naivo_get_variation_options',        'naivo_get_variation_options' );
add_action( 'wp_ajax_nopriv_naivo_get_variation_options', 'naivo_get_variation_options' );
function naivo_get_variation_options() {
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    if ( ! $product_id ) {
        wp_send_json_error( 'Missing product_id' );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        wp_send_json_error( 'Not a variable product' );
    }

    // Collect attribute labels + options
    $attrs_data = array();
    $variation_attributes = $product->get_variation_attributes();
    foreach ( $variation_attributes as $attribute_name => $options ) {
        $taxonomy = $attribute_name; // e.g. "pa_weight"
        $label    = wc_attribute_label( $taxonomy, $product );
        $attrs_data[ $taxonomy ] = array(
            'label'   => $label,
            'options' => array_values( $options ),
        );
    }

    // Collect available variations
    $available_variations = $product->get_available_variations();
    $variations_data      = array();
    foreach ( $available_variations as $v ) {
        $variations_data[] = array(
            'variation_id' => $v['variation_id'],
            'attributes'   => $v['attributes'],
            'price_html'   => $v['price_html'],
            'is_in_stock'  => $v['is_in_stock'],
            'display_price' => $v['display_price'],
        );
    }

    wp_send_json_success( array(
        'attributes' => $attrs_data,
        'variations' => $variations_data,
    ) );
}

/**
 * AJAX endpoint: swap a cart item to a different variation.
 * Removes the old cart item and adds a new one with the selected variation.
 *
 * Request params:
 *   cart_item_key – key of the item to replace
 *   product_id    – parent product ID
 *   variation_id  – new variation ID
 *   quantity      – quantity to keep
 *   attributes    – JSON-encoded { "attribute_pa_weight": "500g", ... }
 */
add_action( 'wp_ajax_naivo_update_cart_variation',        'naivo_update_cart_variation' );
add_action( 'wp_ajax_nopriv_naivo_update_cart_variation', 'naivo_update_cart_variation' );
function naivo_update_cart_variation() {
    $cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( $_POST['cart_item_key'] ) : '';
    $product_id    = isset( $_POST['product_id'] )    ? absint( $_POST['product_id'] )    : 0;
    $variation_id  = isset( $_POST['variation_id'] )   ? absint( $_POST['variation_id'] )  : 0;
    $quantity      = isset( $_POST['quantity'] )       ? wc_stock_amount( $_POST['quantity'] ) : 1;
    $attributes    = isset( $_POST['attributes'] )     ? json_decode( wp_unslash( $_POST['attributes'] ), true ) : array();

    if ( ! $cart_item_key || ! $product_id || ! $variation_id ) {
        wp_send_json_error( 'Missing required parameters' );
    }

    // Sanitize attributes
    $clean_attrs = array();
    if ( is_array( $attributes ) ) {
        foreach ( $attributes as $key => $val ) {
            $clean_attrs[ sanitize_title( $key ) ] = sanitize_text_field( $val );
        }
    }

    // Remove old item
    WC()->cart->remove_cart_item( $cart_item_key );

    // Add the new variation
    $new_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $clean_attrs );

    if ( $new_key ) {
        // Return refreshed fragments so the sidebar re-renders
        WC_AJAX::get_refreshed_fragments();
    } else {
        wp_send_json_error( 'Failed to add variation to cart' );
    }

    wp_die();
}

/**
 * Quick add-to-cart for Best Selling section.
 * Supports both simple and variable products.
 * For variable products, auto-selects the first available variation.
 */
add_action( 'wp_ajax_naivo_quick_add_to_cart',        'naivo_quick_add_to_cart' );
add_action( 'wp_ajax_nopriv_naivo_quick_add_to_cart', 'naivo_quick_add_to_cart' );
function naivo_quick_add_to_cart() {
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

    if ( ! $product_id ) {
        wp_send_json_error( 'Missing product ID' );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( 'Product not found' );
    }

    $quantity = 1;

    if ( $product->is_type( 'simple' ) ) {
        // Simple product — straightforward add
        $result = WC()->cart->add_to_cart( $product_id, $quantity );
        if ( $result ) {
            do_action( 'woocommerce_ajax_added_to_cart', $product_id );
            WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_error( 'Could not add product to cart' );
        }
    } elseif ( $product->is_type( 'variable' ) ) {
        // Variable product — pick the first available variation
        $variations = $product->get_available_variations();
        $selected   = null;

        foreach ( $variations as $v ) {
            if ( $v['is_in_stock'] && $v['is_purchasable'] ) {
                $selected = $v;
                break;
            }
        }

        if ( ! $selected ) {
            wp_send_json_error( 'No available variations' );
        }

        $variation_id = $selected['variation_id'];
        $attributes   = $selected['attributes'];

        $result = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
        if ( $result ) {
            do_action( 'woocommerce_ajax_added_to_cart', $product_id );
            WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_error( 'Could not add variation to cart' );
        }
    } else {
        // Grouped, external, etc. — not supported for quick add
        wp_send_json_error( 'Product type not supported for quick add' );
    }

    wp_die();
}

// Custom Wp-Admin Css
function admin_style() {
    echo '<style>
    #toplevel_page_us-theme-options ul li:nth-child(7), 
    #toplevel_page_us-theme-options ul li:nth-child(8), 
    #toplevel_page_us-theme-options ul li:nth-child(9)
    {
        display: none;
    }
  </style>';
}
add_action('admin_head', 'admin_style');

//Flavour Categories Function
function flavour_cat(){

    // Get the global post object
    global $post, $product;

    // Check if the current post is a WooCommerce product
    if (get_post_type($post->ID) !== 'product') {
        return 'This shortcode can only be used on product pages.';
    }

    // Get the terms of the specified taxonomy for the product
    $terms = wp_get_post_terms($post->ID, 'flavour-categories');

    // If no terms found, return a message
    if (empty($terms) || is_wp_error($terms)) {
        return 'No terms found for this product.';
    }

    // Create a string of term names

    $term_names = array();
	$term_ids = array();
	$term_url = array();
//  	print_r($terms);
    foreach ($terms as $term) {
        $term_names[] = $term->name;
		$term_ids[] = $term->term_id;
		$term_url[] = $term->slug;
    }

    $term_list = implode(', ', $term_names);
	$term_ids_list = implode(', ', $term_ids);
	$term_url_name = implode(', ', $term_url);
	
	?>
 	<div class="fl-tag <?php echo $term_url_name;?>">
        <?php 
		$image = get_field('flavour_thumbnail_image', 'flavour-categories_' . $term_ids_list);
		if( !empty( $image ) ): ?>
			<img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
		<?php endif; ?>
        <?php echo $term_list; ?>
    </div> 
<?php
}
add_shortcode('flavour_cat_name','flavour_cat');

//New Badge Function
function display_new_badge() {
    global $product;

    if (!$product) {
        return '';
    }

    $product_id = $product->get_id();

    $days = 30; // You can change this value as needed
    $post_date = get_the_date('Y-m-d', $product_id);
    $current_date = date('Y-m-d');

    $date_diff = strtotime($current_date) - strtotime($post_date);
    $days_diff = round($date_diff / (60 * 60 * 24));

    if ($days_diff <= $days) {
        return '<span class="badge new-badge">New</span>';
    }

    return '';
}
add_shortcode('new_badge', 'display_new_badge');

//Best Seller Function
function display_best_seller_badge() {
    global $product;

    if (!$product) {
        return '';
    }

    $product_id = $product->get_id();

    $threshold = 100; // Set the number of sales to be considered a best seller
    $total_sales = get_post_meta($product_id, 'total_sales', true);

    if ($total_sales >= $threshold) {
        return '<span class="badge best-seller-badge">Best</span>';
    }

    return '';
}
add_shortcode('best_seller_badge', 'display_best_seller_badge');

//Font Upload
add_filter('upload_mimes', 'add_custom_upload_mimes');
function add_custom_upload_mimes($existing_mimes) {
	$existing_mimes['otf'] = 'application/x-font-otf';
	$existing_mimes['woff'] = 'application/x-font-woff';
	$existing_mimes['ttf'] = 'application/x-font-ttf';
	$existing_mimes['eot'] = 'application/vnd.ms-fontobject';
	return $existing_mimes;
}

//Custom Categories Fetch
function woo_parent_and_child_categories_list_shortcode($atts) {
    // Shortcode attributes
    $atts = shortcode_atts(
        array(
            'parent' => '', // Parent category ID
        ),
        $atts,
        'parent_and_child_categories'
    );

    // Ensure the parent ID is set
    if (!$atts['parent']) {
        return 'Please provide a parent category ID.';
    }

    // Get the parent category
    $parent_category = get_term_by('id', $atts['parent'], 'product_cat');
    if (!$parent_category) {
        return 'Parent category not found.';
    }

    // Get child categories
    $args = array(
        'taxonomy'   => 'product_cat',
        'child_of'   => $atts['parent'],
        'hide_empty' => false,
    );

    $child_categories = get_terms($args);

    $cate = get_queried_object();
    //print_r($cate);
    $cateID = $cate->term_id;
    //echo $cateID;

    // Start output buffer
    ob_start();

    echo '<ul class="woocommerce-parent-and-child-categories">';

    // Display parent category
    $parent_link = get_term_link($parent_category);
    $parent_thumbnail_id = get_term_meta($parent_category->term_id, 'thumbnail_id', true);
    $parent_image_url = wp_get_attachment_url($parent_thumbnail_id);
    $parent_product_count = $parent_category->count;

    echo '<li class="parent-category">';
    if ($parent_image_url) {
        echo '<a href="' . esc_url($parent_link) . '">';
        echo '<img src="' . esc_url($parent_image_url) . '" alt="' . esc_attr($parent_category->name) . '">';
        echo '</a>';
    }
    echo '<div class="category-info">';
    echo '<h3><a href="' . esc_url($parent_link) . '">' . esc_html($parent_category->name) . '</a></h3>';
    //echo '<span class="product-count">' . $parent_product_count . '</span>';
    echo '</div>';
    echo '</li>';

    // Display child categories
    if (!empty($child_categories)) {
        foreach ($child_categories as $category) {
            $category_link = get_term_link($category);
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $image_url = wp_get_attachment_url($thumbnail_id);
            $product_count = $category->count;
            $activeClass = "";
            if($category->term_id == $cateID){
                $activeClass = "activeCat";
            }
            echo '<li class="'.$activeClass.'">';
            if ($image_url) {
                echo '<a href="' . esc_url($category_link) . '" class="cat-img '.$activeClass.'">';
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($category->name) . '">';
                echo '</a>';
            }
            echo '<div class="category-info">';
            echo '<h3><a href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a></h3>';
            echo '<span class="product-count">' . $product_count . '</span>';
            echo '</div>';
            echo '</li>';
        }
    } else {
        echo '<li>No child categories found.</li>';
    }

    echo '</ul>';

    // Return the buffered content
    return ob_get_clean();
}

add_shortcode('parent_and_child_categories', 'woo_parent_and_child_categories_list_shortcode');

function enqueue_custom_woocommerce_styles() {
    // Check if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) ) {
        // Enqueue the custom CSS file
        wp_enqueue_style( 'custom-woocommerce', get_stylesheet_directory_uri() . '/custom_css.css', array(), '1.0.0' );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_woocommerce_styles', 20 );

add_action("wp_head","add_js_call");
function add_js_call(){
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){

            let cart_content_count = "<?php echo WC()->cart->get_cart_contents_count(); ?>";
            jQuery('#sidecartOpenID').append("<span class='cartCounter'>"+cart_content_count+"</span>"); // Update your cart count element


            jQuery("#sidecartOpenID").click(function(){
                jQuery(".vi-wcaio-sidebar-cart-icon-wrap-open").trigger("click");
            });

            jQuery(".w-socials-item-link").removeAttr("target");
            <?php if(!is_user_logged_in()): ?>
                jQuery(".w-socials-item-link").attr("href","<?php echo home_url('/login/'); ?>");
            <?php endif; ?>
        })
        jQuery(function($){
            $(document.body).on('added_to_cart', function(){
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'woocommerce_get_cart_count',
                    },
                    success: function(response) {
                        // console.warn("response",response);
                        $('#sidecartOpenID .cartCounter').remove();
                        $('#sidecartOpenID').append("<span class='cartCounter'>"+response+"</span>"); // Update your cart count element
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_woocommerce_get_cart_count', 'get_cart_count_ajax');
add_action('wp_ajax_nopriv_woocommerce_get_cart_count', 'get_cart_count_ajax');

function get_cart_count_ajax() {
    echo WC()->cart->get_cart_contents_count();
    wp_die(); // Required to terminate AJAX requests properly.
}


function custom_logout_redirect() {
    wp_redirect(home_url('/login/')); // Change '/your-page-slug/' to your desired URL.
    exit();
}
add_action('wp_logout', 'custom_logout_redirect');

function redirect_my_account_to_login() {
    if (is_page('my-account') && !is_user_logged_in()) {
        wp_redirect(home_url('/login/'));
        exit();
    }
}
add_action('template_redirect', 'redirect_my_account_to_login');

function producttags_link_shortcode() 
{
   
    global $product;
    echo '<div class="producttagsbox">';
    echo '<div class="producttagshead">';
    echo 'Flavour Notes';
    echo '</div>'; 
    ;
    echo '<div class="producttags">';
$tags = $product->tag_ids;
foreach($tags as $tag) {
    if(!next($tags)) {
        echo get_term($tag)->name;
    }
    else{
   echo get_term($tag)->name.', ';
    }

}
echo '</div>'; 
echo '</div>'; 
}
add_shortcode('producttags', 'producttags_link_shortcode');

function add_continue_shipping_link() {
    // Replace the URL below with the link to your shop or the page you want users to continue shopping on.
    $shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );
    echo '<div class="continue-shopping">';
    echo '<a href="' . esc_url( $shop_page_url ) . '" class="continue_shopping continue_btn"><img src="https://naivo.in/wp-content/uploads/2024/09/ph_arrow-down.png" alt="back arrow"> Continue Shopping</a>';
    echo '</div>';
}
add_action( 'woocommerce_after_cart_table', 'add_continue_shipping_link' );

add_filter('woocommerce_currency_symbol', 'change_existing_currency_symbol', 
10, 2);

function change_existing_currency_symbol( $currency_symbol, $currency ) {
 switch( $currency ) {
      case 'INR': $currency_symbol = '₹'; break;
 }
 return $currency_symbol;
}
add_filter( 'woocommerce_get_price_html', 'change_variable_products_price_display', 10, 2 );
function change_variable_products_price_display( $price, $product ) {

    // Only for variable products type
    if( ! $product->is_type('variable') ) return $price;

    $prices = $product->get_variation_prices( true );

    if ( empty( $prices['price'] ) )
        return apply_filters( 'woocommerce_variable_empty_price_html', '', $product );

    $min_price = current( $prices['price'] );
    $max_price = end( $prices['price'] );
    $prefix_html = '<span class="price-prefix fromprice">' . __('from  ') . '</span>';

    $prefix = $min_price !== $max_price ? $prefix_html : ''; // HERE the prefix

    return apply_filters( 'woocommerce_variable_price_html', $prefix . wc_price( $min_price ) . $product->get_price_suffix(), $product );
}



/**
 * @snippet       Add Custom Field @ WooCommerce Checkout Page
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 6
 * @community     https://businessbloomer.com/club/
 */
  
 add_action( 'woocommerce_before_order_notes', 'bbloomer_add_custom_checkout_field' );
  
 function bbloomer_add_custom_checkout_field( $checkout ) { 
    $current_user = wp_get_current_user();
    $saved_gstin_no = $current_user->gstin_no;
    woocommerce_form_field( 'gstin_no', array(        
       'type' => 'text',        
       'class' => array( 'form-row-wide' ),        
       'label' => 'GSTIN Number',        
       'placeholder' => '',        
       'required' => false,         
       'default' => $saved_gstin_no,        
    ), $checkout->get_value( 'gstin_no' ) ); 
 }
 /**
 * @snippet       Validate Custom Field @ WooCommerce Checkout Page
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 6
 * @community     https://businessbloomer.com/club/
 */
 
//add_action( 'woocommerce_checkout_process', 'bbloomer_validate_new_checkout_field' );
  
//function bbloomer_validate_new_checkout_field() {    
   //if ( ! $_POST['gstin_no'] ) {
      //wc_add_notice( 'Please enter your GSTIN Number', 'error' );
   //}
//}

/**
 * @snippet       Save & Display Custom Field @ WooCommerce Order
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 6
 * @community     https://businessbloomer.com/club/
 */
 
 add_action( 'woocommerce_checkout_update_order_meta', 'bbloomer_save_new_checkout_field' );
  
 function bbloomer_save_new_checkout_field( $order_id ) { 
     if ( $_POST['gstin_no'] ) update_post_meta( $order_id, '_gstin_no', esc_attr( $_POST['gstin_no'] ) );
 }
  
 add_action( 'woocommerce_thankyou', 'bbloomer_show_new_checkout_field_thankyou' );
    
 function bbloomer_show_new_checkout_field_thankyou( $order_id ) {    
    if ( get_post_meta( $order_id, '_gstin_no', true ) ) echo '<p><strong>GSTIN Number:</strong> ' . get_post_meta( $order_id, '_gstin_no', true ) . '</p>';
 }
   
 add_action( 'woocommerce_admin_order_data_after_billing_address', 'bbloomer_show_new_checkout_field_order' );
    
 function bbloomer_show_new_checkout_field_order( $order ) {    
    $order_id = $order->get_id();
    if ( get_post_meta( $order_id, '_gstin_no', true ) ) echo '<p><strong>GSTIN Number:</strong> ' . get_post_meta( $order_id, '_gstin_no', true ) . '</p>';
 }
  
 add_action( 'woocommerce_email_after_order_table', 'bbloomer_show_new_checkout_field_emails', 20, 4 );
   
 function bbloomer_show_new_checkout_field_emails( $order, $sent_to_admin, $plain_text, $email ) {
     if ( get_post_meta( $order->get_id(), '_gstin_no', true ) ) echo '<p><strong>GSTIN Number:</strong> ' . get_post_meta( $order->get_id(), '_gstin_no', true ) . '</p>';
 }
 

 add_action( 'wpo_wcpdf_after_order_data', 'wpo_wcpdf_delivery_date', 10, 2 );
 function wpo_wcpdf_delivery_date ($template_type, $order) {
     if ($template_type == 'invoice') {
         ?>
         <tr class="delivery-date">
             <th>GSTIN Number:</th>
             <td><?php  echo get_post_meta( $order->get_id(), '_gstin_no', true ) ; ?></td>
         </tr>
         <?php
     }
 }

 /**
 * You can add a custom placeholder to add a hint for your CUs what you expect.
 * Our hooked in function - $fields is passed via the filter.
 */
//add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	//$fields['billing']['billing_phone']['placeholder'] = '09XXXXXXXXX';
	//return $fields;
//} );

/**
 * Process the checkout.
 * Validation for phone field this will throw an error message.
 */
add_action( 'woocommerce_checkout_process', function () {
	global $woocommerce;
	// Check if set, if its not set add an error. This one is only requite for companies.
	if ( ! preg_match( '/^[0-9]{10}$/D', $_POST['billing_phone'] ) ) {
		wc_add_notice( '<strong>Billing Phone</strong> should contain only 10 digits.', 'error' );
	}
} );

//  yoast schema remove

function remove_yoast_webpage_schema($data, $context) {
    if (isset($data['@type']) && is_array($data['@type'])) {
        $data['@type'] = array_diff($data['@type'], ['WebPage']);
    }
    return $data;
}
add_filter('wpseo_schema_webpage', '__return_false'); // Disables WebPage schema
add_filter('wpseo_schema_graph_pieces', 'remove_yoast_webpage_schema', 10, 2);


add_filter( 'wpseo_schema_graph_pieces', 'disable_yoast_breadcrumb_schema', 11, 2 );
function disable_yoast_breadcrumb_schema( $pieces, $context ) {
    foreach ( $pieces as $key => $piece ) {
        if ( $piece instanceof Yoast\WP\SEO\Generators\Schema\Breadcrumb ) {
            unset( $pieces[ $key ] );
        }
    }
    return $pieces;
}

// Custom Shop Implementation
function enqueue_custom_shop_assets() {
    $ver = time();
    wp_enqueue_style( 'custom-shop-css', get_stylesheet_directory_uri() . '/css/custom-shop.css', array(), $ver );
    
    if ( is_shop() || is_product_taxonomy() ) {
        wp_enqueue_script( 'custom-shop-js', get_stylesheet_directory_uri() . '/js/shop-filter.js', array('jquery'), $ver, true );
        wp_localize_script( 'custom-shop-js', 'shop_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_custom_shop_assets', 100 );

add_action('wp_ajax_filter_shop_products', 'handle_filter_shop_products');
add_action('wp_ajax_nopriv_filter_shop_products', 'handle_filter_shop_products');

function handle_filter_shop_products() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 24,
        'post_status' => 'publish',
        'meta_key' => '_price',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'tax_query' => array('relation' => 'AND'),
        'update_post_term_cache' => true, // Ensure terms are cached for template use
        'update_post_meta_cache' => true, // Ensure meta is cached for template use
    );

    if (!empty($_GET['category'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_GET['category'])
        );
    }

    $attributes = array('pa_brew-with', 'pa_roast', 'pa_process', 'pa_country', 'pa_best-had', 'pa_flavour-profile');
    
    foreach ($attributes as $attr) {
        if (!empty($_GET[$attr])) {
            $terms = is_array($_GET[$attr]) ? array_map('sanitize_text_field', wp_unslash($_GET[$attr])) : array(sanitize_text_field($_GET[$attr]));
            $args['tax_query'][] = array(
                'taxonomy' => $attr,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'IN'
            );
        }
    }

    if (!empty($_GET['orderby'])) {
        $orderby = sanitize_text_field($_GET['orderby']);
        if ($orderby === 'price') {
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
        } elseif ($orderby === 'price-desc') {
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ($orderby === 'date') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
    }

    $query = new WP_Query($args);
    
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            include( get_stylesheet_directory() . '/woocommerce/content-product-custom.php' );
        }
    } else {
        echo '<p>No products found matching these criteria.</p>';
    }
    
    $html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json(array(
        'html' => $html,
        'count' => $query->found_posts
    ));
}

// Force load custom shop template (bypassing Impreza theme builders)
add_filter( 'template_include', 'force_custom_shop_template', 9999 );
function force_custom_shop_template( $template ) {
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        $new_template = get_stylesheet_directory() . '/woocommerce/archive-product.php';
        if ( file_exists( $new_template ) ) {
            return $new_template;
        }
    }
    return $template;
}

// Set default WooCommerce Shop sorting to Price: High to Low (matching Figma PLP)
add_action( 'woocommerce_product_query', 'naivo_shop_price_sort' );
function naivo_shop_price_sort( $q ) {
    if ( ! is_admin() && $q->is_main_query() && ( is_shop() || is_product_category() || is_product_tag() ) ) {
        if ( ! isset( $_GET['orderby'] ) || empty($_GET['orderby']) ) {
            $q->set( 'meta_key', '_price' );
            $q->set( 'orderby', 'meta_value_num' );
            $q->set( 'order', 'DESC' );
        }
    }
}

// Remove price decimals on shop/archive pages to match Figma (₹450 not ₹450.00)
add_filter( 'wc_price_args', 'naivo_remove_price_decimals_on_shop' );
function naivo_remove_price_decimals_on_shop( $args ) {
    if ( is_shop() || is_product_category() || is_product_tag() || wp_doing_ajax() ) {
        $args['decimals'] = 0;
    }
    return $args;
}

// Add flavor context and formatted pills to single product page under title
add_action( 'woocommerce_single_product_summary', 'naivo_single_product_pills_and_notes', 15 );
function naivo_single_product_pills_and_notes() {
    global $product;
    if ( ! $product ) return;

    $subtitle = get_field('flavor_notes', $product->get_id());
    if (empty($subtitle)) {
        $fallback = wc_get_product_terms($product->get_id(), 'pa_flavour', array('fields'=>'names'));
        $subtitle = is_array($fallback) ? implode(', ', $fallback) : '';
    }

    if ($subtitle) {
        echo '<div style="margin-bottom: 12px; margin-top: 15px;">';
        echo '<div style="font-size:11px; font-weight:bold; letter-spacing:1px; margin-bottom:4px; text-transform:uppercase; color:#000;">FLAVOUR NOTES</div>';
        echo '<div style="font-size:11px; color:#555; text-transform:uppercase; margin-bottom:0;">' . esc_html($subtitle) . '</div>';
        echo '</div>';
    }

    $flavour_profile = wc_get_product_terms($product->get_id(), 'pa_flavour-profile', array('fields'=>'names'));
    $roasts = wc_get_product_terms($product->get_id(), 'pa_roast', array('fields'=>'names'));
    $countries = wc_get_product_terms($product->get_id(), 'pa_country', array('fields'=>'names'));

    $profile_text = !empty($flavour_profile) ? $flavour_profile[0] : '';
    $roast_text = !empty($roasts) ? $roasts[0] : '';
    $country_text = !empty($countries) ? $countries[0] : '';

    $profile_slug = sanitize_title($profile_text);
    $flavour_icons = array(
        'bright-fruity' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/blue-berry-icon.svg', 'bg' => '#eef2ff', 'color' => '#3b5998'),
        'rich-strong' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/rich-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
        'bold-balanced' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/bold-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
        'sweet-juicy' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/orange-icon.svg', 'bg' => '#fff0e6', 'color' => '#e65c00'),
        'delicate-floral' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/floral-icon.svg', 'bg' => '#f3e8ff', 'color' => '#7e22ce'),
    );

    if ( $profile_text || $roast_text || $country_text ) {
        echo '<div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom: 25px;">';
        
        if ( $profile_text ) {
             $icon_html = '';
             $profile_bg = '#f0f4ff';
             $profile_color = '#3b5998';
             foreach ($flavour_icons as $slug_key => $data) {
                 if (strpos($profile_slug, $slug_key) !== false) {
                     $icon_html = '<img src="' . esc_url($data['icon']) . '" alt="icon" style="width:16px; height:16px; margin:0;" />';
                     $profile_bg = $data['bg'];
                     $profile_color = $data['color'];
                     break;
                 }
             }
             echo '<span style="background: ' . esc_attr($profile_bg) . '; color: ' . esc_attr($profile_color) . '; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: bold; display: flex; align-items: center; gap: 6px;">' . $icon_html . ' ' . esc_html($profile_text) . '</span>';
        }
        if ( $roast_text ) {
             echo '<span style="background: #f4eee8; color: #8b5a2b; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: bold; display: flex; align-items: center; gap: 6px;">☕ ' . esc_html($roast_text) . '</span>';
        }
        if ( $country_text ) {
             echo '<span style="background: #e8ecee; color: #2b6271; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: bold; display: flex; align-items: center; gap: 6px;">📍 ' . esc_html($country_text) . '</span>';
        }
        echo '</div>';
    }
}

/**
 * Change "Place order" button text to "🔒 PAY NOW" on checkout
 * Done server-side so it persists through WC AJAX re-renders
 */
add_filter( 'woocommerce_order_button_text', function() {
    return '🔒  PAY NOW';
});

/**
 * Add product thumbnail with quantity badge to checkout order review table
 * This transforms the plain text product name into a card-style display:
 *   [Thumbnail with qty badge]  Product Name
 *                               Size: 250g  Grind: Aeropress
 *                               ₹450.00
 */
add_filter( 'woocommerce_cart_item_name', function( $name, $cart_item, $cart_item_key ) {
    if ( ! is_checkout() ) return $name;

    $_product = $cart_item['data'];
    $thumbnail = $_product->get_image( array(60, 60) );
    $qty = $cart_item['quantity'];

    // Build variation meta string: "Size: 250g   Grind: Aeropress"
    $variation_text = '';
    if ( ! empty( $cart_item['variation'] ) ) {
        $parts = array();
        foreach ( $cart_item['variation'] as $attr_key => $attr_val ) {
            if ( empty( $attr_val ) ) continue;
            $label = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ), $_product );
            $label = str_ireplace( 'Select Grind Size', 'Grind', $label );
            $parts[] = '<span class="nv-var-label">' . esc_html( $label ) . ':</span> ' . esc_html( $attr_val );
        }
        if ( $parts ) {
            $variation_text = '<div class="nv-item-variation">' . implode( '&nbsp;&nbsp;&nbsp;', $parts ) . '</div>';
        }
    }

    // Build price display
    $price_html = '<div class="nv-item-price">' . WC()->cart->get_product_price( $_product ) . '</div>';

    // Wrap: thumbnail with quantity badge + product info
    $output  = '<div class="nv-checkout-item">';
    $output .= '<div class="nv-checkout-item-thumb">';
    $output .= $thumbnail;
    $output .= '<span class="nv-checkout-item-qty">' . $qty . '</span>';
    $output .= '</div>';
    $output .= '<div class="nv-checkout-item-info">';
    $output .= '<div class="nv-checkout-item-name">' . strip_tags( $name ) . '</div>';
    $output .= $variation_text;
    $output .= $price_html;
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}, 10, 3 );

/**
 * Hide the default variation display and quantity in checkout since we embed them in the name
 */
add_filter( 'woocommerce_checkout_cart_item_quantity', '__return_empty_string', 10, 3 );

/**
 * Remove tax suffix "(includes ...)" from order total
 */
add_filter( 'woocommerce_cart_totals_order_total_html', function( $value ) {
    if ( is_checkout() ) {
        $value = preg_replace( '/<\/strong>.*$/i', '</strong>', $value );
    }
    return $value;
});

/**
 * Add standalone GST row before the order total
 */
add_action( 'woocommerce_review_order_before_order_total', function() {
    if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() ) {
        $tax_totals = WC()->cart->get_tax_totals();
        if ( ! empty( $tax_totals ) ) {
            $total_tax = 0;
            foreach ( $tax_totals as $tax ) {
                $total_tax += $tax->amount;
            }
            if ( $total_tax > 0 ) {
                ?>
                <tr class="nv-gst-charges">
                    <th>GST Charges</th>
                    <td><?php echo wc_price( $total_tax ); ?></td>
                </tr>
                <?php
            }
        }
    }
});


