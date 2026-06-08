<?php
/**
 * LuwiPress Amber — TouristTrip JSON-LD via the core LuwiPress Schema Registry.
 *
 * Registers a 'trip' schema type on tour products. When no manual schema meta
 * is stored, auto_data builds a TouristTrip node from the product's booking
 * config (price → Offer, duration → ISO8601, provider = the agency). Requires
 * the core LuwiPress plugin's Schema Registry; silently no-ops without it.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'WooCommerce' ) ) { return; }

add_action( 'luwipress_schema_registry_init', function ( $registry ) {
	if ( ! is_object( $registry ) || ! method_exists( $registry, 'register_type' ) ) {
		return;
	}
	$registry->register_type( 'trip', [
		'schema_type' => 'TouristTrip',
		'meta_key'    => '_luwipress_schema_trip',
		'contexts'    => [ 'post:product' ],
		'sanitizer'   => 'lwp_amber_sanitize_trip_schema',
		'renderer'    => 'lwp_amber_render_trip_schema',
		'auto_data'   => 'lwp_amber_auto_trip_schema',
		'description' => __( 'TouristTrip schema for bookable tour products.', 'luwipress-amber' ),
	] );
} );

/**
 * Auto-build TouristTrip data from a tour product. Returns [] for non-tour /
 * non-product contexts so the registry skips emission.
 *
 * @param array $context ['object_type','object_id','subtype']
 * @return array
 */
function lwp_amber_auto_trip_schema( $context ) {
	if ( empty( $context['object_type'] ) || 'post' !== $context['object_type'] || ( $context['subtype'] ?? '' ) !== 'product' ) {
		return [];
	}
	$pid = (int) $context['object_id'];
	if ( ! lwp_amber_is_tour( $pid ) ) {
		return [];
	}
	$cfg     = lwp_amber_tour_config( $pid );
	$product = wc_get_product( $pid );
	if ( empty( $cfg ) || ! $product ) {
		return [];
	}

	$data = [
		'name' => $cfg['name'],
		'url'  => get_permalink( $pid ),
	];

	$desc = $product->get_short_description() ?: $product->get_description();
	$desc = trim( wp_strip_all_tags( $desc ) );
	if ( $desc ) {
		$data['description'] = mb_substr( $desc, 0, 300 );
	}

	$img = get_the_post_thumbnail_url( $pid, 'large' );
	if ( $img ) { $data['image'] = $img; }

	if ( $cfg['per_person'] > 0 ) {
		$data['offers'] = [
			'price'         => (string) $cfg['per_person'],
			'priceCurrency' => $cfg['currency'],
			'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url'           => get_permalink( $pid ),
		];
	}

	$iso = lwp_amber_duration_to_iso8601( $cfg['duration'] );
	if ( $iso ) { $data['duration'] = $iso; }

	$data['provider'] = [
		'name' => get_bloginfo( 'name' ),
		'url'  => home_url( '/' ),
	];

	return $data;
}

/**
 * Sanitizer for manually-saved trip schema (recursive, URL-aware).
 *
 * @param mixed $data
 * @param array $context
 * @return array
 */
function lwp_amber_sanitize_trip_schema( $data, $context ) {
	return lwp_amber_sanitize_schema_recursive( $data );
}

function lwp_amber_sanitize_schema_recursive( $value ) {
	if ( is_array( $value ) ) {
		$out = [];
		foreach ( $value as $k => $v ) {
			$out[ sanitize_text_field( (string) $k ) ] = lwp_amber_sanitize_schema_recursive( $v );
		}
		return $out;
	}
	if ( is_string( $value ) && preg_match( '#^https?://#', $value ) ) {
		return esc_url_raw( $value );
	}
	return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
}

/**
 * Renderer — wrap the (auto or stored) data into a TouristTrip node.
 *
 * @param array $data
 * @param array $context
 * @return array|null
 */
function lwp_amber_render_trip_schema( $data, $context ) {
	if ( empty( $data ) || ! is_array( $data ) ) { return null; }

	$node = [ '@type' => 'TouristTrip' ];
	foreach ( [ 'name', 'description', 'url', 'image', 'duration' ] as $k ) {
		if ( ! empty( $data[ $k ] ) ) { $node[ $k ] = $data[ $k ]; }
	}
	if ( ! empty( $data['offers'] ) && is_array( $data['offers'] ) ) {
		$node['offers'] = array_merge( [ '@type' => 'Offer' ], $data['offers'] );
	}
	if ( ! empty( $data['provider'] ) && is_array( $data['provider'] ) ) {
		$node['provider'] = array_merge( [ '@type' => 'TravelAgency' ], $data['provider'] );
	}
	if ( ! empty( $data['itinerary'] ) && is_array( $data['itinerary'] ) ) {
		$node['itinerary'] = $data['itinerary'];
	}
	return $node;
}

/**
 * Best-effort "6 hours" / "3 days" / "90 minutes" → ISO 8601 duration.
 *
 * @param string $text
 * @return string  e.g. PT6H, P3D, PT90M — or '' when not parseable.
 */
function lwp_amber_duration_to_iso8601( $text ) {
	if ( ! preg_match( '/(\d+(?:\.\d+)?)\s*(hour|hr|day|min|minute)/i', (string) $text, $m ) ) {
		return '';
	}
	$n    = (float) $m[1];
	$unit = strtolower( $m[2] );
	if ( 'day' === $unit ) {
		return 'P' . (int) $n . 'D';
	}
	if ( in_array( $unit, [ 'min', 'minute' ], true ) ) {
		return 'PT' . (int) $n . 'M';
	}
	// hours
	if ( floor( $n ) === $n ) {
		return 'PT' . (int) $n . 'H';
	}
	return 'PT' . (int) ( $n * 60 ) . 'M';
}
