<?php

namespace Content_Aggregator\Decoders;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Auto_Detector {
	const HEADERS = array(
		'User-Agent'      => 'Content-Aggregator/1.0 (+https://example.com)',
		'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language' => 'en-us,en;q=0.5',
		'Accept-Encoding' => 'gzip,deflate',
		'Accept-Charset'  => 'utf-8,iso-8859-1;q=0.9,*;q=0.8',
		'Keep-Alive'      => '110',
		'Connection'      => 'keep-alive',
		'Cache-Control'   => 'max-age=0',
	);

	public static function detect( string $base_url ): ?array {
		$configs = \Content_Aggregator\Decoders\Registry::all();
		foreach ( $configs as $key => $config ) {
			$endpoints = (array) ( $config['endpoints'] ?? array() );
			foreach ( $endpoints as $endpoint ) {
				$final = self::join_base_and_endpoint( $base_url, (string) $endpoint );
				$body  = self::http_get_body( $final );
				if ( false === $body ) {
					continue;
				}
				$decoder = \Content_Aggregator\Decoders\Factory::make( $key );
				$data = $decoder->decode( $body );
				if ( ! empty( $data ) ) {
					return array(
						'url' => $final,
						'type' => $key,
						'success' => 1,
					);
				}
			}
		}

		$root = self::http_get_body( $base_url );
		if ( false !== $root ) {
			$rss_like = self::find_feed_link( $root, $base_url );
			if ( $rss_like ) {
				$feed = self::http_get_body( $rss_like );
				if ( false !== $feed ) {
					foreach ( array( '0', '2' ) as $key ) {
						$decoder = \Content_Aggregator\Decoders\Factory::make( $key );
						$data    = $decoder->decode( $feed );
						if ( ! empty( $data ) ) {
							return array(
								'url' => $rss_like,
								'type' => $key,
								'success' => 1,
							);
						}
					}
				}
			}
		}
		return null;
	}

	protected static function http_get_body( string $url ) {
		try {
			$resp = \WpOrg\Requests\Requests::get( $url, self::HEADERS );
			if ( $resp && isset( $resp->body ) ) {
				return $resp->body;
			}
			 return false;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	protected static function join_base_and_endpoint( string $base, string $endpoint ): string {
		$has_query_in_base = ( false !== strpos( $base, '?' ) );
		$is_endpoint_path = ( 0 === strpos( $endpoint, '/' ) );
		if ( $is_endpoint_path ) {
			return rtrim( $base, '/' ) . '/' . ltrim( $endpoint, '/' );
		}
		$sep = $has_query_in_base ? '&' : '?';
		return rtrim( $base, '/' ) . $sep . ltrim( $endpoint, '?&' );
	}

	protected static function find_feed_link( string $html, string $base ): ?string {
		$prev = libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$links = $doc->getElementsByTagName( 'link' );
		foreach ( $links as $link ) {
			if ( $link->getAttribute( 'type' ) === 'application/rss+xml' || $link->getAttribute( 'type' ) === 'application/atom+xml' ) {
				$href = $link->getAttribute( 'href' );
				return self::abs_url( $base, html_entity_decode( $href ) );
			}
		}
		return null;
	}

	protected static function abs_url( string $base, string $rel ): string {
		if ( preg_match( '#^https?://#i', $rel ) ) {
			return $rel;
		}
		$scheme = '';
		if ( 0 === strpos( $rel, '//' ) ) {
			$scheme = 'https';
			$test_scheme = wp_parse_url( $base, PHP_URL_SCHEME );
			if ( $test_scheme ) {
				$scheme = $test_scheme;
			}
			return $scheme . ':' . $rel;
		}
		if ( 0 === strpos( $rel, '/' ) ) {
			$parts  = wp_parse_url( $base );
			$scheme = $parts['scheme'] ?? 'https';
			$host   = $scheme . '://' . ( $parts['host'] ?? '' );
			$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
			return $host . $port . $rel;
		}
		return rtrim( $base, '/' ) . '/' . ltrim( $rel, '/' );
	}
}
