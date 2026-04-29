<?php

declare(strict_types=1);

namespace ContentAggregator\Tests\Decoders;

use ContentAggregator\Tests\TestCase;

final class AutoDetectorTest extends TestCase {
	public function test_join_base_and_endpoint_handles_paths_and_query_endpoints(): void {
		$this->assertSame(
			'https://example.com/feed',
			AutoDetectorProbe::joinBaseAndEndpoint( 'https://example.com', '/feed' )
		);
		$this->assertSame(
			'https://example.com?rest_route=/wp/v2/posts&_embed',
			AutoDetectorProbe::joinBaseAndEndpoint( 'https://example.com', '?rest_route=/wp/v2/posts&_embed' )
		);
		$this->assertSame(
			'https://example.com/blog?foo=bar&rest_route=/wp/v2/posts',
			AutoDetectorProbe::joinBaseAndEndpoint( 'https://example.com/blog?foo=bar', '?rest_route=/wp/v2/posts' )
		);
	}

	public function test_abs_url_resolves_relative_urls(): void {
		$this->assertSame(
			'https://cdn.example.com/feed',
			AutoDetectorProbe::absoluteUrl( 'https://example.com/blog', '//cdn.example.com/feed' )
		);
		$this->assertSame(
			'https://example.com/feed',
			AutoDetectorProbe::absoluteUrl( 'https://example.com/blog', '/feed' )
		);
		$this->assertSame(
			'https://example.com/blog/feed',
			AutoDetectorProbe::absoluteUrl( 'https://example.com/blog', 'feed' )
		);
	}

	public function test_find_feed_link_returns_absolute_rss_link(): void {
		$html = <<<'HTML'
<!doctype html>
<html>
	<head>
		<link rel="alternate" type="application/rss+xml" href="/feed" />
	</head>
	<body></body>
</html>
HTML;

		$this->assertSame(
			'https://example.com/feed',
			AutoDetectorProbe::findFeedLink( $html, 'https://example.com/blog' )
		);
	}
}
