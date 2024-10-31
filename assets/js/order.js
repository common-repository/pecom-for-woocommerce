jQuery(function ($) {
    let pecAdmin = {
        orderId: 78,
        pecIdViewMode: 'view',
        pecId: '',
        pecStatus: '',
        saveNewPecId: function (pecId) {
            pecId = pecId ? pecId : document.getElementById('pec_delivery__pec_id').value;
            let $this = this;
            document.getElementsByClassName('pec_delivery__change_pec_id')[0].disabled = true;
            document.getElementById('pec-delivery__pec_status').value = '';
            document.getElementById('pec-delivery__pec_status').setAttribute('title', '');
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'save_pec_id',
                    orderId: this.orderId,
                    pecId: pecId
                },
                success: function (data) {
                    console.log(data)
                    $this.pecId = pecId;
                    $this.pecIdViewMode = 'view';
                    document.getElementById('pec_delivery__pec_id').disabled = true;
                    document.getElementsByClassName('pec_delivery__change_pec_id')[0].value = 'Сменить код груза';
                    document.getElementsByClassName('pec_delivery__change_pec_id')[0].disabled = false;
                    $this.showFooterBtn();
                }
            });
        },
        showFooterBtn: function() {
            if (this.pecId) {
                document.getElementById('pec_delivery__send_order').style.display = 'none';
                document.getElementById('pec_delivery__pre_registration').style.display = 'none';
                document.getElementById('pec_delivery__get_status').style.display = 'inline-block';
                // document.getElementById('pec_delivery__print_tag').style.display = 'inline-block';
                document.getElementById('pec_delivery__pec_pickup_date').disabled = true;
                document.getElementById('pec_delivery__pec_count_positions').disabled = true;
                document.getElementById('pec_delivery__transport_type').disabled = true;
            } else {
                document.getElementById('pec_delivery__get_status').style.display = 'none';
                // document.getElementById('pec_delivery__print_tag').style.display = 'none';
                document.getElementById('pec_delivery__send_order').style.display = 'inline-block';
                document.getElementById('pec_delivery__pre_registration').style.display = 'inline-block';
                document.getElementById('pec_delivery__pec_pickup_date').disabled = false;
                document.getElementById('pec_delivery__pec_count_positions').disabled = false;
                document.getElementById('pec_delivery__transport_type').disabled = false;
            }
        },
        eventChangePecId: function() {
            let $this = this;

            if ($this.pecIdViewMode == 'view') {
                $this.pecIdViewMode = 'edit';
                document.getElementById('pec_delivery__pec_id').disabled = false
                document.getElementById('pec_delivery__pec_pickup_date').disabled = false
                document.getElementById('pec_delivery__pec_count_positions').disabled = false
                document.getElementById('pec_delivery__transport_type').disabled = false
                document.getElementsByClassName('pec_delivery__change_pec_id')[0].value = 'Сохранить'
            } else {
                let newPecId = document.getElementById('pec_delivery__pec_id').value;
                $this.saveNewPecId(newPecId);
            }

        },
        eventSendOrder() {
            let $this = this;

            if ($this.pecId) return;
            document.getElementById('pec_delivery__send_order').disabled = true;
            let pickupDate = document.getElementById('pec_delivery__pec_pickup_date').value;
            let positionCount = document.getElementById('pec_delivery__pec_count_positions').value;
            let transportType = document.getElementById('pec_delivery__transport_type').value;
            $this.hideError();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    orderId: $this.orderId,
                    pickupDate: pickupDate,
                    positionCount: positionCount,
                    transportType: transportType,
                    action: 'create_pecom_order'
                },
                success: function (data) {
                    document.getElementById('pec_delivery__send_order').disabled = false;
                    console.log('data', data['cargos'])
                    if (data['cargos'] && data['cargos'][0]['cargoCode']) {
                        // document.getElementsByClassName('pec_delivery__change_pec_id')[0].remove()
                        $this.pecId = data['cargos'][0]['cargoCode'];
                        $this.updateStatus();
                    }
                    // if (data.hasOwnProperty('error')) {
                    //     $this.showError(data);
                    // }
                    document.getElementById('pec_delivery__pec_id').value = $this.pecId;
                    document.getElementById('pec_delivery__pec_pickup_date').disabled = true;
                    document.getElementById('pec_delivery__pec_count_positions').disabled = true;
                    document.getElementById('pec_delivery__transport_type').disabled = true;
                    $this.showFooterBtn();
                    $this.saveNewPecId($this.pecId);
                }
            });
        },
        eventGetPecStatus() {
            let $this = this;
            document.getElementById('pec_delivery__get_status').disabled = true;
            $this.updateStatus();
        },
        // eventPrintTag() {
        //     let $this = this;
        //
        //     if (!$this.pecId) return;
        //     document.getElementById('pec_delivery__print_tag').disabled = true;
        //     $.ajax({
        //         url: ajaxurl,
        //         method: 'POST',
        //         dataType: 'json',
        //         data: {
        //             orderId: $this.orderId,
        //             method: 'getTag',
        //             pecId: $this.pecId,
        //             action: 'aaaa'
        //         },
        //         success: function (data) {
        //             document.getElementById('pec_delivery__print_tag').disabled = false;
        //
        //             if (data.error) {
        //                 alert(data.error);
        //                 return;
        //             }
        //
        //             let win = window.open('about:blank', "_new");
        //             win.document.open();
        //             win.document.write(data.html);
        //             win.document.close();
        //         }
        //     });
        // },
        eventPreRegistration() {
            let $this = this;

            if ($this.pecId) return;
            document.getElementById('pec_delivery__send_order').disabled = true;
            let positionCount = document.getElementById('pec_delivery__pec_count_positions').value;
            let transportType = document.getElementById('pec_delivery__transport_type').value;
            let pickupDate = document.getElementById('pec_delivery__pec_pickup_date').value;
            $this.hideError();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    orderId: $this.orderId,
                    pickupDate: pickupDate,
                    positionCount: positionCount,
                    transportType: transportType,
                    action: 'create_pecom_preorder'
                },
                success: function (data) {
                    document.getElementById('pec_delivery__pre_registration').disabled = false;
                    if (data['cargos'] && data['cargos'][0]['cargoCode']) {
                        // document.getElementsByClassName('pec_delivery__change_pec_id')[0].remove()
                        $this.pecId = data['cargos'][0]['cargoCode'];
                        $this.updateStatus();
                    }
                    // if (data.hasOwnProperty('error')) {
                    //     $this.showError(data);
                    // }
                    document.getElementById('pec_delivery__pec_id').value = $this.pecId;
                    document.getElementById('pec_delivery__pec_pickup_date').disabled = true;
                    document.getElementById('pec_delivery__pec_count_positions').disabled = true;
                    document.getElementById('pec_delivery__transport_type').disabled = true;
                    $this.showFooterBtn();
                    $this.saveNewPecId($this.pecId);
                }
            });
        },
        updateStatus: function() {
            if (!this.pecId) return;
            let $this = this;
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    orderId: $this.orderId,
                    pecId: $this.pecId,
                    action: 'update_pec_status'},
                success: function (data) {
                    if (data) {
                        document.getElementById('pec_delivery__get_status').disabled = false;
                        document.getElementById('pec-delivery__pec_status').value = data;
                        document.getElementById('pec-delivery__pec_status').setAttribute('title', data);
                        // $this.pecStatus = data.code;
                    }
                    if (data.code == 'error') {
                        alert(data.name);
                    }
                }
            });
        },
        hideError: function() {
            document.getElementById('pec_delivery__api_error').style.display = 'none';
        },
        showError: function(data) {
            document.getElementById('pec_delivery__api_error').style.display = 'block';
            let error = data.error.fields;
            let txt = '';
            for (i in error) {
                txt += error[i].Key + ': ' + error[i].Value[0] + '<br>';
            }
            document.getElementById('pec_delivery__api_error').innerHTML = txt;
        },
        getOrderId: function() {
            var orderId = {};
            window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                orderId[key] = value;
            });
            return orderId;
        },

        init: function () {
            let $this = this;
            $this.pecId = document.getElementById('pec_delivery__pec_id').value;
            $this.orderId = parseInt($this.getOrderId()["post"]);
            $this.showFooterBtn();
            // this.eventPrintTag();
            // this.eventPreRegistration();

            $( document.body ).on( 'click', '.pec_delivery__change_pec_id', function(){
                $this.eventChangePecId(this);
            });
            $( document.body ).on( 'click', '#pec_delivery__send_order', function(){
                $this.eventSendOrder(this);
            });
            $( document.body ).on( 'click', '#pec_delivery__pre_registration', function(){
                $this.eventSendOrder(this);
            })
            $( document.body ).on( 'click', '#pec_delivery__get_status', function(){
                $this.eventGetPecStatus(this);
            })
        }
    }

    pecAdmin.init();
})
