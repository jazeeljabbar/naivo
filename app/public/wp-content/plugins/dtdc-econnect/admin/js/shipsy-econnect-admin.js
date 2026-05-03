jQuery(function () {

    function disableFieldStyle(errorTextClass, selectedFieldId) {
        jQuery(errorTextClass).css({display: 'block'});
        jQuery(selectedFieldId).css("border-color", "red");
        jQuery('#softdataSubmitButton').prop('disabled', true);
        jQuery('#softdataSubmitButton').css("border-color", "grey");
        jQuery('#softdataSubmitButton').css("background-color", "grey");
    }

    function enableFieldStyle(errorTextClass, selectedFieldId) {
        jQuery(errorTextClass).css({display: "none"});
        jQuery(selectedFieldId).css("border-color", "grey");
        jQuery('#softdataSubmitButton').prop('disabled', false);
        jQuery('#softdataSubmitButton').css("border-color", '#eb5202');
        jQuery('#softdataSubmitButton').css("background-color", '#eb5202');
    }


    jQuery('#useForwardCheck').on('change', function () {
        if (this.checked) {
            jQuery('#reverse-address').find("*").not(this).prop('disabled', true);
        } else {
            jQuery('#reverse-address').find("*").prop('disabled', false);
        }
    });

    jQuery('#shipsy-enable-auto-sync').on('change', function() {
        if(this.checked) {
            jQuery('#select-shipsy-auto-sync-service-type').prop('disabled', false);
        } else {
            jQuery('#select-shipsy-auto-sync-service-type').prop('disabled', true);
        }
    })
});

function onNumPieceChangeHandler(order_id) {
    let form = document.getElementById('sync-form-' + order_id);
    let num_pieces = jQuery('input[id=num-pieces]', form);

    if (jQuery(num_pieces).val() == 0) {
        jQuery('div[class=numpiecesError]', form).css('display', 'block');
    } else {
        jQuery('div[class=numpiecesError]', form).css('display', 'none');
    }
    let pieceDet = jQuery("div[id=piece-det]", form);
    let pieceDetDiv = jQuery(pieceDet).children();
    let checklength = pieceDetDiv.length;
    let pieceDetail1 = jQuery("div[id=piece-detail-1]", form);
    let diff = parseInt(jQuery(num_pieces).val()) - checklength;
    let multicheckval = jQuery("input[id=multiPieceCheck]", form).prop('checked');

    if (parseInt(jQuery(num_pieces).val()) > 0 && !multicheckval) {
        if (diff > 0) {
            for (let curr = checklength + 1, i = 0; i < diff; i++, curr++) {
                let newPiece = pieceDetail1.clone().attr('id', 'piece-detail-' + curr).appendTo(pieceDet);

                // console.log(jQuery("div[id=piece-det]", form));
                newPiece.find('input:text').val('');
                newPiece.find('input:text').attr('id', 'description' + curr);
                newPiece.find("input[name^='weight']").val('0');
                newPiece.find("input[name^='length']").val('1');
                newPiece.find("input[name^='width']").val('1');
                newPiece.find("input[name^='height']").val('1');
                newPiece.find("input[name^='declared-value']").val('0');
                newPiece.find(".description1-error").attr('class', 'description' + curr + '-error');
                newPiece.find(".declared-value1").attr('class', 'declared-value' + curr + '-error');
            }
        } else {
            var rem = pieceDetDiv.length;
            for (var i = 0; i < Math.abs(diff); i++) {
                jQuery('#piece-detail-' + rem).remove();
                rem--;
            }
        }
    }
    // console.log(jQuery(pieceDet).children().length);
}

