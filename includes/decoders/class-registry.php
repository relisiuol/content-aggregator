<?php
namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Registry {
	const OPTION_KEY = 'content_aggregator_parsers';

	public static function defaults() {
		return array(
			'0'  => array(
				'id'         => 'rss',
				'name'       => 'RSS',
				'type'       => 'xml',
				'loop_path'  => array(
					'/rss/channel/item',
					'/channel/item',
					'//item',
				),
				'tags'       => array(
					'date'    => array(
						'pubDate',
						'dc:date',
						'updated',
					),
					'title'   => array(
						'title',
					),
					'content' => array(
						'content:encoded',
						'description',
					),
					'url'     => array(
						'link',
					),
				),
				'namespaces' => array(
					'content' => 'http://purl.org/rss/1.0/modules/content/',
					'dc'      => 'http://purl.org/dc/elements/1.1/',
				),
				'endpoints'  => array(
					'/feed',
					'/rss',
				),
			),
			'1'   => array(
				'id'         => 'wp',
				'name'       => 'WordPress',
				'type'       => 'json',
				'loop_path'  => array(),
				'tags'       => array(
					'date'    => array(
						'date_gmt',
						'date',
					),
					'title'   => array(
						'title.rendered',
						'title',
					),
					'content' => array(
						'content.rendered',
						'excerpt.rendered',
						'content',
					),
					'url'     => array(
						'link',
						'guid.rendered',
					),
					'image'   => array(
						'_embedded.wp:featuredmedia.0.source_url',
					),
				),
				'endpoints'  => array(
					'/wp-json/wp/v2/posts?_embed',
					'?rest_route=/wp/v2/posts&_embed',
				),
			),
			'2' => array(
				'id'         => 'atom',
				'name'       => 'Atom RSS',
				'type'       => 'xml',
				'loop_path'  => array(
					'/atom:feed/atom:entry',
					'//atom:entry',
					'/feed/entry',
					'//entry',
				),
				'tags'       => array(
					'date'    => array(
						'atom:updated',
						'atom:published',
						'updated',
						'published',
					),
					'title'   => array(
						'atom:title',
						'title',
					),
					'content' => array(
						'atom:content',
						'atom:summary',
						'content',
						'summary',
					),
					'url'     => array(
						'atom:link[@rel="alternate"]/@href',
						'atom:link/@href',
						'link[@rel="alternate"]/@href',
						'link/@href',
						'link',
					),
				),
				'namespaces' => array(
					'atom'    => 'http://www.w3.org/2005/Atom',
					'content' => 'http://purl.org/rss/1.0/modules/content/',
				),
				'endpoints'  => array(
					'/feed',
					'/atom',
				),
			),
		);
	}

	public static function all() {
		$stored = get_option( self::OPTION_KEY );
		$defaults = self::defaults();
		$merged = $defaults;
		if ( is_array( $stored ) ) {
			$merged = array_replace_recursive( $defaults, $stored );
		}
		return $merged;
	}

	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function ensure_defaults_installed() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::defaults(), '', 'no' );
		}
	}
}
