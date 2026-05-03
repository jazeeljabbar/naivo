<?php
/**
 * BOGO common section
 *
 * @link
 * @since 3.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The common functionality of new BOGO module.
 */
class Wbte_Smart_Coupon_Bogo_Common {

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
	 * Array of meta fields
	 *
	 * @var array  $meta_arr Array of meta fields used when saving BOGO, array key is ID of fields, and value is properties of fields.
	 */
	public static $meta_arr = array();

	/**
	 * Array of coupon meta fields
	 *
	 * @var array $coupon_meta_arr To store all coupon meta fields.
	 */
	public static $coupon_meta_arr = array();

	/**
	 * Array of BOGO general settings fields.
	 *
	 * @var array $general_settings_arr Array of BOGO general settings fields.
	 */
	public static $general_settings_arr = array();

	/**
	 * BOGO coupon type name
	 *
	 * @var string $bogo_coupon_type_name BOGO coupon type name.
	 */
	public static $bogo_coupon_type_name = 'wbte_sc_bogo';

	/**
	 * To store discount applied for a product
	 * Array key is coupon code and value is array of product id and discount amount.
	 *
	 * @var array $bogo_discount_amount_for_products array of discount amount for each product.
	 */
	public static $bogo_discount_amount_for_products = array();

	/**
	 * Constructor function of the class
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		self::$meta_arr = array(
			'wbte_sc_bogo_type'                         => array(
				'default' => 'wbte_sc_bogo_bxgx',
				'type'    => 'text',
			),
			// Step 2.
			'wbte_sc_bogo_triggers_when'                => array(
				'default' => 'wbte_sc_bogo_triggers_qty',
				'type'    => 'text',
			),
			'_wbte_sc_bogo_min_amount'                  => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wbte_sc_bogo_max_amount'                  => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wbte_sc_bogo_min_qty'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_max_qty'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'wbte_sc_bogo_product_ids'                  => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wt_product_condition'                     => array(
				'default' => 'or',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_exclude_product_ids'          => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wbte_sc_product_cat_condition'            => array(
				'default' => 'and',
				'type'    => 'text',
			),
			'wbte_sc_bogo_product_categories'           => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wt_category_condition'                    => array(
				'default' => 'or',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_exclude_product_categories'   => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			// Step 2 - Optional conditions.
			'_wbte_sc_min_qty_each'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_max_qty_each'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_min_qty_add'                 => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_max_qty_add'                 => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_min_amount_adtl'                 => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wbte_sc_bogo_max_amount_adtl'                 => array(
				'default' => '',
				'type'    => 'float',
			),
			'wbte_sc_bogo_adtl_subtotal_from'             => array(
				'default' => '',
				'type'    => 'text',
			),
			'usage_limit_per_user'                      => array(
				'default' => '',
				'type'    => 'int',
			),
			'usage_limit'                               => array(
				'default' => '',
				'type'    => 'int',
			),
			'customer_email'                            => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'array',
			),
			'_wt_sc_user_roles' => array(
                'default'   => array(),
                'type'      => 'text_arr',
                'save_as'   => 'text',
            ),
			'individual_use'                             => array(
				'default' => 'no',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wt_sc_payment_methods' => array(
                'default'   => array(),
                'type'      => 'text_arr',
                'save_as'   => 'text',
            ),
			'_wt_sc_shipping_methods' => array(
                'default'   => array(), 
                'type'      => 'text_arr', 
                'save_as'   => 'text', 
            ),
			'_wt_coupon_available_location' => array(
                'default'   => array(),
                'type'      => 'text_arr',
                'save_as'   => 'text',
            ),
			'_wt_coupon_available_location_inc_exc' => array( 
                'default'   => 'include',
                'type'      => 'text',
                'save_as'   => 'text',
            ),
			'_wt_need_check_location_in' => array(
                'default' => 'billing',
                'type' => 'text',
            ),
			'wbte_sc_bogo_on_sale_non_sale'             => array(
				'default' => '',
				'type'    => 'text',
			),
			// Step 2 - Optional conditions - Purchase history.
			'wbte_sc_bogo_purchase_history'             => array(
				'default' => '',
				'type'    => 'text',
			),
			'nth_coupon_no_of_coupon_condition'         => array(
				'default' => 'please_select',
				'type'    => 'text',
			),
			'wt_nth_order_no_of_orders'                 => array(
				'default' => 1,
				'type'    => 'int',
			),
			'wt_nth_order_order_total'                  => array(
				'default' => 0,
				'type'    => 'float',
			),
			'_nth_coupon_order_date_or_days'                => array(
				'default' => 'days',
				'type'    => 'text',
			),
			'_wt_sc_nth_order_within_days' =>  array(
				'default'   => '', 
				'type'      => 'absint',
			),
			'_wt_sc_nth_order_date_from' =>  array(
				'default'   => '', 
				'type'      => 'text',
			),
			'_wt_sc_nth_order_date_to' =>  array(
				'default'   => '', 
				'type'      => 'text',
			),
			'wt_order_Status_need_to_count' =>  array(
				'default'   => array(), 
				'type'      => 'text_arr',
			),
			'_wt_sc_nth_order_products' =>  array(
				'default'   => array(), 
				'type'      => 'int_arr',
			),
			// Step 1.
			'wbte_sc_bogo_customer_gets'                => array(
				'default' => 'specific_product',
				'type'    => 'text',
			),
			'wbte_sc_bogo_free_product_ids'             => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_gets_product_condition'       => array(
				'default' => 'all',
				'type'    => 'text',
			),
			'wbte_sc_bogo_free_category_ids'            => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_customer_gets_cheap_exp'      => array(
				'default' => 'wbte_sc_bogo_customer_gets_cheapest',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_qty'            => array(
				'default' => '',
				'type'    => 'int',
			),
			'wbte_sc_bogo_customer_gets_with'           => array(
				'default' => 'wbte_sc_bogo_customer_gets_with_discount',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_discount_type'  => array(
				'default' => 'wbte_sc_bogo_customer_gets_free',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_final_price'    => array(
				'default' => '',
				'type'    => 'float',
			),
			'wbte_sc_bogo_customer_gets_discount_perc'  => array(
				'default' => '',
				'type'    => 'float',
			),
			'wbte_sc_bogo_customer_gets_discount_price' => array(
				'default' => '',
				'type'    => 'float',
			),
			'free_shipping'                             => array(
				'default' => 'no',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			// Step 3.
			'wbte_sc_bogo_apply_offer'                  => array(
				'default' => 'wbte_sc_bogo_apply_once',
				'type'    => 'text',
			),
			'wbte_sc_bogo_repeatedly_times'             => array(
				'default' => '',
				'type'    => 'int',
			),
			'wbte_sc_bogo_apply_custom_min'             => array(
				'default' => '',
				'type'    => 'int_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_apply_custom_max'             => array(
				'default' => '',
				'type'    => 'int_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_apply_custom_times'           => array(
				'default' => '',
				'type'    => 'int_arr',
				'save_as' => 'text',
			),
			// Edit general.
			'wbte_sc_bogo_coupon_name'                  => array(
				'default' => '',
				'type'    => 'text',
			),
			'wbte_sc_bogo_code_condition'               => array(
				'default' => 'wbte_sc_bogo_code_auto',
				'type'    => 'text',
			),
			'_wc_make_coupon_available'                 => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_schedule'                     => array(
				'default' => 'no',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wt_coupon_enable_days'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wt_coupon_expiry_in_days'                  => array(
				'default' => '',
				'type'    => 'int',
			),
			// Common.
			'wbte_sc_bogo_created_on_sc_bogo'           => array(
				'default' => 1,
				'type'    => 'int',
			),
		);

		add_filter(
			'woocommerce_coupon_discount_types',
			array(
				$this,
				'add_bogo_coupon_type',
			)
		);
	}

	/**
	 * Get Instance
	 *
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wbte_Smart_Coupon_Bogo_Common();
		}
		return self::$instance;
	}

	/**
	 * Add BOGO coupon type
	 * Return default discount types if current page in provided restricted page or in coupon edit page.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $discount_types Discount types.
	 * @return array                 Discount types.
	 */
	public function add_bogo_coupon_type( $discount_types ) {
		$restricted_pages = ( class_exists( 'Wt_Smart_Coupon_Common' ) && method_exists( 'Wt_Smart_Coupon_Common', 'bogo_restricted_pages' ) ) ? Wt_Smart_Coupon_Common::bogo_restricted_pages() : array();

		if ( self::is_new_bogo_activated() ) {
			if ( (
					isset( $_GET['page'] ) && in_array( $_GET['page'], $restricted_pages, true )
				) ||
				(
					isset( $_GET['post_type'] ) && 'shop_coupon' === $_GET['post_type'] && ! isset( $_GET['wbte_sc_auto_apply'] )
				) ||
				(
					isset( $_GET['post'] ) && 'shop_coupon' === get_post_type( absint( wp_unslash( $_GET['post'] ) ) )
				)
			) {
				return $discount_types;
			}
			$discount_types[ self::$bogo_coupon_type_name ] = __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' );
		}
		return $discount_types;
	}

