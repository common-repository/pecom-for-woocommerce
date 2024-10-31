<?php
/**
 * Plugin Name: PECOM for WooCommerce
 * Description: Automatically calculate the shipping cost for PECOM tariffs
 * Version: 1.0.0
 * Author: Lean-IT
 * Author URI: mailto:admin-extintegration@pecom.ru
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: pecom-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.0
 * WC requires at least: 4.4
 * WC tested up to: 4.8
 *
 * @package PECOM
 */

defined( 'ABSPATH' ) || exit;

define( 'PECOM_PLUGIN_FILE', __FILE__ );
define( 'PECOM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'PECOM_ABSPATH', dirname( PECOM_PLUGIN_FILE ) . '/' );
register_activation_hook(__FILE__,'pecom_install_table');
// Include the main class.
if ( ! class_exists( 'PECOM', false ) ) {
	include_once dirname(PECOM_PLUGIN_FILE) . '/includes/class-pecom.php';
}

// Init plugin if woo is active.
if ( in_array(
	'woocommerce/woocommerce.php',
	apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),

	true
) ) {
	new PECOM();
}


function pecom_install_table () {
	global $wpdb;

	$table_name = $wpdb->prefix . "pecom_orders";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

		$sql = "CREATE TABLE {$table_name} (
	        ID int(11) NOT NULL auto_increment,
			ORDER_ID int(11),
			PEC_ID varchar(50),
			WIDGET text,
			STATUS text,
			TRANSPORTATION_TYPE varchar(255),
			PEC_API_SUBMIT_REQUEST text,
			PEC_API_SUBMIT_RESPONSE text,
			PEC_API_SUBMIT_OK varchar(1),
			UPTIME varchar(10),
	        PRIMARY KEY  (ID)
		) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	}
}