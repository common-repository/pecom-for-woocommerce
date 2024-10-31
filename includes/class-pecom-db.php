<?php

class PECOM_Db
{

	public static function getTableName() {
		global $wpdb;

		return $wpdb->prefix . "pecom_orders";
	}

	public static function AddNewOrder(int $orderId) {
		if (!$orderId) return;
		global $wpdb;
		$strSql =
			"INSERT INTO " . self::getTableName() . "(ORDER_ID) ".
			"VALUES(". $orderId .")";
		$wpdb->get_results($strSql);
	}

	public static function AddOrderData(int $orderId, string $field, string $data) {
		global $wpdb;
		$strSql = "UPDATE " . self::getTableName() . " SET " . $field . "='" . $data . "' WHERE ORDER_ID=" . $orderId;
		return $wpdb->get_results($strSql);
	}

	public static function GetOrderIds($startOrderId = 0) {
		global $wpdb;

		$strSql = "SELECT ORDER_ID, PEC_ID, STATUS FROM " . self::getTableName() . ' WHERE PEC_ID > "" AND ORDER_ID >= ' . $startOrderId;
		$res = $wpdb->get_results($strSql);
		$result = [];
		while ($arr = $res->Fetch()) {
			$status = unserialize($arr['STATUS']);
			$result[$arr['ORDER_ID']] = ['pecId' => $arr['PEC_ID'], 'status' => $status['code']];
		}
		return $result;
	}

	public static function GetOrderData(int $orderId, string $field) {
		global $wpdb;
		$strSql = "SELECT " . $field . " FROM " . self::getTableName() . " WHERE ORDER_ID=" . $orderId;
		$res = $wpdb->get_row($strSql, 'ARRAY_A');
		if($res) {
			if (in_array($field, ['PEC_ID', 'STATUS']))
				return $res[$field];
			else
				return json_decode($res[$field], true);
		}
		else return false;
	}
}