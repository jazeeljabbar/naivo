=== Smart Coupons for WooCommerce ===
Contributors: WebToffee
Version: 3.2.0
Tags: Smart coupons for WooCommerce, smart coupons, advanced coupons, WooCommerce smart coupons
Requires at least: 3.5
Requires WooCommerce: 3.5
Tested up to: 6.7
Stable tag: 3.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add advanced options to WooCommerce coupons for better WooCommerce coupons management.

== Description ==

Add advanced options to WooCommerce coupons for better WooCommerce coupons management.

Everyone loves to get more than what they pay for. Smart coupons for WooCommerce provides additional features with default WooCommerce coupons to get more conversions.

The main features of the Smart coupons for WooCommerce are


&#128312; Allow Coupon for a specific Payment method.
&#128312; Allow Coupon for a specific Shipping method.
&#128312; Allow Coupon for a specific User role.
&#128312; Duplicate coupon
&#128312; My Coupons under WooCommerce my account


In-order to duplicate a coupon, go to WooCommerce > Coupons.

Find the coupon you wish to duplicate. 

Hover over the coupon and select Duplicate.

== Need for WooCommerce Smart Coupons Plugin ==

The success of every online store depends hugely on how well they market their products. Even if your store sells higher quality products at a reasonable price, improper marketing will keep your sales always on the downside. Thus coming up with the most appropriate marketing strategies from time to time is necessary to keep your store on the move. Smart coupons for WooCommerce is an essential tool for this.

If you are looking for the smartest way to market your products you should go for - Advanced Coupons or Smart Coupons for WooCommerce. People always love getting more than what they pay for. Hence, coupons without a doubt help your store to improve sales by establishing a new customer base and by retaining your existing customers. This, in turn, arises the need for effective coupon management of your store and that is what you will accomplish by having smart coupons plugin in your store.

== How Smart Coupons or Advanced Coupons Benefits Your WooCommerce Store ==

Easily apply coupons: Each Coupon has a coupon code associated with it. Customers are required to enter this code in the allowed field for applying a coupon to their purchase. This task can be made shorter by smart coupons as it displays all the coupons available for the customer on the Cart & My Account page to easily apply them.

Absolute self- management of everything related to coupons: Everything related to coupons will be managed automatically. The issue of product coupons, coupon removal, etc will be managed on its own thereby reducing the workload of the store admin.

Promote specific payment or shipping methods: Provide coupons based on payment or shipping methods that are most likely suitable for your business needs. This could also give room for partnership options with the respective vendor from a business perspective.

Duplicate coupons- Option to duplicate coupons makes things slightly easier since you dont have to necessarily create them as long as most of the criteria is the same. Then you just need to duplictae an existing coupon and make the minor alterations.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wt-smart-coupon.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates



== Changelog ==

= 3.2.0 17-02-2025 =
* [Add] - More optional condition restrictions for new BOGO.
* [Fix] - Free products not incrementing when eligibility changed, instead displaying free products to choose.
* [Fix] - BOGO category restriction checking is failing for variable products.
* [Compatibility] - Tested OK with WooCommerce 9.6

= 3.1.0 05-12-2024 =
* [New] - Cheapest/most expensive item in the cart added to the new BOGO revamp.
* [Add] - Store link placeholder for Any from Store BOGO.
* [Fix] - Resolved early loading issue for translations.
* [Compatibility] - Tested OK with WordPress 6.7
* [Compatibility] - Tested OK with WooCommerce 9.4

= 3.0.0 11-11-2024=
* [New] Added a separate module for BOGO configuration with enhanced customization options.

= 2.4.4 29-08-2024=
* [Fix] Wrong coupon type in email.
* [Fix] Blank coupon code in gift coupon email.
* [Fix] No option to remove the attached coupon from the coupon banner page.
* [Compatibility] - Tested OK with WordPress 6.6
* [Compatibility] - Tested OK with WooCommerce 9.1

= 2.4.3 31-07-2024=
* [Fix] Product not adding to the cart with BOGO coupon, which has options 'apply automatically' enabled.
* [Fix] Auto coupon not replaced by the highest priority coupon
* [Fix] Incorrect coupon discount amount when the coupon has 'Maximum discount value' and a free product.
* [Fix] Issues when coupon cloning.
* [Fix] Console error due to block translation.
* [Compatibility] - Tested OK with WordPress 6.6
* [Compatibility] - Tested OK with WooCommerce 9.1

