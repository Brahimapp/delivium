<?php
/**
 * The store class
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The store class.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Store {

	/**
	 * Function that return vendor role.
	 *
	 * @param int $vendor vendor user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function vendor_role( $vendor ) {
		$result = '';
		switch ( $vendor ) {
			case 'dokan':
				$result = 'seller';
				break;
			case 'wcmp':
				$result = 'dc_vendor';
				break;
			case 'wcfm':
				$result = 'wcfm_vendor';
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Function that return vendor order meta.
	 *
	 * @param int $vendor vendor user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function vendor_order_meta( $vendor ) {
		$result = '';
		switch ( $vendor ) {
			case 'dokan':
				$result = '_dokan_vendor_id';
				break;
			case 'wcmp':
				$result = '_vendor_id';
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Function that return driver seller id.
	 *
	 * @param int $driver_id driver user id.
	 * @since 1.6.0
	 * @return string
	 */
	public function get_driver_seller( $driver_id ) {
		$seller_id = '';
		if ( has_filter( 'delivium_get_driver_seller' ) ) {
			$seller_id = apply_filters( 'delivium_get_driver_seller', $driver_id );
		}
		return $seller_id;
	}

	/**
	 * Function that return order seller id.
	 *
	 * @since 1.6.0
	 * @param object $order order.
	 * @return string
	 */
	public function order_seller( $order, $all_sellers = false ) {
		$result = '';
		$array  = array();
		global $wpdb;
		$order_id = $order->get_id();
		switch ( DELIVIUM_MULTIVENDOR ) {
			case 'dokan':
				if ( $all_sellers && $order->get_meta( 'has_sub_order' ) ) {
					$sub_orders = dokan_get_suborder_ids_by( $order_id );
					if ( ! empty( $sub_orders ) ) {
						foreach ( $sub_orders as $sub_order ) {
							$child_order = wc_get_order( $sub_order );
							$vendor_id   = $child_order->get_meta( '_dokan_vendor_id' );
							if ( ! in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
								$array[ $vendor_id ] = $vendor_id;
							}
						}
						$result = $array;
					}
				} else {
					// Return seller id.
					$result = $order->get_meta( '_dokan_vendor_id' );
				}
				break;
			case 'wcmp':
				if ( $all_sellers && $order->get_meta( 'has_wcmp_sub_order' ) ) {
					$sub_orders = get_wcmp_suborders( $order_id, false, false );
					if ( $sub_orders ) {
						foreach ( $sub_orders as $sub_order ) {
							$child_order = wc_get_order( $sub_order );
							$vendor_id   = $child_order->get_meta( '_vendor_id' );
							if ( ! in_array( $vendor_id, $array ) && '' !== $vendor_id ) {
								$array[ $vendor_id ] = $vendor_id;
							}
						}
						$result = $array;
					}
				} else {
					// Return seller id.
					$result = $order->get_meta( '_vendor_id' );
				}
				break;
			case 'wcfm':
				$sellers = $wpdb->get_results(
					$wpdb->prepare(
						'select vendor_id from ' . $wpdb->prefix . 'wcfm_marketplace_orders where order_id=%s',
						array( $order_id )
					)
				);
				if ( ! empty( $sellers ) ) {
					if ( $all_sellers ) {
						foreach ( $sellers as $seller ) {
							$seller_id = $seller->vendor_id;
							if ( ! in_array( $seller_id, $array ) ) {
								$array[ $seller_id ] = $seller_id;
							}
						}
						// Return sellers array.
						$result = $array;
					} else {
						// Return first seller id.
						$result = $sellers[0]->vendor_id;
					}
				}
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}

	/**
	 * Pickup option.
	 *
	 * @param object $order order object.
	 * @return statement
	 */
	public function get_pickup_type( $order ) {
		/**
		 * Pickup option types:
		 * store - store/vendor pickup location.
		 * customer - customer pickup location.
		 * post - saved pickup location.
		 */
		$result = 'store';
		// Pickup Filter.
		if ( has_filter( 'delivium_pickup_type' ) ) {
			$result = apply_filters( 'delivium_pickup_type', $result, $order );
		}
		return $result;
	}

	/**
	 * Pickup phone.
	 *
	 * @param object $order order object.
	 * @param object $seller_id seller number.
	 * @return statement
	 */
	public function get_pickup_phone( $order, $seller_id ) {
		$phone = $this->store_phone( $order, $seller_id );
		// Pickup phone filter.
		if ( has_filter( 'delivium_pickup_phone' ) ) {
			$phone = apply_filters( 'delivium_pickup_phone', $phone, $order );
		}
		return $phone;
	}

	/**
	 * Pickup address.
	 *
	 * @since 1.0.0
	 * @param string $format address format.
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function pickup_address( $format, $order, $seller_id ) {
		$address = $this->store_address( $format );
		if ( '' !== DELIVIUM_MULTIVENDOR ) {
			// store address.
			if ( '' !== $seller_id ) {
				if ( 'dokan' === DELIVIUM_MULTIVENDOR ) {
					if ( function_exists( 'dokan_get_seller_address' ) ) {
						$array = dokan_get_seller_address( $seller_id, true );
						if ( is_array( $array ) ) {
							$store_address_1 = $array['street_1'];
							$store_address_2 = $array['street_2'];
							$store_city      = $array['city'];
							$store_postcode  = $array['zip'];
							$store_country   = $array['country'];
							$store_state     = $array['state'];
							if ( '' !== $array['street_1'] ) {
								$address = delivium_format_address( $format, $array );
							}
						}
					}
				}
				if ( 'wcmp' === DELIVIUM_MULTIVENDOR ) {
					$array['street_1'] = get_user_meta( $seller_id, '_vendor_address_1', true );
					$array['street_2'] = get_user_meta( $seller_id, '_vendor_address_2', true );
					$array['city']     = get_user_meta( $seller_id, '_vendor_city', true );
					$array['zip']      = get_user_meta( $seller_id, '_vendor_postcode', true );
					$array['country']  = get_user_meta( $seller_id, '_vendor_country', true );
					$array['state']    = get_user_meta( $seller_id, '_vendor_state', true );
					if ( '' !== $array['street_1'] ) {
						$address = delivium_format_address( $format, $array );
					}
				}
				if ( 'wcfm' === DELIVIUM_MULTIVENDOR ) {
					$array['street_1'] = get_user_meta( $seller_id, '_wcfm_street_1', true );
					$array['street_2'] = get_user_meta( $seller_id, '_wcfm_street_2', true );
					$array['city']     = get_user_meta( $seller_id, '_wcfm_city', true );
					$array['zip']      = get_user_meta( $seller_id, '_wcfm_zip', true );
					$array['country']  = get_user_meta( $seller_id, '_wcfm_country', true );
					$array['state']    = get_user_meta( $seller_id, '_wcfm_state', true );
					if ( '' !== $array['street_1'] ) {
						$address = delivium_format_address( $format, $array );
					}
				}
			}
		}
		// Pickup address filter.
		if ( has_filter( 'delivium_pickup_address' ) ) {
			$address = apply_filters( 'delivium_pickup_address', $address, $format, $order );
		}
		return $address;
	}

	/**
	 * Store phone.
	 *
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function store_phone( $order, $seller_id ) {
		$phone = get_option( 'woocommerce_store_phone' );
		if ( '' !== DELIVIUM_MULTIVENDOR ) {
			if ( '' !== $seller_id ) {
				if ( 'dokan' === DELIVIUM_MULTIVENDOR ) {
					$phone = get_user_meta( $seller_id, 'dokan_profile_settings', true );
					if ( is_array( $phone ) ) {
						$phone = $phone['phone'];
					}
				}
				if ( 'wcmp' === DELIVIUM_MULTIVENDOR ) {
					$phone = get_user_meta( $seller_id, '_vendor_phone', true );
				}
				if ( 'wcfm' === DELIVIUM_MULTIVENDOR ) {
					$phone = get_user_meta( $seller_id, '_wcfm_phone', true );
				}
			}
		}
		return $phone;
	}

	/**
	 * Store name.
	 *
	 * @param object $order order object.
	 * @param int    $seller_id seller id.
	 * @return string
	 */
	public function store_name( $order, $seller_id ) {
		$store_name = get_option( 'woocommerce_store_name' );
		if ( '' !== DELIVIUM_MULTIVENDOR ) {
			if ( '' !== $seller_id ) {
				if ( 'dokan' === DELIVIUM_MULTIVENDOR ) {
					$store_user  = dokan()->vendor->get( $seller_id );
					$store_name = $store_user->get_shop_name();
				}
				if ( 'wcmp' === DELIVIUM_MULTIVENDOR ) {
					$vendor = get_wcmp_vendor( $seller_id );
					if ( $vendor ) {
						$store_name = $vendor->page_title;
					}
				}
				if ( 'wcfm' === DELIVIUM_MULTIVENDOR ) {
					$store_name = get_user_meta( $seller_id, 'store_name', true );
				}
			}
		}
		return $store_name;
	}

	/**
	 * Store email.
	 *
	 * @param int $seller_id seller id.
	 * @return string
	 */
	public function store_email( $seller_id ) {
		$store_email = get_option( 'woocommerce_email_from_address' );
		if ( '' !== DELIVIUM_MULTIVENDOR ) {
			if ( '' !== $seller_id ) {
				if ( 'dokan' === DELIVIUM_MULTIVENDOR ) {
					$store_user  = dokan()->vendor->get( $seller_id );
					$store_email = $store_user->get_email();
				}
				if ( 'wcmp' === DELIVIUM_MULTIVENDOR ) {
					$vendor = get_wcmp_vendor( $seller_id );
					if ( $vendor ) {
						$store_email = $vendor->user_data->user_email;
					}
				}
				if ( 'wcfm' === DELIVIUM_MULTIVENDOR ) {
					$store_email = get_user_meta( $seller_id, '_wcfm_email', true );
				}
			}
		}
		return $store_email;
	}

	/**
	 * Store address.
	 *
	 * @param string $format address format.
	 * @return string
	 */
	public function store_address( $format ) {
		$array = array(
			'street_1' => WC()->countries->get_base_address(),
			'street_2' => WC()->countries->get_base_address_2(),
			'city'     => WC()->countries->get_base_city(),
			'state'    => WC()->countries->get_base_state(),
			'country'  => WC()->countries->get_base_country(),
			'zip'      => WC()->countries->get_base_postcode(),
		);
		return delivium_format_address( $format, $array );
	}

	/**
	 * Country unit system.
	 *
	 * @return string
	 */
	public function country_unit_system() {
		$country = WC()->countries->get_base_country();
		$result  = 'METRIC';
		if ( 'US' === $country || 'GB' === $country ) {
			$result = 'IMPERIAL';
		}
		return $result;
	}

	public function delivium_store_name($order, $seller_id) {
		return $this->get_store_name($order, $seller_id);
	}

	public function delivium_store_phone($order, $seller_id) {
		return $this->get_store_phone($order, $seller_id);
	}

	public function delivium_store_address($order, $seller_id) {
		return $this->get_store_address($order, $seller_id);
	}
}
