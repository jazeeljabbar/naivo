<?php
/**
 * Custom Shop Archive Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

?>
<div class="nv-shop-ticker">
    Too many coffees to choose from? Take <a href="#">our quiz</a> and find your match
    <button class="nv-ticker-close" aria-label="Close">&times;</button>
</div>

<div class="nv-shop-banner-fullwidth">
    <h1>Promotional Banners</h1>
</div>

<div class="nv-shop-container">

    <!-- Top Categories -->
    <div class="nv-categories-strip">
        <?php
        $target_slugs = array('espresso', 'filter', 'omni', 'ratnagiri-vault', 'showcase', 'exotic');
        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'slug'       => $target_slugs,
        ));

        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            usort($categories, function($a, $b) use ($target_slugs) {
                return array_search($a->slug, $target_slugs) - array_search($b->slug, $target_slugs);
            });

            foreach ($categories as $category) {
                $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                $image_url    = wp_get_attachment_url( $thumbnail_id );
                if ( ! $image_url ) {
                    $image_url = wc_placeholder_img_src();
                }
                $cat_url = get_term_link( $category );
                
                echo '<a href="' . esc_url($cat_url) . '" class="nv-cat-item" data-category="' . esc_attr($category->slug) . '">';
                echo '<img decoding="async" src="' . esc_url($image_url) . '" alt="' . esc_attr($category->name) . '">';
                echo '<span>' . esc_html($category->name) . '</span>';
                echo '</a>';
            }
        }
        ?>
    </div>

    <!-- Mobile Filter/Sort Toggle -->
    <div class="nv-mobile-filter-toggle">
        <button class="nv-mobile-filter-btn" id="nv-mobile-filters-btn">
            FILTERS <span class="nv-mobile-filter-count" style="display:none;">0</span>
        </button>
        <button class="nv-mobile-sort-btn" id="nv-mobile-sort-btn">SORT</button>
    </div>

    <!-- Filter Section -->
    <div class="nv-filter-section" id="nv-filter-section">
        <div class="nv-filter-bar-header">
            <div class="nv-filter-title-row">FILTER</div>
            <div class="nv-sort-title-row">SORT BY</div>
        </div>
        <div class="nv-filter-bar">
        <?php 
        $attributes_to_filter = array(
            'pa_brew-with' => 'Brew With',
            'pa_roast' => 'Roast',
            'pa_process' => 'Process',
            'pa_country' => 'Country',
            'pa_best-had' => 'Best Had',
            'pa_flavour-profile' => 'Flavour Profile'
        );
        foreach ($attributes_to_filter as $tax => $label): 
            $terms = get_terms(array('taxonomy' => $tax, 'hide_empty' => true));
            if (!empty($terms) && !is_wp_error($terms)):
        ?>
            <div class="nv-custom-dropdown" data-filter="<?php echo esc_attr($tax); ?>">
                <button class="nv-dropdown-btn">
                    <span class="nv-badge-count" style="display:none;">0</span>
                    <?php echo esc_html($label); ?> <span class="nv-chevron"><svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </button>
                <div class="nv-dropdown-menu">
                    <?php foreach ($terms as $term): ?>
                        <label class="nv-checkbox-label">
                            <input type="checkbox" value="<?php echo esc_attr($term->slug); ?>" data-name="<?php echo esc_attr($term->name); ?>">
                            <span><?php echo esc_html($term->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php 
            endif;
        endforeach; 
        ?>
        
        <div class="nv-custom-dropdown nv-sort-dropdown" style="margin-left: auto;">
            <button class="nv-dropdown-btn">
                <span class="nv-sort-label">HIGH TO LOW</span> <span class="nv-chevron"><svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="nv-dropdown-menu nv-dropdown-menu-right">
                <label class="nv-radio-label"><input type="radio" name="orderby" value="price-desc" checked> <span>Price: High to Low</span></label>
                <label class="nv-radio-label"><input type="radio" name="orderby" value="price"> <span>Price: Low to High</span></label>
                <label class="nv-radio-label"><input type="radio" name="orderby" value="alphabetical"> <span>Alphabetical (A-Z)</span></label>
                <label class="nv-radio-label"><input type="radio" name="orderby" value="date"> <span>Newest</span></label>
            </div>
        </div>
        </div>
    </div>

    <div class="nv-active-filters"></div>

    <div class="nv-product-count">
        <?php
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 24,
            'post_status' => 'publish',
            'meta_key' => '_price',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        );
        $query = new WP_Query($args);
        echo 'Showing 1 to ' . min(24, $query->found_posts) . ' of ' . $query->found_posts . ' Products';
        ?>
    </div>

    <!-- Product Grid -->
    <div class="nv-product-grid">
        <?php
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                include( get_stylesheet_directory() . '/woocommerce/content-product-custom.php' );
            }
        } else {
            echo '<p>No products found.</p>';
        }
        wp_reset_postdata();
        ?>
    </div>

</div>
<?php
get_footer( 'shop' );
?>