	/**
	 * Is given coupon is BOGO.
	 *
	 * @since 3.0.0
	 *
	 * @param  int	  $coupon_id Coupon id.
	 * @return boolean           True if the coupon is BOGO, false otherwise.
	 */
	public static function is_bogo( $coupon_id ) {
		return self::$bogo_coupon_type_name === get_post_meta( $coupon_id, 'discount_type', true );
	}

	/**
	 * Is new BOGO activated
	 *
	 * @since 3.0.0
	 * @return bool True if new BOGO is activated, false otherwise.
	 */
	public static function is_new_bogo_activated() {
		return (bool) get_option( 'wbte_sc_new_bogo_actvated' );
	}

	/**
	 * To get all coupon meta and store in global static variable.
	 *
	 * @since 3.0.0
	 * @param int $coupon_id Coupon id.
	 */
	public static function get_all_coupon_meta( $coupon_id ) {
		self::$coupon_meta_arr              = (array) get_post_meta( $coupon_id );
		self::$coupon_meta_arr['coupon_id'] = $coupon_id;
	}

	/**
	 * To get coupon meta value by key.
	 * If it is not set in global static variable, then get all coupon meta from DB and store in global static variable.
	 *
	 * @since 3.0.0
	 *
	 * @param  int    $coupon_id          Coupon id.
	 * @param  string $meta_key           Meta key.
	 * @param  mixed  $default_meta_val   Default value.
	 * @return mixed                      Meta value.
	 */
	public static function get_coupon_meta_value( $coupon_id, $meta_key, $default_meta_val = '' ) {
		if ( ! isset( self::$coupon_meta_arr['coupon_id'] ) || $coupon_id !== self::$coupon_meta_arr['coupon_id'] ) {
			self::get_all_coupon_meta( $coupon_id );
		}
		$default_vl = isset( self::$meta_arr[ $meta_key ] ) && isset( self::$meta_arr[ $meta_key ]['default'] ) ? self::$meta_arr[ $meta_key ]['default'] : $default_meta_val;
		return isset( self::$coupon_meta_arr[ $meta_key ] ) ? self::$coupon_meta_arr[ $meta_key ][0] : $default_vl;
	}

