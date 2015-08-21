<?php
/**
 * Compatibility with other extensions.
 *
 * @class 	WCCSubs_Integrations
 * @version 1.0.0
 */

class WCCSubs_Integrations {

	public static function init() {

		// Bundles
		if ( class_exists( 'WC_Bundles' ) ) {
			add_filter( 'wccsubs_show_cart_item_options', __CLASS__ . '::hide_bundled_item_options', 10, 3 );
			add_filter( 'wccsubs_subscription_schemes', __CLASS__ . '::get_bundled_item_schemes', 10, 3 );
			add_filter( 'wccsubs_subscription_schemes', __CLASS__ . '::get_bundle_schemes', 10, 3 );
			add_action( 'wccsubs_updated_cart_item_scheme_id', __CLASS__ . '::bundled_item_scheme_id', 10, 3 );
		}

		// Composites
		if ( class_exists( 'WC_Composite_Products' ) ) {

		}

		// Mix n Match
		if ( class_exists( 'WC_Mix_and_Match' ) ) {

		}
	}

	/**
	 * Bundled items inherit the active subscription scheme id from their parent.
	 *
	 * @param  string $scheme_id
	 * @param  array  $cart_item
	 * @param  string $cart_item_key
	 * @return string
	 */
	public static function bundled_item_scheme_id( $scheme_id, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ 'bundled_by' ] ) ) {
			$container_key = $cart_item[ 'bundled_by' ];
			if ( isset( WC()->cart->cart_contents[ $container_key ] ) ) {

				$container_cart_item = WC()->cart->cart_contents[ $container_key ];

				if ( self::overrides_child_schemes( $container_cart_item ) && isset( $container_cart_item[ 'wccsub_data' ][ 'active_subscription_scheme_id' ] ) ) {
					$scheme_id = $container_cart_item[ 'wccsub_data' ][ 'active_subscription_scheme_id' ];
				}
			}
		}

		return $scheme_id;
	}

	/**
	 * Bundled cart items inherit the subscription schemes of their parent if:
	 *  - parent is statically priced, or
	 *  - parent has subscription schemes defined at product-level.
	 *
	 * @param  array  $schemes
	 * @param  array  $cart_item
	 * @param  string $scope
	 * @return array
	 */
	public static function get_bundled_item_schemes( $schemes, $cart_item, $scope ) {

		if ( ! empty( $cart_item[ 'bundled_by' ] ) ) {
			$container_key = $cart_item[ 'bundled_by' ];
			if ( isset( WC()->cart->cart_contents[ $container_key ] ) ) {

				$container_cart_item = WC()->cart->cart_contents[ $container_key ];

				if ( self::overrides_child_schemes( $container_cart_item ) ) {
					$schemes = WCCSubs_Schemes::get_subscription_schemes( $container_cart_item, $scope );
				}
			}
		}

		return $schemes;
	}

	/**
	 * Subscription schemes attached on a Product Bundle should not work if the bundle contains a non-convertible product, such as a "legacy" subscription.
	 *
	 * @param  array  $schemes
	 * @param  array  $cart_item
	 * @param  string $scope
	 * @return array
	 */
	public static function get_bundle_schemes( $schemes, $cart_item, $scope ) {

		if ( ! empty( $cart_item[ 'bundled_items' ] ) ) {
			$container = $cart_item[ 'data' ];
			if ( $container->product_type === 'bundle' && $container->contains_sub() ) {
				$schemes = array();
			}
		}

		return $schemes;
	}

	/**
	 * Hide bundled cart item subscription options if:
	 *  - bundle has a static price, or
	 *  - bundle has subscription schemes defined at bundle-level.
	 *
	 * @param  boolean $show
	 * @param  array   $cart_item
	 * @param  string  $cart_item_key
	 * @return boolean
	 */
	public static function hide_bundled_item_options( $show, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item[ 'bundled_by' ] ) ) {
			$container_key = $cart_item[ 'bundled_by' ];
			if ( isset( WC()->cart->cart_contents[ $container_key ] ) ) {

				$container_cart_item = WC()->cart->cart_contents[ $container_key ];

				if ( self::overrides_child_schemes( $container_cart_item ) ) {
					$show = false;
				}
			}
		}

		return $show;
	}

	/**
	 * Return inherited bundled cart item sub schemes from container.
	 *
	 * @param  array $cart_item
	 * @return array
	 */
	public static function overrides_child_schemes( $cart_item ) {

		$container = $cart_item[ 'data' ];
		$overrides = true;

		remove_filter( 'wccsubs_subscription_schemes', __CLASS__ . '::get_bundle_schemes', 10, 3 );
		$bundle_schemes = WCCSubs_Schemes::get_subscription_schemes( $cart_item );
		add_filter( 'wccsubs_subscription_schemes', __CLASS__ . '::get_bundle_schemes', 10, 3 );

		if ( empty( $bundle_schemes ) ) {
			$overrides = false;
		}

		return $overrides;
	}
}

WCCSubs_Integrations::init();
