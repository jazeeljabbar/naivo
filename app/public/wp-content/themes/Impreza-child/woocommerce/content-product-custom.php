<?php
/**
 * Custom Product Card Template - Replicating Naivo Premium Style with Design Polish
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
    return;
}

// Variables Setup
$title = $product->get_name();
$is_in_stock = $product->is_in_stock();
$is_simple = $product->is_type('simple');

$price = $product->get_price_html();

// Get tasting notes / ingredients (subtitle) below title
$subtitle = get_field('flavor_notes', $product->get_id());
if (empty($subtitle)) {
    // Falls back directly to commas-separated product tags (e.g. Almonds, Floral, Gooseberries)
    $tags = get_the_terms($product->get_id(), 'product_tag');
    if (!empty($tags) && !is_wp_error($tags)) {
        $tag_names = array();
        foreach ($tags as $tag) {
            $tag_names[] = $tag->name;
        }
        $subtitle = implode(', ', $tag_names);
    }
}

// Roast and Country Origin terms
$roasts = get_the_terms($product->get_id(), 'pa_roast');
$countries = get_the_terms($product->get_id(), 'pa_country');

$roast_text = (!empty($roasts) && !is_wp_error($roasts)) ? $roasts[0]->name : 'Roast';
if ( $roast_text && $roast_text !== 'Roast' && strpos( strtolower( $roast_text ), 'roast' ) === false ) {
    $roast_text .= ' Roast';
}
$country_text = (!empty($countries) && !is_wp_error($countries)) ? $countries[0]->name : 'Origin';

// Retrieve flavour profile terms dynamically (flavour-categories first, fallback to pa_flavour-profile)
$fl_terms = get_the_terms($product->get_id(), 'flavour-categories');
if (empty($fl_terms) || is_wp_error($fl_terms)) {
    $fl_terms = get_the_terms($product->get_id(), 'pa_flavour-profile');
}

$profile_text = '';
$profile_slug = '';
$profile_icon = '';

if (!empty($fl_terms) && !is_wp_error($fl_terms)) {
    $term = $fl_terms[0];
    $profile_text = $term->name;
    $profile_slug = $term->slug;
    
    // Dynamic ACF image lookup matching Homepage
    $image = get_field('flavour_thumbnail_image', $term->taxonomy . '_' . $term->term_id);
    if (!empty($image) && is_array($image) && !empty($image['url'])) {
        $profile_icon = wp_make_link_relative($image['url']);
    }
}

// Fallback to static mapping using relative paths if database image is missing
if (empty($profile_icon) && !empty($profile_slug)) {
    $flavour_icons = array(
        'bright-fruity' => '/wp-content/uploads/2024/08/blue-berry-icon.svg',
        'rich-strong' => '/wp-content/uploads/2024/08/rich-icon.svg',
        'bold-balanced' => '/wp-content/uploads/2024/08/bold-icon.svg',
        'sweet-juicy' => '/wp-content/uploads/2024/08/orange-icon.svg',
        'delicate-floral' => '/wp-content/uploads/2024/08/floral-icon.svg',
    );
    foreach ($flavour_icons as $slug_key => $icon_path) {
        if (strpos($profile_slug, $slug_key) !== false) {
            $profile_icon = $icon_path;
            break;
        }
    }
}

// Get High-Resolution Featured and Hover images ('large' falls back to 'full')
$primary_image_id = $product->get_image_id();
$primary_image_url = wp_get_attachment_image_url( $primary_image_id, 'large' );
if ( ! $primary_image_url ) {
    $primary_image_url = wp_get_attachment_image_url( $primary_image_id, 'full' );
}
if ( ! $primary_image_url ) {
    $primary_image_url = wc_placeholder_img_src('large');
}

$gallery_image_ids = $product->get_gallery_image_ids();
$hover_image_url = '';
if ( ! empty( $gallery_image_ids ) ) {
    $hover_image_url = wp_get_attachment_image_url( $gallery_image_ids[0], 'large' );
    if ( ! $hover_image_url ) {
        $hover_image_url = wp_get_attachment_image_url( $gallery_image_ids[0], 'full' );
    }
}
?>

<div <?php wc_product_class( 'nv-product-card', $product ); ?>>
    <div class="nv-product-img">
        <?php 
        $is_new = has_term( 'new-arrivals', 'product_cat', $product->get_id() );
        $is_bestseller = has_term( 'best-sellers', 'product_cat', $product->get_id() );
        if ($is_new || $is_bestseller) : ?>
            <div class="nv-badges-container">
                <?php if ($is_bestseller) : ?>
                    <div class="nv-badge bestseller">Bestseller</div>
                <?php elseif ($is_new) : ?>
                    <div class="nv-badge new">New</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="nv-product-image-container">
            <img src="<?php echo esc_url( $primary_image_url ); ?>" class="nv-primary-image" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
            <?php if ( $hover_image_url ) : ?>
                <img src="<?php echo esc_url( $hover_image_url ); ?>" class="nv-hover-image" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
            <?php endif; ?>
        </a>

        <?php if ( $hover_image_url ) : ?>
            <button class="nv-mobile-details-toggle" aria-label="Toggle details">
                <svg class="nv-info-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <svg class="nv-close-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        <?php endif; ?>

        <div class="nv-overlay-badges">
            <span class="nv-overlay-badge left">
                <img src="/wp-content/uploads/2024/09/coffee-beans.png" alt="roast" width="16" height="16" style="object-fit: contain;">
                <?php echo esc_html($roast_text); ?>
            </span>
            <span class="nv-overlay-badge right">
                <img src="/wp-content/uploads/2024/09/location-04.png" alt="location" width="16" height="16" style="object-fit: contain;">
                <?php echo esc_html($country_text); ?>
            </span>
        </div>
    </div>

    <div class="nv-product-info">
        <div class="nv-badge-price-row">
            <?php if (!empty($profile_text)): ?>
                <div class="fl-tag <?php echo esc_attr($profile_slug); ?>">
                    <?php if ($profile_icon): ?>
                        <img decoding="async" src="<?php echo esc_url($profile_icon); ?>" alt="">
                    <?php endif; ?>
                    <?php echo esc_html($profile_text); ?>
                </div>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <div class="nv-price"><?php echo $price; ?></div>
        </div>

        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="nv-title"><?php echo esc_html($title); ?></a>
        <?php if ($subtitle): ?>
            <div class="nv-subtitle"><?php echo esc_html($subtitle); ?></div>
        <?php endif; ?>

        <div class="nv-actions">
            <?php if ( $is_in_stock ) : ?>
                <?php if ( $is_simple ) : ?>
                    <!-- Outlined Add to Cart (Simple Product) -->
                    <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
                       data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                       data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                       data-quantity="1"
                       class="nv-cart-btn add_to_cart_button ajax_add_to_cart"
                       aria-label="Add to cart">
                        <svg class="nv-cart-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <svg class="nv-cart-spinner" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        <svg class="nv-cart-check" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </a>
                <?php else :
                    // Outlined Add to Cart (Variable Product: adds first available/default variation)
                    $default_variation_id = 0;
                    $default_attributes = $product->get_default_attributes();
                    if ( ! empty( $default_attributes ) ) {
                        $data_store = WC_Data_Store::load( 'product' );
                        $default_variation_id = $data_store->find_matching_product_variation( $product, $default_attributes );
                    }
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
                            <svg class="nv-cart-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            <svg class="nv-cart-spinner" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            <svg class="nv-cart-check" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $product->get_permalink() ); ?>"
                           class="nv-cart-btn nv-cart-btn-variable"
                           aria-label="Select options">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Buy Now Button -->
                <a href="<?php echo esc_url( $product->get_permalink() ); ?>"
                   class="nv-buy-btn"
                   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
                    Buy Now
                </a>
            <?php else : ?>
                <span class="nv-cart-btn nv-cart-btn-oos" aria-label="Out of stock">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                </span>
                <span class="nv-buy-btn nv-buy-btn-oos">Sold Out</span>
            <?php endif; ?>
        </div>
    </div>
</div>
