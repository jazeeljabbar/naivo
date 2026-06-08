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

    // 1. First gather attributes from the variation object (baseline)
    // We trust the variation object's attributes as the absolute source of truth
    // because third-party cart plugins often cache form data and send stale POST payloads.
    $variation = array();
    $variation_obj = wc_get_product( $variation_id );
    if ( $variation_obj ) {
        foreach ( $variation_obj->get_variation_attributes() as $attr_key => $attr_val ) {
            $variation[ 'attribute_' . sanitize_title( $attr_key ) ] = sanitize_text_field( $attr_val );
        }
    }

    // 2. FALLBACK to POST data ONLY for "Any [Attribute]" variations.
    // If the variation object returned an empty string for an attribute, we must use the POST data.
    foreach ( $_POST as $key => $value ) {
        if ( strpos( $key, 'attribute_' ) === 0 && !empty($value) ) {
            $clean_key = sanitize_title( $key );
            // Only use POST data if the variation object didn't provide a specific value
            if ( empty( $variation[ $clean_key ] ) ) {
                $variation[ $clean_key ] = sanitize_text_field( $value );
            }
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

function naivo_home_hero_fit_width_css() {
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    $hero_image_url = get_stylesheet_directory_uri() . '/images/Banner-03.png';
    ?>
	    <style id="naivo-home-hero-fit-width">
            .home .l-main > section[id="For-Desktop"],
            .home .l-main > section[id="For-Mobile"] {
                position: relative;
            }
            .home .l-main > section[id="For-Desktop"] .n2-ss-slide-background-image,
            .home .l-main > section[id="For-Mobile"] .n2-ss-slide-background-image {
                background-image: url('<?php echo esc_url( $hero_image_url ); ?>') !important;
                background-size: 100% auto !important;
                background-position: top center !important;
                background-repeat: no-repeat !important;
            }
            .home .l-main > section[id="For-Desktop"] .n2-ss-slide-background-image picture,
            .home .l-main > section[id="For-Mobile"] .n2-ss-slide-background-image picture,
            .home .l-main > section[id="For-Desktop"] .n2-ss-slide-background-image img,
            .home .l-main > section[id="For-Mobile"] .n2-ss-slide-background-image img,
            .home .l-main > section[id="For-Desktop"] .n2-ss-slide-thumbnail,
            .home .l-main > section[id="For-Mobile"] .n2-ss-slide-thumbnail {
                opacity: 0 !important;
                visibility: hidden !important;
	        }
            .home .nv-home-hero-overlay {
                position: absolute;
                z-index: 6;
                top: 50%;
                left: clamp(24px, 7vw, 96px);
                transform: translateY(-50%);
                max-width: 390px;
                pointer-events: auto;
            }
            .home .nv-home-hero-overlay h1 {
                margin: 0 0 16px;
                color: #1f1a16;
                font-size: clamp(38px, 4.8vw, 68px);
                line-height: 1.05;
                font-weight: 800;
                letter-spacing: 0;
            }
            .home .nv-home-hero-overlay p {
                margin: 0 0 28px;
                color: #3d332b;
                font-size: clamp(18px, 1.8vw, 24px);
                line-height: 1.35;
                font-weight: 500;
            }
            .home .nv-home-hero-overlay .nv-home-hero-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 48px;
                padding: 0 30px;
                border-radius: 0;
                background: #1f1a16;
                color: #fff !important;
                font-size: 13px;
                line-height: 1;
                font-weight: 800;
                letter-spacing: 1.2px;
                text-decoration: none !important;
                text-transform: uppercase;
            }
            .home .nv-home-hero-overlay .nv-home-hero-button:hover {
                background: #3b3129;
            }
            @media (max-width: 767px) {
                /* ── Give the mobile hero section meaningful height ── */
                .home .l-main > section[id="For-Mobile"] {
                    min-height: 88vw !important;
                }
                /* ── Switch to cover so image fills the section height ── */
                .home .l-main > section[id="For-Mobile"] .n2-ss-slide-background-image {
                    background-size: cover !important;
                    background-position: left center !important;
                }
                /* ── Force Smart Slider inner to respect the min-height ── */
                .home .l-main > section[id="For-Mobile"] .n2-ss-align,
                .home .l-main > section[id="For-Mobile"] .n2-padding,
                .home .l-main > section[id="For-Mobile"] .n2-ss-slider,
                .home .l-main > section[id="For-Mobile"] .n2-ss-slider-wrapper,
                .home .l-main > section[id="For-Mobile"] .n2-ss-section-main-content {
                    min-height: 88vw !important;
                }
                /* ── Overlay: bottom-anchored, left-aligned, full-width ── */
                .home .nv-home-hero-overlay {
                    top: auto !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    transform: none !important;
                    max-width: 100% !important;
                    padding: 20px 20px 24px;
                    background: linear-gradient(to top, rgba(255,255,255,0.92) 0%, rgba(255,255,255,0.6) 70%, rgba(255,255,255,0) 100%);
                }
                .home .nv-home-hero-overlay h1 {
                    margin-bottom: 6px;
                    font-size: clamp(26px, 7.5vw, 34px);
                    line-height: 1.1;
                }
                .home .nv-home-hero-overlay p {
                    margin-bottom: 14px;
                    font-size: clamp(13px, 3.8vw, 16px);
                    line-height: 1.4;
                }
                .home .nv-home-hero-overlay .nv-home-hero-button {
                    min-height: 48px;
                    padding: 0 28px;
                    font-size: 12px;
                    letter-spacing: 1.5px;
                }
                /* ── Reduce excessive space below hero on mobile ── */
                .home .l-main > section[id="For-Mobile"] + section {
                    padding-top: 24px !important;
                }
            }
            @media (max-width: 400px) {
                .home .l-main > section[id="For-Mobile"] {
                    min-height: 95vw !important;
                }
                .home .l-main > section[id="For-Mobile"] .n2-ss-align,
                .home .l-main > section[id="For-Mobile"] .n2-padding,
                .home .l-main > section[id="For-Mobile"] .n2-ss-slider,
                .home .l-main > section[id="For-Mobile"] .n2-ss-slider-wrapper,
                .home .l-main > section[id="For-Mobile"] .n2-ss-section-main-content {
                    min-height: 95vw !important;
                }
                .home .nv-home-hero-overlay h1 {
                    font-size: 26px;
                }
                .home .nv-home-hero-overlay p {
                    font-size: 13px;
                }
            }
	    </style>
	    <?php
	}
	add_action( 'wp_head', 'naivo_home_hero_fit_width_css', 99 );

function naivo_home_hero_overlay_markup() {
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }
    ?>
    <script id="naivo-home-hero-overlay-js">
        document.addEventListener('DOMContentLoaded', function() {
            var heroSections = document.querySelectorAll('.home .l-main > section[id="For-Desktop"], .home .l-main > section[id="For-Mobile"]');
            heroSections.forEach(function(section) {
                if (!section.querySelector('.n2-section-smartslider') || section.querySelector('.nv-home-hero-overlay')) {
                    return;
                }

                var overlay = document.createElement('div');
                overlay.className = 'nv-home-hero-overlay';
                overlay.innerHTML = '<h1>We&rsquo;ve Got Stories to Tell</h1><p>...but our coffees do the talking</p><a class="nv-home-hero-button" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">Shop Now</a>';
                section.appendChild(overlay);

                section.querySelectorAll('.n2-ss-slide[data-haslink="1"]').forEach(function(slide) {
                    slide.setAttribute('data-href', '<?php echo esc_js( home_url( '/shop/' ) ); ?>');
                });
            });
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'naivo_home_hero_overlay_markup', 99 );

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

    // Only apply to variable products
    if ( ! $product->is_type('variable') ) {
        return $price;
    }

    // Do not alter in admin, cart, or checkout pages
    if ( is_admin() || is_cart() || is_checkout() || ( function_exists('is_wc_endpoint_url') && ( is_wc_endpoint_url('order-pay') || is_wc_endpoint_url('order-received') ) ) ) {
        return $price;
    }

    // Prevent duplicate prefix
    if ( strpos( $price, 'fromprice' ) !== false ) {
        return $price;
    }

    $prices = $product->get_variation_prices( true );

    if ( empty( $prices['price'] ) ) {
        return apply_filters( 'woocommerce_variable_empty_price_html', '', $product );
    }

    $min_price     = current( $prices['price'] );
    $min_reg_price = current( $prices['regular_price'] );

    // Determine if the minimum priced variation is on sale
    if ( $min_price !== $min_reg_price ) {
        $formatted_price = wc_format_sale_price( wc_price( $min_reg_price ), wc_price( $min_price ) );
    } else {
        $formatted_price = wc_price( $min_price );
    }

    // Add "from" prefix with styled spans matching Naivo design
    $from_prefix = '<span class="price-from fromprice">from</span> ';
    return apply_filters( 'woocommerce_variable_price_html', $from_prefix . $formatted_price . $product->get_price_suffix(), $product );
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

    // Enqueue PDP-specific JS globally so Quick View modal can use it on shop pages
    wp_enqueue_script( 'naivo-pdp-js', get_stylesheet_directory_uri() . '/js/pdp.js', array('jquery'), $ver, true );
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
        echo '<div class="nv-flavor-notes-wrap">';
        echo '<div class="nv-flavor-notes-title">FLAVOUR NOTES</div>';
        echo '<div class="nv-flavor-notes-content">' . esc_html($subtitle) . '</div>';
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
        'bright-fruity' => array('icon' => '/wp-content/uploads/2024/08/blue-berry-icon.svg', 'bg' => '#eef2ff', 'color' => '#3b5998'),
        'rich-strong' => array('icon' => '/wp-content/uploads/2024/08/rich-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
        'bold-balanced' => array('icon' => '/wp-content/uploads/2024/08/bold-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
        'sweet-juicy' => array('icon' => '/wp-content/uploads/2024/08/orange-icon.svg', 'bg' => '#fff0e6', 'color' => '#e65c00'),
        'delicate-floral' => array('icon' => '/wp-content/uploads/2024/08/floral-icon.svg', 'bg' => '#f3e8ff', 'color' => '#7e22ce'),
    );

    if ( $profile_text || $roast_text || $country_text ) {
        echo '<div class="nv-product-pills">';
        
        if ( $profile_text ) {
             $icon_html = '';
             $profile_bg = '#f0f4ff';
             $profile_color = '#3b5998';
             foreach ($flavour_icons as $slug_key => $data) {
                 if (strpos($profile_slug, $slug_key) !== false) {
                     $icon_html = '<img src="' . esc_url($data['icon']) . '" alt="icon" class="nv-pill-icon" />';
                     $profile_bg = $data['bg'];
                     $profile_color = $data['color'];
                     break;
                 }
             }
             echo '<span class="nv-pill nv-pill-profile" style="background: ' . esc_attr($profile_bg) . '; color: ' . esc_attr($profile_color) . ';">' . $icon_html . ' ' . esc_html($profile_text) . '</span>';
        }
        if ( $roast_text ) {
             echo '<span class="nv-pill nv-pill-roast">☕ ' . esc_html($roast_text) . '</span>';
        }
        if ( $country_text ) {
             echo '<span class="nv-pill nv-pill-country">📍 ' . esc_html($country_text) . '</span>';
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

/**
 * Rename variation labels for premium look
 */
add_filter( 'woocommerce_attribute_label', 'naivo_rename_pdp_labels', 10, 3 );
function naivo_rename_pdp_labels( $label, $name, $product ) {
    if ( is_product() ) {
        if ( $name === 'pa_filter' || $name === 'pa_grind-size' || $name === 'Grind Size' || strpos(strtolower($label), 'grind size') !== false || strpos(strtolower($label), 'filter') !== false ) {
            return 'SELECT GRIND SIZE'; // Keep it clean here, JS will add the icon
        }
        if ( $name === 'pa_weight' || $name === 'Weight' || strpos(strtolower($label), 'weight') !== false ) {
            return 'WEIGHT';
        }
    }
    return $label;
}

/**
 * Add "Buy Now" button next to "Add to Cart"
 */
add_action( 'woocommerce_after_add_to_cart_button', 'naivo_add_buy_now_button' );
function naivo_add_buy_now_button() {
    echo '<button type="submit" name="naivo_buy_now" value="1" class="button nv-buy-now-btn">BUY NOW</button>';
}

/**
 * Handle "Buy Now" redirect to checkout
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'naivo_buy_now_redirect' );
function naivo_buy_now_redirect( $url ) {
    if ( isset( $_REQUEST['naivo_buy_now'] ) ) {
        return wc_get_checkout_url();
    }
    return $url;
}

/**
 * Inject Sticky CTA Bar and WhatsApp Icon in Footer
 */
add_action( 'wp_footer', 'naivo_inject_sticky_elements' );
function naivo_inject_sticky_elements() {
    if ( is_product() ) {
        global $product;
        if ( ! $product ) return;

        $thumb = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );
        $title = $product->get_name();
        $price = $product->get_price_html();
        ?>
        <!-- Sticky CTA Bar -->
        <div id="nv-sticky-cta-bar" class="nv-sticky-bar">
            <div class="nv-sticky-bar-container">
                <div class="nv-sticky-info">
                    <img src="<?php echo esc_url( $thumb ); ?>" alt="Product Thumb">
                    <div class="nv-sticky-text">
                        <div class="nv-sticky-title"><?php echo esc_html( $title ); ?></div>
                        <div class="nv-sticky-price"><?php echo $price; ?></div>
                    </div>
                </div>
                <div class="nv-sticky-actions">
                    <button class="nv-sticky-buy-btn" onclick="document.querySelector('.single_add_to_cart_button').click();">ADD TO CART</button>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
        <!-- Sticky WhatsApp -->
        <a href="https://wa.me/919686365058" class="nv-sticky-whatsapp" target="_blank" rel="nofollow">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="#fff"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.316 1.592 5.43 0 9.856-4.426 9.858-9.855.002-5.43-4.425-9.856-9.855-9.856-5.431 0-9.856 4.426-9.858 9.855 0 2.046.611 3.654 1.611 5.326l-1.017 3.71 3.845-.972zm11.366-7.06c-.349-.174-2.065-1.02-2.387-1.137-.322-.117-.557-.174-.79.174-.234.348-.905 1.137-1.11 1.369-.205.232-.41.261-.758.087-.348-.174-1.472-.542-2.803-1.728-1.035-.923-1.733-2.062-1.937-2.41-.205-.348-.022-.537.152-.711.156-.156.348-.406.522-.609.174-.203.232-.348.348-.58.116-.232.058-.435-.03-.609-.087-.174-.79-1.902-1.082-2.603-.284-.682-.572-.59-.79-.601-.204-.01-.439-.012-.673-.012s-.614.088-.936.435c-.322.348-1.23 1.203-1.23 2.93s1.258 3.393 1.434 3.625c.176.232 2.476 3.782 5.998 5.304.838.362 1.492.578 2.003.74.841.268 1.607.23 2.212.14.675-.102 2.065-.844 2.357-1.657.292-.812.292-1.508.205-1.656-.087-.148-.322-.232-.67-.406z"/></svg>
        </a>
    <script>
        jQuery(document).ready(function($) {
            if ($('#nv-sticky-cta-bar').length) {
                var cartBtn = $('.single_add_to_cart_button');
                if (cartBtn.length) {
                    $(window).scroll(function() {
                        if ($(window).scrollTop() > cartBtn.offset().top + 100) {
                            $('#nv-sticky-cta-bar').addClass('visible');
                        } else {
                            $('#nv-sticky-cta-bar').removeClass('visible');
                        }
                    });
                }
            }
        });
    </script>
    <?php
}






/**
 * Move Quantity above Add to Cart buttons
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 35 );

/**
 * AJAX: Quick View product data
 * Returns structured JSON for the Figma quick-view modal.
 * Request: POST { product_id }
 */
add_action( 'wp_ajax_naivo_quick_view_data',        'naivo_quick_view_data' );
add_action( 'wp_ajax_nopriv_naivo_quick_view_data', 'naivo_quick_view_data' );
function naivo_quick_view_data() {
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
    if ( ! $product_id && ! empty( $_POST['product_slug'] ) ) {
        $slug = sanitize_text_field( $_POST['product_slug'] );
        $posts = get_posts( array(
            'name'           => $slug,
            'post_type'      => 'product',
            'fields'         => 'ids',
            'posts_per_page' => 1
        ) );
        if ( ! empty( $posts ) ) {
            $product_id = $posts[0];
        }
    }
    if ( ! $product_id ) {
        wp_send_json_error( 'Missing product_id' );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product || ! $product->is_visible() ) {
        wp_send_json_error( 'Product not found' );
    }

    // ── Gallery images ──────────────────────────────────────
    $gallery_ids     = $product->get_gallery_image_ids();
    $featured_id     = $product->get_image_id();
    $all_image_ids   = array_merge( array( $featured_id ), $gallery_ids );
    $gallery_images  = array();
    foreach ( $all_image_ids as $img_id ) {
        if ( ! $img_id ) continue;
        $src = wp_get_attachment_image_src( $img_id, 'woocommerce_single' );
        if ( $src ) {
            $gallery_images[] = array(
                'url' => $src[0],
                'alt' => get_post_meta( $img_id, '_wp_attachment_image_alt', true ),
            );
        }
    }

    // ── Flavour notes ────────────────────────────────────────
    $flavor_notes = get_field( 'flavor_notes', $product_id );
    if ( empty( $flavor_notes ) ) {
        $tags = get_the_terms( $product_id, 'product_tag' );
        if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
            $tag_names   = wp_list_pluck( $tags, 'name' );
            $flavor_notes = implode( ', ', $tag_names );
        }
    }

    // ── Attribute pills ──────────────────────────────────────
    $roasts         = get_the_terms( $product_id, 'pa_roast' );
    $countries      = get_the_terms( $product_id, 'pa_country' );
    $flavour_profile = get_the_terms( $product_id, 'pa_flavour-profile' );

    $roast_text   = ( ! empty( $roasts )   && ! is_wp_error( $roasts ) )   ? $roasts[0]->name   : '';
    $country_text = ( ! empty( $countries ) && ! is_wp_error( $countries ) ) ? $countries[0]->name : '';
    $profile_text = ( ! empty( $flavour_profile ) && ! is_wp_error( $flavour_profile ) ) ? $flavour_profile[0]->name : '';
    $profile_slug = sanitize_title( $profile_text );

    $flavour_icons = array(
        'bright-fruity'    => array( 'icon' => 'https://naivo.in/wp-content/uploads/2024/08/blue-berry-icon.svg',  'bg' => '#eef2ff', 'color' => '#3b5998' ),
        'rich-strong'      => array( 'icon' => 'https://naivo.in/wp-content/uploads/2024/08/rich-icon.svg',        'bg' => '#f4eee8', 'color' => '#8b5a2b' ),
        'bold-balanced'    => array( 'icon' => 'https://naivo.in/wp-content/uploads/2024/08/bold-icon.svg',        'bg' => '#f4eee8', 'color' => '#8b5a2b' ),
        'sweet-juicy'      => array( 'icon' => 'https://naivo.in/wp-content/uploads/2024/08/orange-icon.svg',      'bg' => '#fff0e6', 'color' => '#e65c00' ),
        'delicate-floral'  => array( 'icon' => 'https://naivo.in/wp-content/uploads/2024/08/floral-icon.svg',      'bg' => '#f3e8ff', 'color' => '#7e22ce' ),
    );

    $profile_icon  = '';
    $profile_bg    = '#f0f4ff';
    $profile_color = '#3b5998';
    foreach ( $flavour_icons as $slug_key => $data ) {
        if ( strpos( $profile_slug, $slug_key ) !== false ) {
            $profile_icon  = $data['icon'];
            $profile_bg    = $data['bg'];
            $profile_color = $data['color'];
            break;
        }
    }

    // ── Price ────────────────────────────────────────────────
    if ( $product->is_type( 'variable' ) ) {
        $price_html = '<span class="price-from fromprice">from</span> ' . wc_price( $product->get_variation_price( 'min', true ) );
    } else {
        $price_html = wc_price( wc_get_price_to_display( $product ) );
    }

    // ── Variations (weight + filter) ─────────────────────────
    $weight_options = array();
    $filter_options = array();
    $variations_map = array(); // variation_id => attributes

    if ( $product->is_type( 'variable' ) ) {
        $variation_attributes = $product->get_variation_attributes();

        foreach ( $variation_attributes as $attribute_name => $options ) {
            $label = wc_attribute_label( $attribute_name, $product );
            if ( stripos( $label, 'weight' ) !== false || $attribute_name === 'pa_weight' ) {
                foreach ( $options as $opt ) {
                    $weight_options[] = $opt;
                }
            } elseif ( stripos( $label, 'filter' ) !== false || $attribute_name === 'pa_filter' || $attribute_name === 'pa_grind' ) {
                $filter_options[] = array( 'value' => $opt, 'label' => $opt );
                // Rebuild properly
            }
        }

        // Rebuild filter options cleanly
        $filter_options = array();
        foreach ( $variation_attributes as $attribute_name => $options ) {
            $label = wc_attribute_label( $attribute_name, $product );
            if ( stripos( $label, 'filter' ) !== false || stripos( $attribute_name, 'filter' ) !== false || stripos( $attribute_name, 'grind' ) !== false ) {
                foreach ( $options as $opt ) {
                    $filter_options[] = array( 'value' => $opt, 'label' => $opt );
                }
                break;
            }
        }

        // Build variations map for price lookup
        $available_variations = $product->get_available_variations();
        foreach ( $available_variations as $v ) {
            $variations_map[] = array(
                'variation_id'  => $v['variation_id'],
                'attributes'    => $v['attributes'],
                'display_price' => $v['display_price'],
                'price_html'    => $v['price_html'],
                'is_in_stock'   => $v['is_in_stock'],
            );
        }

        // Default variation
        $default_attrs = $product->get_default_attributes();
        if ( ! empty( $default_attrs ) ) {
            $ds     = WC_Data_Store::load( 'product' );
            $def_id = $ds->find_matching_product_variation( $product, $default_attrs );
        } else {
            $def_id = 0;
            foreach ( $available_variations as $v ) {
                if ( $v['is_in_stock'] ) { $def_id = $v['variation_id']; break; }
            }
        }
    } else {
        $def_id = 0;
    }

    // ── Permalink ─────────────────────────────────────────────
    $permalink = get_permalink( $product_id );

    // ── Response ──────────────────────────────────────────────
    wp_send_json_success( array(
        'product_id'         => $product_id,
        'product_type'       => $product->get_type(),
        'name'               => $product->get_name(),
        'price_html'         => $price_html,
        'flavor_notes'       => $flavor_notes,
        'gallery'            => $gallery_images,
        'profile_text'       => $profile_text,
        'profile_icon'       => $profile_icon,
        'profile_bg'         => $profile_bg,
        'profile_color'      => $profile_color,
        'roast_text'         => $roast_text,
        'country_text'       => $country_text,
        'weight_options'     => array_values( array_unique( $weight_options ) ),
        'filter_options'     => $filter_options,
        'variations_map'     => $variations_map,
        'default_variation'  => $def_id,
        'permalink'          => $permalink,
        'ajax_url'           => admin_url( 'admin-ajax.php' ),
        'nonce'              => wp_create_nonce( 'wc-add-to-cart' ),
    ) );
}

/**
 * Update checkout address country field layout
 * Restrict all countries to India only to completely remove any dropdowns
 */
add_filter( 'woocommerce_countries', 'nv_restrict_shipping_country', 9999 );
add_filter( 'woocommerce_shipping_countries', 'nv_restrict_shipping_country', 9999 );
add_filter( 'woocommerce_countries_allowed_countries', 'nv_restrict_shipping_country', 9999 );
function nv_restrict_shipping_country( $countries ) {
    return array( 'IN' => 'India' );
}

add_filter( 'woocommerce_checkout_fields', 'nv_force_checkout_country_fields', 9999 );
function nv_force_checkout_country_fields( $fields ) {
    // Force billing country
    if ( isset( $fields['billing']['billing_country'] ) ) {
        $fields['billing']['billing_country']['priority'] = 95;
    }
    // Force shipping country
    if ( isset( $fields['shipping']['shipping_country'] ) ) {
        $fields['shipping']['shipping_country']['priority'] = 95;
    }
    return $fields;
}

/**
 * Remove priority override from WooCommerce locale for India
 */
add_filter( 'woocommerce_get_country_locale_default', 'nv_remove_locale_country_priority', 9999 );
add_filter( 'woocommerce_get_country_locale', 'nv_remove_locale_country_priority_all', 9999 );

function nv_remove_locale_country_priority( $locale ) {
    if ( isset( $locale['country']['priority'] ) ) {
        unset( $locale['country']['priority'] );
    }
    return $locale;
}

function nv_remove_locale_country_priority_all( $locales ) {
    if ( isset( $locales['IN']['country']['priority'] ) ) {
        unset( $locales['IN']['country']['priority'] );
    }
    return $locales;
}

/**
 * Change checkout toggle text
 */
add_filter( 'gettext', 'nv_change_checkout_ship_to_bill_text', 10, 3 );
function nv_change_checkout_ship_to_bill_text( $translated_text, $text, $domain ) {
    if ( $domain === 'woocommerce' && $text === 'Ship to a different address?' ) {
        $translated_text = 'Bill to a different address?';
    }
    return $translated_text;
}
