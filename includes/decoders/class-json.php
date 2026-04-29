<?php

namespace Content_Aggregator\Decoders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JSON extends Base {
	public function decode( $json ) {
		$out = array();
		$decoded = json_decode( $json, true );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			$loop_paths = $this->loop_paths;
			if ( ! is_array( $loop_paths ) ) {
				$loop_paths = array( $loop_paths );
			}
			$items = null;
			if ( empty( $loop_paths ) ) {
				$items = $decoded;
			} else {
				foreach ( $loop_paths as $lp ) {
					$tmp = $decoded;
					if ( '' !== $lp ) {
						$tmp = $this->getByDotPath( $decoded, (string) $lp );
					}
					if ( is_array( $tmp ) ) {
						$items = $tmp;
						break;
					}
				}
			}
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$data = $this->extractItemData( $item );
					if ( $data ) {
						$out[] = $data;
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Extract data from a single item.
	 *
	 * @param $item The item from which to extract data.
	 *
	 * @return array The extracted item data.
	 */
	protected function extractItemData( $item ) {
		$item_data = array();
		foreach ( $this->tags as $tag => $paths ) {
			foreach ( (array) $paths as $path ) {
				$val = $this->getByDotPath( $item, (string) $path );
				$str = $this->toScalarStringOrNull( $val );
				if ( null !== $str && '' !== trim( $str ) ) {
					$item_data[ $tag ] = $str;
					break;
				}
			}
		}
		return $item_data;
	}

	protected function getByDotPath( $data, string $path ) {
		if ( '' === $path ) {
			return $data;
		}
		$parts = explode( '.', $path );
		$val = $data;
		foreach ( $parts as $part ) {
			if ( is_array( $val ) && array_key_exists( $part, $val ) ) {
				$val = $val[ $part ];
			} else {
				return null;
			}
		}
		return $val;
	}

	protected function toScalarStringOrNull( $val ) {
		if ( is_null( $val ) ) {
			return null;
		}
		if ( is_scalar( $val ) ) {
			return (string) $val;
		}
		return wp_json_encode( $val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}
