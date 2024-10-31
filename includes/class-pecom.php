<?php
/**
 * PECOM setup
 *
 * @package PECOM
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main PECOM Class.
 *
 * @class PECOM
 */
class PECOM {
	/**
	 * PECOM constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 */
	public function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ));
		add_action( 'woocommerce_shipping_init', array( $this, 'init_method' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_method' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PECOM_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'auto_update_plugin', array( $this, 'auto_update_plugin' ), 10, 2 );
		add_action( 'woocommerce_debug_tools', array( $this, 'add_debug_tools' ) );
		add_action( 'wp_footer', array( $this, 'pecom_include_frontend_js' ));
		add_filter('woocommerce_after_order_itemmeta', array( $this, 'custom_link_after_order_itemmeta'), 20, 3 );
	}

	function custom_link_after_order_itemmeta( $item_id, $item, $product ) {
		// Only for "line item" order items
		if ( ! $item->is_type( 'line_item' ) ) {
			return;
		}

		// Only for backend and  for product ID 123
		if ( $product->get_id() == 67 && is_admin() ) {
			echo '<a href="http://example.com/new-view/?id=' . $item->get_order_id() . '">' . __( "Click here to view this" ) . '</a>';
		}
	}

	/**
	 * Load textdomain for a plugin
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'pecom-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add shipping method
	 */
	public function init_method() {
		if ( ! class_exists( 'PECOM_Shipping_Method' ) ) {
			include_once PECOM_ABSPATH . 'includes/class-pecom-shipping-method.php';
		}
	}

	/**
	 * Register shipping method
	 *
	 * @param array $methods shipping methods.
	 *
	 * @return array
	 */
	public function register_method( $methods ) {
		$methods['pecom_shipping'] = 'PECOM_Shipping_Method';

		return $methods;
	}

	/**
	 * Add all partials
	 */
	public function includes() {
		include_once PECOM_ABSPATH . 'includes/class-pecom-admin.php';
		include_once PECOM_ABSPATH . 'includes/class-pecom-client.php';
		include_once PECOM_ABSPATH . 'includes/class-pecom-tools-shipping.php';
		include_once PECOM_ABSPATH . 'includes/class-pecom-db.php';
	}

	/**
	 * Display helpful links
	 *
	 * @param array $links key - link pair.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings = array( 'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pecom' ) . '">' . esc_html__( 'Settings', 'pecom-for-woocommerce' ) . '</a>' );

		$links = $settings + $links;

		return $links;
	}

	/**
	 * Auto update plugin
	 *
	 * @param bool   $should_update If should update.
	 * @param object $plugin Plugin data.
	 *
	 * @return bool
	 */
	public function auto_update_plugin( $should_update, $plugin ) {
		if ( 'pecom-for-woocommerce/pecom-for-woocommerce.php' === $plugin->plugin ) {
			return true;
		}

		return $should_update;
	}

	/**
	 * Add debug tools
	 *
	 * @param array $tools List of available tools.
	 *
	 * @return array
	 */
	public function add_debug_tools( $tools ) {
		$tools['pecom_clear_transients'] = array(
			'name'     => __( 'PECOM transients', 'pecom-for-woocommerce' ),
			'button'   => __( 'Clear transients', 'pecom-for-woocommerce' ),
			'desc'     => __( 'This tool will clear the request transients cache.', 'pecom-for-woocommerce' ),
			'callback' => array( $this, 'clear_transients' ),
		);

		return $tools;
	}

	/**
	 * Callback to clear transients
	 *
	 * @return string
	 */
	public function clear_transients() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_pecom_cache__%'" );

		return __( 'Transients cleared', 'pecom-for-woocommerce' );
	}

	/**
	 * Send message to logger
	 *
	 * @param string $message Log text.
	 * @param string $type Message type.
	 */
	public static function log_it( $message, $type = 'info' ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$hide_log_info = get_option( 'pecom_hide_info_log', 'no' );

		if ( 'yes' === $hide_log_info && 'info' === $type ) {
			return;
		}

		$logger = wc_get_logger();
		$logger->{$type}( $message, array( 'source' => 'pecom' ) );
	}

	/**
	 * Helper to add admin notice
	 *
	 * @param string $message Notice text.
	 * @param string $type Notice type.
	 */
	public function add_admin_notice( $message, $type = 'message' ) {
		add_action(
			'admin_notices',
			function () use ( $message, $type ) {
				$notice_class = array(
					'type'   => 'notice-' . esc_html( $type ),
					'is-dis' => 'error' !== $type ? 'is-dismissible' : '',

				);
				echo '<div class="notice ' . esc_attr( implode( ' ', $notice_class ) ) . '">' . $message . '</div>';
			}
		);
	}


	function pecom_include_frontend_js() {
		if (is_checkout() ) {
			$from_address     = get_option( 'woocommerce_store_address' );
			$from_address_2   = get_option( 'woocommerce_store_address_2' );
			$from_city        = get_option( 'woocommerce_store_city' );
			$widget_data = [
				'url' => 'https://calc.pecom.ru/iframe/e-store-calculator',
				'address' => WC()->session->get( 'pecom_address'),
				'from_address' => $from_city .', '. $from_address .' '. $from_address_2,
				'intake' => get_option( 'pecom_store_pzz' ) == 'address' ? 1 : 0,
				'weight' => WC()->cart->cart_contents_weight,
				'volume' => WC()->session->get( 'pecom_volume'),
				'total_price' => WC()->cart->cart_contents_total,
				'needPackingRigid' => get_option( 'pecom_self_pack' ) == 'yes',
				'transportation_type' => get_option( 'pecom_transportation_type' ),
				'id' => WC()->session->get('pecom_id'),
				'pecom_shipping_term' => WC()->session->get('pecom_shipping_term'),
			];

			?>
			<script>
                var widget_data = <?php echo json_encode($widget_data)?>;
                // console.log(widget_data)
			</script>
			<?php
			wp_enqueue_script("jquery-ui-autocomplete", array('jquery','jquery-ui-core'));
			wp_enqueue_script('cds_pecom_script', PECOM_PLUGIN_DIR_URL . 'assets/js/pecom.js', array('jquery' ), '1.0');
			wp_enqueue_style('cds_pecom_style', PECOM_PLUGIN_DIR_URL . 'assets/css/pecom.css');
		}
	}
}
