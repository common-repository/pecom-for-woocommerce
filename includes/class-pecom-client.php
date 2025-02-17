<?php
/**
 * PECOM client
 *
 * @package PECOM/Client
 * @since   1.0.0
 */
namespace PECOM_Client;
defined( 'ABSPATH' ) || exit;

/**
 * Client API connection
 *
 * @class PECOM_Client
 */
class PECOM_Client {
	/**
	 * Main api url.
	 *
	 * @var string
	 */
	private static $api_url = 'https://kabinet.pecom.ru/api/v1/';

	/**
	 * Calculate shipping rate https://confluence.pecom.ru/pages/viewpage.action?pageId=15616129#id-%D0%9F%D1%80%D0%BE%D1%82%D0%BE%D0%BA%D0%BE%D0%BB%D0%BE%D0%B1%D0%BC%D0%B5%D0%BD%D0%B0%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D0%BC%D0%B8(v1.5)-4.14.1.%D0%A0%D0%B0%D1%81%D1%87%D0%B5%D1%82%D1%81%D1%82%D0%BE%D0%B8%D0%BC%D0%BE%D1%81%D1%82%D0%B8%D0%BF%D0%BE%D1%82%D0%B0%D1%80%D0%B8%D1%84%D0%B0%D0%BC%D1%81%D0%BF%D1%80%D0%B8%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D1%82%D0%BE%D0%BC
	 *
	 * @param array $args Shipping params.
	 *
	 * @return bool|mixed|null
	 */
	public static function calculate_rate( $args ) {
		$client = self::get_client_credentials();

//		$data = [];
//		switch ($arParams['transportationType']) {
//			case 'auto':
//			case 'avia':
//				$data['transport'] = $arParams['transportationType'];
//				$data['transportationType'] = 'regular';
//				break;
//			case 'easyway':
//				$data['transport'] = 'auto';
//				$data['transportationType'] = $arParams['transportationType'];
//				break;
//		}

//		$deliveryType = $arParams['FROM_TYPE'] == 'store' ? 'address' : 'department';
//		$data['needPackingRigid'] = $arParams['SELF_PACK'];
//		$data['cargo']['volume'] = $arParams['VOLUME'];
//		$data['cargo']['weight'] = $arParams['WEIGHT'];
//		$data['cargo']['declaredAmount'] = $arParams['PRICE'];
//
//		$data['direction']['from']['address'] = $arParams['FROM_ADDRESS'];
//		$data['direction']['from']['coords'] = null;
//		$data['direction']['from']['type'] = $deliveryType;
//		$data['direction']['from']['department'] = null;
//
//		$data['direction']['to']['address'] = $arParams['ADDRESS'];
//		$data['direction']['to']['coords'] = null;
//		$data['direction']['to']['type'] = 'department';
//		$data['direction']['to']['department'] = null;



		$date   = gmdate( 'Y-m-d', strtotime( current_time( 'mysql' ) ) );

		$req_params = array(
			'version'     => '1.0',
			'currency'    => get_woocommerce_currency(),
			'dateExecute' => $date,
		);

		// Add account data if not a test request.
		if ( ! $client['test'] ) {
			$req_params['authLogin'] = $client['account'];
			$req_params['secure']    = md5( $date . '&' . $client['password'] );
		}

		$args = array_merge( $args, $req_params );

		return self::get_data_from_api( 'calculator/calculate_tarifflist.php', $args, 'POST', false );
	}

	/**
	 * Create new order https://confluence.pecom.ru/pages/viewpage.action?pageId=29923926
	 *
	 * @param array $args Orders params.
	 *
	 * @return bool|mixed|null
	 */
	public static function create_order( $args ) {
		return self::get_data_from_api( 'v2/orders', $args );
	}

	/**
	 * Delete order info https://confluence.pecom.ru/pages/viewpage.action?pageId=29924487
	 *
	 * @param string $args Order uuid key.
	 *
	 * @return bool|mixed|null
	 */
	public static function delete_order( $args ) {
		return self::get_data_from_api( 'v2/orders/' . $args, array(), 'DELETE' );
	}

	/**
	 * Get order info https://confluence.pecom.ru/pages/viewpage.action?pageId=29923975
	 *
	 * @param string $args Order uuid key.
	 *
	 * @return bool|mixed|null
	 */
	public static function get_order( $args ) {
		return self::get_data_from_api( 'v2/orders/' . $args, array(), 'GET' );
	}

	/**
	 * Register courier intake https://confluence.pecom.ru/pages/viewpage.action?pageId=29925274
	 *
	 * @param array $args Intake params.
	 *
	 * @return bool|mixed|null
	 */
	public static function register_intake( $args ) {
		return self::get_data_from_api( 'v2/intakes', $args, 'POST' );
	}

	/**
	 * Get courier intake status https://confluence.pecom.ru/pages/viewpage.action?pageId=29925274
	 *
	 * @param array $args Intake params.
	 *
	 * @return bool|mixed|null
	 */
	public static function get_intake( $args ) {
		return self::get_data_from_api( 'v2/intakes/' . $args, array(), 'GET' );
	}

