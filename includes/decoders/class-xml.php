<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class XML extends \Content_Aggregator\Decoders\Base {
	protected $namespaces = array();

	public function __construct( array $tags, array $loop_path = array(), array $namespaces = array() ) {
		parent::__construct( $tags, $loop_path );
		$this->namespaces = $namespaces;
	}

	public function decode( $xml_content ) {
		$prev = libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content, 'SimpleXMLElement', LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		if ( false === $xml ) {
			return array();
		}
		$this->registerNamespaces( $xml );
		$loop_paths = is_array( $this->loop_paths ) ? $this->loop_paths : array( $this->loop_paths );
		$items = null;
		foreach ( $loop_paths as $lp ) {
			if ( '' === (string) $lp ) {
				continue;
			}
			$res = $xml->xpath( (string) $lp );
			if ( false === $res ) {
				continue;
			}
			if ( is_array( $res ) && ! empty( $res ) ) {
				$items = $res;
				break;
			}
		}
		$decoded_data = array();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$this->registerNamespaces( $item );
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
		foreach ( $this->tags as $tag => $xpaths ) {
			foreach ( (array) $xpaths as $xp ) {
				$nodes = $item->xpath( (string) $xp );
				if ( false === $nodes || empty( $nodes ) ) {
					continue;
				}
				$val = trim( (string) $nodes[0] );
				if ( '' !== $val ) {
					$item_data[ $tag ] = $val;
					break;
				}
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

	private function registerNamespaces( \SimpleXMLElement $node ): void {
		foreach ( $this->namespaces as $prefix => $namespace ) {
			$node->registerXPathNamespace( $prefix, $namespace );
		}
		$namespaces = $node->getDocNamespaces( true );
		if ( empty( $namespaces ) ) {
			$namespaces = array();
		}
		foreach ( $namespaces as $prefix => $namespaces ) {
			if ( empty( $prefix ) ) {
				$prefix = 'ns';
			}
			if ( ! isset( $this->namespaces[ $prefix ] ) ) {
				$node->registerXPathNamespace( $prefix, $namespace );
			}
		}
	}
}
