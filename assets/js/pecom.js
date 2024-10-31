jQuery(function ($) {
    console.log(333333333)
    if ( typeof wc_checkout_params === 'undefined' ) {
        return false;
    }

    let shipping_methods, shipping_id;
    $( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
        shipping_methods = $( this ).val();
        shipping_id = $( this ).attr('id');
    });

    if (shipping_methods == widget_data['id']) {
        content(widget_data);
    }

    $( document.body ).on( 'click', '.shipping_method', function(){
        if (this.value == widget_data['id']) {
            content(widget_data);
        } else {
            $('#billing_postcode').attr('readonly', false);
            $('#billing_state').attr('readonly', false);
            $('#billing_city').attr('readonly', false);
            $('#billing_address_1').attr('readonly', false);
            $('#billing_address_2').attr('readonly', false);
        }
    });

    function content(data) {
        let iframe = `<iframe id="pecom-widget-content" src="` + data['url'] + `?address-from=` + data['from_address'] + `&intake=` + data['intake'] + `&address-to=` + data['address'] + `&delivery=0&weight=` + data['weight'] + `&volume=` + data['volume'] + `&declared-amount=` + data['price'] + `&packing-rigid=` + data['needPackingRigid'] + `&transportation-type=` + data['transportation_type'] + `&auto-run=1&hide-price=1&hide-terms=1" width="100%" height="552" frameborder="0"></iframe>`;
        let modal = `
            <div id="pecom-widget" style="display: none;">
                <div id="pecom-widget-map" style="">
                    ${iframe}
                    <div id="pecom-widget-map-backdrop"></div>
                    <input type="hidden" id="pec_price" name="pec_price" value="">
                    <input type="hidden" id="pec_days" name="pec_days" value="">
                    <input type="hidden" id="pec_to_address" name="pec_to_address" value="">
                    <input type="hidden" id="pec_widget_data" name="pec_widget_data" value="">
                    <input type="hidden" id="pec_to_uid" name="pec_to_uid" value="">
                    <input type="hidden" id="pec_to_type" name="pec_to_type" value="">
                    <input type="hidden" id="pec_transport_type" name="pec_transport_type" value="">
                    <input type="hidden" id="pec_address_selected" name="pec_address_selected" value="">
                </div>
            </div>
        `;

        $('body .checkout').append(modal)
        $('body').on('click', '#pecom-map-trigger', function () {
            if ($("div").is("#pecom-widget")) {
                $('#pecom-widget').css('display', 'flex')
            } else {
                $('body .checkout').append(modal)
            }
        })

        $('body').on('click', '#pecom-widget-map-backdrop', function () {
            $('#pecom-widget').css('display', 'none')
        })

        window.addEventListener('message', (event) => {
            if (!event.data.hasOwnProperty('pecDelivery')) {
                return;
            }
            if (event.data.pecDelivery.hasOwnProperty('result')) {
                let country, province, address, city, street, house, zip, toAddress;
                let data = event.data.pecDelivery.result;
                console.log('data', data)
                let addresses = data.toDepartmentData.Addresses[0].address;
                let addressType = data.toAddressType;
                let addressSelected = data.isFirstRequest ? 0 : 1;
                if (data.toAddressType === "department") {
                    toAddress = data.toDepartmentData.Addresses[0].address.RawAddress;
                } else
                    toAddress = data.toAddress;
                $('#pec_to_address').val(toAddress);
                $('#pec_price').val(data.price);
                $('#pec_days').val(data.term.days);
                $('#pecom-term').val('От '+data.term.days);
                $('#pec_widget_data').val(JSON.stringify(data));
                document.getElementById('pec_widget_data').value
                $('#pec_to_uid').val(data.toDepartmentUid);
                $('#pec_to_type').val(addressType);
                $('#pec_address_selected').val(addressSelected);
                $('#pec_transport_type').val(widget_data['transportation_type']);

                $.ajax({
                    type: "POST",
                    url: wc_checkout_params.ajax_url,
                    data: {
                        action: 'update_pecom_cost',
                        cost: data.price,
                        term: data.term.days,
                        addressType: addressType
                    },
                });

                address = data.toDepartmentData.Addresses[0].address.Coordinates;
                if (addressType == "address") {
                    address = data.toAddress;
                }

                function geocoder() {
                    let rescont = '';
                    let lat = address.latitude;
                    let lng = address.longitude;
                    let geocode = lng + ',' + lat;
                    if (addressType == "address")
                        geocode = address;
                    $.ajax({
                        type: "GET",
                        url: "https://geocode-maps.yandex.ru/1.x/?",
                        data: 'apikey=3750d813-139e-48ce-ae61-83100db11f73&format=json&kind=house&results=1&geocode=' + geocode,
                        dataType: "JSON", timeout: 30000, async: false,
                        error: function (xhr) {
                            rescont += 'Ошибка геокодирования: ' + xhr.status + ' ' + xhr.statusText;
                        },
                        success: function (res) {
                            rescont = res.response.GeoObjectCollection.featureMember;
                        }
                    });
                    return rescont[0].GeoObject.metaDataProperty.GeocoderMetaData.Address;

                }

                let geo = geocoder();
                let geocode = Object.values(geo.Components);
                geocode.forEach(function (elem, index) {
                    if (index === 1) return;
                    if (elem['kind'] == 'country') country = elem['name'];
                    if (elem['kind'] == 'province') province = elem['name'];
                    if (elem['kind'] == 'locality') city = elem['name'];
                    if (elem['kind'] == 'street') street = elem['name'];
                    if (elem['kind'] == 'house') house = elem['name'];
                })
                if (geo.postal_code)
                    zip = geo.postal_code;

                if (!zip) {
                    zip = addresses.ZipCode;
                    if (!zip) {
                        address = addresses.RawAddress.split(',');
                        zip = address[0];
                    }
                }
                console.log(2222222222)

                $('#billing_postcode').val(zip).attr('readonly', true);
                $('#billing_state').val(province).attr('readonly', true);
                $('#billing_city').val(city).attr('readonly', true);
                $('#billing_address_1').val(street).attr('readonly', true);
                $('#billing_address_2').val(house).attr('readonly', true);
                $('#pecom_delivery_field').val(data.price);
                $('#billing_fias_code').val(data.price);

                $('body').trigger('update_checkout');

                // let content_widget = `
                //     <div class="pecom_widget_block">
                //         <p style="margin-left: 25px">
                //             <span id="pecom-term">От ${data.term.days}</span>
                //
                //             <input type="hidden" id="pec_price" name="pec_price" value="${data.price}">
                //             <input type="hidden" id="pec_days" name="pec_days" value="${data.term.days}">
                //             <input type="hidden" id="pec_to_address" name="pec_to_address" value="${toAddress}">
                //             <input type="hidden" id="pec_widget_data" name="pec_widget_data" value="${json_data}">
                //             <input type="hidden" id="pec_to_uid" name="pec_to_uid" value="${data.toDepartmentUid}">
                //             <input type="hidden" id="pec_to_type" name="pec_to_type" value="${addressType}">
                //             <input type="hidden" id="pec_transport_type" name="pec_transport_type" value="${widget_data['transportation_type']}">
                //             <input type="hidden" id="pec_address_selected" name="pec_address_selected" value="${addressSelected}">
                //
                //             <a href="javascript:void(0)" id="pecom-map-trigger">Выбрать пункт выдачи на карте</a>
                //         </p>
                //     </div>
                // `;

                // $( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
                //     // $('.pecom_widget_block').remove();
                //     // console.log('THIS', $('.pecom_widget_block').length);
                //     // if ($('.pecom_widget_block').length == 0) {
                //     //     $('#'+shipping_id).parent().append(content_widget)
                //     // }
                // });
                $('#pecom-widget').css('display', 'none')
            }
            if (event.data.pecDelivery.hasOwnProperty('error')) {
                console.log('event.data.pecDelivery.error', event.data.pecDelivery.error);
            }
        });
    }
});
