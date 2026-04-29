<?php

declare(strict_types=1);

namespace ContentAggregator\Tests\Decoders;

use Content_Aggregator\Decoders\JSON;
use ContentAggregator\Tests\TestCase;

final class JsonDecoderTest extends TestCase {
	public function test_decode_uses_first_matching_loop_path(): void {
		$decoder = new JSON(
			array(
				'date' => array( 'date' ),
				'title' => array( 'title.rendered' ),
				'content' => array( 'content.rendered' ),
				'url' => array( 'link' ),
			),
			array( 'missing.path', 'posts' )
		);

		$json = wp_json_encode(
			array(
				'posts' => array(
					array(
						'date' => '2026-04-01 10:00:00',
						'title' => array( 'rendered' => 'Post title' ),
						'content' => array( 'rendered' => '<p>Body</p>' ),
						'link' => 'https://example.com/post',
					),
				),
			)
		);
		$this->assertIsString( $json );

		$this->assertSame(
			array(
				array(
					'date' => '2026-04-01 10:00:00',
					'title' => 'Post title',
					'content' => '<p>Body</p>',
					'url' => 'https://example.com/post',
				),
			),
			$decoder->decode( $json )
		);
	}

	public function test_decode_serializes_nested_arrays_to_json_strings(): void {
		$decoder = new JSON(
			array(
				'title' => array( 'title' ),
				'image' => array( 'media' ),
			)
		);

		$json = wp_json_encode(
			array(
				array(
					'title' => 'Post title',
					'media' => array(
						'source_url' => 'https://example.com/image.jpg',
						'alt' => 'Alt text',
					),
				),
			)
		);
		$this->assertIsString( $json );

		$this->assertSame(
			array(
				array(
					'title' => 'Post title',
					'image' => '{"source_url":"https://example.com/image.jpg","alt":"Alt text"}',
				),
			),
			$decoder->decode( $json )
		);
	}
}
