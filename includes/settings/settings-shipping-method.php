<?php
/**
 * Settings for PECOM shipping.
 *
 * @package PECOM/Settings/Shipping
 */

defined( 'ABSPATH' ) || exit;

$shipping_classes         = WC()->shipping()->get_shipping_classes();
$post_index_message       = '';
$shipping_classes_options = array();
foreach ( $shipping_classes as $shipping_class ) {
	if ( ! isset( $shipping_class->term_id ) ) {
		continue;
	}
	$shipping_classes_options[ $shipping_class->term_id ] = $shipping_class->name;
}
$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. 10.00 * [qty].', 'pecom-for-woocommerce' ) . '<br/><br/>' . __( 'Use [qty] for the number of items, [cost] for the total cost of items, and [fee percent="10" min_fee="20" max_fee=""] for percentage based fees.', 'pecom-for-woocommerce' );

$post_index_message = '<br><br><span style="color: red">' . __( 'Please note!', 'pecom-for-woocommerce' ) . '</span> <span style="color: #007cba">' . __( 'Delivery is calculated only from the sender postcode to the recipient\'s postcode. Make sure that the postcode field in your store is not disabled on the checkout page and is required, otherwise, the calculation will not be possible. This limitation is absent in the PRO version of the plugin since the bases of regions and cities of the Russian Federation are used.', 'pecom-for-woocommerce' ) . '</span>';

$settings = array(
	'title'
	=> array(
		'title'       => __( 'Method title', 'pecom-for-woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This title the user sees during checkout.', 'pecom-for-woocommerce' ),
		'default'     => __( 'PECOM', 'pecom-for-woocommerce' ),
	),
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
		'type' => 'sectionend',
	),
);

if ( $shipping_classes_options ) {
	$settings['cond_has_shipping_class'] = array(
		'title'             => __( 'Exclude for specific shipping classes', 'pecom-for-woocommerce' ),
		'type'              => 'multiselect',
		'class'             => 'wc-enhanced-select',
		'options'           => $shipping_classes_options,
	);
	$settings['class_costs_title']       = array(
		'title'             => __( 'Additional costs for shipping classes', 'pecom-for-woocommerce' ),
		'type'              => 'title',
		'default'           => '',
		// translators: %s href link.
	);
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}

		$settings[ 'class_cost_' . $shipping_class->term_id ] = array(
			// translators: %s shipping class name.
			'title'             => sprintf( __( '"%s" shipping class cost', 'pecom-for-woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'pecom-for-woocommerce' ),
			'desc_tip'          => true,
			'sanitize_callback' => array( $this, 'sanitize_cost' ),
		);
	}

	$settings['no_class_cost'] = array(
		'title'             => __( 'No shipping class cost', 'pecom-for-woocommerce' ),
		'type'              => 'text',
		'placeholder'       => 'N/A',
		'default'           => '',
		'desc_tip'          => true,
		'sanitize_callback' => array( $this, 'sanitize_cost' ),
	);

	$settings['class_cost_calc_type'] = array(
		'title'             => __( 'Calculation type', 'pecom-for-woocommerce' ),
		'type'              => 'select',
		'class'             => 'wc-enhanced-select',
		'default'           => 'class',
		'options'           => array(
			'class' => __( 'Per class: Charge shipping for each shipping class individually', 'pecom-for-woocommerce' ),
			'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'pecom-for-woocommerce' ),
		),
	);
}

return $settings;
