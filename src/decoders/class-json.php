<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there!  I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class JSON extends \Content_Aggregator\Decoders\Base {
	public function decode( $json ) {
		$decoded = json_decode( $json, true );
		$decoded_data = array();
		if ( ! empty( $decoded ) ) {
			foreach ( $decoded as $item ) {
				$item_data = $this->extractItemData( $item );
				if ( ! empty( $item_data ) ) {
					$decoded_data[] = $item_data;
				}
			}
		}
		return $decoded_data;
	}

	/**
	 * Extract data from a single item.
	 *
	 * @param $item The item from which to extract data.
	 *
	 * @return array The extracted item data.
	 */
	protected function extractItemData( $item ) {
		$item_data = array();
		foreach ( $this->tags as $tag => $path ) {
			$path_parts = explode( '.', $path );
			$value = $item;
			foreach ( $path_parts as $part ) {
				if ( is_array( $value ) && isset( $value[ $part ] ) ) {
					$value = $value[ $part ];
				} else {
					$value = null;
					break;
				}
			}
			if ( null !== $value ) {
				$item_data[ $tag ] = $value;
			}
		}
		return $item_data;
	}
}
