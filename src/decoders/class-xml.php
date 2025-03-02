<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class XML extends \Content_Aggregator\Decoders\Base {
	public function decode( $xml_content ) {
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content, 'SimpleXMLElement', LIBXML_NOCDATA );
		if ( false === $xml ) {
			libxml_clear_errors();
			return array();
		}
		$xml->registerXPathNamespace( 'content', 'http://purl.org/rss/1.0/modules/content/' );
		$decoded_data = array();
		if ( ! empty( $this->loop_path ) ) {
			$items = $xml->xpath( $this->loop_path );
			foreach ( $items as $item ) {
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
	 * @param \SimpleXMLElement $item The item from which to extract data.
	 *
	 * @return array The extracted item data.
	 */
	protected function extractItemData( $item ) {
		$item_data = array();
		foreach ( $this->tags as $tag => $path ) {
			$path_parts = explode( '.', $path );
			$value = $item;
			foreach ( $path_parts as $part ) {
				if ( $value ) {
					$value = $value->xpath( $part );
				} else {
					$value = null;
					break;
				}
			}
			if ( null !== $value ) {
				$item_data[ $tag ] = $value ? (string) $value[0] : '';
			}
		}
		return $item_data;
	}

	/**
	 * Convert a date string into a WordPress compatible date format.
	 *
	 * @param string $date_str The date string.
	 *
	 * @return string The formatted date.
	 */
	protected function convertDate( $date_str ) {
		$date = date_create_from_format( DATE_RSS, $date_str );
		return $date ? $date->format( 'Y-m-d H:i:s' ) : '';
	}
}
