<?php
/**
 * Custom Product Card Template
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}

if ( $product->is_type('variable') ) {
    $price = wc_price($product->get_variation_price( 'min', true ));
} else {
    $price = wc_price(wc_get_price_to_display( $product ));
}
$title = $product->get_name();
$is_in_stock = $product->is_in_stock();
$is_simple = $product->is_type('simple');
$subtitle = get_field('flavor_notes', $product->get_id());
if (empty($subtitle)) {
    // Check pa_flavour-profile first as it's the most common
    $profile_terms = get_the_terms($product->get_id(), 'pa_flavour-profile');
    if (!empty($profile_terms) && !is_wp_error($profile_terms)) {
        $subtitle = $profile_terms[0]->name;
    } else {
        // Fallback to tags or other taxonomies
        $tags = get_the_terms($product->get_id(), 'product_tag');
        if (!empty($tags) && !is_wp_error($tags)) {
            $subtitle = $tags[0]->name;
        }
    }
}

// Optimization: Use get_the_terms which is cached by WP_Query
$roasts = get_the_terms($product->get_id(), 'pa_roast');
$countries = get_the_terms($product->get_id(), 'pa_country');
$flavour_profile = get_the_terms($product->get_id(), 'pa_flavour-profile');

$roast_text = (!empty($roasts) && !is_wp_error($roasts)) ? $roasts[0]->name : 'Roast';
$country_text = (!empty($countries) && !is_wp_error($countries)) ? $countries[0]->name : 'Origin';

$is_new = (time() - strtotime(get_the_time('Y-m-d'))) < ( 30 * DAY_IN_SECONDS );
$profile_text = (!empty($flavour_profile) && !is_wp_error($flavour_profile)) ? $flavour_profile[0]->name : '';
$profile_slug = sanitize_title($profile_text);

$flavour_icons = array(
    'bright-fruity' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/blue-berry-icon.svg', 'bg' => '#eef2ff', 'color' => '#3b5998'),
    'rich-strong' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/rich-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
    'bold-balanced' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/bold-icon.svg', 'bg' => '#f4eee8', 'color' => '#8b5a2b'),
    'sweet-juicy' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/orange-icon.svg', 'bg' => '#fff0e6', 'color' => '#e65c00'),
    'delicate-floral' => array('icon' => 'https://naivo.in/wp-content/uploads/2024/08/floral-icon.svg', 'bg' => '#f3e8ff', 'color' => '#7e22ce'),
);

$profile_icon = '';
$profile_bg = '#f0f4ff';
$profile_color = '#3b5998';

foreach ($flavour_icons as $slug_key => $data) {
    if (strpos($profile_slug, $slug_key) !== false) {
        $profile_icon = $data['icon'];
        $profile_bg = $data['bg'];
        $profile_color = $data['color'];
        break;
    }
}
?>

<div <?php wc_product_class( 'nv-product-card', $product ); ?>>
    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="nv-product-img">
        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
        <div class="nv-badges">
            <?php if ($is_new): ?>
                <span class="nv-badge new">NEW</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($profile_text)): ?>
            <div class="nv-badge-secondary" style="background: <?php echo esc_attr($profile_bg); ?> !important; color: <?php echo esc_attr($profile_color); ?> !important;">
                <?php if ($profile_icon): ?>
                    <img src="<?php echo esc_url($profile_icon); ?>" alt="icon" style="width: 16px; height: 16px; margin: 0; display: inline-block;">
                <?php endif; ?>
                <?php echo esc_html($profile_text); ?>
            </div>
        <?php endif; ?>
    </a>

    <div class="nv-product-info">
        <div class="nv-price"><?php echo $price; ?></div>
        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="nv-title"><?php echo esc_html($title); ?></a>
        <?php if ($subtitle): ?>
            <div class="nv-subtitle"><?php echo esc_html($subtitle); ?></div>
        <?php endif; ?>

        <div class="nv-features" style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #555; font-weight: 500; margin-bottom: 20px;">
            <div class="nv-feature" style="background:transparent; padding:0; display:flex; gap:4px; align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg> 
                <?php echo esc_html($roast_text); ?>
            </div>
            <span style="color: #ccc;">|</span>
            <div class="nv-feature" style="background:transparent; padding:0; display:flex; gap:4px; align-items:center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> 
                <?php echo esc_html($country_text); ?>
            </div>
        </div>

        <div class="nv-actions">
            <?php if ( $is_in_stock ) : ?>
                <?php if ( $is_simple ) : ?>
                    <!-- AJAX Add to Cart for simple products -->
                    <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
                       data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                       data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                       data-quantity="1"
                       class="nv-cart-btn add_to_cart_button ajax_add_to_cart"
                       aria-label="Add to cart">
                        <svg class="nv-cart-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <svg class="nv-cart-spinner" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        <svg class="nv-cart-check" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </a>
                <?php else :
                    // Variable product: find default variation for AJAX add-to-cart
                    $default_variation_id = 0;
                    $default_attributes = $product->get_default_attributes();
                    
                    if ( ! empty( $default_attributes ) ) {
                        // Try to match default attributes to a variation
                        $data_store = WC_Data_Store::load( 'product' );
                        $default_variation_id = $data_store->find_matching_product_variation( $product, $default_attributes );
                    }
                    
                    // If no default, get the first available variation
                    if ( ! $default_variation_id ) {
                        $available_variations = $product->get_available_variations();
                        if ( ! empty( $available_variations ) ) {
                            foreach ( $available_variations as $variation ) {
                                if ( $variation['is_in_stock'] ) {
                                    $default_variation_id = $variation['variation_id'];
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ( $default_variation_id ) :
                        $variation_obj = wc_get_product( $default_variation_id );
                        $variation_attributes = $variation_obj ? $variation_obj->get_variation_attributes() : array();
                    ?>
                    <!-- AJAX Add to Cart for variable products (uses default/first variation) -->
                    <a href="#"
                       data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                       data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                       data-quantity="1"
                       data-variation_id="<?php echo esc_attr( $default_variation_id ); ?>"
                       <?php foreach ( $variation_attributes as $attr_name => $attr_value ) : ?>
                       data-<?php echo esc_attr( sanitize_title( $attr_name ) ); ?>="<?php echo esc_attr( $attr_value ); ?>"
                       <?php endforeach; ?>
                       class="nv-cart-btn nv-cart-btn-variable add_to_cart_button ajax_add_to_cart"
                       aria-label="Add to cart">
                        <svg class="nv-cart-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <svg class="nv-cart-spinner" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        <svg class="nv-cart-check" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </a>
                    <?php else : ?>
                    <!-- Fallback: link to PDP if no variation found -->
                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>"
                       class="nv-cart-btn nv-cart-btn-variable"
                       aria-label="Select options">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Direct checkout link -->
                <?php if ( $is_simple ) : ?>
                    <a href="<?php echo esc_url( wc_get_checkout_url() . '?add-to-cart=' . $product->get_id() ); ?>" class="nv-buy-btn">
                        Buy Now
                    </a>
                <?php else :
                    // For variable products, link to checkout with default variation
                    $buy_now_url = $product->get_permalink();
                    if ( ! empty( $default_variation_id ) ) {
                        $buy_now_url = wc_get_checkout_url() . '?add-to-cart=' . $product->get_id() . '&variation_id=' . $default_variation_id;
                        if ( ! empty( $variation_attributes ) ) {
                            foreach ( $variation_attributes as $attr_name => $attr_value ) {
                                $buy_now_url .= '&' . urlencode( sanitize_title( $attr_name ) ) . '=' . urlencode( $attr_value );
                            }
                        }
                    }
                ?>
                    <a href="<?php echo esc_url( $buy_now_url ); ?>" class="nv-buy-btn">
                        Buy Now
                    </a>
                <?php endif; ?>
            <?php else : ?>
                <span class="nv-cart-btn nv-cart-btn-oos" aria-label="Out of stock">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </span>
                <span class="nv-buy-btn nv-buy-btn-oos">Sold Out</span>
            <?php endif; ?>
        </div>
    </div>
</div>

