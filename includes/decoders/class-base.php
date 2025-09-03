<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
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
