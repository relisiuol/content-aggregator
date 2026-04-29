<?php

declare(strict_types=1);

namespace ContentAggregator\Tests\Decoders;

use Content_Aggregator\Decoders\Auto_Detector;

final class AutoDetectorProbe extends Auto_Detector {
	public static function joinBaseAndEndpoint( string $base, string $endpoint ): string {
		return parent::join_base_and_endpoint( $base, $endpoint );
	}

	public static function absoluteUrl( string $base, string $rel ): string {
		return parent::abs_url( $base, $rel );
	}

	public static function findFeedLink( string $html, string $base ): ?string {
		return parent::find_feed_link( $html, $base );
	}
}
