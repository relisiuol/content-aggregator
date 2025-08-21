<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Atom extends \Content_Aggregator\Decoders\XML {
	public function __construct() {
		parent::__construct(
			array(
				'date' => 'updated',
				'title' => 'title',
				'content' => 'summary',
				'url' => 'link',
			),
			'entry'
		);
	}

	public function decode( $xml_content ) {
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $xml_content, 'SimpleXMLElement', LIBXML_NOCDATA );
		if ( false === $xml ) {
			libxml_clear_errors();
			return array();
		}
		$xml->registerXPathNamespace( 'content', 'http://purl.org/rss/1.0/modules/content/' );
		$decoded_data = array();
		if ( ! empty( $this->loop_path ) && ! empty( $xml->{$this->loop_path} ) ) {
			$items = $xml->{$this->loop_path};
			foreach ( $items as $item ) {
				$item_data = $this->extractItemData( $item );
				if ( ! empty( $item_data ) ) {
					$decoded_data[] = $item_data;
				}
			}
		}
		return $decoded_data;
	}
}