= 2.4.2 14-06-2024=
* [FIX] Store credit deducting before order completion while using the block checkout
* [FIX] Auto coupons are not removed when individual coupons are applied.
* [FIX] Store credit is not deducted when using block checkout and selecting 'Before applying store credit discount' in store credit settings.
* [Compatibility] - Tested OK with WordPress 6.5
* [Compatibility] - Tested OK with WooCommerce 8.9 

= 2.4.1 15-05-2024=
* [FIX] Auto coupons with a zero amount are not auto applied.
* [FIX] Translation issue in block Cart/Checkout.

= 2.4.0 06-05-2024=
* [FIX] Issue with Coupon expiry in days 
* [FIX] Usage limit once per product issue in block cart and checkout
* [FIX] Auto coupon listing issue in coupons page
* [FIX] Auto coupon priority swapping not working as intended
* [FIX] Gift coupons are not generated when the gift form is hidden in checkout when an order placed through classic checkout
* [FIX] Start/expiry time not imported
* [FIX] Store credit is not taking general settings if store credit settings are empty
* [FIX] Stop showing user-role restricted coupons for guest users
* [FIX] Remove button is visible in block cart/checkout for auto coupons
* [FIX] Non-BOGO coupons with a zero coupon amount are treated as invalid for auto coupons
* [IMPROVEMENT] Option to make gift coupons as master coupon
* [ADD] Redirect to All coupons from import coupons
* [ADD] Check all button in admin store credit template page


= 2.3.0 04-03-2024=
* [Add] - Separate listing page for auto-apply coupons.
* [Add] - Priority option for auto-apply coupons.
* [Compatibility] - With Cart/Checkout blocks.
* [Fix] - Resolved plugin update issues in the license manager.
* [Improvement] - Added an option for automatic plugin updates.
* [Improvement] - Included an option to hide/show the 'Who to send' box for gift coupons during checkout.
* [Improvement] - Introduced support for additional languages.
* [Tweak] - Removed disabled shipping options from the coupon edit checkout options.


= 2.2.0 22-12-2023=
* [Fix] - Issue when taking expiry time for auto coupons via SQL
* [Fix] - BOGO giveaway product not working in checkout.
* [Fix] - Subtotal calculation issue when `exclude sale items` is enabled.
* [Fix] - Gift card product page theme compatibility improved for the following themes. Woostify, Astra, Blockpress, Blocksy, Botiga, Hello Elementor, Moog, OceanWP, Saryu, Storefront, Twenty-Twenty Twenty-One, Twenty Twenty-Two, Twenty Twenty-Three, Twenty Twenty-Four.
* [Add] - New filter hook to bypass 'is_admin' check. Helpful when some plugins use `admin Ajax` on the front end.
* [Add] - Show linked coupon in Product listing page.
* [Tweak] - Convert existing cart item as a giveaway for specific product.


= 2.1.2 14-11-2023=
* [Fix] -  Few coupon values are not imported. Eg: Tags, Attributes, Coupon active days, Purchase history, Product condition, etc.
* [Tweak] - More hook examples were added in the hooks help section.
* [Improvement] - Block Theme Compatibility for gift card product page.


= 2.1.1  16-10-2023 =
* [Add] Option to restrict coupon usage on a particular day of the week.
* [Fix] Master coupon status not changing from `draft` to `publish` when selecting yes in 'use master code in Signup coupons'
* [Fix] If 'Use master coupon code as is' is 'yes' the user email is duplicated in allowed emails.
* [Fix] Max discount calculation issue when the limit to x items option is enabled.
* [Fix] Click to apply coupon is not working when coupon metabox added via shortcode
* [Fix] Unable to add multiple variations of a product when `apply repeatedly` is enabled on the `Specific product` giveaway.
* [Tweak] More hook examples were added in the hooks help section.
* [Improvement] BOGO added to the `Any category` option, removed the 'All category' option, and added a message for existing users.
* [Improvement] Merged all messages and showed a single message when multiple giveaways were added.
* [Improvement] Cart item text for giveaway updated.
* [Improvement] UI updated for hour and minute fields in coupon start/expiry section.
* [Improvement] UI updated for the `apply repeatedly` section.
* [Compatibility] Product category search isn't working when the StoreApps smart coupon plugin is activated.
* [Compatibility] 'Select2 selected' item color issue in WooCcommerce 8.1


