<?php
/**
 * PECOM pvz shipping options
 *
 * @package PECOM/PVZ
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;
/**
 * Delivery points for PECOM methods
 *
 * @class PECOM_Tools_Shipping
 */
class PECOM_Tools_Shipping {

	public static $PREREGISTRATION_TRANSPORT_TYPE = ['auto' => 3, 'avia' => 1, 'easyway' => 12]; // Тип перевозки (1 - Авиаперевозка, 3 - Автоперевозка, 12 - Изи Уэй) [Number]

    public static $first_load = false;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'add_pvz_select' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_pvz_to_order_meta' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_pvz_after_shipping_address' ) );
		add_action( 'woocommerce_email_order_meta', array( $this, 'display_pvz_in_email' ), 10, 4 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_pvz_in_order_details' ) );
		add_action( 'wp_ajax_update_pecom_cost', array( $this, 'update_pecom_cost') );
		add_action( 'wp_ajax_nopriv_update_pecom_cost', array( $this, 'update_pecom_cost') );
		add_action( 'add_meta_boxes', array( $this, 'my_extra_fields'), 25 );

		add_action( 'wp_ajax_nopriv_create_pecom_order', array( $this, 'create_pecom_order') );
		add_action( 'wp_ajax_create_pecom_order', array( $this, 'create_pecom_order') );
		add_action( 'wp_ajax_create_pecom_preorder', array( $this, 'create_pecom_preorder') );
		add_action( 'wp_ajax_nopriv_create_pecom_preorder', array( $this, 'create_pecom_preorder') );
		add_action( 'wp_ajax_save_pec_id', array( $this, 'save_pec_id') );
		add_action( 'wp_ajax_nopriv_save_pec_id', array( $this, 'save_pec_id') );
		add_action( 'wp_ajax_update_pec_status', array( $this, 'update_pec_status') );
		add_action( 'wp_ajax_nopriv_update_pec_status', array( $this, 'update_pec_status') );

		add_action( 'woocommerce_new_order', array( $this, 'order_details'),  1, 1  );
		if ( !class_exists( 'Pecom_Db' ) )
			include_once PECOM_ABSPATH . 'includes/class-pecom-db.php';
	}

	function my_extra_fields() {
		add_meta_box( 'extra_fields', 'Отправка ПЭК', array( $this, 'extra_fields_box_func'), null, 'normal', 'high'  );
	}

	function extra_fields_box_func( $post ){
		$order = wc_get_order($_REQUEST['post']);
        $order_id = (int) $_GET['post'];
		foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
			$shipping_method_id = $item->get_method_id(); // The method ID
            if ($shipping_method_id != 'pecom_shipping')
                return;
		}

		$pecId = self::get_delivery_field($order_id, 'PEC_ID') ? : '';
		$pickUpDate = self::get_pickup_date($order_id);
		$countPositions = self::get_pec_position_count($order_id);
		$status = '';
		if ($pecId) {
			$status = self::get_delivery_field($order_id, 'STATUS');
		}
		if ($pecId && !$status) {
			$status = self::get_save_pec_status($order_id, $pecId);
		}
		$transportType = self::get_delivery_field($order_id, 'TRANSPORTATION_TYPE');

		echo '
            <p id="pec_delivery__api_error" style="display: none;color: red;"></p>
            <label>'.__("Индекс ПЭК: ", 'pecom-for-woocommerce').'<input id="pec_delivery__pec_id" type="text" value="' . $pecId . '" disabled> </label>
            <label>'.__(" Дата забора: ", 'pecom-for-woocommerce').'
                <input id="pec_delivery__pec_pickup_date" type="text" min="' . date('Y-m-d', strtotime("+1 day")) . '" value="' . $pickUpDate . '" ' . ($pecId ? 'disabled' : '') . ' style="font-size: 13px;height: 25px;padding: 0 5px;margin: 0;background: #fff;border: 1px solid;border-color: #87919c #959ea9 #9ea7b1 #959ea9;border-radius: 4px;color: #000;box-shadow: 0 1px 0 0 rgba(255,255,255,0.3), inset 0 2px 2px -1px rgba(180,188,191,0.7);display: inline-block;outline: none;vertical-align: middle;-webkit-font-smoothing: antialiased;width: 125px;">
            </label>
            <label>'.__(" Количество мест: ", 'pecom-for-woocommerce').'
                <input id="pec_delivery__pec_count_positions" type="number" value="' . $countPositions . '" ' . ($pecId ? 'disabled' : '') . ' style="font-size: 13px;height: 25px;padding: 0 5px;margin: 0;background: #fff;border: 1px solid;border-color: #87919c #959ea9 #9ea7b1 #959ea9;border-radius: 4px;color: #000;box-shadow: 0 1px 0 0 rgba(255,255,255,0.3), inset 0 2px 2px -1px rgba(180,188,191,0.7);display: inline-block;outline: none;vertical-align: middle;-webkit-font-smoothing: antialiased;width: 50px;">
            </label>
            <label>'.__("Способ доставки: ", 'pecom-for-woocommerce').' 
                <select id="pec_delivery__transport_type"' . ($pecId ? 'disabled' : '') . '>
                    <option value="auto" ' . ($transportType == 'auto' ? 'selected' : '') . '>'.__("Авто", 'pecom-for-woocommerce').' </option>
                    <option value="avia" ' . ($transportType == 'avia' ? 'selected' : '') . '>'.__("Авиа", 'pecom-for-woocommerce').' </option>
                    <option value="easyway" ' . ($transportType == 'easyway' ? 'selected' : '') . '>'.__("Изи-Уей", 'pecom-for-woocommerce').' </option>
                </select>
            </label>
            <label>'.__("Статус: ", 'pecom-for-woocommerce').' <input id="pec-delivery__pec_status" type="text" title="' . $status . '" value="' . $status . '" disabled style="width:'.strlen($status)*7 .'px; min-width: 150px"> </label>
            <input class="pec_delivery__change_pec_id button" type="button" value="'.__("Сменить код груза", 'pecom-for-woocommerce').'">
            <br><br>
            <input type="hidden" value="'.$order_id.'">
            <input id="pec_delivery__get_status" class="button" type="button" style="display: none" value="'. __( 'Получить статус', 'pecom-for-woocommerce' ).'">
            <input id="pec_delivery__print_tag" class="button" type="button" style="display: none" value="'.__( 'Распечатать этикетку', 'pecom-for-woocommerce' ).'">
            <input id="pec_delivery__send_order" class="button" type="button" value="'.__( "Подать заявку", 'pecom-for-woocommerce' ).'">
            <input id="pec_delivery__pre_registration" class="button" type="button" value="'.__("Сделать предварительное оформление", 'pecom-for-woocommerce' ).'">
        ';
		wp_enqueue_script('cds_pecom_order_script', PECOM_PLUGIN_DIR_URL . 'assets/js/order.js', array('jquery' ), '1.0');

	}

	public static function get_orders($order_id): array {
		$order = wc_get_order($order_id);
		return $order->get_items();
	}

	function create_pecom_order() {
		$orderId = $_REQUEST['orderId'];
		$pickupDate = $_REQUEST['pickupDate'];
		$positionCount = $_REQUEST['positionCount'];
		$transportType = self::$PREREGISTRATION_TRANSPORT_TYPE[$_REQUEST['transportType']];

		$data = [
			"common" => [ // Общие данные [Object]
				"type"                   => $transportType,
				// Тип заявки (1 - Авиаперевозка, 3 - Забор груза, 12 - Изи Уэй) [Number]
				"applicationDate"        => $pickupDate, //"2012-02-25", Дата исполнения заявки [Date]
				"description"            => self::get_description_products(),
				// Описание груза [String]
				"weight"                 => self::get_total_order_weight($orderId),
				// Вес груза, кг [Number]
				"volume"                 => self::get_order_max_dimension($orderId)['volume'],
				// Объём груза, м3 [Number]
				"positionsCount"         => $positionCount, //self::getOrderPositionsCount(),
				// Количество мест, шт [Number]
				"width"                  => self::get_order_max_dimension($orderId)['width']/1000,
				// Ширина, м [Number]
				"length"                 => self::get_order_max_dimension($orderId)['length']/1000,
				// Длина, м [Number]
				"height"                 => self::get_order_max_dimension($orderId)['height']/1000,
				// Высота, м [Number]
				"isFragile"              => false,
				// Хрупкий груз [Boolean]
				"isGlass"                => false,
				// Стекло [Boolean]
				"isLiquid"               => false,
				// Жидкость [Boolean]
				"isOtherType"            => false,
				// Груз другого типа [Boolean]
				"isOtherTypeDescription" => null,
				// Описание груза другого типа [String],
				// поле обязательно, если "isOtherType" => true
				"isOpenCar"              => false,
				// Необходима открытая машина [Boolean]
				"isSideLoad"             => true,
				// Необходима боковая погрузка [Boolean]
				"isSpecialEquipment"     => false,
				// Необходимо специальное оборудование [Boolean],
				// поле необязательно, если не указано считается равным false
				"isUncovered"            => false,
				// Необходима растентовка [Boolean],
				// поле необязательно, если не указано считается равным false
				"isDayByDay"             => false,
				// Необходим забор день в день [Boolean]

				"whoRegisterApplication" => 1,
				// Представитель какой стороны оформляет заявки
				// (1 - отправитель, 2 - получатель, 3 - третье лицо) [Number]
				"responsiblePerson"      => get_option( 'pecom_option_sender_name' ),
				// ФИО ответственного за оформление заявки [String]
				// "typeClientBarcode"      => "CODE128",
				// Тип штрих-кодов, указанных для мест грузов заявки [String]
				// тип штрих-кода можно набирать символами любого регистра
				// "clientPositionsBarcode" => [     // Штрих-коды мест груза [Array]
				//     "123654789", // Штрих-код клиента [String]
				// ],
				"customerCorrelation"    => $orderId,
				// Произвольное значение для синхронизации на стороне клиента [String], поле необязательно
				"cargoSourceSystemGUID" => "5ff31c58-2c7f-11eb-80ce-00155d4a0436"
			],

			"services" => [ // Услуги [Object]
				"isHP"                    => self::is_self_pack(),
				// Изготовление защитной транспортировочной упаковки [Boolean]
				"isInsurance"             => self::is_insurance(),
				// Дополнительное страхование груза [Boolean]
				"isInsurancePrice"        => self::get_total_price($orderId),
				// Стоимость груза для страхования, руб [Number]
				// поле обязательно, если "isInsurance" => true
				"isSealing"               => false,
				// Пломбировка груза (только до 3 кг) [Boolean]
				"isSealingPositionsCount" => null,
				// Количество мест для пломбировки [Number]
				// поле обязательно, если "isSealing" => true
				"isStrapping"             => false,
				// Упаковка груза стреппинг‑лентой [Boolean]
				"isDocumentsReturn"       => false,
				// Возврат документов [Boolean]
				"isLoading"               => false,
				// Необходима погрузка силами «ПЭК» [Boolean]
				// "floor"                   => 8,
				// Этаж с которого необходимо забрать груз, поле необязательно [Number]
				// "isElevator"              => true,
				// Есть лифт, поле необязательно [Boolean]
				// "carryingDistance"        => 150,
				// Метров переноски груза, поле необязательно [Number]
				// "email"                   => "example@example.com",
				// Email для бухгалтерских уведомлений [String], поле необязательно
				"cashOnDelivery"          => [ // Наложенный платеж [Object]
					"enabled"                      => false,
					// Заказана услуга наложенного платежа [Boolean], поле обязательно, если заказана услуга НП [Number]
					// "cashOnDeliverySum"            => 456.26,
					// // Общая стоимость заказа (сумма НП) [Number]
					// "actualCost"                   => 789.36,
					// // Фактическая стоимость товара [Number]
					// "includeTES"                   => false,
					// // За услуги платит получатель сверх суммы НП [Boolean]
					// "isPartialDistributionAllowed" => true,
					// // Возможна частичная выдача [Boolean], поле необязательно
					// "isOpenAndInspectAllowed"      => true,
					// // Возможно вскрытие и внутритарный осмотр [Boolean], поле необязательно
					// "orderNumber"                  => "№23434-АБ",
					// // Номер заказа клиента [String], поле необязательно
					// "sellerINN"                    => "7716542310",
					// // ИНН отправителя (продавца) [String], поле необязательно
					// "sellerTitle"                  => "Наименование организации",
					// // Наименование отправителя (продавца) [String], поле необязательно
					// "sellerPhone"                  => "88-99-00",
					// // Телефон [String], поле необязательно
					// "sellerServices"               => [
					//     [ // Дополнительные услуги [Object]
					//         "type"            => 1,
					//         // Список дополнительных услуг, предоставляемых Грузотправителем [Number]
					//         // 1 - Доставка,
					//         // 2 - Курьерская доставка,
					//         // 3 - Доставка и выдача на терминале,
					//         // 4 - Доставка и выдача на ПВЗ,
					//         // 5 - Подъем на этаж,
					//         // 6 - Доставка интернет-магазина,
					//         // 7 - Погрузочно-разгрузочные работы интернет магазина
					//         "rateVAT"         => "НДС20",
					//         // Ставка НДС [String]
					//         "sumIncludingVAT" => 68403.17,
					//         // Стоимость дополнительных услуг, в т.ч. НДС, руб. [Number]
					//     ],
					// ],
					// "specification"                => [ // Частичная выдача груза [Object]
					//     "takeDeliveryZeroSum"     => false,
					// // Брать сумму «доставки» при полном отказе получателя [Boolean]
					// "amountDeliveryMandatory" => 300,
					// // Обязательная сумма доставки. Обязательно если takeDeliveryZeroSum = true [Number]
					// "specifications"          => [
					//     [ // состав спецификации
					//         "vendorCode"               => "32711600Y",
					//         // Артикул [String]
					//         "title"                    => "Гофра AlcaPlast A75",
					//         // Наименование позиции [String]
					//         "amount"                   => 1,
					//         // Количество [Number]
					//         "kit"                      => true,
					//         // Комплект [Boolean]
					//         "rateVAT"                  => "20%",
					//         // Ставка НДС [String]
					//         "actualCostPerUnit"        => 123.36,
					//         // Объявленная ценность за ед., в т.ч. НДС, руб. [Number]
					//         "sumPerUnit"               => 123.36,
					//         // К оплате с Грузополучателя за ед., в т.ч. НДС, руб. [Number]
					//         "actualCostTotal"          => 123.36,
					//         // Объявленная ценность всего, руб., в т.ч. НДС [Number]
					//         "sumTotal"                 => 123.36,
					//         // К оплате с Грузополучателя всего, руб., в т.ч. НДС, руб. [Number]
					//         "fitting"                  => true,
					//         // Примерка [Boolean]
					//         "openingIndividualPacking" => true
					//         // Вскрытие инд. упаковки [Boolean]
					//     ],
					// ],
					// ],
				],
			],

			"sender" => [ // Отправитель [Object]
				"inn"                  => get_option( 'pecom_option_inn' ), // ИНН [String], поле необязательно
				"city"                 => get_option( 'pecom_option_address' ), // Город [City]
				"title"                => get_option( 'pecom_option_phone' ), // Наименование [String]
				"person"               => get_option( 'pecom_option_sender_name' ), // Контактное лицо [String]
				"phone"                => get_option( 'pecom_option_phone' ), // Телефон [String]
				// "phoneAdditional"      => "1234", // добавочный номер (максимум 10 символов) [String]
				// "email"                => "example@example.com", // E-mail [String], поле необязательно
				"addressOffice"        => get_option( 'pecom_option_address' ), // Адрес офиса [String]
				// "addressOfficeComment" => "пятый подъезд", // Комментарий к адресу офиса [String]
				"addressStock"         => get_option( 'pecom_option_address' ), // Адрес склада [String]
				// "addressStockComment"  => "вход со второго этажа", // Комментарий к адресу склада [String]
				// "latitudeForCar"       => 55.432025, // Координаты для подачи машины [String]
				// "longitudeForCar"      => 37.545734, // Координаты для подачи машины [String]
				// "workTimeFrom"         => "09 => 00", // Время начала рабочего дня [Time], поле необязательно
				// "workTimeTo"           => "18 => 00", // Время окончания рабочего дня [Time], поле необязательно
				// "lunchBreakFrom"       => "14 => 00", // Время начала обеденного перерыва [Time], поле необязательно
				// "lunchBreakTo"         => "15 => 00", // Время окончания обеденного перерыва [Time], поле необязательно
				// "cargoDocumentNumber"  => "ЕК-419987234С", // Номер счета на оплату груза накладной
				// или другого документа на груз [String]
				"isAuthorityNeeded"    => false, // Для получения груза необходима доверенность «ПЭК»
				// (иначе, доверенность клиента) [Boolean]
				// "identityCard"         => [ // Документ удостоверяющий личность  [Object]
				//     "type"   => 10, // тип документа [Number] (1 - ПАСПОРТ ИНОСТРАННОГО ГРАЖДАНИНА,
				//     // 2 - РАЗРЕШЕННИЕ НА ВРЕМЕННОЕ ПРОЖИВАНИЕ, 3 - ВОДИТЕЛЬСКОЕ УДОСТОВЕРЕНИЕ,
				//     // 4 - ВИД НА ЖИТЕЛЬСТВО, 5 - ЗАГРАНИЧНЫЙ ПАСПОРТ, 6 - УДОСТОВЕРЕНИЕ БЕЖЕНЦА,
				//     // 7 - ВРЕМЕННОЕ УДОСТОВЕРЕНИЕ ЛИЧНОСТИ ГРАЖДАНИНА РФ,
				//     // 8 - СВИДЕТЕЛЬСТВО О ПРЕДОСТАВЛЕНИИ ВРЕМЕННОГО УБЕЖИЩА НА ТЕРРИТОРИИ РФ,
				//     // 9 - ПАСПОРТ МОРЯКА, 10 - ПАСПОРТ ГРАЖДАНИНА РФ,
				//     // 11 - СВИДЕТЕЛЬСТВО О РАССМОТРЕНИИ ХОДАТАЙСТВА О ПРИЗНАНИИ БЕЖЕНЦЕМ,
				//     // 12 - ВОЕННЫЙ  БИЛЕТ)
				//     "series" => "1234", // Серия [String]
				//     "number" => "56789", // Номер [String]
				//     "date"   => "1985-01-01", // Дата [DateTime]
				//     "note"   => "" // служебное поле для других документов [String]
				// ],
			],

			"receiver" => [ // Получатель [Object]
				// "inn"                                => "7716542310",
				// ИНН [String], поле необязательно
				"city"                               => self::get_receiver_data($orderId)['city'],
				// Город [City]
				"title"                              => self::get_receiver_data($orderId)['title'] ? : self::get_receiver_data($orderId)['person'],
				// Наименование [String]
				"person"                             => self::get_receiver_data($orderId)['person'],
				// Контактное лицо [String]
				"phone"                              => self::get_receiver_data($orderId)['phone'],
				// Телефон [String]
				// "phoneAdditional"                    => "1234",
				// добавочный номер (максимум 10 символов) [String]
				// "email"                              => "test@test.com",
				// E-mail [String], поле необязательно
				"isCityDeliveryNeeded"               => self::is_delivery_to_address($orderId),
				// Необходима доставка по городу получателя [Boolean]
				// "isLoading"                          => true,
				// Необходима разгрузка силами «ПЭК», поле необязательно [Boolean]
				// "floor"                              => 15,
				// Этаж на который необходимо занести груз, поле необязательно [Number]
				// "isElevator"                         => false,
				// Есть лифт, поле необязательно [Boolean]
				// "carryingDistance"                   => 30,
				// Метров переноски груза, поле необязательно [Number]
				// "isCityDeliveryNeededAddress"        => self::getDeliveryAddress(),
				// Адрес доставки груза [String]
				// Поле обязательно,
				// если "isCityDeliveryNeeded" => true
				// "isCityDeliveryNeededAddressComment" => "Вход со двора",
				// Комментарий к адресу доставки
				// [String], необязательное поле
				// "avisationDateTime"                  => "2013-04-02",
				// Дата авизации [DateTime], поле необязательно
				// "dateOfDelivery"                     => "2013-04-02",
				// Плановая дата доставки [DateTime], поле необязательно
				"declaredCost"                       => self::get_total_price($orderId),
				// Объявленная стоимость товара

				// "warehouseId"  => self::getDeliveryWarehouseId(),
				// Идентификатор склада [String]
//                 "identityCard" => [ // Документ удостоверяющий личность  [Object]
//                     "type"   => 10, // тип документа [Number] (
				//     // 0 - БЕЗ ПРЕДОСТАВЛЕНИЯ ДОКУМЕНТА (серию\номер оставить пустыми),
				//     // 1 - ПАСПОРТ ИНОСТРАННОГО ГРАЖДАНИНА,
				//     // 2 - РАЗРЕШЕННИЕ НА ВРЕМЕННОЕ ПРОЖИВАНИЕ, 3 - ВОДИТЕЛЬСКОЕ УДОСТОВЕРЕНИЕ,
				//     // 4 - ВИД НА ЖИТЕЛЬСТВО, 5 - ЗАГРАНИЧНЫЙ ПАСПОРТ, 6 - УДОСТОВЕРЕНИЕ БЕЖЕНЦА,
				//     // 7 - ВРЕМЕННОЕ УДОСТОВЕРЕНИЕ ЛИЧНОСТИ ГРАЖДАНИНА РФ,
				//     // 8 - СВИДЕТЕЛЬСТВО О ПРЕДОСТАВЛЕНИИ ВРЕМЕННОГО УБЕЖИЩА НА ТЕРРИТОРИИ РФ,
				//     // 9 - ПАСПОРТ МОРЯКА, 10 - ПАСПОРТ ГРАЖДАНИНА РФ,
				//     // 11 - СВИДЕТЕЛЬСТВО О РАССМОТРЕНИИ ХОДАТАЙСТВА О ПРИЗНАНИИ БЕЖЕНЦЕМ,
				//     // 12 - ВОЕННЫЙ  БИЛЕТ)
//                     "series" => "1234", // Серия [String]
//                     "number" => "56789", // Номер [String]
//                     "date"   => "1985-01-01", // Дата [DateTime]
				//     "note"   => "" // служебное поле для других документов [String]
				// ],

			],

			"payments" => [ // Оплата [Object]
				"pickUp"    => [ // Оплата забора груза [Object]
					"type"        => self::get_delivery_payer_type($orderId),
					// Плательщик (1 - отправитель, 2 - получатель, 3 - третье лицо) [Number]
					"paymentCity" => self::get_delivery_payer_city($orderId),
					// Город оплаты за услугу [City], указывается только при type = 3 - третье лицо.
					// Остальные поля не указываются, т.к. плательщик отправитель
				],
				"moving"    => [ // Оплата перевозки [Object]
					"type"        => self::get_delivery_payer_type($orderId),
					"paymentCity" => self::get_delivery_payer_city($orderId),
				],
				"insurance" => [ // Оплата страхования [Object],
					"type"        => self::get_delivery_payer_type($orderId),
					"paymentCity" => self::get_delivery_payer_city($orderId),
				],
				"delivery"  => [ // Оплата доставки по городу получателя [Object],
					"type"        => self::get_delivery_payer_type($orderId),
					"paymentCity" => self::get_delivery_payer_city($orderId),
					// "inn"          => "7716542310",
					// // ИНН третьего лица,
					// // поле необязательно [String]
					// "title"        => "ОАО \"Заливные луга\"",
					// // Наименование третьего лица [String],
					// // поле обязательно, если "type" => 3
					// "phone"        => "12-12-12",
					// // Телефон третьего лица [String],
					// // поле обязательно, если "type" => 3
					// "identityCard" => [ // Документ удостоверяющий личность  [Object]
					//     // поле обязательно, если "type" => 3
					//     "type"   => 10, // тип документа [Number] (1 - ПАСПОРТ ИНОСТРАННОГО ГРАЖДАНИНА,
					//     // 2 - РАЗРЕШЕННИЕ НА ВРЕМЕННОЕ ПРОЖИВАНИЕ, 3 - ВОДИТЕЛЬСКОЕ УДОСТОВЕРЕНИЕ,
					//     // 4 - ВИД НА ЖИТЕЛЬСТВО, 5 - ЗАГРАНИЧНЫЙ ПАСПОРТ, 6 - УДОСТОВЕРЕНИЕ БЕЖЕНЦА,
					//     // 7 - ВРЕМЕННОЕ УДОСТОВЕРЕНИЕ ЛИЧНОСТИ ГРАЖДАНИНА РФ,
					//     // 8 - СВИДЕТЕЛЬСТВО О ПРЕДОСТАВЛЕНИИ ВРЕМЕННОГО УБЕЖИЩА НА ТЕРРИТОРИИ РФ,
					//     // 9 - ПАСПОРТ МОРЯКА, 10 - ПАСПОРТ ГРАЖДАНИНА РФ,
					//     // 11 - СВИДЕТЕЛЬСТВО О РАССМОТРЕНИИ ХОДАТАЙСТВА О ПРИЗНАНИИ БЕЖЕНЦЕМ,
					//     // 12 - ВОЕННЫЙ  БИЛЕТ)
					//     "series" => "1234", // Серия [String]
					//     "number" => "56789", // Номер [String]
					//     "date"   => "1985-01-01", // Дата [DateTime]
					//     "note"   => "" // служебное поле для других документов [String]
					// ],
				],
			],
		];

//		echo "<pre>";
//		var_dump($data);
//		echo "</pre>";
//		echo json_encode($data);
//		wp_die();


		$body = self::request_api($data, 'cargopickup/SUBMIT');

		PECOM_Db::AddOrderData($orderId, 'PEC_API_SUBMIT_REQUEST', json_encode($data));
		PECOM_Db::AddOrderData($orderId, 'PEC_API_SUBMIT_RESPONSE', json_encode($body));


		if ($pecId = $body->cargos[0]->cargoCode) {
			self::savePecId($orderId, $pecId);
		}
        echo wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		wp_die();
	}

	function create_pecom_preorder() {
		$orderId = $_REQUEST['orderId'];
		$transportType = $_REQUEST['transportType'] ? self::$PREREGISTRATION_TRANSPORT_TYPE[$_REQUEST['transportType']] : self::getPreregistrationTransportType();

		$data = [
			'sender' => [
				'inn'   => get_option( 'pecom_option_inn' ), // ИНН [String], поле необязательно
				'fs'    => get_option( 'pecom_option_ownership_form' ), // Форма собственности [String], поле необязательно
				'city'  => get_option( 'pecom_option_address' ),
				'title' => get_option( 'pecom_option_name' ),
				'phone' => get_option( 'pecom_option_phone' ),
				'person' => get_option( 'pecom_option_sender_name' ),
			],
			'cargos' => [
				[
					'common'   => [
						'type'                  => $transportType, // Тип перевозки (1 - Авиаперевозка, 3 - Автоперевозка, 12 - Изи Уэй) [Number]
						'declaredCost'          => self::get_total_discount($orderId), // Объявленная стоимость товара [Number]
						'description'           => self::get_description_products(), // Описание груза [String]
						'orderNumber'           => $orderId, // Номер заказа клиента [String], поле необязательно
						'accompanyingDocuments' => false, // Есть комплект сопроводительных документов [Boolean]
						'positionsCount'        => $_REQUEST['positionCount'], //self::getOrderPositionsCount(), // Количество мест [Number]
						'cargoSourceSystemGUID' => '5ff31c58-2c7f-11eb-80ce-00155d4a0436'
					],
					'receiver' => self::get_receiver_data($orderId),
					'services' => [
						'hardPacking'        => ['enabled' => self::is_self_pack()],
						'delivery'           => [
							'enabled' => false, //self::isDeliveryToAddress(),
							"payer"   => [
								'type' => 2,//self::getDeliveryPayerType(),
								"paymentCity" => self::get_order_sender_city($orderId),
							],
						],
						'transporting'       => [
							"payer"   => ['type' => self::get_delivery_payer_type()],
						],
						"insurance"          => [ // Страховка [Object]
							"enabled" => self::is_insurance(), // Заказана ли услуга [Boolean]
							"cost"    => self::get_total_discount($orderId), // Оценочная стоимость, руб [Number],
							// поле обязательно, если "enabled"=>true
							"payer"   => ['type' => self::get_delivery_payer_type()],
						],
						'sealing'            => ['enabled' => false],
						'strapping'          => ['enabled' => false],
						'documentsReturning' => ['enabled' => false],
					],
				],
			],
		];

		$body = self::request_api($data, 'preregistration/SUBMIT');

		PECOM_Db::AddOrderData($orderId, 'PEC_API_SUBMIT_REQUEST', json_encode($data));
		PECOM_Db::AddOrderData($orderId, 'PEC_API_SUBMIT_RESPONSE', json_encode($body));


		if ($pecId = $body->cargos[0]->cargoCode) {
			self::savePecId($orderId, $pecId);
		}
        echo wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		wp_die();
	}

	public static function request_api($data, $method) {
		$client = self::get_client_credentials();
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic '.base64_encode( $client['account'].':'.$client['password'] ),
		);

		$url = 'https://kabinet.pecom.ru/api/v1/'.$method;
