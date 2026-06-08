/**
 * Naivo Cart AJAX Handler
 * Handles: side cart auto-open after add, header badge update, button loading states,
 *          sidebar cart Figma restyling (header title, coupon relocation, footer note),
 *          editable variation dropdowns, footer reordering
 */
(function ($) {
    'use strict';

    /* ── Sidebar Cart Figma Enhancements ────────────────── */

    /**
     * Update the sidebar cart header to show "Your Cart (X Products)"
     */
    function nvUpdateSidebarHeader() {
        var $header = $('.vi-wcaio-sidebar-cart-header-title-wrap');
        if (!$header.length) return;

        // Count products in the sidebar cart
        var productCount = $('.vi-wcaio-sidebar-cart-pd-wrap').not('.vi-wcaio-sidebar-cart-pd-empty').length;

        if (productCount > 0) {
            var label = productCount === 1 ? 'Product' : 'Products';
            $header.html('Your Cart <span style="font-weight:400; color:#666; font-size:14px;">( ' + productCount + ' ' + label + ' )</span>');
        } else {
            $header.html('Your Cart');
        }
    }

    /**
     * Move coupon section from header to footer.
     * New order: Best Selling (order 1) → Coupon (order 2) → Subtotal (order 3)
     */
    function nvRelocateCoupon() {
        var $cart = $('.vi-wcaio-sidebar-cart');
        if (!$cart.length) return;

        var $couponWrap = $cart.find('.vi-wcaio-sidebar-cart-header-coupon-wrap');
        var $footerWrap = $cart.find('.vi-wcaio-sidebar-cart-footer-wrap');

        if ($couponWrap.length && $footerWrap.length) {
            // Clone and move coupon to footer if not already done
            if (!$footerWrap.find('.nv-footer-coupon-moved').length) {
                var $couponClone = $couponWrap.clone()
                    .removeClass('vi-wcaio-sidebar-cart-header-coupon-wrap')
                    .addClass('nv-footer-coupon-moved')
                    .css('display', 'flex');
                $footerWrap.append($couponClone);

                // Update placeholder text
                $couponClone.find('.vi-wcaio-coupon-code').attr('placeholder', 'Got Coupon code?');

                // Bind the apply button on clone
                $couponClone.find('.vi-wcaio-bt-coupon-code').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var code = $couponClone.find('.vi-wcaio-coupon-code').val();
                    $couponWrap.find('.vi-wcaio-coupon-code').val(code);
                    $couponWrap.find('.vi-wcaio-bt-coupon-code').trigger('click');
                });
            }

            // Hide the original header coupon
            $couponWrap.css('display', 'none');
        }
    }

    /**
     * Move delete icons from the name row to the quantity row
     * Figma shows: [Qty selector] ... [Delete icon] on the same line
     */
    function nvMoveDeleteToQtyRow() {
        $('.vi-wcaio-sidebar-cart-pd-wrap').not('.vi-wcaio-sidebar-cart-pd-empty').each(function () {
            var $item = $(this);
            var $desc = $item.find('.vi-wcaio-sidebar-cart-pd-desc');
            var $removeOriginal = $item.find('.vi-wcaio-sidebar-cart-pd-name-wrap .vi-wcaio-sidebar-cart-pd-remove-wrap');

            // Only move if desc row exists and doesn't already have a delete icon
            if ($desc.length && $removeOriginal.length && !$desc.find('.vi-wcaio-sidebar-cart-pd-remove-wrap').length) {
                var $removeClone = $removeOriginal.clone(true); // clone with events
                $removeClone.css('display', ''); // ensure visible
                $removeClone.css('margin-left', 'auto'); // push to right
                $desc.append($removeClone);
            }
        });
    }

    /**
     * Build editable variation dropdowns replacing static meta pills.
     * Each cart item with variation data gets <select> dropdowns that allow
     * changing the variation directly in the mini cart.
     */
    function nvBuildVariationDropdowns() {
        $('.vi-wcaio-sidebar-cart-pd-wrap').not('.vi-wcaio-sidebar-cart-pd-empty').each(function () {
            var $item = $(this);
            var $meta = $item.find('.vi-wcaio-sidebar-cart-pd-meta');

            // Skip if already has dropdowns or no meta
            if ($meta.hasClass('nv-dropdowns-built') || !$meta.length) return;

            var productId = $item.data('product_id');
            var cartItemKey = $item.data('cart_item_key');
            if (!productId) return;

            // Parse existing meta to get current attribute values
            // WooCommerce outputs either <dl><dt>Label</dt><dd>Value</dd></dl> or plain text "Label: Value"
            var currentValues = {};

            // Try DL/DT/DD structure first
            var $dls = $meta.find('dl');
            if ($dls.length) {
                $dls.each(function () {
                    var $dt = $(this).find('dt');
                    var $dd = $(this).find('dd p, dd');
                    if ($dt.length && $dd.length) {
                        var lbl = $dt.text().replace(/:$/, '').trim();
                        var val = $dd.last().text().trim();
                        if (lbl && val) currentValues[lbl] = val;
                    }
                });
            }

            // Fallback: parse plain text
            if (Object.keys(currentValues).length === 0) {
                var rawText = $meta.text().trim();
                if (!rawText) return;
                var regex = /([A-Za-z\s]+?):\s*([^:]+?)(?=\s+[A-Z]|$)/g;
                var match;
                while ((match = regex.exec(rawText)) !== null) {
                    var label = match[1].trim();
                    var value = match[2].trim();
                    if (label.toLowerCase().startsWith('select ')) {
                        label = label.substring(7);
                    }
                    currentValues[label] = value;
                }
            }

            if (Object.keys(currentValues).length === 0) return;

            // Mark as building to prevent re-entry
            $meta.addClass('nv-dropdowns-built');

            // Fetch available options from server
            $.ajax({
                url: (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: { action: 'naivo_get_variation_options', product_id: productId },
                success: function (response) {
                    if (!response || !response.success || !response.data) {
                        // Fallback: render static pills
                        nvRenderStaticPills($meta, currentValues);
                        return;
                    }

                    var attrData = response.data.attributes;
                    var variations = response.data.variations;

                    // Store variations data on the item for later lookup
                    $item.data('nv_variations', variations);
                    $item.data('nv_attr_data', attrData);

                    // Label mapping: WooCommerce attr names → Figma display names
                    var labelMap = {
                        'grind size': 'Filter',
                        'select grind size': 'Filter'
                    };

                    var html = '';
                    $.each(attrData, function (taxonomy, info) {
                        var currentVal = '';
                        // Clean label: strip "Select " prefix if present
                        var cleanLabel = info.label;
                        if (cleanLabel.toLowerCase().startsWith('select ')) {
                            cleanLabel = cleanLabel.substring(7);
                        }
                        // Apply label mapping
                        var mappedLabel = labelMap[cleanLabel.toLowerCase()] || labelMap[info.label.toLowerCase()];
                        if (mappedLabel) {
                            cleanLabel = mappedLabel;
                        }

                        // Match current value by label (fuzzy matching)
                        $.each(currentValues, function (lbl, val) {
                            var cleanLbl = lbl;
                            if (cleanLbl.toLowerCase().startsWith('select ')) {
                                cleanLbl = cleanLbl.substring(7);
                            }
                            if (cleanLabel.toLowerCase() === cleanLbl.toLowerCase() ||
                                info.label.toLowerCase() === lbl.toLowerCase()) {
                                currentVal = val;
                            }
                        });

                        html += '<div class="nv-variation-select-wrap">';
                        html += '<label class="nv-variation-label">' + cleanLabel + '</label>';
                        html += '<select class="nv-variation-select" data-taxonomy="attribute_' + taxonomy + '">';

                        info.options.forEach(function (opt) {
                            // Format display: use mapped option from server if available, else fallback
                            var displayOpt = opt.replace(/-/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
                            if (info.mapped_options && info.mapped_options[opt]) {
                                displayOpt = info.mapped_options[opt];
                            }
                            // Handle URL-encoded or slug values vs display values
                            var selected = '';
                            if (currentVal) {
                                // Normalize both values for comparison
                                var normOpt = opt.toLowerCase().replace(/[-_\s]+/g, '');
                                var normCurrent = currentVal.toLowerCase().replace(/[-_\s]+/g, '');
                                var normDisplay = displayOpt.toLowerCase().replace(/[-_\s]+/g, '');
                                if (normOpt === normCurrent ||
                                    normDisplay === normCurrent ||
                                    opt.toLowerCase() === currentVal.toLowerCase()) {
                                    selected = ' selected';
                                }
                            }
                            html += '<option value="' + opt + '"' + selected + '>' + displayOpt + '</option>';
                        });

                        html += '</select>';
                        html += '</div>';
                    });

                    $meta.html(html);
                },
                error: function () {
                    // Fallback: render static pills
                    nvRenderStaticPills($meta, currentValues);
                }
            });
        });
    }

    /**
     * Fallback: render static pills if AJAX fails
     */
    function nvRenderStaticPills($meta, pairs) {
        var html = '';
        $.each(pairs, function (label, value) {
            html += '<span class="nv-meta-pill">' +
                '<span class="nv-meta-label">' + label + '</span> ' +
                '<span class="nv-meta-value">' + value + '</span>' +
                '<span class="nv-meta-arrow">▾</span>' +
                '</span>';
        });
        $meta.html(html);
    }

    /**
     * Handle variation dropdown change: find matching variation and swap cart item
     */
    $(document).on('change', '.nv-variation-select', function () {
        var $select = $(this);
        var $item = $select.closest('.vi-wcaio-sidebar-cart-pd-wrap');
        var cartItemKey = $item.data('cart_item_key');
        var productId = $item.data('product_id');
        var variations = $item.data('nv_variations');

        if (!cartItemKey || !productId || !variations) return;

        // Collect current dropdown values
        var selectedAttrs = {};
        $item.find('.nv-variation-select').each(function () {
            selectedAttrs[$(this).data('taxonomy')] = $(this).val();
        });

        // Find matching variation
        var matchedVariation = null;
        for (var i = 0; i < variations.length; i++) {
            var v = variations[i];
            var isMatch = true;
            $.each(selectedAttrs, function (attrKey, attrVal) {
                var vAttrVal = v.attributes[attrKey] || '';
                // Empty string in variation = "any" value, so it matches
                if (vAttrVal !== '' && vAttrVal.toLowerCase() !== attrVal.toLowerCase()) {
                    isMatch = false;
                    return false; // break
                }
            });
            if (isMatch) {
                matchedVariation = v;
                break;
            }
        }

        if (!matchedVariation) {
            // No matching variation found — show a subtle warning
            $select.css('border-color', '#e74c3c');
            setTimeout(function () { $select.css('border-color', ''); }, 2000);
            return;
        }

        // Get current quantity
        var quantity = parseInt($item.find('input.vi_wcaio_qty').val(), 10) || 1;

        // Show loading state
        var $wrap = $item.closest('.vi-wcaio-sidebar-cart-wrap');
        $wrap.find('.vi-wcaio-sidebar-cart-loading-wrap').removeClass('vi-wcaio-disabled');

        // Fire AJAX to swap cart item
        $.ajax({
            url: (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: {
                action: 'naivo_update_cart_variation',
                cart_item_key: cartItemKey,
                product_id: productId,
                variation_id: matchedVariation.variation_id,
                quantity: quantity,
                attributes: JSON.stringify(selectedAttrs)
            },
            success: function (response) {
                if (response && response.fragments) {
                    // Apply WC fragments to refresh the cart content
                    $.each(response.fragments, function (selector, html) {
                        $(selector).replaceWith(html);
                    });
                    $(document.body).trigger('wc_fragments_refreshed');
                } else {
                    // Reload if fragments not available
                    $(document.body).trigger('viwcaio_fragment_refresh');
                }
            },
            error: function () {
                $wrap.find('.vi-wcaio-sidebar-cart-loading-wrap').addClass('vi-wcaio-disabled');
            }
        });
    });

    /**
     * Move "Best Selling Products" section and restructure the footer.
     * Target order: Best Selling → Coupon → Subtotal → Action → Note
     *
     * The plugin renders everything inside a single .vi-wcaio-sidebar-cart-footer-products div.
     * We need to detach the best selling section from message-wrap and inject it
     * directly into footer-wrap as a separate child with CSS order.
     */
    function nvRestructureFooter() {
        var $footerWrap = $('.vi-wcaio-sidebar-cart-footer-wrap');
        if (!$footerWrap.length) return;

        // 1. Move best selling from message-wrap to footer-wrap (if not already moved)
        var $messageWrap = $footerWrap.find('.vi-wcaio-sidebar-cart-footer-message-wrap');
        var $pdWrapWrap = $messageWrap.find('.vi-wcaio-sidebar-cart-footer-pd-wrap-wrap');

        if ($pdWrapWrap.length && !$pdWrapWrap.hasClass('nv-moved-to-footer')) {
            // Also grab the title if it's a sibling
            var $pdTitle = $messageWrap.find('.vi-wcaio-sidebar-cart-footer-pd-plus-title');

            // Create a container for the best selling section
            var $bestSellingContainer = $('<div class="nv-best-selling-section"></div>');
            if ($pdTitle.length) {
                $bestSellingContainer.append($pdTitle.detach());
            }
            $bestSellingContainer.append($pdWrapWrap.detach());
            $pdWrapWrap.addClass('nv-moved-to-footer');

            // Append to footer-wrap
            $footerWrap.append($bestSellingContainer);
        }

        // 2. Inject the note as a direct child of footer-wrap (not inside message-wrap)
        if (!$footerWrap.find('.nv-footer-note').length) {
            var noteHtml = '<div class="nv-footer-note">' +
                '<strong>Note:</strong> &nbsp; Shipping charges, Taxes, Discounts will be applied on checkout' +
                '</div>';
            $footerWrap.append(noteHtml);
        }

        // 3. Clean up the empty message-wrap (was only used for best selling + note)
        if ($messageWrap.length && !$messageWrap.find('.vi-wcaio-sidebar-cart-footer-pd-wrap-wrap').length) {
            // Remove remaining text and hide the empty wrapper
            $messageWrap.find('.vi-wcaio-sidebar-cart-footer-pd-plus-title').remove();
            if ($messageWrap.text().trim().length < 5 && !$messageWrap.find('.nv-footer-note').length) {
                $messageWrap.css('display', 'none');
            }
        }
    }

    /**
     * Inject '+' add-to-cart buttons into best selling product cards.
     * Uses AJAX to add directly to cart with a flying image animation.
     */
    function nvInjectBestSellingAddButtons() {
        $('.vi-wcaio-sidebar-cart-footer-pd').each(function () {
            var $pd = $(this);

            // Skip if already has a button
            if ($pd.find('.nv-bs-atc-btn, .vi-wcaio-pd_plus-product-bt-atc').length) return;

            var productId = $pd.data('product_id');
            var $control = $pd.find('.vi-wcaio-sidebar-cart-footer-pd-control');

            // Create the + button
            var $btn = $('<button class="nv-bs-atc-btn" title="Add to cart">+</button>');

            $btn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Prevent double-clicks
                if ($btn.hasClass('nv-bs-loading')) return;

                // If no product ID, try extracting from link URL
                var pid = productId;
                if (!pid) {
                    // Fallback: redirect to product page
                    var $link = $pd.find('.vi-wcaio-sidebar-cart-footer-pd-name a');
                    if ($link.length) window.location.href = $link.attr('href');
                    return;
                }

                // Show loading state
                $btn.addClass('nv-bs-loading');
                $btn.html('<span class="nv-bs-spinner"></span>');

                // Fire AJAX add-to-cart
                $.ajax({
                    url: (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php'),
                    type: 'POST',
                    data: {
                        action: 'naivo_quick_add_to_cart',
                        product_id: pid
                    },
                    success: function (response) {
                        // Trigger flying animation
                        nvFlyImageToCart($pd);

                        // Reset button after short delay
                        setTimeout(function () {
                            $btn.removeClass('nv-bs-loading');
                            $btn.html('✓');
                            $btn.addClass('nv-bs-added');

                            setTimeout(function () {
                                $btn.html('+');
                                $btn.removeClass('nv-bs-added');
                            }, 1500);
                        }, 300);

                        // Apply WC fragments to refresh the cart
                        if (response && response.fragments) {
                            $.each(response.fragments, function (selector, html) {
                                $(selector).replaceWith(html);
                            });
                            $(document.body).trigger('wc_fragments_refreshed');
                        }

                        // Re-apply enhancements after fragment refresh
                        setTimeout(nvApplySidebarEnhancements, 600);
                    },
                    error: function () {
                        $btn.removeClass('nv-bs-loading');
                        $btn.html('!');
                        setTimeout(function () { $btn.html('+'); }, 1500);
                    }
                });
            });

            // Insert the button
            if ($control.length) {
                $control.append($btn);
            } else {
                var $descWrap = $pd.find('.vi-wcaio-sidebar-cart-footer-pd-desc-wrap');
                if ($descWrap.length) {
                    $descWrap.after($('<div class="vi-wcaio-sidebar-cart-footer-pd-control"></div>').append($btn));
                }
            }
        });
    }

    /**
     * Animate the product image flying from the best-selling card up to the cart items area.
     */
    function nvFlyImageToCart($pd) {
        var $img = $pd.find('.vi-wcaio-sidebar-cart-footer-pd-img1, .vi-wcaio-sidebar-cart-footer-pd-img img');
        if (!$img.length) return;

        // Get the source image (visible one, might use data-src)
        var imgSrc = $img.attr('src') || $img.attr('data-src');
        if (!imgSrc) return;

        // Get positions
        var imgRect = $img[0].getBoundingClientRect();
        var $target = $('.vi-wcaio-sidebar-cart-products-wrap');
        if (!$target.length) $target = $('.vi-wcaio-sidebar-cart-header-title-wrap');
        var targetRect = $target.length ? $target[0].getBoundingClientRect() : { top: 80, left: imgRect.left };

        // Create the flying clone
        var $clone = $('<img class="nv-fly-clone" />')
            .attr('src', imgSrc)
            .css({
                position: 'fixed',
                top: imgRect.top + 'px',
                left: imgRect.left + 'px',
                width: imgRect.width + 'px',
                height: imgRect.height + 'px',
                zIndex: 999999,
                borderRadius: '8px',
                pointerEvents: 'none',
                objectFit: 'cover',
                boxShadow: '0 4px 20px rgba(0,0,0,0.25)',
                transition: 'none'
            });

        $('body').append($clone);

        // Calculate animation target (center of the products area)
        var endTop = targetRect.top + (targetRect.height ? targetRect.height / 2 : 30);
        var endLeft = targetRect.left + (targetRect.width ? targetRect.width / 2 : 100);

        // Animate using requestAnimationFrame for smooth parabolic arc
        var startTop = imgRect.top;
        var startLeft = imgRect.left;
        var startWidth = imgRect.width;
        var duration = 600; // ms
        var startTime = null;

        function animateFrame(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);

            // Ease out cubic
            var ease = 1 - Math.pow(1 - progress, 3);

            // Parabolic arc (higher in the middle)
            var arcHeight = -60 * Math.sin(progress * Math.PI);

            var currentTop = startTop + (endTop - startTop) * ease + arcHeight;
            var currentLeft = startLeft + (endLeft - startLeft) * ease;
            var currentWidth = startWidth * (1 - ease * 0.6); // shrink to 40%
            var opacity = 1 - (ease * 0.5); // fade to 50%

            $clone.css({
                top: currentTop + 'px',
                left: currentLeft + 'px',
                width: currentWidth + 'px',
                height: 'auto',
                opacity: opacity
            });

            if (progress < 1) {
                requestAnimationFrame(animateFrame);
            } else {
                // Flash the target area
                $target.addClass('nv-cart-flash');
                setTimeout(function () {
                    $target.removeClass('nv-cart-flash');
                }, 400);

                // Remove clone
                $clone.fadeOut(200, function () { $clone.remove(); });
            }
        }

        requestAnimationFrame(animateFrame);
    }

    /**
     * Apply all Figma enhancements to the sidebar cart
     */
    function nvApplySidebarEnhancements() {
        nvUpdateSidebarHeader();
        nvRelocateCoupon();
        nvMoveDeleteToQtyRow();
        nvBuildVariationDropdowns();
        nvRestructureFooter();
        nvInjectBestSellingAddButtons();
    }

    /* ── Run enhancements after DOM ready & after updates ── */
    $(document).ready(function () {
        // Initial application (small delay to let plugin render)
        setTimeout(nvApplySidebarEnhancements, 500);
        setTimeout(nvApplySidebarEnhancements, 1500);
    });

    // Re-apply after cart fragments are refreshed
    $(document).on('viwcaio_after_update_cart', function () {
        setTimeout(nvApplySidebarEnhancements, 300);
    });

    // Re-apply when sidebar opens
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.target.classList && mutation.target.classList.contains('vi-wcaio-sidebar-cart-content-wrap')) {
                if (mutation.target.classList.contains('vi-wcaio-sidebar-cart-content-open')) {
                    setTimeout(nvApplySidebarEnhancements, 200);
                }
            }
        });
    });

    $(document).ready(function () {
        var $wrap = $('.vi-wcaio-sidebar-cart-content-wrap');
        if ($wrap.length) {
            observer.observe($wrap[0], { attributes: true, attributeFilter: ['class'] });
        }
    });

    // Also hook into WooCommerce fragment refresh
    $(document.body).on('wc_fragments_refreshed', function () {
        setTimeout(nvApplySidebarEnhancements, 400);
    });

    /* ── Auto-update cart when quantity changes ──────────────── */
    // The plugin's change handler calls e.stopPropagation(), so we can't
    // listen for 'change' on the input. Instead we listen for clicks on
    // the +/- buttons and also intercept direct input changes.
    var nvQtyUpdateTimer = null;
    function nvScheduleCartUpdate() {
        clearTimeout(nvQtyUpdateTimer);
        nvQtyUpdateTimer = setTimeout(function () {
            var $updateBtn = $('.vi-wcaio-sidebar-cart-wrap .vi-wcaio-sidebar-cart-bt-update').not('.vi-wcaio-disabled').not('.vi-wcaio-bt-disabled');
            if ($updateBtn.length) {
                $updateBtn.trigger('click');
            }
        }, 800);
    }

    // Listen for clicks on the +/- quantity buttons
    $(document.body).on('click', '.vi-wcaio-sidebar-cart-pd-wrap .vi_wcaio_change_qty', function () {
        nvScheduleCartUpdate();
    });

    // Also catch manual edits to the quantity input
    $(document.body).on('input', '.vi-wcaio-sidebar-cart-pd-wrap input.vi_wcaio_qty', function () {
        nvScheduleCartUpdate();
    });

    /* ── Loading state on click ─────────────────────────────── */
    $(document).on('click', '.nv-cart-btn.ajax_add_to_cart', function () {
        var $btn = $(this);
        $btn.addClass('nv-adding');
    });

    /* ── After successful AJAX add to cart ───────────────────── */
    $(document.body).on('added_to_cart', function (evt, fragments, cart_hash, $btn) {

        /* 1. Remove loading, show success checkmark */
        if ($btn && $btn.hasClass('nv-adding')) {
            $btn.removeClass('nv-adding loading').addClass('nv-added');
            setTimeout(function () {
                $btn.removeClass('nv-added');
            }, 2000);
        }

        /* 2. Update header cart badge count */
        nvUpdateCartBadge(fragments);

        /* 3. Auto-open the side cart drawer (woo-cart-all-in-one plugin) */
        if (typeof vi_wcaio_sc_toggle === 'function') {
            // Small delay to let fragments render first
            setTimeout(function () {
                vi_wcaio_sc_toggle('show');
            }, 300);
        }

        /* 4. Pulse animation on header cart icon */
        nvPulseHeaderCart();

        /* 5. Update sidebar enhancements */
        setTimeout(nvApplySidebarEnhancements, 500);
    });

    /* ── Update header cart icon badge ───────────────────────── */
    function nvUpdateCartBadge(fragments) {
        // Try to extract count from fragments
        if (fragments) {
            // WooCommerce fragments often contain the cart widget HTML
            // Look for cart count in common selectors
            $.each(fragments, function (selector, html) {
                $(selector).replaceWith(html);
            });
        }

        // Also try updating via common Impreza/US-core cart count selectors
        var $badges = $('.w-cart-quantity, .us-cart-quantity, .cart-contents .count, .header_cart_qty');
        if ($badges.length) {
            // The fragments should have already updated these, but let's force a visual refresh
            $badges.each(function () {
                var $b = $(this);
                $b.addClass('nv-badge-pulse');
                setTimeout(function () {
                    $b.removeClass('nv-badge-pulse');
                }, 600);
            });
        }
    }

    /* ── Pulse header cart icon ──────────────────────────────── */
    function nvPulseHeaderCart() {
        var $cartIcon = $('.w-cart, .us_custom_cart, [class*="cart"] .w-cart-link, .header_cart_link');
        $cartIcon.addClass('nv-cart-pulse');
        setTimeout(function () {
            $cartIcon.removeClass('nv-cart-pulse');
        }, 800);
    }

    /* ── Ensure WC AJAX add-to-cart is initialized ──────────── */
    // WooCommerce loads `add-to-cart.min.js` which listens for clicks on `.ajax_add_to_cart`
    // It collects ALL data attributes (including variation_id) and sends them via AJAX.
    // Our server-side hook (wc_ajax_add_to_cart at priority 1) intercepts variable products.
    // We just need to make sure our buttons have the right classes (done in PHP template).

})(jQuery);
