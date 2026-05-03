<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $current_user, $woocommerce, $wpdb; 

do_action('wt_before_my_store_credit');
?>

<div class="wt_Store_credit">

<?php do_action('wt_my_store_credit'); ?>

</div>

<?php
do_action('wt_after_my_store_credit');