= 2.1.0  31-08-2023 =
* [Add] - Option to show only current cart eligible coupons in Cart/Checkout pages
* [Add] - Gift card email last send date on admin order edit screen.
* [Add] - Order notes are added on each gift card email sent.
* [Add] - New coupon restrict option: Coupon not available if the user placed less than X number of orders.
* [Add] - New coupon restrict option: Coupon not available if the coupon is used to purchase the same product before.
* [Add] - Auto add option in `specific product` and `same product in the cart` giveaway.
* [Add] - Users can now request a feature.
* [Add] - Some useful hooks and sample code snippets.
* [Compatibility] - Tested OK with WooCommerce 8.0
* [Compatibility] - Tested OK with WordPress 6.3
* [Fix] - Conflict fixed between product gift coupon meta box and gift card metabox.
* [Fix] - Coupon box style issue in email fixed.
* [Fix] - Auto apply coupons are not applied on payment method change.
* [Fix] - Coupon box spacing issue in gift coupon order meta box when multiple coupons are generated.
* [Fix] - Product restriction fields are not removed when restrictions are disabled.
* [Fix] - Maximum discount calculation issue.
* [Fix] - Gift card email scheduling issue when custom date formats are set.
* [Improvement] - Gift coupons based on cart item quantity. New filter: wt_sc_alter_gift_coupon_cart_item_quantity to alter the number of coupons generated per cart item.
* [Improvement] - Displaying additional content in emails.


= 2.0.9  2023-07-20 =
* [Bug fix] - Used and expired coupons are not showing properly in My account
* [Bug fix] - Giveaway Add to cart button is not visible in the 2023 theme.
* [Bug fix] - Click-to-apply coupon is not working on the banner.
* [Add] - State-wise restrictions for coupons added along with country-wise restrictions.
* [Add] - `Order within days` condition added in the purchase history section of the coupon.
* [Add] - `Include/Exclude` option added for country/state restrictions.
* [Add] - Offer coupon for a specific product that the user purchased in the past.
* [Compatibility] - Bulk generate option compatible with the `URL coupons` plugin
* [Compatibility] - Tested OK with WooCommerce 7.9
* [Compatibility] - Tested OK with WordPress 6.2

= 2.0.8 2023-05-29 =
* [Add] - New filter to process the shipping method for validation. Filter: `wt_sc_chosen_shipping_for_validation`
* [Add] - Custom coupon success message (Individual coupons settings)
* [Add] - Custom notification messages for coupon (General coupons settings)
* [Add] - New BOGO option: `Any product from same category as in cart`
* [Add] - Cheapest BOGO option for `Any product from same category as in cart`
* [Bug fix] - Compatibility issue fix for PHP version 8.0 or greater.
* [Bug fix] - Undefined index customer-logout
* [Bug fix] - JS error in console when template disabled on gift card product page.
* [Bug fix] - Removed click to apply title and pointer cursor from coupon block in email.
* [Bug fix] - Safari is not showing the count-down timer in the coupon banner.
* [Bug fix] - Master coupon existence checking added when inserting abandonment cart data.
* [Bug fix] - Unable to empty store-credit gift card product.
* [Bug fix] - Cart with single item becomes empty when applying the cheapest coupon without restriction.
* [Compatibility] - Compatibility with WooCommerce HPOS
* [Compatibility] - Time based coupon expiry compatibility for `WebToffee Gift cards` plugin
* [Compatibility] - User roles validation compatibility with `Easy Loyalty Points and Rewards` plugin.
* [Compatibility] - WC 7.7
* [Compatibility] - WP 6.2
* [Improvement] - Preparing cart/order items from WC_Discounts items
* [Improvement] - Store credit backend applying compatibility
* [Improvement] - Tag based coupon restriction.
* [Improvement] - Attribute based coupon restriction.
* [Improvement] - For guest users: auto-apply is limited to coupons that do not have email restrictions.
* [Improvement] - Disable quantity field on gift card product page: In some themes quantity field is added via JavaScript and other methods. Force the quantity to one on the gift card add to cart
* [Improvement] - Product category preparation improved. New filter: wt_sc_product_categories_with_ancestors.

