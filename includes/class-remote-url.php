<?php

namespace Content_Aggregator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Remote_Url {
	public const TIMEOUT = 10;
	private const MAX_RESPONSE_BYTES = 10485760;

	public static function validate( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}

		$is_private_or_reserved_host = self::is_private_or_reserved_host( $host );
		if ( $is_private_or_reserved_host && ! self::private_urls_allowed( $url, $host ) ) {
			return '';
		}

		if (
			! $is_private_or_reserved_host &&
			function_exists( 'wp_http_validate_url' ) &&
			! wp_http_validate_url( $url )
		) {
			return '';
		}

		return $url;
	}

	public static function request_args( array $headers = array(), array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'timeout'             => self::TIMEOUT,
				'redirection'         => 3,
				'reject_unsafe_urls'  => ! self::private_urls_allowed(),
				'limit_response_size' => self::MAX_RESPONSE_BYTES,
				'headers'             => array(),
			)
		);
		$args['headers'] = array_merge( $headers, (array) $args['headers'] );

		return $args;
	}

	public static function get_body( string $url, array $headers = array(), array $args = array() ) {
		$url = self::validate( $url );
		if ( '' === $url ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$private_url_allowed = is_string( $host ) && self::is_private_or_reserved_host( $host ) && self::private_urls_allowed( $url, $host );
		$request_args = self::request_args( $headers, $args );
		$response = $private_url_allowed
			? wp_remote_get( $url, $request_args )
			: wp_safe_remote_get( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 > $status_code || 300 <= $status_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		return is_string( $body ) ? $body : false;
	}

	public static function is_private_or_reserved_host( string $host ): bool {
		$host = trim( strtolower( $host ), "[] \t\n\r\0\x0B." );
		if ( '' === $host || 'localhost' === $host || ! str_contains( $host, '.' ) ) {
			return true;
		}

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return ! self::is_public_ip( $host );
		}

		foreach ( self::resolve_host_ips( $host ) as $ip ) {
			if ( ! self::is_public_ip( $ip ) ) {
				return true;
			}
		}

		return false;
	}

	private static function resolve_host_ips( string $host ): array {
		$ips = array();
		$ipv4 = gethostbynamel( $host );
		if ( is_array( $ipv4 ) ) {
			$ips = array_merge( $ips, $ipv4 );
		}

		if ( function_exists( 'dns_get_record' ) ) {
			$records = dns_get_record( $host, DNS_AAAA );
			if ( is_array( $records ) ) {
				foreach ( $records as $record ) {
					if ( ! empty( $record['ipv6'] ) ) {
						$ips[] = $record['ipv6'];
					}
				}
			}
		}

		return array_unique( $ips );
	}

	private static function is_public_ip( string $ip ): bool {
		return (bool) filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	private static function private_urls_allowed( string $url = '', string $host = '' ): bool {
		$allowed = false;
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$allowed = in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
		}
		if ( function_exists( 'apply_filters' ) ) {
			$allowed = (bool) apply_filters( 'content_aggregator_allow_private_remote_urls', $allowed, $url, $host );
		}

		return $allowed;
	}
}
