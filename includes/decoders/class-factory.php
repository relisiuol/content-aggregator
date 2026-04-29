<?php
namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Factory {
	public static function make( $key ) {
		$config = Registry::get( $key );
		if ( ! $config ) {
			return false;
		}
		if ( 'xml' === $config['type'] ) {
			$namespaces = $config['namespaces'] ?? array();
			return new XML( $config['tags'], $config['loop_path'], $namespaces );
		}
		if ( 'json' === $config['type'] ) {
			return new JSON( $config['tags'], $config['loop_path'] );
		}
		return false;
	}
}
