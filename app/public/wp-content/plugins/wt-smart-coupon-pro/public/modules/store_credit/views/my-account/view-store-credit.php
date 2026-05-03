<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $current_user, $woocommerce, $wpdb,$wp; 

do_action('wt_before_view_my_store_credit');
?>

<div class="wt_Store_credit">

<?php

if( isset( $wp->query_vars['wt-view-store-credit'] ) && $wp->query_vars['wt-view-store-credit'] > 0 )
{
    $coupon_id = $wp->query_vars['wt-view-store-credit'];

    do_action('wt_store_credit_history', $coupon_id);

} else {
    do_action('wt_my_store_credit');
}



?>

</div>

<?php
do_action('wt_after_store_credit_history');

