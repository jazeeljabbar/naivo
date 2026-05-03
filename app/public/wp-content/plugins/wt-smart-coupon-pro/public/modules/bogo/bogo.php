<?php
/**
 * BOGO common section
 *
 * @since 3.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbte_Smart_Coupon_Bogo_Common' ) ) {
	return;
}

/**
 * The common functionality of new BOGO module.
 *
 * @since 3.0.0
 */
class Wbte_Smart_Coupon_Bogo_Public extends Wbte_Smart_Coupon_Bogo_Common {

	/**
	 *  Module name
	 *
	 *  @var string $module_base module name
	 */
	public $module_base = 'bogo';

	/**
	 *  Module Id
	 *
	 *  @var string $module_id module id
	 */
	public $module_id = '';

	/**
	 *  Module static id
	 *
	 *  @var string $module_id_static module static id
	 */
	public static $module_id_static = '';

	/**
	 *  Class instance
	 *
	 *  @var null|object $instance instance of class or null
	 */
	private static $instance = null;

	/**
	 * To store total BOGO discount amount.
	 * Array key is coupon code, and value is total discount amount.
	 *
	 * @var array $bogo_discounts array of total discount amount for each coupon
	 */
	public static $bogo_discounts = array();

	/**
	 *  Session key for BOGO eligible array session.
	 *
	 *  @var string $bogo_eligible_session_id session key for BOGO eligible array session.
	 */
	public static $bogo_eligible_session_id = 'wbte_sc_bogo_eligible';

	/**
	 *  To store discount data of cheap/expensive product of coupon.
	 *  eg structure:
	 *      array(
	 *          'coupon_code'  => array(
	 *              'discount' => 10,
	 *              'quantity' => 3,
	 *          )
	 *      )
	 *
	 *  @var array $bogo_cheap_exp_coupon_data
	 */
	public static $bogo_cheap_exp_coupon_data = array();

	/**
	 *  To store checked products data for cheap/expensive product of coupon.
	 *  eg structure:
	 *      array(
	 *          'cart_item_key' => array(
	 *              'discounted_qty' => 1, // Total discounted quantity for the product
	 *              'discount'       => 10, // Total discount amount for the product
	 *              'coupons'        => array( 'COUPON1', 'COUPON2' ), // If auto bogo then coupon title otherwise coupon code
	 *              'coupon_codes'   => array( 'coupon1', 'coupon2' ),
	 *          )
	 *      )
	 *
	 *  @var array $bogo_cheap_exp_checked_products
	 */
	public static $bogo_cheap_exp_checked_products = array();

	/**
	 *  Session key for cheap/expensive checked products array session.
	 *
	 *  @var string $cheap_exp_checked_products_session_id session key for cheap/expensive checked products array session.
	 */
	public static $cheap_exp_checked_products_session_id = 'wbte_sc_cheap_exp_checked_products';

	/**
	 * To store balance amount available for discounting with normal coupons.
	 * eg structure:
	 *      array(
	 *          'cart_item_key' => 17.99
	 *      )
	 *
	 * @var array $giveaway_discounted_amount
	 */
	public static $giveaway_discounted_amount = array();

	/**
	 * To store the coupon code of cheap/expensive coupon whenapplied.
	 *
	 * @var string $is_cheap_exp_coupon_applied
	 */
	private static $is_cheap_exp_coupon_applied = '';


	/**
	 * Constructor function of the class
	 * Add submenu for new BOGO
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		/**
		 *  Ajax hooks
		 */
		$this->hooks_ajax();

		/**
		 *
		 * Action/processing hooks
		 */
		$this->hooks_actions_and_processing();

		/**
		 *  Display hooks
		 */
		$this->hooks_display();

		/**
		 *
		 * Value update/calculation hooks
		 */
		$this->hooks_calc_and_update();

