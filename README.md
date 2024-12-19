# Google Distance Matrix API for WordPress

A PHP library for integrating with the Google Distance Matrix API in WordPress. This library provides robust distance and time calculations between multiple origins and destinations with support for WordPress transient caching and WP_Error handling.

## Features

- 📊 **Distance Calculations**: Calculate distances between multiple origins and destinations
- ⏱️ **Travel Times**: Get accurate travel duration with traffic considerations
- 🚗 **Multiple Modes**: Support for driving, walking, cycling, and transit modes
- 🌍 **International**: Support for distance calculations worldwide
- 📏 **Unit Flexibility**: Support for both metric and imperial measurements
- 🔄 **Response Parsing**: Clean response object for easy data access
- ⚡ **WordPress Integration**: Native transient caching and WP_Error support
- 🛡️ **Type Safety**: Full type hinting and strict types
- 🚦 **Traffic Data**: Optional real-time traffic consideration
- 🗺️ **Route Options**: Support for route restrictions and preferences

## Requirements

- PHP 7.4 or later
- WordPress 6.7.1 or later
- Google Distance Matrix API key

## Installation

Install via Composer:

```bash
composer require arraypress/google-distance-matrix
```

## Basic Usage

```php
use ArrayPress\Google\DistanceMatrix\Client;

// Initialize client with your API key
$client = new Client( 'your-google-api-key' );

// Calculate distance between two points
$result = $client->calculate(
    '1600 Amphitheatre Parkway, Mountain View, CA',
    'Googleplex, Mountain View, CA'
);

if ( ! is_wp_error( $result ) ) {
    // Get distance and duration
    $distance = $result->get_formatted_distance( 0, 0 );
    $duration = $result->get_formatted_duration( 0, 0 );
    
    echo "Distance: {$distance}\n";
    echo "Duration: {$duration}\n";
    
    // Get raw values in meters/seconds
    $meters = $result->get_distance_meters( 0, 0 );
    $seconds = $result->get_duration_seconds( 0, 0 );
}
```

## Multiple Origins/Destinations

```php
// Calculate distances between multiple points
$origins = [
    'San Francisco, CA',
    'Los Angeles, CA'
];

$destinations = [
    'Las Vegas, NV',
    'Phoenix, AZ',
    'San Diego, CA'
];

$result = $client->calculate( $origins, $destinations, [
    'mode' => 'driving',
    'units' => 'imperial'
]);

if (!is_wp_error( $result)) {
    // Get all calculated distances
    $distances = $result->get_all_distances();
    foreach ( $distances as $route) {
        echo "From: {$route['origin']}\n";
        echo "To: {$route['destination']}\n";
        echo "Distance: {$route['distance']['text']}\n";
        echo "Duration: {$route['duration']['text']}\n\n";
    }
}
```

### Working with Options

```php
$result = $client->calculate(
    'New York, NY',
    'Boston, MA',
    [
        'mode' => 'driving',           // driving, walking, bicycling, transit
        'units' => 'imperial',         // metric or imperial
        'avoid' => 'tolls',           // tolls, highways, ferries
        'language' => 'en',           // response language
        'traffic_model' => 'best_guess' // best_guess, pessimistic, optimistic
    ]
);
```

### Handling Responses with Caching

```php
// Initialize with custom cache duration (1 hour = 3600 seconds)
$client = new Client('your-api-key', true, 3600);

// Results will be cached
$result = $client->calculate( 'New York, NY', 'Boston, MA') ;

// Clear specific cache
$client->clear_cache( 'matrix_New York, NY_Boston, MA' );

// Clear all distance matrix caches
$client->clear_cache();
```

## API Methods

### Client Methods

* `calculate( $origins, $destinations, $options = [] )`: Calculate distances between origins and destinations
* `clear_cache( $identifier = null )`: Clear cached responses

### Response Methods

#### Distance Methods
* `get_distance( $origin_index, $destination_index )`: Get distance data for specific pair
* `get_duration( $origin_index, $destination_index )`: Get duration data for specific pair
* `get_formatted_distance( $origin_index, $destination_index )`: Get formatted distance string
* `get_formatted_duration( $origin_index, $destination_index )`: Get formatted duration string
* `get_distance_meters( $origin_index, $destination_index )`: Get distance in meters
* `get_duration_seconds( $origin_index, $destination_index )`: Get duration in seconds

#### Data Access Methods
* `get_origins()`: Get array of origin addresses
* `get_destinations()`: Get array of destination addresses
* `get_all_distances()`: Get all calculated distances in array format
* `get_element( $origin_index, $destination_index )`: Get specific matrix element
* `get_element_status( $origin_index, $destination_index )`: Get status of specific calculation
* `is_complete()`: Check if all calculations were successful
* `find_nearest_destination( $origin_index )`: Find nearest destination to specific origin

## Use Cases

* **Delivery Planning**: Calculate delivery routes and times
* **Shipping Costs**: Distance-based shipping calculations
* **Service Areas**: Define and check service coverage areas
* **Route Optimization**: Find optimal routes for multiple destinations
* **Travel Time Estimation**: Accurate travel time calculations
* **Fleet Management**: Support for vehicle routing and planning
* **Coverage Analysis**: Analyze service area coverage
* **Location Planning**: Optimize location selection based on distance

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/google-distance-matrix)
- [Issue Tracker](https://github.com/arraypress/google-distance-matrix/issues)