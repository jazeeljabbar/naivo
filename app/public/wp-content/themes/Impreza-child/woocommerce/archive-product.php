<?php
/**
 * Custom Shop Archive Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

?>
<div class="nv-shop-ticker">
    Take <a href="#">our quiz</a> and find your match
    <button class="nv-ticker-close" aria-label="Close">&times;</button>
</div>

<section class="nv-shop-favorites-hero" aria-label="The World’s Favourite Coffees">
    <div class="nv-shop-favorites-inner">
        <h1>The World’s Favourite Coffees</h1>
        <p class="nv-shop-favorites-location">
            <img src="<?php echo esc_url( home_url( '/wp-content/uploads/2024/08/location.png' ) ); ?>" alt="" width="24" height="25">
            <span>Crafted in India</span>
        </p>
    </div>
</section>

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

            // Get All Coffee total products count
            $all_coffee_count = wp_count_posts('product')->publish;
            $shop_url = get_permalink( wc_get_page_id( 'shop' ) );

            // All Coffee Icon Card
            echo '<a href="' . esc_url($shop_url) . '" class="nv-cat-item" data-category="all">';
            echo '<div class="nv-cat-icon">';
            echo '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 15px 0;"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>';
            echo '</div>';
            echo '<div class="nv-cat-details">';
            echo '<span class="nv-cat-name">All Coffee</span>';
            echo '<span class="nv-cat-count">' . esc_html($all_coffee_count) . '</span>';
            echo '</div>';
            echo '</a>';

            foreach ($categories as $category) {
                $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                $image_url    = wp_get_attachment_url( $thumbnail_id );
                if ( ! $image_url ) {
                    $image_url = wc_placeholder_img_src();
                }
                $cat_url = get_term_link( $category );
                
                echo '<a href="' . esc_url($cat_url) . '" class="nv-cat-item" data-category="' . esc_attr($category->slug) . '">';
                echo '<div class="nv-cat-icon"><img decoding="async" src="' . esc_url($image_url) . '" alt="' . esc_attr($category->name) . '"></div>';
                echo '<div class="nv-cat-details">';
                echo '<span class="nv-cat-name">' . esc_html($category->name) . '</span>';
                echo '<span class="nv-cat-count">' . esc_html($category->count) . '</span>';
                echo '</div>';
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

    <div class="nv-mobile-filter-backdrop" id="nv-mobile-filter-backdrop"></div>

    <!-- Filter Section -->
    <div class="nv-filter-section" id="nv-filter-section">
        <div class="nv-mobile-filter-header">
            <h3>FILTERS</h3>
            <button class="nv-mobile-filter-close" id="nv-mobile-filter-close" aria-label="Close filters">&times;</button>
        </div>
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
        <div class="nv-mobile-filter-footer">
            <button class="nv-mobile-filter-reset" id="nv-mobile-filter-reset">RESET FILTERS</button>
            <button class="nv-mobile-filter-apply" id="nv-mobile-filter-apply">APPLY FILTERS</button>
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