= 2.0.7 2023-02-06 =
* [Improvement] Search coupons using email
* [Improvement] Coupon block HTML preparation improved
* [Improvement] Time option added for coupon expiry and start
* [Improvement] Lookup table migration improved
* [Improvement] Multi-currency switcher compatibility - New hooks added
* [Improvement] Import compatibility for giveaway and coupon restriction modules.
* [Add] Excluded roles option for checkout
* [Add] Coupon restriction considered when applying coupon in the backend.
* [Add] Cheapest item as a giveaway for BOGO options `Any product from store` and `Any product from selected category'
* [Add] Compatibility for WPML on BOGO Specific products
* [Bug fix] Lookup table large database issue
* [Bug fix] Validation error when multiple coupons with the same product restriction but different quantity restrictions are used.
* [Bug fix] Giveaway product alignment issue in small screens.
* [Bug fix] Coupon banner responsiveness issue in small screens.
* [Bug fix] Store credit is applied on excluded products when tax calculation is done before applying store credit.
* [Bug fix] Non-existing coupon ids are in the list.
* [Bug fix] Master/Gift coupons are not removed. Affected modules: Signup, Abandoned, Gift coupon
* [Compatibility] WC 7.1
* [Compatibility] WP 6.1

= 2.0.6 =
* [Improvement] Excluding master coupons from normal coupon usages.
* [Improvement] Gift coupon email additional content enabled. (Email template updated).
* [Improvement] Coupon fetching and displaying optimized. Lookup table added.
* [Add] Added allowed emails column in admin panel coupon listing
* [Add] Added new filter to alter the coupon list: (wt_sc_auto_coupons_list)
* [Add] Added new filter to alter calculate totals hook priority. `wt_sc_calculate_totals_hook_priority`
* [Add] Added a new filter to alter giveaway product cart item data before adding to cart. `wt_sc_alter_giveaway_cart_item_data_before_add_to_cart`
* [Bug fix] Auto coupon applying on non existing coupon.
* [Bug fix] Fatal error: Uncaught Error: Call to a member function get_applied_coupons() 
* [Bug fix] Auto coupons removing when email restrictions added
* [Bug fix] BOGO eligibility is calculated based on the quantity of the last added variable of the product instead of total number of variable products available in the cart
* [Bug fix] Email validation is not working properly when email has capital letters
* [Bug fix] Available coupons shortcode causing error on page edit section. (Coupon validation fails when cart object is not available in backend)
* [Bug fix] Validation fails when global quantity restriction with excluded product/category exists
* [Bug fix] SQL issue: Not taking coupons having multiple user role restriction.
* [Bug fix] Coupon banner timer issue.
* [Bug fix] In gift card product page, the first template is not automatically selected on page load, If admin disabled the default `general` template.
* [Bug fix] Multi Select field overflow issue when product with lengthy name chosen.
* [Bug fix] Unable to delete custom gift card template
* [Bug fix] Custom signup coupon code length is not working
* [Bug fix] Showing multiple loaders when choosing a coupon and applying it, on front end.
* [Compatibility] WC 7.1
* [Compatibility] WP 6.1


= 2.0.5 =
* [Add] Added pagination for Available coupons in My account, Cart, and Checkout pages
* [Add] Added filters to alter coupons count per page: wt_sc_cart_available_coupons_per_page, wt_sc_checkout_available_coupons_per_page, wt_sc_my_account_available_coupons_per_page
* [Add] Added shortcode to print user available coupons: [wt_sc_user_available_coupons]
* [Add] Added column for used coupons in order listing page.
* [Add] Added option to set quantity of giveaway for `Same product as in cart` option.
* [Add] Added new filter to alter store credit email args: wt_sc_alter_admin_storecredit_email_args
* [Add] Coupon URL help-guide popup added in the coupon edit page.
* [Add] Added new action hook in store credit gift card mail template: wt_sc_giftcard_email_after_coupon_code
* [Add] Added new filters to alter no coupon available message
    Available: wt_sc_alter_myaccount_no_available_coupons_msg, Used: wt_sc_alter_myaccount_no_used_coupons_msg, Expired: wt_sc_alter_myaccount_no_expired_coupons_msg
* [Improvement] Email field set as a required field in store credit product page.
* [Improvement] Updated style of Used and Expired coupons block in my account page.
* [Improvement] Hide giveaway products in the cart once the offer is redeemed.
* [Improvement] For 'Same product as in cart` type giveaway offer, Add to cart button now adds products individually.
* [Improvement] Giveaway `add to cart` button and quantity field styles updated.
* [Improvement] Giveaway add to cart ajax error messages changed to normal WooCommerce error messages.
* [Improvement] Total giveaway amount shows as coupon amount in BOGO
* [Improvement] Giveaway indication in cart item table updated.
* [Improvement] Coupon URL `Copy to clipboard` style updated
* [Improvement] Added decimal support in the price field in the giveaway tab of the coupon edit page.
* [Improvement] Removed price striking for cart items. Now showing the discount just below the actual price (BOGO)
* [Improvement] CSS class added for `No coupons DIV` in My account.
    Available: wt_sc_myaccount_no_available_coupons, Used: wt_sc_myaccount_no_used_coupons, Expired: wt_sc_myaccount_no_expired_coupons