async function changeAddresses(order_id, consignment_type, origin, destination) {
    const allAddresses = await getAllAddresses();
    const forwardAddress = allAddresses['forwardAddress'];
    const reverseAddress = allAddresses['reverseAddress'];
    const exceptionalReturnAddress = allAddresses['exceptionalReturnAddress'];
    const validServiceTypes = allAddresses['serviceTypes'];
    const shippingAddress = await getShippingAddress(order_id);

    const selectedValue = consignment_type.val();
    if (selectedValue === 'reverse') {
        /*
        For reverse consignment type
        Origin details - Shipping Address
        Destination details - Reverse Address (getting value for Shipsy)
        */
        origin.find("#origin-name", origin).val(shippingAddress['name']);
        origin.find("#origin-number", origin).val(shippingAddress['phone']);
        origin.find("#origin-alt-number", origin).val(shippingAddress['phone']);
        origin.find("#origin-line-1", origin).val(shippingAddress['address_1']);
        origin.find("#origin-line-2", origin).val(shippingAddress['address_2']);
        origin.find("#origin-city", origin).val(shippingAddress['city']);
        origin.find("#origin-state", origin).val(shippingAddress['state']);
        origin.find("#origin-country", origin).val(shippingAddress['country']);
        origin.find("#origin-pincode", origin).val(shippingAddress['pincode']);

        destination.find("#destination-name", destination).val(reverseAddress['name']);
        destination.find("#destination-number", destination).val(reverseAddress['phone']);
        destination.find("#destination-alt-number", destination).val(reverseAddress['phone']);
        destination.find("#destination-line-1", destination).val(reverseAddress['address_line_1']);
        destination.find("#destination-line-2", destination).val(reverseAddress['address_line_2']);
        destination.find("#destination-city", destination).val(reverseAddress['city']);
        destination.find("#destination-state", destination).val(reverseAddress['state']);
        destination.find("#destination-country", destination).val(reverseAddress['country']);
        destination.find("#destination-pincode", destination).val(reverseAddress['pincode']);
    } else if (selectedValue === 'forward') {
        /*
        For forward consignment type
        Origin details - Forward Address (getting value for Shipsy)
        Destination details - Shipping Address
        */
        origin.find("#origin-name", origin).val(forwardAddress['name']);
        origin.find("#origin-number", origin).val(forwardAddress['phone']);
        origin.find("#origin-alt-number", origin).val(forwardAddress['phone']);
        origin.find("#origin-line-1", origin).val(forwardAddress['address_line_1']);
        origin.find("#origin-line-2", origin).val(forwardAddress['address_line_2']);
        origin.find("#origin-city", origin).val(forwardAddress['city']);
        origin.find("#origin-state", origin).val(forwardAddress['state']);
        origin.find("#origin-country", origin).val(forwardAddress['country']);
        origin.find("#origin-pincode", origin).val(forwardAddress['pincode']);

        destination.find("#destination-name", destination).val(shippingAddress['name']);
        destination.find("#destination-number", destination).val(shippingAddress['phone']);
        destination.find("#destination-alt-number", destination).val(shippingAddress['phone']);
        destination.find("#destination-line-1", destination).val(shippingAddress['address_1']);
        destination.find("#destination-line-2", destination).val(shippingAddress['address_2']);
        destination.find("#destination-city", destination).val(shippingAddress['city']);
        destination.find("#destination-state", destination).val(shippingAddress['state']);
        destination.find("#destination-country", destination).val(shippingAddress['country']);
        destination.find("#destination-pincode", destination).val(shippingAddress['pincode']);
    }
}

function onConsignmentTypeChangeHandler(order_id) {
    let form = document.getElementById('sync-form-' + order_id);
    let origin = jQuery('#origin-details', form);
    let destination = jQuery('#destination-details', form);
    let consignment_type = jQuery('#select-consignment-type', form);
    changeAddresses(order_id, consignment_type, origin, destination);
}

function onMultiPieceCheckChangeHandler(order_id) {
    let form = document.getElementById('sync-form-' + order_id);
    let multiPieceCheck = jQuery('#multiPieceCheck', form);

    if (multiPieceCheck.is(':checked')) {
        // let divlength = jQuery("div[id=piece-det]", form).children().length;
        let divlength = jQuery("#piece-det > div", form).children().length;
        if (divlength - 1 > 0) {
            let flag = divlength;
            for (let i = 0; i < divlength - 1; i++) {
                jQuery('#piece-detail-' + flag, form).remove();
                flag--;
            }
        }
    } else {
        const numpieceval = jQuery('#num-pieces', form).val();
        let pieceDet1 = jQuery('#piece-detail-1', form);
        if (numpieceval > 0) {
            let newCount = 2;
            for (var i = 0; i < numpieceval - 1; i++) {
                pieceDet1.clone().attr('id', 'piece-detail-' + newCount).appendTo(jQuery("#piece-det", form));
                let currPieceDet = jQuery('#piece-detail-' + newCount, form);
                currPieceDet.find('input:text').val('');
                currPieceDet.find('input:text').attr('id', 'description' + newCount);
                currPieceDet.find("input[name^='weight']").val('0');
                currPieceDet.find("input[name^='length']").val('1');
                currPieceDet.find("input[name^='width']").val('1');
                currPieceDet.find("input[name^='height']").val('1');
                currPieceDet.find("input[name^='declared-value']").val('0');
                currPieceDet.find(".description1-error").attr('class', 'description' + newCount + '-error');
                currPieceDet.find(".declared-value1").attr('class', 'declared-value' + newCount + order_id + '-error');
                newCount++;
            }
        }
    }
}

