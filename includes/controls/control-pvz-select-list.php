<?php
/**
 * PECOM PVZ select list
 *
 * @package PECOM/Controls/PVZ
 * @since   1.0.0
 */


$shippingId = WC()->session->get( 'chosen_shipping_methods' )[0];
$pecomId = WC()->session->get( 'pecom_id' );
?>

<div class="pecom_widget_block" <? if ($shippingId != $pecomId) echo 'style="display: none"'?>>
    <p style="margin-left: 25px">
        <span id="pecom-term"><?php WC()->session->get('pecom_shipping_term'); ?></span>
        <a href="javascript:void(0)" id="pecom-map-trigger">Выбрать пункт выдачи на карте</a>
    </p>
</div>




<!--<div class="order_data_column" style="width: 32%;">-->
<!--    <p id="pec-delivery__api-error" style="display: none;color: red;"></p>-->
<!--    <p class="form-field form-field-wide">-->
<!--        <label for="pec-delivery__pec-id">'.__("Индекс ПЭК: ", 'pecom-for-woocommerce').':</label>-->
<!--        <input id="pec-delivery__pec-id" type="text" value="' . $pecId . '" disabled>-->
<!--    </p>-->
<!---->
<!--    <p class="form-field form-field-wide wc-order-status">-->
<!--        <label for="pec-delivery__pec_pickup_date">'.__(" Дата забора: ", 'pecom-for-woocommerce').'</label>-->
<!--        <input id="pec-delivery__pec_pickup_date" type="date" min="' . date('Y-m-d', strtotime("+1 day")) . '" value="' . $pickUpDate . '" ' . ($pecId ? 'disabled' : '') . '>-->
<!--    </p>-->
<!---->
<!--    <p class="form-field form-field-wide wc-order-status">-->
<!--        <label for="pec-delivery__pec_pickup_date">'.__("Количество мест: ", 'pecom-for-woocommerce').'</label>-->
<!--        <input id="pec-delivery__pec-count-positions" type="number" value="' . $countPositions . '" ' . ($pecId ? 'disabled' : '') . '>-->
<!--    </p>-->
<!--    <p class="form-field form-field-wide wc-order-status">-->
<!--        <label for="pec-delivery__transport-type">'.__("Способ доставки: ", 'pecom-for-woocommerce').'</label>-->
<!--        <select id="pec-delivery__transport-type">-->
<!--            <option value="auto" ' . ($transportType == 'auto' ? 'selected' : '') . '>'.__("Авто", 'pecom-for-woocommerce').' </option>-->
<!--            <option value="avia" ' . ($transportType == 'avia' ? 'selected' : '') . '>'.__("Авиа", 'pecom-for-woocommerce').' </option>-->
<!--            <option value="easyway" ' . ($transportType == 'easyway' ? 'selected' : '') . '>'.__("Изи-Уей", 'pecom-for-woocommerce').' </option>-->
<!--        </select>-->
<!--    </p>-->
<!---->
<!--    <p class="form-field form-field-wide wc-order-status">-->
<!--        <label for="pec-delivery__pec_pickup_date">'.__("Статус: ", 'pecom-for-woocommerce').'</label>-->
<!--        <input id="pec-delivery__pec-status" type="text" title="' . $status['name'] . '" value="' . $status['name'] . '" disabled style="width:'.strlen($status['name'])*7 .'px; min-width: 150px">-->
<!--    </p>-->
<!--</div>-->
