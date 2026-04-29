<?php

namespace Content_Aggregator\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Parsers {
	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
	}
}
