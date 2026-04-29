<?php

namespace Content_Aggregator\Decoders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Base {
	/**
	 * Tags to extract from the XML.
	 *
	 * @var array
	 */
	protected $tags;

	/**
	 * XPaths to loop through items.
	 *
	 * @var array
	 */
	protected $loop_paths;

	/**
	 * Constructor.
	 *
	 * @param array  $tags     Tags for data extraction.
	 * @param string $loopPath XPath for looping through items.
	 */
	public function __construct( array $tags, array $loop_paths = array() ) {
		$this->tags = $tags;
		$this->loop_paths = $loop_paths;
	}

	/**
	 * Decode the data.
	 *
	 * @param string $data Data to decode.
	 *
	 * @return array Decoded data.
	 */
	abstract public function decode( $data );
}