	/**
	 * To get BOGO general settings value by key.
	 * If it is not set in global static variable, then get all general settings from DB and store in global static variable.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $field_name Field name.
	 * @return string             Field value
	 */
	public static function get_general_settings_value( $field_name ) {
		if ( empty( self::$general_settings_arr ) ) {
			self::$general_settings_arr = get_option( 'wbte_sc_bogo_general_settings', array() );
		}
		$default_fields = array(
			'wbte_sc_bogo_apply_tax_on'                    => 'wbte_sc_bogo_apply_tax_on_discount',
			'wbte_sc_bogo_auto_add_giveaway'               => 'wbte_sc_bogo_auto_add_full_giveaway',
			'wbte_sc_bogo_general_discount_apply_message'  => __( '{bogo_title} applied!', 'wt-smart-coupons-for-woocommerce-pro' ),
			'wbte_sc_bogo_general_product_added_message'   => __( 'Giveaway added to cart!', 'wt-smart-coupons-for-woocommerce-pro' ),
			'wbte_sc_bogo_general_cheap_exp_added_message' => __( 'Hooray! Amazing discounts have been applied to {qty} item(s) in your cart!', 'wt-smart-coupons-for-woocommerce-pro' ),
			'wbte_sc_bogo_general_discount_under_product_msg' => '{bogo_title}',
			'wbte_sc_bogo_general_apply_choose_product_title' => __( 'Choose product', 'wt-smart-coupons-for-woocommerce-pro' ),
			'wbte_sc_bogo_general_select_any_from_store'   => __( 'Woohoo! Add any product to your cart, and it’s on us! Enjoy your freebie!', 'wt-smart-coupons-for-woocommerce-pro' ),
			'wbte_sc_bogo_general_select_from_specific_category' => __( 'Woohoo! Add any product from the {category_name} to your cart, and it’s on us! Enjoy your freebie!', 'wt-smart-coupons-for-woocommerce-pro' ),
		);

		return self::$general_settings_arr[ $field_name ] ?? ( $default_fields[ $field_name ] ?? '' );
	}

