(function( $ ) {
	'use strict';

	var wt_sc_giveaway_admin=
	{
		Set:function()
		{
			/* linking default giveaway select field to bogo select fields in the table. So updating BOGO fields will also update here too */
			$('.wt_sc_bogo_products_fieldset').data('parent-select', $('[name="_wt_free_product_ids[]"]'));

			/* these fields are using for both bogo and normal coupons. So the values must be populated to all fields with same name */
			$('[name="_wt_product_discount_quantity"], [name="_wt_product_discount_amount"], [name="_wt_product_discount_type"]').on('input change', function(){
				var vl=$(this).val();
				var name=$(this).attr('name');
				$('[name="'+name+'"]').val(vl);
			});
			$('[name="wt_apply_discount_before_tax_calculation"]').on('click', function(){
				var name=$(this).attr('name');			
				$('[name="'+name+'"]').prop('checked', $(this).is(':checked')); /* the another checkbox with same name */							
			});

			setTimeout(function(){
				$('._wt_max_cat_qty_field, ._wt_min_cat_qty_field').insertAfter('.wt_sc_coupon_categories_fieldset');
			}, 200);

			
			/** 
			 * Here only one common field for min/max values. 
			 * Auto-populating the values to min/max values in the category table to minimize validation complexity.
			 */
			$('#_wt_min_cat_qty, #_wt_max_cat_qty').on('input', function(){

				let elm = $(this);
				let val = elm.val();
				let name = elm.attr('name');

				if('_wt_max_cat_qty' === name)
				{
					$('input[name^="_wt_sc_coupon_category_max_qty"]').val(val);

				}else
				{
					$('input[name^="_wt_sc_coupon_category_min_qty"]').val(val);
				}
			});


			$('[name="_wt_sc_bogo_customer_gets"]').on('change', function() {

				let coupon_type = $('#discount_type').val();
				let customer_gets = (wt_sc_giveaway_params.bogo_coupon_type === coupon_type ? $(this).val() : ''); /* clear `customer_gets` value for no-bogo */
				
				let enable_pro_cat_restri_elm = $('#_wt_enable_product_category_restriction');
				let individual_min_max_elm = $('#_wt_use_individual_min_max');
				let cat_min_max_elms = $('._wt_max_cat_qty_field, ._wt_min_cat_qty_field'); /* cat min/max field container */

				if("any_product_from_category" === customer_gets || "any_product_from_store" === customer_gets || "any_product_from_category_in_the_cart" === customer_gets)
				{
					$('._wt_sc_cheapest_item_as_giveaway_field').show();
				}else
				{
					$('._wt_sc_cheapest_item_as_giveaway_field').hide();
				}

				/**
				 * 	Disable coupon restrictions like product, tag, attribute etc
				 * 
				 * 	@since 2.0.8
				 */
				if("any_product_from_category_in_the_cart" === customer_gets)
				{
					if(!individual_min_max_elm.is(':checked'))
					{
						individual_min_max_elm.trigger('click');
					}

					/* Disable editing of individual min/max functionality */
					individual_min_max_elm.prop('disabled', true).siblings('span.description').css({'opacity':'.7'});
					let individual_min_max_elm_name = individual_min_max_elm.attr('name');

					if(!$('input[type="hidden"][name="'+individual_min_max_elm_name+'"]').length)
					{
						individual_min_max_elm.before('<input type="hidden" name="'+individual_min_max_elm_name+'" value="yes" />'); /* insert a hidden element with same name. This will give value to backend */
					}

					$('.wt_sc_coupon_restriction_min_max input').prop('readonly', true);


					/* Hide product restriction fields */
					$('.wt_sc_coupon_product_restriction_fields, .wt_sc_product_attributes_fieldset, .wt_sc_product_tags_fieldset').hide();
					
					/* Empty WC default product/exclude product fields */
					$('[name="product_ids[]"]').val(null).trigger('change');
					$('[name="exclude_product_ids[]"]').val(null).trigger('change');

					/* Change the product/category restriction labels */
					$('label[for="_wt_enable_product_category_restriction"]').html(enable_pro_cat_restri_elm.attr('data-wt_sc_cat_only_label'));
					$('p._wt_enable_product_category_restriction_field span.description').html(enable_pro_cat_restri_elm.attr('data-wt_sc_cat_only_desc'));

					/* Limit category condition to `Any` */
					$('.wt_category_condition[value="or"]').trigger('click');
					$('.wt_category_condition[value="and"]').prop('disabled', true).parent('label').css({'opacity':'.7'});
					
					/* Disable Min/Max quantity, subtotal */
					$('.wt_sc_coupon_restriction_matching_products').hide().find('input[type="text"]').val('');

					/* Change the individual min/max description */
					$('p._wt_use_individual_min_max_field span.description').html(individual_min_max_elm.attr('data-wt_sc_cat_only_desc'));
				
					/* Show category min/max fields */
					cat_min_max_elms.show();

					/* Restore individual min/max values from the first row of the category table. This is usefull when user switches the `customer gets` option */
					if( $( '[name="_wt_sc_coupon_category_min_qty[0]"]' ).val() > 0 ){
						$( '#_wt_min_cat_qty').val( $( '[name="_wt_sc_coupon_category_min_qty[0]"]' ).val() );
					}
					if( $( '[name="_wt_sc_coupon_category_max_qty[0]"]' ).val() > 0 ){
						$( '#_wt_max_cat_qty').val( $( '[name="_wt_sc_coupon_category_max_qty[0]"]' ).val() );
					}
					$('#_wt_min_cat_qty, #_wt_max_cat_qty').trigger('input'); /* make all the values uniform, if multiple rows exists */
				

					/* Disable min/max spend fields (WC fields) */
					$('.minimum_amount_field, .maximum_amount_field').hide().find('input').val('');
				
				}else
				{
					/* Re-enable editing of individual min/max functionality */
					individual_min_max_elm.prop('disabled', false).siblings('span.description').css({'opacity':'1'});
					$('input[type="hidden"][name="'+individual_min_max_elm.attr('name')+'"]').remove(); /* remove the hidden element */
					$('.wt_sc_coupon_restriction_min_max input').prop('readonly', false);

					/* Show product restriction fields. Only when restriction enabled. */
					if($('.wt_enable_product_category_restriction').is(':checked'))
					{
						$('.wt_sc_coupon_product_restriction_fields, .wt_sc_product_attributes_fieldset, .wt_sc_product_tags_fieldset').show();
					}

					/* Restore the values of WC default product field from WT product fields */
					wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm($('.wt_sc_coupon_products_fieldset').find('.wt_sc_select2:eq(0)'));
					
					/* change the product/category restriction labels */
					$('p._wt_enable_product_category_restriction_field span.description').html(enable_pro_cat_restri_elm.attr('data-wt_sc_pro_cat_desc'));
					$('label[for="_wt_enable_product_category_restriction"]').html(enable_pro_cat_restri_elm.attr('data-wt_sc_pro_cat_label'));

					/* Remove category condition limitation */
					$('.wt_category_condition[value="and"]').prop('disabled', false).parent('label').css({'opacity':'1'});
				
					/* Re-enable Min/Max quantity, subtotal */
					$('.wt_sc_coupon_restriction_matching_products').show();

					/* Change the individual min/max description */
					$('p._wt_use_individual_min_max_field span.description').html(individual_min_max_elm.attr('data-wt_sc_pro_cat_desc'));
				
					/* hide category min/max fields */
					cat_min_max_elms.hide().val('');

					/* Re-enable min/max spend fields (WC fields) */
					$('.minimum_amount_field, .maximum_amount_field').show();
				}

				/**
				 * 	Enable `convert existing as giveaway` on `specific product`
				 * 
				 * 	@since 2.2.0
				 */
				if("specific_product" === customer_gets) {
					$('._wt_sc_convert_existing_as_giveaway_field').show();
				}else {
					$('._wt_sc_convert_existing_as_giveaway_field').hide();
				}

			});

			
			$('.wt_sc_coupon_categories_fieldset .wt_sc_meta_item_tb_add_row').on('click', function(){

				if(wt_sc_giveaway_params.bogo_coupon_type === $('#discount_type').val() && "any_product_from_category_in_the_cart" === $('[name="_wt_sc_bogo_customer_gets"]').val())
				{
					setTimeout(function(){	
						$('#_wt_min_cat_qty, #_wt_max_cat_qty').trigger('input');
					}, 100);
				}
				
			});


			$('.wt_enable_product_category_restriction').on('click', function(){
				
				setTimeout(function(){	
					$('[name="_wt_sc_bogo_customer_gets"]').trigger('change');
				}, 100);
			});

			this.give_away_tab_switch();
			
		},
		give_away_tab_switch:function()
		{
			$('#discount_type').on('change', function(){
				var type = $(this).val();
				if(type == wt_sc_giveaway_params.bogo_coupon_type)
				{
					$('.wt_sc_normal_coupon_giveaway_tab_content').hide(); /* hide default giveaway tab */
					$('.wt_sc_bogo_coupon_giveaway_tab_content').show(); /* show bogo giveaway tab */
					$('.coupon_amount_field').hide(); /* hide coupon amount field */
					$('._wt_sc_bogo_apply_frequency_field').show(); /* Show `Coupon apply frequency` option */
					
					wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm($('.wt_sc_bogo_products_fieldset').find('.wt_sc_select2:eq(0)'));				

				}else
				{
					$('.wt_sc_normal_coupon_giveaway_tab_content').show(); /* show default giveaway tab */
					$('.wt_sc_bogo_coupon_giveaway_tab_content').hide(); /* hide bogo giveaway tab */
					$('.coupon_amount_field').show(); /* hide coupon amount field */
					$('._wt_sc_bogo_apply_frequency_field').hide(); /* Hide `Coupon apply frequency` option */
				}

				$('[name="_wt_sc_bogo_customer_gets"]').trigger('change'); /* handle `customer gets` onchange events */

			});
			$('#discount_type').trigger('change'); /* toggle visibility on page load */
		}
	};

	$(document).ready(function(){
		wt_sc_giveaway_admin.Set();
	});

})( jQuery );