async function getShippingLabel(ref_no, shop_url, id) {
    const referenceNumber = ref_no;
    const cookieValue = Object.fromEntries(document.cookie.split('; ').map(c => {
        const [key, ...v] = c.split('=');
        return [key, v.join('=')];
    }));

    const base_url = await getEndpoint('SHIPPING_LABEL_API');
    const url = base_url + '/link?reference_number=' + referenceNumber;
    let response = await fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'organisation-id': cookieValue['org_id'],
            'shop-url': shop_url,
            'shop-origin': 'wordpress',
            'customer-id': cookieValue['cust_id'],
            'access-token': cookieValue['access_token']
        },
    });
    let data = await response.json();
    if ('data' in data) {
        const requiredData = data.data;
        document.getElementById(id).innerHTML = "Download";
        // document.getElementById(id).className = "woocommerce-button button blue";
        document.getElementById(id).onclick = function () {
            window.open(requiredData.url, '_blank');
        };

    } else {
        alert("Error occurred while generating label: " + data.error.message);
    }
}

async function cancelOrderOnClick(ref_no, shop_url, id) {
    const referenceNumberList = [ref_no];
    const cookieValue = Object.fromEntries(document.cookie.split('; ').map(c => {
        const [key, ...v] = c.split('=');
        return [key, v.join('=')];
    }));

    const url = await getEndpoint('CANCEL_CONSIGNMENT_API');
    if (confirm("Are you sure you want to cancel the consignment?") == true) {
        let response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'organisation-id': cookieValue['org_id'],
                'shop-url': shop_url,
                'shop-origin': 'wordpress',
                'customer-id': cookieValue['cust_id'],
                'access-token': cookieValue['access_token']
            },
            body: JSON.stringify({'referenceNumberList': referenceNumberList})
        });
        let data = await response.json();
        if (data.success) {
            document.getElementById(id).innerHTML = "Cancelled";
            document.getElementById(id).disabled = true;
            document.getElementById(id.split("_")[0]).style.visibility = "hidden";

        } else {
            alert(data.failures[0].message);
        }
    }
}

function check(input) {
    console.log("Got here buddy!");
    if (input.value === 0) {
        input.setCustomValidity('The number must be greater than zero.');
    } else {
        input.setCustomValidity('');
    }
}


/***************** Button / Submit Handlers **************************/
async function onBulkSyncOrderHandler(order_ids) {
    order_ids = order_ids.split(',');
    console.log("Syncing " + order_ids.length + " orders");
    let consignments = []

    let areFormsValid = true;
    let firstInvalid;
    order_ids.map((order_id) => {
        let form = jQuery('#sync-form-' + order_id.trim());
        let firstInvalidFromForm = formValidator(form);
        if(!firstInvalid) firstInvalid = firstInvalidFromForm;

        areFormsValid &= !firstInvalidFromForm;
    });

    if (!areFormsValid) {
        jQuery(firstInvalid).focus();
        return;
    }

    order_ids.map((order_id) => {
        let form = document.getElementById('sync-form-' + order_id.trim());

        let formData = new FormData(form);

        let consignment = {};
        formData.forEach((value, key) => {
            let isArray = false;
            if (key.slice(key.length - 2) === '[]') {
                isArray = true;
                key = key.slice(0, key.length - 2);
            }

            // Reflect.has in favor of: object.hasOwnProperty(key)
            if (!isArray && !Reflect.has(consignment, key)) {
                consignment[key] = value;
                return;
            }
            if (isArray && !Array.isArray(consignment[key])) {
                consignment[key] = [];
            }
            consignment[key].push(value);
        });
        consignments.push(consignment);
    });

    // console.log(JSON.stringify(consignments));

    jQuery('#sync-form-overlay').css('display', 'block');
    jQuery('#sync-form-spanner').css('display', 'block');

    let response = await postConsignments(consignments);

    jQuery('#sync-form-overlay').css('display', 'none');
    jQuery('#sync-form-spanner').css('display', 'none');
    // document.write(response);
    // location.reload();
    // response = response.json();

    // alert(response.redirect);
    if (response.success) {
        // let res = await postSyncResult(response.data);
        // console.log(res);
        // document.write(res);
        let redirect = {
            url: response.redirect,
            method: 'POST',
            data: response.data
        }
        url_redirect(redirect)
    } else {
        window.location.href = response.redirect;
    }
    // return response;
}

