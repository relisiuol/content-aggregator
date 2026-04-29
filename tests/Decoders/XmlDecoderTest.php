<?php

declare(strict_types=1);

namespace ContentAggregator\Tests\Decoders;

use Content_Aggregator\Decoders\XML;
use ContentAggregator\Tests\TestCase;

final class XmlDecoderTest extends TestCase {
	public function test_decode_extracts_rss_content_with_namespaces(): void {
		$decoder = new XML(
			array(
				'date' => array( 'pubDate' ),
				'title' => array( 'title' ),
				'content' => array( 'content:encoded', 'description' ),
				'url' => array( 'link' ),
			),
			array( '/rss/channel/item' ),
			array(
				'content' => 'http://purl.org/rss/1.0/modules/content/',
			)
		);

		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<item>
			<title>Post title</title>
			<link>https://example.com/post</link>
			<pubDate>Sat, 26 Apr 2026 09:30:00 +0000</pubDate>
			<content:encoded><![CDATA[<p>Body</p>]]></content:encoded>
		</item>
	</channel>
</rss>
XML;

		$this->assertSame(
			array(
				array(
					'date' => 'Sat, 26 Apr 2026 09:30:00 +0000',
					'title' => 'Post title',
					'content' => '<p>Body</p>',
					'url' => 'https://example.com/post',
				),
			),
			$decoder->decode( $xml )
		);
	}

	public function test_decode_returns_empty_array_for_invalid_xml(): void {
		$decoder = new XML(
			array(
				'title' => array( 'title' ),
			),
			array( '//item' )
		);

		$this->assertSame( array(), $decoder->decode( '<rss><channel>' ) );
	}

	public function test_decode_extracts_atom_content_with_default_namespace(): void {
		$decoder = new XML(
			array(
				'date'    => array( 'atom:updated', 'atom:published' ),
				'title'   => array( 'atom:title' ),
				'content' => array( 'atom:content', 'atom:summary' ),
				'url'     => array( 'atom:link[@rel="alternate"]/@href', 'atom:link/@href' ),
			),
			array( '/atom:feed/atom:entry' ),
			array(
				'atom' => 'http://www.w3.org/2005/Atom',
			)
		);

		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<entry>
		<title>Atom title</title>
		<link rel="alternate" href="https://example.com/atom-post" />
		<updated>2026-04-26T09:30:00Z</updated>
		<content type="html">&lt;p&gt;Atom body&lt;/p&gt;</content>
	</entry>
</feed>
XML;

		$this->assertSame(
			array(
				array(
					'date'    => '2026-04-26T09:30:00Z',
					'title'   => 'Atom title',
					'content' => '<p>Atom body</p>',
					'url'     => 'https://example.com/atom-post',
				),
			),
			$decoder->decode( $xml )
		);
	}
}
