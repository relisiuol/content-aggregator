<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class WP extends \Content_Aggregator\Decoders\JSON {
	public function __construct() {
		parent::__construct(
			array(
				'date' => 'date_gmt',
				'title' => 'title.rendered',
				'content' => 'content.rendered',
				'url' => 'link',
				'image' => '_embedded.wp:featuredmedia.0.source_url',
			),
			false
		);
	}
}
