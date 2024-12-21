<?php
/**
 * Google Distance Matrix API Parameters Trait
 *
 * @package     ArrayPress\Google\DistanceMatrix
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\DistanceMatrix\Traits;

use InvalidArgumentException;
use WP_Error;

/**
 * Trait Parameters
 *
 * Manages parameters for the Google Distance Matrix API.
 *
 * @package ArrayPress\Google\DistanceMatrix
 */
trait Parameters {

	/**
	 * API key for Google Distance Matrix
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Cache settings
	 *
	 * @var array
	 */
	private array $cache_settings = [
		'enabled'    => true,
		'expiration' => DAY_IN_SECONDS
	];

	/**
	 * Valid travel modes
	 *
	 * @var array<string>
	 */
	private array $valid_modes = [
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
	private array $valid_units = [
		'metric',
		'imperial'
	];

	/**
	 * Valid avoid options
	 *
	 * @var array<string>
	 */
	private array $valid_avoid = [
		'tolls',
		'highways',
		'ferries'
	];

	/**
	 * Valid traffic model options
	 *
	 * @var array<string>
	 */
	private array $valid_traffic_models = [
		'best_guess',
		'pessimistic',
		'optimistic'
	];

	/**
	 * API options
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [
		'mode'           => 'driving',
		'units'          => 'metric',
		'language'       => 'en',
		'avoid'          => null,
		'traffic_model'  => null,
		'departure_time' => null,
		'arrival_time'   => null
	];

	/** API Key ******************************************************************/

	/**
	 * Set API key
	 *
	 * @param string $api_key The API key to use
	 *
	 * @return self
	 */
	public function set_api_key( string $api_key ): self {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->api_key;
	}

	/** Cache ********************************************************************/

	/**
	 * Set cache status
	 *
	 * @param bool $enable Whether to enable caching
	 *
	 * @return self
	 */
	public function set_cache_enabled( bool $enable ): self {
		$this->cache_settings['enabled'] = $enable;

		return $this;
	}

	/**
	 * Get cache status
	 *
	 * @return bool
	 */
	public function is_cache_enabled(): bool {
		return $this->cache_settings['enabled'];
	}

	/**
	 * Set cache expiration time
	 *
	 * @param int $seconds Cache expiration time in seconds
	 *
	 * @return self|WP_Error
	 */
	public function set_cache_expiration( int $seconds ) {
		if ( $seconds < 0 ) {
			return new WP_Error(
				'invalid_expiration',
				__( 'Cache expiration time cannot be negative', 'arraypress' )
			);
		}
		$this->cache_settings['expiration'] = $seconds;

		return $this;
	}

	/**
	 * Get cache expiration time in seconds
	 *
	 * @return int
	 */
	public function get_cache_expiration(): int {
		return $this->cache_settings['expiration'];
	}

	/**
	 * Get all cache settings
	 *
	 * @return array Current cache settings
	 */
	public function get_cache_settings(): array {
		return $this->cache_settings;
	}

	/** API Options *************************************************************/

	/**
	 * Set travel mode
	 *
	 * @param string $mode Travel mode (driving, walking, bicycling, transit)
	 *
	 * @return self
	 * @throws InvalidArgumentException If invalid mode provided
	 */
	public function set_mode( string $mode ): self {
		if ( ! in_array( $mode, $this->valid_modes ) ) {
			throw new InvalidArgumentException( "Invalid mode. Must be one of: " . implode( ', ', $this->valid_modes ) );
		}
		$this->options['mode'] = $mode;

		return $this;
	}

	/**
	 * Get current travel mode
	 *
	 * @return string
	 */
	public function get_mode(): string {
		return $this->options['mode'];
	}

	/**
	 * Set units for distance
	 *
	 * @param string $units Units (metric, imperial)
	 *
	 * @return self
	 * @throws InvalidArgumentException If invalid units provided
	 */
	public function set_units( string $units ): self {
		if ( ! in_array( $units, $this->valid_units ) ) {
			throw new InvalidArgumentException( "Invalid units. Must be one of: " . implode( ', ', $this->valid_units ) );
		}
		$this->options['units'] = $units;

		return $this;
	}

	/**
	 * Get current units
	 *
	 * @return string
	 */
	public function get_units(): string {
		return $this->options['units'];
	}

	/**
	 * Set avoid options
	 *
	 * @param string|null $avoid Features to avoid (tolls, highways, ferries)
	 *
	 * @return self
	 * @throws InvalidArgumentException If invalid avoid option provided
	 */
	public function set_avoid( ?string $avoid ): self {
		if ( $avoid !== null && ! in_array( $avoid, $this->valid_avoid ) ) {
			throw new InvalidArgumentException( "Invalid avoid option. Must be one of: " . implode( ', ', $this->valid_avoid ) );
		}
		$this->options['avoid'] = $avoid;

		return $this;
	}

	/**
	 * Get current avoid option
	 *
	 * @return string|null
	 */
	public function get_avoid(): ?string {
		return $this->options['avoid'];
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
	 * Get current language
	 *
	 * @return string
	 */
	public function get_language(): string {
		return $this->options['language'];
	}

	/**
	 * Set traffic model
	 *
	 * @param string|null $model Traffic model (best_guess, pessimistic, optimistic)
	 *
	 * @return self
	 * @throws InvalidArgumentException If invalid traffic model provided
	 */
	public function set_traffic_model( ?string $model ): self {
		if ( $model !== null && ! in_array( $model, $this->valid_traffic_models ) ) {
			throw new InvalidArgumentException( "Invalid traffic model. Must be one of: " . implode( ', ', $this->valid_traffic_models ) );
		}
		$this->options['traffic_model'] = $model;

		return $this;
	}

	/**
	 * Get current traffic model
	 *
	 * @return string|null
	 */
	public function get_traffic_model(): ?string {
		return $this->options['traffic_model'];
	}

	/**
	 * Set departure time
	 *
	 * @param int|null $timestamp Unix timestamp for departure time
	 *
	 * @return self
	 */
	public function set_departure_time( ?int $timestamp ): self {
		$this->options['departure_time'] = $timestamp;

		return $this;
	}

	/**
	 * Get current departure time
	 *
	 * @return int|null
	 */
	public function get_departure_time(): ?int {
		return $this->options['departure_time'];
	}

	/**
	 * Set arrival time
	 *
	 * @param int|null $timestamp Unix timestamp for arrival time
	 *
	 * @return self
	 */
	public function set_arrival_time( ?int $timestamp ): self {
		$this->options['arrival_time'] = $timestamp;

		return $this;
	}

	/**
	 * Get current arrival time
	 *
	 * @return int|null
	 */
	public function get_arrival_time(): ?int {
		return $this->options['arrival_time'];
	}

	/**
	 * Get all current options
	 *
	 * @return array<string, mixed>
	 */
	public function get_all_options(): array {
		return array_filter( $this->options, fn( $value ) => $value !== null );
	}

	/**
	 * Reset all options to defaults
	 *
	 * @return self
	 */
	public function reset_options(): self {
		$this->options = [
			'mode'           => 'driving',
			'units'          => 'metric',
			'language'       => 'en',
			'avoid'          => null,
			'traffic_model'  => null,
			'departure_time' => null,
			'arrival_time'   => null
		];

		return $this;
	}

}