		/**
		 *
		 * Other hooks
		 */
		$this->hooks_others();
	}

	/**
	 * Get Instance
	 *
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wbte_Smart_Coupon_Bogo_Public();
		}
		return self::$instance;
	}

	/**
	 *  This function lists all ajax hooks.
	 *
	 *  @since 3.0.0
	 */
	public function hooks_ajax() {
		// Ajax hook to return variation ID on giveaway product attribute change.
		add_action( 'wc_ajax_update_variation_id_on_choose', array( $this, 'ajax_find_matching_product_variation_id' ) );

		// Ajax function for adding Giveaway products into cart when customer clicks on the product.
		add_action( 'wc_ajax_wbte_choose_free_product', array( $this, 'add_free_product_to_cart' ) );
	}

	/**
	 *  This function lists all hooks related to action/processing.
	 *
	 *  @since 3.0.0
	 */
	public function hooks_actions_and_processing() {

		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_coupon_valid' ), 10, 2 );

		add_action( 'woocommerce_applied_coupon', array( $this, 'add_free_product_into_cart' ) );

		/* Display giveaway products in the cart page */
		add_action( 'template_redirect', array( $this, 'add_giveaway_products_with_coupon' ), 16 );

		/* Remove free products from the cart if cart is empty */
		add_action( 'template_redirect', array( $this, 'check_any_free_products_without_coupon' ), 15 );

		add_action( 'woocommerce_removed_coupon', array( $this, 'remove_free_product_from_cart' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'check_and_add_giveaway_on_add_to_cart' ), 111, 6 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'check_to_add_giveaway' ), 111, 4 );

		add_action( 'woocommerce_cart_item_removed', array( $this, 'update_cart_giveaway_count_on_item_removed' ), 111, 2 );

		// For any_product_from_store and any_product_from_category.

		add_action( 'woocommerce_add_to_cart', array( $this, 'add_giveaway_on_add_to_cart' ), 11, 5 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'add_giveaway_on_update_cart' ), 110, 4 );

		add_action( 'woocommerce_cart_item_removed', array( $this, 'remove_free_gift_of_removed_product' ), 10, 2 );

		add_action( 'woocommerce_removed_coupon', array( $this, 'remove_bogo_eligible_session' ) );

		add_action( 'woocommerce_thankyou', array( $this, 'remove_all_bogo_sessions' ) );

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_bxgx_discount_amount' ), 10, 5 );

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'alter_discount_amount_for_giveaway_products' ), 9, 5 );

		/** BOGO type Cheapest/Expensive */

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_cheap_exp_discount_amount' ), 10, 5 );

		add_action( 'woocommerce_applied_coupon', array( $this, 'cheap_exp_coupon_applied' ) );

		add_action( 'woocommerce_removed_coupon', array( $this, 'remove_cheap_exp_checked_products_session' ) );

		/** BOGO type Cheapest/Expensive  end */
	}

	/**
	 *  This function lists all hooks related to display.
	 *
	 *  @since 3.0.0
	 */
	public function hooks_display() {
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'campaign_name_instead_code' ), 10, 2 );

		// Show/Update subtotal HTML.
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'alter_cart_item_price' ), 1000, 2 );

		// Mention its a giveaway product in the cart item table.
		add_action( 'woocommerce_after_cart_item_name', array( $this, 'display_giveaway_product_description' ) );

		// Alter coupon price section in order summary section.
		add_filter( 'woocommerce_coupon_discount_amount_html', array( $this, 'alter_coupon_discount_amount_html' ), 100, 2 );

		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'exclude_giveaway_from_other_discounts' ), 10, 4 );

		add_filter( 'wt_sc_alter_custom_coupon_applied_message', array( $this, 'alter_bogo_applied_message' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'show_giveaway_eligible_message' ) );

		// Alter the coupon title text when printing the coupon in My account, cart, checkout etc.
		add_filter( 'wt_smart_coupon_meta_data', array( $this, 'alter_coupon_title_text' ), 10, 2 );

		// Set cart item quantity as non editable.
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'update_cart_item_quantity_field' ), 5, 3 );

		add_filter( 'wbte_sc_alter_blocks_data', array( $this, 'add_blocks_data' ) );

		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'woocommerce_get_order_item_totals' ), 11, 2 );

		// Remove/hide giveaway product meta data from item meta array.
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'unset_free_product_order_item_meta_data' ), 10, 2 );

		/** BOGO type Cheapest/Expensive */

		add_action( 'woocommerce_after_cart_item_name', array( $this, 'display_giveaway_product_description_cheap_exp' ) );

		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'update_cart_item_price_cheap_exp' ), 10, 2 );

		/** BOGO type Cheapest/Expensive  end */
	}

	/**
	 *  This function lists all hooks related to value updates/calculation.
	 *
	 *  @since 3.0.0
	 */
	public function hooks_calc_and_update() {

		// Update total after discount.
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'discounted_calculated_total' ), 999 );

		// Update gift item details as order item meta when creating an order.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_free_product_details_into_order' ), 10, 3 );

		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'update_order_total' ), 100, 2 );

		/** BOGO type Cheapest/Expensive */

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'discounted_calculated_total_cheap_exp' ), 999 );

		/** BOGO type Cheapest/Expensive  end */
	}

	/**
	 *  This function lists all hooks other than above list
	 *
	 *  @since 3.0.0
	 */
	public function hooks_others() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'wt_sc_blocks_register', array( $this, 'register_blocks' ) );
	}

	/**
	 *  Add required scripts/styles for public side BOGO functionality.
	 *
	 *  @since 3.0.0
	 */
	public function enqueue_scripts() {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_style( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/style.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION );
			wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/script.js', array( 'jquery' ), WEBTOFFEE_SMARTCOUPON_VERSION, false );
		}
	}

	/**
	 * Check applied BOGO coupon is valid or not.
	 * Check the cart amount and quantity against the coupon's minimum and maximum restrictions. Only consider products applicable for the coupon when performing the check. If there are no restrictions, include all products (except free products for both scenarios).
	 * If the coupon's apply repeatedly mode is set to 'custom,' there is no need to check the maximum amount and maximum quantity. These will be verified during the 'custom' check.
	 *
	 * @since 3.0.0
	 *
	 * @param  boolean $valid  Current status of coupon.
	 * @param  object  $coupon Coupon object.
	 * @return boolean     Return true if coupon is valid, otherwise false
	 * @throws Exception   Throws exception if the cart does not meet the required conditions for the coupon.
	 */
	public static function is_coupon_valid( $valid, $coupon ) {

		// If coupon is not valid or not a BOGO coupon, return the current status.
		if ( ! $valid || ! self::is_new_bogo_activated() || ! self::is_bogo( $coupon->get_id() ) ) {
			return $valid;
		}

		// If BOGO not created from SC, return false.
		if ( ! get_post_meta( $coupon->get_id(), 'wbte_sc_bogo_created_on_sc_bogo', true ) ) {
			return false;
		}

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return false;
		}

		$coupon_id = $coupon->get_id();
		$coupon_code = $coupon->get_code();

		// $applicable_products is reference arguments for the below function.
		if ( ! self::validate_coupon_on_products_categories( $coupon_id ) ) {
			throw new Exception( esc_html__( 'Sorry, this coupon is not applicable to selected products.', 'wt-smart-coupons-for-woocommerce-pro' ), 109 );
		}

		$cart_amount = 0;
		$cart_qty    = 0;

		$total_cart_amount = 0;

		$is_custom                            = 'wbte_sc_bogo_apply_custom' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
		$is_same_product_in_cart              = 'same_product_in_the_cart' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		$same_product_applicable_found        = false;
		$same_product_custom_applicable_found = false;
		$is_spends                            = self::is_coupon_based_on_subtotal( $coupon_id );

		$wbte_sc_min_each_qty = max( 1, absint( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ) );
		$wbte_sc_max_each_qty = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' );

		$is_bxgx = self::is_bxgx( $coupon_id );
		$cheap_exp_session = self::get_cheap_exp_checked_products_session();

		$specific_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$specific_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		$on_sale_non_sale = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' );

		$no_restriction = empty( $specific_products ) && empty( $excluded_products ) && empty( $specific_categories ) && empty( $excluded_categories ) && empty( $on_sale_non_sale );

		// To get applicable products.
		$args = array(
			'coupon_products'           => $specific_products,
			'coupon_categories'         => $specific_categories,
			'coupon_exclude_products'   => $excluded_products,
			'coupon_exclude_categories' => $excluded_categories,
			'on_sale_non_sale'          => $on_sale_non_sale
		);

		foreach ( $cart->get_cart() as $item ) {

			if ( isset( $item['wt_credit_amount'] ) ) {
				continue;
			}

			if ( self::is_a_free_item( $item ) || self::is_old_bogo_free_product( $item ) ){

				if( apply_filters( 'wbte_sc_bogo_include_free_item_in_total_cart_amount', true, $coupon_code ) ){
					$free_item_price = 0;
					if( isset( $item['wbte_sc_bogo_discount'] ) && $coupon_code !== $item['wbte_sc_free_gift_coupon'] ){
						$free_item_price = $item['data']->get_price() - $item['wbte_sc_bogo_discount'] ;
					}
					else if( self::is_old_bogo_free_product( $item ) && $coupon_code !== $item['free_gift_coupon'] ){
						$free_item_price = $item['data']->get_price();
					}
					$total_cart_amount += ( $free_item_price * $item['quantity'] );
				}
				continue;
			}

			// Enabled exclude for coupons in product edit page.
			if ( 'yes' === get_post_meta( $item['product_id'], '_wt_disabled_for_coupons', true ) ) {
				continue;
			}

			if ( ! $no_restriction && ! self::is_coupon_applicable_product( $item, $args ) ) {
				$total_cart_amount += $item['data']->get_price() * $item['quantity'];
				continue;
			}

			$item_qty     = $item['quantity'];
			$item_price   = $item['data']->get_price();
			$item_price   = self::alter_price_for_validation_check( $item_price, $item, $coupon_id );
			$_item_amount = $item_price * $item_qty;

			if( $is_bxgx && isset( $cheap_exp_session[ $item['key'] ]['discounted_qty'] ) && isset( $cheap_exp_session[ $item['key'] ]['discount'] ) ){
				$_item_amount = ( $item_price * $item_qty ) - $cheap_exp_session[ $item['key'] ]['discount'];
				$item_qty -= $cheap_exp_session[ $item['key'] ]['discounted_qty'];
			}

			
			$total_cart_amount += $_item_amount;

			if ( $is_same_product_in_cart ) {
				if ( ! $same_product_applicable_found ) {
					$same_product_applicable_found = self::is_product_applicable_for_giveaway_same_in_cart( $coupon_id, $item );
				}

				if ( $is_custom && ! $same_product_custom_applicable_found ) {
					$free_product_qty = $is_spends
						? self::get_free_product_qty_by_range_for_custom( $_item_amount, $coupon_id, true )
						: self::get_free_product_qty_by_range_for_custom( $item_qty, $coupon_id );

					$same_product_custom_applicable_found = 0 !== $free_product_qty;
				}
			}

			$cart_amount += $_item_amount;
			$cart_qty    += $item_qty;

			// Min each qty check.
			if ( $item_qty < $wbte_sc_min_each_qty ) {
				throw new Exception( sprintf( esc_html__( 'The minimum quantity of each product for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro' ), $wbte_sc_min_each_qty ), 113 );
			}

			// Max each qty check.
			if ( ! $is_custom && ! empty( $wbte_sc_max_each_qty ) && $item_qty > $wbte_sc_max_each_qty ) {
				throw new Exception( sprintf( esc_html__( 'The maximum quantity of each product for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro' ), $wbte_sc_max_each_qty ), 114 );
			}
		}

		if ( $is_same_product_in_cart && ! $same_product_applicable_found ) {
			throw new Exception( esc_html__( 'Coupon is not valid.', 'wt-smart-coupons-for-woocommerce-pro' ), 109 );
		}

		$store_credit_total = 0;
		$applied_coupons = WC()->cart->get_applied_coupons();
		foreach( $applied_coupons as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );
			if( method_exists( $coupon, 'is_type' ) && $coupon->is_type( 'store_credit' ) ) {
				$store_credit_total += WC()->cart->get_coupon_discount_amount( $coupon_code );
			}
		}

		$blnc_after_store_credit = $total_cart_amount - $cart_amount - $store_credit_total;
		if( $blnc_after_store_credit < 0 ){
			$cart_amount -= absint( $blnc_after_store_credit );
		}
		
		$total_cart_amount -= $store_credit_total;
		if( $no_restriction ){
			$cart_amount = $total_cart_amount;
		}

		// Coupon is invalid if not in valid range in apply repeat custom mode.
		if ( $is_custom ) {

			if ( ! $same_product_custom_applicable_found ) {
				$free_product_qty = ! $is_spends
				? self::get_free_product_qty_by_range_for_custom( $cart_qty, $coupon_id )
				: self::get_free_product_qty_by_range_for_custom( $cart_amount, $coupon_id, true );

				if ( 0 === $free_product_qty ) {
					throw new Exception( esc_html__( 'The cart does not meet the required conditions for the coupon.', 'wt-smart-coupons-for-woocommerce-pro' ) );
				}
			}
		} else {

			if ( ! $is_same_product_in_cart ) {
				// Amount checks.
				self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' ), $cart_amount, 'min', false );
				self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount' ), $cart_amount, 'max', false );

				// Quantity checks.
				self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' ), $cart_qty, 'min', true );
				self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty' ), $cart_qty, 'max', true );
			}
		}
		
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ), $cart_qty, 'min', true );
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ), $cart_qty, 'max', true );

		$adtl_subtotal_from = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_adtl_subtotal_from' );
		if( ! empty( $adtl_subtotal_from ) ){

			$adtl_subtotal_amt = 'entire_cart' === $adtl_subtotal_from ? $total_cart_amount : $cart_amount;

			self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount_adtl' ), $adtl_subtotal_amt, 'min', false );
			self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount_adtl' ), $adtl_subtotal_amt, 'max', false );
		}

		return $valid;
	}

	/**
	 * Validate coupon on products, exclude products, categories and exclude categories restrictions.
	 *
	 * @since 3.0.0
	 * @param int   $coupon_id  Coupon ID.
	 * @return bool True if coupon is valid, otherwise false
	 */
	public static function validate_coupon_on_products_categories( $coupon_id ) {

		$specific_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$specific_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return false;
		}

		$discounts_obj     = new WC_Discounts( $cart );
		$items_to_validate = $discounts_obj->get_items_to_validate();

		if ( empty( $specific_products ) && empty( $excluded_products ) && empty( $specific_categories ) && empty( $excluded_categories ) ) {
			return true;
		}

		$wbte_sc_product_cat_condition = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_product_cat_condition' );
		$product_condition             = self::get_coupon_meta_value( $coupon_id, '_wt_product_condition' );
		$category_condition            = self::get_coupon_meta_value( $coupon_id, '_wt_category_condition' );

		$product_condition_valid  = false;
		$category_condition_valid = false;

		// Only product or category condition is set, then consider 'or' condition.
		if (
			(
				( ! empty( $specific_products ) || ! empty( $excluded_products ) )
				&& empty( $specific_categories )
				&& empty( $excluded_categories )
			) || (
				empty( $specific_products )
				&& empty( $excluded_products )
				&& ( ! empty( $specific_categories ) || ! empty( $excluded_categories ) )
			)
		) {
			$wbte_sc_product_cat_condition = 'or';
		}

		// Specific products.
		if ( ! empty( $specific_products ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				// Enabled exclude for coupons in product edit page.
				if ( 'yes' === get_post_meta( $parent_id > 0 ? $parent_id : $product_id, '_wt_disabled_for_coupons', true ) ) {
					continue;
				}

				if ( ( in_array( $product_id, $specific_products, true ) || in_array( $parent_id, $specific_products, true ) ) ) {
					$product_condition_valid = true;
					if ( 'or' === $product_condition ) {
						break;
					}
					$specific_products = array_diff( $specific_products, array( $product_id, $parent_id ) );
				}
			}
			if ( 'and' === $product_condition && ! empty( $specific_products ) ) {
				$product_condition_valid = false;
			}
		}

		// Specific categories.
		if ( ! empty( $specific_categories ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				// Enabled exclude for coupons in product edit page.
				if ( 'yes' === get_post_meta( 0 < $parent_id ? $parent_id : $product_id, '_wt_disabled_for_coupons', true ) ) {
					continue;
				}

				$product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids( 0 < $parent_id ? $parent_id : $product_id );

				if ( 0 < count( array_intersect( $product_cats, $specific_categories ) ) ) {
					$category_condition_valid = true;
					if ( 'or' === $category_condition ) {
						break;
					}
					$specific_categories = array_diff( $specific_categories, array_intersect( $product_cats, $specific_categories ) );
				}
			}
		}
		if ( 'and' === $category_condition && ! empty( $specific_categories ) ) {
			$category_condition_valid = false;
		}

		// Excluded products.
		if ( ! empty( $excluded_products ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				if ( in_array( $product_id, $excluded_products, true ) || in_array( $parent_id, $excluded_products, true ) ) {
					$product_condition_valid = false;
					break;
				} else {
					$product_condition_valid = true;
				}
			}
		}

		// Excluded categories.
		if ( ! empty( $excluded_categories ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				$product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids( 0 < $parent_id ? $parent_id : $product_id );

				if ( 0 < count( array_intersect( $product_cats, $excluded_categories ) ) ) {
					$category_condition_valid = false;
					break;
				} else {
					$category_condition_valid = true;
				}
			}
		}

		if ( 'and' === $wbte_sc_product_cat_condition ) {
			return $product_condition_valid && $category_condition_valid;
		}

		return $product_condition_valid || $category_condition_valid;
	}

	/**
	 * Throw exception if coupon min/max condition is not met.
	 *
	 * @since 3.0.0
	 * @param int|float $condition   Coupon min/max values of amount, quantity or additional qty.
	 * @param int|float $cart_value  Cart amount, quantity or additional qty.
	 * @param string    $type        Type of check (min/max).
	 * @param bool      $is_qty      True if checking quantity, otherwise false.
	 * @throws \Exception            Throws exception if condition is not met, that is coupon is not valid.
	 */
	private static function check_coupon_min_max_condition( $condition, $cart_value, $type, $is_qty ) {
		if ( 0 < $condition && ( ( 'min' === $type && $cart_value < $condition ) || ( 'max' === $type && $cart_value > $condition ) ) ) {

			$msg = sprintf(
				// translators: 1$s is 'minimum' or 'maximum', 2$s is 'quantity' or 'subtotal', 3$s is min/max values of amount, quantity or additional qty.
				__( 'The %s %s of matching products for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro' ),
				'min' === $type ? 'minimum' : 'maximum',
				$is_qty ? 'quantity' : 'subtotal',
				$condition
			);
			throw new Exception( esc_html( $msg ), 112 );
		}
	}

	/**
	 * Display BOGO coupon name instead of code in the cart page coupon section for auto BOGO coupons.
	 *
	 * @since 3.0.0
	 * @param  string    $label  Default coupon label.
	 * @param  WC_Coupon $coupon Coupon object.
	 * @return string            BOGO name if it is auto BOGO coupon, otherwise return the default label.
	 */
	public static function campaign_name_instead_code( $label, $coupon ) {

		if ( self::is_bogo( $coupon->get_id() ) && self::is_auto_bogo( $coupon->get_id() ) ) {
			$label = esc_html__( 'Coupon: ', 'wt-smart-coupons-for-woocommerce-pro' ) . get_post_meta( $coupon->get_id(), 'wbte_sc_bogo_coupon_name', true );
		}
		return $label;
	}

	/**
	 * To get cart object
	 *
	 * @since 3.0.0
	 * @return WC_Cart|null   Return cart object if available, otherwise return null.
	 */
	public static function get_cart_object() {
		if ( Wt_Smart_Coupon_Public::is_admin() ) {
			return null;
		}

		return ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
	}

	/**
	 *  Checks the current cart item is a free item. Or a free item under the given coupon code
	 *
	 *  @since 3.0.0
	 *  @param array  $cart_item   Cart item array.
	 *  @param string $coupon_code Coupon code, default is empty.
	 *  @return bool               Return true if the cart item is a free item, otherwise false.
	 */
	public static function is_a_free_item( $cart_item, $coupon_code = '' ) {
		$out = isset( $cart_item['wbte_sc_free_gift_coupon'] ) && isset( $cart_item['wbte_sc_free_product'] ) && 'wbte_sc_giveaway_product' === $cart_item['wbte_sc_free_product'];

		if ( '' !== $coupon_code && $out ) {
			$out = wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ) === wc_format_coupon_code( $coupon_code );
		}

		$out = apply_filters( 'wt_sc_alter_is_free_cart_item', $out, $cart_item, $coupon_code ); /* other plugins to confirm their giveaway item */
		return $out;
	}

	/**
	 *  Checks the current cart item is a old bogo free item. Or a free item under the given coupon code
	 *
	 *  @since 3.0.0
	 *  @param array  $cart_item   Cart item array.
	 *  @param string $coupon_code Coupon code, default is empty.
	 *  @return bool               Return true if the cart item is a old bogo free item, otherwise false.
	 */
	private static function is_old_bogo_free_product( $cart_item, $coupon_code = '' ) {

		$out = false;

		if ( class_exists( 'Wt_Smart_Coupon_Giveaway_Product_Public' ) && method_exists( 'Wt_Smart_Coupon_Giveaway_Product_Public', 'is_a_free_item' ) ) {
			$out = Wt_Smart_Coupon_Giveaway_Product_Public::is_a_free_item( $cart_item, $coupon_code ); // For giving compatibility with normal bogo.
		}

		return $out;
	}

	/**
	 *  Get all giveaway product ids for cart operations.
	 *
	 *  @since  3.0.0
	 *  @param  int $post_id   Id of coupon.
	 *  @return array            Array of giveaway product ids. Product ids will be updated to current language product ids if multi language plugin(WPML) is active
	 */
	public static function get_giveaway_products( $post_id ) {
		$free_products          = parent::get_giveaway_products( $post_id );
		$free_products_original = $free_products; // assumes main language product id.

		$multi_lang_obj = Wt_Smart_Coupon_Mulitlanguage::get_instance();

		if ( $multi_lang_obj->is_multilanguage_plugin_active() ) {
			$out = array();

			foreach ( $free_products as $product_id ) {
				/**
				 *  Take id of product in the current language.
				 *
				 *  @param  $product_id       Id of product.
				 *  @param  string posttype   Post type of the product. Default: product.
				 *  @param  bool              Return original if no translation found in the current language. Default: false
				 */
				$out[] = apply_filters( 'wpml_object_id', $product_id, 'product', true );
			}

			$free_products = $out;
		}
		$free_products = array_map( 'intval', $free_products );
		/**
		 *  Alter BOGO product ids for cart (Only applicable for frontend functionalities)
		 *
		 *  @param  $free_products              int[]       Array of giveaway product ids. Product ids of this array was converted to current language ids if any multi lang plugin(WPML) exists.
		 *  @param  $post_id                    int         Id of coupon.
		 *  @param  $free_products_original     int[]       Array of giveaway product ids. Here the product ids are the ids configured by admin from backend.
		 */
		return apply_filters( 'wt_sc_alter_bogo_giveaway_product_ids_for_cart', $free_products, $post_id, $free_products_original );
	}

	/**
	 * Add giveaway products to the cart when the coupon is applied.
	 * For BOGO type bxgx.
	 *
	 * @since 3.0.0
	 * @param string $coupon_code Coupon code.
	 */
	public function add_free_product_into_cart( $coupon_code ) {

		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

		if ( ! $coupon_id || ! self::is_bogo( $coupon_id ) || ! self::is_bxgx( $coupon_id ) ) {
			return;
		}

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return;
		}

		$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

		if ( 'same_product_in_the_cart' === $bogo_customer_gets ) {
			$this->process_same_product_in_cart_giveaway( $coupon_id );
		} elseif ( 'any_product_from_store' === $bogo_customer_gets ) {
			self::process_any_product_from_store_giveaway( $coupon_id );
		} elseif ( 'any_product_from_category' === $bogo_customer_gets ) {
			self::process_any_product_from_category( $coupon_id );
		} elseif ( 'specific_product' === $bogo_customer_gets ) {
			$this->process_specific_product_giveaway( $coupon_id );
		}

		// Added product get removed when 'any_product_from_category' or 'any_product_from_store' coupons are auto-applied (added product change to free product which leads to failing coupon restriction).
		remove_action( 'woocommerce_add_to_cart', array( $this, 'add_giveaway_on_add_to_cart' ), 11 );

		// Show giveaway eligible message on checkout page.
		if( wc_get_checkout_url() === wp_get_referer() ){
            self::show_giveaway_eligible_message();
        }
		
	}

	/**
	 *  Is automatically add giveaway products to cart.
	 *  Applicable for `specific_product` and `same_product_in_the_cart`.
	 *
	 *  @param  int    $coupon_id              Id of coupon.
	 *  @param  string $coupon_code            Coupon code.
	 *  @param  array  $free_products          Available free product ids.
	 *  @return bool                           Is auto add or not.
	 */
	private static function is_auto_add_giveaway( $coupon_id, $coupon_code, $free_products ) {

		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		/**
		 *  Only applicable for `specific_product` and `same_product_in_the_cart`
		 */
		if ( 'specific_product' !== $customer_gets ) {
			return false;
		}

		$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );

		/**
		 *  `or(any)` condition.
		 */
		if ( 'any' === $bogo_product_condition ) {
			if( 1 === count( $free_products ) ) {
				if ( self::is_auto_add_product( $free_products[0], $coupon_id ) ) {
					return true;
				}
			}

			return false;
		}

		foreach ( $free_products as $free_product_id ) {
			if ( ! self::is_auto_add_product( $free_product_id, $coupon_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 *  Checks the product purchasable or not.
	 *  If varaible product, checks any of the variation is purchasable, and returns the variation id if successfull, otherwise false will return
	 *
	 *  @since 3.0.0
	 *  @param  Wc_Product $_product  Product object.
	 *  @return bool|int               Return false if not purchasable, otherwise return variation id if successfull
	 */
	public static function is_purchasable( $_product ) {
		if ( is_int( $_product ) ) {
			$_product = wc_get_product( $_product );
		}

		if ( ! $_product ) {
			return false;
		}

		if ( $_product->is_type( 'variable' ) ) {
			$variations = $_product->get_available_variations();

			if ( empty( $variations ) ) {
				return false;
			}

			foreach ( $variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );

				if ( self::is_purchasable( $variation_product ) ) {
					return $variation['variation_id'];
				}
			}

			return false;
		}

		if ( ! $_product->has_enough_stock( 1 ) ) {
			if ( 0 === $_product->get_stock_quantity() ) {
				return false;
			}
		}

		return $_product->is_purchasable();
	}

	/**
	 *  Giveaway add to cart function
	 *
	 *  @since 3.0.0
	 *  @param  int    $item_id        Product/variation id.
	 *  @param  int    $quantity       Quantity.
	 *  @param  string $coupon_code    Coupon code.
	 *  @param  array  $args           Extra args [Optional].
	 *  @return bool|string            Return cart_item_key if successfull, otherwise false
	 */
	private function add_item_to_cart( $item_id, $quantity, $coupon_code, $args = array() ) {
		$product = wc_get_product( $item_id );
		if ( $product ) {
			if ( ! self::is_purchasable( $product ) ) {
				return false;
			}
			if ( 'variable' === $product->get_type() ) {
				return false; /* not possible to add variable parent  */
			}

			if ( ! $product->has_enough_stock( $quantity ) ) {
				$quantity = $product->get_stock_quantity();
				if ( 0 === $quantity ) {
					return false;
				}
			}

			$variation_id = 0;
			$product_id   = $item_id;
			$variation    = $args['variation_attributes'] ?? array();

			if ( $product && 'variation' === $product->get_type() ) {
				$variation_id = $product_id;
				$product_id   = $product->get_parent_id();

				if ( empty( $variation ) ) {
					$variation = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array(), 'text_arr' );
					$variation = empty( $variation ) ? array() : $variation;
				}

				if ( empty( $variation ) ) {
					$variation_attributes = $product->get_variation_attributes();

					foreach ( $variation_attributes as $key => $value ) {
						if ( empty( $value ) ) {
							$variation[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
						}
					}
				}

				foreach ( $variation as $attribute_name => $options ) {
					if ( '' === $options ) {
						return false;
					}
				}
			}

			$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
			$discount  = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );

			$cart_item_data = array(
				'wbte_sc_free_product'     => 'wbte_sc_giveaway_product',
				'wbte_sc_free_gift_coupon' => wc_format_coupon_code( $coupon_code ),
				'wbte_sc_bogo_discount'    => $discount,
			);

			if ( isset( $args['_wbte_sc_giveaway_trigger_product'] ) && ! empty( $args['_wbte_sc_giveaway_trigger_product'] ) ) {
				$cart_item_data['_wbte_sc_giveaway_trigger_product'] = $args['_wbte_sc_giveaway_trigger_product'];
			}

			// Extra cart item data.
			if ( isset( $args['cart_item_data'] ) && is_array( $args['cart_item_data'] ) ) {
				$cart_item_data = array_merge( $cart_item_data, $args['cart_item_data'] );
			}

			$old_cart_item_data = $args['old_cart_item_data'] ?? array();
			$cart_item_data     = apply_filters( 'wt_sc_alter_giveaway_cart_item_data_before_add_to_cart', $cart_item_data, $product_id, $variation_id, $quantity, $old_cart_item_data );

			remove_action( 'woocommerce_add_to_cart', array( $this, 'add_giveaway_on_add_to_cart' ), 11 );
			return WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
		}
		return false;
	}

	/**
	 *  Show altered cart item price for giveaway item.
	 *
	 *  @since 3.0.0
	 *  @param  string $price       Cart item price HTML.
	 *  @param  array  $cart_item   Cart item array.
	 *  @return string              Altered cart item price HTML.
	 */
	public static function alter_cart_item_price( $price, $cart_item ) {
		$out = $price;
		if ( self::is_a_free_item( $cart_item ) ) {

			$discount_data = self::calculate_bogo_discount( $cart_item );
			$item_price    = $discount_data['product_price'];
			$discount      = $discount_data['discount'];

			if ( ( $item_price - $discount ) >= $item_price ) {
				return $out;
			}

			$out = '<span>' . wp_kses_post( wc_price( $item_price ) ) . '</span> <br /> <span class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'wt-smart-coupons-for-woocommerce-pro' ) . wp_kses_post( wc_price( $item_price - $discount ) ) . '</span>';
		}

		return $out;
	}

	/**
	 * Calculate the discount for giveaway item.
	 * For bxgx BOGO.
	 *
	 * @since 3.1.0  Moved to separate function.
	 * @param  array $cart_item  Cart item.
	 * @return array             Discount data.
	 */
	public static function calculate_bogo_discount( $cart_item ) {
		$out = array(
			'product_price' => 0,
			'discount'      => 0,
		);

		if ( self::is_a_free_item( $cart_item ) ) {

			$coupon_code = isset( $cart_item['wbte_sc_free_gift_coupon'] ) ? wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ) : '';
			$coupon_id   = wc_get_coupon_id_by_code( $coupon_code );
			if ( $coupon_id && self::is_bxgx( $coupon_id ) ) {
				$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product    = wc_get_product( $item_id );
				$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );
				$item_price = self::get_product_price( $product );

				$qty         = $cart_item['quantity'] ?? 1;
				$item_price *= $qty;
				$discount   *= $qty;

				if ( ! isset( self::$bogo_discounts[ $coupon_code ] ) ) {
					self::$bogo_discounts[ $coupon_code ] = $discount;
				} else {
					self::$bogo_discounts[ $coupon_code ] += $discount;
				}

				if ( ( $item_price - $discount ) >= $item_price ) {
					return $out;
				}

				$out = array(
					'product_price' => $item_price,
					'discount'      => $discount,
				);
			}
		}

		return $out;
	}

	/**
	 *  This function will hook a callback function to show giveaway products in the cart page
	 *
	 *  @since 3.0.0
	 */
	public function set_hook_to_show_giveaway_products() {
		add_action( 'woocommerce_after_cart_table', array( $this, 'display_giveaway_products' ), 1 );
	}

	/**
	 * Callback function for displaying giveaway products in the cart page.
	 *
	 * @since 3.0.0
	 */
	public static function display_giveaway_products() {
		$applied_coupons = WC()->cart->applied_coupons;
		if ( empty( $applied_coupons ) ) {
			return;
		}

		$free_products      = array();
		$free_products_qty  = array();
		$triggered_item_arr = array();
		$qty_alter_option   = array();
		foreach ( $applied_coupons as $coupon_code ) {
			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );
			if ( ! $coupon ) {
				continue;
			}

			$coupon_id = $coupon->get_id();

			if ( self::is_bogo( $coupon_id ) ) {
				$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

				if ( 'specific_product' === $bogo_customer_gets ) {
					$bogo_eligible_qty      = self::get_bogo_eligible_qty( $coupon_id );
					$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );

					$bogo_products = self::get_giveaway_products( $coupon_id );

					$bogo_products = self::unset_no_discount_product_from_free_products( $bogo_products, $coupon_id );

					if ( self::is_user_can_change_free_product_qty( $coupon_id ) ) {

						$qty_alter_option[ $coupon_code ] = true;

						if ( 'all' === $bogo_product_condition ) {
							$bogo_eligible_qty *= count( $bogo_products );
						}

						$bogo_eligible_qty = self::alter_giveaway_eligible_qty_based_on_cart( $coupon_code, $bogo_eligible_qty );

						if ( 0 >= $bogo_eligible_qty ) {
							$bogo_products = array();
						}
					} else {

						$qty_alter_option[ $coupon_code ] = false;

						$bogo_products = self::alter_free_products_display_arr( $coupon_code, $bogo_products, $bogo_customer_gets, array( 'product_condition' => $bogo_product_condition ) );
					}

					foreach ( $bogo_products as $product_id ) {
						$free_products_qty[ $coupon_code ][ $product_id ] = $bogo_eligible_qty;
					}
					$free_products[ $coupon_code ] = $bogo_products;
				} elseif ( 'same_product_in_the_cart' === $bogo_customer_gets ) {

					$_free_prod_qty_arr  = array();
					$_triggered_item_arr = array();

					// $_free_prod_qty_arr and $_triggered_item_arr are reference arguments for the below function.
					$coupon_products = self::get_cart_coupon_applicable_products_same_in_cart( $coupon_id, $_free_prod_qty_arr, $_triggered_item_arr );

					$coupon_products = self::alter_free_products_display_arr( $coupon_code, $coupon_products, $bogo_customer_gets, array( 'same_in_cart_free_prod_qty' => $_free_prod_qty_arr ) );

					$free_products[ $coupon_code ]      = $coupon_products;
					$free_products_qty[ $coupon_code ]  = $_free_prod_qty_arr;
					$triggered_item_arr[ $coupon_code ] = $_triggered_item_arr;

					$qty_alter_option[ $coupon_code ] = self::is_user_can_change_free_product_qty( $coupon_id );
				}
			}
		}

		if ( empty( $free_products ) ) {
			return;
		}

		include_once plugin_dir_path( __FILE__ ) . 'views/-cart-giveaway-products.php';
	}

	/**
	 * To display free products choosing box in cart page.
	 * Applicable for 'specific_product' and 'same_product_in_the_cart' BOGO types.
	 * This function will be called on hook 'template_redirect'.
	 * For BOGO type bxgx.
	 *
	 * @since 3.0.0
	 */
	public function add_giveaway_products_with_coupon() {
		$cart = self::get_cart_object();

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$coupons = $cart->get_applied_coupons();
		$coupons = ! is_array( $coupons ) ? array() : $coupons;

		foreach ( $coupons as $coupon_code ) {

			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );

			$coupon_id = $coupon->get_id();

			if ( ! $coupon_id || ! self::is_bogo( $coupon_id ) || ! self::is_bxgx( $coupon_id ) ) {
				continue;
			}

			$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

			if ( 'specific_product' === $bogo_customer_gets ) {
				$this->set_hook_to_show_specific_product_giveaway( $coupon_id, $coupon_code );
			} elseif ( 'same_product_in_the_cart' === $bogo_customer_gets ) {
				$this->set_hook_to_show_giveaway_products();
			}
		}
	}

	/**
	 *  Removes any free products from the cart if their related coupon is not present in the cart
	 *
	 *  @since 3.0.0
	 */
	public static function check_any_free_products_without_coupon() {
		$cart = self::get_cart_object();

		if ( ! is_null( $cart ) && is_object( $cart ) && is_callable( array( $cart, 'is_empty' ) ) && ! $cart->is_empty() ) {
			$coupons    = $cart->get_applied_coupons();
			$cart_items = $cart->get_cart();
			$cart_items = ( isset( $cart_items ) && is_array( $cart_items ) ) ? $cart_items : array();
			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item ) ) {
					if ( ! in_array( wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ), $coupons, true ) ) {
						$cart->remove_cart_item( $cart_item_key ); /* remove the free item */
						unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
					}
				}
			}
		}
	}

	/**
	 *  This function will decide whether to show or add to cart get the giveaway items
	 *  For BOGO specific products
	 *
	 *  @since 3.0.0
	 *  @param      int    $coupon_id          ID of coupon.
	 *  @param      string $coupon_code        Coupon code.
	 */
	public function set_hook_to_show_specific_product_giveaway( $coupon_id, $coupon_code ) {
		$free_products = self::get_giveaway_products( $coupon_id );

		if ( ! empty( $free_products ) ) {
			$this->set_hook_to_show_giveaway_products();
		}
	}

	/**
	 * Specific product BOGO functionality.
	 *
	 * @since 3.0.0
	 * @param int  $coupon_id    Coupon ID.
	 * @param bool $when_applied If true, it is called when coupon applied, otherwise called when qty updated.
	 */
	public function process_specific_product_giveaway( $coupon_id, $when_applied = true ) {

		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}
		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		if ( 'specific_product' !== $customer_gets ) {
			return;
		}

		$coupon_code   = wc_get_coupon_code_by_id( $coupon_id );
		$free_products = self::get_giveaway_products( $coupon_id );
		$free_products = self::unset_no_discount_product_from_free_products( $free_products, $coupon_id );
		if ( ! empty( $free_products ) ) {
			$free_products_qty = self::get_bogo_eligible_qty( $coupon_id );
			$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );
			if ( $when_applied ) {
				$item_added = false;
				if ( self::is_auto_add_giveaway( $coupon_id, $coupon_code, $free_products ) ) {
					foreach ( $free_products as $item_id ) {
						$_product   = wc_get_product( $item_id );
						$item_price = self::get_product_price( $_product );
						$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );
						
						// If auto add only full discount is enabled and discount is not 100% discount, then skip adding the product.
						if ( self::is_auto_add_only_full_discount() && $discount  !== $item_price ) {
							continue;
						}
						$item_added = $this->add_item_to_cart( $item_id, $free_products_qty, $coupon_code );
					}
				} else {
					if ( 'all' === $bogo_product_condition ) {
						foreach ( $free_products as $item_id ) {
							$_product   = wc_get_product( $item_id );
							$item_price = self::get_product_price( wc_get_product( $item_id ) );
							$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );

							if ( self::is_auto_add_product( $item_id, $coupon_id ) && ! ( self::is_auto_add_only_full_discount() && ! self::is_full_giveaway( $coupon_id, $item_id ) ) ) {
								$item_added = $this->add_item_to_cart( $item_id, $free_products_qty, $coupon_code );
							}
						}
					}
				}

				if ( $item_added ) {
					self::show_product_added_msg( $coupon_id );
				}
			} else {

				if ( ! self::is_user_can_change_free_product_qty( $coupon_id )  ) {
					$old_giveaway_qty = self::get_coupon_giveaway_count_in_cart( $coupon_code );
					if ( $old_giveaway_qty !== $free_products_qty ) {
						self::update_giveaway_cart_qty( $coupon_code, $free_products_qty );
						return;
					}
				}
				$free_prod_qty_in_cart = self::get_coupon_giveaway_count_in_cart( $coupon_code, true );
				if ( 'all' === $bogo_product_condition || 1 === count( $free_products ) ){
					$free_products_qty *= count( $free_products );

					$cart_items     = $cart->get_cart();
					if( $free_products_qty < $free_prod_qty_in_cart ){ //giveaway qty reduced, so remove the free items from cart.
						self::reduce_free_product_qty( $coupon_code, $free_prod_qty_in_cart - $free_products_qty );
						return;
					}
					foreach ( $cart_items as $cart_item_key => $cart_item ) {
						if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
							$free_qty_ratio =  $cart_item['quantity'] / $free_prod_qty_in_cart;
							$free_qty_to_add = absint( $free_products_qty * $free_qty_ratio );
							if( 0 < $cart_item['variation_id'] && $free_qty_to_add > $cart_item['quantity'] ){ //If variation product and qty increased, then skip auto add.
								continue;
							}
							if ( $cart->get_cart_item( $cart_item_key ) ) {
								$cart->set_quantity( $cart_item_key, $free_qty_to_add );
							}
						}
					}
				}
				else if( 'any' === $bogo_product_condition && $free_products_qty <  $free_prod_qty_in_cart ){
					self::reduce_free_product_qty( $coupon_code, $free_prod_qty_in_cart - $free_products_qty );
				}
			}
		}
	}

	/**
	 *  Ajax action function for getting variation id
	 *
	 *  @since 3.0.0
	 */
	public static function ajax_find_matching_product_variation_id() {
		$out = array(
			'status'     => false,
			'status_msg' => __( 'Invalid request', 'wt-smart-coupons-for-woocommerce-pro' ),
		);

		if ( check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce', false ) ) {
			if ( isset( $_POST['attributes'] ) && isset( $_POST['product'] ) ) {
				$product_id = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['product'] ) ? wp_unslash( $_POST['product'] ) : '', 'int' );
				$attributes = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array(), 'text_arr' );
				if ( '' !== $product_id && ! empty( $attributes ) ) {
					$variation_id = self::find_matching_product_variation_id( $product_id, $attributes );
					$_product     = wc_get_product( $variation_id );

					$image   = $_product ? wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' ) : false;
					$img_url = '';
					if ( $image && is_array( $image ) && isset( $image[0] ) ) {
						$img_url = $image[0];
					}

					if ( self::is_purchasable( $_product ) ) {
						$out = array(
							'variation_id' => $variation_id,
							'status'       => true,
							'status_msg'   => __( 'Success', 'wt-smart-coupons-for-woocommerce-pro' ),
							'img_url'      => $img_url,
						);
					} else {
						$out['status_msg'] = self::get_customized_text( 'non_purchasable_giveaway_varaition' );
					}
				}
			}
		}

		echo wp_json_encode( $out );
		wp_die();
	}

	/**
	 * Function for getting variation id from product and selected attributes
	 *
	 * @param int   $product_id Given Product Id.
	 * @param array $attributes Attribute values ad key value pair.
	 * @since 3.0.0
	 */
	public static function find_matching_product_variation_id( $product_id, $attributes ) {
		return ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation(
			new \WC_Product( $product_id ),
			$attributes
		);
	}

	/**
	 *  Ajax action function for adding Giveaway products into cart.
	 *
	 *  @since 3.0.0
	 */
	public function add_free_product_to_cart() {
		check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce' );

		$coupon_id            = isset( $_POST['coupon_id'] ) ? absint( $_POST['coupon_id'] ) : 0;
		$product_id           = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id         = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$variation_attributes = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array(), 'text_arr' );
		$free_quantity        = isset( $_POST['free_qty'] ) ? absint( $_POST['free_qty'] ) : self::get_bogo_eligible_qty( $coupon_id );
		$coupon               = new WC_Coupon( $coupon_id );
		$coupon_code          = wc_format_coupon_code( $coupon->get_code() );

		$item_added = false;

		if ( 0 === $coupon_id ) {
			self::set_add_to_cart_messages( 'coupon_id_missing' );
			wp_die();
		} else if ( 0 === $product_id ) {
			self::set_add_to_cart_messages( 'product_id_missing', array( 'coupon_id' => $coupon_id ) );
			wp_die();
		} else {
			$args = array(
				'variation_attributes' => $variation_attributes,
				'variation_id'         => $variation_id,
			);
			if ( isset( $_POST['triggered_item_key'] ) ) {
				$args['_wbte_sc_giveaway_trigger_product'] = sanitize_text_field( wp_unslash( $_POST['triggered_item_key'] ) );
			}

			$item_id    = $variation_id > 0 ? $variation_id : $product_id;
			$item_added = $this->add_item_to_cart( $item_id, $free_quantity, $coupon_code, $args );
		}

		if ( $item_added ) {
			self::show_product_added_msg( $coupon_id );
		}

		$notices = wc_get_notices( 'error' );
		if ( count( $notices ) > 0 ) {
			$last_error = end( $notices );
			if ( isset( $last_error['notice'] ) ) {
				echo '<ul class="woocommerce-error" role="alert">
                        <li>' . wp_kses_post( $last_error['notice'] ) . '</li>
                </ul>';
				wc_clear_notices(); /* to avoid notice printing on page refresh */
				wp_die();
			}
		} else {
			echo true; /* no translation required */
			wp_die();
		}
	}

	/**
	 *  Error/Validation messages when giveaway products are adding to cart.
	 *
	 *  @since 3.0.0
	 *  @param string $reason reason string.
	 *  @param array  $extra_args extra arguments to process the message.
	 *  @param string $coupon_type coupon type.
	 */
	public static function set_add_to_cart_messages( $reason, $extra_args = array(), $coupon_type = null ) {
		$out = __( "Oops! It seems like you've made an invalid request. Please try again.", 'wt-smart-coupons-for-woocommerce-pro' );

		$msg = apply_filters( 'wt_sc_alter_giveaway_addtocart_messages', $out, $reason, $extra_args, $coupon_type );

		if ( '' !== $msg ) {
			wc_add_notice( $msg, 'error' );
			wc_print_notices();
		}
	}

	/**
	 * To alter the free products array based on the cart items.
	 * This array is used to display the free products in the cart page for BOGO type 'specific_product' and 'same_product_in_the_cart'.
	 *
	 * @since 3.0.0
	 * @param string $coupon_code   Coupon code.
	 * @param mixed  $bogo_products  BOGO products array.
	 * @param string $customer_gets Customer gets, specific_product or same_product_in_the_cart.
	 * @param array  $args           Addition arguments.
	 * @return array                Updated free products array
	 */
	public static function alter_free_products_display_arr( $coupon_code, $bogo_products, $customer_gets, $args = array() ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $bogo_products;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$item_id = self::prepare_item_id_for_free_products( $cart_item, $bogo_products );

				if ( in_array( $item_id, $bogo_products, true ) ) {

					if ( 'specific_product' === $customer_gets ) {
						$bogo_product_condition = $args['product_condition'] ?? 'all';
						if ( 'all' === $bogo_product_condition ) {
							unset( $bogo_products[ array_search( $item_id, $bogo_products, true ) ] );
						} else {
							$bogo_products = array(); // Reset $bogo_products to an empty array.
							break; // Exit the loop since we don't need to check other items.
						}
					} elseif ( 'same_product_in_the_cart' === $customer_gets ) {
						if ( isset( $args['same_in_cart_free_prod_qty'] ) && is_array( $args['same_in_cart_free_prod_qty'] ) ) {
							$_free_prod_qty = $args['same_in_cart_free_prod_qty'];
							if ( isset( $_free_prod_qty[ $item_id ] ) && 1 > $_free_prod_qty[ $item_id ] ) {
								unset( $bogo_products[ array_search( $item_id, $bogo_products, true ) ] );
							}
						} else {
							unset( $bogo_products[ array_search( $item_id, $bogo_products, true ) ] );
						}
					}
				}
			}
		}
		return $bogo_products;
	}

	/**
	 * Reduce the BOGO eligible quantity based on the cart free items.
	 *
	 * @since 3.0.0
	 * @param  string $coupon_code          Coupon code.
	 * @param  int    $bogo_eligible_qty    BOGO eligible quantity.
	 * @return int                      Updated BOGO eligible quantity
	 */
	public static function alter_giveaway_eligible_qty_based_on_cart( $coupon_code, $bogo_eligible_qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $bogo_eligible_qty;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$bogo_eligible_qty -= $cart_item['quantity'];
			}
		}
		return $bogo_eligible_qty;
	}

	/**
	 * If product not have any discount, remove from the free product list
	 * eg scenario: discount type is final price and final price is greater than product price
	 *
	 * @since 3.0.0
	 * @param array $free_products  Array of free products id.
	 * @param int   $coupon_id      Coupon id.
	 * @return      array           Updated free products array
	 */
	private static function unset_no_discount_product_from_free_products( $free_products, $coupon_id ) {
		foreach ( $free_products as $key => $product_id ) {
			$_product   = wc_get_product( $product_id );
			$item_price = self::get_product_price( $_product );
			$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );
			if ( ( $item_price - $discount ) >= $item_price ) {
				unset( $free_products[ $key ] );
			}
		}
		return $free_products;
	}

	/**
	 *  Take the giveaway item id based on BOGO configuration.
	 *
	 *  @since  3.0.0
	 *  @param  array $cart_item          Cart item array.
	 *  @param  array $bogo_products      BOGO product array.
	 *  @return int         Item id, variation id when variation is configured as BOGO product, otherwise product id
	 */
	private static function prepare_item_id_for_free_products( $cart_item, $bogo_products ) {
		$item_id = 0;

		if ( 0 < $cart_item['variation_id'] && in_array( $cart_item['variation_id'], $bogo_products, true ) ) {
			$item_id = $cart_item['variation_id'];

		} elseif ( in_array( $cart_item['product_id'], $bogo_products, true ) ) {
			$item_id = $cart_item['product_id'];
		}

		return $item_id;
	}

	/**
	 * To check whether the product is able to be added automatically to the cart.
	 *
	 * @since 3.0.0
	 * @param int $product_id  Product id.
	 * @param int $coupon_id   Coupon id.
	 * @return bool            True if product is able to be added automatically to the cart, otherwise false.
	 */
	public static function is_auto_add_product( $product_id, $coupon_id ) {

		$free_product = wc_get_product( $product_id );
		if ( ! self::is_purchasable( $free_product ) ) {
			return false;
		}

		/**
		 *  `specific_product` BOGO
		 */
		if ( 'variable' === $free_product->get_type() ) {
			return false;
		}

		/**
		 *  Alter the product types for auto add.
		 *
		 *  @param string[]     Product types. Default: array( `simple`, `variation` ).
		 *  @param string       Coupon code.
		 */
		$alter_allowed_product_types_for_auto_add = (array) apply_filters( 'wbte_sc_alter_allowed_product_types_for_auto_add', array( 'simple', 'variation' ), $coupon_id );

		/**
		 *  Variation product in `same_product_in_the_cart` BOGO
		 */
		if ( ! in_array( $free_product->get_type(), $alter_allowed_product_types_for_auto_add, true ) ) {
			return false;
		}

		/**
		 *  Variation product in `specific_product` non without attributes
		 */
		if ( 'variation' === $free_product->get_type() ) {
			foreach ( $free_product->get_variation_attributes() as $attribute_name => $options ) {
				if ( '' === $options ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Action function for displaying description for Giveaway product on cart page
	 *
	 *  @since  3.0.0
	 *  @param  array $cart_item    Cart item array.
	 */
	public static function display_giveaway_product_description( $cart_item ) {

		if ( self::is_a_free_item( $cart_item ) ) { // This is a free item.

			echo wp_kses_post( self::get_product_under_msg( $cart_item ) );
		}
	}

	/**
	 * To get the message to display under the free gift product.
	 * User can customize the message from the BOGO general settings. If msg contains {bogo_title} then it will be replaced with the BOGO coupon title.
	 *
	 * @since   3.0.0
	 * @param   array  $cart_item   Cart line item data.
	 * @param   string $coupon_code Coupon code.
	 * @return  string              Message to display under the free gift product.
	 */
	private static function get_product_under_msg( $cart_item, $coupon_code = '' ) {

		if ( empty( $cart_item ) ) {
			return '';
		}

		$info_text = self::get_general_settings_value( 'wbte_sc_bogo_general_discount_under_product_msg' );
		$coupon_id = wc_get_coupon_id_by_code( $cart_item['wbte_sc_free_gift_coupon'] ?? $coupon_code );

		$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );

		$info_text = str_replace( '{bogo_title}', $bogo_title, $info_text );

		return apply_filters( 'wt_sc_alter_giveaway_cart_lineitem_text', '<p class="wbte_sc_bogo_msg_under_free_gift">' . $info_text . '</p>', $cart_item );
	}

	/**
	 * Remove free products from cart when coupon removed.
	 *
	 * @since 3.0.0
	 * @param string $coupon_code Coupon code.
	 */
	public static function remove_free_product_from_cart( $coupon_code ) {

		$cart            = WC()->cart;
		$applied_coupons = $cart->get_applied_coupons();
		if ( isset( $coupon_code ) && ! empty( $coupon_code ) && ! in_array( $coupon_code, $applied_coupons, true ) ) {
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
					$cart->remove_cart_item( $cart_item_key );
					unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
				}
			}
		}
	}

	/**
	 *  Show the giveaway discount on cart summary section.
	 *
	 *  @since 3.0.0
	 *
	 *  @param  string    $discount_amount_html     Coupon Discount HTML.
	 *  @param  WC_Coupon $coupon                   Coupon object.
	 *  @return string                              Coupon Discount HTML
	 */
	public static function alter_coupon_discount_amount_html( $discount_amount_html, $coupon ) {
		$cart = self::get_cart_object();

		if ( ! is_null( $cart ) && self::is_bogo( $coupon->get_id() ) && ! self::is_apply_tax_on_discounted_price() ) {

			if ( empty( self::$bogo_discounts ) ) {
				foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
					self::calculate_bogo_discount( $cart_item );
				}
			}

			$coupon_code          = wc_format_coupon_code( $coupon->get_code() );
			$discount             = self::$bogo_discounts[ $coupon_code ] ?? 0;
			$discount_amount_html = '-'.wc_price( wc_cart_round_discount( $discount, wc_get_price_decimals() ) );
		}

		return $discount_amount_html;
	}

	/**
	 * Calculate the Cart Total after reducing the free product price.
	 * For BOGO with 'Apply tax on original price' option enabled. For bxgx BOGO.
	 *
	 *  @since 3.0.0.
	 *  @param  object $cart_object Cart object.
	 */
	public static function discounted_calculated_total( $cart_object ) {

		if ( self::is_apply_tax_on_discounted_price() ) {
			return;
		}
		$new_total = $cart_object->get_total( 'edit' );

		if ( self::is_cart_contains_free_products() ) {

			foreach ( $cart_object->get_cart() as $cart_item ) {

				if ( self::is_a_free_item( $cart_item ) ) {
					$coupon_code = $cart_item['wbte_sc_free_gift_coupon'];

					if ( ! empty( $coupon_code ) ) {
						$coupon_code = wc_format_coupon_code( $coupon_code );
						$coupon      = new WC_Coupon( $coupon_code );

						if ( $coupon ) {
							$coupon_id = $coupon->get_id();

							$item_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
							$product = wc_get_product( $item_id );

							$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );
							$new_total -= ( $discount * $cart_item['quantity'] );
						}
					}
				}
			}

			$new_total = round( $new_total, $cart_object->dp );
			$cart_object->set_total( $new_total );
		}
	}

	/**
	 * Check whether cart contains any Giveaway products from given coupon
	 *
	 * @since 3.0.0
	 *
	 * @param   string $coupon_code    Optional, Coupon code.
	 * @return  bool                   True when free product exists otherwise false
	 */
	public static function is_cart_contains_free_products( $coupon_code = '' ) {
		$cart = self::get_cart_object();

		if ( ! is_null( $cart ) ) {
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 *  Exclude the free giveaway products from applying other coupons.
	 *
	 *  @since    3.0.0
	 *  @since    3.1.0  If cheapest/expensive BOGO, return true.
	 *  @param bool       $valid     Is valid or not.
	 *  @param WC_Product $product   Product instance.
	 *  @param WC_Coupon  $coupon    Coupon data.
	 *  @param array      $values    Cart item values.
	 *  @return bool                 If prodct is free item then return false. If cheapest/expensive BOGO, return true. Otherwise return default value.
	 */
	public static function exclude_giveaway_from_other_discounts( $valid, $product, $coupon, $values ) {

		if ( self::is_a_free_item( $values ) && 0 >= $values['data']->get_price() ) {
			return false;
		}

		if ( self::is_bogo( $coupon->get_id() ) ) {
			return true;
		}

		return $valid;
	}

	/**
	 * 'same_product_in_the_cart' BOGO functionality.
	 *
	 * @since 3.0.0
	 * @param int $coupon_id Coupon ID.
	 */
	public function process_same_product_in_cart_giveaway( $coupon_id ) {
		$cart = self::get_cart_object();

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		if ( 'same_product_in_the_cart' !== $customer_gets ) {
			return;
		}
		$free_products                 = array();
		$cart_items                    = $cart->get_cart();
		$coupon_code                   = wc_get_coupon_code_by_id( $coupon_id );
		$free_items_triggered_item_key = self::get_free_items_triggered_item_keys(
			$coupon_code,
			$cart
		);

		$coupon_products            = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$coupon_excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$coupon_categories          = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$coupon_excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		// Assigning $args before for loop to avoid multiple time fetching restriction data.
		$args = array(
			'coupon_products'           => $coupon_products,
			'coupon_categories'         => $coupon_categories,
			'coupon_exclude_products'   => $coupon_excluded_products,
			'coupon_exclude_categories' => $coupon_excluded_categories,
			'on_sale_non_sale'          => self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' )
		);

		foreach ( $cart_items as $cart_item_key => $cart_item ) {

			if ( 
				self::is_a_free_item( $cart_item ) 
				|| self::is_old_bogo_free_product( $cart_item ) 
				|| in_array( $cart_item_key, $free_items_triggered_item_key, true )
				|| isset( $cart_item['wt_credit_amount'] ) // Skip if the product is a store credit product.
			) {
				continue;
			}

			$item_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];

			$item_price           = self::get_product_price( wc_get_product( $item_id ) );
			$discount             = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $cart_item['data'] );
			$variation_attributes = $cart_item['variation'] ?? array();

			if ( ! ( ( $item_price - $discount ) >= $item_price )
				&& ! ( self::is_auto_add_only_full_discount()
					&& ! self::is_full_giveaway( $coupon_id, $item_id )
					)
				&& ! ( self::is_variation_choose_same_in_cart( $coupon_id ) && ! empty( $variation_attributes ) )
				&& self::is_coupon_applicable_product( $cart_item, $args )
				&& self::is_product_applicable_for_giveaway_same_in_cart( $coupon_id, $cart_item )
			) {
				$free_products[ $item_id ] = array(
					'variation_attributes'      => $variation_attributes,
					'_giveaway_trigger_product' => $cart_item_key,
					'cart_item_data'            => $cart_item,
				);
			}
		}
		$item_added = false;
		if ( ! empty( $free_products ) ) {

			foreach ( $free_products as $product_id => $arguments ) {
				$_cart_item = $arguments['cart_item_data'] ?? array();

				$free_products_qty = self::get_bogo_eligible_qty_same_in_cart( $coupon_id, $_cart_item );

				$args = array(
					'variation_attributes'              => $arguments['variation_attributes'] ?? array(),
					'_wbte_sc_giveaway_trigger_product' => $arguments['_giveaway_trigger_product'] ?? '',
					'old_cart_item_data'                => $_cart_item,
				);

				$item_added = $this->add_item_to_cart( $product_id, $free_products_qty, $coupon_code, $args );
			}
		}
		if ( $item_added ) {
			self::show_product_added_msg( $coupon_id );
		}
	}

	/**
	 * Update the quantity of the free product in the cart for 'same_product_in_the_cart' BOGO when product quantity is changed.
	 *
	 * '_wbte_sc_giveaway_trigger_product' is added for the free product that is added by 'same_product_in_the_cart' BOGO. The value of this key is the cart item key of the product, which triggers the free product. If the quantity of the triggering product is changed, then the quantity of the free product should also be changed.
	 *
	 * @since 3.0.0
	 *
	 * @param string $coupon_code   Coupon code.
	 * @param string $cart_item_key Cart item key.
	 */
	private function adjust_same_in_cart_giveaway_count( $coupon_code, $cart_item_key ) {

		$cart           = self::get_cart_object();
		$cart_item_data = $cart->get_cart_item( $cart_item_key );
		if ( empty( $cart_item_data ) ) {
			return;
		}

		$old_total_qty = ( 0 < $cart_item_data['variation_id'] )
			? self::get_variation_giveaway_count_in_cart( $coupon_code, $cart_item_data['product_id'] )
			: 0;
		$coupon_id     = wc_get_coupon_id_by_code( $coupon_code );

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code )
				&& isset( $cart_item['_wbte_sc_giveaway_trigger_product'] )
				&& trim( $cart_item['_wbte_sc_giveaway_trigger_product'], '"' ) === $cart_item_key ) {

				$free_qty         = self::get_bogo_eligible_qty_same_in_cart( $coupon_id, $cart_item_data );
				$old_giveaway_qty = $cart_item['quantity'];

				if ( 0 < $cart_item_data['variation_id'] && self::is_variation_choose_same_in_cart( $coupon_id ) ) { // Variation product.

					if ( $free_qty < $old_total_qty ) {
						$reduce_qty = absint( $old_total_qty - $free_qty );
						$cart->set_quantity( $key, max( 0, $old_giveaway_qty - $reduce_qty ) );
						$old_total_qty -= $reduce_qty;
						if ( $old_total_qty <= 0 ) {
							break;
						}
					}
				} elseif ( $old_giveaway_qty !== $free_qty ) {
					$cart->set_quantity( $key, $free_qty );
					break;
				}
			}
		}
		$this->process_same_product_in_cart_giveaway( $coupon_id );
	}


	/**
	 * Check whether the product is applicable for giveaway based on the BOGO configuration for 'same_product_in_the_cart' BOGO
	 * For 'same_product_in_the_cart' min/max qty/amount will be checked for each applicable product in the cart
	 *
	 * @since 3.0.0
	 *
	 * @param  int   $coupon_id Id of the coupon.
	 * @param  mixed $item      Cart item data.
	 * @return bool             True if product is applicable for giveaway, otherwise false
	 */
	private static function is_product_applicable_for_giveaway_same_in_cart( $coupon_id, $item ) {

		if ( ! $coupon_id || empty( $item ) ) {
			return false;
		}

		$min_qty    = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' );
		$max_qty    = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty' );
		$min_amount = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' );
		$max_amount = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount' );
		$is_spends  = self::is_coupon_based_on_subtotal( $coupon_id );
		$is_custom  = 'wbte_sc_bogo_apply_custom' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );

		$quantity    = $item['quantity'];
		$item_price  = $item['data']->get_price();

		$is_bxgx = self::is_bxgx( $coupon_id );
		$cheap_exp_session = self::get_cheap_exp_checked_products_session();
		if( $is_bxgx && isset( $cheap_exp_session[ $item['key'] ]['discounted_qty'] ) ){
			$quantity = $quantity - $cheap_exp_session[ $item['key'] ]['discounted_qty'];
		}

		$item_price  = self::alter_price_for_validation_check( $item_price, $item, $coupon_id );
		$item_amount = $item_price * $quantity;

		if ( ! $is_custom ) {
			if ( ( ! $is_spends && $max_qty && $quantity > $max_qty ) || ( $is_spends && $max_amount && $item_amount > $max_amount ) ) {
				return false;
			}
			if ( ( ! $is_spends && $min_qty && $quantity >= $min_qty ) ||
				( $is_spends && $min_amount && $item_amount >= $min_amount )
			) {
				return true;
			}
		} else {
			$free_qty = self::get_bogo_eligible_qty_same_in_cart( $coupon_id, $item );
			if ( $free_qty > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the quantity of the free product based on the BOGO configuration for 'same_product_in_the_cart' BOGO
	 *
	 * @since 3.0.0
	 *
	 * @param  int   $coupon_id     Id of the coupon.
	 * @param  array $item_data     Cart item data.
	 * @return int                  Quantity of the free product
	 */
	private static function get_bogo_eligible_qty_same_in_cart( $coupon_id, $item_data ) {

		$free_qty = absint( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_qty' ) );

		if ( empty( $item_data ) || ! $coupon_id ) {
			return $free_qty;
		}

		$apply_offer_times = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
		$bogo_triggers     = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );

		$min_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
			? (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' )
			: (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' );

		$eligible_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
			? $item_data['quantity']
			: $item_data['data']->get_price() * $item_data['quantity'];

		switch ( $apply_offer_times ) {
			case 'wbte_sc_bogo_apply_once':
				if ( $eligible_value >= $min_value ) {
					return $free_qty;
				} else {
					return 0;
				}

			case 'wbte_sc_bogo_apply_repeatedly':
				$apply_repeatedly_times = (int) self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_repeatedly_times' );

				$min_value = ( 0 >= $min_value ) ? 1 : $min_value;

				$frequency = (int) ( $eligible_value / $min_value );

				if ( 0 < $apply_repeatedly_times ) {
					return min( $frequency, $apply_repeatedly_times ) * $free_qty;
				}
				return $frequency * $free_qty;

			case 'wbte_sc_bogo_apply_custom':
				$free_qty = self::get_free_product_qty_by_range_for_custom( $eligible_value, $coupon_id );

				return $free_qty;

			default:
				return $free_qty;
		}
	}

	/**
	 * To get the applicable product from the cart for 'same_product_in_the_cart' BOGO.
	 * '$free_products_qty_arr' and '$triggered_item_arr' are passed by reference.
	 *
	 * @since 3.0.0
	 * @param int   $coupon_id              Coupon ID.
	 * @param array $free_products_qty_arr  Free products quantity array.
	 * @param array $triggered_item_arr     Triggered item array.
	 * @return array                        Array of BOGO applicable product IDs.
	 */
	public static function get_cart_coupon_applicable_products_same_in_cart( $coupon_id, &$free_products_qty_arr = array(), &$triggered_item_arr = array() ) {
		$cart                = self::get_cart_object();
		$applicable_products = array();

		if ( ! $cart || $cart->is_empty() ) {
			return $applicable_products;
		}

		$cart_items = $cart->get_cart();

		$coupon      = new WC_Coupon( $coupon_id );
		$coupon_code = $coupon->get_code();

		$coupon_products            = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$coupon_excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$coupon_categories          = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$coupon_excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		// Assigning $args before for loop to avoid multiple time fetching restriction data.
		$args = array(
			'coupon_products'           => $coupon_products,
			'coupon_categories'         => $coupon_categories,
			'coupon_exclude_products'   => $coupon_excluded_products,
			'coupon_exclude_categories' => $coupon_excluded_categories,
			'on_sale_non_sale'          => self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' )
		);

		foreach ( $cart_items as $cart_item_key => $cart_item ) {

			if ( isset( $cart_item['wt_credit_amount'] ) ) { // Skip if the product is a store credit product.
				continue;
			}

			if ( self::is_old_bogo_free_product( $cart_item ) ) {
				continue;
			}

			$item_id     = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
			$_product_id = self::is_variation_choose_same_in_cart( $coupon_id ) ? $cart_item['product_id'] : $item_id;

			if ( self::is_a_free_item( $cart_item ) ) {
				if ( wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ) === $coupon_code && ( array_key_exists( $item_id, $free_products_qty_arr ) || array_key_exists( $_product_id, $free_products_qty_arr ) ) ) {
					if ( $_product_id === $item_id ) {
						$free_products_qty_arr[ $item_id ] -= $cart_item['quantity'];
					} else {
						$free_products_qty_arr[ $_product_id ] -= $cart_item['quantity'];
					}
				}
				continue;
			}

			$item_price = self::get_product_price( wc_get_product( $item_id ) );
			$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $cart_item['data'] );

			if ( ! ( ( $item_price - $discount ) >= $item_price )
				&& self::is_coupon_applicable_product( $cart_item, $args )
				&& self::is_product_applicable_for_giveaway_same_in_cart( $coupon_id, $cart_item )
			) {
				$applicable_products[]                 = $_product_id;
				$_free_qty                             = self::get_bogo_eligible_qty_same_in_cart( $coupon_id, $cart_item );
				$free_products_qty_arr[ $_product_id ] = $_free_qty;
				$triggered_item_arr[ $_product_id ]    = $cart_item_key;
			}
		}

		return $applicable_products;
	}

	/**
	 * To check whether the free product is full giveaway or not. That is, 100% discount is applied.
	 *
	 * @since 3.0.0
	 * @param int $coupon_id Coupon ID.
	 * @param int $item_id   Product ID or Variation ID which the discount is applied.
	 * @return bool          True if full giveaway, otherwise false.
	 */
	public static function is_full_giveaway( $coupon_id, $item_id ) {
		$product       = wc_get_product( $item_id );
		$discount      = self::get_available_discount_for_giveaway_product( $coupon_id, $product );
		$product_price = $product->get_price();

		return $discount >= $product_price;
	}

	/**
	 * To check whether automatically add only full discount products.
	 * This value is set in the BOGO general settings.
	 *
	 * @since 3.0.0
	 * @return bool True if automatically add only full discount products, otherwise false.
	 */
	public static function is_auto_add_only_full_discount() {
		return 'wbte_sc_bogo_auto_add_full_giveaway' === self::get_general_settings_value( 'wbte_sc_bogo_auto_add_giveaway' );
	}

	/**
	 * Process BOGO functionality function when a product is removed from the cart.
	 * It will adjust the free product quantity if eligible qty is changed.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of removed product.
	 * @param object $cart          Cart object.
	 */
	public function update_cart_giveaway_count_on_item_removed( $cart_item_key, $cart ) {

		if ( empty( $cart ) ) {
			$cart = self::get_cart_object();
		}

		$cart_coupons = $cart->get_applied_coupons();
		if ( empty( $cart_coupons ) ) {
			return;
		}

		$this->process_giveaway_for_coupons( $cart_coupons );
	}

	/**
	 * Process BOGO functionalites when a product is added to the cart.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of added/updated product.
	 * @param int    $product_id       Product ID.
	 * @param int    $quantity         Currenct quantity in the cart.
	 * @param int    $variation_id     Variation ID.
	 * @param array  $variation      Variation data.
	 * @param array  $cart_item_data Cart item data.
	 */
	public function check_and_add_giveaway_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$this->check_to_add_giveaway( $cart_item_key, $quantity, 0, WC()->cart );
	}

	/**
	 * Process BOGO functionalites when added/qty updated in the cart.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of added/updated product.
	 * @param int    $quantity         Currenct quantity in the cart.
	 * @param int    $old_quantity     Old quantity.
	 * @param object $cart          Cart object.
	 */
	public function check_to_add_giveaway( $cart_item_key, $quantity, $old_quantity, $cart ) {

		$cart_item_data = $cart->cart_contents[ $cart_item_key ] ?? null;

		if ( is_null( $cart_item_data ) ) {
			return;
		}

		if ( self::is_a_free_item( $cart_item_data ) ) {
			return; /* already a free item so no need to check */
		}

		$cart_coupons = $cart->get_applied_coupons();
		if ( empty( $cart_coupons ) ) {
			return;
		}

		$this->process_giveaway_for_coupons( $cart_coupons, $cart_item_key );
	}

	/**
	 * To get the triggered product keys for the free items in the cart.
	 * Returns a array of cart item keys of products which triggered to add the free item.
	 *
	 * @since 3.0.0
	 * @param string $coupon_code Coupon code.
	 * @param object $cart        Cart object.
	 * @return array              Array of cart item keys.
	 */
	public static function get_free_items_triggered_item_keys( $coupon_code, $cart ) {

		if ( is_null( $cart ) ) {
			$cart = self::get_cart_object();
		}
		$free_items_triggered_item_key = array();

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $free_items_triggered_item_key;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) && isset( $cart_item['_wbte_sc_giveaway_trigger_product'] ) ) {
				$free_items_triggered_item_key[] = $cart_item['_wbte_sc_giveaway_trigger_product'];
			}
		}
		return $free_items_triggered_item_key;
	}

	/**
	 * Remove the free giveaway product when the triggered product is removed from the cart.
	 * This is applicable for 'same_product_in_the_cart' BOGO.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of the removed product.
	 * @param object $cart          Cart object.
	 */
	public function remove_free_gift_of_removed_product( $cart_item_key, $cart ) {
		foreach ( $cart->get_cart() as $key => $cart_item ) {

			if ( self::is_a_free_item( $cart_item ) && isset( $cart_item['_wbte_sc_giveaway_trigger_product'] ) && trim( $cart_item['_wbte_sc_giveaway_trigger_product'], '"' ) === $cart_item_key ) {
				$cart->remove_cart_item( $key );
				unset( self::$giveaway_discounted_amount[ $key ] );
			}
		}
	}

	/**
	 *  To alter coupon applied message. If coupon is BOGO, then message saved in general settings will be considered.
	 *  {bogo_title} will be replaced with the BOGO title.
	 *
	 *  @since  3.0.0
	 *  @param  string $msg            Coupon applied msg.
	 *  @param  string $coupon_code    Coupon code.
	 *  @return string                  If bogo coupon new message, otherwise old message
	 */
	public static function alter_bogo_applied_message( $msg, $coupon_code ) {
		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
		if ( self::is_bogo( $coupon_id ) ) {
			$message    = self::get_general_settings_value( 'wbte_sc_bogo_general_discount_apply_message' );
			$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
			$message    = str_replace( '{bogo_title}', $bogo_title, $message );
			return $message;
		}
		return $msg;
	}

	/**
	 * Any product from store BOGO functionality.
	 *
	 * @since 3.0.0
	 * @since 3.1.0 Added '{shop_link $text$}' in the message, $text$ will be replaced with the text passed in the message.
	 * @param int  $coupon_id    Coupon ID.
	 * @param bool $when_applied If true, it is called when coupon applied, otherwise called when qty updated.
	 */
	public static function process_any_product_from_store_giveaway( $coupon_id, $when_applied = true ) {
		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		if ( 'any_product_from_store' !== $customer_gets ) {
			return;
		}

		$message    = self::get_general_settings_value( 'wbte_sc_bogo_general_select_any_from_store' );
		$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );

		$message = preg_replace_callback(
			'/\{shop_link\s*\$([^$\n\r]*)\$\}/',
			function ( $matches ) {
				// Extract the custom text.
				$custom_text = $matches[1];
				// Generate the shop link.
				$shop_link = '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html( $custom_text ) . '</a>';
				return $shop_link;
			},
			$message
		);

		$message     = str_replace( '{bogo_title}', $bogo_title, $message );
		$qty         = self::get_bogo_eligible_qty( $coupon_id );
		$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

		if ( ! $when_applied ) {
			$cart = self::get_cart_object();
			if ( is_null( $cart ) || $cart->is_empty() ) {
				return;
			}
			$cart_items    = $cart->get_cart();
			$free_item_qty = 0;
			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
					$free_item_qty += $cart_item['quantity'];
				}
			}
			$qty -= $free_item_qty;
		}
		self::set_bogo_eligible_session(
			$coupon_id,
			array(
				'msg' => $message,
				'qty' => $qty,
			)
		);
		if ( 0 > $qty ) {
			self::reduce_free_product_qty( $coupon_code, $qty );
		}
	}

	/**
	 *  To reduce the free product quantity when the boogo eligibility changed.
	 *  When bogo eligibility quantity is reduced, then free product quantity will be reduced, free product with higher price will be reduced first.
	 *
	 *  @since  3.0.0
	 *  @param  string $coupon_code    Coupon code.
	 *  @param  int    $qty            Quantity to reduce.
	 */
	private static function reduce_free_product_qty( $coupon_code, $qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}
		$cart_items     = $cart->get_cart();
		$qty            = abs( $qty );
		$free_items_arr = array();
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$free_items_arr[ $cart_item_key ] = array(
					'qty'   => $cart_item['quantity'],
					'price' => $cart_item['data']->get_price() * $cart_item['quantity'],
				);
			}
		}

		// Sort the free items by price in descending order.
		uasort(
			$free_items_arr,
			function ( $a, $b ) {
				return $b['price'] - $a['price'];
			}
		);

		if ( $qty > 0 ) {
			foreach ( $free_items_arr as $cart_item_key => $item ) {
				if ( $qty <= 0 ) {
					break;
				}

				$current_qty = $item['qty'];

				if ( $current_qty <= $qty ) {
					// Remove the entire item if its quantity is less than or equal to qty to remove.
					$cart->remove_cart_item( $cart_item_key );
					unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
					$qty -= $current_qty;
				} else {
					// Otherwise, reduce the quantity of the item.
					$new_qty = $current_qty - $qty;
					$cart->set_quantity( $cart_item_key, $new_qty );
					$qty = 0;
				}
			}
		}
	}

	/**
	 *  To set the giveaway available session.
	 *
	 *  @since  3.0.0
	 *  @param      int   $coupon_id    Coupon id.
	 *  @param      array $data         Data to store in session. Data contains message and balance quantity to add.
	 */
	public static function set_bogo_eligible_session( $coupon_id, $data ) {
		$bogo_eligible                 = self::get_bogo_eligible_session();
		$coupon_code                   = wc_format_coupon_code( wc_get_coupon_code_by_id( $coupon_id ) );
		$bogo_eligible[ $coupon_code ] = $data;
		if ( empty( $data ) ) {
			unset( $bogo_eligible[ $coupon_code ] );
		} elseif ( isset( $data['qty'] ) && $data['qty'] <= 0 ) {
				unset( $bogo_eligible[ $coupon_code ] );
		}
		WC()->session->set( self::$bogo_eligible_session_id, $bogo_eligible );
	}

	/**
	 * To get stored giveaway available session.
	 *
	 * @since  3.0.0
	 * @return array If session available, return session data otherwise empty array.
	 */
	public static function get_bogo_eligible_session() {
		return ! is_null( WC()->session ) ? WC()->session->get( self::$bogo_eligible_session_id ) : array();
	}

	/**
	 * To show the giveaway available message in cart page.
	 *
	 * @since 3.0.0
	 */
	public static function show_giveaway_eligible_message() {
		$cart = self::get_cart_object();

		if ( is_null( $cart ) ) {
			return;
		}
		if ( $cart->is_empty() ) {
			return;
		}

		// Disable message on block cart/checkout.
		if ( function_exists( 'has_block' ) &&
			( ( is_checkout() && has_block( 'woocommerce/checkout' ) ) || ( is_cart() && has_block( 'woocommerce/cart' ) ) )
		) {
			return;
		}

		$coupons = $cart->get_applied_coupons();
		$coupons = ! is_array( $coupons ) ? array() : $coupons;

		$bogo_eligible = self::get_bogo_eligible_session();

		$bogo_eligible = ! is_array( $bogo_eligible ) ? array() : $bogo_eligible;
		foreach ( $bogo_eligible as $coupon_code => $data ) {
			if ( in_array( $coupon_code, $coupons, true ) ) {
				$msg = $data['msg'];
				if ( '' !== $data['msg'] ) {
					if ( isset( $data['qty'] ) && $data['qty'] > 0 ) {
						$msg = str_replace( '{qty}', $data['qty'], $msg );
					}
					
					wc_add_notice( $msg, 'notice' );
				}
			} else {
				self::remove_bogo_eligible_session( $coupon_code );
			}
		}
	}

	/**
	 *  To remove the giveaway available session of given coupon
	 *
	 *  @since  3.0.0
	 *  @param      string $coupon_code    Coupon code.
	 */
	public static function remove_bogo_eligible_session( $coupon_code ) {
		$bogo_eligible = self::get_bogo_eligible_session();
		$coupon_code   = wc_format_coupon_code( $coupon_code );
		if ( isset( $bogo_eligible[ $coupon_code ] ) ) {
			unset( $bogo_eligible[ $coupon_code ] );
			WC()->session->set( self::$bogo_eligible_session_id, $bogo_eligible );
		}
	}

	/**
	 * Triggered when a product is added to the cart.
	 * It will trigger 'add_giveaway_on_update_cart' method to check the added product is eligible for giveaway based on BOGO eligible session.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of the added product.
	 * @param int    $product_id       Prouct id.
	 * @param int    $quantity         Add quantity.
	 * @param int    $variation_id     Variation id.
	 * @param array  $variation      Variation attributes.
	 */
	public function add_giveaway_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation ) {
		$cart = self::get_cart_object();

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$args = array(
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);
		$this->add_giveaway_on_update_cart( $cart_item_key, $quantity, 0, $cart, $args );
	}

	/**
	 * When a product is added to the cart or quantity is updated, check added/qty updated product is eligible for giveaway based of BOGO eligible session.
	 * Used for 'any_product_from_category' and 'any_product_from_store' BOGO.
	 * If added qty is greater than giveaway qty, then balance qty will be added as normal product. If added qty is less than giveaway qty, then update balance giveaway qty in session.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key of the added/updated product.
	 * @param int    $quantity         Current quantity of the product in cart.
	 * @param int    $old_quantity     Old quantity of the product in cart.
	 * @param object $cart          Cart object.
	 * @param array  $args           Additional arguments.
	 */
	public function add_giveaway_on_update_cart( $cart_item_key, $quantity, $old_quantity, $cart, $args = array() ) {
		$cart_item_data = $cart->cart_contents[ $cart_item_key ] ?? array();

		if ( ! empty( $cart_item_data ) && ! self::is_a_free_item( $cart_item_data ) && ! self::is_old_bogo_free_product( $cart_item_data ) && ( $quantity >= $old_quantity ) ) {
			$product_id   = $args['product_id'] ?? $cart_item_data['product_id'] ?? 0;
			$variation_id = $args['variation_id'] ?? $cart_item_data['variation_id'] ?? 0;
			$variation    = $args['variation'] ?? $cart_item_data['variation'] ?? array();

			$new_added_qty = $quantity - $old_quantity;

			$item_id = $variation_id > 0 ? $variation_id : $product_id;

			$_product   = $cart_item_data['data'];
			$item_price = self::get_product_price( $_product );

			$bogo_eligible = self::get_bogo_eligible_session();
			$bogo_eligible = ! is_array( $bogo_eligible ) ? array() : $bogo_eligible;

			if ( ! empty( $bogo_eligible ) ) {
				$args['variation_attributes'] = $variation;
				$args['old_cart_item_data']   = $cart_item_data;
				$item_cat_ids                 = (array) Wt_Smart_Coupon_Common::get_product_cat_ids( $product_id ); // Get category ids of the product.

				foreach ( $bogo_eligible as $coupon_code => $data ) {
					$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

					if ( ! $coupon_id || ! self::is_bxgx( $coupon_id ) ) {
						continue;
					}

					$discount = self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );
					if ( ( ( $item_price - $discount ) >= $item_price ) ) {
						continue;
					}

					/**
					 * Alter the product applicable for giveaway on add/update cart ( for 'any_product_from_category' and 'any_product_from_store' ).
					 *
					 * @since 3.2.0
					 * @param bool   $applicable     True if product is applicable for giveaway, otherwise false.
					 * @param string $cart_item_key  Cart item key.
					 * @param int    $quantity       Current quantity of the product in cart.
					 * @param int    $old_quantity   Old quantity of the product in cart.
					 * @param int    $coupon_id      Coupon id.
					 * @param array  $data  		 BOGO eligible data.
					 */
					if( ! apply_filters( 'wbte_sc_bogo_alter_product_applicable_for_giveaway_on_add_update', true, $cart_item_key, $quantity, $old_quantity, $coupon_id, $data ) ) {
						continue;
					}

					$customer_gets     = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
					$customer_gets_qty = $data['qty'];
					if ( 'any_product_from_category' === $customer_gets ) {
						$free_categories = $data['cat_ids']; // Get category ids which are eligible for giveaway.
						if ( empty( $free_categories ) ) {
							continue;
						}
						$com_cat = array_intersect( $item_cat_ids, $free_categories ); // Intersection of product categories and giveaway categories. If any category matched, then add product as giveaway.
						if ( empty( $com_cat ) ) {
							continue;
						}
					}

					$item_added = false;
					remove_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20 );
					if ( $customer_gets_qty > $new_added_qty ) {
						// If newly added qty is less than giveaway qty, add full qty as giveaway then update giveaway qty in session.
						$new_qty                              = $customer_gets_qty - $new_added_qty;
						$item_added                           = $this->add_item_to_cart( $item_id, $new_added_qty, $coupon_code, $args );
						$bogo_eligible[ $coupon_code ]['qty'] = $new_qty;
						$new_added_qty                        = 0;
						self::set_bogo_eligible_session( $coupon_id, $bogo_eligible[ $coupon_code ] );
					} else {

						$item_added     = $this->add_item_to_cart( $item_id, $customer_gets_qty, $coupon_code, $args );
						$new_added_qty -= $customer_gets_qty;
						self::remove_bogo_eligible_session( $coupon_code );
					}
					if ( $item_added ) {
						self::show_product_added_msg( $coupon_id );
					}
				}
				if ( 0 < $new_added_qty ) {
					// Added qty is greater than giveaway qty, so add balance as normal product. Here qty is changing not adding new product because, product already added, this function triggered in that time.
					if ( $old_quantity > 0 ) {
						// Last added product already in cart, so add balance qty to old qty.
						$this->update_normal_product_qty( $cart_item_key, $old_quantity + $new_added_qty, $cart );
					} else {
						// Last added product not in cart, so add balance qty to the product.
						$this->update_normal_product_qty( $cart_item_key, $new_added_qty, $cart );
					}
				} else {
					$this->update_normal_product_qty( $cart_item_key, $old_quantity, $cart );
				}
			}
		}
	}

	/**
	 * Update balance qty as normal product.
	 * Eg scenario: For BOGO 'any_product_from_category' and 'any_product_from_store', if customer gets qty 1 and added qty 2, then 1 qty will be added as giveaway and 1 qty will be added as normal product.
	 * Remove actions to avoid infinite loop.
	 *
	 * @since 3.0.0
	 * @param string $cart_item_key Cart item key to update qty.
	 * @param int    $quantity      Quantity to update.
	 * @param object $cart          Cart object.
	 */
	private function update_normal_product_qty( $cart_item_key, $quantity, $cart ) {
		remove_action( 'woocommerce_add_to_cart', array( $this, 'add_giveaway_on_add_to_cart' ), 11 );
		remove_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'add_giveaway_on_update_cart' ), 110 );
		remove_action( 'woocommerce_cart_item_removed', array( $this, 'update_cart_giveaway_count_on_item_removed' ), 111 );

		$cart->set_quantity( $cart_item_key, $quantity );

		add_action( 'woocommerce_add_to_cart', array( WC()->cart, 'calculate_totals' ), 20 );
	}

	/**
	 *  Alter coupon block title text.
	 *
	 *  @since  3.0.0
	 *  @param      array  $coupon_data    Coupon data.
	 *  @param      object $coupon         WC_Coupon object.
	 *  @return     array                  $coupon_data
	 */
	public static function alter_coupon_title_text( $coupon_data, $coupon ) {
		$coupon_id = $coupon->get_id();
		if ( self::is_bogo( $coupon_id ) ) {
			$coupon_data['coupon_amount'] = '';
			$bogo_title                   = self::is_auto_bogo( $coupon_id ) ? self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' ) : $coupon->get_code();
			$coupon_data['coupon_type']   = apply_filters( 'wt_sc_alter_coupon_title_text', $bogo_title, $coupon );
		}
		return $coupon_data;
	}

	/**
	 * Make the quantity field as uneditable for free giveaway products.
	 *
	 * @since 3.0.0
	 * @param  string $product_quantity HTML code of the product quantity field.
	 * @param  string $cart_item_key    Cart item key.
	 * @param  array  $cart_item        Cart item data.
	 * @return string                   If product is free, then return the quantity field as uneditable, otherwise return the original quantity field.
	 */
	public function update_cart_item_quantity_field( $product_quantity, $cart_item_key, $cart_item ) {
		if ( self::is_a_free_item( $cart_item ) ) {
			$product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
		}
		return $product_quantity;
	}

	/**
	 * Any product from category BOGO functionality.
	 *
	 * @since 3.0.0
	 * @param int  $coupon_id    Coupon ID.
	 * @param bool $when_applied If true, it is called when coupon applied, otherwise called when qty updated.
	 */
	public static function process_any_product_from_category( $coupon_id, $when_applied = true ) {
		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		if ( 'any_product_from_category' !== $customer_gets ) {
			return;
		}

		$message                 = self::get_general_settings_value( 'wbte_sc_bogo_general_select_from_specific_category' );
		$qty                     = self::get_bogo_eligible_qty( $coupon_id );
		$giveaway_categories_ids = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_free_category_ids' ) );
		$cat_arr                 = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'orderby'    => 'name',
				'hide_empty' => false,
				'include'    => $giveaway_categories_ids,
			)
		);

		if ( is_array( $cat_arr ) ) {
			$cat_link_arr = array();

			foreach ( $cat_arr as $cat ) {
				$cat_link_arr[] = '<a href="' . esc_attr( get_term_link( $cat->term_id ) ) . '" class="wt_sc_giveaway_category_link">' . esc_html( $cat->name ) . '</a>';
			}
		}
		$cat_links = implode( ', ', $cat_link_arr );

		if ( ! $when_applied ) {
			$cart = self::get_cart_object();
			if ( is_null( $cart ) || $cart->is_empty() ) {
				return;
			}
			$coupon_code   = wc_get_coupon_code_by_id( $coupon_id );
			$cart_items    = $cart->get_cart();
			$free_item_qty = 0;
			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
					$free_item_qty += $cart_item['quantity'];
				}
			}
			$qty -= $free_item_qty;
		}

		$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );

		$message = str_replace(
			array( '{category_name}', '{bogo_title}' ),
			array( $cat_links, $bogo_title ),
			$message
		);

		self::set_bogo_eligible_session(
			$coupon_id,
			array(
				'msg'     => $message,
				'qty'     => $qty,
				'cat_ids' => $giveaway_categories_ids,
			)
		);

		if ( 0 > $qty ) {
			self::reduce_free_product_qty( $coupon_code, $qty );
		}
	}

	/**
	 * Remove all BOGO session when an order placed.
	 *
	 * @since  3.0.0
	 */
	public static function remove_all_bogo_sessions() {
		WC()->session->__unset( self::$bogo_eligible_session_id );
		WC()->session->__unset( self::$cheap_exp_checked_products_session_id );
	}

	/**
	 * Get the quantity of the free product for the given coupon.
	 *
	 * @since  3.0.0
	 * @param  int $coupon_id  Coupon ID.
	 * @return int              The quantity of the free product based on the offer type and conditions.
	 */
	public static function get_bogo_eligible_qty( $coupon_id ) {
		$free_qty          = absint( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_qty' ) );
		$apply_offer_times = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
		$bogo_triggers     = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );

		switch ( $apply_offer_times ) {
			case 'wbte_sc_bogo_apply_once':
				return $free_qty;

			case 'wbte_sc_bogo_apply_repeatedly':
				$apply_repeatedly_times = (int) self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_repeatedly_times' );
				$min_value              = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
					? (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' )
					: (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' );

				$eligible_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
					? self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'qty' )
					: self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'amount' );

				$min_value = ( 0 >= $min_value ) ? 1 : $min_value;
				$frequency = max( 1, (int) ( $eligible_value / $min_value ) );

				if ( 0 < $apply_repeatedly_times ) {
					return min( $frequency, $apply_repeatedly_times ) * $free_qty;
				}
				return $frequency * $free_qty;

			case 'wbte_sc_bogo_apply_custom':
				$eligible_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
					? self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'qty' )
					: self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'amount' );

				$free_qty = self::get_free_product_qty_by_range_for_custom( $eligible_value, $coupon_id );
				if ( 0 === $free_qty ) {
					$coupon_code = wc_get_coupon_code_by_id( $coupon_id );
					WC()->cart->remove_coupon( sanitize_text_field( $coupon_code ) );
					$msg = __( 'The cart does not meet the required conditions for the coupon.', 'wt-smart-coupons-for-woocommerce-pro' );
					if ( ! wc_has_notice( $msg, 'error' ) ) {
						wc_add_notice( $msg, 'error' );
					}
				}
				return $free_qty;

			default:
				return $free_qty;
		}
	}


	/**
	 * Get the free product quantity by range for custom repeatedly mode.
	 *
	 * @since 3.0.0
	 *
	 * @param int|float $value          The value to be checked against the custom ranges (can be quantity or amount).
	 * @param int       $coupon_id            The ID of the coupon being checked.
	 * @param bool      $exclude_overlap     $exclude_overlap Optional. Whether to exclude the maximum value of each range when checking. Default false.
	 * @return int                      The quantity of the free product.
	 */
	private static function get_free_product_qty_by_range_for_custom( $value, $coupon_id, $exclude_overlap = false ) {

		$custom_min   = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_min' ) );
		$custom_max   = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_max' ) );
		$custom_times = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_times' ) );

		$last_index = count( $custom_min ) - 1;

		foreach ( $custom_min as $index => $min_value ) {
			$max_value = (int) $custom_max[ $index ];  // Corresponding max value.

			if ( $index === $last_index && 0 === $max_value && $value >= $min_value ) {
				return (int) $custom_times[ $index ];
			}

			if ( $exclude_overlap && $value >= $min_value && $value < $max_value ) {
				// For amount, exclude the max value from the current range.
				return (int) $custom_times[ $index ];
			} elseif ( ! $exclude_overlap && $value >= $min_value && $value <= $max_value ) {
				// For quantity, include the max value in the current range.
				return (int) $custom_times[ $index ];
			}
		}

		return 0;
	}

	/**
	 * To get coupon eligible cart amount or quantity.
	 *
	 * @since 3.0.0
	 *
	 * @param  int    $coupon_id          Id of the coupon.
	 * @param  string $type               Type of the return value. 'qty' or 'amount'.
	 * @return int      $eligible_count     Return eligible amount or quantity by iterating through cart items.
	 */
	public static function get_coupon_eligible_cart_amount_qty( $coupon_id, $type ) {

		$cart           = self::get_cart_object();
		$eligible_count = 0;
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $eligible_count;
		}

		$coupon_products            = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$coupon_excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$coupon_categories          = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$coupon_excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		// Assigning $args before for loop to avoid multiple time fetching restriction data.
		$args = array(
			'coupon_products'           => $coupon_products,
			'coupon_categories'         => $coupon_categories,
			'coupon_exclude_products'   => $coupon_excluded_products,
			'coupon_exclude_categories' => $coupon_excluded_categories,
			'on_sale_non_sale'          => self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' )
		);

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item ) || self::is_old_bogo_free_product( $cart_item ) ) {
				continue;
			}
			if ( self::is_coupon_applicable_product( $cart_item, $args ) ) {

				if ( 'qty' === $type ) {
					$eligible_count += $cart_item['quantity'];
				} elseif ( 'amount' === $type ) {
					$eligible_count += $cart_item['data']->get_price() * $cart_item['quantity'];
				}
			}
		}
		return $eligible_count;
	}

	/**
	 * To get the quantity of giveaway product in cart for given coupon.
	 * This function is using to get the old giveaway quantity when updating the cart for specific product giveaway.
	 *
	 * @since 3.0.0
	 * @since 3.1.0 Added $total parameter.
	 * @param  string $coupon_code  Coupon code to check whether the free item belongs to this coupon.
	 * @param  bool   $total        If true, return total quantity of giveaway products in cart, otherwise return quantity of one giveaway product.
	 * @return int                  Quantity of giveaway product in cart.
	 */
	private static function get_coupon_giveaway_count_in_cart( $coupon_code, $total = false ) {
		$cart           = self::get_cart_object();
		$giveaway_count = 0;
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $giveaway_count;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				if( $total ) {
					$giveaway_count += $cart_item['quantity'];
				} else {
					return $cart_item['quantity'];
				}
			}
		}
		return $giveaway_count;
	}


	/**
	 * To get the quantity of total giveaway variation products in cart for given coupon by parent product id.
	 *
	 * @since 3.0.0
	 * @param  string $coupon_code Coupon code.
	 * @param  int    $parent_id   Parent product id.
	 * @return int                 Quantity of giveaway product in cart.
	 */
	private static function get_variation_giveaway_count_in_cart( $coupon_code, $parent_id ) {
		$cart           = self::get_cart_object();
		$giveaway_count = 0;
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $giveaway_count;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) && 0 < $cart_item['variation_id'] && $parent_id === $cart_item['product_id'] ) {
				$giveaway_count += $cart_item['quantity'];
			}
		}
		return $giveaway_count;
	}

	/**
	 * To update quantity of giveaway product in cart when cart items quantity updated.
	 *
	 * @since 3.0.0
	 * @param string $coupon_code Coupon code to check whether the free item belongs to this coupon.
	 * @param int    $qty         Quantity to update.
	 */
	public static function update_giveaway_cart_qty( $coupon_code, $qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$cart->set_quantity( $cart_item_key, $qty );
			}
		}
	}

	/**
	 * Process BOGO coupons based on the customer gets option when a product added to cart or quantity updated.
	 * For BOGO type bxgx.
	 *
	 * @since 3.0.0
	 * @param array  $coupons       Array of coupon codes.
	 * @param string $cart_item_key Cart item key. Used for same product in cart giveaway.
	 */
	public function process_giveaway_for_coupons( $coupons, $cart_item_key = '' ) {

		if ( empty( $coupons ) ) {
			return;
		}

		foreach ( $coupons as $coupon_code ) {

			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );

			if ( ! $coupon ) {
				continue;
			}

			$coupon_id = $coupon->get_id();

			if ( self::is_bogo( $coupon_id ) && self::is_bxgx( $coupon_id ) ) {

				$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
				if ( 'specific_product' === $bogo_customer_gets ) {
					$this->process_specific_product_giveaway( $coupon_id, false );
				} elseif ( 'any_product_from_store' === $bogo_customer_gets ) {
					self::process_any_product_from_store_giveaway( $coupon_id, false );
				} elseif ( 'any_product_from_category' === $bogo_customer_gets ) {
					self::process_any_product_from_category( $coupon_id, false );
				} elseif ( 'same_product_in_the_cart' === $bogo_customer_gets ) {
					$this->adjust_same_in_cart_giveaway_count( $coupon_code, $cart_item_key );
				}
			}
		}
	}

	/**
	 * To show product added message when a product added to cart as a giveaway.
	 * If placeholder {bogo_title} available in the message, then replace it with the BOGO title.
	 *
	 * @since 3.0.0
	 * @param int $coupon_id Coupon id.
	 */
	private static function show_product_added_msg( $coupon_id ) {
		$bogo_title        = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
		$product_added_msg = self::get_general_settings_value( 'wbte_sc_bogo_general_product_added_message' );
		$product_added_msg = str_replace( '{bogo_title}', $bogo_title, $product_added_msg );
		wc_add_notice( $product_added_msg, 'success' );
	}

	/**
	 * Add giveaway product msg for block.
	 * Hooked into: wbte_sc_alter_blocks_data
	 *
	 * @since 3.0.0
	 * @param  array $block_data block data array.
	 * @return array    Block data array with added giveaway product msg.
	 */
	public static function add_blocks_data( $block_data ) {

		$cart = self::get_cart_object();
		if ( ! is_null( $cart ) && ! $cart->is_empty() ) {
			$out           = array();
			$cheap_exp_msg = array();

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

				if ( self::is_old_bogo_free_product( $cart_item ) ) {
					continue;
				}

				$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				$qty        = $cart_item['quantity'];
				$product    = wc_get_product( $item_id );
				$item_price = self::get_product_price( $product );

				if ( self::is_a_free_item( $cart_item ) ) {

					$gift_msg = self::get_product_under_msg( $cart_item );
					if ( ! empty( $gift_msg ) ) {
						$out[ $cart_item_key ] = '<div class="wbte_sc_bogo_bxgx_product_under_msg" >' . $gift_msg . '<p class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'wt-smart-coupons-for-woocommerce-pro' ) . wp_kses_post( wc_price( ( $item_price - $cart_item['wbte_sc_bogo_discount'] ) * $qty ) ) . '</p></div>';
					}
					continue;
				}

				if ( isset( self::$bogo_cheap_exp_checked_products[ $cart_item_key ] ) ) {
					$coupons      = self::$bogo_cheap_exp_checked_products[ $cart_item_key ]['coupons'];
					$coupon_codes = self::$bogo_cheap_exp_checked_products[ $cart_item_key ]['coupon_codes'];
					if ( ! empty( $coupon_codes ) ) {

						$cheap_exp_msg[ $cart_item_key ] = '<div>' . apply_filters( 'wbte_sc_alter_bogo_cheap_exp_cart_lineitem_text', '<p class="wbte_sc_bogo_msg_under_free_gift wbte_sc_bogo_cheap_exp_cart_item">' . implode( ', ', $coupons ) . '</p>', $coupon_codes, $cart_item ) . '<p class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'wt-smart-coupons-for-woocommerce-pro' ) . wp_kses_post( wc_price( ( $item_price * $qty ) - self::$bogo_cheap_exp_checked_products[ $cart_item_key ]['discount'] ) ) . '</p></div>';
					}
				}
			}

			if ( ! empty( $out ) ) {
				$block_data['cartitem_bogo_text'] = $out;
			}

			if ( ! empty( $cheap_exp_msg ) ) {
				$block_data['cartitem_bogo_cheap_exp_text'] = $cheap_exp_msg;
			}

			/** Giveaway products ================================ */
			$out = '';
			ob_start();
			self::display_giveaway_products();
			$out                              = ob_get_clean();
			$block_data['bogo_products_html'] = $out;

			/** Giveaway eligible message ======================== */
			$out     = array();
			$coupons = $cart->get_applied_coupons();
			$coupons = ! is_array( $coupons ) ? array() : $coupons;

			$bogo_eligible = self::get_bogo_eligible_session();

			$bogo_eligible = ! is_array( $bogo_eligible ) ? array() : $bogo_eligible;
			foreach ( $bogo_eligible as $coupon_code => $data ) {
				if ( in_array( $coupon_code, $coupons, true ) ) {
					$msg = $data['msg'];
					if ( '' !== $data['msg'] ) {
						if ( isset( $data['qty'] ) && $data['qty'] > 0 ) {
							$msg = str_replace( '{qty}', $data['qty'], $msg );
						}
						$out[] = $msg;
					}
				} else {
					self::remove_bogo_eligible_session( $coupon_code );
				}
			}

			if ( ! empty( $out ) ) {
				$block_data['bogo_eligible_message'] = implode( '<br />', $out ); // Merge to single message.
			}

			/** BOGO coupons list for displaying offer title instead of code in block ===== */
			$bogo_coupons = array();
			foreach ( $coupons as $coupon_code ) {
				$coupon = new WC_Coupon( $coupon_code );
				$coupon_id = $coupon->get_id();
				if ( self::is_bogo( $coupon_id ) && self::is_auto_bogo( $coupon->get_id() ) ) {
					$bogo_title                   = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
					$bogo_coupons[ $coupon_code ] = $bogo_title;
				}
			}
			$block_data['auto_bogo_coupons'] = $bogo_coupons;
		}
		return $block_data;
	}

	/**
	 * Add block to the block list
	 *
	 * @since 3.0.0
	 * @param  array $registered_blocks Blocks data array.
	 * @return array Registered blocks data array
	 */
	public static function register_blocks( $registered_blocks ) {

		$registered_blocks['bogo'] = array(
			'block_dir'      => 'bogo',
			'script_handles' => array( 'frontend-js' ),
		);

		return $registered_blocks;
	}

	/**
	 * To check whether the user can change the quantity of the free product.
	 * Default is true. Customer can change the behavior by using the filter hook.
	 * It is used for 'specific_product' and 'same_product_in_the_cart' giveaway types, in which giveaway products will list in the cart and the checkout page.
	 *
	 * @since 3.0.0
	 * @param  int $coupon_id Coupon id.
	 * @return boolean
	 */
	private static function is_user_can_change_free_product_qty( $coupon_id ) {
		return (bool) apply_filters( 'wbte_sc_bogo_user_can_change_free_product_qty', true, $coupon_id );
	}

	/**
	 * Whether user can choose other variation of variation product in cart or not.
	 * For 'same_product_in_the_cart' giveaway type.
	 *
	 * @since 3.0.0
	 *
	 * @param  int $coupon_id Coupon id.
	 * @return bool            If true, then the same variation product will not add to the cart, user can choose the variation, otherwise it will add same variation product to the cart.
	 */
	private static function is_variation_choose_same_in_cart( $coupon_id ) {
		return (bool) apply_filters( 'wbte_sc_bogo_is_variation_choose_same_in_cart', false, $coupon_id );
	}

	/**
	 * Display free product discount detail in order details.
	 *
	 * @since 3.0.0
	 * @param array  $total_rows Order item total rows.
	 * @param object $order     Order object.
	 * @return array            Order item total rows with free product discount detail.
	 */
	public static function woocommerce_get_order_item_totals( $total_rows, $order ) {
		$out         = array();
		$order_items = $order->get_items();
		foreach ( $order_items as $order_item_id => $order_item ) {
			$giveaway_info = self::prepare_giveaway_info_for_order( $order_item_id, $order_item );

			if ( $giveaway_info ) {
				$coupon_code = wc_get_order_item_meta( $order_item_id, 'wbte_sc_free_gift_coupon', true );
				$label_text  = self::get_customized_text( 'giveaway_order_summary_label', array( 'coupon_code' => $coupon_code ) );
				$label_text  = apply_filters( 'wt_sc_alter_order_detail_giveaway_info_label', $label_text, $order_item, $order_item_id, $order );

				$out[ 'wbte_sc_free_product_' . $order_item_id ] = array(
					'label' => $label_text,
					'value' => $giveaway_info,
				);
			}
		}

		if ( ! empty( $out ) ) {
			$offset     = array_search( 'shipping', array_keys( $total_rows ), true );
			$total_rows = array_merge(
				array_slice( $total_rows, 0, $offset ),
				$out,
				array_slice( $total_rows, $offset, null )
			);
		}

		return $total_rows;
	}

	/**
	 * Add Free Prodcut details on cart item list.
	 *
	 * @since 3.0.0
	 * @since 3.1.0 Added cheap/expensive discount meta data.
	 * @param object $item        Cart item object.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $values      Cart item data.
	 */
	public static function add_free_product_details_into_order( $item, $cart_item_key, $values ) {
		if ( self::is_a_free_item( $values ) ) {
			$item->add_meta_data( 'wbte_sc_free_product', $values['wbte_sc_free_product'] );
			$item->add_meta_data( 'wbte_sc_free_gift_coupon', $values['wbte_sc_free_gift_coupon'] );
			return;
		}

		if ( isset( self::$bogo_cheap_exp_checked_products[ $cart_item_key ] ) ) {
			$item->add_meta_data( 'wbte_sc_cheap_exp_item', 'wbte_sc_cheap_exp_item' );
			$item->add_meta_data( 'wbte_sc_cheap_exp_discount', self::$bogo_cheap_exp_checked_products[ $cart_item_key ]['discount'] );
		}
	}

	/**
	 * Hide free product meta details in order details page.
	 *
	 * @since 3.0.0
	 * @since 3.1.0  Added cheap/expensive discount meta removal.
	 * @param array  $formatted_meta Formatted meta data.
	 * @param object $item Order item object.
	 */
	public static function unset_free_product_order_item_meta_data( $formatted_meta, $item ) {

		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, array( 'wbte_sc_free_product', 'wbte_sc_free_gift_coupon', 'wbte_sc_cheap_exp_item', 'wbte_sc_cheap_exp_discount' ), true )
			) {
				unset( $formatted_meta[ $key ] );
			}
		}
		return $formatted_meta;
	}

	/**
	 * Update order total if BOGO coupon applied.
	 * Only applicable for Apply tax on original price.
	 *
	 * @since 3.0.0
	 * @since 3.1.0  Added cheap/expensive discount reduction.
	 * @param bool   $taxes     Having taxes or not.
	 * @param object $order     Order object.
	 */
	public static function update_order_total( $taxes, $order ) {

		if ( self::is_apply_tax_on_discounted_price() || is_cart() || is_checkout() ) {
			return;
		}
		$new_total     = $order->get_total( 'edit' );
		$old_total     = $new_total;
		$order_coupons = $order->get_coupons();
		if ( ! empty( $order_coupons ) ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( self::is_a_free_item( $item ) ) {
					$coupon_code = $item['wbte_sc_free_gift_coupon'];

					if ( ! empty( $coupon_code ) ) {
						$coupon_code = wc_format_coupon_code( $coupon_code );
						$coupon      = new WC_Coupon( $coupon_code );

						if ( $coupon ) {
							$coupon_id = $coupon->get_id();

							$item_id = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
							$product = wc_get_product( $item_id );

							$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );
							$new_total -= ( $discount * $item['quantity'] );
						}
					}
				}
				if ( isset( $item['wbte_sc_cheap_exp_discount'] ) ) {
					$new_total -= $item['wbte_sc_cheap_exp_discount'];
				}
			}
			if ( $new_total !== $old_total ) {
				$new_total = max( 0, $new_total );
				$new_total = round( $new_total, wc_get_price_decimals() );
				$order->set_total( $new_total );
			}
		}
	}

	/**
	 * To check whether the item is applicable for the coupon or not.
	 *
	 * @since 3.0.0
	 * @param array $item Cart item.
	 * @param array $args Coupon arguments( coupon_products, coupon_categories, coupon_exclude_products, coupon_exclude_categories ).
	 * @return bool      True if the item is applicable for the coupon, otherwise false.
	 */
	public static function is_coupon_applicable_product( $item, $args = array() ) {

		$coupon_products           = isset( $args['coupon_products'] ) && is_array( $args['coupon_products'] ) ? $args['coupon_products'] : array();
		$coupon_categories         = isset( $args['coupon_categories'] ) && is_array( $args['coupon_categories'] ) ? $args['coupon_categories'] : array();
		$coupon_exclude_products   = isset( $args['coupon_exclude_products'] ) && is_array( $args['coupon_exclude_products'] ) ? $args['coupon_exclude_products'] : array();
		$coupon_exclude_categories = isset( $args['coupon_exclude_categories'] ) && is_array( $args['coupon_exclude_categories'] ) ? $args['coupon_exclude_categories'] : array();

		$is_product_restriction_enabled  = count( $coupon_products ) > 0;
		$is_category_restriction_enabled = count( $coupon_categories ) > 0;
		$is_exclude_product_enabled      = count( $coupon_exclude_products ) > 0;
		$is_exclude_category_enabled     = count( $coupon_exclude_categories ) > 0;

		$on_sale_non_sale = $args['on_sale_non_sale'] ?? '';

		if( ! empty( $on_sale_non_sale ) ) {
			if( 'wbte_sc_bogo_on_sale' === $on_sale_non_sale && ! $item['data']->is_on_sale() ) {
				return false;
			}
			if( 'wbte_sc_bogo_on_non_sale' === $on_sale_non_sale && $item['data']->is_on_sale() ) {
				return false;
			}
		}

		// If no restrictions are there, then return true.
		if ( ! $is_product_restriction_enabled && ! $is_category_restriction_enabled && ! $is_exclude_product_enabled && ! $is_exclude_category_enabled ) {
			return true;
		}

		$_product_id = 0 < $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
		$_parent_id  = 0 < $item['variation_id'] ? $item['product_id'] : 0;

		// Enabled exclude for coupons in product edit page.
		if ( 'yes' === get_post_meta( 0 < $_parent_id ? $_parent_id : $_product_id, '_wt_disabled_for_coupons', true ) ) {
			return false;
		}

		$is_a_matching_product = false;

		if ( $is_product_restriction_enabled ) {
			if ( in_array( $_product_id, $coupon_products, true ) || in_array( $_parent_id, $coupon_products, true ) ) {
				$is_a_matching_product = true;
			}
		}

		if ( ! $is_a_matching_product && $is_category_restriction_enabled ) {
			$product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids( 0 < $_parent_id ? $_parent_id : $_product_id );

			if ( 0 < count( array_intersect( $coupon_categories, $product_cats ) ) ) {
				$is_a_matching_product = true;
			}
		}

		if ( ! $is_a_matching_product ) {
			if ( ! empty( $coupon_exclude_categories ) || ! empty( $coupon_exclude_products ) ) {
				$product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids( 0 < $_parent_id ? $_parent_id : $_product_id );

				if (
					! in_array( $_product_id, $coupon_exclude_products, true )
					&& ! in_array( $_parent_id, $coupon_exclude_products, true )
					&& 0 === count( array_intersect( $coupon_exclude_categories, $product_cats ) )
				) {
					$is_a_matching_product = true;
				}
			} elseif ( ! $is_product_restriction_enabled && ! $is_category_restriction_enabled ) {
				$is_a_matching_product = true;
			}
		} elseif (
				! empty( $coupon_exclude_products )
				&& ( in_array( $_product_id, $coupon_exclude_products, true )
				|| in_array( $_parent_id, $coupon_exclude_products, true ) )
			) {

				$is_a_matching_product = false;
		}

		return $is_a_matching_product;
	}

	/**
	 * Alter price of product for coupon validation check.
	 * For store credit, price will not get by get_price function, so at that time, take price from cart_item. Also added a hook to add compatibility for other plugins.
	 *
	 * @since 3.0.0
	 * @param  float $item_price Price of the product.
	 * @param  array $item       Cart item data.
	 * @param  int   $coupon_id   Coupon ID.
	 * @return float             Price of the product.
	 */
	private static function alter_price_for_validation_check( $item_price, $item, $coupon_id ) {

		$item_price = empty( $item_price ) && isset( $item['wt_credit_amount'] ) ? $item['wt_credit_amount'] : $item_price;

		return apply_filters( 'wbte_sc_bogo_alter_item_price_for_coupon_validation', $item_price, $item, $coupon_id ); // Allow other plugins to alter item price for coupon validation if price not get as expected.
	}

	/**
	 * Get the discount amount for cheapest/expensive giveaway item.
	 *
	 * @since 3.1.0
	 * @param  float  $discount           Discount amount.
	 * @param  float  $discounting_amount Discounting amount.
	 * @param  array  $cart_item          Cart item.
	 * @param  bool   $single             If true, then discount amount for single quantity.
	 * @param  object $coupon             Coupon object.
	 * @return float                      Discount amount.
	 */
	public static function get_cheap_exp_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

		if ( ! self::is_bogo( $coupon->get_id() ) || self::is_bxgx( $coupon->get_id() ) ) {
			return $discount;
		}

		$coupon_code = $coupon->get_code();

		if ( ! isset( self::$bogo_cheap_exp_coupon_data[ $coupon_code ] ) ) {
			self::calculate_cheap_exp_discount_amount( $coupon );
		}

		if ( ! self::is_apply_tax_on_discounted_price() ) {
			return 0;
		}

		if ( isset( self::$bogo_cheap_exp_coupon_data[ $coupon_code ][ $cart_item['key'] ] ) ) {
			$discount_data = self::$bogo_cheap_exp_coupon_data[ $coupon_code ][ $cart_item['key'] ];
			return ( $discount_data['discount'] * $discount_data['quantity'] ) / $cart_item['quantity'];
		}

		return 0;
	}

	/**
	 * Calculate the discount amount for cheapest/expensive giveaway item.
	 *
	 * @since 3.1.0
	 * @param WC_Coupon $coupon  Coupon object.
	 */
	private static function calculate_cheap_exp_discount_amount( $coupon ) {

		$cart     = self::get_cart_object();
		$discount = 0;

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$coupon_id = $coupon->get_id();

		$cheap_exp_type = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_cheap_exp' );

		$coupon_products            = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) ) );
		$coupon_excluded_products   = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) ) );
		$coupon_categories          = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) ) );
		$coupon_excluded_categories = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) ) );

		// Assigning $args before for loop to avoid multiple time fetching restriction data.
		$args = array(
			'coupon_products'           => $coupon_products,
			'coupon_categories'         => $coupon_categories,
			'coupon_exclude_products'   => $coupon_excluded_products,
			'coupon_exclude_categories' => $coupon_excluded_categories,
			'on_sale_non_sale'          => self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' )
		);

		$eligible_products = array();

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! self::is_a_free_item( $cart_item )
				&& ! self::is_old_bogo_free_product( $cart_item )
				&& ! isset( $cart_item['wt_credit_amount'] )
				&& self::is_coupon_applicable_product( $cart_item, $args )
			) {

				$discounted_qty = isset( self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discounted_qty'] )
				? self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discounted_qty']
				: 0;

				$remaining_qty = $cart_item['quantity'] - $discounted_qty;

				if ( $remaining_qty > 0 ) {
					$cart_item_price = self::alter_price_for_validation_check( $cart_item['data']->get_price(), $cart_item, $coupon_id );
					$item_id         = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];

					$eligible_products[] = array(
						'key'      => $cart_item['key'],
						'price'    => $cart_item_price,
						'quantity' => $remaining_qty,
						'item_id'  => $item_id,
					);
				}
			}
		}

		// Sort products by price.
		if ( 'wbte_sc_bogo_customer_gets_cheapest' === $cheap_exp_type ) {
			usort(
				$eligible_products,
				function ( $a, $b ) {
					return $a['price'] <=> $b['price']; // Sort ascending for cheapest.
				}
			);
		} else {
			usort(
				$eligible_products,
				function ( $a, $b ) {
					return $b['price'] <=> $a['price']; // Sort descending for most expensive.
				}
			);
		}

		$giveaway_qty = self::get_bogo_eligible_qty( $coupon_id );

		$remaining_qty = $giveaway_qty;
		$discounts     = array();

		$coupon_code = $coupon->get_code();
		$bogo_title  = $coupon_code;
		if ( self::is_auto_bogo( $coupon_id ) ) {
			$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
		}

		foreach ( $eligible_products as $product ) {

			if ( $remaining_qty <= 0 ) {
				break;
			}

			$product_obj = wc_get_product( $product['item_id'] );
			$discount    = self::$bogo_discount_amount_for_products[ $coupon_id ][ $product['item_id'] ]
				?? self::get_available_discount_for_giveaway_product( $coupon_id, $product_obj );

			if ( ( $product['price'] - $discount ) >= $product['price'] ) {
				continue;
			}

			$qty_to_discount = min( $remaining_qty, $product['quantity'] );
			$remaining_qty  -= $qty_to_discount;

			$discounts[ $product['key'] ] = array(
				'discount' => $discount,
				'quantity' => $qty_to_discount,
			);

			if ( ! isset( self::$bogo_cheap_exp_checked_products[ $product['key'] ] ) ) {
				self::$bogo_cheap_exp_checked_products[ $product['key'] ] = array(
					'discounted_qty' => $qty_to_discount,
					'discount'       => $discount * $qty_to_discount,
					'coupons'        => array( $bogo_title ),
					'coupon_codes'   => array( $coupon_code ),
				);
			} else {
				self::$bogo_cheap_exp_checked_products[ $product['key'] ]['discounted_qty'] += $qty_to_discount;
				self::$bogo_cheap_exp_checked_products[ $product['key'] ]['discount']       += ( $discount * $qty_to_discount );
				self::$bogo_cheap_exp_checked_products[ $product['key'] ]['coupons'][]       = $bogo_title;
				self::$bogo_cheap_exp_checked_products[ $product['key'] ]['coupon_codes'][]  = $coupon_code;
			}
		}

		self::$bogo_cheap_exp_coupon_data[ $coupon_code ] = $discounts;

		$total_discount = 0;
		$total_qty      = 0;
		foreach ( $discounts as $discount_data ) {
			$total_discount += ( $discount_data['discount'] * $discount_data['quantity'] );
			$total_qty      += $discount_data['quantity'];
		}
		self::$bogo_discounts[ $coupon_code ] = $total_discount;

		if ( ! empty( $discounts ) && $coupon_code === self::$is_cheap_exp_coupon_applied ) {

			$cheap_exp_msg = self::get_general_settings_value( 'wbte_sc_bogo_general_cheap_exp_added_message' );
			$cheap_exp_msg = str_replace( array( '{qty}', '{bogo_title}' ), array( $total_qty, self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' ) ), $cheap_exp_msg );
			wc_add_notice( $cheap_exp_msg, 'success' );
		}

		WC()->session->set( self::$cheap_exp_checked_products_session_id, self::$bogo_cheap_exp_checked_products );
	}

	/**
	 * Action function for displaying description for Giveaway product on cart page
	 *
	 *  @since  3.1.0
	 *  @param  array $cart_item    Cart item array.
	 */
	public static function display_giveaway_product_description_cheap_exp( $cart_item ) {

		if ( self::is_a_free_item( $cart_item )
			|| self::is_old_bogo_free_product( $cart_item )
			|| ! isset( self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ] )
		) {
			return;
		}

		$coupons      = self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['coupons'];
		$coupon_codes = self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['coupon_codes'];
		if ( ! empty( $coupon_codes ) ) {
			echo wp_kses_post( apply_filters( 'wbte_sc_alter_bogo_cheap_exp_cart_lineitem_text', '<p class="wbte_sc_bogo_msg_under_free_gift">' . esc_html__( 'Offer: ', 'wt-smart-coupons-for-woocommerce-pro' ) . implode( ', ', $coupons ) . '</p>', $coupon_codes, $cart_item ) );
		}
	}

	/**
	 * Calculate the Cart Total after reducing cheapest/expensive product price.
	 * For BOGO with 'Apply tax on original price' option enabled.
	 *
	 *  @since 3.1.0.
	 *  @param  object $cart_object Cart object.
	 */
	public static function discounted_calculated_total_cheap_exp( $cart_object ) {

		if ( self::is_apply_tax_on_discounted_price() ) {
			return;
		}
		$new_total = $cart_object->get_total( 'edit' );

		if ( ! empty( self::$bogo_cheap_exp_coupon_data ) ) {

			foreach ( self::$bogo_cheap_exp_coupon_data as $coupon_code => $coupon_data ) {

				if ( isset( self::$bogo_discounts[ $coupon_code ] ) ) {
					$new_total -= self::$bogo_discounts[ $coupon_code ];
				}
			}

			$new_total = round( $new_total, $cart_object->dp );
			$cart_object->set_total( $new_total );
		}
	}

	/**
	 * To add discounted price with quantity for giveaway item.
	 * For cheapest/expensive giveaway item.
	 *
	 * @since 3.1.0
	 * @param  string $price        Cart item price HTML.
	 * @param  array  $cart_item    Cart item array.
	 * @return string               Altered cart item price HTML.
	 */
	public static function update_cart_item_price_cheap_exp( $price, $cart_item ) {

		if ( self::is_a_free_item( $cart_item )
			|| self::is_old_bogo_free_product( $cart_item )
			|| ! isset( self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discount'] )
		) {
			return $price;
		}

		$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		$product    = wc_get_product( $item_id );
		$item_price = self::get_product_price( $product ) * $cart_item['quantity'];

		$price = '<span>' . $price . '</span> <br /> <span class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'wt-smart-coupons-for-woocommerce-pro' ) . wp_kses_post( wc_price( $item_price - self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discount'] ) ) . '</span>';

		return $price;
	}

	/**
	 * Get the discount amount for bxgx giveaway item.
	 *
	 * @since 3.1.0
	 * @param  float  $discount           Discount amount.
	 * @param  float  $discounting_amount Discounting amount.
	 * @param  array  $cart_item          Cart item.
	 * @param  bool   $single             If true, then discount amount for single quantity.
	 * @param  object $coupon             Coupon object.
	 * @return float                      Discount amount.
	 */
	public static function get_bxgx_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

		$coupon_id = $coupon->get_id();
		if ( ! self::is_bogo( $coupon_id ) || ! self::is_bxgx( $coupon_id ) || ! self::is_a_free_item( $cart_item, $coupon->get_code() ) || ! self::is_apply_tax_on_discounted_price() ) {
			return $discount;
		}

		$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		if( isset( self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ) ){
			$discount = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ];
		}
		else{
			$product    = wc_get_product( $item_id );
			$discount   = self::get_available_discount_for_giveaway_product( $coupon_id, $product );
		}
		return $discount;
	}

	/**
	 * Alter the discount amount for giveaway item for normal coupons.
	 *
	 * @since 3.1.0
	 * @param  float  $discount           Discount amount.
	 * @param  float  $discounting_amount Discounting amount.
	 * @param  array  $cart_item          Cart item.
	 * @param  bool   $single             If true, then discount amount for single quantity.
	 * @param  object $coupon             Coupon object.
	 * @return float                      Discount amount.
	 */
	public static function alter_discount_amount_for_giveaway_products( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

		if ( self::is_bogo( $coupon->get_id() ) ) {
			return $discount;
		}

		$free_product_price = self::get_product_price( $cart_item['data'] );
		$discount_type      = $coupon->get_discount_type();

		$is_free_item         = self::is_a_free_item( $cart_item );
		$is_cheap_exp_product = isset( self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ] );

		if ( $is_free_item || $is_cheap_exp_product ) {

			if ( $is_free_item ) {
				$free_product_new_price = ( $free_product_price - $cart_item['wbte_sc_bogo_discount'] ) * $cart_item['quantity'];
			} elseif ( $is_cheap_exp_product ) {
				$free_product_new_price = ( $free_product_price * $cart_item['quantity'] ) - self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discount'];
			}

			if ( isset( self::$giveaway_discounted_amount[ $cart_item['key'] ] ) ) {
				$free_product_new_price = self::$giveaway_discounted_amount[ $cart_item['key'] ];
			}

			switch ( $discount_type ) {
				case 'percent':
					$discounting_perc = ( $discount * 100 ) / $discounting_amount;
					$new_discount     = $free_product_new_price * ( $discounting_perc / 100 );
					break;

				case 'fixed_cart':
				case 'fixed_product':
					if( $is_cheap_exp_product ){
						$balance_qty = $cart_item['quantity'] - self::$bogo_cheap_exp_checked_products[ $cart_item['key'] ]['discounted_qty'];
						$discount_individual = $discount / $cart_item['quantity'];
						$new_discount = $discount_individual * $balance_qty;
					}
					else{
						$new_discount = 0;
					}
					break;

				default:
					$new_discount = apply_filters( 'wbte_sc_alter_get_discount_amount', $discount, $discounting_amount, $cart_item, $single, $coupon );
			}

			self::$giveaway_discounted_amount[ $cart_item['key'] ] = max( $free_product_new_price - $new_discount, 0 );

			return $new_discount;
		}

		return $discount;
	}


	/**
	 * To store the coupon code of cheap/expensive coupon when applied.
	 *
	 * @since 3.1.0
	 * @param  string $coupon_code Coupon code.
	 */
	public static function cheap_exp_coupon_applied( $coupon_code ) {

		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
		if ( ! $coupon_id || ! self::is_bogo( $coupon_id ) || self::is_bxgx( $coupon_id ) ) {
			return;
		}

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return;
		}

		self::$is_cheap_exp_coupon_applied = $coupon_code;
	}

	/**
	 * To get stored cheap expensive checked products session.
	 *
	 * @since  3.1.0
	 * @return array If session available, return session data otherwise empty array.
	 */
	public static function get_cheap_exp_checked_products_session() {
		return ! is_null( WC()->session ) ? WC()->session->get( self::$cheap_exp_checked_products_session_id ) : array();
	}

	/**
	 *  To remove cheap expensive checked products session.
	 *  Remove session if removed coupon is cheap/expensive coupon and no other cheap/expensive coupon in the cart. If other cheap/expensive coupon available in the cart, session will be updated from the 'calculate_cheap_exp_discount_amount' function.
	 *
	 *  @since  3.1.0
	 *  @param	string $coupon_code    Coupon code.
	 */
	public static function remove_cheap_exp_checked_products_session( $coupon_code ) {
		
		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return;
		}
		$found = false;
		$applied_coupons = $cart->get_applied_coupons();
		foreach ( $applied_coupons as $coupon_code ){
			$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
			if ( self::is_bogo( $coupon_id ) && ! self::is_bxgx( $coupon_id ) ) {
				$found = true; // Other cheap/expensive coupon available in the cart.
				break;
			}
		}
		if ( ! $found ) {
			WC()->session->set( self::$cheap_exp_checked_products_session_id, array() );
		}
	}

}

Wbte_Smart_Coupon_Bogo_Public::get_instance();
