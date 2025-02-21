<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there!  I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class RSS extends \Content_Aggregator\Decoders\XML {
	public function __construct() {
		parent::__construct(
			array(
				'date' => 'pubDate',
				'title' => 'title',
				'content' => 'description',
				'url' => 'link',
			),
			'/rss/channel/item'
		);
	}
}
