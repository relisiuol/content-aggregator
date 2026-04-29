<?php
namespace Content_Aggregator\Decoders;

if ( ! defined( 'ABSPATH' ) ) {
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