function setupSubmitValidationInterceptor(form) {
    const invalidField = formValidator(form);
    if(invalidField) {
        invalidField.focus();
    }
    return !invalidField;
}
/*******************************************************************/

/************************* Validators ******************************/
function formElementValidator(element, validationFn) {
    /*
     TODO[#1]:
      - Generally, a div with name *-error is followed by input, but this is not the case
        in some places. Is there some better way to extract or check if the div following this
        input element is an error div.
      - Same goes for `invalidLabel`. Is there some better way to extract this too.
      Note: getting element by id is hectic, so we are using jQuery for this task and doing it this way.
    */
    let errorDiv = jQuery(element).next();
    let invalidLabel = jQuery(element).prev();
    let invalidLabelText = invalidLabel.text();

    if(jQuery(element).prop('required') && jQuery(element).val().length === 0) {
        // TODO: this is the check to see if the div is error div or not. Any improvements?
        if (errorDiv && errorDiv.prop('class') && errorDiv.prop('class').includes('error')) {
            jQuery(errorDiv).css('display', 'block');
            jQuery(errorDiv).text(invalidLabelText.replace(/[^a-zA-Z ]/g, "") + " is required!");
        } else {
            jQuery(element).addClass('error');
        }
        return false;
    }
    else if(jQuery(element).val().length > 0 && !validationFn(jQuery(element).val())) {
        if(errorDiv && errorDiv.prop('class') && errorDiv.prop('class').includes('error')) {
            jQuery(errorDiv).css('display', 'block');
            jQuery(errorDiv).text("Invalid value for " + invalidLabelText.replace(/[^a-zA-Z ]/g, ""));
        }
        else {
            jQuery(element).addClass('error');
        }
        return false;
    }
    _resetInvalidText(element);
    return true;
}

function formValidator(form) {
    let firstInvalid = null;
    jQuery('input', form).each(function () {
        if (!this.getAttribute('readonly')) {
            // console.log("validation form input...");
            // console.log(jQuery(this).prop('id'));
            if(jQuery(this).prop('type') === 'tel' &&
                (!jQuery(this).prop('id').includes('destination') ||
                    localized_data['consignment-config']['domestic-shipping'])) {
                const isInvalid = !formElementValidator(this, (value) => {
                    return checkPhoneNumber(jQuery(this).prop('id'), value, form)
                });
                if(isInvalid && !firstInvalid) {
                    firstInvalid = this;
                }
            }
                // else if(jQuery(this).prop('id').includes('country')) {
                //     const isInvalid = !formElementValidator(this, checkCountry);
                //     if(isInvalid && !firstInvalid) {
                //         firstInvalid = this;
                //     }
            // }
            else if (!this.validity.valid) {
                // TODO: Check if using `jQuery(this).next()` to get errorDiv can produce any error
                // let errorDivName = '.' + this.getAttribute('id') + '-error';
                // let errorDiv = jQuery(errorDivName, form);
                formElementValidator(this, (value) => {
                    return value.length > 0;
                });

                if(!firstInvalid) {
                    firstInvalid = this;
                }
            } else {
                _resetInvalidText(this);
            }
        }
    });

    jQuery('select', form).each(function () {
        if (!this.validity.valid) {
            jQuery(this).addClass('error');
            jQuery(this).focus();
        } else if (jQuery(this).hasClass('error')) {
            jQuery(this).removeClass('error');
        }
    });

    return firstInvalid;
}
/*******************************************************************/


