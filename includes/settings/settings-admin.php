<?php
/**
 * Settings for admin page.
 *
 * @package PECOM/Settings/Admin
 */

defined( 'ABSPATH' ) || exit;

return array(
//	'title'
//	=> array(
//		'title'       => __( 'Method title', 'pecom-for-woocommerce' ),
//		'type'        => 'text',
//		'description' => __( 'This title the user sees during checkout.', 'pecom-for-woocommerce' ),
//		'default'     => __( 'PECOM', 'pecom-for-woocommerce' ),
//	),
	array(
		'title' => __( 'PECOM', 'pecom-for-woocommerce' ),
		'type'  => 'title',
	),
	array(
		'id'    => 'pecom_account',
		'title' => __( 'API Account', 'pecom-for-woocommerce' ),
		'desc'  => __( 'Account for API integration.', 'pecom-for-woocommerce' ),
		'type'  => 'text',
	),
	array(
		'id'    => 'pecom_password',
		'title' => __( 'API Secure Password', 'pecom-for-woocommerce' ),
		'desc'  => __( 'Secret key for API integration.', 'pecom-for-woocommerce' ),
		'type'  => 'text',
	),
	array(
		'type' => 'sectionend',
	),
	array(
		'title' => __( 'Dimensions of one product by default', 'pecom-for-woocommerce' ),
		'desc'  => __( 'These values will be taken into account in the absence of overall characteristics of the product.', 'pecom-for-woocommerce' ),
		'type'  => 'title',
	),
	array(
		'title' => __( 'Weight (g.)', 'pecom-for-woocommerce' ),
		'type'  => 'text',
		'id'    => 'pecom_weight',
		'default'  => 0.05,
	),
	array(
		'title' => __( 'Объем товара, м3', 'pecom-for-woocommerce' ),
		'type'  => 'text',
		'id'    => 'pecom_volume',
		'default'  => 0.001,
	),
	array(
		'title' => __( 'Максимальный размер (длина или ширина или высота) товара, м', 'pecom-for-woocommerce' ),
		'type'  => 'number',
		'id'    => 'pecom_max_size',
		'default'  => 1,
	),
	array(
		'type' => 'sectionend',
	),

	array(
		'title' => __( 'Seller', 'pecom-for-woocommerce' ),
		'desc'  => __( 'Details of the real seller. It is used when printing invoices to display the address of the real seller of the goods, as well as for international orders.', 'pecom-for-woocommerce' ),
		'type'  => 'title',
	),
	array(
		'title'             => __( 'Store Name', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'id'                => 'pecom_option_name',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'             => __( 'INN', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'id'                => 'pecom_option_inn',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'             => __( 'Contact person', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'id'                => 'pecom_option_sender_name',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'             => __( 'Phone', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'id'                => 'pecom_option_phone',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'             => __( 'Ownership Form', 'pecom-for-woocommerce' ),
		'type'              => 'select',
		'options'           => array(
			9   => __( 'Joint-stock company', 'pecom-for-woocommerce' ),
			61  => __( 'Closed joint-stock company', 'pecom-for-woocommerce' ),
			63  => __( 'Individual entrepreneur', 'pecom-for-woocommerce' ),
			119 => __( 'Open joint-stock company', 'pecom-for-woocommerce' ),
			137 => __( 'Limited liability company', 'pecom-for-woocommerce' ),
			147 => __( 'Public joint-stock company', 'pecom-for-woocommerce' ),
		),
		'id'                => 'pecom_option_ownership_form',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'   => __( 'Transportation Type', 'pecom-for-woocommerce' ),
		'type'    => 'select',
		'options' => array(
			'auto'    => __( 'auto', 'pecom-for-woocommerce' ),
			'avia' 	  => __( 'avia', 'pecom-for-woocommerce' ),
			'easyway' => __( 'easyway', 'pecom-for-woocommerce' )
		),
		'id'                => 'pecom_transportation_type',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'             => __( 'Address', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'id'                => 'pecom_option_address',
		'custom_attributes' => array(
			'required'		=> true,
		),
	),
	array(
		'title'    => __( 'Доставка по умолчанию', 'woocommerce' ),
		'id'       => 'pecom_store_pzz',
		'default'  => 'address',
		'type'     => 'radio',
		'options'  => array(
			'address' => __( 'По адресу', 'woocommerce' ),
			'department'  => __( 'В отделение', 'woocommerce' ),
		),
	),
	array(
		'title'    => __( 'Indicate protective shipping packaging', 'pecom-for-woocommerce' ),
		'type'     => 'checkbox',
		'id'       => 'pecom_self_pack',
		'default'  => '0',
	),
	array(
		'title'    => __( 'Indicate declared value', 'pecom-for-woocommerce' ),
		'type'     => 'checkbox',
		'id'       => 'pecom_safe_price',
		'default'  => '0',
	),
	array(
		'type' => 'sectionend',
	),
);