	/**
	 * Delete courier intake https://confluence.pecom.ru/pages/viewpage.action?pageId=29948379
	 *
	 * @param string $args Intake uuid.
	 *
	 * @return bool|mixed|null
	 */
	public static function delete_intake( $args ) {
		return self::get_data_from_api( 'v2/intakes/' . $args, array(), 'DELETE' );
	}

	/**
	 * Get delivery points https://confluence.pecom.ru/pages/viewpage.action?pageId=36982648
	 *
	 * @param object $order It could be customer (WC()->customer) or order object.
	 *
	 * @return array|bool
	 */
	public static function get_pvz_list( $order ) {
		$postcode        = $order->get_shipping_postcode();
		$state           = $order->get_shipping_state();
		$city            = $order->get_shipping_city();
		$country         = $order->get_shipping_country();
		$is_cod          = 'allowed_cod';
		$delivery_points = array();

		if ( ! $country ) {
			return false;
		}

		$args = array(
			'country_code' => $country,
		);

		if ( 'RU' === $country ) {
			if ( 101000 === $postcode || 'москва' === mb_strtolower( $city ) ) {
				$args['region_code'] = 81;
			} else {
				$args['postal_code'] = $postcode;
			}
		}

		$items = self::get_data_from_api( add_query_arg( $args, 'v2/deliverypoints' ), array(), 'GET', false );

		if ( ! $items ) {
			return false;
		}

		// Sort array in alphabetical order based on address.
		if ( version_compare(
			phpversion(),
			7,
			'>='
		) ) {
			usort(
				$items,
				function ( $item1, $item2 ) {
					return $item1['location']['address'] <=> $item2['location']['address'];
				}
			);
		}

		foreach ( $items as $item ) {
			if ( isset( $item['location']['address'] ) && isset( $item['location']['latitude'] ) ) {
				$delivery_points[ $item['code'] ] = array(
					'fullAddress'     => 'RU' === $country ? '' : $item['location']['city'] . ',' . $item['location']['address'],
					'name'            => $item['name'],
					'code'            => $item['code'],
					'nearest_station' => ! empty( $item['nearest_station'] ) ? $item['nearest_station'] : '',
					'city_code'       => $item['location']['city_code'],
					'address'         => str_replace( '\\', '/', $item['location']['address'] ),
					'coordinates'     => $item['location']['latitude'] . ',' . $item['location']['longitude'],
				);
			}
		}

		return $delivery_points;
	}

