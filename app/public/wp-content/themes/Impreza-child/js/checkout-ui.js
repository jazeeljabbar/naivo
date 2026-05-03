/**
 * Naivo Checkout UI — DOM restructuring to match Figma Desktop-86
 *
 * Layout target:
 *   LEFT column  — Contact (email) → Shipping Address → Billing Address → Payment Method → PAY NOW
 *   RIGHT column — Order Summary (products, coupon, discount, totals)
 *
 * WooCommerce's default structure:
 *   form.checkout > #customer_details (.col-1 billing, .col-2 shipping)
 *                  > #order_review_heading
 *                  > #order_review (table + #payment)
 *
 * We restructure this via JS so #payment (with Place Order) moves into the left column.
 */
(function ($) {
    'use strict';

    function nvRestructureCheckout() {
        var $form = $('form.checkout.woocommerce-checkout');
        if (!$form.length) return;

        // Avoid running twice
        if ($form.hasClass('nv-checkout-restructured')) return;
        $form.addClass('nv-checkout-restructured');

        /* ─── 1. Create wrapper columns ─── */
        var $customerDetails = $('#customer_details');
        var $orderReviewHeading = $('#order_review_heading');
        var $orderReview = $('#order_review');

        if (!$customerDetails.length || !$orderReview.length) return;

        // Create the two-column wrapper
        var $leftCol = $('<div class="nv-checkout-left"></div>');
        var $rightCol = $('<div class="nv-checkout-right"></div>');
        var $wrapper = $('<div class="nv-checkout-columns"></div>');

        /* ─── 2. Move billing (Contact + Shipping Address) into left column ─── */
        var $col1 = $customerDetails.find('.col-1');
        var $col2 = $customerDetails.find('.col-2');

        $leftCol.append($col1.children());
        $leftCol.append($col2.children());

        /* ─── 3. Move #payment from order_review into left column ─── */
        var $payment = $orderReview.find('#payment');
        if ($payment.length) {
            $leftCol.append($payment.detach());
        }

        /* ─── 4. Build right column — Order Summary ─── */
        if ($orderReviewHeading.length) {
            $rightCol.append($orderReviewHeading.detach());
        }

        // Move the order review wrapper (table, but not payment — already detached)
        var $reviewOrder = $orderReview.find('.woocommerce-checkout-review-order');
        if ($reviewOrder.length) {
            $rightCol.append($reviewOrder.detach());
        } else {
            // Fallback: move all remaining children
            $rightCol.append($orderReview.children().detach());
        }
        $orderReview.remove();

        /* ─── 5. Move coupon into order summary ─── */
        var $wooContainer = $form.closest('.woocommerce');
        var $couponForm = $wooContainer.find('form.checkout_coupon, .checkout_coupon');
        var $couponToggle = $wooContainer.find('.woocommerce-form-coupon-toggle');

        // Create the "Apply Discount" container for inside order summary
        var $discountSection = $('<div class="nv-checkout-discount-section"></div>');
        $discountSection.append('<h4 class="nv-discount-heading">🏷️ Apply Discount</h4>');

        if ($couponForm.length) {
            var $couponClone = $couponForm.detach();
            // Make it always visible inside order summary & style
            $couponClone.css('display', 'flex').removeClass('hidden').addClass('nv-coupon-form');
            // Update the input placeholder text
            $couponClone.find('input[type="text"]').attr('placeholder', 'Enter discount code or Gift card');
            $discountSection.append($couponClone);
        } else {
            // No coupon form found — create our own
            var couponHtml = '<div class="nv-coupon-inline">' +
                '<input type="text" class="nv-coupon-input" placeholder="Enter discount code or Gift card" />' +
                '<button type="button" class="nv-coupon-apply-btn">APPLY</button>' +
                '</div>';
            $discountSection.append(couponHtml);
        }

        // Add "Discount Code" promo card (like in Figma)
        var $promoCard = $('<div class="nv-discount-code-section">' +
            '<h5 class="nv-discount-code-heading">Discount Code</h5>' +
            '<div class="nv-discount-code-card">' +
            '<div class="nv-promo-info">' +
            '<div class="nv-promo-title">Flat 15% OFF</div>' +
            '<div class="nv-promo-desc">Get 15% off + 3 easy pour sachets on your first coffee purchase</div>' +
            '</div>' +
            '<button type="button" class="nv-promo-apply" data-code="FIRST15">APPLY</button>' +
            '</div>' +
            '</div>');
        $discountSection.append($promoCard);

        // Hide the toggle notice
        if ($couponToggle.length) {
            $couponToggle.hide();
        }

        // Hide the "Returning customer?" login toggle
        $wooContainer.find('.woocommerce-form-login-toggle').hide();

        // We'll insert the discount section into rightCol — it will be positioned between table and totals via JS later
        $rightCol.append($discountSection);

        /* ─── 6. Wrap the right column in a sticky order summary card ─── */
        var $orderSummaryCard = $('<div class="nv-order-summary-card"></div>');
        $orderSummaryCard.append($rightCol.children());
        $rightCol.append($orderSummaryCard);

        /* ─── 7. Assemble ─── */
        $wrapper.append($leftCol);
        $wrapper.append($rightCol);

        // Replace old structure
        $customerDetails.replaceWith($wrapper);

        /* ─── 7b. Restructure order summary: products → discount → totals ─── */
        var $orderTable = $orderSummaryCard.find('.woocommerce-checkout-review-order-table');
        if ($orderTable.length) {
            // Extract the tfoot totals from the table and make them a separate section
            var $tfoot = $orderTable.find('tfoot');
            if ($tfoot.length && $discountSection.length) {
                // Create a new totals container
                var $totalsDiv = $('<div class="nv-order-totals"></div>');

                // Convert tfoot rows to div-based totals
                $tfoot.find('tr').each(function () {
                    var $tr = $(this);
                    var thText = $tr.find('th').text().trim();
                    var $tdContent = $tr.find('td').clone();
                    var rowClass = $tr.attr('class') || '';
                    var $row = $('<div class="nv-total-row ' + rowClass + '"></div>');
                    $row.append('<span class="nv-total-label">' + thText + '</span>');
                    var $val = $('<span class="nv-total-value"></span>');
                    $val.append($tdContent.contents());
                    $row.append($val);
                    $totalsDiv.append($row);
                });

                // Remove tfoot from table
                $tfoot.remove();

                // Insert: table → discount → totals
                $orderTable.after($totalsDiv);
                $orderTable.after($discountSection.detach());
            }

            // Hide any remaining product-total column (the "× 1" quantity or price column)
            $orderTable.find('td.product-total').hide();
        }

        /* ─── 8. Style enhancements ─── */
        nvEnhanceCheckoutLabels();
        nvEnhancePlaceOrderButton();
        nvEnhanceOrderSummaryHeading();
        nvAddBreadcrumb();
        nvAddPlaceholders();
        nvCleanStrayTextNodes();
    }

    /**
     * Add clear section labels per Figma design
     */
    function nvEnhanceCheckoutLabels() {
        var $left = $('.nv-checkout-left');
        if (!$left.length) return;

        // ── "Contact" header with login link ──
        var $billingFields = $left.find('.woocommerce-billing-fields');
        if ($billingFields.length && !$left.find('.nv-section-contact').length) {
            var $contactHeader = $('<div class="nv-section-header nv-section-contact">' +
                '<h3>Contact</h3>' +
                '<span class="nv-login-link">Have an account? <a href="#" class="nv-checkout-login-trigger">Log in</a></span>' +
                '</div>');
            $billingFields.before($contactHeader);

            // Move email field after Contact header
            var $emailRow = $left.find('#billing_email_field');
            if ($emailRow.length) {
                $contactHeader.after($emailRow.detach());

                // Add "Receive instant updates by email" checkbox after email
                if (!$left.find('.nv-email-updates').length) {
                    var $updatesCheck = $('<p class="nv-email-updates">' +
                        '<label><input type="checkbox" checked /> Receive instant updates by email</label>' +
                        '</p>');
                    $emailRow.after($updatesCheck);
                }
            }

            // Move newsletter/subscribe checkbox after email updates
            var $newsletter = $left.find('#mailchimp_woocommerce_newsletter_field, [class*="mailchimp"], [class*="newsletter"]');
            if ($newsletter.length) {
                var $updatesEl = $left.find('.nv-email-updates');
                if ($updatesEl.length) {
                    // Hide our custom one and keep the real one
                    $updatesEl.remove();
                }
                $emailRow.after($newsletter.detach());
            }
        }

        // ── "Shipping Address" heading ──
        var $billingH3 = $left.find('.woocommerce-billing-fields > h3');
        if ($billingH3.length) {
            $billingH3.text('Shipping Address');
            $billingH3.addClass('nv-section-heading');
        }

        // ── "Billing Address" heading before shipping-different section ──
        var $shipDiff = $left.find('.woocommerce-shipping-fields');
        if ($shipDiff.length && !$shipDiff.prev('.nv-billing-heading').length) {
            $shipDiff.before('<div class="nv-section-header nv-billing-heading"><h3 class="nv-section-heading">Billing Address</h3></div>');
        }

        // ── "Payment Method" heading ──
        var $paymentBox = $left.find('#payment');
        if ($paymentBox.length && !$left.find('.nv-payment-heading').length) {
            // Add shipping info next to Payment Method heading
            var shippingText = '';
            var $shippingMethod = $paymentBox.find('.shipping td, .woocommerce-shipping-totals td');
            if ($shippingMethod.length) {
                shippingText = '<span class="nv-shipping-badge">' + $shippingMethod.text().trim() + '</span>';
            }
            $paymentBox.before('<div class="nv-section-header nv-payment-heading">' +
                '<h3 class="nv-section-heading">Payment Method</h3>' +
                shippingText +
                '</div>');
        }

        // ── GST checkboxes section ──
        var $gstinField = $left.find('#gstin_no_field');
        if ($gstinField.length) {
            $gstinField.addClass('nv-gstin-field');
            // Add GST checkbox before GSTIN field if not present
            if (!$left.find('.nv-gst-checkbox').length) {
                var $gstCheck = $('<p class="nv-gst-checkbox">' +
                    '<label><input type="checkbox" id="nv_has_gst" /> Yes, I have GST Number</label>' +
                    '</p>');
                $gstinField.before($gstCheck);

                // Toggle GSTIN visibility based on checkbox
                $gstinField.hide();
                $(document).on('change', '#nv_has_gst', function () {
                    $gstinField.toggle(this.checked);
                });
            }
        }

        // Note: WooCommerce handles its own save address logic
    }

    /**
     * Add placeholder text to inputs that lost their labels
     */
    function nvAddPlaceholders() {
        var placeholders = {
            '#billing_email': 'Email',
            '#billing_first_name': 'First Name',
            '#billing_last_name': 'Last Name',
            '#billing_company': 'Company name (optional)',
            '#billing_country': 'Country',
            '#billing_address_1': 'Address',
            '#billing_address_2': 'Apartment, Villa, etc',
            '#billing_city': 'City',
            '#billing_postcode': 'Pincode',
            '#billing_phone': 'Mobile Number',
            '#shipping_first_name': 'First Name',
            '#shipping_last_name': 'Last Name',
            '#shipping_company': 'Company name (optional)',
            '#shipping_country': 'Country',
            '#shipping_address_1': 'Address',
            '#shipping_address_2': 'Apartment, Villa, etc',
            '#shipping_city': 'City',
            '#shipping_postcode': 'Pincode',
            '#shipping_phone': 'Phone Number',
            '#order_comments': 'Notes about your order, e.g. special notes for delivery.'
        };

        $.each(placeholders, function (selector, text) {
            var $el = $(selector);
            if ($el.length && !$el.attr('placeholder')) {
                $el.attr('placeholder', text);
            }
        });
    }

    /**
     * Rename "Place order" button to "🔒 PAY NOW"
     */
    function nvEnhancePlaceOrderButton() {
        var $btn = $('#place_order');
        if ($btn.length && $btn.val() !== '🔒  PAY NOW') {
            $btn.val('🔒  PAY NOW');
            $btn.addClass('nv-pay-now-btn');
        }

        // Style privacy text
        var $privacy = $('.woocommerce-privacy-policy-text');
        if ($privacy.length) {
            $privacy.addClass('nv-privacy-text');
        }
    }

    /**
     * Add product count to the "Your order" heading
     */
    function nvEnhanceOrderSummaryHeading() {
        var $card = $('.nv-order-summary-card');
        var $heading = $card.find('h3#order_review_heading, h3:first');
        if ($heading.length) {
            var itemCount = $card.find('.cart_item').length;
            if (itemCount === 0) {
                // Try from the table
                itemCount = $card.find('.woocommerce-checkout-review-order-table tbody tr').length;
            }
            $heading.html('Order Summary <span class="nv-order-count">(' + itemCount + ' Products)</span>');
            $heading.addClass('nv-order-summary-title');
        }
    }

    /**
     * Add breadcrumb if not already present
     */
    function nvAddBreadcrumb() {
        var $woo = $('form.checkout').closest('.woocommerce');
        if ($woo.length && !$woo.find('.nv-checkout-breadcrumb').length) {
            var breadcrumb = '<nav class="nv-checkout-breadcrumb">' +
                '<a href="/">Home</a> / <a href="/shop/">Shop</a> / <span>Check Out</span>' +
                '</nav>';
            $woo.prepend(breadcrumb);
        }
    }

    /**
     * Re-apply enhancements after WC AJAX updates (e.g., after coupon applied)
     */
    function nvRefreshCheckoutEnhancements() {
        nvEnhancePlaceOrderButton();
        nvEnhanceOrderSummaryHeading();
        nvFixCouponButton();
        nvCleanStrayTextNodes();
    }

    /**
     * Remove stray text nodes (like WooCommerce's default quantity "1")
     * from the product-name cell.
     */
    function nvCleanStrayTextNodes() {
        $('.nv-order-summary-card .cart_item td.product-name').each(function() {
            var el = this;
            var child = el.firstChild;
            while (child) {
                var next = child.nextSibling;
                if (child.nodeType === 3) {
                    // Check if it's not just whitespace
                    if (child.nodeValue.trim().length > 0) {
                        el.removeChild(child);
                    }
                }
                child = next;
            }
        });
    }

    /**
     * Ensure the WooCommerce coupon Apply button has visible text
     */
    function nvFixCouponButton() {
        var $couponBtns = $('.nv-checkout-discount-section .checkout_coupon button[type="submit"], .nv-checkout-discount-section .checkout_coupon input[type="submit"]');
        $couponBtns.each(function() {
            var $b = $(this);
            if ($b.is('button') && !$b.text().trim()) {
                $b.text('APPLY');
            } else if ($b.is('input') && !$b.val().trim()) {
                $b.val('APPLY');
            }
        });
    }

    /* ─── Watch for Place Order button re-render ─── */
    var nvPlaceOrderObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.type === 'attributes' && m.attributeName === 'value') {
                var btn = document.getElementById('place_order');
                if (btn && btn.value !== '🔒  PAY NOW') {
                    btn.value = '🔒  PAY NOW';
                }
            }
        });
        // Also check if button was replaced entirely
        var btn = document.getElementById('place_order');
        if (btn && btn.value !== '🔒  PAY NOW') {
            btn.value = '🔒  PAY NOW';
        }
    });

    function nvWatchPlaceOrder() {
        var payment = document.getElementById('payment');
        if (payment) {
            nvPlaceOrderObserver.observe(payment, { childList: true, subtree: true, attributes: true, attributeFilter: ['value'] });
        }
    }

    /* ─── Custom coupon AJAX (for our inline form) ─── */
    $(document).on('click', '.nv-coupon-apply-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $input = $btn.closest('.nv-coupon-inline').find('.nv-coupon-input');
        var code = $input.val().trim();
        if (!code) return;

        $btn.text('...');
        $.ajax({
            url: wc_checkout_params.ajax_url || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'woocommerce_apply_coupon',
                security: wc_checkout_params.apply_coupon_nonce,
                coupon_code: code
            },
            success: function () {
                $(document.body).trigger('update_checkout');
                $input.val('');
                $btn.text('APPLY');
            },
            error: function () {
                $btn.text('APPLY');
            }
        });
    });

    /* ─── Initialize ─── */
    $(document).ready(function () {
        // Small delay to let WooCommerce fully render
        setTimeout(function () {
            nvRestructureCheckout();
            nvWatchPlaceOrder();
            nvFixCouponButton();
            nvEnhanceTotals();
        }, 300);
    });

    // Re-apply on WooCommerce update events
    $(document.body).on('updated_checkout', function () {
        setTimeout(function () {
            nvRefreshCheckoutEnhancements();
            nvEnhanceTotals();
        }, 200);
    });

    // Login trigger
    $(document).on('click', '.nv-checkout-login-trigger', function (e) {
        e.preventDefault();
        var $loginForm = $('.woocommerce-form-login');
        if ($loginForm.length) {
            $loginForm.slideToggle(200);
        } else {
            $('.woocommerce-form-login-toggle .woocommerce-info a').trigger('click');
        }
    });

    // Promo card "APPLY" button
    $(document).on('click', '.nv-promo-apply', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var code = $btn.data('code');
        if (!code) return;

        // Fill the coupon input and trigger apply
        var $couponInput = $('.nv-checkout-discount-section').find('input[type="text"]').first();
        if ($couponInput.length) {
            $couponInput.val(code);
        }
        $btn.text('...');

        $.ajax({
            url: wc_checkout_params.ajax_url || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'woocommerce_apply_coupon',
                security: wc_checkout_params.apply_coupon_nonce,
                coupon_code: code
            },
            success: function () {
                $(document.body).trigger('update_checkout');
                $btn.text('APPLIED ✓');
                $btn.css({ background: '#27ae60', color: '#fff', borderColor: '#27ae60' });
            },
            error: function () {
                $btn.text('APPLY');
            }
        });
    });

    /**
     * Enhance totals — re-extract tfoot if WC re-creates it, add "Total items" row & "FREE" badge
     */
    function nvEnhanceTotals() {
        var $card = $('.nv-order-summary-card');
        if (!$card.length) return;

        // If WC AJAX re-created a tfoot, extract it again
        var $tfoot = $card.find('.woocommerce-checkout-review-order-table tfoot');
        if ($tfoot.length) {
            var $existingTotals = $card.find('.nv-order-totals');
            if ($existingTotals.length) {
                $existingTotals.remove();
            }

            var $totalsDiv = $('<div class="nv-order-totals"></div>');
            $tfoot.find('tr').each(function () {
                var $tr = $(this);
                var thText = $tr.find('th').text().trim();
                var $tdContent = $tr.find('td').clone();
                var rowClass = $tr.attr('class') || '';
                var $row = $('<div class="nv-total-row ' + rowClass + '"></div>');
                $row.append('<span class="nv-total-label">' + thText + '</span>');
                var $val = $('<span class="nv-total-value"></span>');
                $val.append($tdContent.contents());
                $row.append($val);
                $totalsDiv.append($row);
            });

            $tfoot.remove();

            var $discountSection = $card.find('.nv-checkout-discount-section');
            if ($discountSection.length) {
                $discountSection.after($totalsDiv);
            } else {
                var $orderTable = $card.find('.woocommerce-checkout-review-order-table');
                $orderTable.after($totalsDiv);
            }

            // Also hide product-total column
            $card.find('td.product-total').hide();
        }

        var $totals = $card.find('.nv-order-totals');
        if (!$totals.length) return;

        // Count total quantity
        var totalQty = 0;
        $card.find('.nv-checkout-item-qty').each(function () {
            totalQty += parseInt($(this).text()) || 0;
        });
        if (totalQty === 0) {
            totalQty = $card.find('.cart_item').length;
        }

        // Add "Total items" row if not present
        if (!$totals.find('.nv-total-items-row').length && totalQty > 0) {
            var $totalItemsRow = $('<div class="nv-total-row nv-total-items-row">' +
                '<span class="nv-total-label">Total items</span>' +
                '<span class="nv-total-value">' + totalQty + ' items</span>' +
                '</div>');
            $totals.prepend($totalItemsRow);
        }

        // "FREE" shipping badge
        $totals.find('.nv-total-row.shipping .nv-total-value, .nv-total-row.woocommerce-shipping-totals .nv-total-value').each(function () {
            var $val = $(this);
            var text = $val.text().trim().toLowerCase();
            if (text.indexOf('free') !== -1 && !$val.find('.nv-free-badge').length) {
                $val.html('<span class="nv-free-badge">FREE</span>');
            }
        });

        // Rename "Subtotal" to "Sub Total"
        $totals.find('.cart-subtotal .nv-total-label').each(function () {
            if ($(this).text().trim() === 'Subtotal') {
                $(this).text('Sub Total');
            }
        });

        // Rename "Shipping" to "Delivery Fee"
        $totals.find('.shipping .nv-total-label, .woocommerce-shipping-totals .nv-total-label').each(function () {
            $(this).text('Delivery Fee');
        });
    }

})(jQuery);
