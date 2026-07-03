<?php
/**
 * Custom Shop Archive Page
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

?>
<main id="page-content" class="l-main">



<?php echo do_shortcode('[vc_row css="%7B%22mobiles%22%3A%7B%22padding-top%22%3A%2250px%22%2C%22padding-bottom%22%3A%2250px%22%7D%7D" el_class="icon-carousel"][vc_column][vc_custom_heading text="The World’s Favourite Coffees" font_container="tag:h1|text_align:center" use_theme_fonts="yes" css="%7B%22default%22%3A%7B%22color%22%3A%22%23151516%22%2C%22text-align%22%3A%22center%22%2C%22font-size%22%3A%2244px%22%2C%22line-height%22%3A%2260.1px%22%2C%22font-weight%22%3A%22700%22%7D%2C%22laptops%22%3A%7B%22color%22%3A%22%23151516%22%2C%22text-align%22%3A%22center%22%2C%22font-size%22%3A%2244px%22%2C%22line-height%22%3A%2260.1px%22%2C%22font-weight%22%3A%22700%22%7D%2C%22tablets%22%3A%7B%22color%22%3A%22%23151516%22%2C%22text-align%22%3A%22center%22%2C%22font-size%22%3A%2244px%22%2C%22line-height%22%3A%2260.1px%22%2C%22font-weight%22%3A%22700%22%7D%2C%22mobiles%22%3A%7B%22color%22%3A%22%23151516%22%2C%22text-align%22%3A%22center%22%2C%22font-size%22%3A%2235px%22%2C%22line-height%22%3A%2245.1px%22%2C%22font-weight%22%3A%22700%22%7D%7D"][vc_column_text css="%7B%22default%22%3A%7B%22color%22%3A%22%23494A4B%22%7D%7D" el_class="icon-location"]<p style="text-align: center;"><img class="alignnone size-full wp-image-134" src="' . esc_url( home_url( '/wp-content/uploads/2024/08/location.png' ) ) . '" alt="" width="24" height="25" />Crafted in India</p>[/vc_column_text][us_separator size="custom" height="30px"][vc_row_inner content_placement="middle" css="%7B%22default%22%3A%7B%22margin-top%22%3A%2250px%22%2C%22padding-top%22%3A%2220px%22%2C%22padding-bottom%22%3A%2220px%22%2C%22border-style%22%3A%22solid%22%2C%22border-top-width%22%3A%221px%22%2C%22border-bottom-width%22%3A%221px%22%2C%22border-color%22%3A%22%23E4E4E4%22%7D%7D"][vc_column_inner][us_carousel post_type="home_icon_carousal" items_layout="219" columns="4" carousel_loop="1" carousel_speed="700ms" el_class="star_icon"][/vc_column_inner][/vc_row_inner][/vc_column][/vc_row]'); ?>


<section class="l-section wpb_row height_medium custom-shop-section">
    <div class="l-section-h i-cf">
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
</div>
</section>
</main>
<?php
get_footer( 'shop' );
?>