	/**
	 * Get new updated version for delivery points from API
	 *
	 * @return bool
	 */
	public static function retrieve_all_pvz() {
		$data = self::get_data_from_api( 'v2/deliverypoints', array(), 'GET' );

		if ( ! $data ) {
			return false;
		}

		$file_all = fopen( PECOM_ABSPATH . 'includes/lists/pvz-all.json', 'w+' );
		fwrite( $file_all, json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		fclose( $file_all );

		return true;
	}

	/**
	 * Get new updated version for delivery points from API
	 *
	 * @return bool
	 */
	public static function retrieve_all_city_codes() {
		$url  = add_query_arg(
			array(
				'country_codes' => array( 'RU' ),
				'size'          => 99999,
				'page'          => 0,
			),
			'v2/location/cities'
		);
		$data = self::get_data_from_api( $url, array(), 'GET' );

		if ( ! $data ) {
			return false;
		}

		$file_all = fopen( PECOM_ABSPATH . 'includes/lists/cities-ru.json', 'w+' );
		fwrite( $file_all, json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		fclose( $file_all );

		return true;
	}

	/**
	 * Get new updated version for delivery points from API
	 *
	 * @return bool
	 */
	public static function retrieve_all_region_codes() {
		$url  = add_query_arg(
			array(),
			'v2/location/regions'
		);
		$data = self::get_data_from_api( $url, array(), 'GET' );

		if ( ! $data ) {
			return false;
		}

		$file_all = fopen( PECOM_ABSPATH . 'includes/lists/regions.json', 'w+' );
		fwrite( $file_all, json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		fclose( $file_all );

		return true;
	}

	/**
	 * Get client credentials for requests
	 *
	 * If no credentials are set use test data
	 *
	 * @return array
	 */
	public static function get_client_credentials() {
		$pecom_account = get_option( 'pecom_account' );

		// Check in case someone tries to set test account as data.
		if ( $pecom_account && 'EMscd6r9JnFiQ3bLoyjJY6eM78JrJceI' !== $pecom_account ) {
			return array(
				'account'  => get_option( 'pecom_account' ),
				'password' => get_option( 'pecom_password' ),
				'api_url'  => 'https://kabinet.pecom.ru/api/v1/',
				'test'     => false,
				'sender' => array(
					'inn' => get_option( 'pecom_option_inn' ),
					'name' => get_option( 'pecom_option_name' ),
					'phone' => get_option( 'pecom_option_phone' ),
					'type' => get_option( 'pecom_option_ownership_form' ),
					'address' => get_option( 'pecom_option_address' ),
				)
			);
		} else {
			return array(
				'account'  => 'EMscd6r9JnFiQ3bLoyjJY6eM78JrJceI',
				'password' => 'PjLZkKBHEiLK3YsjtNrt3TGNG0ahs3kG',
				'api_url'  => 'https://kabinet.pecom.ru/api/v1/',
				'test'     => true,
			);
		}
	}

	/**
	 * Get client auth token
	 *
	 * @return string|mixed
	 */
	public static function get_client_auth_token() {
		$client     = self::get_client_credentials();
		$hash       = 'pecom_cache_auth_token_' . md5( $client['account'] );
		$auth_token = get_transient( $hash );

		if ( ! $auth_token ) {
			$parameters = array(
				'grant_type'    => 'client_credentials',
				'client_id'     => $client['account'],
				'client_secret' => $client['password'],
			);

			$request         = add_query_arg( $parameters, $client['api_url'] . 'v2/oauth/token' );
			$remote_response = wp_remote_post(
				$request,
				array(
					'timeout'   => 50,
					'sslverify' => false,
					'headers'   => array(
						'Content-Type' => 'application/x-www-form-urlencoded',
					),
				)
			);

			$error_msg = esc_html__( 'Could not get client auth token.', 'pecom-for-woocommerce' );

			if ( ! $remote_response ) {
				PECOM::log_it( $error_msg . ' ' . wp_json_encode( $remote_response ), 'error' );

				return false;
			}

			$response_code = wp_remote_retrieve_response_code( $remote_response );

			if ( 200 !== $response_code ) {
				PECOM::log_it( $error_msg . ' ERROR: ' . wp_json_encode( $response_code ) . ' ' . wp_remote_retrieve_body( $remote_response ), 'error' );

				return false;
			}

			$response_body = json_decode( wp_remote_retrieve_body( $remote_response ), true );

			if ( ! isset( $response_body['access_token'] ) ) {
				PECOM::log_it( $error_msg . ' ' . wp_json_encode( $response_body ), 'error' );

				return false;
			}

			$auth_token = $response_body['access_token'];

			set_transient( $hash, $auth_token, $response_body['expires_in'] );
		}

		return $auth_token;
	}

	/**
	 * Connect to Post API and get body for requested URL
	 *
	 * @param string  $url API url.
	 * @param array   $body Request body.
	 * @param string  $method Type.
	 * @param boolean $skip_cache Skip cash.
	 *
	 * @return bool|mixed|null
	 */
	public static function get_data_from_api( $url, $body = array(), $method = 'POST', $skip_cache = true ) {
		if ( ! $skip_cache ) {
			$client = self::get_client_credentials();
			$hash   = self::get_request_hash( $client['account'], $url, $body );
			$cache  = get_transient( $hash );

			if ( $cache ) {
				if ( isset( $cache['error'] ) ) {
					PECOM::log_it( esc_html__( 'CACHED!', 'pecom-for-woocommerce' ) . ' ' . esc_html__( 'API request error:', 'pecom-for-woocommerce' ) . ' ' . $url . ' ' . wp_json_encode( $cache, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . 'Body' . wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), 'error' );

					return false;
				}

				return $cache;
			}
		}

		$client_auth_token = self::get_client_auth_token();

		if ( ! $client_auth_token ) {
			return false;
		}

		$headers = array(
			'Accept'        => 'application/json;charset=UTF-8',
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $client_auth_token,
		);

		$remote_response = wp_remote_request(
			self::$api_url . $url,
			array(
				'headers' => $headers,
				'method'  => $method,
				'body'    => $body ? wp_json_encode( $body, JSON_UNESCAPED_UNICODE ) : '',
				'timeout' => 100, // must be that big for huge requests like getting PVZ list.
			)
		);

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

		$response_body = json_decode( wp_remote_retrieve_body( $remote_response ), true );

		if ( ! $skip_cache ) {
			set_transient( $hash, $response_body, DAY_IN_SECONDS );
		}

		if ( isset( $response_body['error'] ) || isset( $response_body['errors'] ) ) {
			PECOM::log_it( esc_html__( 'API request error:', 'pecom-for-woocommerce' ) . ' ' . $url . ' ' . wp_json_encode( $response_body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . 'Body' . wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), 'error' );

			return false;
		}

		if ( 'GET' !== $method ) {
			PECOM::log_it( esc_html__( 'API response:', 'pecom-for-woocommerce' ) . ' ' . $url . ' ' . wp_json_encode( $response_body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		}

		return $response_body;
	}

	/**
	 * Get hash by removing time relevant data
	 *
	 * @param string $account Account ID.
	 * @param string $url Request url.
	 * @param array  $body Request body.
	 *
	 * @return string
	 */
	public static function get_request_hash( $account, $url, $body ) {
		unset( $body['authLogin'] );
		unset( $body['secure'] );
		unset( $body['dateExecute'] );

		return 'pecom_cache_' . md5( $account . $url . wp_json_encode( $body ) );
	}
}
