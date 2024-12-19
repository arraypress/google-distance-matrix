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
	 * API endpoint for the Distance Matrix API
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';

	/**
	 * Valid travel modes
	 *
	 * @var array<string>
	 */
	private const VALID_MODES = [
		'driving',
		'walking',
		'bicycling',
		'transit'
	];

	/**
	 * Valid units
	 *
	 * @var array<string>
	 */
	private const VALID_UNITS = [
		'metric',
		'imperial'
	];

	/**
	 * Valid avoid options
	 *
	 * @var array<string>
	 */
	private const VALID_AVOID = [
		'tolls',
		'highways',
		'ferries'
	];

	/**
	 * Valid traffic model options
	 *
	 * @var array<string>
	 */
	private const VALID_TRAFFIC_MODELS = [
		'best_guess',
		'pessimistic',
		'optimistic'
	];

	/**
	 * Default options for API requests
	 *
	 * @var array<string, string>
	 */
	private const DEFAULT_OPTIONS = [
		'mode'     => 'driving',
		'units'    => 'metric',
		'language' => 'en'
	];

	/**
	 * API key for Google Distance Matrix
	 *
	 * @var string
	 */
	private string $api_key;

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
	 * Current options for the client
	 *
	 * @var array<string, string|null>
	 */
	private array $options;

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
		$this->options          = self::DEFAULT_OPTIONS;
	}

	/**
	 * Set travel mode
	 *
	 * @param string $mode Travel mode (driving, walking, bicycling, transit)
	 *
	 * @return self
	 * @throws \InvalidArgumentException If invalid mode provided
	 */
	public function set_mode( string $mode ): self {
		if ( ! in_array( $mode, self::VALID_MODES ) ) {
			throw new \InvalidArgumentException( "Invalid mode. Must be one of: " . implode( ', ', self::VALID_MODES ) );
		}
		$this->options['mode'] = $mode;

		return $this;
	}

	/**
	 * Set units for distance
	 *
	 * @param string $units Units (metric, imperial)
	 *
	 * @return self
	 * @throws \InvalidArgumentException If invalid units provided
	 */
	public function set_units( string $units ): self {
		if ( ! in_array( $units, self::VALID_UNITS ) ) {
			throw new \InvalidArgumentException( "Invalid units. Must be one of: " . implode( ', ', self::VALID_UNITS ) );
		}
		$this->options['units'] = $units;

		return $this;
	}

	/**
	 * Set avoid options
	 *
	 * @param string|null $avoid Features to avoid (tolls, highways, ferries)
	 *
	 * @return self
	 * @throws \InvalidArgumentException If invalid avoid option provided
	 */
	public function set_avoid( ?string $avoid ): self {
		if ( $avoid !== null && ! in_array( $avoid, self::VALID_AVOID ) ) {
			throw new \InvalidArgumentException( "Invalid avoid option. Must be one of: " . implode( ', ', self::VALID_AVOID ) );
		}
		$this->options['avoid'] = $avoid;

		return $this;
	}

	/**
	 * Set language for results
	 *
	 * @param string $language Language code
	 *
	 * @return self
	 */
	public function set_language( string $language ): self {
		$this->options['language'] = $language;

		return $this;
	}

	/**
	 * Set traffic model
	 *
	 * @param string|null $model Traffic model (best_guess, pessimistic, optimistic)
	 *
	 * @return self
	 * @throws \InvalidArgumentException If invalid traffic model provided
	 */
	public function set_traffic_model( ?string $model ): self {
		if ( $model !== null && ! in_array( $model, self::VALID_TRAFFIC_MODELS ) ) {
			throw new \InvalidArgumentException( "Invalid traffic model. Must be one of: " . implode( ', ', self::VALID_TRAFFIC_MODELS ) );
		}
		$this->options['traffic_model'] = $model;

		return $this;
	}

	/**
	 * Reset options to defaults
	 *
	 * @return self
	 */
	public function reset_options(): self {
		$this->options = self::DEFAULT_OPTIONS;

		return $this;
	}

	/**
	 * Calculate distances between origins and destinations
	 *
	 * @param string|array $origins      Single origin or array of origins
	 * @param string|array $destinations Single destination or array of destinations
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

		// Merge instance options with provided options (provided options take precedence)
		$merged_options = array_merge(
			array_filter( $this->options, fn( $value ) => $value !== null ),
			$options
		);

		// Prepare request parameters
		$params = array_merge( $merged_options, [
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