<?php

declare(strict_types=1);

namespace ContentAggregator\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class TestCase extends PhpUnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['content_aggregator_test_options'] = array();
	}
}
