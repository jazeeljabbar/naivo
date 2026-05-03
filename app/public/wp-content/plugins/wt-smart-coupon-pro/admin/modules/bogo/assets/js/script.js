/**
 * BOGO admin side JS file.
 *
 * @since 3.0.0
 * @package Wt_Smart_Coupon
 */

( function ( $ ) {
	'use strict';

	var wbte_sc_bogo_form_submitted  = false;
	var wbte_sc_bogo_user_interacted = false;

	$( document ).ready(
		function () {

			$( '.wbte_sc_bogo_switching_btn' ).on(
				'click',
				function ( e ) {
					e.preventDefault();

					const old_bogo_count = $( this ).attr( 'data-old-bogo-count' );
					
					if ( 0 < old_bogo_count ) {
						if ( !confirm( wbte_sc_bogo_params.text.continue_confirm ) ) {
							return;
						}
					}

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							data: {
								'action'	: 'wbte_sc_switch_to_new_bogo',
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function ( data ) {
								window.location.reload();
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			$( '.woocommerce-help-tip' ).tipTip(
				{
					'attribute': 'data-tip',
					'fadeIn':    50,
					'fadeOut':   50,
					'delay':     200
				}
			);

			$( '.wbte_sc_new_campaign_box, .wbte_sc_bogo_add_new_popup_predefined p' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					$( this ).addClass( 'wbte_sc_new_campaign_box_selected' );
					$( '.wbte_sc_new_campaign_box, .wbte_sc_bogo_add_new_popup_predefined p' ).not( this ).removeClass( 'wbte_sc_new_campaign_box_selected' );
					$( '#wbte_sc_bogo_campaign_selected_default' ).val( $( this ).attr( 'data-default-btn' ) );

					if ( $( '.wbte_sc_bogo_add_new_popup_form' ).length ) {
						$( '.wbte_sc_bogo_add_new_popup_form' ).css( 'display', 'block' );
						$( this ).css( { 'font-weight' : '700', 'border-color' : '#CCE3FF', 'background-color' : '#F1F8FE' } );
						$( '.wbte_sc_bogo_add_new_popup_predefined p' ).not( this ).css( { 'border-color' : '#EAEBED', 'font-weight' : 'normal', 'background-color' : 'white' } );
					}

					$( '.wbte_sc_bogo_campaign_submit, .wbte_sc_bogo_add_new_continue' ).css( { 'background-color' : '#3157A6', 'cursor' : 'pointer', 'pointer-events' : 'auto' } );
					$( '.wbte_sc_bogo_text_input' ).css( { 'pointer-events' : 'auto', 'border' : '15px solid Ff0000 !important' } );
					$( '.wbte_sc_new_campaign_form_contents' ).css( { 'cursor' : 'pointer', 'color' : '#2A3646' } );
				}
			);

			$( '.wbte_sc_new_campaign_box.default, .wbte_sc_bogo_add_new_popup_predefined p:not( .custom )' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					if ( $( this ).find( 'p' ).length ) {
						$( '#wbte_sc_bogo_coupon_name' ).val( $( this ).find( 'p' ).text().trim() );
					} else {
						$( '#wbte_sc_bogo_coupon_name' ).val( $( this ).text().trim() );
						$( '#wbte_sc_bogo_campaign_description' ).val( $( this ).attr( 'data-desc' ) );
					}
					if ( $( this ).parent().find( '.wbte_sc_new_campaign_box_default_tooltip' ).length ) {
						$( '#wbte_sc_bogo_campaign_description' ).val( $( this ).parent().find( '.wbte_sc_new_campaign_box_default_tooltip' ).text().trim() );
					}

					$( '.wbte_sc_bogo_campaign_custom_radio' ).hide();
				}
			);

			$( '.wbte_sc_new_campaign_box:not( .default ), .wbte_sc_bogo_add_new_popup_predefined p.custom' ).on(
				'click',
				function ( e ) {
					e.preventDefault();

					$( '.wbte_sc_bogo_campaign_custom_radio' ).show();
					$( '#wbte_sc_bogo_coupon_name' ).val( '' );
					$( '#wbte_sc_bogo_campaign_description' ).val( '' );
				}
			);

			wbte_sc_bogo_add_new();

			/** Bogo listing select all */
			$( '#wbte_sc_bogo_listing_check_all' ).on(
				'change',
				function () {
					
					wbte_sc_bogo_display_list_selected();
				}
			);

			/** Add search element to current URL and reload the page */
			$( '#wbte_bogo_search' ).on(
				'keydown',
				function ( event ) {
					if ( 13 === event.keyCode || 'Enter' === event.key ) {
						event.preventDefault();

						wbte_sc_bogo_search_listing();
					}
				}
			);

			$( '.wbte_bogo_search_icon' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					wbte_sc_bogo_search_listing();
				}
			);

			$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).on(
				'change',
				function () {
					wbte_sc_bogo_display_list_selected();
				}
			);

			$( '.wbte_sc_bogo_status_filtering' ).on(
				'click',
				function () {
					if ( $( '.wbte_sc_bogo_listing_status_filter_dropdown' ).is( ':visible' ) ) {
						$( '.wbte_sc_bogo_listing_status_filter_dropdown' ).hide();
					} else {
						$( '.wbte_sc_bogo_listing_status_filter_dropdown' ).css( 'display', 'flex' );
					}
				}
			);

			/** To add 'Request a feature' button in the help widget */
			$( '.wbte_sc_help-widget_popupover ul' ).children().eq( 1 ).addClass( 'wt_sc_request_a_feature_btn' ).attr( 'data-wt_sc_popup', 'wt_sc_request_a_feature_popup' );

			/** Bogo general settings click */
			$( '.wbte_sc_bogo_general_settings_button' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '#wbte_sc_bogo_general_settings' ).css( { 'width' : '367px', 'padding' : '26px 29px 0 29px' } );
					wbte_sc_bogo_show_overlay();
				}
			);

			$( '.wbte_sc_bogo_placeholder' ).on(
				'click',
				function () {
					const parent_input = $( this ).attr( 'data-parent-input' );
					const inputElement = $( '#' + parent_input );
					const startPos     = inputElement[0].selectionStart;
					const endPos       = inputElement[0].selectionEnd;
					const inputValue   = inputElement.val();
					const selectedText = inputValue.substring( startPos, endPos );
					const newText      = $( this ).attr( 'id' );

					inputElement.val( inputValue.substring( 0, startPos ) + newText + selectedText + inputValue.substring( endPos ) );
					inputElement[0].setSelectionRange( startPos + newText.length, startPos + newText.length );

					inputElement.trigger( 'focus' );
				}
			);

			$( '.wbte_sc_bogo_general_settings_close' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '#wbte_sc_bogo_general_settings' ).css( { 'width' : '0', 'padding' : '26px 0px' } );
					wbte_sc_bogo_remove_overlay();
				}
			);

			wbte_sc_submit_general_settings();

			$( '#wpbody' ).on(
				'click',
				function (e) {
					if ( $( '.wbte_sc_bogo_general_settings' ).length
					&& (
					( '0px' !== $( '#wbte_sc_bogo_general_settings' ).css( 'width' ) )
					|| ( $( '.wbte_sc_bogo_add_new_popup' ).length && 'none' !== $( '.wbte_sc_bogo_add_new_popup' ).css( 'display' ) )
					|| ( $( '.wbte_sc_delete_bogo_popup' ).length && 'none' !== $( '.wbte_sc_delete_bogo_popup' ).css( 'display' ) )
					)
					) {

						if ( "wbte_sc_bogo_general_settings" === e.target.id
							|| $( e.target ).hasClass( 'wbte_sc_add_new_bogo' )
							|| 'wbte_sc_bogo_listing_single_delete' === e.target.className
							) {
							return;
						}

						if ( $( e.target ).parents( '#wbte_sc_bogo_general_settings' ).length
						|| $( e.target ).parents( '.wbte_sc_bogo_add_new_popup' ).length
						|| $( e.target ).parents( '.wbte_sc_delete_bogo_popup' ).length
							) {
								return;
						}

						e.preventDefault();

						$( '#wbte_sc_bogo_general_settings' ).css( { 'width' : '0', 'padding' : '26px 0px' } );
						$( '.wbte_sc_bogo_add_new_popup, .wbte_sc_delete_bogo_popup' ).hide();
						wbte_sc_bogo_remove_overlay();
					}

					if ( $( '.wbte_sc_bogo_listing_status_filter_dropdown' ).is( ':visible' ) && 0 === $( e.target ).closest( '.wbte_sc_bogo_status_filtering' ).length ) {
						$( '.wbte_sc_bogo_listing_status_filter_dropdown' ).hide();
					}
				}
			);

			/**Add new BOGO click */
			$( '.wbte_sc_add_new_bogo' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_bogo_add_new_popup' ).show();
					wbte_sc_bogo_show_overlay();
				}
			);

			/** BOGO add popup radio */
			$( 'input[type = radio][name = wbte_sc_bogo_type]' ).on(
				'change',
				function () {
					if ( 'wbte_sc_bogo_cheap_expensive' === $(this).val() ) {
							$( '.wbte_sc_bogo_custom_cheap_expensive_img' ).css( 'display' , 'block' );
							$( '.wbte_sc_bogo_custom_bogo_img' ).hide();
					} else {
						$( '.wbte_sc_bogo_custom_bogo_img' ).css( 'display' , 'block' );
						$( '.wbte_sc_bogo_custom_cheap_expensive_img' ).hide();
					}
				}
			);

			/** BOGO 'add new popup' close */
			$( '.wbte_sc_bogo_add_new_cancel' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_bogo_add_new_popup' ).hide();
					wbte_sc_bogo_remove_overlay();
				}
			);

			/** Delete bogo coupons from listing, ajax action */
			$( '.wbte_sc_bogo_listing_single_delete' ).on(
				'click',
				function (e) {
					if ( $( this ).closest( '.wbte_sc_bogo_listing_actions_content' ).hasClass( 'wbte_sc_bogo_master_coupon' ) ) {
						return;
					}

					e.preventDefault();
					const coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								'action'	: 'wbte_sc_bogo_delete_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								window.location.reload();
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);

				}
			);

			/** Hide BOGO delete popup when click on cancel button */
			$( '.wbte_sc_delete_bogo_cancel' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_blanket, .wbte_sc_popup' ).hide();
				}
			);

			/** Duplicate bogo coupons from listing, ajax action. After duplicating it will redirect to coupon edit page */
			$( '.wbte_sc_bogo_listing_single_duplicate' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					const $coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );
					jQuery.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_single_duplicate',
								'coupon_id'	  : $coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status && 0 !== data.id && data.url ) {
									window.location.href = data.url;
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_bogo_remove_overlay();
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Delete multiple coupons at a time ( to trash ). */
			$( '.wbte_sc_bogo_multiple_trash' ).on(
				'click',
				function (e) {
					e.preventDefault();
					var checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_delete_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {

									var currentUrl = new URL( window.location.href );
									/** Preserve existing query parameters */
									var params = new URLSearchParams( currentUrl.search );

									var keys       = [...params.keys()]
									var retainList = ['page']
									for (var key of keys) {
										if ( ! retainList.includes( key )) {
											params.delete( key )
										}
									};

									/** Construct the new URL */
									var newUrl = currentUrl.origin + currentUrl.pathname + '?' + params.toString();

									window.location.href = newUrl;
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Enable multiple coupons at a time ( draft to publish ). */
			$( '.wbte_sc_bogo_listing_selected_enable' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					var checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_multiple_enable',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status ) {
									data.changed_arrs.forEach(
										function (couponId) {
											var row        = $( `tr[data-coupon_id = "${couponId}"]` );
											var row_status = row.find( '.wbte_sc_bogo_listing_table_status span' );
											var toggle     = row.find( '.wbte_sc_toggle-checkbox' );
											toggle.prop( 'checked', true );
											row_status.removeClass();
											row_status.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
										}
									);
									$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).each(
										function () {
											$( this ).prop( 'checked', false );
										}
									);
									$( '.wbte_sc_bogo_listing_selected_div' ).hide();
									$( '#wbte_sc_bogo_listing_check_all' ).prop( 'checked', false );
									wbte_sc_bogo_remove_overlay();
									if ( data.changed_arrs.length > 0 ) {
										wbte_sc_notify_msg.success( data.msg );
									}
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Disable multiple coupons at a time ( publish to draft ). */
			$( '.wbte_sc_bogo_listing_selected_disable' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					var checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_multiple_disable',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status ) {
									data.changed_arrs.forEach(
										function (couponId) {
											var row        = $( `tr[data-coupon_id = "${couponId}"]` );
											var row_status = row.find( '.wbte_sc_bogo_listing_table_status span' );
											var toggle     = row.find( '.wbte_sc_toggle-checkbox' );
											toggle.prop( 'checked', false );
											row_status.removeClass();
											row_status.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
										}
									);
									$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).each(
										function () {
											$( this ).prop( 'checked', false );
										}
									);
									$( '.wbte_sc_bogo_listing_selected_div' ).hide();
									$( '#wbte_sc_bogo_listing_check_all' ).prop( 'checked', false );
									wbte_sc_bogo_remove_overlay();
									if ( data.changed_arrs.length > 0 ) {
										wbte_sc_notify_msg.success( data.msg );
									}
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Bogo listing trash screen function */

			/** Restore single coupon from trash */
			$( '.wbte_sc_bogo_listing_single_restore' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								'action'	: 'wbte_sc_bogo_restore_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												let currentUrl = window.location.href;

												let url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Delete permanently single coupon from trash, adding coupon id to popup */
			$( '.wbte_sc_bogo_single_perm_dlt_listing' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const parent_tr    = $( this ).closest( 'tr' );
					const coupon_id    = parent_tr.attr( 'data-coupon_id' );
					const coupon_title = parent_tr.find( '.wbte_sc_bogo_listing_table_title h3' ).text();
					$( '.wbte_sc_bogo_single_dlt_title' ).text( coupon_title );
					$( '.wbte_sc_popup[data-id="wbte_sc_bogo_delete_popup_single"]' ).attr( 'data-coupon_id', coupon_id );

				}
			);

			$( '.wbte_sc_bogo_single_perm_delete' ).on(
				'click',
				function (e) {

					e.preventDefault();
					const coupon_id = $( this ).closest( '.wbte_sc_popup' ).attr( 'data-coupon_id' );
					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							data: {
								'action'	: 'wbte_sc_bogo_perm_dlt_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												let currentUrl = window.location.href;

												let url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
										}
									);
									window.location.reload();
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Multiple bogo coupons restore from trash */
			$( '.wbte_sc_bogo_listing_selected_restore' ).on(
				'click',
				function (e) {
					e.preventDefault();
					var checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_restore_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												let currentUrl = window.location.href;

												let url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Multiple bogo coupons delete permanently from trash */
			$( '.wbte_sc_delete_perm_bogo_multiple' ).on(
				'click',
				function (e) {
					e.preventDefault();
					var checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_perm_dlt_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												let currentUrl = window.location.href;

												let url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			wbte_sc_bogo_listing_status_toggle();

			$( '.wbte_sc_bogo_listing_table tbody tr' ).on(
				'click',
				function ( e ) {
					if ( 
						0 < $( e.target ).closest( '.wbte_sc_bogo_listing_actions_content' ).length 
						|| 0 < $( e.target ).closest( '.wbte_sc_bogo_listing_trash_actions_content' ).length 
						|| $( e.target ).hasClass( 'wbte_sc_bogo_listing_checkbox' )
						|| 0 < $( e.target ).closest( '.wbte_sc_bogo_listing_checkbox_td' ).length
					) {
						return;
					}
					window.location.href = $( this ).attr( 'data-edit-url' );
				}
			)

			$( document ).on(
				'change',
				'input[ type=checkbox ][ name^=wbte_sc_bogo_listing_filters ]',
				function () {
					var selected_filters = [];
					$( 'input[ type=checkbox ][ name^=wbte_sc_bogo_listing_filters ]:checked' ).each(
						function () {
							selected_filters.push( $( this ).val() );
						}
					);

					var currentUrl = new URL( window.location.href );

					/** Preserve existing query parameters */
					var params       = new URLSearchParams( currentUrl.search );
					var pagenumValue = params.get( 'pagenum' );
					params.delete( 'pagenum' );

					var keys       = [...params.keys()]
					var retainList = ['page', 'listing_status', 'search']
					for (var key of keys) {
						if ( ! retainList.includes( key )) {
							params.delete( key )
						}
					};

					if ( 3 !== selected_filters.length ) {
						selected_filters.forEach(
							function (filter) {
								params.append( 'listing_filters[]', filter );
							}
						);
					}

					/** Append 'pagenum' at the end of the params, pagination purpose. */
					if (pagenumValue) {
						params.append( 'pagenum', pagenumValue );
					}

					var newUrl = currentUrl.origin + currentUrl.pathname + '?' + params.toString();

					window.location.href = newUrl;
				}
			);

			if ( 0 < $( '.wbte_sc_bogo_edit_step' ).length ) {
				/** Bogo edit page */
				wbte_sc_bogo_edit_page();

				// Adjust width for some elements when the sidebar is collapsed.
				$( document ).on( 
					'wp-collapse-menu', 
					function() { 

						const is_sidebar_collapsed = 0 < $( '.folded #adminmenu' ).length;

						if( wbte_sc_bogo_params.is_rtl ){

							if ( is_sidebar_collapsed ) {
								$( '.wbte_sc_bogo_edit_general' ).css( 'right', '37px' );
								$( '.wbte_sc_bogo_edit_save_buttons' ).css( 'right', '37px' );
							}else{
								$( '.wbte_sc_bogo_edit_general' ).css( 'right', '163px' );
								$( '.wbte_sc_bogo_edit_save_buttons' ).css( 'right', '163px' );
							}
						}
					} 
				);

			}

		}
	);

	function wbte_sc_bogo_search_listing(){
		var inputValue = $( '#wbte_bogo_search' ).val();
		var currentUrl = window.location.href;
		var url        = new URL( currentUrl );

		if (inputValue.trim() === "") {
			url.searchParams.delete( 'search' );
		} else {
			url.searchParams.set( 'search', inputValue );
		}

		window.location.href = url.toString();
	}

	function wbte_sc_bogo_add_new(){
		$( '#wbte_sc_new_bogo_coupon' ).on(
			'submit',
			function (e) {
				e.preventDefault();
				$( '.wbte_sc_bogo_overlay' ).css( 'z-index' , 5 )
				wbte_sc_bogo_show_overlay();
				var data = $( this ).serialize();
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' : 'wbte_sc_bogo_add_new',
							'data'	 : data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function (data) {
							if ( data.status && 0 !== data.id && data.url ) {
								window.location.href = data.url;
							}else{
								window.location.reload();
							}
						},
						error:function ( data ) {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	/** BOGO main general settings form submit. */
	function wbte_sc_submit_general_settings(){
		$( '#wbte_sc_bogo_general_settings_form' ).on(
			'submit',
			function (e) {
				e.preventDefault();

				var data = $( this ).serialize();
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' : 'wbte_sc_bogo_general_settings',
							'data'	: data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function (data) {
							if ( data.status ) {
								$( '.wbte_sc_bogo_general_settings' ).css( { 'width' : '0', 'padding' : '26px 0px' } );
								wbte_sc_bogo_remove_overlay();
								wbte_sc_notify_msg.success( data.msg );
							}
							else{
								window.location.reload();
							}
						},
						error:function ( data ) {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	/** Returns bogo coupon counts on ajax success. */
	function wbte_sc_trash_bogo_count() {
		var deferred = $.Deferred();

		$.ajax(
			{
				type: "POST",
				dataType: 'json',
				url: wbte_sc_bogo_params.ajaxurl,
				data: {
					'action': 'wbte_sc_trash_bogo_count_ajax'
				},
				success: function (data) {
					deferred.resolve( data.count );
				},
				error: function () {
					deferred.reject();
				}
			}
		);
		return deferred.promise();
	}

	/** Bogo coupon status toggle button action in listing page, ajax action. Switch between status publish and draft. */
	function wbte_sc_bogo_listing_status_toggle(){

		$( 'input[name="wbte_sc_bogo_listing_actions_toggle"]' ).on(
			'change',
			function (e) {
				const coupon_id      = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );
				const is_checked     = $( this ).is( ':checked' );
				const status_element = $( this ).closest( 'tr' ).find( '.wbte_sc_bogo_listing_table_status span' );
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action'      : 'wbte_sc_bogo_listing_update_status_on_toggle',
							'data'		  : {
								'coupon_id'	  : coupon_id,
								'is_checked'  : is_checked
							},
							'_wpnonce'	  : wbte_sc_bogo_params.admin_nonce
						},
						success:function ( data ) {
							if ( data.status ) {
								status_element.removeClass();
								status_element.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
								wbte_sc_notify_msg.success( data.msg );
							}
							else{
								window.location.reload();
							}
						},
						error:function ( data ) {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	function wbte_sc_bogo_selected_count(){
		return $( 'input[name="wbte_sc_bogo_listing_check_ind"]:checked' ).length;
	}

	function wbte_sc_bogo_get_selected_coupons_ids(){
		var selected_coupons = [];
		$( 'input[name="wbte_sc_bogo_listing_check_ind"]:checked' ).each(
			function () {
				selected_coupons.push( $( this ).closest( 'tr' ).attr( 'data-coupon_id' ) );
			}
		);
		return selected_coupons;
	}

	function wbte_sc_bogo_display_list_selected(){
		if ( 0 < wbte_sc_bogo_selected_count() ) {
			const new_text = '(' + wbte_sc_bogo_selected_count() + ' ' + wbte_sc_bogo_params.text.selected + ')';
			$( '.wbte_sc_bogo_listing_selected_div' ).css( 'display', 'flex' );
			$( '.wbte_sc_bogo_listing_selected_div_select_count' ).text( new_text );
		} else {
			$( '.wbte_sc_bogo_listing_selected_div' ).hide();
			$( '.wbte_sc_bogo_listing_selected_div_select_count' ).text( wbte_sc_bogo_params.text.selected );
		}
	}

	function wbte_sc_bogo_show_overlay(){
		$( '#wpbody' ).prepend( '<div class="wbte_sc_bogo_overlay"></div>' );
		$( 'html, body' ).css( { overflow: 'hidden', height: '100%' } );
	}

	function wbte_sc_bogo_remove_overlay(){
		$( '#wpbody .wbte_sc_bogo_overlay' ).remove();
		$( 'html, body' ).css( { overflow: 'auto', height: 'auto' } );
	}

	/** Actions or bogo edit page */
	function wbte_sc_bogo_edit_page(){

		/** Set boolean to check if user has interacted with the form */
		$( '#wbte_sc_bogo_coupon_save' ).on(
			'input change',
			function () {
				wbte_sc_bogo_user_interacted = true; /** User has interacted with the form */
			}
		);

		/** Show confirmation message when user tries to leave the page without saving the form */
		$( window ).on(
			'beforeunload',
			function (e) {
				if ( wbte_sc_bogo_user_interacted && ! wbte_sc_bogo_form_submitted ) {
					const confirmationMessage = wbte_sc_bogo_params.err_msgs.browser_leaving;
					e.preventDefault();
					e.returnValue = confirmationMessage;
					return confirmationMessage;
				}
			}
		);

		/** Individual summary, other values settings on page load. */
		wbte_sc_bogo_val_set_on_pageload();

		/** Add arrow icon base on open/closed state */
		$( '.wbte_sc_bogo_edit_step' ).each(
			function () {
				if ( $( this ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) {
						$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-up-alt2' );
				} else {
					$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-down-alt2' );
				}
			}
		);

		/** Action to trigger on clicking step container */
		$( '.wbte_sc_bogo_edit_step' ).on(
			'click',
			function (e) {
				if ( ! $( this ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) { /** Closed state */
					$( this ).addClass( 'wbte_sc_bogo_step_container_opened' );
					$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).removeClass( 'dashicons-arrow-down-alt2' ).addClass( 'dashicons-arrow-up-alt2' );
					$( '.wbte_sc_bogo_edit_step' ).not( $( this ).closest( '.wbte_sc_bogo_edit_step' ) ).removeClass( 'wbte_sc_bogo_step_container_opened' );
					$( '.wbte_sc_bogo_step_arrow' ).not( $( this ).find( 'span.wbte_sc_bogo_step_arrow' ) ).removeClass( 'dashicons-arrow-up-alt2' ).addClass( 'dashicons-arrow-down-alt2' );
				}
				wbte_sc_bogo_step2_add_conditions_summary();
				wbte_sc_bogo_custom_short_summary();
			}
		);

		/** Close step container on clicking header div in step container */
		$( '.wbte_sc_bogo_edit_step_head, .wbte_sc_bogo_step_arrow' ).on(
			'click',
			function () {
				if ( $( this ).closest( '.wbte_sc_bogo_edit_step' ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) { /** Opened state */
					setTimeout(
						() => {
							$( this ).closest( '.wbte_sc_bogo_edit_step' ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-down-alt2' ).removeClass( 'dashicons-arrow-up-alt2' );
							$( this ).closest( '.wbte_sc_bogo_edit_step' ).removeClass( 'wbte_sc_bogo_step_container_opened' );
						},
						10
					);
				}
			}
		);

		/**
		 *  Only allow numbers with decimal in some fields
		 */
		$( document ).on(
			'input',
			'.wbte_sc_bogo_input_only_numbers_with_decimal',
			function () {
				var vl  = $( this ).val();
				var reg = /^[0-9]*\.?[0-9]*$/;

				if ( ! reg.test( vl ) ) {
					var new_vl        = '';
					vl                = String( vl );
					var val_length    = vl.length;
					var decimal_found = false;
					for ( var i = 0; i < val_length; i++ ) {
						if ( vl[i] === '.' ) {
							if ( decimal_found ) {
								continue;
							} else {
								decimal_found = true;
							}
						}
						if ( reg.test( vl[i] ) || (vl[i] === '.' && ! decimal_found) ) {
							new_vl += vl[i];
						}
					}
					$( this ).val( new_vl );
				}
			}
		);

		/**
		 *  Only allow numbers
		 */
		$( document ).on(
			'input',
			'.wbte_sc_bogo_input_only_number',
			function () {
				var vl  = $( this ).val();
				var reg = /^[0-9]*$/;

				if ( ! reg.test( vl ) ) {
					var new_vl     = '';
					vl             = String( vl );
					var val_length = vl.length;
					for ( var i = 0; i < val_length; i++ ) {
						if ( reg.test( vl[i] ) ) {
							new_vl += vl[i];
						}
					}
					$( this ).val( new_vl );
				}
			}
		);

		/** Change in hidden/visible and disable/enable dropdown on clicking grouped dropdown item */
		$('.wbte_sc_bogo_edit_custom_drop_down p')
			.not('.wbte_sc_bogo_dropdown_menu_item_head')
			.on('click', function () {
				const group = $(this).attr( 'data-group' );

				if( group ){
					const currentOneRow = $(this).attr( 'data-row' );
					$( '.wbte_sc_bogo_edit_custom_drop_down p[data-group="' + group + '"]' ).each( function () {
						const dataRow = $( this ).attr( 'data-row' );
						if( dataRow && dataRow !== currentOneRow ){
							$(`tr[data-row="${dataRow}"]`).addClass('wbte_sc_bogo_conditional_hidden');
						}
						$( this ).removeClass( 'wbte_sc_disabled' );
						$( this ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
					});
				}
			}
		);

		wbte_sc_bogo_step2();

		/** Removing tab when clicks on delete icon */
		$( '.wbte_sc_bogo_edit_trash' ).on(
			'click',
			function () {
				if ( $( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_apply_repeatedly_custom_row' ) ) {
					setTimeout(
						() => {
							$( this ).closest( 'tr' ).remove();
							wbte_sc_bogo_apply_repeatedly_fields_index();
						},
						10
					);
					return;
				}
				$( this ).closest( 'tr' ).addClass( 'wbte_sc_bogo_conditional_hidden' );

				const trDataRow = $( this ).closest( 'tr' ).attr( 'data-row' );
				if ( trDataRow ) {
					$( '.wbte_sc_bogo_edit_custom_drop_down p' ).each( function () { 
						if ( trDataRow === $( this ).attr( 'data-row' ) ) {
							$( this ).removeClass( 'wbte_sc_disabled' );
							$( this ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
						}
					 } );
				}
				wbte_sc_bogo_show_prod_cat_and_or();
				wbte_sc_bogo_show_prod_cat_default();
			}
		);

		/** Step 1 */
		wbte_sc_bogo_step1();

		$( document ).on(
			'click',
			function ( e ) {
				if( 
					0 < $(e.target).closest('.wbte_sc_bogo_edit_custom_drop_down_head').length
				){
					return;
				}
				$( '.wbte_sc_bogo_edit_custom_drop_down, .wbte_sc_bogo_submenu' ).fadeOut();
			}
		);

		$( '.wbte_sc_bogo_edit_custom_drop_down_btn' ).on(
			'click',
			function () {
				if ( $( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).is( ':visible' ) ) {
					$( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).fadeOut();
				} else {
					$( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).fadeIn();
				}
			}
		);

		/** Step 3 */
		wbte_sc_bogo_step3();

		/** Edit page general settings */
		wbte_sc_bogo_edit_general_settings();

		/** Bogo coupon save */
		wbte_sc_bogo_coupon_save();

		wbte_sc_bogo_realtime_validation();

		$( '#_wbte_sc_bogo_min_amount, #_wbte_sc_bogo_min_qty, input[name="wbte_sc_bogo_triggers_when"], #wbte_sc_bogo_customer_gets_qty, input[name="wbte_sc_bogo_customer_gets"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_repeatedly_sum_list();
			}
		);

		/** Disable dropdown btn selected one*/
		$('.wbte_sc_bogo_edit_custom_drop_down p')
			.not('.wbte_sc_bogo_dropdown_menu_item_head')
			.on('click', function () {
				$( this ).addClass( 'wbte_sc_disabled' );
				
				if( ! $( this ).hasClass( 'wbte_sc_bogo_excl_sel_icn' ) ){
					const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
					$( this ).append( selectedImg );

					/** Show elements of the selected class */
					const rowToDisplay = $(this).attr( 'data-row' );
					if( rowToDisplay ){
						const rowToDisplayContent = $(`tr[data-row="${rowToDisplay}"]`);
						
						if( 0 < rowToDisplayContent.closest( '.wbte_sc_bogo_additional_fields_contents' ).length ){
							$( '.wbte_sc_bogo_additional_fields_contents' ).append( rowToDisplayContent );
						}
						rowToDisplayContent.removeClass('wbte_sc_bogo_conditional_hidden');
					}
				}

				$('.wbte_sc_bogo_edit_custom_drop_down').hide();
			}
		);
		$('.wbte_sc_bogo_edit_custom_drop_down label')
			.on('click', function () {
				$( this ).addClass( 'wbte_sc_disabled' )
			}
		);
		$( '.wbte_sc_bogo_edit_custom_drop_down_head input[type="hidden"]' ).each( function () {
			const hiddenInput = $( this );
			const hiddenInputVal = hiddenInput.val();
			if ( hiddenInputVal ) {
				hiddenInput.closest( '.wbte_sc_bogo_edit_custom_drop_down_head' ).find( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).each( function () {
					if ( hiddenInputVal === $( this ).attr( 'data-val' ) ) {
						$( this ).addClass( 'wbte_sc_disabled' );

						if( ! $( this ).hasClass( 'wbte_sc_bogo_excl_sel_icn' ) ){
							const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
							$( this ).append( selectedImg );
						}
					}
				});
			}
		});
		$( '.wbte_sc_bogo_edit_custom_drop_down p' ).each( function () {
			const dataRow = $( this ).attr( 'data-row' );
			if( dataRow && ! $( `tr[data-row=${dataRow}]` ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ){
				$( this ).addClass( 'wbte_sc_disabled' );
				const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
				$( this ).append( selectedImg );
			}
		});

	}

	function wbte_sc_bogo_val_set_on_pageload(){

		/** Step 2 */

		wbte_sc_bogo_repeatedly_once_sum();

		/** Buys, Spends, quantities of, on. */
		if ( wbte_sc_bogo_is_spends() ) {
			$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.spends );
			$( 'tr[data-row="wbte_sc_bogo_adtl_subtotal_row"], .wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_adtl_subtotal_row"]' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
		} else {
			$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.buys );

			if( 'same_product_in_the_cart' !== $( 'input[name="wbte_sc_bogo_customer_gets"]:checked' ).val() ){
				$( 'tr[data-row="wbte_sc_bogo_qty_row"], .wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_qty_row"]' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			}
		}

		$( '.wbte_sc_bogo_prod_cat_restriction_sub_btn' ).on(
			'click',
			function () {
				const toggleVisibility = (addClassSelector, removeClassSelector, inputSelector) => {
					$( addClassSelector ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( removeClassSelector ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( inputSelector ).val( null ).trigger( 'change' );
				};
		
				if ( $( this ).hasClass( 'wbte_sc_bogo_edit_specific_prod_btn' ) ) {
					toggleVisibility(
						'.wbte_sc_bogo_edit_excluded_products_row',
						'.wbte_sc_bogo_edit_specific_products_row',
						'#wbte_sc_bogo_excluded_products'
					);
				} else if ( $( this ).hasClass( 'wbte_sc_bogo_edit_excluded_prod_btn' ) ) {
					toggleVisibility(
						'.wbte_sc_bogo_edit_specific_products_row',
						'.wbte_sc_bogo_edit_excluded_products_row',
						'#wbte_sc_bogo_specific_products'
					);
				} else if ( $( this ).hasClass( 'wbte_sc_bogo_edit_specific_cat_btn' ) ) {
					toggleVisibility(
						'.wbte_sc_bogo_edit_excluded_cat_row',
						'.wbte_sc_bogo_edit_specific_cat_row',
						'#exclude_product_categories'
					);
				} else if ( $( this ).hasClass( 'wbte_sc_bogo_edit_excluded_cat_btn' ) ) {
					toggleVisibility(
						'.wbte_sc_bogo_edit_specific_cat_row',
						'.wbte_sc_bogo_edit_excluded_cat_row',
						'#product_categories'
					);
				}
			}
		);		

		/** Change dropdown text and hide dropdown on clicking sub button */
		$( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).on(
			'click',
			function () {
				const dropdownHead = $( this ).closest( '.wbte_sc_bogo_edit_custom_drop_down_head' );
				dropdownHead.find( '.wbte_sc_bogo_edit_custom_drop_down_btn p' ).html( $( this ).text() );

				const hiddenInput = dropdownHead.find( 'input[type="hidden"]' );

				if ( 0 < hiddenInput.length ) {
					hiddenInput.val( $( this ).attr( 'data-val' ) );

					dropdownHead.find( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).removeClass( 'wbte_sc_disabled' );
					dropdownHead.find( '.wbte_sc_dropdown_selected_icon' ).remove();
				}
			}
		);

		/** Selected products, any products */
		wbte_sc_bogo_show_prod_cat_default();

		wbte_sc_bogo_apply_repeatedly_default_fields();

		/**Step 1 */

		/** Giveaway quantity*/
		$( '.wbte_sc_bogo_step1_summary_qty' ).html( $( 'input[name=wbte_sc_bogo_customer_gets_qty]' ).val() );

		/** BOGO giveaway type. */
		wbte_sc_set_customer_gets_value_summary();

		$( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search option' ).length > 1 ? $( '.wbte_sc_bogo_customer_gets_product_condition_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' ) : $( '.wbte_sc_bogo_customer_gets_product_condition_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );

		/** Free, Discount, Final Price*/
		wbte_sc_bogo_step1_summary_change();

		/**Step 3 */

		/** Once, Repeatedly, Custom */
		const applyOfferSel = $( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' );
		let selected_offer = `<span>${ applyOfferSel.parent().text().trim() }</span>`;
		if( 'wbte_sc_bogo_apply_custom' === applyOfferSel.val() ){
			selected_offer = wbte_sc_bogo_params.text.ctm_rls;
		}
		$( '.wbte_sc_bogo_apply_repeatedly_short' ).html( selected_offer );

		var giveaway_qty = $( '#wbte_sc_bogo_customer_gets_qty' ).val() || '-';
		$( '.wbte_sc_apply_custom_first_giveaway_qty' ).html( giveaway_qty );
		$( 'input[name="wbte_sc_bogo_apply_custom_times[0]"]' ).val( giveaway_qty );

		/** Edit general settings */

		/** Datepicker color based value */
		$( '.wbte_sc_bogo_date_picker' ).each(
			function () {
				'' === $( this ).val() ? $( this ).attr( 'style', 'color: #9DA3AA !important' ) : $( this ).attr( 'style', 'color: #2A3646 !important' );
			}
		);
	}

	/** Show and/or radio button if product and category selection is visisble */
	function wbte_sc_bogo_show_prod_cat_and_or(){
		setTimeout(
			() => {
				if ( 1 < $( '.wbte_sc_bogo_edit_products_categories_tab:visible' ).length ) {
					$( '.wbte_sc_bogo_prod_cat_and_or_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				}else{
					$( '.wbte_sc_bogo_prod_cat_and_or_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				}
			},
			50
		);
	}

	/** Show or hide 'Any product (default)' based on product category restriction */
	function wbte_sc_bogo_show_prod_cat_default(){
		setTimeout(
			() => {
            if ( 0 < $( '.wbte_sc_bogo_edit_products_categories_row:visible' ).length ) {
                $( '.wbte_sc_bogo_prod_cat_default_row' ).fadeOut();
            } else {
					$( '.wbte_sc_bogo_prod_cat_default_row' ).fadeIn();
            }
				wbte_sc_bogo_step2_individual_summary();
			},
			500
		);
	}

	/** Change selected product/category cross button from left to right */
	function wbte_sc_bogo_search_tile_alter(){
		setTimeout(
			function () {
				$( '.select2-selection__choice__remove' ).each(
					function () {
						$( this ).parent().append( $( this ) );
						$( this ).css( { 'font-size': '18px', 'font-weight' : 'normal', 'margin' : '0 2px' } );
					}
				);
			},
			10
		);
	}

	/** Function call is done by dynamically, dont remove it. */
	function wbte_sc_bogo_email_validation(){
		if ( ! $( '#customer_email' ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
			let emails             = jQuery( '[name="wbte_sc_bogo_emails[]"]' ).val();
			let valid_emails_count = 0;

			jQuery.each(
				emails,
				function ( index, email ) {
					if ( wbte_sc_bogo_email_select.validateEmail( email ) ) {
						valid_emails_count++;
					}
				}
			);

			if ( 0 === valid_emails_count ) {
				var elm       = $( '#customer_email' );
				var parentElm = elm.closest( 'td' );
				var err_icon  = '<img style="vertical-align:middle; width:16px; display:inline-block;" src="' + wbte_sc_bogo_params.urls.image_path + 'exclamation_red.svg">';
				if ( 0 === parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).length ) {
					parentElm.append( `<span class="wbte_sc_bogo_edit_error_txt_container"><br>${ err_icon }&nbsp;<span class="wbte_sc_bogo_edit_error_txt">${ wbte_sc_bogo_params.err_msgs.email_error }</span></span>` );
				}
				elm.parent( '.wbte_sc_bogo_email_field' ).addClass( 'wbte_sc_bogo_error_border' );
				var stepContainer = elm.closest( '.wbte_sc_bogo_edit_step' );
				if ( ! stepContainer.hasClass( 'wbte_sc_bogo_step_container_opened' )) {
					stepContainer.trigger( 'click' );
				}
				$( '.wbte_sc_bogo_email_select' )[0].scrollIntoView( { behavior: 'smooth', block: 'center' } );
				$( '.wbte_sc_bogo_email_select' ).show().trigger( 'focus' );
				setTimeout(
					function () {
						var offset = $( '.wbte_sc_bogo_email_select' ).offset().top - ($( window ).height() / 2);
						$( 'html, body' ).animate( { scrollTop: offset }, 500 );
					},
					100
				);

				return false;
			}
		}
		return true;
	}

	/** Function call is done by dynamically, dont remove it. */
	function wbte_sc_bogo_min_qty_validation() {
		wbte_sc_bogo_remove_all_validation_msg();
		const minQty = parseInt($('#_wbte_sc_bogo_min_qty').val(), 10);
		const isBxGx = wbte_sc_is_bxgx();
		const parentLoc = isBxGx ? 'td' : 'th';
	
		if (!minQty || (isBxGx && minQty < 1) || (!isBxGx && minQty < 2)) {
			const errorMsg = isBxGx 
				? wbte_sc_bogo_params.err_msgs.gre_equal_1 
				: wbte_sc_bogo_params.err_msgs.gre_equal_2;
	
			wbte_sc_bogo_show_validation_msg('_wbte_sc_bogo_min_qty', errorMsg, false, parentLoc);
			return false;
		}
	
		if (!isBxGx && minQty < parseInt($('#wbte_sc_bogo_customer_gets_qty').val(), 10)) {
			wbte_sc_bogo_show_validation_msg('_wbte_sc_bogo_min_qty', wbte_sc_bogo_params.err_msgs.gre_custmr_gets, false, parentLoc);
			return false;
		}
	
		return true;
	}

	/** Step 2 functions */
	function wbte_sc_bogo_step2(){

		const $triggerRadioButtons = $('input[type=radio][name=wbte_sc_bogo_triggers_when]');
		const $minmaxQty = $('.wbte_sc_bogo_edit_minmax_qty');
		const $minmaxAmount = $('.wbte_sc_bogo_edit_minmax_amount');
		const $customDropdown = $('.wbte_sc_bogo_edit_custom_drop_down');
		const $adtlQtyRow = $('tr[data-row="wbte_sc_bogo_qty_row"]');
		const $adtlSubtotalRow = $('tr[data-row="wbte_sc_bogo_adtl_subtotal_row"]');
		const $adtlQtyRowDropdownHead = $customDropdown.find('p[data-row="wbte_sc_bogo_qty_row"]');
		const $adtlSubtotalRowDropdownHead = $customDropdown.find('p[data-row="wbte_sc_bogo_adtl_subtotal_row"]');
		const $customerGets = $( 'input[name="wbte_sc_bogo_customer_gets"]:checked' );
		
		const $summaryCustomerAction = $('.wbte_sc_bogo_step2_summary_customer_action');

		/** Step2: Switch qty and amount field by change in radio */
		$triggerRadioButtons.on('change', function () {
			const isQtyTrigger = 'wbte_sc_bogo_triggers_qty' === this.value;
	
			if ( isQtyTrigger ) {
				/** Hide and disable relevant elements */
				$minmaxAmount.addClass('wbte_sc_bogo_conditional_hidden');
				$minmaxQty.removeClass('wbte_sc_bogo_conditional_hidden');
				$summaryCustomerAction.html(wbte_sc_bogo_params.text.buys);
				if( 'same_product_in_the_cart' !== $customerGets.val() ){
					$adtlQtyRow.add($adtlQtyRowDropdownHead).addClass('wbte_sc_bogo_conditional_hidden');
				}
				$adtlSubtotalRowDropdownHead.removeClass( 'wbte_sc_disabled wbte_sc_bogo_conditional_hidden' ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
			} else {
				/** Show and enable relevant elements */
				$minmaxAmount.removeClass('wbte_sc_bogo_conditional_hidden');
				$minmaxQty.add($adtlSubtotalRow).add($adtlSubtotalRowDropdownHead).addClass('wbte_sc_bogo_conditional_hidden');
				$summaryCustomerAction.html(wbte_sc_bogo_params.text.spends);

				$adtlQtyRowDropdownHead.removeClass( 'wbte_sc_disabled wbte_sc_bogo_conditional_hidden' ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
			}
		});

		$( 'input[ type=text ][ name=_wbte_sc_bogo_min_qty ], input[ type=text ][ name=_wbte_sc_bogo_max_qty ], input[ type=text ][ name=_wbte_sc_bogo_min_amount ], input[ type=text ][ name=_wbte_sc_bogo_max_amount ], input[ type=radio ][ name=wbte_sc_bogo_triggers_when ], input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]' ).on(
			'change, input',
			function () {
				wbte_sc_bogo_apply_repeatedly_default_fields();

				wbte_sc_bogo_custom_short_summary();

				wbte_sc_bogo_step2_individual_summary();

				wbte_sc_bogo_repeatedly_once_sum();
			}
		);

		wbte_sc_bogo_search_tile_alter();

		$( '.wc-product-search, .wc-enhanced-select' ).on(
			'change',
			function (e) {
				wbte_sc_bogo_search_tile_alter();
			}
		);

		/** Customer buys, product-cat include exclude */
		$( '.wbte_sc_bogo_edit_add_customer_buys' ).on(
			'click',
			function ( e ) {
				e.preventDefault();
				if ( $( '.wbte_sc_bogo_edit_customer_buys_select' ).is( ':visible' ) ) {
					$( '.wbte_sc_bogo_edit_customer_buys_select' ).fadeOut();
				} else {
					$( '.wbte_sc_bogo_edit_customer_buys_select' ).fadeIn();
				}
			}
		);

		wbte_sc_step2_opt_conditions();

		wbte_sc_bogo_step2_individual_summary();

		/**Step 2 Customer buys select */
		$( '.wbte_sc_bogo_edit_customer_buys_select p' ).not( '.wbte_sc_bogo_dropdown_menu_item' ).on(
			'click',
			function () {
				wbte_sc_bogo_show_prod_cat_default();
				wbte_sc_bogo_show_prod_cat_and_or();
			}
		);
	}

	function wbte_sc_bogo_step2_individual_summary(){
		let min_val, max_val, textToShow, conditionKeyPrefix;
		const is_selected_products = $( '.wbte_sc_bogo_edit_products_categories_row' ).not( '.wbte_sc_bogo_conditional_hidden' ).length > 0;

		if (wbte_sc_bogo_is_spends()) {
			min_val            = $( '#_wbte_sc_bogo_min_amount' ).val() ? wbte_sc_bogo_params.text.currency_symbol + $( '#_wbte_sc_bogo_min_amount' ).val() : '';
			max_val            = $( '#_wbte_sc_bogo_max_amount' ).val() ? wbte_sc_bogo_params.text.currency_symbol + $( '#_wbte_sc_bogo_max_amount' ).val() : '';
			conditionKeyPrefix = 'spends';
		} else {
			min_val            = $( '#_wbte_sc_bogo_min_qty' ).val();
			max_val            = $( '#_wbte_sc_bogo_max_qty' ).val();
			conditionKeyPrefix = wbte_sc_is_bxgx() ? 'buys' : 'contains';
		}

		if (min_val && max_val) {
			textToShow = is_selected_products
				? wbte_sc_bogo_params.individual_summary_text[`${conditionKeyPrefix}_between_selected`]
				: wbte_sc_bogo_params.individual_summary_text[`${conditionKeyPrefix}_between_any`];

		} else {
			textToShow = is_selected_products
				? wbte_sc_bogo_params.individual_summary_text[`${conditionKeyPrefix}_atleast_selected`]
				: wbte_sc_bogo_params.individual_summary_text[`${conditionKeyPrefix}_atleast_any`];
		}

		min_val = min_val || '-';
		max_val = max_val || '-';
		textToShow = textToShow.replace('{min}', min_val).replace('{max}', max_val);
		$( '.wbte_sc_bogo_step2_short_description p' ).html( textToShow );
	}

	function wbte_sc_bogo_is_spends(){
		return 'wbte_sc_bogo_triggers_subtotal' === $( 'input[ type="radio" ][ name="wbte_sc_bogo_triggers_when" ]:checked' ).val();
	}

	/** Function to handle Step 2 additional condition short summary */
	function wbte_sc_bogo_step2_add_conditions_summary() {
		// Cache jQuery selectors
		const $minEachQty 			= $('#_wbte_sc_min_qty_each');
		const $maxEachQty 			= $('#_wbte_sc_max_qty_each');
		const $minQty 				= $('#_wbte_sc_bogo_min_qty_add');
		const $maxQty 				= $('#_wbte_sc_bogo_max_qty_add');
		const $usageLimitPerUser 	= $('#usage_limit_per_user');
		const $usageLimitPerCoupon 	= $('#usage_limit');
		const $minSubtotal 			= $('#_wbte_sc_bogo_min_amount_adtl');
		const $maxSubtotal 			= $('#_wbte_sc_bogo_max_amount_adtl');
		const $individualUse 		= $('#individual_use');
	
		const currencySymbol = wbte_sc_bogo_params.text.currency_symbol;
	
		/** Check if the closest tr is not hidden */
		const isVisible = (selector) => !$(selector).closest('tr').hasClass('wbte_sc_bogo_conditional_hidden');
	
		const onSaleNonSale = $('input[name="wbte_sc_bogo_on_sale_non_sale"]:checked').val();
		const subtotalFrom 	= $('input[name="wbte_sc_bogo_adtl_subtotal_from"]:checked').val();
	
		const summaryTextMap = {
			qty_each: $minEachQty.val() && $maxEachQty.val() && isVisible('#_wbte_sc_min_qty_each'),
			min_qty_each: $minEachQty.val() && !$maxEachQty.val() && isVisible('#_wbte_sc_min_qty_each'),
			qty: $minQty.val() && $maxQty.val() && isVisible('#_wbte_sc_bogo_min_qty_add'),
			min_qty: $minQty.val() && !$maxQty.val() && isVisible('#_wbte_sc_bogo_min_qty_add'),
			subtotal_eligible:
				$minSubtotal.val() &&
				$maxSubtotal.val() &&
				isVisible('#_wbte_sc_bogo_min_amount_adtl') &&
				subtotalFrom === 'selected_products',
			subtotal_entire:
				$minSubtotal.val() &&
				$maxSubtotal.val() &&
				isVisible('#_wbte_sc_bogo_min_amount_adtl') &&
				subtotalFrom === 'entire_cart',
			min_subtotal_eligible:
				$minSubtotal.val() &&
				!$maxSubtotal.val() &&
				isVisible('#_wbte_sc_bogo_min_amount_adtl') &&
				subtotalFrom === 'selected_products',
			min_subtotal_entire:
				$minSubtotal.val() &&
				!$maxSubtotal.val() &&
				isVisible('#_wbte_sc_bogo_min_amount_adtl') &&
				subtotalFrom === 'entire_cart',
			on_sale:
				onSaleNonSale === 'wbte_sc_bogo_on_sale' &&
				isVisible('#wbte_sc_bogo_on_sale_non_sale_label'),
			on_non_sale:
				onSaleNonSale === 'wbte_sc_bogo_on_non_sale' &&
				isVisible('#wbte_sc_bogo_on_sale_non_sale_label'),
			limit_per_user: $usageLimitPerUser.val() && isVisible('#usage_limit_per_user'),
			limit_per_coupon: $usageLimitPerCoupon.val() && isVisible('#usage_limit'),
			individual_only: $individualUse.is(':checked') && isVisible('#individual_use'),
		};
	
		let summaryItems = [];
	
		for (const [key, condition] of Object.entries(summaryTextMap)) {
			if (condition) {
				summaryItems.push(`<li>${wbte_sc_bogo_params.short_summary_text[key]}</li>`);
			}
		}
	
		const checkedLocation = $('input[name="_wt_need_check_location_in"]:checked').val();
		const locationType = $('#_wt_coupon_available_location_inc_exc').val();
		const locationMsgMap = {
			billing: {
				include: 'billing_location',
				exclude: 'ineligible_billing_location',
			},
			shipping: {
				include: 'shipping_location',
				exclude: 'ineligible_shipping_location',
			},
		};
		const locInclusionKey = locationType === 'include' ? 'include' : 'exclude';
		const locationMsg = locationMsgMap[checkedLocation]?.[locInclusionKey] || '';
	
		const additionalSelectElements = {
			'#_wt_sc_user_roles': { textKey: 'user_role' },
			'#_wt_sc_bogo_emails': { textKey: 'email', limit: 1 },
			'#_wt_sc_payment_methods': { textKey: 'payment_method' },
			'#_wt_sc_shipping_methods': { textKey: 'shipping_method' },
			'#_wt_coupon_available_location': { textKey: locationMsg },
		};
	
		for (const [selector, { textKey, limit }] of Object.entries(additionalSelectElements)) {
			if (isVisible(selector)) {
				const $element = $(selector);
				const selectedOptions = $element.find('option:selected');
				if (selectedOptions.length) {
					let optionTexts = [];
					selectedOptions.each(function (index) {
						if (limit && index >= limit) {
							optionTexts.push('...');
							return false; // Exit the loop
						}
						optionTexts.push(`<span>${$(this).text()}</span>`);
					});
					if (optionTexts.length) {
						const optionsHTML = optionTexts.join('&nbsp; ');
						summaryItems.push(`<li>${wbte_sc_bogo_params.short_summary_text[textKey]} ${optionsHTML}</li>`);
					}
				}
			}
		}
	
		/** Purchase history */
		if (isVisible('.wbte_sc_bogo_purchase_history_container')) {
			let purchaseSummary = '';
	
			const purchaseHistoryValue = $('input[name="wbte_sc_bogo_purchase_history"]:checked').val();
			if (purchaseHistoryValue === 'wbte_sc_bogo_puchase_history_first_time') {
				purchaseSummary = `<li>${wbte_sc_bogo_params.short_summary_text.purchase_first_time}</li>`;
			} else {
				purchaseSummary = `<li>${wbte_sc_bogo_params.short_summary_text.nth_returning}`;
	
				/** Date text */
				const $dateRange = $('.wbte_sc_bogo_purchase_history_container_inner[data-row="date_range"]');
				if (!$dateRange.hasClass('wbte_sc_bogo_conditional_hidden')) {
					const dateType = $('input[name="_nth_coupon_order_date_or_days"]:checked').val();
					purchaseSummary += 'days' === dateType
						? wbte_sc_bogo_params.short_summary_text.pch_days
						: wbte_sc_bogo_params.short_summary_text.pch_date;
				}
	
				/** Order status text */
				const $orderStatusContainer = $('.wbte_sc_bogo_purchase_history_container_inner[data-row="order_status"]');
				if (!$orderStatusContainer.hasClass('wbte_sc_bogo_conditional_hidden')) {
					const $selectedOrderStatus = $('#wt_order_Status_need_to_count option:selected');
					if ($selectedOrderStatus.length > 0) {
						let orderStatusText = wbte_sc_bogo_params.short_summary_text.pch_order_sts;
						orderStatusText += Array.from($selectedOrderStatus).map(option => `<span>${$(option).text()}</span>`).join(' ');
						purchaseSummary += orderStatusText;
					}
				}
	
				/** Product purchased text */
				const $productsPurchasedContainer = $('.wbte_sc_bogo_purchase_history_container_inner[data-row="products_purchased"]');
				if (!$productsPurchasedContainer.hasClass('wbte_sc_bogo_conditional_hidden')) {
					const $selectedProducts = $('#_wt_sc_nth_order_products option:selected');
					if ($selectedProducts.length > 0) {
						let orderProductsText = wbte_sc_bogo_params.short_summary_text.pch_order_products;
						if ($selectedProducts.length > 3) {
							orderProductsText += `<span>${wbte_sc_bogo_params.text.yes}</span>`;
						} else {
							const productNames = Array.from($selectedProducts).map(option => {
								let productName = $(option).text();
								return productName.length > 15 ? `${productName.substring(0, 15)}...` : productName;
							});
							orderProductsText += productNames.map(name => `<span>${name}</span>`).join(' ');
						}
						purchaseSummary += orderProductsText;
					}
				}
	
				// Replace placeholders
				purchaseSummary = purchaseSummary
					.replace( '{nth_order_cond}', $('.wbte_sc_nth_order_condition_selected p').html() )
					.replace( '{nth_no_orders}', $('#wt_nth_order_no_of_orders').val() || '-' )
					.replace( '{nth_amount}', `${currencySymbol}${$('#wt_nth_order_order_total').val()}` )
					.replace( '{nth_pch_days}', $('#_wt_sc_nth_order_within_days').val() || '-' )
					.replace( '{date_from}', $('#_wt_sc_nth_order_date_from').val() || '-' )
					.replace( '{date_to}', $('#_wt_sc_nth_order_date_to').val() || '-' );
	
				purchaseSummary += '</li>';
				summaryItems.push(purchaseSummary);
			}
		}
	
		let summaryHTML = '';
		if ( 0 < summaryItems.length ) {
			summaryHTML = `
				<p style="font-size:14px; font-weight:500;">
					${wbte_sc_bogo_params.short_summary_text.add_conditions}
				</p>
				<ul>
					${summaryItems.join('')}
				</ul>
			`;
		}
	
		/** Replace placeholders in the final summary */
		summaryHTML = summaryHTML
			.replace('{min_subtotal}', `${currencySymbol}${$minSubtotal.val()}`)
			.replace('{max_subtotal}', `${currencySymbol}${$maxSubtotal.val()}`)
			.replace('{limit_per_user}', $usageLimitPerUser.val())
			.replace('{limit_per_coupon}', $usageLimitPerCoupon.val())
			.replace('{min_qty}', $minQty.val())
			.replace('{max_qty}', $maxQty.val())
			.replace('{min_qty_each}', $minEachQty.val())
			.replace('{max_qty_each}', $maxEachQty.val());
	
		$('.wbte_sc_bogo_step_add_desc').html(summaryHTML);
	}	

	/** Step 1 functions */
	function wbte_sc_bogo_step1(){

		/**  Display or hide fields of 'Discount' and 'Final price' of radio 'wbte_sc_bogo_customer_gets_with' on page load.*/
		wbte_sc_bogo_customer_gets_with();

		if( wbte_sc_is_bxgx() ){
			wbte_sc_bogo_on_customer_gets_change();
		}
		
		/** Customer gets dropdown */
		$( 'input[ type=radio ][ name=wbte_sc_bogo_customer_gets ], input[name="wbte_sc_bogo_triggers_when"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_on_customer_gets_change();
			}
		);
		

		wbte_sc_bogo_gets_prod_condition();
		$( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search' ).on(
			'change',
			function () {
				wbte_sc_bogo_gets_prod_condition();
			}
		);

		/** Change giveaway qty on summary on change in input */
		$( 'input[ type=text ][ name=wbte_sc_bogo_customer_gets_qty ]' ).on(
			'change',
			function () {
				const giveaway_qty = $( 'input[name=wbte_sc_bogo_customer_gets_qty]' ).val();
				$( '.wbte_sc_apply_custom_first_giveaway_qty' ).html( giveaway_qty );
				$( 'input[name="wbte_sc_bogo_apply_custom_times[0]"]' ).val( giveaway_qty );
				$( '.wbte_sc_bogo_step1_summary_qty' ).html( giveaway_qty );
			}
		);

		/**  Display or hide fields of 'Discount' and 'Final price' of radio 'wbte_sc_bogo_customer_gets_with' on radio change.*/
		$( 'input[name="wbte_sc_bogo_customer_gets_with"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_customer_gets_with();
				wbte_sc_bogo_step1_summary_change();
			}
		);

		/**  Display or hide fields of 'Free', 'Percentage' and 'Fixed amount' of radio 'wbte_sc_bogo_customer_gets_discount_type' on radio change.*/
		$( 'input[name="wbte_sc_bogo_customer_gets_discount_type"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_customer_gets_discount_type();
				wbte_sc_bogo_step1_summary_change();
			}
		);

		/** Change values in Step 1 summary on changes in input */
		$( 'input[ type=text ][ name=wbte_sc_bogo_customer_gets_final_price ], input[ type=text ][ name=wbte_sc_bogo_customer_gets_discount_perc ], input[ type=text ][ name=wbte_sc_bogo_customer_gets_discount_price ]' ).on(
			'change',
			function () {
				wbte_sc_bogo_step1_summary_change();
			}
		);

		if( $( '#free_shipping' ).is( ':checked' ) && '1' !== $( '.wbte_sc_bogo_free_shipping_warning' ).attr( 'data-free-shipp-enabled' ) ){
			$( '.wbte_sc_bogo_free_shipping_warning' ).css( 'display', 'flex' );
		}

		$( '#free_shipping' ).on( 'change', function(){
			if( $( this ).is( ':checked' ) && '1' !== $( '.wbte_sc_bogo_free_shipping_warning' ).attr( 'data-free-shipp-enabled' ) ){
				$( '.wbte_sc_bogo_free_shipping_warning' ).css( 'display', 'flex' );
			}else{
				$( '.wbte_sc_bogo_free_shipping_warning' ).hide();
			}
		});

	}

	function wbte_sc_bogo_on_customer_gets_change(){

		const customerGetsElm = $( 'input[name="wbte_sc_bogo_customer_gets"]:checked' );
		$( '.wbte_sc_bogo_customer_gets_select_btn p, .wbte_sc_bogo_edit_gets_selected' ).text( customerGetsElm.parent().text().trim() );

		$( '.wbte_sc_bogo_edit_custom_drop_down label' ).removeClass( 'wbte_sc_disabled' );
		customerGetsElm.closest( 'label' ).addClass( 'wbte_sc_disabled' );

		if( $( 'tr[data-row="wbte_sc_bogo_each_qty_row"]' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ){
			$( '.wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_each_qty_row"]' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
		}

		if( 'same_product_in_the_cart' !== customerGetsElm.val() && 'wbte_sc_bogo_triggers_qty' === $( 'input[name="wbte_sc_bogo_triggers_when"]:checked' ).val() ){
			$( 'tr[data-row="wbte_sc_bogo_qty_row"], .wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_qty_row"]' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
		}

		var selectedValue = customerGetsElm.val(),
        specificProdRow = $('.wbte_sc_bogo_customer_gets_specific_prod_row'),
        specificCatRow = $('.wbte_sc_bogo_customer_gets_specific_cat_row'),
        productConditionRow = $('.wbte_sc_bogo_customer_gets_product_condition_row'),
        sameInCartHelpTxtRow = $('.wbte_sc_bogo_customer_gets_same_in_cart_helptxt_row');

		specificProdRow.add(specificCatRow).add(productConditionRow).addClass('wbte_sc_bogo_conditional_hidden');
		sameInCartHelpTxtRow.hide();

		$( 'label[for="_wbte_sc_bogo_min_amount"]' ).text( wbte_sc_bogo_params.text.min_amount );
		$( 'label[for="_wbte_sc_bogo_max_amount"]' ).text( wbte_sc_bogo_params.text.max_amount );
		$( 'label[for="_wbte_sc_bogo_min_qty"]' ).text( wbte_sc_bogo_params.text.min_qty );
		$( 'label[for="_wbte_sc_bogo_max_qty"]' ).text( wbte_sc_bogo_params.text.max_qty );

		switch (selectedValue) {
			case 'same_product_in_the_cart':
				sameInCartHelpTxtRow.show();
				$( 'label[for="_wbte_sc_bogo_min_amount"]' ).text( wbte_sc_bogo_params.text.min_amount_each );
				$( 'label[for="_wbte_sc_bogo_max_amount"]' ).text( wbte_sc_bogo_params.text.max_amount_each );
				$( 'label[for="_wbte_sc_bogo_min_qty"]' ).text( wbte_sc_bogo_params.text.min_qty_each );
				$( 'label[for="_wbte_sc_bogo_max_qty"]' ).text( wbte_sc_bogo_params.text.max_qty_each );
				$( 'tr[data-row="wbte_sc_bogo_each_qty_row"], .wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_each_qty_row"]' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_qty_row"]' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
	
			case 'specific_product':
				specificProdRow.removeClass('wbte_sc_bogo_conditional_hidden');
				if( 1 < $( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search option:selected' ).length ){
					$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				}
				break;
	
			case 'any_product_from_store':
				// already handled by initial hiding, no further action needed
				break;
	
			default:
				specificCatRow.removeClass('wbte_sc_bogo_conditional_hidden');
		}

		$( '.wbte_sc_bogo_edit_custom_drop_down' ).hide();
		wbte_sc_bogo_step2_add_conditions_summary();
	}

	/** Function to handle 'wbte_sc_bogo_customer_gets_with' radio */
	function wbte_sc_bogo_customer_gets_with() {
		if ( 'wbte_sc_bogo_customer_gets_with_final_price' === $( 'input[name="wbte_sc_bogo_customer_gets_with"]:checked' ).val() ) {
			$( '.wbte_sc_bogo_customer_gets_discount_type_final_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
			$( '.wbte_sc_bogo_customer_gets_discount_type_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
		} else {
			$( '.wbte_sc_bogo_customer_gets_discount_type_final_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			$( '.wbte_sc_bogo_customer_gets_discount_type_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
			wbte_sc_bogo_customer_gets_discount_type();
		}
	}

	function wbte_sc_bogo_gets_prod_condition(){
		if ( 1 < $( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search' ).find( 'option:selected' ).length ) {
			$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
		} else {
			$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			$( 'input[name="wbte_sc_bogo_gets_product_condition"][value="all"]' ).prop( 'checked', true );
			$( 'input[name="wbte_sc_bogo_gets_product_condition"][value="any"]' ).prop( 'checked', false );
		}
	}

	/** Function to handle 'wbte_sc_bogo_customer_gets_discount_type' radio */
	function wbte_sc_bogo_customer_gets_discount_type() {
		switch ($( "input[name='wbte_sc_bogo_customer_gets_discount_type']:checked" ).val()) {
			case 'wbte_sc_bogo_customer_gets_free':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
			case 'wbte_sc_bogo_customer_gets_with_perc_discount':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
			case 'wbte_sc_bogo_customer_gets_with_fixed_discount':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
		}
	}

	/** Function to handle Step 1 summary on change in inputs */
	function wbte_sc_bogo_step1_summary_change() {

		const selected_customer_gets_with = $( 'input[type=radio][name=wbte_sc_bogo_customer_gets_with]:checked' ).val();
		const selected_discount_type      = $( 'input[type=radio][name=wbte_sc_bogo_customer_gets_discount_type]:checked' ).val();
		let textToShow, discount_amount;
		const customer_gets_qty = $( '#wbte_sc_bogo_customer_gets_qty' ).val() || '-';

		if ( 'wbte_sc_bogo_customer_gets_with_final_price' === selected_customer_gets_with ) {
			textToShow      = wbte_sc_bogo_params.text.final_price;
			discount_amount = wbte_sc_bogo_params.text.currency_symbol + $( '#wbte_sc_bogo_customer_gets_final_price' ).val();
		} else {
			if ( 'wbte_sc_bogo_customer_gets_free' === selected_discount_type ) {
				textToShow = wbte_sc_bogo_params.text.discount_free;
			} else {
				textToShow = wbte_sc_bogo_params.text.discount_perc_fixed;
				if ( 'wbte_sc_bogo_customer_gets_with_perc_discount' === selected_discount_type ) {
					discount_amount = $( '#wbte_sc_bogo_customer_gets_discount_perc' ).val() + '%';
				} else {
					discount_amount = wbte_sc_bogo_params.text.currency_symbol + $( '#wbte_sc_bogo_customer_gets_discount_price' ).val();
				}
			}
		}

		$( '.wbte_sc_bogo_step1_short_description p' ).html( textToShow );
		$( '.wbte_sc_bogo_step1_summary_qty' ).html( customer_gets_qty );
		wbte_sc_set_customer_gets_value_summary();
		$( '.wbte_sc_bogo_s2_summary_discount_amount' ).html( discount_amount );

	}

	/** Step 3 functions */
	function wbte_sc_bogo_step3(){

		/**  Display or hide fields of 'Apply offer' of radio 'wbte_sc_bogo_apply_offer' on page load.*/
		wbte_sc_bogo_apply_offer_times();

		/** To make last index of custom range infinite */
		$( '#wbte_sc_bogo_apply_custom_max\\[' + wbte_sc_bogo_apply_repeatedly_fields_index() + '\\]' ).attr( 'placeholder', '\u221E' );

		/**  Display or hide fields of 'Apply offer' of radio 'wbte_sc_bogo_apply_offer' on radio change.*/
		$( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]' ).on(
			'change',
			function () {
				wbte_sc_bogo_apply_offer_times();
				let selected_offer = `<span>${$( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' ).parent().text().trim()}</span>`;
				if( 'wbte_sc_bogo_apply_custom' === $(this).val() ){
					selected_offer = wbte_sc_bogo_params.text.ctm_rls;
				}
				$( '.wbte_sc_bogo_apply_repeatedly_short' ).html( selected_offer );
			}
		);

		$( 'input[name="wbte_sc_bogo_repeatedly_times"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_repeatedly_sum_list();
			}
		);
		wbte_sc_bogo_repeatedly_sum_list();

		wbte_sc_bogo_apply_repeatedly_default_fields();

		wbte_sc_bogo_custom_short_summary();

		/** Clone apply repreatedly custom row on click 'Add range' */
		$( '.wbte_sc_bogo_repeatedly_custom_range_btn' ).on(
			'click',
			function () {
				const cloneRowClass = wbte_sc_is_bxgx() ? 'wbte_sc_bogo_apply_repeatedly_custom_range_hidden_row' : 'wbte_sc_bogo_apply_repeatedly_custom_range_cheap_exp_hidden_row';
				
				var clonedRow = $( '.' + cloneRowClass ).clone( true );

				clonedRow.removeClass( cloneRowClass + ' wbte_sc_bogo_conditional_hidden' ).addClass( 'wbte_sc_bogo_apply_repeatedly_custom_row wbte_sc_bogo_apply_repeatedly_custom_range_row' );

				if ( 0 < $( '.wbte_sc_bogo_apply_repeatedly_custom_range_row' ).length ) {
					$( '.wbte_sc_bogo_apply_repeatedly_custom_range_row:last' ).after( clonedRow );
				} else {
					$( '.' + cloneRowClass ).after( clonedRow );
				}
				const new_index     = wbte_sc_bogo_apply_repeatedly_fields_index();
				let new_min_element = $( "input[name='wbte_sc_bogo_apply_custom_min[" + new_index + "]']" );
				let old_max_element = $( "input[name='wbte_sc_bogo_apply_custom_max[" + (new_index - 1) + "]']" );
				if ( '' !== old_max_element.val() ) {
					if ( wbte_sc_bogo_is_spends() ) {
						new_min_element.val( parseFloat( old_max_element.val() ) );
					} else {
						new_min_element.val( parseInt( old_max_element.val() ) + 1 );
					}
				}
				$( 'input[ name^="wbte_sc_bogo_apply_custom_max"]' ).each(
					function () {
						$( this ).attr( 'placeholder', '' );
					}
				);
				/** To make last index of custom range infinite */
				$( '#wbte_sc_bogo_apply_custom_max\\[' + new_index + '\\]' ).attr( 'placeholder', '\u221E' );
			}
		);

		/** Update max qty/amount value when chnage in 0th index of custom range */
		$( 'input[ type=text ][ name="wbte_sc_bogo_apply_custom_max[0]" ]' ).on(
			'change',
			function () {
				if ( wbte_sc_bogo_is_spends() ) {
					$( '#_wbte_sc_bogo_max_amount' ).val( parseFloat( $( this ).val() ) );
				} else {
					$( '#_wbte_sc_bogo_max_qty' ).val( parseInt( $( this ).val() ) );
				}
			}
		);

	}

	function wbte_sc_bogo_repeatedly_once_sum(){
		const isSpends     = wbte_sc_bogo_is_spends();
		const minVal 	   = $( isSpends ? '#_wbte_sc_bogo_min_amount' : '#_wbte_sc_bogo_min_qty' ).val() || '-';
		const maxVal 	   = $( isSpends ? '#_wbte_sc_bogo_max_amount' : '#_wbte_sc_bogo_max_qty' ).val();
		const giveawayQty  = $( '#wbte_sc_bogo_customer_gets_qty' ).val() || '-';
		const repeatelyMsg = wbte_sc_bogo_params.short_summary_text[ isSpends ? 'custom_spends' : wbte_sc_is_bxgx() ? 'custom_buys' : 'custom_contains' ];
		$( '.wbte_sc_bogo_repeatedly_once_msg' ).html( repeatelyMsg );
		if ( minVal ) {
			$( '.wbte_sc_bogo_custom_min_sum' ).html( minVal );
		}
		if ( maxVal ) {
			$( '.wbte_sc_bogo_custom_max_sum' ).html( maxVal );
		} else {
			$( '.wbte_sc_bogo_custom_max_sum' ).html( '\u221E' );
		}
		$( '.wbte_sc_bogo_free_count_sum' ).html( giveawayQty );
		wbte_sc_set_customer_gets_value_summary();
		return $( '.wbte_sc_bogo_repeatedly_once_msg' ).html();
	}

	function wbte_sc_bogo_repeatedly_sum_list() {
		const isSpends     = wbte_sc_bogo_is_spends();
		const minVal 	   = $( isSpends ? '#_wbte_sc_bogo_min_amount' : '#_wbte_sc_bogo_min_qty' ).val();
		const giveawayQty  = $( '#wbte_sc_bogo_customer_gets_qty' ).val();
		const repeatLimit  = $( '#wbte_sc_bogo_repeatedly_times' ).val();
		const repeatelyMsg = wbte_sc_bogo_params.individual_summary_text[ isSpends ? 'repeatedly_spends' : wbte_sc_is_bxgx() ?  'repeatedly_buys' : 'repeatedly_contains' ];
		let repeatedlySum  = '<ul>';

		if ( ! repeatLimit ) {
			for ( let i = 0; i < 5; i++ ) {
				if ( [2, 3, 4].includes( i ) ) {
					repeatedlySum += '<li class="wbte_sc_bogo_repeatedly_dot">.</li>';
					continue;
				}
				let _repeatedlyMsg = repeatelyMsg
					.replace( '{buy_spend_val}', minVal * (i + 1) )
					.replace( '{repeatedly_free_count}', giveawayQty * (i + 1) );
				repeatedlySum     += ` <li> ${_repeatedlyMsg} </li> `;
			}
			repeatedlySum += ` <li> ${wbte_sc_bogo_params.text.and_so_on} </li> `;
		} else {
			if ( 3 >= repeatLimit ) {
				for (let i = 0; i < repeatLimit; i++) {
					let _repeatedlyMsg = repeatelyMsg
						.replace( '{buy_spend_val}', minVal * (i + 1) )
						.replace( '{repeatedly_free_count}', giveawayQty * (i + 1) );
					repeatedlySum     += ` <li> ${_repeatedlyMsg} </li> `;
				}
			} else {
				for (let i = 0; i < 6; i++) {
					if (i === 2 || (i >= 3 && i < 5)) {
						repeatedlySum += '<li class="wbte_sc_bogo_repeatedly_dot">.</li>';
						continue;
					}

					let currentMinVal 	   = minVal * (i + 1);
					let currentGiveawayQty = giveawayQty * (i + 1);

					if (i === 5) {
						currentMinVal 	   = minVal * repeatLimit;
						currentGiveawayQty = giveawayQty * repeatLimit;
					}

					let _repeatedlyMsg = repeatelyMsg
						.replace( '{buy_spend_val}', currentMinVal )
						.replace( '{repeatedly_free_count}', currentGiveawayQty );

					repeatedlySum += ` <li> ${_repeatedlyMsg} </li> `;
				}
			}
		}

		repeatedlySum += '</ul>';

		$( '.wbte_sc_bogo_repeatedly_msg' ).html( repeatedlySum );
		wbte_sc_set_customer_gets_value_summary();
		return $( '.wbte_sc_bogo_repeatedly_msg' ).html();
	}

	/** Function to handle 'wbte_sc_bogo_apply_offer' radio. */
	function wbte_sc_bogo_apply_offer_times() {
		var selectedValue = $( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' ).val();

		switch ( selectedValue ) {
			case 'wbte_sc_bogo_apply_once':
				$( '.wbte_sc_bogo_apply_repeatedly_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_repeatedly_custom_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_once_row' ).show();
				break;
			case 'wbte_sc_bogo_apply_repeatedly':
				$( '.wbte_sc_bogo_apply_repeatedly_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_repeatedly_custom_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_once_row' ).hide();
				break;
			case 'wbte_sc_bogo_apply_custom':
				$( '.wbte_sc_bogo_apply_repeatedly_custom_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_repeatedly_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_once_row' ).hide();
				break;
		}
	}

	/** Function to show repeated fields summary */
	function wbte_sc_bogo_custom_short_summary(){
		const repeatedlyMode = $( 'input[ type="radio" ][ name="wbte_sc_bogo_apply_offer" ]:checked' ).val();
		let summary          = '';

		if ( 'wbte_sc_bogo_apply_once' === repeatedlyMode ) {
			const once_summary = wbte_sc_bogo_repeatedly_once_sum();
			$( '.wbte_sc_bogo_repeatedly_additional_summary' ).html( ` <p style = "margin-top:15px;" > ${once_summary} </p> ` );
		} else if ( 'wbte_sc_bogo_apply_repeatedly' === repeatedlyMode ) {
			const repeatLimit = $( '#wbte_sc_bogo_repeatedly_times' ).val();
			if ( repeatLimit ) {
				summary = wbte_sc_bogo_params.short_summary_text.repeatedly;
			}
			const repeatedly_summary = wbte_sc_bogo_repeatedly_sum_list();
			$( '.wbte_sc_bogo_repeatedly_additional_summary' ).html( summary + repeatedly_summary );
			$( '.wbte_sc_bogo_repeatedly_sum' ).html( repeatLimit );
		} else if ( 'wbte_sc_bogo_apply_custom' === repeatedlyMode ) {
			const customMin   = $( 'input[name^="wbte_sc_bogo_apply_custom_min"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();
			const customMax   = $( 'input[name^="wbte_sc_bogo_apply_custom_max"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();
			const customTimes = $( 'input[name^="wbte_sc_bogo_apply_custom_times"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();

			const summaryText     = wbte_sc_bogo_params.short_summary_text[ wbte_sc_bogo_is_spends() ? 'custom_spends' : wbte_sc_is_bxgx() ? 'custom_buys' : 'custom_contains' ];
			const currency        = wbte_sc_bogo_is_spends() ? wbte_sc_bogo_params.text.currency_symbol : '';
			let i                 = 0;
			const customMinLenght = customMin.length;
			for (i = 0; i < customMinLenght; i++) {
				summary += ` <li> ${summaryText} </li> `;
			}

			if ( '' !== summary ) {
				summary = ` <ol> ${summary} </ol> `;

				$( '.wbte_sc_bogo_repeatedly_additional_summary' ).html( summary );
			}

			wbte_sc_set_customer_gets_value_summary();

			for (i = 0; i < customMinLenght; i++) {
				const minValue   = parseFloat( customMin[i] );
				const maxValue   = parseFloat( customMax[i] );
				const timesValue = parseFloat( customTimes[i] );

				$( 'ol .wbte_sc_bogo_custom_min_sum' ).eq( i ).html( ! isNaN( minValue ) ? currency + minValue : '-' );
				$( 'ol .wbte_sc_bogo_custom_max_sum' ).eq( i ).html( ! isNaN( maxValue ) ? currency + maxValue : '-' );
				$( 'ol .wbte_sc_bogo_free_count_sum' ).eq( i ).html( timesValue || '-' );
			}

			/** For last range, if max is empty show the infinity symbol in custom individual summary. */
			if ( isNaN( parseFloat( customMax[i - 1] ) ) ) {
				$( 'ol .wbte_sc_bogo_custom_max_sum' ).eq( i - 1 ).html( '&infin;' );
			}

		}
	}

	function wbte_sc_bogo_apply_repeatedly_default_fields(){

		var min_value;
		var max_value;

		if ( wbte_sc_bogo_is_spends() ) {
			min_value = $( 'input[name=_wbte_sc_bogo_min_amount]' ).val();
			max_value = $( 'input[name=_wbte_sc_bogo_max_amount]' ).val();
			$( '[name^=wbte_sc_bogo_apply_custom_min], input[name^=wbte_sc_bogo_apply_custom_max]' ).addClass( 'wbte_sc_bogo_input_only_numbers_with_decimal' ).removeClass( 'wbte_sc_bogo_input_only_number' );
		} else {
			min_value = $( 'input[name=_wbte_sc_bogo_min_qty]' ).val();
			max_value = $( 'input[name=_wbte_sc_bogo_max_qty]' ).val();
			$( 'input[name=wbte_sc_bogo_apply_custom_min], input[name=wbte_sc_bogo_apply_custom_max]' ).addClass( 'wbte_sc_bogo_input_only_number' ).removeClass( 'wbte_sc_bogo_input_only_numbers_with_decimal' );
		}

		/** Hide/show elements based on min_qty and max_qty */
		$( '.wbte_sc_bogo_apply_custom_min_span_input' ).toggle( min_value === '' );
		$( '.wbte_sc_bogo_apply_custom_min_span' ).toggle( min_value != '' );
		$( '.wbte_sc_bogo_apply_custom_min_span' ).html( min_value );
		$( 'input[name="wbte_sc_bogo_apply_custom_min[0]"]' ).val( min_value );

		$( '.wbte_sc_bogo_apply_custom_max_span_input' ).toggle( max_value === '' );
		$( '.wbte_sc_bogo_apply_custom_max_span' ).toggle( max_value != '' );
		$( '.wbte_sc_bogo_apply_custom_max_span' ).html( max_value );
		$( 'input[name="wbte_sc_bogo_apply_custom_max[0]"]' ).val( max_value );
	}

	function wbte_sc_bogo_apply_repeatedly_fields_index(){
		var total_length = 0
		$( '.wbte_sc_bogo_apply_repeatedly_custom_range_row' ).each(
			function (index) {
				$( this ).find( '.wbte_sc_bogo_edit_input' ).each(
					function () {
						let oldName = $( this ).attr( 'name' );
						let newName = oldName.replace( /\[.*?\]/, '[' + ( index + 1 ) + ']' );
						let oldId   = $( this ).attr( 'id' );
						let newId   = oldId.replace( /\[.*?\]/, '[' + ( index + 1 ) + ']' );
						$( this ).attr( 'id', newId );
						$( this ).attr( 'name', newName ).removeClass( 'wbte_sc_bogo_exclude_serialize' );
					}
				);
				total_length++;
			}
		);
		$( '#wbte_sc_bogo_apply_custom_max\\[' + total_length + '\\]' ).attr( 'placeholder', '\u221E' );
		return total_length;
	}

	/** Function call is done by dynamically, dont remove it. */
	function wbte_sc_bogo_repeatedly_custom_validation(){
		/** For validating custom ranges. */
		if ( 'wbte_sc_bogo_apply_custom' === $( 'input[ name=wbte_sc_bogo_apply_offer ]:checked' ).val() ) {
			var customMin   = $( 'input[name^="wbte_sc_bogo_apply_custom_min"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();
			var customMax   = $( 'input[name^="wbte_sc_bogo_apply_custom_max"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();
			var customTimes = $( 'input[name^="wbte_sc_bogo_apply_custom_times"]' ).not( '.wbte_sc_bogo_exclude_serialize' ).map( (_, el) => el.value ).get();

			const is_spends = wbte_sc_bogo_is_spends();
			let prevMax     = '';
			let lastIndex   = customMin.length - 1;

			const customMinLenght = customMin.length;
			for (let i = 0; i < customMinLenght; i++) {
				const minValue   = parseFloat( customMin[i] );
				const maxValue   = parseFloat( customMax[i] );
				const timesValue = parseFloat( customTimes[i] );

				/** For checking min value. */
				if ( ! wbte_sc_bogo_validate_custom_min( minValue, prevMax, is_spends ) ) {
					wbte_sc_bogo_show_validation_msg(
						'wbte_sc_bogo_apply_custom_min\\[' + i + '\\]'
						,
						is_spends ? wbte_sc_bogo_params.err_msgs.range_spe_min : wbte_sc_bogo_params.err_msgs.range_qty_min
						,
						false
						,
						'tr'
					);
					return false;
				}

				/** For checking max value. No need to check for last range. */
				if (  i === lastIndex ) {
					if ( '' !== maxValue && maxValue < minValue ) {
						wbte_sc_bogo_show_validation_msg(
							'wbte_sc_bogo_apply_custom_max\\[' + i + '\\]'
							,
							is_spends ? wbte_sc_bogo_params.err_msgs.range_spe_max : wbte_sc_bogo_params.err_msgs.range_qty_max
							,
							false
							,
							'tr'
						);
						return false;
					}
				} else {
					if ( ! maxValue || maxValue < minValue ) {
						wbte_sc_bogo_show_validation_msg(
							'wbte_sc_bogo_apply_custom_max\\[' + i + '\\]'
							,
							is_spends ? wbte_sc_bogo_params.err_msgs.range_spe_max : wbte_sc_bogo_params.err_msgs.range_qty_max
							,
							false
							,
							'tr'
						);
						return false;
					}
				}

				/** For checking giveway times value. */
				if ( ! timesValue || timesValue < 1 ) {
					wbte_sc_bogo_show_validation_msg(
						'wbte_sc_bogo_apply_custom_times\\[' + i + '\\]'
						,
						wbte_sc_bogo_params.err_msgs.gre_equal_1
						,
						false
						,
						'tr'
					);
					return false;
				}

				prevMax = maxValue;
			}
		}
		return true;
	}

	function wbte_sc_bogo_edit_general_settings(){

		const element = $('.wbte_sc_bogo_code_cond_help_txt').detach();
        $('input[name="wbte_sc_bogo_code_condition"][value="wbte_sc_bogo_code_auto"]').parent().after(element);

		/** Coupon code automatic or manual */
		$( 'input[ type=radio ][ name=wbte_sc_bogo_code_condition ]' ).on(
			'change',
			function () {
				if ( 'wbte_sc_bogo_code_auto' === this.value ) {
					$( '#wbte_sc_bogo_coupon_code' ).parent( 'div' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				} else {
					$( '#wbte_sc_bogo_coupon_code' ).parent( 'div' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				}
			}
		);

		$( '.wbte_sc_bogo_code_copy' ).on(
			'click',
			function () {
				const couponName = $( '#wbte_sc_bogo_coupon_name' ).val() || '';
				const couponId	= $( '#wt_sc_bogo_coupon_id' ).val() || 0 ;
				let timeoutId;

				$.ajax(
					{
						url: wbte_sc_bogo_params.ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							'action'	: 'wbte_sc_get_auto_offer_code',
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce,
							'coupon_name' : couponName,
							'coupon_id'	: couponId
						},
						success: async function ( data ) {

							if ( data.status && '' !== data.coupon_code ) {
								try {
									await navigator.clipboard.writeText( data.coupon_code );
									const newToolTip = wbte_sc_bogo_params.text.success_copy.replace( '{coupon_code}', data.coupon_code );
									$( '.wbte_sc_hidden_tooltip' ).html( newToolTip );
									
									if ( timeoutId ) clearTimeout( timeoutId );
									timeoutId = setTimeout( () => {
										$( '.wbte_sc_hidden_tooltip' ).html( wbte_sc_bogo_params.text.coupon_copy_tooltip ); 
									}, 1000 );
								} catch (err) {
									wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.failed_copy, err );
								}
							} else {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.failed_copy );
							}
						},
						error:function () {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.failed_copy );
						}
					}
				);
			}
		);

		$( '#wbte_sc_bogo_coupon_code' ).on(
			'input',
			function () {
				var errorSpan = $( '.wbte_sc_bogo_coupon_code_error_span' );
				errorSpan.find( '.wbte_sc_bogo_edit_error_txt' ).prev( 'br' ).remove();
				errorSpan.find( '.wbte_sc_bogo_edit_error_txt' ).remove();

				if ( ! wbte_sc_bogo_coupon_code_validation() ) {
					errorSpan.append( '<br><span class="wbte_sc_bogo_edit_error_txt">' + wbte_sc_bogo_params.err_msgs.coupon_code_error + '</span>' );
				} else {
					errorSpan.empty();
				}
			}
		);

		/** Display coupon display checkbox on edit or add */
		$( '.wbte_sc_bogo_coupon_display_add_btn, .wbte_sc_bogo_display_div img' ).on(
			'click',
			function () {
				$( '.wbte_sc_bogo_display_div .wbte_sc_checkbox' ).fadeIn();
				$( '.wbte_sc_bogo_selected_display_span, .wbte_sc_bogo_coupon_display_add_btn' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			}
		);

		/** Add or remove display coupon tag on change in display coupon selection */
		$( 'input[type="checkbox"][name="_wc_make_coupon_available[]"]' ).on(
			'change',
			function () {

				const checkboxId = $( this ).attr( 'id' );
				if ( ! this.checked ) {
					$( '.wbte_sc_bogo_selected_display.' + checkboxId ).remove();
				} else {

					const labelText = $( 'label[for="' + checkboxId + '"]' ).text().trim();
					const newSpan   = ` <span class = "wbte_sc_bogo_selected_display ${checkboxId}" > ${labelText} </span> `;

					$( '.wbte_sc_bogo_selected_display_span' ).prepend( newSpan );
				}
			}
		);

		/**
		 *  Number validation in time fields
		 */
		jQuery( '.wt_sc_coupon_time_field' ).on(
			'input',
			function () {
				var vl  = jQuery( this ).val();
				var reg = /^[0-9]{0,2}$/;

				if ( ! reg.test( vl ) ) {
					var new_vl     = '';
					vl             = String( vl );
					var val_length = vl.length;
					for ( var i = 0; i < val_length; i++ ) {

						if ( 2 === new_vl.length ) {
							break;
						}

						if ( reg.test( vl[i] ) ) {
							new_vl += vl[i];
						}
					}

					jQuery( this ).val( new_vl );
					wbte_sc_schedule_min_max_validation( jQuery( this ) );

				} else {
					wbte_sc_schedule_min_max_validation( jQuery( this ) );
				}
			}
		);

		/** Display or hide start/enddate fields on clicking schedule checkbox  */
		$( '#wbte_sc_bogo_schedule' ).on(
			'change',
			function () {
				if ( $( this ).is( ':checked' ) ) {
					$( '#wbte_sc_bogo_schedule_content' ).fadeIn();
				} else {
					$( '#wbte_sc_bogo_schedule_content' ).fadeOut();
				}
			}
		);
		/** Expiry enable days in days checkbox */
		$( '#_wt_coupon_enable_days' ).on(
			'change',
			function () {
				if ( $( this ).is( ':checked' ) ) {
					$( '.wbte_sc_schedule_expiry_in_days_div' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_schedule_expiry_div' ).hide();
				} else {
					$( '.wbte_sc_schedule_expiry_in_days_div' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_schedule_expiry_div' ).show();
				}
			}
		);

		/** Change color of datepicker placeholder and value. */
		$( '.wbte_sc_bogo_date_picker' ).on(
			'change',
			function () {
				'' === $( this ).val() ? $( this ).attr( 'style', 'color: #9DA3AA !important' ) : $( this ).attr( 'style', 'color: #2A3646 !important' );
				if ( '' !== $( this ).val() ) {
					const parentTr = $( '#wbte_sc_bogo_schedule_content' );
					parentTr.find( '.wbte_sc_bogo_edit_error_txt' ).remove();
					parentTr.find( 'img[src$="exclamation_red.svg"]' ).prev( 'br' ).remove();
					parentTr.find( 'img[src$="exclamation_red.svg"]' ).remove();
				}
			}
		);

		/** Show warning if expiry date is less than current date on page load */
		wbte_sc_bogo_show_expiry_warning();

		/** Show warning if expiry date is less than current date */
		$( '.wbte_sc_schedule_expiry_field_row input' ).on( 'change input', function () {
			wbte_sc_bogo_show_expiry_warning();
		});
	}

	function wbte_sc_bogo_show_expiry_warning() {
		const expiryDateInput = $('#expiry_date').val();
		const expiryHour = $('input[name="_wt_sc_coupon_expiry_time_hour"]').val() || '11';
		const expiryMinute = $('input[name="_wt_sc_coupon_expiry_time_minute"]').val() || '59';
		const expiryMeridiem = $('input[name="wbte_sc_bogo_expire_meridiem"]:checked').val() || 'AM';
	
		/** Parse the expiry date and time */
		const expiryDate = new Date(expiryDateInput);
		let expiryHour24 = parseInt(expiryHour, 10);
		const expiryMinuteInt = parseInt(expiryMinute, 10);
	
		/** Convert 12-hour format to 24-hour format */
		if (expiryMeridiem === 'PM' && expiryHour24 !== 12) {
			expiryHour24 += 12;
		} else if (expiryMeridiem === 'AM' && expiryHour24 === 12) {
			expiryHour24 = 0;
		}
	
		expiryDate.setHours(expiryHour24);
		expiryDate.setMinutes(expiryMinuteInt);
		expiryDate.setSeconds(0); // set seconds to 0 for precise comparison.
	

		const timezone = wbte_sc_bogo_params.timezone || 'UTC';
	
		try {
			
			const currentDate = new Date();
			const currentDateInWPTimeZone = new Date(
				currentDate.toLocaleString( 'en-US', { timeZone: timezone } )
			);
	
			
			if ( currentDateInWPTimeZone > expiryDate ) {
				$( '.wbte_sc_bogo_end_date_warning' ).css( 'display', 'flex' );
			} else {
				$( '.wbte_sc_bogo_end_date_warning' ).hide();
			}
		} catch ( error ) {
			$( '.wbte_sc_bogo_end_date_warning' ).hide(); 
		}
	}	

	/** Function call is done by dynamically, dont remove it. Return false to show error message. */
	function wbte_sc_bogo_coupon_code_validation(){

		/** If auto coupon code is selected then no need to validate. */
		if ( 'wbte_sc_bogo_code_auto' === $( 'input[ name="wbte_sc_bogo_code_condition" ]:checked' ).val() ) {
			return true;
		}

		/** Regular expression to allow only alphabets, numbers, and hyphens */
		var validPattern = /^[a-z0-9-]+$/;
		var inputVal 	 = $( '#wbte_sc_bogo_coupon_code' ).val();

		return validPattern.test( inputVal );
	}

	/** Function call is done by dynamically, dont remove it. Return false to show error message. */
	function wbte_sc_bogo_schedule_empty_check(){
		if (
			$( '#wbte_sc_bogo_schedule' ).is( ':checked' )
			&& ! $( '#_wt_coupon_enable_days' ).is( ':checked' )
			&& '' === $( '#_wt_coupon_start_date' ).val()
			&& '' === $( '#expiry_date' ).val()
		) {
			return false;
		} else {
			return true;
		}
	}

	function wbte_sc_schedule_min_max_validation( elm ) {
		var vl  = parseInt( elm.val() );
		var min = parseInt( elm.attr( 'min' ) );
		var max = parseInt( elm.attr( 'max' ) );

		if ( vl < min || vl > max ) {
			elm.val( '' );
		} else {
			if ( 2 === elm.val().length ) {
				elm.next().trigger( 'focus' );
			}
		}
	}

	function wbte_sc_bogo_coupon_save(){
		$( '#wbte_sc_bogo_coupon_save' ).on(
			'submit',
			function (e) {
				e.preventDefault();
				wbte_sc_bogo_show_overlay();
				const clicked_button = $( e.originalEvent.submitter ).attr( 'data-btn-id' );
				var allowed_emails   = [];

				$( 'input[type="text"]' ).each(
					function () {
						if ( 
							$( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' )
							|| $( this ).closest( 'div.wbte_sc_parent_div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) 
						) {
								$( this ).val( '' ); /** Make text field value empty if field is in hidden state */
						}
					}
				);

				$( 'input[type="checkbox"]' ).each(
					function () {
						if ( $( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
								$( this ).prop( 'checked', false );
						}
					}
				);

				$( 'select' ).each(
					function () {
						if ( 
							$( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) 
							|| $( this ).closest( 'div.wbte_sc_parent_div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' )
						) {
								$( this ).val( null ).trigger( 'change' ); /** Clear selected values if hidden */
						}
					}
				);

				$( 'input[type="date"]' ).each(
					function () {
						if ( 
							$( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' )
							|| $( this ).closest( 'div.wbte_sc_parent_div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) 
						) {
								$( this ).val( '' ); 
						}
					}
				);

				wbte_sc_bogo_remove_empty_custom_ranges();

				if ( ! $( '#wbte_sc_bogo_schedule' ).is( ":checked" ) ) {
					$( '#_wt_coupon_start_date, #expiry_date, #_wt_coupon_expiry_in_days' ).val( '' );
					$( '#_wt_coupon_enable_days' ).prop( 'checked', false ).trigger( 'change' );
				}
				if( 0 === $( 'tr[data-row="wbte_sc_bogo_purchase_history_row"]:visible' ).length ){
					$( '#nth_coupon_no_of_coupon_condition' ).val( '' );
				}

				/** Remove checked radio values if they are in hidden state. The values will be used to set the radio button checked state after form submission. */
				let hidden_radio_values = {};
				$( '.wbte_sc_bogo_radio_remove_val_if_hidden' ).each( function(){
					if( $(this).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ){
						hidden_radio_values[ $(this).find( 'input[type="radio"]:checked' ).attr( 'name' ) ] = $(this).find( 'input[type="radio"]:checked' ).val();
						$(this).find( 'input[type="radio"]:checked' ).prop( 'checked', false );
					}
				} );

				var fieldValues = {};
				$( this ).find( ":input" ).not( '.wbte_sc_bogo_exclude_serialize' ).each(
					function () {
						var input = $( this );
						var name  = input.attr( 'name' );
						var value = input.val();

						if (input.is( ':radio' )) {
							/** Only store the value if the radio button is checked */
							if (input.is( ':checked' )) {
								fieldValues[name] = value;
							}
						} else if (input.is( ':checkbox' )) {
							/** Only store the value if the checkbox is checked */
							if (input.is( ':checked' )) {
								if ( ! (name in fieldValues)) {
									fieldValues[name] = [];
								}
								fieldValues[name].push( value );
							}
						} 
						else {
							if( input.hasClass( 'wbte_sc_bogo_avoid_if_hidden' ) && input.closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ){
								return;
							}
							/** For other input types, store the value if it's not empty or if the field hasn't been seen before */
							if (value !== '' || ! (name in fieldValues)) {
								fieldValues[name] = value;
							}
						}
					}
				);
				if ( ! wbte_sc_form_submit_validation() ) {
					wbte_sc_bogo_remove_overlay();
					return;
				}
				wbte_sc_bogo_remove_all_validation_msg(); /** If here means validation passed. So remove all validation messages if any. */

				/** Set the checked radio button values as how they were before form submission. */
				for ( const [ name, value ] of Object.entries( hidden_radio_values ) ) {
					$( `input[name="${name}"][value="${value}"]` ).prop( 'checked', true );
				}

				var data = $.param( fieldValues );

				/** Add allowed emails to data */
				let emails = $( '[name="wbte_sc_bogo_emails[]"]' ).val();
				$.each(
					emails,
					function ( index, email ) {
						if ( wbte_sc_bogo_email_select.validateEmail( email ) ) {
							allowed_emails.push( email );
						}
					}
				);
				$( '.wbte_sc_bogo_email_select_inner span.invalid' ).remove();

				data += '&customer_email=' + allowed_emails + '&clicked_button=' + clicked_button;
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' 	: 'wbte_sc_bogo_coupon_save',
							'data'		: data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function ( data ) {
							if ( data.status ) {
								wbte_sc_notify_msg.success( data.msg );

								wbte_sc_bogo_form_submitted = true;

								/** Remove get param newly_created from URL. Which is used to hide or show status selection */
								let currentUrl = window.location.href;
								let url        = new URL( currentUrl );
								if ( url.searchParams.has( 'newly_created' ) ) {
									url.searchParams.delete( 'newly_created' );
									window.history.replaceState( null, '', url.toString() );
									if ( 'publish' === data.bogo_sts ) {
										$( '#_wbte_sc_bogo_selected_sts_publish' ).prop( 'checked', true );
									} else {
										$( '#_wbte_sc_bogo_selected_sts_draft' ).prop( 'checked', true );
									}
									$( '.wbte_sc_bogo_save_and_activate' ).hide();
									$( '.wbte_sc_bogo_edit_gnrl_sts_radio' ).removeClass( 'hide' );
								}
							} else {
								wbte_sc_notify_msg.error( data.msg );
							}
							wbte_sc_bogo_remove_overlay();
						},
						error:function () {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	function wbte_sc_form_submit_validation(){

		var err_fields = wbte_sc_bogo_get_error_fields();

		for ( const [key, value] of Object.entries( err_fields ) ) {

			if ( 
				! $( '#' + key ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) 
				&& ! $( '#' + key ).closest( 'div.wbte_sc_parent_div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) 
				&& 'disabled' !== $( '#' + key ).attr( 'disabled' )
			) {

				var val1       = $( '#' + key ).val();
				var val2       = value.restriction.constructor === Array ? value.restriction[0] : value.restriction;
				const isSelect = value.type && 'select' === value.type;
				let parentLoc  = typeof( value.parent_loc ) !== undefined ? value.parent_loc : 'td';

				if ( isSelect && $( '#' + key ).closest( 'div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
					continue;
				}

				if ( 'special' === value.condition && 'undefined' !== typeof( value.func_name ) ) {
					const specialFields = ['wbte_sc_bogo_emails', 'wbte_sc_bogo_repeatedly_custom', '_wbte_sc_bogo_min_qty' ];
					if ( specialFields.includes( key ) ) {
						if ( ! eval( value.func_name + '()' ) ) {
							return false;
						}
					} else if ( ! eval( value.func_name + '()' ) ) {
						wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg ], isSelect, parentLoc );
						return false;
					}
					continue;
				}

				if ( '' === val1 ) {
					if ( value.strict ) {
						wbte_sc_bogo_show_validation_msg(
							key,
							value.err_msg.constructor === Array ? wbte_sc_bogo_params.err_msgs[ value.err_msg[0] ] : wbte_sc_bogo_params.err_msgs[ value.err_msg ],
							isSelect,
							parentLoc
						);
						return false;
					} else {
						continue;
					}
				}

				if ( ( 'string' === typeof( val2 ) && val2.startsWith( '#' ) ) ) { /** ID given. */
					val2 = $( val2 ).val();
				}

				if ( value.multiple_condition ) {
					let err_flag = false;
					value.condition.forEach(
						function ( condition, index ) {
							if ( ! wbte_sc_bogo_validation_arithmetic( val1, value.restriction[index], condition ) ) {
									wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg[index] ], isSelect, parentLoc );
									err_flag = true;
									return false;
							}
						}
					);
					if ( err_flag ) {
						return false;
					}
				} else {
					if ( ! wbte_sc_bogo_validation_arithmetic( val1, val2, value.condition ) ) {
						wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg ], isSelect, parentLoc );
						return false;
					}
				}
			}
		}

		return true;
	}

	function wbte_sc_bogo_show_validation_msg( id, msg, is_select, parentLoc = 'td' ){
		var elm        = $( '#' + id );
		var parentElm  = elm.closest( parentLoc );
		let breakFront = '';
		let breakEnd   = '<br>';
		var err_icon   = '<img style="vertical-align:middle; width:16px; display:inline-block;" src="' + wbte_sc_bogo_params.urls.image_path + 'exclamation_red.svg">';

		/** Handle 'select2' elements */
		if (is_select) {
			if ( 0 < elm.closest( 'div' ).find( 'span.select2-selection' ).length ) {
				elm = elm.closest( 'div' ).find( 'span.select2-selection' );
			}
			breakFront = '<br>';
			breakEnd   = '';
		}

		/** Add error text if not already present */
		if (parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).length === 0) {
			parentElm.append( `<span class="wbte_sc_bogo_edit_error_txt_container">${breakFront}<span class="wbte_sc_bogo_edit_error_txt">${breakEnd}${msg}</span></span>` );
		}

		/** Handle input fields with icons */
		if (elm.closest( 'div' ).hasClass( 'wbte_sc_bogo_icon_input' )) {
			elm = elm.closest( 'div' );
		}

		/** Append error icon */
		if ( parentElm.find( 'img[src$="exclamation_red.svg"]' ).length === 0 ) {
			if ( ! is_select) {
				elm.after( `<span class="wbte_sc_bogo_edit_error_txt_container">&nbsp;${ err_icon }</span>` );
			} else {
				parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).before( err_icon + '&nbsp;' );
			}
		}

		/** Add error class to input */
		elm.addClass( 'wbte_sc_bogo_error_border' );

		/** Open step container if it's not already opened */
		var stepContainer = elm.closest( '.wbte_sc_bogo_edit_step' );
		if ( ! stepContainer.hasClass( 'wbte_sc_bogo_step_container_opened' )) {
			stepContainer.trigger( 'click' );
		}

		/** Trigger focus on the input or select field */
		var focusElem = elm.hasClass( 'wbte_sc_bogo_icon_input' ) ? elm.find( 'input' ) : elm;
		focusElem.trigger( 'focus' );
		setTimeout(
			() => {
            focusElem[0].scrollIntoView( { behavior: 'smooth', block: 'center' } );
			},
			10
		);

	}

	function wbte_sc_bogo_remove_validation_msg( id, is_name = false ) {
		var elm       = $( '#' + id );
		var parentElm = elm.closest( 'td' );
		if ( is_name ) {
			elm       = $( 'input[name="' + id + '"]' );
			parentElm = elm.closest( 'tr' );
		}
		if ( ! parentElm.length ) {
			parentElm = elm.closest( 'div' );
		}
		parentElm.find( '.wbte_sc_bogo_edit_error_txt_container' ).remove();

		parentElm.find( '.wbte_sc_bogo_error_border' ).removeClass( 'wbte_sc_bogo_error_border' );
	}

	function wbte_sc_bogo_realtime_validation(){
		var err_fields = wbte_sc_bogo_get_error_fields();

		for (const [key, value] of Object.entries( err_fields )) {

			const continueArr = ['wbte_sc_bogo_emails', 'wbte_sc_bogo_repeatedly_custom', 'wbte_sc_bogo_schedule_content' ];
			if ( continueArr.includes( key ) ) {
				continue;
			}
			/** Only add listeners for fields that are not 'select' elements */
			if (value.type !== 'select') {
				$( '#' + key ).on(
					'input',
					function () {
						wbte_sc_bogo_validate_fields( key, value );
					}
				);
			} else {
				$( '#' + key ).on(
					'change',
					function () {
						wbte_sc_bogo_validate_fields( key, value );
					}
				);
			}
		}

		const is_spends = wbte_sc_bogo_is_spends();

		$( 'input[name^="wbte_sc_bogo_apply_custom_min"]' ).on(
			'input',
			function () {
				var nameAttr = $( this ).attr( 'name' );
				var index    = nameAttr.match( /\d+/ )[0];
				let minVal   = $( this ).val();
				let prevMax  = $( 'input[name="wbte_sc_bogo_apply_custom_max[' + (index - 1) + ']"]' ).val();
				if ( wbte_sc_bogo_validate_custom_min( minVal, prevMax, is_spends ) ) {
					wbte_sc_bogo_remove_validation_msg( nameAttr, true );
				}
			}
		);

		$( 'input[name^="wbte_sc_bogo_apply_custom_max"]' ).on(
			'input',
			function () {
				var nameAttr = $( this ).attr( 'name' );
				var index    = nameAttr.match( /\d+/ )[0];
				let maxVal   = $( this ).val();
				let minVal   = $( 'input[name="wbte_sc_bogo_apply_custom_min[' + index + ']"]' ).val();

				const lastIndex = $( 'input[name^="wbte_sc_bogo_apply_custom_max"]:not(.wbte_sc_bogo_exclude_serialize)' ).length - 1;
				if ( maxVal >= minVal || ( index == lastIndex && '' == maxVal ) ) {
					wbte_sc_bogo_remove_validation_msg( nameAttr, true );
				}
			}
		);

		$( 'input[name^="wbte_sc_bogo_apply_custom_times"]' ).on(
			'input',
			function () {
				var nameAttr = $( this ).attr( 'name' );
				let timesVal = $( this ).val();
				if ( timesVal >= 1 ) {
					wbte_sc_bogo_remove_validation_msg( nameAttr, true );
				}
			}
		);
	}

	function wbte_sc_bogo_validate_custom_min( minVal, prevMax, isSpends ){
		/**
		 * For checking min value.
		 * Condition left of 'OR' is for 'spends' and right of 'OR' is for 'quantity'.
		 * For spends min value can be same or greater than previous max value and for quantity min value should be greater than previous max value.
		 */
		if (
			( isSpends
				&& ( ( ! minVal && 0 != minVal )
					|| ( prevMax && minVal < prevMax )
				)
			)
			|| ( ! isSpends
				&& ( ! minVal
					|| ( prevMax && minVal <= prevMax )
				)
			)
		) {
			return false;
		}
		return true;
	}

	function wbte_sc_bogo_validate_fields( fieldId, fieldConfig ){
		var val1 = $( '#' + fieldId ).val();
		var val2 = fieldConfig.restriction.constructor === Array ? fieldConfig.restriction[0] : fieldConfig.restriction;

		if ( ! fieldConfig.strict && '' === val1 ) {
			wbte_sc_bogo_remove_validation_msg( fieldId );
		}

		if (  'special' === fieldConfig.condition && typeof( fieldConfig.func_name ) !== 'undefined' ) {
			if ( eval( fieldConfig.func_name + '()' ) ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		}

		/** Handle ID-based restriction */
		if ( typeof val2 === 'string' && val2.startsWith( '#' ) ) {
			val2 = $( val2 ).val();
		}

		/** Remove validation message if value is valid */
		if ( fieldConfig.multiple_condition ) { /** For multiple conditions. */
			let err_flag = true;
			fieldConfig.condition.forEach(
				function ( condition, index ) {
					if ( wbte_sc_bogo_validation_arithmetic( val1, fieldConfig.restriction[index], condition ) ) {
							err_flag = false;
					}
					err_flag = wbte_sc_bogo_validation_arithmetic( val1, fieldConfig.restriction[index], condition ) ? false : true;
				}
			);
			if ( ! err_flag ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		} else {
			if ( wbte_sc_bogo_validation_arithmetic( val1, val2, fieldConfig.condition ) ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		}

	}

	/** 
	 * Field validation is done in given order. But for optional condition fields the order will be as user choosing. So optional fields are added in an array (optlCondIdList), it will will be sorted how they are in DOM, after that it will add to the returning object.
	*/
	function wbte_sc_bogo_get_error_fields(){
		let untilOptl = {
			/** Step 1. */
			'wbte_sc_bogo_free_product_ids' : {
				'err_msg'	  : 'atleast_1_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_free_category_ids' : {
				'err_msg'	  : 'atleast_1_cat',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_customer_gets_qty' : {
				'err_msg'	  : 'gre_equal_1',
				'condition'	  : '>=',
				'restriction' : 1,
				'strict'	  : true,
			},
			'wbte_sc_bogo_customer_gets_final_price' : {
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'wbte_sc_bogo_customer_gets_discount_perc' : {
				'err_msg'	  		 : [ 'gre_0', 'perc_less_eq_100' ],
				'restriction' 		 : [ 0, 100 ],
				'condition'	  		 : [ '>', '<=' ],
				'strict'	  		 : true,
				'multiple_condition' : true
			},
			'wbte_sc_bogo_customer_gets_discount_price' : {
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			/** Step 2 */
			'_wbte_sc_bogo_min_amount' : {
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_amount' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_amount',
				'condition'	  : '>='
			},
			'_wbte_sc_bogo_min_qty' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_min_qty_validation',
			},
			'_wbte_sc_bogo_max_qty' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_qty',
				'condition'	  : '>=',
				'parent_loc'  : wbte_sc_is_bxgx() ? 'td' : 'th'
			},
			/** Step 2 prod/cat fields. */
			'wbte_sc_bogo_specific_products' : {
				'err_msg'	  : 'atleast_1_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_excluded_products' :{
				'err_msg'	  : 'atleast_1_ex_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'product_categories' :{
				'err_msg'	  : 'atleast_1_cat',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'exclude_product_categories' :{
				'err_msg'	  : 'atleast_1_ex_cat',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			}
		};

		const optlCondIdList = [ '_wbte_sc_bogo_min_qty_add', '_wbte_sc_bogo_max_qty_add', '_wbte_sc_min_qty_each', '_wbte_sc_max_qty_each', '_wbte_sc_bogo_min_amount_adtl', '_wbte_sc_bogo_max_amount_adtl', '_wt_sc_user_roles', 'wbte_sc_bogo_emails', 'usage_limit', 'usage_limit_per_user', '_wt_sc_payment_methods', '_wt_sc_shipping_methods', '_wt_coupon_available_location', 'wt_nth_order_no_of_orders', 'wt_nth_order_order_total', '_wt_sc_nth_order_within_days', '_wt_sc_nth_order_date_from', 'wt_order_Status_need_to_count', '_wt_sc_nth_order_products' ];

		let optlCondition = {
			/** Step 2 optional fields. */
			'_wbte_sc_bogo_min_qty_add' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_qty_add' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_qty_add',
				'condition'	  : '>'
			},
			'_wbte_sc_min_qty_each' :{
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_max_qty_each' :{
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_min_qty_each',
				'condition'	  : '>'
			},
			'_wbte_sc_bogo_min_amount_adtl' :{
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_amount_adtl' :{
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_amount_adtl',
				'condition'	  : '>'
			},
			'_wt_sc_user_roles' : {
				'err_msg'	  : 'atleast_1_user_role',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_emails' : {
				'err_msg'	  : 'email_error',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_email_validation',
			},
			'usage_limit' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'usage_limit_per_user' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'_wt_sc_payment_methods' : {
				'err_msg'	  : 'atleast_1_payment',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'_wt_sc_shipping_methods' : {
				'err_msg'	  : 'atleast_1_shipping',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'_wt_coupon_available_location' : {
				'err_msg'	  : 'atleast_1_loc',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			/** Step 2 Additional conditions Purchase history */
			'wt_nth_order_no_of_orders' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'wt_nth_order_order_total' : {
				'err_msg'	  : 'gre_equal_0',
				'restriction' : 0,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'_wt_sc_nth_order_within_days' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'_wt_sc_nth_order_date_from' : {
				'err_msg'	  : 'empty_schedule',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_pch_date_range_validation',
				'type'		  : 'select',
			},
			'wt_order_Status_need_to_count' : {
				'err_msg'	  : 'atleast_1_order',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'_wt_sc_nth_order_products' : {
				'err_msg'	  : 'atleast_1_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
		}

		let sortedOptlCondIdList = [];
		let newOptlCondIdList = {};

		$( '.wbte_sc_bogo_additional_fields_contents tr' ).each( function () {
			$( this ).find('[name]').each(function() {
				const $trName = $(this).attr('name').replace(/\[\]$/, '');
				if( optlCondIdList.includes( $trName ) ){
					sortedOptlCondIdList.push( $trName );
				}
			});
		} );

		sortedOptlCondIdList.forEach( function ( id ) {
			newOptlCondIdList[ id ] = optlCondition[ id ];
		} );

		let afterOptl = {
				
			/** Step 3. */
			'wbte_sc_bogo_repeatedly_times' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 0,
				'condition'	  : '>'
			},
			'wbte_sc_bogo_repeatedly_custom' : {
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_repeatedly_custom_validation',
				'parent_loc'  : 'div'
			},
			/** General settings. */
			'wbte_sc_bogo_coupon_name' : {
				'err_msg'	  : 'no_camp_title',
				'restriction' : '',
				'condition'	  : '!=',
				'strict'	  : true,
				'parent_loc'  : 'div'
			},
			'wbte_sc_bogo_coupon_code' : {
				'err_msg'	  : 'coupon_code_error',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_coupon_code_validation',
				'strict'	  : true,
				'parent_loc'  : 'div'
			},
			'wbte_sc_bogo_schedule_content' : {
				'err_msg'	  : 'empty_schedule',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_schedule_empty_check',
				'strict'	  : true,
				'parent_loc'  : 'div',
				'type'		  : 'select',
			},
			'_wt_coupon_expiry_in_days' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true,
				'parent_loc'  : 'div'
			},
		}

		return $.extend( untilOptl, newOptlCondIdList, afterOptl );
	}

	function wbte_sc_bogo_validation_arithmetic( val1, val2, operator ){

		if ( Array.isArray( val1 ) ) {
			val1 = val1.length; 
		} else if ( isNaN( val1 ) ) {
			val1 = 0; 
		} else {
			val1 = parseFloat( val1 );
		}
	
		if ( Array.isArray( val2 ) ) {
			val2 = val2.length; 
		} else if ( isNaN( val2 ) ) {
			val2 = 0; 
		} else {
			val2 = parseFloat( val2 );
		}
		
		switch ( operator ) {
			case '>':
				return val1 > val2;
			case '>=':
				return val1 >= val2;
			case '<':
				return val1 < val2;
			case '<=':
				return val1 <= val2;
			case '==':
				return val1 == val2;
			case '===':
				return val1 === val2;
			case '!=':
				return val1 != val2;
			default:
				return false;
		}
	}

	function wbte_sc_bogo_remove_empty_custom_ranges(){

		if ( 'wbte_sc_bogo_apply_custom' === $( 'input[ name=wbte_sc_bogo_apply_offer ]:checked' ).val() ) {

			$( 'input[name^="wbte_sc_bogo_apply_custom_min"]:not(.wbte_sc_bogo_exclude_serialize)' ).each(
				function () {
					var nameAttr = $( this ).attr( 'name' );
					var index    = nameAttr.match( /\d+/ )[0];

					var minVal   = $( this ).val();
					var maxVal   = $( 'input[name="wbte_sc_bogo_apply_custom_max[' + index + ']"]' ).val();
					var timesVal = $( 'input[name="wbte_sc_bogo_apply_custom_times[' + index + ']"]' ).val();

					if ( 0 != index && ! minVal && ! maxVal && ! timesVal ) {
						$( this ).closest( 'tr' ).remove();
					}
				}
			);
		}
	}

	function wbte_sc_bogo_remove_all_validation_msg(){
		$( '.wbte_sc_bogo_edit_error_txt_container' ).remove();
		$( '.wbte_sc_bogo_error_border' ).removeClass( 'wbte_sc_bogo_error_border' );
	}

	function wbte_sc_is_bxgx(){
		return 'wbte_sc_bogo_bxgx' === $( 'input[ name=wbte_sc_bogo_type ]' ).val();
	}

	function wbte_sc_set_customer_gets_value_summary(){

		const customerGets = wbte_sc_is_bxgx() ? $( 'input[ type=radio ][ name=wbte_sc_bogo_customer_gets ]:checked' ).parent().text().trim() : $( 'input[ type=radio ][ name=wbte_sc_bogo_customer_gets_cheap_exp ]:checked' ).closest( 'label' ).find( '.wbte_sc_radio-text' ).text();

		$( '.wbte_sc_bogo_edit_gets_selected' ).text( customerGets );
	}

	function wbte_sc_step2_opt_conditions(){

		/** Additional conditions */
		$( '.wbte_sc_bogo_edit_addition_conditions' ).on(
			'click',
			function ( e ) {
				if ( $( '.wbte_sc_bogo_edit_additional_condition_select' ).is( ':visible' ) ) {
					$( '.wbte_sc_bogo_edit_additional_condition_select' ).fadeOut();
				} else {
					$( '.wbte_sc_bogo_edit_additional_condition_select' ).fadeIn();
				}
			}
		);

		wbte_sc_bogo_step2_add_conditions_summary();

		$( '.wbte_sc_bogo_dropdown_menu_item_head' ).on( 'click', function () {
			$( '.wbte_sc_bogo_submenu' ).hide();
			$( this ).closest( '.wbte_sc_bogo_dropdown_menu_item' ).find( '.wbte_sc_bogo_submenu' ).css({ display: 'flex', flexDirection: 'column', gap: '2px' });
		} );

		wbte_sc_bogo_email_select.Set();

		$( 'input[type="radio"][name="wbte_sc_bogo_purchase_history"]' ).on( 
			'change', function () {
				
				if( 'wbte_sc_bogo_puchase_history_returning' === $( this ).val() ){
					$( '.wbte_sc_bogo_purchase_history_returning_div' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				}else{
					$( '.wbte_sc_bogo_purchase_history_returning_div' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				}
			}
		);

		$( 'input[type="radio"][name="_nth_coupon_order_date_or_days"]' ).on( 
			'change', function () {
				
				if( 'date' === $( this ).val() ){
					$( '#wbte_sc_bogo_purchase_history_date_range' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '#_wt_sc_nth_order_within_days' ).prop( 'disabled', true );
				}else{
					$( '#wbte_sc_bogo_purchase_history_date_range' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '#_wt_sc_nth_order_within_days' ).prop( 'disabled', false );
				}
			}
		);

		$( '.wbte_sc_bogo_edit_filter_btn[data-row]' ).each( function () {

			const dataRow = $( this ).attr( 'data-row' );
			const $visibleElement = $( '.wbte_sc_bogo_purchase_history_container_inner[data-row="'+ dataRow +'"]' ).hasClass( 'wbte_sc_bogo_conditional_hidden' );
		
			if ( ! $visibleElement ) { 
				$( this ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			}
		} );
		
		if( 0 === $( '.wbte_sc_bogo_edit_filter_btn:not(.wbte_sc_bogo_conditional_hidden)' ).length ){
			$( '.wbte_sc_bogo_edit_filter_span_setion p:first' ).hide();
		}

		/**  Hide fields row on hide button click, also unhide filter button */
		$( '.wbte_sc_bogo_edit_hide_row_btn img' ).on( 'click', 
			function () {
				const closestDiv = $( this ).closest( '.wbte_sc_bogo_purchase_history_container_inner' );
				const dataRow = closestDiv.attr( 'data-row' );
				closestDiv.addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_edit_filter_btn[data-row="' + dataRow + '"]' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );

				if( 0 !== $( '.wbte_sc_bogo_edit_filter_btn:not(.wbte_sc_bogo_conditional_hidden)' ).length ){
					$( '.wbte_sc_bogo_edit_filter_span_setion p:first' ).show();
				}
			}
		);

		/** Show the hidden row on filter button click */
		$( '.wbte_sc_bogo_edit_filter_btn' ).on( 'click', 
			function () {
				const dataRow = $( this ).attr( 'data-row' );
				$( '.wbte_sc_bogo_purchase_history_container_inner[data-row="' + dataRow + '"]' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( this ).addClass( 'wbte_sc_bogo_conditional_hidden' );

				if( 0 === $( '.wbte_sc_bogo_edit_filter_btn:not(.wbte_sc_bogo_conditional_hidden)' ).length ){
					$( '.wbte_sc_bogo_edit_filter_span_setion p:first' ).hide();
				}
			}
		);

	}

	/** Function call is done by dynamically, dont remove it. Return false to show error message. */
	function wbte_sc_bogo_pch_date_range_validation(){
		if (
			'date' === $( 'input[name="_nth_coupon_order_date_or_days"]:checked' ).val()
			&& '' === $( '#_wt_sc_nth_order_date_from' ).val()
			&& '' === $( '#_wt_sc_nth_order_date_to' ).val()
		) {
			return false;
		} else {
			return true;
		}
	}

} )( jQuery );

/**
 * 	Email search multi select box
 *
 * 	@since 3.0.0
 */
var wbte_sc_bogo_email_select =
{
	doingSelectAll:false,
	Set:function () {
		this.regMultiSelect();
		this.regPaste();
		this.regKeyPress();
		this.regBtnRemove();
		this.regEditable(); /* editable on double click */
		this.regBlur();
	},
	regMultiSelect:function () {
		jQuery( '.wbte_sc_bogo_email_search' ).each(
			function () {

				/**
				 * 	 Prepare the HTML
				 */
				let parent_elm = jQuery( this ).addClass( 'wbte_sc_bogo_email_select_input_sele' ).wrap( '<div class="wbte_sc_bogo_email_select"></div>' ).parent( '.wbte_sc_bogo_email_select' );
				parent_elm.append( '<div class="wbte_sc_bogo_email_select_inner"></div><input type="text" class="wbte_sc_bogo_email_select_input_txt" id="customer_email">' );

				/**
				 *  Load the values
				 */
				let emails = jQuery( this ).val();

				if (emails.length) { /* default value exists */
					let input_txt_elm = parent_elm.find( '.wbte_sc_bogo_email_select_input_txt' );
					input_txt_elm.val( emails.join( ',' ) );
					jQuery( this ).html( '' ); /* clear it, otherwise below function will not add new values */
					wbte_sc_bogo_email_select.prepareEmailBlocks( input_txt_elm );
				} else {
					/* show the placeholder, if exists */
					let placeholder = jQuery( this ).attr( 'data-placeholder' );

					if (undefined !== typeof placeholder) {
						parent_elm.find( '.wbte_sc_bogo_email_select_input_txt' ).attr( 'placeholder', placeholder );
					}
				}

			}
		);
	},
	regBtnRemove:function () {
		jQuery( document ).on(
			'click',
			'.wbte_sc_bogo_email_select_inner span b',
			function () {
				setTimeout(
					() => {
                    let elm      = jQuery( this );
                    let elm_span = elm.parent( 'span' );
                    elm.remove();
                    let txt = elm_span.text().trim();
                    wbte_sc_bogo_email_select.removeVal( elm_span, txt );
                    elm_span.remove();
					},
					10
				);
			}
		);
	},
	regEditable:function () {
		jQuery( document ).on(
			'dblclick',
			'.wbte_sc_bogo_email_select_inner span',
			function (e) {

				e.stopPropagation();
				wbte_sc_bogo_email_select.makeEditable( jQuery( this ) );

			}
		);
	},
	regPaste:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'input',
			function (e) {

				if ('insertFromPaste' === e.originalEvent.inputType) {
					wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ) );
				}
				const value    = jQuery( this ).val().trim();
				const parentTr = jQuery( this ).closest( 'tr' );
				if ( value && wbte_sc_bogo_email_select.validateEmail( value ) ) {
					parentTr.find( '.wbte_sc_bogo_edit_error_txt_container' ).remove();
				}
			}
		);
	},
	regBlur:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'blur',
			function (e) {
				wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ), true );
			}
		);
	},
	regKeyPress:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'focus click',
			function (e) {
				wbte_sc_bogo_email_select.removeFocus( jQuery( this ) );
			}
		);

		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'keyup',
			function (e) {

				if (' ' === e.key || ',' === e.key || 'Enter' === e.key) {
					wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ) );

				} else if (('Backspace' === e.key || 'Delete' === e.key) && "" === jQuery( this ).val().trim()) {
					if (wbte_sc_bogo_email_select.isAllSelected( jQuery( this ) )) {
						jQuery( this ).parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span b' ).trigger( 'click' );
					} else {
						if ('Backspace' === e.key) { /* only for backspace */
							let span_elm = jQuery( this ).parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' );

							if (span_elm.length) {
								if (span_elm.last().hasClass( 'focused' )) {
									wbte_sc_bogo_email_select.makeEditable( span_elm.last() );
									wbte_sc_bogo_email_select.removeFocus( jQuery( this ) ); /* maybe in select all state */
								} else {
									span_elm.last().addClass( 'focused' ).trigger( 'focus' );
								}
							}
						}
					}

				} else {
					if ( ! wbte_sc_bogo_email_select.doingSelectAll) {
						wbte_sc_bogo_email_select.removeFocus( jQuery( this ) );
					}
				}

			}
		);

		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'keydown',
			function (e) {

				if ('Enter' === e.key) {
					return false;
				}

				if ("" === jQuery( this ).val().trim() && (e.ctrlKey || e.metaKey) && 'a' === e.key.toLowerCase()) {
					wbte_sc_bogo_email_select.doingSelectAll = true;
					wbte_sc_bogo_email_select.addFocus( jQuery( this ) );

				} else {
					wbte_sc_bogo_email_select.doingSelectAll = false;
				}
			}
		);
	},
	addFocus:function (elm) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' ).addClass( 'focused' );
	},
	removeFocus:function (elm) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' ).removeClass( 'focused' );
	},
	isAllSelected:function (elm) {
		let span_elm = elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' );
		return span_elm.length > 1 && ! span_elm.not( '.focused' ).length; /* first condition is greater than 1 because to avoid if there is only one single item */
	},
	makeEditable:function (elm) {
		/**
		 * 	Take the email address
		 */
		let temp_elm = jQuery( '<div>' ).html( elm.html() );
		temp_elm.find( 'b' ).remove();
		let email = temp_elm.text().trim();

		/**
		 * Add the email as input text value
		 */
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_txt' ).val( email );

		elm.find( 'b' ).trigger( 'click' ); /* remove the email block */

	},
	getExistingVal:function (elm) {
		return elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele' ).val();
	},
	setVal:function (elm, vals) {
		let sele_option_html = '';

		jQuery.each(
			vals,
			function (index, email) {
				sele_option_html += '<option value="' + email + '" selected="selected">' + email + '</option>';
			}
		);

		elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele' ).html( sele_option_html );

	},
	removeVal:function (elm, val) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele option[value="' + val + '"]' ).remove();
	},
	prepareEmailBlocks:function (elm, valid_only) {
		let txt = elm.val().trim();

		if ("" === txt) {
			return;
		}

		let emails             = txt.split( /[\s,]+/ );
		let email_block_elm    = elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner' );
		let email_html         = email_block_elm.html();
		let existing_val       = wbte_sc_bogo_email_select.getExistingVal( elm );
		let valid_emails_found = false; /* applicable only when `valid only` enabled */

		if (email_block_elm.find( 'span.focused' ).length) {
			email_block_elm.find( 'span.focused' ).removeClass( 'focused' );
		}

		jQuery.each(
			emails,
			function (index, email) {

				if ("" !== email.trim() && -1 === jQuery.inArray( email, existing_val )) {
					if ( ! valid_only) {
						let class_attr = ! wbte_sc_bogo_email_select.validateEmail( email ) ? ' class="invalid"' : '';
						email_html    += '<span' + class_attr + '>' + email + ' <b>x</b></span>';
						existing_val.push( email );
					} else {
						if ( wbte_sc_bogo_email_select.validateEmail( email ) ) { /* valid only */
							email_html += '<span>' + email + ' <b>x</b></span>';
							existing_val.push( email );
							valid_emails_found = true;
						}
					}
				}

			}
		);

		email_block_elm.html( email_html );

		if ( ! valid_only || valid_emails_found) {
			elm.val( '' );
		}

		this.setVal( elm, existing_val );
	},
	validateEmail:function (email) {
		var mailformat = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
		return email.toLowerCase().match( mailformat );
	}
}