<?php

declare(strict_types=1);

namespace ContentAggregator\Tests\Decoders;

use Content_Aggregator\Decoders\Factory;
use Content_Aggregator\Decoders\JSON;
use Content_Aggregator\Decoders\Registry;
use Content_Aggregator\Decoders\XML;
use ContentAggregator\Tests\TestCase;

final class RegistryFactoryTest extends TestCase {
	public function test_registry_returns_merged_defaults_and_overrides(): void {
		$GLOBALS['content_aggregator_test_options'][ Registry::OPTION_KEY ] = array(
			'1' => array(
				'endpoints' => array( '/custom-endpoint' ),
				'tags' => array(
					'title' => array( 'headline' ),
				),
			),
		);

		$all = Registry::all();

		$this->assertSame(
			array( '/custom-endpoint', '?rest_route=/wp/v2/posts&_embed' ),
			$all['1']['endpoints']
		);
		$this->assertSame( array( 'headline', 'title' ), $all['1']['tags']['title'] );
		$this->assertSame( array( 'date_gmt', 'date' ), $all['1']['tags']['date'] );
	}

	public function test_factory_returns_expected_decoder_instances(): void {
		$this->assertInstanceOf( XML::class, Factory::make( '0' ) );
		$this->assertInstanceOf( JSON::class, Factory::make( '1' ) );
		$this->assertInstanceOf( XML::class, Factory::make( '2' ) );
		$this->assertFalse( Factory::make( 'missing' ) );
	}

	public function test_ensure_defaults_installed_populates_missing_option(): void {
		Registry::ensure_defaults_installed();

		$this->assertArrayHasKey(
			Registry::OPTION_KEY,
			$GLOBALS['content_aggregator_test_options']
		);
		$this->assertArrayHasKey( '0', $GLOBALS['content_aggregator_test_options'][ Registry::OPTION_KEY ] );
	}
}
