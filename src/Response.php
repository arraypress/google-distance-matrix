<?php
/**
 * Google Distance Matrix API Response Class
 *
 * @package     ArrayPress\Google\DistanceMatrix
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\DistanceMatrix;

/**
 * Class Response
 *
 * Handles and structures the response data from Google Distance Matrix API.
 */
class Response {

	/**
	 * Raw response data from the API
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize the response object
	 *
	 * @param array $data Raw response data from Distance Matrix API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get raw data array
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->data;
	}

	/**
	 * Get origin addresses
	 *
	 * @return array
	 */
	public function get_origins(): array {
		return $this->data['origin_addresses'] ?? [];
	}

	/**
	 * Get destination addresses
	 *
	 * @return array
	 */
	public function get_destinations(): array {
		return $this->data['destination_addresses'] ?? [];
	}

	/**
	 * Get all rows of the distance matrix
	 *
	 * @return array
	 */
	public function get_rows(): array {
		return $this->data['rows'] ?? [];
	}

	/**
	 * Get distance between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin (defaults to first origin)
	 * @param int $destination_index Index of the destination (defaults to first destination)
	 *
	 * @return array|null Distance data or null if not found
	 */
	public function get_distance( int $origin_index = 0, int $destination_index = 0 ): ?array {
		$element = $this->get_element( $origin_index, $destination_index );

		return $element ? $element['distance'] : null;
	}

	/**
	 * Get duration between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin (defaults to first origin)
	 * @param int $destination_index Index of the destination (defaults to first destination)
	 *
	 * @return array|null Duration data or null if not found
	 */
	public function get_duration( int $origin_index = 0, int $destination_index = 0 ): ?array {
		$element = $this->get_element( $origin_index, $destination_index );

		return $element ? $element['duration'] : null;
	}

	/**
	 * Get specific element from the matrix
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return array|null Element data or null if not found
	 */
	public function get_element( int $origin_index = 0, int $destination_index = 0 ): ?array {
		return $this->data['rows'][ $origin_index ]['elements'][ $destination_index ] ?? null;
	}

	/**
	 * Get the status of a specific element
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return string|null Status or null if not found
	 */
	public function get_element_status( int $origin_index = 0, int $destination_index = 0 ): ?string {
		$element = $this->get_element( $origin_index, $destination_index );

		return $element ? $element['status'] : null;
	}

	/**
	 * Get formatted distance between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return string|null Formatted distance or null if not found
	 */
	public function get_formatted_distance( int $origin_index = 0, int $destination_index = 0 ): ?string {
		$distance = $this->get_distance( $origin_index, $destination_index );

		return $distance ? $distance['text'] : null;
	}

	/**
	 * Get distance in meters between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return int|null Distance in meters or null if not found
	 */
	public function get_distance_meters( int $origin_index = 0, int $destination_index = 0 ): ?int {
		$distance = $this->get_distance( $origin_index, $destination_index );

		return $distance ? $distance['value'] : null;
	}

	/**
	 * Get formatted duration between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return string|null Formatted duration or null if not found
	 */
	public function get_formatted_duration( int $origin_index = 0, int $destination_index = 0 ): ?string {
		$duration = $this->get_duration( $origin_index, $destination_index );

		return $duration ? $duration['text'] : null;
	}

	/**
	 * Get duration in seconds between specific origin and destination
	 *
	 * @param int $origin_index      Index of the origin
	 * @param int $destination_index Index of the destination
	 *
	 * @return int|null Duration in seconds or null if not found
	 */
	public function get_duration_seconds( int $origin_index = 0, int $destination_index = 0 ): ?int {
		$duration = $this->get_duration( $origin_index, $destination_index );

		return $duration ? $duration['value'] : null;
	}

	/**
	 * Check if all elements in the matrix are OK
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		foreach ( $this->get_rows() as $row ) {
			foreach ( $row['elements'] as $element ) {
				if ( $element['status'] !== 'OK' ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get all distances in a simple array format
	 *
	 * @return array Array of distances with origin and destination indices
	 */
	public function get_all_distances(): array {
		$distances = [];
		foreach ( $this->get_rows() as $i => $row ) {
			foreach ( $row['elements'] as $j => $element ) {
				if ( $element['status'] === 'OK' ) {
					$distances[] = [
						'origin_index'      => $i,
						'destination_index' => $j,
						'origin'            => $this->get_origins()[ $i ],
						'destination'       => $this->get_destinations()[ $j ],
						'distance'          => $element['distance'],
						'duration'          => $element['duration']
					];
				}
			}
		}

		return $distances;
	}

	/**
	 * Find the nearest destination to an origin
	 *
	 * @param int $origin_index Index of the origin (defaults to first origin)
	 *
	 * @return array|null Array with destination info or null if none found
	 */
	public function find_nearest_destination( int $origin_index = 0 ): ?array {
		$row = $this->data['rows'][ $origin_index ]['elements'] ?? [];
		if ( empty( $row ) ) {
			return null;
		}

		$nearest      = null;
		$min_distance = PHP_INT_MAX;

		foreach ( $row as $j => $element ) {
			if ( $element['status'] === 'OK' && $element['distance']['value'] < $min_distance ) {
				$min_distance = $element['distance']['value'];
				$nearest      = [
					'destination_index' => $j,
					'destination'       => $this->get_destinations()[ $j ],
					'distance'          => $element['distance'],
					'duration'          => $element['duration']
				];
			}
		}

		return $nearest;
	}

}