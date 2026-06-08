/**
 * Naivo PDP – Variation Dropdown Enforcer
 *
 * The WooCommerce Variation Swatches plugin converts native <select>
 * elements into visual swatch grids (ul/li) and hides the original selects
 * with inline style="display: none;". This script reverses that:
 * - Removes inline display:none from native selects
 * - Hides the swatch wrapper elements
 */
(function ($) {
    'use strict';

    var dropdownCSS = {
        'display': 'block',
        'visibility': 'visible',
        'opacity': '1',
        'position': 'static',
        'width': '100%',
        'height': '48px',
        'border': '1.5px solid #e0e0e0',
        'border-radius': '26px',
        'padding': '0 40px 0 20px',
        'font-size': '14px',
        'font-weight': '600',
        'color': '#1a1a1a',
        '-webkit-appearance': 'none',
        '-moz-appearance': 'none',
        'appearance': 'none',
        'background-color': '#fff',
        'background-image': 'url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23333\' stroke-width=\'2.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e")',
        'background-repeat': 'no-repeat',
        'background-position': 'right 18px center',
        'background-size': '16px',
        'cursor': 'pointer',
        'box-shadow': 'none',
        'font-family': "'Manrope', sans-serif"
    };

    function enforceVariationDropdowns() {
        var $form = $('form.cart.variations_form, form.variations_form');
        if (!$form.length) return;

        // Iterate exactly once per attribute by targeting the labels
        $form.find('label').each(function () {
            var $label = $(this);
            var labelText = $label.text().toUpperCase();

            // Skip visually hidden labels or empty ones
            if (!labelText.trim()) return;

            // Find the immediate container for this specific variation (table row or specific div)
            var $row = $label.closest('tr, .woocommerce-variation-attribute, .us-woo-attribute, .attribute-row');
            if (!$row.length) {
                // Fallback: assume the label's parent or grandparent is the container
                $row = $label.closest('div, th').parent();
            }

            // Prevent duplicate processing if multiple labels exist in the same row
            if ($row.hasClass('nv-processed')) return;
            $row.addClass('nv-processed');

            var $value = $row.find('.value');
            if (!$value.length) $value = $row;

            // Find the select associated specifically with this row
            var $select = $value.find('select');

            // --- WEIGHT LOGIC ---
            if (labelText.indexOf('WEIGHT') !== -1) {
                // 1. Multiple Weights (Select exists)
                if ($select.length) {
                    if (!$row.find('.nv-custom-pills').length) {
                        var $pillsWrap = $('<ul class="nv-custom-pills"></ul>');
                        // Iterate ONLY over the options of THIS specific select element
                        $select.first().find('option').each(function () {
                            var val = $(this).val();
                            var txt = $(this).text();
                            if (val) {
                                var $pill = $('<li class="nv-pill-item" data-value="' + val + '">' + txt + '</li>');
                                if ($select.val() === val) $pill.addClass('selected');
                                $pillsWrap.append($pill);
                            }
                        });
                        $value.append($pillsWrap);

                        // Handle pill clicks
                        $pillsWrap.on('click', '.nv-pill-item', function () {
                            var val = $(this).data('value');
                            $select.val(val).trigger('change');
                            $(this).addClass('selected').siblings().removeClass('selected');
                        });
                    }
                    $select.hide().attr('style', 'display:none !important');
                }
                // 2. Single Weight (Static Text case)
                else if (!$row.find('.nv-custom-pills').length) {
                    // Try to get value from: woo-selected-variation-item-name, .value text, or variable-item
                    var staticVal = '';

                    // Strategy A: read from woo-selected-variation-item-name span
                    var $selectedSpan = $value.find('.woo-selected-variation-item-name');
                    if ($selectedSpan.length) {
                        staticVal = $selectedSpan.text().replace(/^[\s:]+/, '').trim().toUpperCase();
                    }

                    // Strategy B: read from .value td text directly
                    if (!staticVal) {
                        staticVal = $value.text().replace(/^[\s:]+/, '').trim().toUpperCase();
                    }

                    // Strategy C: read from a .variable-item (text button swatch)
                    if (!staticVal) {
                        var $varItem = $value.find('.variable-item').first();
                        if ($varItem.length) staticVal = $varItem.text().trim().toUpperCase();
                    }

                    if (staticVal && staticVal.length > 0 && staticVal.length < 20) {
                        var $pillsWrap = $('<ul class="nv-custom-pills"><li class="nv-pill-item selected">' + staticVal + '</li></ul>');
                        // Append pills into the value cell and hide original text spans
                        $value.append($pillsWrap);
                        $value.find('.woo-selected-variation-item-name').hide();
                        // Also hide any raw text nodes with ':'
                        $value.contents().each(function () {
                            if (this.nodeType === 3 && this.textContent.indexOf(':') !== -1) {
                                this.textContent = '';
                            }
                        });
                    }
                }

                // Hide standard swatch wrappers
                $row.find('.variable-items-wrapper, .woo-variation-swatches-variable-items-wrapper').hide().attr('style', 'display:none !important');
                $row.find('.nv-custom-pills').show().css('display', 'flex');
            }
            // --- OTHER ATTRIBUTES (e.g., SELECT FILTER) ---
            else {
                if ($select.length && ($select.css('display') === 'none' || !$select.hasClass('nv-enforced'))) {
                    $select.show().addClass('nv-enforced').attr('style', 'display: block !important').css(dropdownCSS);
                }
                $row.find('.variable-items-wrapper, .woo-variation-swatches-variable-items-wrapper').hide().attr('style', 'display:none !important');
            }
        });



    }


    // Declare at IIFE scope so restructurePDPForm is accessible from pill-click handler
    // (which lives outside document.ready but needs to call this function)
    var restructurePDPForm;

    // Run on initial page load
    $(document).ready(function () {
        enforceVariationDropdowns();

        // Remove "from" prefix text from variable product prices
        function removeFromPrefix() {
            $('.single-product p.price, .single-product .woocommerce-variation-price .price').each(function () {
                $(this).contents().filter(function () {
                    return this.nodeType === 3 && /^\s*(from|From|FROM)\s*:?\s*$/i.test(this.textContent.trim());
                }).remove();
                // Also hide any span.from
                $(this).find('.from, span.from').hide();
            });
        }
        removeFromPrefix();

        // Re-run after any WooCommerce variation-related events
        $(document.body).on(
            'wc_variation_form ' +
            'woocommerce_update_variation_values ' +
            'found_variation ' +
            'reset_data ' +
            'wvs_variation_swatches_loaded',
            function () {
                setTimeout(enforceVariationDropdowns, 100);
                setTimeout(removeFromPrefix, 150);
            }
        );

        // Observe DOM mutations to catch late plugin initialization
        var targetNode = document.querySelector('form.variations_form, form.cart.variations_form');
        if (targetNode && typeof MutationObserver !== 'undefined') {
            var debounceTimer;
            var observer = new MutationObserver(function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    enforceVariationDropdowns();
                }, 150);
            });
            // Watch for changes in subtree, but focus on attributes that the plugin usually toggles
            observer.observe(targetNode, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }

        // --- Gallery Carousel Fix ---
        function fixGalleryCarousel() {
            var $gallery = $('.woocommerce-product-gallery');
            if (!$gallery.length) return;

            // Ensure thumbnails are clickable
            $gallery.off('click', '.flex-control-nav li img').on('click', '.flex-control-nav li img', function (e) {
                e.preventDefault();
                var index = $(this).parent().index();
                if ($gallery.data('flexslider')) {
                    $gallery.flexslider(index);
                }
            });

            // Trigger re-init if needed, but only once
            if (!$gallery.hasClass('nv-gallery-initialized')) {
                $(document.body).trigger('wc-product-gallery-after-init', [$gallery]);
                $gallery.addClass('nv-gallery-initialized');
            }
        }

        fixGalleryCarousel();

        // --- Know About Grind Size Click Handler ---
        $(document).off('click', '.nv-grind-info').on('click', '.nv-grind-info', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if ($('#nv-grind-modal').length) return;

            // Simple overlay modal for Grind Size info
            var modalHtml = '<div id="nv-grind-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:999999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px);">' +
                '<div style="background:#fff;padding:40px;border-radius:20px;max-width:500px;width:100%;position:relative;font-family:\'Manrope\', sans-serif;box-shadow:0 20px 50px rgba(0,0,0,0.3);">' +
                '<span id="nv-modal-close" style="position:absolute;top:20px;right:25px;font-size:28px;cursor:pointer;color:#333;line-height:1;">&times;</span>' +
                '<h3 style="margin-top:0;font-weight:800;font-size:24px;color:#1a1a1a;margin-bottom:15px;">About Grind Sizes</h3>' +
                '<p style="line-height:1.6;color:#555;font-size:15px;margin-bottom:20px;">Choosing the right grind size is crucial for the perfect brew. Here is a quick guide:</p>' +
                '<div style="display:flex;flex-direction:column;gap:12px;">' +
                '<div style="background:#f9f9f9;padding:12px 16px;border-radius:10px;border-left:4px solid #111;"><strong style="display:block;margin-bottom:2px;">Whole Beans</strong> Best if you have your own grinder. Keeps coffee fresh longer.</div>' +
                '<div style="background:#f9f9f9;padding:12px 16px;border-radius:10px;border-left:4px solid #111;"><strong style="display:block;margin-bottom:2px;">Fine (Espresso)</strong> For espresso machines and moka pots.</div>' +
                '<div style="background:#f9f9f9;padding:12px 16px;border-radius:10px;border-left:4px solid #111;"><strong style="display:block;margin-bottom:2px;">Medium (Filter)</strong> For Aeropress, V60, and pour-overs.</div>' +
                '<div style="background:#f9f9f9;padding:12px 16px;border-radius:10px;border-left:4px solid #111;"><strong style="display:block;margin-bottom:2px;">Coarse (French Press)</strong> For French Press and Cold Brew.</div>' +
                '</div>' +
                '</div></div>';

            $('body').append(modalHtml).css('overflow', 'hidden');
        });

        $(document).on('click', '#nv-modal-close, #nv-grind-modal', function (e) {
            if (e.target.id === 'nv-modal-close' || e.target.id === 'nv-grind-modal') {
                $('#nv-grind-modal').fadeOut(200, function () {
                    $(this).remove();
                    $('body').css('overflow', '');
                });
            }
        });

        // --- PDP Form Restructure (Figma Parity) ---
        // Assigned to outer var so click handlers outside ready() can also call it
        restructurePDPForm = function () {
            var $form = $('.variations_form');
            if (!$form.length) return;

            // 1. Wrap quantity controls into a styled pill box (safe - doesn't move outside form)
            $('.qib-button-wrapper').each(function () {
                var $wrapper = $(this);
                if (!$wrapper.find('.nv-quantity-box').length) {
                    var $box = $('<div class="nv-quantity-box"></div>');
                    $wrapper.children().wrapAll($box);
                }
            });

            // 2. Use CSS order to position elements visually:
            //    quantity (order:1) → buttons row (order:2)
            //    Do NOT move the ATC button in the DOM — WooCommerce binds events to it in-place.
            var $addToCartArea = $form.find('.woocommerce-variation-add-to-cart, .variations_button').first();
            if (!$addToCartArea.length) $addToCartArea = $form;

            if (!$addToCartArea.hasClass('nv-restructured')) {
                $addToCartArea.addClass('nv-restructured');

                // Apply visual ordering via inline style (CSS class already sets flex-direction:column)
                var $qibWrapper = $addToCartArea.find('.qib-button-wrapper').first();
                var $atcBtn = $addToCartArea.find('.single_add_to_cart_button').first();
                var $buyBtn = $addToCartArea.find('.nv-buy-now-btn').first();

                // Quantity first
                $qibWrapper.css('order', '1');

                // Wrap Buy Now + ATC in actions row only if not already wrapped
                if ($atcBtn.length && !$atcBtn.closest('.nv-actions-row').length) {
                    var $actionsRow = $('<div class="nv-actions-row"></div>').css('order', '2');
                    // Insert the actions row BEFORE the ATC button in its current position
                    $atcBtn.before($actionsRow);
                    // Move only Buy Now into the row (it was injected separately, safe to move)
                    if ($buyBtn.length) {
                        $actionsRow.append($buyBtn);
                    }
                    // Move ATC into the row - it stays inside $addToCartArea (inside the form)
                    $actionsRow.append($atcBtn);
                }
            }

            // 3. Relocate ORIGINAL Grind Size Info button (preserves the theme modal)
            var $originalGrind = $('button.w-popup-trigger:contains("Grind"), .summary a[href*="grind"]').first();
            var $filterLabel = $('.variations tr').filter(function () {
                return $(this).find('select[name*="filter"]').length;
            }).find('.label label');

            if ($originalGrind.length && $filterLabel.length) {
                // Ensure it's not hidden by theme CSS and style it to match Figma
                $originalGrind.appendTo($filterLabel).addClass('nv-relocated-grind').css({
                    'display': 'inline-flex',
                    'background': 'none',
                    'border': 'none',
                    'padding': '0',
                    'margin-left': '10px',
                    'color': '#999',
                    'font-size': '11px',
                    'text-decoration': 'underline',
                    'cursor': 'pointer',
                    'box-shadow': 'none',
                    'vertical-align': 'baseline'
                }).find('.w-btn-label').css('font-size', '11px');

                // Add the icon if missing
                if (!$originalGrind.find('i').length) {
                    $originalGrind.prepend('ⓘ ');
                }
            }

            // REMOVE any lingering duplicates
            $('.nv-grind-info').not($originalGrind).remove();

            // 4. Add Share Icon & Click Handler
            var $sharing = $('.w-sharing, .us-sharing').first();
            if (!$sharing.length) {
                var $titleWrapper = $('.product-title-wrapper').first();
                if ($titleWrapper.length) {
                    $titleWrapper.append('<div class="w-sharing nv-custom-sharing-wrap"></div>');
                    $sharing = $('.nv-custom-sharing-wrap');
                }
            }

            if ($sharing.length) {
                $sharing.find('.w-sharing-list').remove();
                if (!$sharing.find('.nv-share-icon').length) {
                    $sharing.append('<span class="nv-share-icon" style="cursor:pointer;display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:#f4f5f9;border:1px solid #eee;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#111" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg></span>');
                }
                $sharing.show().css({ 'display': 'inline-flex', 'float': 'right', 'margin-top': '5px', 'visibility': 'visible', 'opacity': '1' });
            }

            // SHARE FUNCTIONALITY
            $(document).off('click', '.nv-share-icon').on('click', '.nv-share-icon', function (e) {
                e.preventDefault();
                if (navigator.share) {
                    navigator.share({
                        title: document.title,
                        url: window.location.href
                    }).catch(console.error);
                } else {
                    // Fallback: Copy to clipboard
                    var dummy = document.createElement('input');
                    document.body.appendChild(dummy);
                    dummy.value = window.location.href;
                    dummy.select();
                    document.execCommand('copy');
                    document.body.removeChild(dummy);
                    alert('Link copied to clipboard!');
                }
            });
        }






        restructurePDPForm();

        // One-time restructure only - no further rebuilds needed after page load.
        // The layout is permanent; WC manages button state (enabled/disabled) itself.
        setTimeout(function () {
            enforceVariationDropdowns();
            fixGalleryCarousel();
        }, 500);

    });

    // Re-enforce dropdowns after pill/swatch clicks (layout is already built, no restructure needed)
    $(document).on('click', '.variable-item, .nv-pill-item', function () {
        setTimeout(enforceVariationDropdowns, 200);
    });

    // ── Save variation_id when WC successfully matches a variation ────────────
    // This is bulletproof: whether the user clicks a pill or a native select,
    // WooCommerce eventually matches the variation and fires 'found_variation'.
    // We capture this ID so we can restore it if a plugin resets the form later.
    var nvSavedVariationId = 0;
    $(document.body).on('found_variation', function(event, variation) {
        if (variation && variation.variation_id) {
            nvSavedVariationId = variation.variation_id;
            
            // IMMEDIATELY stamp the button with the variation ID and product ID
            // so it is fully primed before any cart plugin click listeners fire.
            var $form = $(event.target).closest('form');
            var $btn = $form.find('.single_add_to_cart_button');
            if ($btn.length) {
                $btn.attr('data-variation_id', variation.variation_id);
                var productId = parseInt($form.find('input[name="product_id"]').val(), 10) || parseInt($form.data('product_id'), 10) || 0;
                if (productId > 0) { $btn.attr('data-product_id', productId); }
            }
        }
    });

    $(document).on('change', 'select[name^="attribute_"]', function (e) {
        var $form = $(this).closest('form.variations_form, form.cart');
        if (!$form.length) return;

        // IMMEDIATELY stamp this select's value to the ATC button.
        // We do this on 'change' rather than 'click' to avoid race conditions
        // with WooCommerce's own click handlers.
        var $btn = $form.find('.single_add_to_cart_button');
        if ($btn.length) {
            $form.find('select[name^="attribute_"]').each(function() {
                var name = $(this).attr('name');
                var val = $(this).val();
                $btn.attr('data-' + name, val);
            });
        }
    });

    // ── Restore variation_id on the form if it was reset ──────────────
    // This survives any plugin resets that happen after the user's real selection.
    $(document).on('click', '.single_add_to_cart_button', function () {
        var $btn      = $(this);
        var $form     = $btn.closest('form.variations_form, form.cart');
        if (!$form.length) return;

        var $varInput = $form.find('input[name="variation_id"]');
        var currentId = parseInt($varInput.val(), 10) || 0;

        // Restore saved variation_id if the form's current value was reset to 0
        if (currentId === 0 && nvSavedVariationId > 0) {
            $varInput.val(nvSavedVariationId);
        }
    });





    // ─── QUICK VIEW MODAL (Figma-exact) ───────────────────────────────────
    // State
    var nvQV = { galleryIndex: 0, data: null };

    function nvQVOpen(productId, productSlug) {
        if (!productId && !productSlug) return;

        // Ensure DOM shells exist
        if (!$('#nv-qv-overlay').length) {
            $('body').append(
                '<div id="nv-qv-overlay"></div>' +
                '<div id="nv-qv-modal" role="dialog" aria-modal="true">' +
                  '<button id="nv-qv-close" aria-label="Close">&times;</button>' +
                  '<div id="nv-qv-body"></div>' +
                '</div>'
            );
        }

        // Show loading state
        $('#nv-qv-body').html('<div class="nv-qv-loading"><span></span></div>');
        $('#nv-qv-overlay, #nv-qv-modal').fadeIn(180);
        $('body').addClass('nv-qv-open');

        // Fetch structured data via AJAX
        $.post(
            (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php'),
            { 
                action: 'naivo_quick_view_data', 
                product_id: productId || 0,
                product_slug: productSlug || ''
            },
            function(res) {
                if (!res.success) { nvQVClose(); return; }
                nvQV.data = res.data;
                nvQV.galleryIndex = 0;
                nvQVRender(res.data);
            }
        ).fail(function() { nvQVClose(); });
    }

    function nvQVRender(d) {
        var gallery = d.gallery || [];
        var weights = d.weight_options || [];
        var filters = d.filter_options || [];
        var varMap  = d.variations_map || [];

        // Determine default weight + filter
        var defVar = null;
        if (d.default_variation) {
            for (var i = 0; i < varMap.length; i++) {
                if (varMap[i].variation_id == d.default_variation) { defVar = varMap[i]; break; }
            }
        }
        if (!defVar && varMap.length) defVar = varMap[0];

        var defWeight = '';
        var defFilter = '';
        if (defVar) {
            for (var k in defVar.attributes) {
                if (k.indexOf('weight') !== -1) defWeight = defVar.attributes[k];
                if (k.indexOf('filter') !== -1 || k.indexOf('grind') !== -1) defFilter = defVar.attributes[k];
            }
        }
        if (!defWeight && weights.length) defWeight = weights[0].value || weights[0];
        if (!defFilter && filters.length) defFilter = filters[0].value;

        // ── Gallery HTML ──────────────────────────────────────
        var galleryImg = gallery.length
            ? '<img id="nv-qv-main-img" src="' + gallery[0].url + '" alt="' + (gallery[0].alt || d.name) + '" style="width: 100% !important; height: 100% !important; object-fit: contain !important; display: block !important;">'
            : '<div class="nv-qv-no-img"></div>';

        var prevArrow = gallery.length > 1
            ? '<button class="nv-qv-arrow nv-qv-prev" aria-label="Previous">&#8592;</button>'
            : '';
        var nextArrow = gallery.length > 1
            ? '<button class="nv-qv-arrow nv-qv-next" aria-label="Next">&#8594;</button>'
            : '';

        // ── Attribute pills HTML ──────────────────────────────
        var pillsHtml = '';
        if (d.profile_text) {
            var pIcon = d.profile_icon ? '<img src="' + d.profile_icon + '" alt="" class="nv-qv-pill-icon">' : '';
            pillsHtml += '<span class="nv-qv-pill" style="background:' + d.profile_bg + ';color:' + d.profile_color + ';border-color:' + d.profile_color + '20;">' + pIcon + d.profile_text + '</span>';
        }
        if (d.roast_text) {
            pillsHtml += '<span class="nv-qv-pill nv-qv-pill-roast"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>' + d.roast_text + '</span>';
        }
        if (d.country_text) {
            pillsHtml += '<span class="nv-qv-pill nv-qv-pill-country"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' + d.country_text + '</span>';
        }

        // ── Weight pills HTML ─────────────────────────────────
        var weightHtml = '';
        for (var wi = 0; wi < weights.length; wi++) {
            var wVal = weights[wi].value || weights[wi];
            var wLab = weights[wi].label || weights[wi];
            var sel = (wVal === defWeight) ? ' nv-qv-weight-selected' : '';
            weightHtml += '<button class="nv-qv-weight-pill' + sel + '" data-weight="' + wVal + '" style="height: 44px !important; border-radius: 22px !important; box-sizing: border-box !important;">' + wLab + '</button>';
        }

        // ── Filter dropdown HTML ──────────────────────────────
        var filterHtml = '';
        if (filters.length) {
            filterHtml = '<div class="nv-qv-select-wrap"><select id="nv-qv-filter" style="height: 44px !important; line-height: 44px !important; padding: 0 32px 0 14px !important; font-size: 12px !important; border-radius: 22px !important; border-color: #e0e0e0 !important; background-color: #fff !important; box-sizing: border-box !important;">';
            for (var fi = 0; fi < filters.length; fi++) {
                var selF = (filters[fi].value === defFilter) ? ' selected' : '';
                filterHtml += '<option value="' + filters[fi].value + '"' + selF + '>' + String(filters[fi].label).toUpperCase() + '</option>';
            }
            filterHtml += '</select></div>';
        } else {
            filterHtml = '<span class="nv-qv-no-filter">—</span>';
        }

        // ── Controls section ──────────────────────────────────
        var controlsHtml = '<div class="nv-qv-controls">';
        if (weights.length) {
            controlsHtml += '<div class="nv-qv-control-group"><span class="nv-qv-ctrl-label">WEIGHT</span><div class="nv-qv-weight-pills" id="nv-qv-weights">' + weightHtml + '</div></div>';
        }
        if (filters.length) {
            controlsHtml += '<div class="nv-qv-control-group"><span class="nv-qv-ctrl-label">SELECT GRIND SIZE</span>' + filterHtml + '</div>';
        }
        controlsHtml += '<div class="nv-qv-control-group"><span class="nv-qv-ctrl-label">QUANTITY</span><div class="nv-qv-qty-wrap" style="height: 44px !important; border-radius: 22px !important; display: flex !important; width: 100% !important; justify-content: space-between !important; align-items: center !important; overflow: hidden !important; border: 1.5px solid #e0e0e0 !important; background: #fff !important; box-sizing: border-box !important;"><button class="nv-qv-qty-btn" id="nv-qv-minus" style="height: 100% !important; width: 44px !important; display: flex !important; align-items: center !important; justify-content: center !important; background: transparent !important; border: none !important; font-size: 18px !important; color: #111 !important; cursor: pointer !important; flex-shrink: 0 !important;">&#8722;</button><input type="number" id="nv-qv-qty" value="1" min="1" max="99" readonly style="text-align: center !important; padding: 0 !important; text-indent: 0 !important; flex: 1 !important; width: auto !important; border: none !important; background: transparent !important; font-size: 14px !important; font-weight: 800 !important; color: #111 !important; outline: none !important; font-family: \'Manrope\', sans-serif !important; -moz-appearance: textfield !important; margin: 0 !important;"><button class="nv-qv-qty-btn" id="nv-qv-plus" style="height: 100% !important; width: 44px !important; display: flex !important; align-items: center !important; justify-content: center !important; background: transparent !important; border: none !important; font-size: 18px !important; color: #111 !important; cursor: pointer !important; flex-shrink: 0 !important;">&#43;</button></div></div>';
        controlsHtml += '</div>';

        // ── Initial price HTML ────────────────────────────────
        var priceHtml = defVar ? defVar.price_html : d.price_html;

        // ── Full modal HTML ───────────────────────────────────
        var html =
            '<div class="nv-qv-layout">' +
              '<div class="nv-qv-gallery-col" style="padding: 0 !important; overflow: hidden !important; background: #FAF5F0 !important;">' +
                prevArrow +
                '<div class="nv-qv-img-wrap" style="width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important;">' + galleryImg + '</div>' +
                nextArrow +
              '</div>' +
              '<div class="nv-qv-info-col">' +
                '<h2 class="nv-qv-title">' + d.name + '</h2>' +
                (d.flavor_notes ? '<div class="nv-qv-flavor"><span class="nv-qv-flavor-label">FLAVOUR NOTES</span><div class="nv-qv-flavor-text">' + d.flavor_notes + '</div></div>' : '') +
                (pillsHtml ? '<div class="nv-qv-pills">' + pillsHtml + '</div>' : '') +
                '<div class="nv-qv-price-row"><div id="nv-qv-price" class="nv-qv-price">' + priceHtml + '</div><a href="#" class="nv-qv-grind-link nv-grind-info">&#9432; Know about Grind Size</a></div>' +
                '<hr class="nv-qv-hr">' +
                controlsHtml +
                '<hr class="nv-qv-hr">' +
                '<button id="nv-qv-atc" class="nv-qv-atc-btn" data-product-id="' + d.product_id + '" data-variation-id="' + (defVar ? defVar.variation_id : 0) + '">ADD TO CART</button>' +
                '<a href="' + d.permalink + '" id="nv-qv-buynow" class="nv-qv-buy-btn" data-product-id="' + d.product_id + '" data-variation-id="' + (defVar ? defVar.variation_id : 0) + '">' +
                  'BUY NOW' +
                '</a>' +
                '<div class="nv-qv-secure">Payments secured by <img src="https://naivo.in/wp-content/uploads/2024/09/razorpay_logo.png" alt="Razorpay" class="nv-qv-razorpay-logo" onerror="this.style.display=\'none\'"></div>' +
                '<div class="nv-qv-pincode-wrap"><p class="nv-qv-pincode-title">Check Shipping Options And Delivery Time</p><div class="nv-qv-pincode-row"><input type="text" id="nv-qv-pincode" placeholder="PIN Code" maxlength="6"><button id="nv-qv-pin-check">CHECK</button></div><div id="nv-qv-pin-result"></div></div>' +
              '</div>' +
            '</div>';

        $('#nv-qv-body').html(html);

        // Store variation data for interactions
        $('#nv-qv-modal').data('nv-qv-data', d);
    }

    function nvQVClose() {
        $('#nv-qv-overlay, #nv-qv-modal').fadeOut(180, function() {
            $('#nv-qv-body').empty();
            $('body').removeClass('nv-qv-open');
        });
    }

    function nvQVFindVariation(data, weight, filter) {
        var varMap = data.variations_map || [];
        for (var i = 0; i < varMap.length; i++) {
            var v = varMap[i];
            var attrs = v.attributes;
            var wMatch = true, fMatch = true;
            for (var k in attrs) {
                if (k.indexOf('weight') !== -1 && weight && attrs[k] !== weight) wMatch = false;
                if ((k.indexOf('filter') !== -1 || k.indexOf('grind') !== -1) && filter && attrs[k] !== filter) fMatch = false;
            }
            if (wMatch && fMatch && v.is_in_stock) return v;
        }
        // Fallback: match just weight
        for (var i = 0; i < varMap.length; i++) {
            var v = varMap[i];
            var attrs = v.attributes;
            var wMatch = true;
            for (var k in attrs) {
                if (k.indexOf('weight') !== -1 && weight && attrs[k] !== weight) wMatch = false;
            }
            if (wMatch && v.is_in_stock) return v;
        }
        return varMap[0] || null;
    }

    // ── Open trigger ─────────────────────────────────────────
    $(document).on('click', '.nv-quick-view-btn, a[href*="/product/"]', function(e) {
        var $btn = $(this);
        
        // If it's a standard product link but doesn't have "Buy Now" text, don't intercept
        if (!$btn.hasClass('nv-quick-view-btn')) {
            var text = $btn.text().trim().toLowerCase();
            var innerLabel = $btn.find('.w-btn-label').text().trim().toLowerCase();
            if (text !== 'buy-now' && text !== 'buy now' && innerLabel !== 'buy-now' && innerLabel !== 'buy now') {
                return; // Let it navigate to product detail page as normal
            }
        }
        
        e.preventDefault();
        
        if ($btn.hasClass('nv-qv-loading-state')) return;
        $btn.addClass('nv-qv-loading-state');
        
        var pid = $btn.data('product-id') 
               || $btn.closest('[data-product_id], .nv-product-card').find('[data-product_id]').data('product_id');
        
        var slug = '';
        if (!pid) {
            var url = $btn.attr('href') || '';
            var slugMatch = url.match(/\/product\/([^\/]+)/);
            if (slugMatch) {
                slug = slugMatch[1];
            }
        }
        
        if (!pid && !slug) { 
            $btn.removeClass('nv-qv-loading-state'); 
            return; 
        }
        
        nvQVOpen(pid, slug);
        setTimeout(function() { $btn.removeClass('nv-qv-loading-state'); }, 800);
    });

    // ── Close ────────────────────────────────────────────────
    $(document).on('click', '#nv-qv-close, #nv-qv-overlay', function(e) {
        nvQVClose();
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') nvQVClose();
    });

    // ── Gallery navigation ────────────────────────────────────
    $(document).on('click', '.nv-qv-prev', function() {
        var g = nvQV.data && nvQV.data.gallery || [];
        if (!g.length) return;
        nvQV.galleryIndex = (nvQV.galleryIndex - 1 + g.length) % g.length;
        $('#nv-qv-main-img').attr('src', g[nvQV.galleryIndex].url).attr('alt', g[nvQV.galleryIndex].alt || '');
    });
    $(document).on('click', '.nv-qv-next', function() {
        var g = nvQV.data && nvQV.data.gallery || [];
        if (!g.length) return;
        nvQV.galleryIndex = (nvQV.galleryIndex + 1) % g.length;
        $('#nv-qv-main-img').attr('src', g[nvQV.galleryIndex].url).attr('alt', g[nvQV.galleryIndex].alt || '');
    });

    // ── Weight pill toggle ────────────────────────────────────
    $(document).on('click', '.nv-qv-weight-pill', function() {
        var $pill = $(this);
        $('.nv-qv-weight-pill').removeClass('nv-qv-weight-selected');
        $pill.addClass('nv-qv-weight-selected');

        var d = $('#nv-qv-modal').data('nv-qv-data');
        if (!d) return;
        var weight = $pill.data('weight');
        var filter = $('#nv-qv-filter').val() || '';
        var v = nvQVFindVariation(d, weight, filter);
        if (v) {
            $('#nv-qv-price').html(v.price_html);
            $('#nv-qv-atc, #nv-qv-buynow').data('variation-id', v.variation_id).attr('data-variation-id', v.variation_id);
        }
    });

    // ── Filter dropdown change ────────────────────────────────
    $(document).on('change', '#nv-qv-filter', function() {
        var d = $('#nv-qv-modal').data('nv-qv-data');
        if (!d) return;
        var filter = $(this).val();
        var weight = $('.nv-qv-weight-pill.nv-qv-weight-selected').data('weight') || '';
        var v = nvQVFindVariation(d, weight, filter);
        if (v) {
            $('#nv-qv-price').html(v.price_html);
            $('#nv-qv-atc, #nv-qv-buynow').data('variation-id', v.variation_id).attr('data-variation-id', v.variation_id);
        }
    });

    // ── Quantity stepper ──────────────────────────────────────
    $(document).on('click', '#nv-qv-minus', function() {
        var $q = $('#nv-qv-qty'), v = parseInt($q.val(), 10) || 1;
        if (v > 1) $q.val(v - 1);
    });
    $(document).on('click', '#nv-qv-plus', function() {
        var $q = $('#nv-qv-qty'), v = parseInt($q.val(), 10) || 1;
        $q.val(v + 1);
    });

    // ── Fly image to cart animation ───────────────────────────
    function nvQVFlyToCart() {
        var $img = $('#nv-qv-main-img');
        if (!$img.length) return;

        var imgSrc = $img.attr('src');
        if (!imgSrc) return;

        var imgRect = $img[0].getBoundingClientRect();
        // Target: sidebar cart products area or header cart icon
        var $target = $('.vi-wcaio-sidebar-cart-products-wrap');
        if (!$target.length) $target = $('.w-cart, .us_custom_cart, #sidecartOpenID');
        var targetRect = $target.length
            ? $target[0].getBoundingClientRect()
            : { top: 60, left: window.innerWidth - 80, width: 40, height: 40 };

        // Create flying clone
        var $clone = $('<img class="nv-fly-clone" />')
            .attr('src', imgSrc)
            .css({
                position: 'fixed',
                top:    imgRect.top  + 'px',
                left:   imgRect.left + 'px',
                width:  imgRect.width  + 'px',
                height: imgRect.height + 'px',
                zIndex: 1100000,
                borderRadius: '10px',
                pointerEvents: 'none',
                objectFit: 'cover',
                boxShadow: '0 6px 24px rgba(0,0,0,0.3)',
                transition: 'none'
            });

        $('body').append($clone);

        var startTop  = imgRect.top;
        var startLeft = imgRect.left;
        var startW    = imgRect.width;
        var endTop    = targetRect.top  + (targetRect.height || 40) / 2;
        var endLeft   = targetRect.left + (targetRect.width  || 40) / 2;
        var duration  = 650;
        var startTime = null;

        function animateFrame(ts) {
            if (!startTime) startTime = ts;
            var progress = Math.min((ts - startTime) / duration, 1);
            var ease     = 1 - Math.pow(1 - progress, 3);
            var arc      = -80 * Math.sin(progress * Math.PI);
            var curTop   = startTop  + (endTop  - startTop)  * ease + arc;
            var curLeft  = startLeft + (endLeft - startLeft) * ease;
            var curW     = startW * (1 - ease * 0.65);
            var opacity  = 1 - ease * 0.6;

            $clone.css({ top: curTop + 'px', left: curLeft + 'px', width: curW + 'px', height: 'auto', opacity: opacity });

            if (progress < 1) {
                requestAnimationFrame(animateFrame);
            } else {
                // Pulse target
                $target.addClass('nv-cart-flash');
                setTimeout(function() { $target.removeClass('nv-cart-flash'); }, 400);
                $clone.fadeOut(180, function() { $clone.remove(); });
            }
        }
        requestAnimationFrame(animateFrame);
    }

    // ── Shared: build AJAX post data from current modal state ─
    function nvQVBuildPostData(productId, variationId, qty) {
        var weight = $('.nv-qv-weight-pill.nv-qv-weight-selected').data('weight') || '';
        var filter = $('#nv-qv-filter').val() || '';

        if (variationId && parseInt(variationId, 10) > 0) {
            var pd = {
                product_id:   productId,
                variation_id: variationId,
                quantity:     qty
            };
            if (weight) pd['attribute_pa_weight'] = weight;
            if (filter) pd['attribute_pa_filter'] = filter;
            return pd;
        }
        return { product_id: productId, quantity: qty };
    }

    // ── Resolve correct WC AJAX URL ───────────────────────────
    function nvQVAjaxUrl() {
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
            return wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart');
        }
        return '/?wc-ajax=add_to_cart';
    }

    // ── Resolve checkout URL ──────────────────────────────────
    function nvQVCheckoutUrl() {
        if (typeof wc_add_to_cart_params !== 'undefined') {
            if (wc_add_to_cart_params.checkout_url) return wc_add_to_cart_params.checkout_url;
            if (wc_add_to_cart_params.cart_url) {
                // Derive: swap /cart/ → /checkout/
                return wc_add_to_cart_params.cart_url.replace(/\/cart\/?$/, '/checkout/');
            }
        }
        return '/checkout/';
    }

    // ── ADD TO CART ───────────────────────────────────────────
    $(document).on('click', '#nv-qv-atc', function() {
        var $btn = $(this);
        if ($btn.hasClass('nv-qv-adding')) return;

        var productId   = $btn.attr('data-product-id');
        var variationId = $btn.attr('data-variation-id');
        var qty = parseInt($('#nv-qv-qty').val(), 10) || 1;

        $btn.addClass('nv-qv-adding').text('Adding...');

        var postData = nvQVBuildPostData(productId, variationId, qty);

        $.post(nvQVAjaxUrl(), postData, function(resp) {
            var ok = resp && (resp.fragments || (resp.success !== false && !resp.error));

            if (ok) {
                // 1. Fly animation from modal image
                nvQVFlyToCart();

                // 2. Trigger WC events → opens sidebar cart, updates badge
                $(document.body).trigger('added_to_cart', [resp.fragments || {}, resp.cart_hash || '', $btn]);

                // 3. Apply WC fragments if present
                if (resp.fragments) {
                    $.each(resp.fragments, function(sel, html) {
                        $(sel).replaceWith(html);
                    });
                }

                // 4. Close modal after fly animation completes (~700ms)
                setTimeout(function() {
                    nvQVClose();
                    $btn.text('ADD TO CART').removeClass('nv-qv-adding');
                }, 700);

            } else {
                $btn.text('ADD TO CART').removeClass('nv-qv-adding');
                if (resp && resp.product_url) window.location.href = resp.product_url;
            }
        }).fail(function() {
            $btn.text('ADD TO CART').removeClass('nv-qv-adding');
        });
    });

    // ── BUY NOW ───────────────────────────────────────────────
    $(document).on('click', '#nv-qv-buynow', function(e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.hasClass('nv-qv-buying')) return;
        $btn.addClass('nv-qv-buying').css('opacity', '0.75');

        var productId   = $btn.attr('data-product-id');
        var variationId = $btn.attr('data-variation-id');
        var qty = parseInt($('#nv-qv-qty').val(), 10) || 1;

        var postData = nvQVBuildPostData(productId, variationId, qty);
        var checkoutUrl = nvQVCheckoutUrl();

        $.post(nvQVAjaxUrl(), postData, function() {
            window.location.href = checkoutUrl;
        }).fail(function() {
            window.location.href = checkoutUrl;
        });
    });

    // ── Pincode check (UI placeholder) ────────────────────────
    $(document).on('click', '#nv-qv-pin-check', function() {
        var pin = $('#nv-qv-pincode').val().replace(/\D/g, '');
        var $res = $('#nv-qv-pin-result');
        if (pin.length !== 6) {
            $res.html('<span style="color:#c0392b;font-size:12px;">Please enter a valid 6-digit PIN code.</span>');
            return;
        }
        $res.html('<span style="color:#888;font-size:12px;">Checking delivery availability...</span>');
        setTimeout(function() {
            $res.html('<span style="color:#27ae60;font-size:12px;">✓ Delivery available to ' + pin + ' (3–5 business days)</span>');
        }, 800);
    });

})(jQuery);

