<?php
/**
 * PECOM Shipping Method.
 *
 * @version 1.0.0
 * @package PECOM/Shipping
 */

defined( 'ABSPATH' ) || exit;

/**
 * PECOM_Shipping_Method class.
 */
class PECOM_Shipping_Method extends WC_Shipping_Method {

	static $cash = [];
	/**
	 * Constructor
	 *
	 * @param int $instance_id Shipping method instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'pecom_shipping';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'PECOM', 'pecom-for-woocommerce' );
		$this->method_description = __( 'Calculate shipping rates for PECOM tariffs.', 'pecom-for-woocommerce' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
		);
		$this->init();
	}

	/**
	 * Init variables
	 */
	public function init() {
		$this->instance_form_fields = include 'settings/settings-shipping-method.php';

		foreach ( $this->instance_form_fields as $key => $settings ) {
			$this->{$key} = $this->get_option( $key );
		}

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'custom_cart_total' ) );
	}

	function custom_cart_total($aaa) {

		WC()->cart->calculate_shipping();
	}

	/**
	 * Calculate shipping rate
	 *
	 * @param array $package Package of items from cart.
	 */
	public function calculate_shipping( $package = array() ) {
//		wp_cache_flush();
//		var_dump(WC()->session->get( 'pecom_cost'));
		if (!WC()->session->get( 'pecom_cost')) {
			$state  = $package['destination']['state'];
			$city   = $package['destination']['city'];
			$street = $package['destination']['address_1'] . ' ' . $package['destination']['address_2'];
			$cost   = 0;
			$result = [];
			if ( $city ) {
				$address = $state . ', ' . $city . ', ' . $street;
				$volume  = $this->get_goods_dimensions( $package ) ?: get_option( 'pecom_weight' );
				$weight  = WC()->cart->cart_contents_weight ?: get_option( 'pecom_weight' );
				WC()->session->set( 'pecom_address', $address );
				WC()->session->set( 'pecom_volume', $volume );
				WC()->session->set( 'pecom_id', $this->get_rate_id() );
				$from_address   = get_option( 'woocommerce_store_address' );
				$from_address_2 = get_option( 'woocommerce_store_address_2' );
				$from_city      = get_option( 'woocommerce_store_city' );
				$from_address   = $from_city . ', ' . $from_address . ' ' . $from_address_2;

				$data = [];
				switch ( get_option( 'pecom_transportation_type' ) ) {
					case 'auto':
					case 'avia':
						$data['transport']          = get_option( 'pecom_transportation_type' );
						$data['transportationType'] = 'regular';
						break;
					case 'easyway':
						$data['transport']          = 'auto';
						$data['transportationType'] = get_option( 'pecom_transportation_type' );
						break;
				}

				$deliveryType                    = get_option( 'pecom_store_pzz' );
				$data['needPackingRigid']        = get_option( 'pecom_self_pack' ) == 'yes';
				$data['cargo']['volume']         = $volume;
				$data['cargo']['weight']         = $weight;
				$data['cargo']['declaredAmount'] = WC()->cart->cart_contents_total;

				$data['direction']['from']['address']    = $from_address;
				$data['direction']['from']['coords']     = null;
				$data['direction']['from']['type']       = $deliveryType;
				$data['direction']['from']['department'] = null;

				$data['direction']['to']['address']    = $address;
				$data['direction']['to']['coords']     = null;
				$data['direction']['to']['type']       = WC()->session->get( 'pecom_addressType' ) ?: 'department';
				$data['direction']['to']['department'] = null;
				$url                                   = 'https://calc.pecom.ru/api/e-store-calculate';
				$headers                               = array(
					'Content-Type'     => 'application/json;charset=UTF-8',
					'X-Requested-With' => 'XMLHttpRequest'
				);

				$body   = json_encode( $data );
				$key    = md5( $body );
				$result = WC()->session->get( 'pecom_address' . $key );

				if ( ! $result ) {
					$result = wp_remote_post( $url, array(
							'method'      => 'POST',
							'headers'     => $headers,
							'httpversion' => '1.0',
							'sslverify'   => false,
							'body'        => $body
						)
					);
					WC()->session->set( 'pecom_address' . $key, $result );
				}


				$result = json_decode( $result['body'], true );
				$term   = WC()->session->get( 'pecom_shipping_term' ) ?: $result['result']['term']['days'];
				WC()->session->set( 'pecom_shipping_term', $term );
				file_put_contents( 'wp-content/plugins/pecom-for-woocommerce/1111111.txt', print_r( WC()->session->get('pecom_shipping_term'), true ) . "\n", FILE_APPEND );

				$cost = round( $result['result']['price'], 0 );
			}
		} else {
			$cost = WC()->session->get( 'pecom_cost');
		}
//		file_put_contents( 'wp-content/plugins/pecom-for-woocommerce/1111111.txt', print_r( WC()->session->get( 'pecom_cost'), true ) . "\n", FILE_APPEND );

		$array = array(
			'id'        => $this->get_rate_id(),
			'label'     => $this->title,
			'package'   => $package,
		);
		if (isset($cost))
		$array['cost'] = $cost;
//		if (isset($term)) $array['meta_data']['Доставка ПЭК'] = $term;
//		$array['meta_data']['pec widget data'] = json_encode($result['result']);
		$this->add_rate( $array );
//		var_dump(json_encode($result['result']));
	}

	/**
	 * Check all condition to display a method before calculation
	 *
	 * @param array $package Shipping package.
	 *
	 * @return bool
	 */
	public function check_condition_for_disable( $package ) {
		$total_val = WC()->cart->get_cart_subtotal();
		$weight    = wc_get_weight( WC()->cart->get_cart_contents_weight(), 'g' );

		// check if cost is less than provided in options.
		if ( $this->cond_min_cost && intval( $this->cond_min_cost ) > 0 && $total_val < $this->cond_min_cost ) {
			return true;
		}

		// check conditional weights.
		if ( ( $this->cond_min_weight && $weight < intval( $this->cond_min_weight ) ) || ( $this->cond_max_weight && $weight > intval( $this->cond_max_weight ) ) ) {
			return true;
		}

		// check if has specific shipping class.
		if ( isset( $this->cond_has_shipping_class ) ) {
			$found_shipping_classes  = $this->find_shipping_classes( $package );
			$is_shipping_class_found = false;
			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
				if ( $shipping_class_term && $shipping_class_term->term_id && in_array( (string) $shipping_class_term->term_id, $this->cond_has_shipping_class, true ) ) {
					$is_shipping_class_found = true;
					break;
				}
			}

			if ( $is_shipping_class_found ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Additional percentage cost.
	 *
	 * @param int $shipping_cost Shipping cost.
	 *
	 * @return float|int
	 * @since 1.0.3
	 */
	public function get_percentage_cost( $shipping_cost ) {
		$percentage = floatval( $this->add_percentage_cost ) / 100;
		$type       = $this->add_percentage_cost_type;

		if ( ! $percentage ) {
			return 0;
		}

		switch ( $type ) {
			case 'percentage_shipping_cost':
				return $shipping_cost * $percentage;
			case 'percentage_total':
				return ( WC()->cart->get_subtotal() + WC()->cart->get_fee_total() + $shipping_cost ) * $percentage;
			default:
				return WC()->cart->get_subtotal() * $percentage;
		}
	}

	/**
	 * Add additional cost based on shipping classes
	 *
	 * @param array $package Shipping package.
	 *
	 * @return int
	 */
	public function get_shipping_class_cost( $package ) {
		$shipping_classes = WC()->shipping()->get_shipping_classes();
		$cost             = 0;

		if ( ! empty( $shipping_classes ) && isset( $this->class_cost_calc_type ) ) {
			$found_shipping_classes = $this->find_shipping_classes( $package );
			$highest_class_cost     = 0;

			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				// Also handles BW compatibility when slugs were used instead of ids.
				$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
				$class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option( 'class_cost_' . $shipping_class_term->term_id, $this->get_option( 'class_cost_' . $shipping_class, '' ) ) : $this->get_option( 'no_class_cost', '' );

				if ( '' === $class_cost_string ) {
					continue;
				}

				$class_cost = $this->evaluate_cost(
					$class_cost_string,
					array(
						'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
						'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) ),
					)
				);

				if ( 'class' === $this->class_cost_calc_type ) {
					$cost += $class_cost;
				} else {
					$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
				}
			}

			if ( 'order' === $this->class_cost_calc_type && $highest_class_cost ) {
				$cost += $highest_class_cost;
			}
		}

		return $cost;
	}

	/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @param mixed $package Package of items from cart.
	 *
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}

	/**
	 * Work out fee (shortcode).
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function fee( $atts ) {
		$atts = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $atts['min_fee'] && $calculated_fee < $atts['min_fee'] ) {
			$calculated_fee = $atts['min_fee'];
		}

		if ( $atts['max_fee'] && $calculated_fee > $atts['max_fee'] ) {
			$calculated_fee = $atts['max_fee'];
		}

		return $calculated_fee;
	}


	/**
	 * Evaluate a cost from a sum/string.
	 *
	 * @param string $sum Sum of shipping.
	 * @param array  $args Args.
	 *
	 * @return string
	 */
	protected function evaluate_cost( $sum, $args = array() ) {
		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

		// Allow 3rd parties to process shipping cost arguments.
		$args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $this );
		$locale         = localeconv();
		$decimals       = array(
			wc_get_price_decimal_separator(),
			$locale['decimal_point'],
			$locale['mon_decimal_point'],
			',',
		);
		$this->fee_cost = $args['cost'];

		// Expand shortcodes.
		add_shortcode( 'fee', array( $this, 'fee' ) );

		$sum = do_shortcode(
			str_replace(
				array(
					'[qty]',
					'[cost]',
				),
				array(
					$args['qty'],
					$args['cost'],
				),
				$sum
			)
		);

		remove_shortcode( 'fee', array( $this, 'fee' ) );

		// Remove whitespace from string.
		$sum = preg_replace( '/\s+/', '', $sum );

		// Remove locale from string.
		$sum = str_replace( $decimals, '.', $sum );

		// Trim invalid start/end characters.
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// Do the math.
		return $sum ? WC_Eval_Math::evaluate( $sum ) : 0;
	}

	/**
	 * Get all goods dimensions
	 *
	 * @param array $package Package of items from cart.
	 * @param array $services Method services.
	 *
	 * @return array|float|int
	 */
	public function get_goods_dimensions( $package ) {
		$volume = 0;

        foreach ( $package['contents'] as $item_id => $item_values ) {
            if ( ! $item_values['data']->needs_shipping() ) {
                continue;
            }

            $length = wc_get_dimension( floatval( $item_values['data']->get_length() ), 'm' );
            $width  = wc_get_dimension( floatval( $item_values['data']->get_width() ), 'm' );
            $height = wc_get_dimension( floatval( $item_values['data']->get_height() ), 'm' );
	        if ( $length && $width && $height ) {
		        $volume += $length * $width * $height * $item_values['quantity'];
	        }
        }
		return round($volume, 3);
	}

	/**
	 * Print human error only for admin to easy debug errors
	 */
	public function maybe_print_error() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$this->add_rate(
			array(
				'id'        => $this->get_rate_id(),
				'label'     => $this->title . '. ' . __( 'Error during calculation. This message and method are visible only for the site Administrator for debugging purposes.', 'pecom-for-woocommerce' ),
				'cost'      => 0,
				'meta_data' => array( 'pecom_error' => true ),
			)
		);
	}


	/**
	 * Check if free shipping is available based on the package and cart.
	 *
	 * @return bool
	 */
	public function is_free_shipping_available() {
		$has_coupon         = false;
		$has_met_min_amount = false;

		if ( in_array( $this->free_shipping_cond, array( 'coupon', 'either', 'both' ), true ) ) {
			$coupons = WC()->cart->get_coupons();

			if ( $coupons ) {
				foreach ( $coupons as $code => $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
			}
		}

		if ( in_array( $this->free_shipping_cond, array( 'min_amount', 'either', 'both' ), true ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = $total - WC()->cart->get_discount_tax();
			}

			if ( 'no' === $this->free_shipping_ignore_discounts ) {
				$total = $total - WC()->cart->get_discount_total();
			}

			$total = round( $total, wc_get_price_decimals() );

			if ( $total >= $this->free_shipping_cond_amount ) {
				$has_met_min_amount = true;
			}
		}

		switch ( $this->free_shipping_cond ) {
			case 'min_amount':
				$is_available = $has_met_min_amount;
				break;
			case 'coupon':
				$is_available = $has_coupon;
				break;
			case 'both':
				$is_available = $has_met_min_amount && $has_coupon;
				break;
			case 'either':
				$is_available = $has_met_min_amount || $has_coupon;
				break;
			default:
				$is_available = true;
				break;
		}

		return $is_available;
	}
}