* [Bug fix] Price limited to 100 when price type is percentage
* [Bug fix] post_type on null - Thanks to `Leonidas` for pointing out the bug
* [Bug fix] Conflict with `auto apply` and `coupon individual use`.
* [Bug fix] Fixed null value meta getting converted to empty strings when duplicating coupons causing an issue to coupon display in the front end.
* [Bug fix] Product variation support in the giveaway product field.
* [Bug fix] Giveaway item is considered as a sale item and removes the coupon with exclude sale item option enabled.
* [Bug fix] RTL alignment issue in coupon settings section corrected
* [Bug fix] BOGO apply-repeatedly eligibility calculation issue in the category and product restriction when `All from below` with global quantity one or empty.
* [Bug fix] Giveaway eligible message is not showing. Message for: Non-free product not found on `same product as in the cart`
* [Bug fix] Non-index error when a product contains multiple categories on category-wise BOGO section
* [Bug fix] Manually created store credits are not displaying in my account.
* [Bug fix] Backward compatibility issue with filter in `get_endpoint_title` method.
* [Bug fix] Eligibility calculation issue on `Any` option in product category restriction with global quantity enabled. 
* [Bug fix] Fixed coupon behavior issue when giveaway product and eligibility product are the same for an auto-apply coupon. 
* [Bug fix] Fixed error causing adding more than coupon eligibility quantity for auto-apply coupons.
* [Bug fix] Exclude category not importing when single category in the CSV
* [Bug fix] Subtotal calculation issue when apply repeatedly enabled.
* [Bug fix] Undefined variable $variation on add to cart function.
* [Bug fix] Store credit gift card email not working when the scheduled date is empty. getTimestamp function causing a fatal error.
* [Bug fix] Store credit calculation issue when applying via backend.
* [Bug fix] Fatal error when `$woocommerce` global variable is missing (Bulk generate section).
* [Bug fix] Exclude the products from applying discounts that are not satisfying the min/max quantity restriction. This is applicable when the product condition is `any(or)`
* [Compatibility] Fixed `My account -> Coupons` style issue in some themes
* [Compatibility] Fixed issues with YITH POS custom add-to-cart
* [Compatibility] Double store credit discount amount when placing an order via Phone Orders for WooCommerce By AlgolPlus
* [Compatibility] WC_Coupon::is_valid is deprecated. Method updated to WC_Discounts::is_coupon_valid
* [Compatibility] with WooCommerce 6.8
* [Compatibility] with WordPress 6.0


= 2.0.4 =
* [New] `BOGO offers` option introduced.
* [New] Individual product/category quantity restriction.
* [Improvement] Added support for coupon categories in coupon import.
* [Improvement] Added option to alter decimal value support in store credit gift card amount. 
* [Improvement] Compatibility added for `Advanced Dynamic Pricing for WooCommerce - By AlgolPlus` added 
* [Improvement] Coupon name auto complete added in coupon URL generating section.
* [Improvement] Enable/disable product/category restriction.
* [Bug Fix] Resolved the issue of altering WC_Email object as array
* [Bug fix] Media library is not working in store credit custom template section.
* [Bug fix] Multiple recipient emails not importing.
* [Bug fix] Custom templates are not sent via email
* [Bug fix] Chosen template is not selected after form validation fails.
* [Bug fix] Showing subscription field on non subscription coupon type in bulk generate section. (Added compatibility for WooCommerce Subscriptions, WebToffee Subscriptions)
* [Bug fix] Gift card caption is not translatable.
* [Bug fix] My account coupon is not clickable.
* [Bug fix] Store credit amount is not fully utilizing.
* [Bug fix] Showing `Coupon not available for selected products` when chosen variable product as coupon product.
* [Bug fix] Fails to check individual user usage count when displaying coupons in My account, Cart, Checkout etc
* [Compatibility] with WooCommerce 6.5