	/**
	 *  Get giveaway products id from coupon meta
	 *
	 *  @since 3.0.0
	 *  @param int $post_id Coupon id.
	 *  @return array of giveaway product ids
	 */
	public static function get_giveaway_products( $post_id ) {
		$free_product_ids    = self::get_instance()->get_coupon_meta_value( $post_id, 'wbte_sc_bogo_free_product_ids' );
		$free_product_id_arr = array();
		if ( $free_product_ids && is_string( $free_product_ids ) ) {
			$free_product_id_arr = explode( ',', $free_product_ids );
		}
		return $free_product_id_arr;
	}

	/**
	 *  Get customized notification messages
	 *
	 *  @since  3.0.0
	 *  @param  string $key    Unique key for the message.
	 *  @param  array  $args   Values for the function: Coupon code, Placeholders etc.
	 *  @return string      Empty string when message was disabled otherwise the message
	 */
	public static function get_customized_text( $key, $args = array() ) {
		return Wt_Smart_Coupon_Public::get_customized_text( $key, $args );
	}

	/**
	 * To check if the coupon is triggered based on subtotal.
	 *
	 * @since  3.0.0
	 * @param  int $coupon_id Coupon id.
	 * @return bool           True if the coupon is triggered based on subtotal, false otherwise(that is based on qty).
	 */
	protected static function is_coupon_based_on_subtotal( $coupon_id ) {
		return 'wbte_sc_bogo_triggers_subtotal' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );
	}

	/**
	 *  Check whether the coupon is auto BOGO or not.
	 *
	 *  @since   3.0.0
	 *  @param   int $coupon_id    Coupon id.
	 *  @return  boolean           True when auto BOGO otherwise false.
	 */
	public static function is_auto_bogo( $coupon_id ) {
		return 'wbte_sc_bogo_code_auto' === get_post_meta( $coupon_id, 'wbte_sc_bogo_code_condition', true );
	}

	/**
	 *  Is apply discount before tax is enabled
	 *
	 *  @since  3.0.0
	 *  @return bool    True when enabled otherwise False
	 */
	public static function is_apply_tax_on_discounted_price() {
		return 'wbte_sc_bogo_apply_tax_on_discount' === self::get_general_settings_value( 'wbte_sc_bogo_apply_tax_on' );
	}

	/**
	 *  To get the available discount for the giveaway product.
	 *  If product is on sale, then sale price will be considered, otherwise regular price. If both are empty, then get_price will be considered. A filter hook is also available to alter the price.
	 *
	 *  @since  3.0.0
	 *  @param  object $product    Product object.
	 *  @return float   Discount amount
	 */
	public static function get_product_price( $product ) {
		$product_price = 0.0;
		
		if ( $product instanceof WC_Product ) {
			$product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();

			if ( empty( $product_price ) ) {
				$product_price = $product->get_price();
			}
		}

		$product_price = (float) $product_price;

		return apply_filters( 'wt_sc_alter_giveaway_product_price', $product_price, $product );
	}

	/**
	 * To get discount amount for giveaway product with the given coupon.
	 * Also store the discount amount in a static variable to avoid multiple calculations.
	 *
	 * @since 3.0.0
	 * @param  int    $coupon_id Coupon id.
	 * @param  object $product   Product object.
	 * @return float             Discount amount.
	 */
	public static function get_available_discount_for_giveaway_product( $coupon_id, $product ) {
		$product_id = $product->get_id();

		// Check if the discount is already calculated for this product and coupon.
		if ( isset( self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] ) ) {
			return self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ];
		}

		$item_price     = self::get_product_price( $product );
		$discount_price = 0.0;
		$coupon         = new WC_Coupon( $coupon_id );
		if ( ! $coupon || ! self::is_bogo( $coupon_id ) ) {
			return $discount_price;
		}

		$discount_with = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_with' );
		if ( 'wbte_sc_bogo_customer_gets_with_discount' === $discount_with ) {
			$discount_type = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_type' );

			if ( 'wbte_sc_bogo_customer_gets_free' === $discount_type ) {
				$discount_price = $item_price;
			} elseif ( 'wbte_sc_bogo_customer_gets_with_perc_discount' === $discount_type ) {
				$discount_perc = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_perc' );

				if ( $discount_perc > 100 ) {
					$discount_perc = 100;
				} elseif ( $discount_perc < 0 ) {
					$discount_perc = 0;
				}

				$discount_price = ( $item_price * $discount_perc ) / 100;
			} else {
				$discount_price = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_price' );

				if ( $discount_price >= $item_price ) {
					$discount_price = $item_price;
				}
			}
		} else {
			$final_price = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_final_price' );
			$discount_price = ( $final_price >= $item_price ) ? 0.0 : ( $item_price - $final_price );
		}

		if ( ! isset( self::$bogo_discount_amount_for_products[ $coupon_id ] ) ) {
			self::$bogo_discount_amount_for_products[ $coupon_id ] = array();
		}

		self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] = $discount_price;
		return (float) $discount_price;
	}

	/**
	 * Prepare giveaway data for order detail section.
	 *
	 * @since  3.0.0
	 * @since  3.1.0  Added for cheap/expensive discount.
	 * @param  int    $order_item_id Order item id.
	 * @param  object $order_item    Order item.
	 * @return mixed                 False if not a giveaway product or apply tax on discounted price, otherwise the value to display.
	 */
	public static function prepare_giveaway_info_for_order( $order_item_id, $order_item ) {

		if ( self::is_apply_tax_on_discounted_price() ) {
			return false;
		}
		if ( wc_get_order_item_meta( $order_item_id, 'wbte_sc_free_product', true ) === 'wbte_sc_giveaway_product' ) {
			$coupon_code = wc_get_order_item_meta( $order_item_id, 'wbte_sc_free_gift_coupon', true );
			$coupon_id   = wc_get_coupon_id_by_code( $coupon_code );

			if ( $coupon_id ) {
				$item_id = ( $order_item['variation_id'] > 0 ? $order_item['variation_id'] : $order_item['product_id'] );
				$product = wc_get_product( $item_id );
				if ( ! $product instanceof WC_Product ) {
					return false;
				}

				$discount = (float) self::get_available_discount_for_giveaway_product( $coupon_id, $product ) * $order_item['quantity'];
				return wc_price( -$discount );
			}
		}

		if ( 'wbte_sc_cheap_exp_item' === wc_get_order_item_meta( $order_item_id, 'wbte_sc_cheap_exp_item', true ) ) {
			$discount = $order_item['wbte_sc_cheap_exp_discount'];
			return wc_price( -$discount );
		}
		return false;
	}

	/**
	 * To check if the BOGO is bxgx( Buy X Get X/Y, not cheapest/expensive ).
	 *
	 * @since 3.1.0
	 * @param  int $coupon_id Coupon id.
	 * @return bool            True if the BOGO is bxgx, false otherwise.
	 */
	public static function is_bxgx( $coupon_id ) {
		return 'wbte_sc_bogo_bxgx' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_type' );
	}
}

Wbte_Smart_Coupon_Bogo_Common::get_instance();
