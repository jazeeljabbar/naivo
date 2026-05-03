<?php
/**
 * Exclude Product from coupon public section
 *
 * @link
 * @since 2.0.1
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Common module class not found so return */
if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product' ) ) {
	return;
}
if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Public' ) ) {

	/**
	 * The public functionality of Exclude product.
	 */
	class Wt_Smart_Coupon_Exclude_Product_Public extends Wt_Smart_Coupon_Exclude_Product {

		/**
		 *  Module name
		 *
		 *  @var string $module_base module name
		 */
		public $module_base = 'exclude_product';

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
		 * Constructor function of the class
		 */
		public function __construct() {
			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'set_coupon_validity_for_excluded_products' ), 12, 3 );
			add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'zero_discount_for_excluded_products' ), 12, 5 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'set_fixed_cart_not_valid_for_excluded_products' ), 10, 2 );
		}

		/**
		 * Get Instance
		 *
		 * @since 2.0.1
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Exclude_Product_Public();
			}
			return self::$instance;
		}

		/**
		 * Check validity of coupon with exclude product.
		 *
		 * @since 1.2.1
		 * @since 2.0.2 [Bug fix] Coupons applying to the products from excluded category
		 * @param  bool   $valid            Coupon validity for product.
		 * @param  object $product          Product object.
		 * @param  object $coupon           Coupon object.
		 * @return bool                     Coupon validity.
		 */
		public function set_coupon_validity_for_excluded_products( $valid, $product, $coupon ) {
			if ( $this->check_if_excluded_product( $product->get_id(), $coupon ) ) {
				$valid = false;
			}

			// Do not override valid param for WC default coupon types as WC itself validate the product and already determined whether it is valid or not.
			elseif ( $coupon->is_type( 'store_credit' ) ) {
				$valid = true;
			}

			return $valid;
		}

		/**
		 * Check if the product is a disabled product
		 *
		 * @since  1.3.3
		 * @since  1.3.4 Added checking with coupon excluded/included product list
		 * @access public
		 * @param  string $product_id Product id.
		 * @param  object $coupon     Coupon object.
		 * @return bool
		 */
		public function check_if_excluded_product( $product_id, $coupon ) {
			$excluded_products = $this->get_current_settings();

			$exclude_product = false;

			if ( $coupon->is_type( 'store_credit' ) ) {
				/**
				 *  If this coupon is restricted to some products. Then check the current product is in the list
				 */
				$coupon_allowed_product_id_arr = $coupon->get_product_ids();
				if ( count( $coupon_allowed_product_id_arr ) > 0 && ! in_array( $product_id, $coupon_allowed_product_id_arr, true ) ) {
					$exclude_product = true;
				}

				if ( ! $exclude_product ) {
					/**
					 *  If the current product is in the coupon excluded list
					 */
					$coupon_excluded_product_id_arr = $coupon->get_excluded_product_ids();
					if ( in_array( $product_id, $coupon_excluded_product_id_arr, true ) ) {
						$exclude_product = true;
					}
				}

				$disabled_products = ( isset( $excluded_products['disabled_store_credits'] ) && is_array( $excluded_products['disabled_store_credits'] ) ) ? $excluded_products['disabled_store_credits'] : array();

			} else {
				$disabled_products = ( isset( $excluded_products['disabled_products'] ) && is_array( $excluded_products['disabled_products'] ) ) ? $excluded_products['disabled_products'] : array();
			}

			$product   = wc_get_product( $product_id );
			$parent_id = $product->get_parent_id();

			if ( in_array( $product_id, $disabled_products ) || ( 0 < $parent_id && in_array( $parent_id, $disabled_products ) ) ) {
				$exclude_product = true;
			}

			return $exclude_product;
		}

		/**
		 * Add zero discount for excluded products
		 *
		 * @since  1.3.3
		 * @access public
		 * @param  float  $discount           Discount amount.
		 * @param  float  $discounting_amount Discounting amount.
		 * @param  array  $cart_item          Cart item.
		 * @param  bool   $single             If true, then discount amount for single quantity.
		 * @param  object $coupon            Coupon object.
		 * @return float                     Discount amount.
		 */
		public function zero_discount_for_excluded_products( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
			if ( isset( $cart_item['product_id'] ) ) {
				if ( $this->check_if_excluded_product( $cart_item['product_id'], $coupon ) === true ) {
					$discount = 0;
				}
			}
			return $discount;
		}

		/**
		 * Set fixed cart not valid for excluded products.
		 * If any product is excluded from coupon then set fixed cart not valid.
		 *
		 * @since 3.1.0
		 * @param  bool   $valid            Coupon validity.
		 * @param  object $coupon           Coupon object.
		 * @return bool                     Coupon validity.
		 * @throws Exception                Throw exception if exclude product in cart and coupon is fixed cart.
		 */
		public function set_fixed_cart_not_valid_for_excluded_products( $valid, $coupon ) {

			if ( ! $valid ) {
				return $valid;
			}

			if ( in_array( $coupon->get_discount_type(), array( 'fixed_cart', 'store_credit' ), true ) && apply_filters( 'wbte_sc_alter_coupon_valid_on_cart_discount_coupons', true, $coupon->get_id() ) ) {
				$cart = WC()->cart;
				if( ! is_null( $cart ) && ! $cart->is_empty() ) {
					foreach ( $cart->get_cart() as $cart_item ) {
						if ( $this->check_if_excluded_product( $cart_item['product_id'], $coupon ) ) {
							$valid = false;
							break;
						}
					}
				}
			}

			if ( ! $valid ) {
				throw new Exception( esc_html__( 'Sorry, this coupon is not applicable for selected products.', 'wt-smart-coupons-for-woocommerce-pro' ), 109 );
			}

			return $valid;
		}
	}
	Wt_Smart_Coupon_Exclude_Product_Public::get_instance();
}