= 2.0.3 =
* [Bug fix] Coupon restricted by email is displayed for guest users.

= 2.0.2 =
* [Bug fix] Shows a warning message when `Hide shipping costs until an address is entered` option is enabled.
* [Bug fix] Coupons are applying to the products from excluded category.
* [Bug fix] Couldn't use entire store credit coupon value
* [Bug fix] Calculate store credit discount based on the apply before tax settings option.
* [Bug fix] Unwanted transient is loading.
* [Bug fix] Undefined variable $coupon_timer on expired coupon banner.
* [Bug fix] Showing fatal error when using Subscription coupon of WC subscription plugin.
* [Bug fix] Unable to choose variation giveaway product when the parent and variation does not have image.
* [Bug fix] Wrong discount amount when applied a percentage coupon from backend.
* [Bug fix] Unable to submit the form when a required element is hidden.
* [Bug fix] Percentage coupon not working for variable products.
* [Bug fix] Timezone issue when scheduling store credit.
* [Bug fix] Prevent displaying my account page as default for 'make coupon available in' option
* [Bug fix] Fixed issue of store credit being applied for excluded product when discount after tax is calculated 
* [Bug fix] Fixed auto coupon sql performance related issue. Thanks `Brad Patton` for the suggestion.
* [Bug fix] Fixed issue of excluded category not working for coupon type Fixed Product Discount.
* [Bug fix] Minimum quantity of matching product is not working for variable products.
* [Bug fix] PHP 8 warning message, Undefined array key "_wt_product_coupon_variation"
* [Bug fix] Imported coupons not listing in My account, Checkout, Cart
* [Bug fix] Unable to send/resend non scheduled mails. If the payment/order status was manually updated via admin
* [Bug fix] PHP warning : count(): Parameter must be an array or an object that implements countable
* [Bug fix] The Exclude from store credit option on a product page is not working when cart contain only invalid product
* [Tweak] Default placeholder image added if parent and variations does not have image (Giveaway product choosing section)
* [Tweak] Options to add decimal denomination for email store credit coupons.
* [Tweak] Coupon displaying queries optimized in My account, Cart, Checkout.
* [Tweak] Auto coupon SQL optimized.
* [Compatibility] with WooCommerce 6.4
* [Compatibility] with WordPress 5.9

= 2.0.1 =
* [Fix] Excluded product for store credit not working
* [Fix] Modules deactivating automatically
* [Fix] Compatibility fix for DIVI theme.
* [Tweak] Email templates updated.
* [Tweak] Translation update.
* [Compatibility] with WooCommerce 6.1

= 2.0.0 =
* [Fix] Style breaking issue of store credit product page fixed.
* [Fix] Order total resets after status change.
* [Fix] Compatibility fix for Woocommerce rental and booking.
* [Fix] SQL injection issue in Duplicate coupon functionality
* [Add] New templates for store credit Gift card
* [Tweak] Custom gift card adding option introduced.
* [Tweak] Template category introduced.
* [Tweak] Template visibility control added.
* [Compatibility] with WooCommerce 6.0

= 1.3.6 =
* [Enhancement] Separate tab added for URL coupons.
* [Tweak] Showing coupon URL preview on coupons add/edit page.
* [Bug fix] Coupon length issue in bulk generate.
* [Bug fix] Product restrictions not working in bulk generate.
* [Add] Translations added for Norwegian.
* [Add] Coupon category option added.
* [Compatibility] with WooCommerce 5.8

= 1.3.5 =
* [Enhancement] Separate menu for Smart coupon settings
* [Enhancement] UI and UX improvement
* [Enhancement] Minimum order count section in nth order coupon settings modified to setup zero as minimum order count.
* [Enhancement] New filter added to customize/hide free product added success message. Filter: wt_smart_coupon_free_product_added_message
* [Enhancement] New filter added to customize gift card caption. Filter: wt_smart_coupon_gift_card_caption
* [Add] Translations added for AR, DK, DE, ES, NL, FR, IT
* [Tweak] Option added to send the store credit on same day.
* [Bug fix] Unable to apply coupon for non-logged in users.
* [Bug fix] Non numeric value warning
* [Bug fix] Coupon conflict if coupons with same code exists.
* [Bug fix] Coupon code comparing fails when the coupon code has capital letters.
* [Bug fix] Error: `Coupon not valid for selected shipping method`. If the coupon restricted to `flat rate` shipping and chosen shipping was `free_shipping` when the coupon has free shipping option enabled.
* [Bug fix] Variation product image not displaying on checkout page.
* [Bug fix] Unable to delete associated master coupon in Signup coupon settings.
* [Bug fix] Store credit is applying to recurring total on subscription products
* [Bug fix] Able to apply store credit to excluded products.
* [Bug fix] Filter not working (wt_smart_coupon_store_credit_validation)
* [Bug fix] Coupon banner not displaying on shop page
* [Compatibility] with WooCommerce 5.7
* [Compatibility] with WordPress 5.8


