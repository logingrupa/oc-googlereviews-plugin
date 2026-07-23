<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Classes\Fetch;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesClient;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewFetcher;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewSynchronizer;
use Logingrupa\GoogleReviews\Models\Review;
use Logingrupa\GoogleReviews\Models\Settings;
use PluginTestCase;

class ReviewFetcherTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeFetcher(): ReviewFetcher
    {
        return new ReviewFetcher(new GooglePlacesClient(app(HttpFactory::class)), new ReviewSynchronizer());
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(): array
    {
        return [
            'id' => 'PLACE_ID',
            'rating' => 4.7,
            'userRatingCount' => 55,
            'reviews' => [
                [
                    'name' => 'places/PLACE_ID/reviews/REVIEW_1',
                    'rating' => 5,
                    'text' => ['text' => 'Great nails!', 'languageCode' => 'en'],
                    'originalText' => ['text' => 'Lieliski!', 'languageCode' => 'lv'],
                    'authorAttribution' => ['displayName' => 'Anna B'],
                    'publishTime' => '2026-05-01T10:00:00Z',
                ],
            ],
        ];
    }

    public function testFetchStoresReviewsAndAggregate(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response($this->samplePayload(), 200),
        ]);

        $iActiveCount = $this->makeFetcher()->fetch('api-key', 'PLACE_ID', 4);

        $this->assertSame(1, $iActiveCount);
        $this->assertSame(1, Review::query()->where('is_active', true)->count());
        $this->assertSame(4.7, (float) Settings::get('aggregate_rating'));
        $this->assertSame(55, (int) Settings::get('aggregate_count'));
    }
}