/*************************** Helpers *******************************/
function _resetInvalidText(element) {
    //TODO[#2]: Same as TODO[#1]
    let errorDiv = jQuery(element).next();
    if(errorDiv && errorDiv.prop('class') && errorDiv.prop('class').includes('error')) {
        jQuery(errorDiv).css('display', 'none');
    }
    else {
        jQuery(element).removeClass('error');
    }
}

function getCountryCode(country) {
    const countries = localized_data['countries-json'];
    if(country.length === 2) {
        const res = countries.find((item) => item.code.toLowerCase() === country.toLowerCase());
        return res && res.code;
    }
    const res = countries.find((item) => item.name.toLowerCase() === country.toLowerCase());
    return res && res.code;
}

function checkCountry(value) {
    return getCountryCode(localized_data['consignment-config']['origin-country']) ? true : false;
}

function checkPhoneNumber(id, value, form) {
    return true;
    // // console.log(id, value);
    // const requiredCountryIdList = id.split('-');
    // // console.log(requiredCountryIdList);
    // let requiredCountry = localized_data['consignment-config']['origin-country'];
    // // console.log(requiredCountryIdList[requiredCountryIdList.length-2]);
    // // if (requiredCountryIdList[requiredCountryIdList.length - 2] === 'alt') {
    // //     requiredCountry = id.split('-', id.split('-').length - 2).join('-') + '-country';
    // // } else {
    // //     requiredCountry = id.split('-', id.split('-').length - 1).join('-') + '-country';
    // // }

    // // console.log(requiredCountry);
    // const type = jQuery('#' + requiredCountry, form);
    // const countryCode = getCountryCode(localized_data['consignment-config']['origin-country']);
    // if(!countryCode) return false;
    // // console.log(countryCode);
    // const phoneNumber = libphonenumber.parsePhoneNumber(value, countryCode);
    // // console.log(phoneNumber);
    // // console.log(phoneNumber.isValid());
    // return phoneNumber.country === countryCode && phoneNumber.isValid();
}

function url_redirect(options) {
    let $form = jQuery("<form />");

    $form.attr("action", options.url);
    $form.attr("method", options.method);
    $form.attr("enctype", "multipart/form-data");

    for (const key in options.data) {
        let values = options.data[key];

        for (const value in values) {
            let result = values[value];
            $form.append('<input type="hidden" name="' + key + '[]' + '" value="' + result.orderId + ',' + result.message + '" />');
        }
    }

    jQuery("#wpbody").append($form);
    $form.submit();
}
/*******************************************************************/


/*********************** Request Handlers **************************/
// Function to make an ajax requests
async function getEndpoint(api) {
    let request_url;

    let params = {
        action: 'shipsy_get_endpoint_url',
        api: api,
    };
    let response = await internalAjaxRequest(params);

    if (response.data && response.data.success) {
        request_url = response.data.url;
    } else {
        alert(response.data.message)
    }
    return request_url;
}

async function getAllAddresses() {
    let addresses;

    let params = {
        action: 'shipsy_get_all_addresses'
    };
    let response = await internalAjaxRequest(params);

    if (response.data && response.data.success) {
        addresses = response.data.addresses;
    } else {
        alert(response.data.message)
    }
    return addresses;
}

async function getShippingAddress(order_id) {
    let shippingAddress;

    let params = {
        action: 'shipsy_get_shipping_address',
        order_id: order_id
    };
    let response = await internalAjaxRequest(params);

    if (response.data && response.data.success) {
        shippingAddress = response.data['shipping_address'];
    } else {
        alert(response.data.message)
    }
    return shippingAddress;
}

async function postConsignments(consignments) {
    let params = {
        action: 'on_sync_submit',
        consignments: consignments
    };
    return await internalAjaxRequest(params, 'POST');
}

function internalAjaxRequest(params, method = 'GET') {
    let helper_url = localized_data.ajaxurl;

    return jQuery.ajax({
        method: method,
        url: helper_url,
        data: params,
    });
}