= 1.3.4 =
 * [Bug fix] Store credit email not sending after placing the order
 * [Bug fix] Unable to choose the give away product
 * [Bug fix] Exclude category settings from master coupon isn't applying to Signup coupon
 * [Bug fix] Imported store credit automatic coupons are not applying automatically
 * [Bug fix] Removes any free products from the cart if their related coupon is not present in the cart
 * Tested OK with WC 5.4

= 1.3.3 =
 * Enhancement: Option to modify gift coupon HTML using templates
 * Enhancement: New filter `wt_smart_coupon_enable_gift_coupon_form` to show or hide gift coupon form
 * Fix: Mulitple exclude categories are not working after importing the coupon
 * Fix: Removed UTF-8 bom from a CSV file
 * Fix: Hide expired store credits from cart, checkout and my account pages
 * Fix: Coupon amount is not reflected on coupon email after import

= 1.3.2 =
 * Enhancement: Option to add coupon expiry in days
 * Enhancement: Option to add custom coupon endpoint and title.
 * Enhancement: Option to modify my account coupon listing via templates
 * Fix: The auto coupons not applied according to the payment methods.
 * Tested OK with WooCommerce 4.7.0
 
= 1.3.1 =
 * Security improvements
 * [fix] Missing coupon fields while importing the coupons

= 1.3.0 =
 * Security improvements
 * [fix] Giveaway product is not added if subscription product is in the cart
 

= 1.2.10 =
 * Action on click on coupon banner
 * Action on expiry coupon banner
 * Tested OK with latest version of WordPress and WooCommerce


= 1.2.9 =
 * Coupon countdown timer
 * Configurable denominations for purchase
 * Currency position update
 * Activate WC coupon on plugin activation

= 1.2.8 =
 * Signup Coupon
 * Cart/Checkout Abandonment Coupon
 * Implemented Extended Store credit coupon
 * nth Order Coupon
 * Tested Ok with latest version of WordPress and WooCommerce


= 1.2.7 =
 * Option to set maximum discount amount for fixed product discount coupon.
 * Tested ok with WP 5.2.2 WC 3.7

= 1.2.6 =
 * Specify the Quantity for giveaway products
 * Create Dynamic Gift coupon
 * Resend coupon manually ( gift and store credit) - for admin in order page
 * Store credit may use as conjuction with restricted coupons ( override individual use only)


= 1.2.5 =
 * Maximum available discount for % discount coupon
 * Restrict discount amount / percent for giveaway product.
 * Implemented Gift coupon for variable products.
 * Tested OK with latest version of WC and WP

= 1.2.4 =
* Bug fix import section

= 1.2.3 =

* Implemented AJAX bulkcoupon import.
* Implemented URL coupon.
* Tested OK with WooCommerce 3.6.4

= 1.2.2 =

* Updated French and German translation.
* Tested OK with WooCommerce 3.6.3

= 1.2.1 =

* Updated the plugin into different pluggable modules.
* Manage refund for credit coupon.
* Implement Schedule date for store credit.
* Added start date for coupon.


= 1.2.0 =

* Store Credit
* Apply Coupon automatically
* Combo Coupons

= 1.1.1 =

* Bug Fix in Admin Product tabs.
* Updated coupon title for give away Product.

= 1.1.0 =

* Gift a coupon upon product purchase.
* Option to restrict coupons by country.
* Option to display available coupons on cart and checkout page.
* Option to email a coupon upon import.
* Click to apply coupon feature.
* Added basic coupon style options.

= 1.0.0 =
* Released First version


== Upgrade Notice ==

= 3.2.0 =
* [Add] - More optional condition restrictions for new BOGO.
* [Fix] - Free products not incrementing when eligibility changed, instead displaying free products to choose.
* [Fix] - BOGO category restriction checking is failing for variable products.
* [Compatibility] - Tested OK with WooCommerce 9.6
