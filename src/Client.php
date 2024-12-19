<?php
/**
 * Google Distance Matrix API Client Class
 *
 * @package     ArrayPress\Google\DistanceMatrix
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\DistanceMatrix;

use WP_Error;

/**
 * Class Client
 *
 * A comprehensive utility class for interacting with the Google Distance Matrix API.
 */
class Client {

	/**
	 * API key for Google Distance Matrix
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Base URL for the Distance Matrix API
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

	/**
	 * Whether to enable response caching
	 *
	 * @var bool
	 */
	private bool $enable_cache;

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private int $cache_expiration;

	/**
	 * Initialize the Distance Matrix client
	 *
	 * @param string $api_key          API key for Google Distance Matrix
	 * @param bool   $enable_cache     Whether to enable caching (default: true)
	 * @param int    $cache_expiration Cache expiration in seconds (default: 24 hours)
	 */
	public function __construct( string $api_key, bool $enable_cache = true, int $cache_expiration = 86400 ) {
		$this->api_key          = $api_key;
		$this->enable_cache     = $enable_cache;
		$this->cache_expiration = $cache_expiration;
	}

	/**
	 * Calculate distances between origins and destinations
	 *
	 * @param array|string $origins      Array of origins or single origin
	 * @param array|string $destinations Array of destinations or single destination
	 * @param array        $options      Additional options for the request
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function calculate( $origins, $destinations, array $options = [] ) {
		// Prepare origins and destinations
		$origins      = is_array( $origins ) ? implode( '|', $origins ) : $origins;
		$destinations = is_array( $destinations ) ? implode( '|', $destinations ) : $destinations;

		// Generate cache key
		$cache_key = $this->get_cache_key( "matrix_{$origins}_{$destinations}_" . md5( serialize( $options ) ) );

		// Check cache
		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		// Prepare request parameters
		$params = array_merge( $options, [
			'origins'      => $origins,
			'destinations' => $destinations
		] );

		// Make request
		$response = $this->make_request( $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Cache response
		if ( $this->enable_cache ) {
			set_transient( $cache_key, $response, $this->cache_expiration );
		}

		return new Response( $response );
	}

	/**
	 * Make a request to the Distance Matrix API
	 *
	 * @param array $params Request parameters
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_request( array $params ) {
		$params['key'] = $this->api_key;

		// Add default parameters if not set
		$params = array_merge( [
			'units' => 'metric',
			'mode'  => 'driving',
		], $params );

		$url = add_query_arg( $params, self::API_ENDPOINT );

		$response = wp_remote_get( $url, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ]
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Distance Matrix API request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse Distance Matrix API response', 'arraypress' )
			);
		}

		if ( $data['status'] !== 'OK' ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'Distance Matrix API returned error: %s', 'arraypress' ),
					$data['status']
				)
			);
		}

		return $data;
	}

	/**
	 * Generate cache key
	 *
	 * @param string $identifier Cache identifier
	 *
	 * @return string Cache key
	 */
	private function get_cache_key( string $identifier ): string {
		return 'google_distance_matrix_' . md5( $identifier . $this->api_key );
	}

	/**
	 * Clear cached data
	 *
	 * @param string|null $identifier Optional specific cache to clear
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache( ?string $identifier = null ): bool {
		if ( $identifier !== null ) {
			return delete_transient( $this->get_cache_key( $identifier ) );
		}

		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_google_distance_matrix_' ) . '%';

		return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			) !== false;
	}

}