//		$url = 'https://kabinet.pecom.ru/api/v1/cargopickup/SUBMIT';

		$args = array(
			'headers' => $headers,
			'method'  => 'POST',
			'body'    => $data ? wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) : '',
			'timeout' => 100, // must be that big for huge requests like getting PVZ list.
		);

		$WP_Http = new WP_Http();

		$remote_response = $WP_Http->request( $url, $args );
		$body = json_decode( wp_remote_retrieve_body( $remote_response ) );

		PECOM::log_it( esc_html__( 'Making request to', 'pecom-for-woocommerce' ) . ' ' . $method . ': ' . $url . ' ' . esc_html__( 'with the next body:', 'pecom-for-woocommerce' ) . ' ' . wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

		if ( is_wp_error( $remote_response ) ) {
			PECOM::log_it( esc_html__( 'Cannot connect to', 'pecom-for-woocommerce' ) . ' ' . $url . ' ' . $remote_response->get_error_message() . ' Body: ' . wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), 'error' );

			return false;
		}

		$response_code = intval( wp_remote_retrieve_response_code( $remote_response ) );

		if ( ! in_array( $response_code, array( 200, 202 ), true ) ) {
			PECOM::log_it( esc_html__( 'Cannot connect to', 'pecom-for-woocommerce' ) . ' ' . $url . ' ' . esc_html__( 'response status code:', 'pecom-for-woocommerce' ) . ' ' . $response_code . ' ' . wp_remote_retrieve_body( $remote_response ) . ' Body: ' . wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), 'error' );

			return false;
		}

		return $body;
    }

	function save_pec_id() {
		$order_id = $_POST['orderId'] ? sanitize_text_field( $_POST['orderId'] ) : 0;
		$pec_id = $_POST['pecId'] ? sanitize_text_field( $_POST['pecId'] ) : 0;
		$result = self::savePecId($order_id, $pec_id);
		echo json_encode($result);
		wp_die();
	}

	function update_pec_status() {
		$order_id = $_POST['orderId'] ? sanitize_text_field( $_POST['orderId'] ) : 0;
		$pec_id = $_POST['pecId'] ? sanitize_text_field( $_POST['pecId'] ) : 0;
		$result = self::get_save_pec_status($order_id, $pec_id);
		echo json_encode($result);
		wp_die();
	}

	function update_pecom_cost() {
		$cost = $_POST['cost'] ? sanitize_text_field( $_POST['cost'] ) : 0;
		$term = $_POST['term'] ? sanitize_text_field( $_POST['term'] ) : 0;
		$addressType = $_POST['addressType'] ? sanitize_text_field( $_POST['addressType'] ) : 'department';

		WC()->session->set( 'pecom_cost', $cost );
		WC()->session->set( 'pecom_addressType', $addressType );
		WC()->session->set( 'pecom_shipping_term', $term );
		echo 'OK';
		wp_die();
	}

	public static function get_order_max_dimension($order_id): array {
		$order = wc_get_order($order_id);
		$order_items = $order->get_items();

		$result = ['width' => 0, 'height' => 0, 'length' => 0];
		foreach( $order_items as $item_id => $item ){
			$wc_product = $item->get_product();
			$dimensions = $wc_product->get_dimensions(false);
			$item_data = $item->get_data();

			$quantity = $item_data['quantity'];
			if (!$dimensions['width']) $dimensions['width'] = get_option( 'pecom_max_size' );
			if (!$dimensions['height']) $dimensions['height'] = get_option( 'pecom_max_size' );
			if (!$dimensions['length']) $dimensions['length'] = get_option( 'pecom_max_size' );

			$result['volume'] += $result['length']/1000 * $result['height']/1000 * $result['width']/1000 * $quantity;
			sort($dimensions);
			$dimensions[0] = $dimensions[0]*$quantity;
			rsort($dimensions);

			if ($result['width'] < $dimensions[2]) $result['width'] = $dimensions[2];
			if ($result['height'] < $dimensions[1]) $result['height'] = $dimensions[1];
			if ($result['length'] < $dimensions[0]) $result['length'] = $dimensions[0];

		}
		$result['volume'] = $result['volume'] ? : get_option( 'pecom_volume' );;
		return $result;
	}

	/**
	 * Add select with delivery points after shipping rate in checkout
	 *
	 * @param object $method shipping method.
	 */
	public function add_pvz_select( $method ) {
	    if (!self::$first_load) {
		    $meta_data        = $method->meta_data;
		    if ( isset( $meta_data['pecom_error'] ) ) {
			    // translators: %s: Links.
			    echo ' ' . sprintf( __( 'Please check %1$sWooCommerce Logs%2$s to get more information about the issue.', 'pecom-for-woocommerce' ), '<a style="color: #ac0608; font-weight: bold;" target="_blank" href="' . admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . WC_Log_Handler_File::get_log_file_name( 'pecom' ) ) . '">', '</a>' ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

			    return;
		    }

		    if ( ! is_checkout() ) {
			    return;
		    }
		    if ( 'pecom_shipping' !== $method->method_id ) {
			    return;
		    }
		    if ( WC()->session->get( 'chosen_shipping_methods' )[0] !== $method->id ) {
			    return;
		    }

		    self::$first_load = true;
		    include 'controls/control-pvz-select-list.php';
	    }
	}

	/**
	 * Save PVZ for order
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_pvz_to_order_meta( $order_id ) {
		$pvz = ! empty( $_REQUEST['pecom-pvz-code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pecom-pvz-code'] ) ) : null;

		if ( ! $pvz ) {
			return;
		}

		$pvz = explode( '|', $pvz );

		$pvz_data = array(
			'code'      => $pvz[0],
			'address'   => $pvz[1],
			'city_code' => $pvz[2],
		);

		update_post_meta( $order_id, '_pecom_pvz', $pvz_data );
	}

	/**
	 * Display info about PVZ in admin order details
	 *
	 * @param int $order Order ID.
	 */
	public function display_pvz_after_shipping_address( $order ) {
		$pvz = get_post_meta( $order->get_id(), '_pecom_pvz', true );
		if ( $pvz ) {
			echo '<div class="pecom-admin-pvz" style="float: left; width: 100%; margin: 10px 0;"><strong>ПВЗ: </strong><span class="pecom-admin-pvz-value">' . esc_attr( $pvz['code'] ) . ', ' . esc_attr( $pvz['address'] ) . '</span></div>';
		}
	}

	/**
	 * Display info about PVZ delivery points in customer email
	 *
	 * @param int $order Order ID.
	 */
	public function display_pvz_in_email( $order ) {
		$pvz = get_post_meta( $order->get_id(), '_pecom_pvz', true );
		if ( $pvz ) {
			?>
			<h2><?php esc_html_e( 'Delivery Point', 'pecom-for-woocommerce' ); ?></h2>
			<p><?php echo esc_html( $pvz['address'] ); ?></p>
			<br>
			<?php
		}
	}

	/**
	 * Display info about PVZ in order details
	 *
	 * @param int $order Order ID.
	 */
	public function display_pvz_in_order_details( $order ) {
		$this->display_pvz_in_email( $order );
	}

	public static function get_client_credentials(): array {
		$pecom_account = get_option( 'pecom_account' );

		// Check in case someone tries to set test account as data.
		if ( $pecom_account && 'test' !== $pecom_account ) {
			return array(
				'account'  => get_option( 'pecom_account' ),
				'password' => get_option( 'pecom_password' ),
				'test'     => false,
			);
		} else {
			return array(
				'account'  => 'test',
				'password' => '650EF828F066E9A33B2765141F90DA5ECFC3C044',
				'test'     => true,
			);
		}
	}

	public static function get_delivery_city($order_id) {
		$db = PECOM_Db::GetOrderData($order_id, 'WIDGET');
		return $db['toDepartmentData']['Town']['Town'];
	}

	public static function get_total_discount($order_id) {
		$order = wc_get_order($order_id);
		return $order->get_subtotal();
	}

	public static function get_total_price($order_id) {
		$order = wc_get_order($order_id);
		return $order->get_total();
	}

	public static function is_self_pack(): bool {
		return (bool) get_option( 'pecom_self_pack' );
	}

	public static function is_insurance(): bool {
		return (bool) get_option( 'pecom_safe_price' );
	}

	public static function get_delivery_payer_type($order_id): bool {
		return 1;
	}

	public static function get_description_products(): string {
		return 'товары интернет магазина';
	}

	public static function get_delivery_payer_city($order_id) {
//		if (Option::get('pecom.ecomm', "PEC_COST_OUT", '1')) {
//			return self::getDeliveryCity();
//		} else {
			return self::get_order_sender_city($order_id);
//		}
	}

	public static function get_order_sender_city($order_id) {
		$db = PECOM_Db::GetOrderData($order_id, 'WIDGET');
		return $db['fromDepartmentData']['Town']['Town'];
	}

	public static function get_receiver_data($order_id): array {
		$result = [
			'city'        => self::get_delivery_city($order_id),
			'title'       => self::get_order_receiver_title($order_id) ? : self::get_order_receiver_name($order_id),
			'person'      => self::get_order_receiver_name($order_id),
			'phone'       => self::get_order_receiver_phone($order_id),
		];

		if (self::is_delivery_to_address($order_id)) {
			$result['addressStock'] = self::get_delivery_address($order_id);
		} else {
			$result['warehouseId'] = self::get_delivery_warehouse_id($order_id);
		}

		return $result;
	}

	public static function get_order_receiver_title($order_id): string {
		$order = wc_get_order($order_id);
		return $order->get_billing_company() ;
	}

	public static function get_order_receiver_name($order_id): string {
		$order = wc_get_order($order_id);
		return $order->get_billing_first_name() .' '. $order->get_billing_last_name() ;
	}

	public static function get_order_receiver_phone($order_id): string {
		$order = wc_get_order($order_id);
		return $order->get_billing_phone() ;
	}

	public static function is_delivery_to_address($order_id): bool {
		$db = PECOM_Db::GetOrderData($order_id, 'WIDGET');
		return $db['toAddressType'] == 'address';
	}

	public static function get_delivery_address($order_id) {
		$db = PECOM_Db::GetOrderData($order_id, 'WIDGET');
		return $db['toAddress'];
	}

	public static function get_delivery_warehouse_id($order_id) {
		$db = PECOM_Db::GetOrderData($order_id, 'WIDGET');
		return $db['toDepartmentData']['Warehouses'][0]['UID'];
	}

	public static function savePecId($order_id, $pecId) {
		return PECOM_Db::AddOrderData($order_id, 'PEC_ID', $pecId);
	}

	public static function get_delivery_field($order_id, $field) {
		return PECOM_Db::GetOrderData($order_id, $field);
	}

	public static function get_pickup_date($orderId) {
		$db = PECOM_Db::GetOrderData($orderId, 'PEC_API_SUBMIT_REQUEST');
		$pickUpDate = $db['common']['applicationDate'];

		if (!$pickUpDate) {
			$pickUpDate = date('Y-m-d', strtotime("+1 day"));
		}
		return $pickUpDate;
	}

	public static function get_pec_position_count($orderId) {
		$db = PECOM_Db::GetOrderData($orderId, 'PEC_API_SUBMIT_REQUEST');
		$positionsCount = $db['common']['positionsCount'];
		if (!$positionsCount) {
			$positionsCount = $db['common']['positionsCount'];
		}
		if (!$positionsCount) {
			$positionsCount = 1;
		}
		return $positionsCount;
	}

	public static function get_save_pec_status($orderId, string $pecId) {
		$response = self::request_api(['cargoCodes' => $pecId], 'cargos/STATUS');
		$status = '';
		if ($response->cargos) {
			$status = $response->cargos[0]->info->cargoStatus;
			PECOM_Db::AddOrderData($orderId, 'STATUS', $status);
		} elseif ($response->error) {
			$status = $response->error->title;
		}

		return $status;
	}

	function order_details ($order_id) {
		if ($_REQUEST['pec_widget_data']) {
			PECOM_Db::AddNewOrder($order_id);
			PECOM_Db::AddOrderData($order_id, 'WIDGET', $_REQUEST['pec_widget_data']);
			PECOM_Db::AddOrderData($order_id, 'TRANSPORTATION_TYPE', $_REQUEST['pec_transport_type']);
		}
	}

	function get_total_order_weight( $order_id ) {
		$order        = wc_get_order( $order_id );
		$order_items  = $order->get_items();
		$total_qty    = 0;
		$total_weight = 0;

		foreach ( $order_items as $item_id => $product_item ) {
			$product         = $product_item->get_product();
			$product_weight  = $product->get_weight();
			$quantity        = $product_item->get_quantity();
			$total_qty      += $quantity;
			$total_weight   += floatval( $product_weight * $quantity );
		}
		return $total_weight ? : get_option( 'pecom_weight' );
	}

}

new PECOM_Tools_Shipping();
