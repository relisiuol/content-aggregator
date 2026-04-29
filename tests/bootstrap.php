<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../' );
define( 'CONTENT_AGGREGATOR_DIR', __DIR__ . '/../' );

$GLOBALS['content_aggregator_test_options'] = array();

if ( ! function_exists( 'add_action' ) ) {
	function add_action() {
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key ) {
		return $GLOBALS['content_aggregator_test_options'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( string $key, $value ) {
		$GLOBALS['content_aggregator_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		return -1 === $component ? parse_url( $url ) : parse_url( $url, $component );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, int $flags = 0, int $depth = 512 ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		return json_encode( $value, $flags, $depth );
	}
}

require_once __DIR__ . '/../includes/vendor/wordpress-autoload.php';
require_once __DIR__ . '/TestCase.php';
