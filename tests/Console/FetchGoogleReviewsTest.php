<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Logingrupa\GoogleReviews\Models\Review;
use Logingrupa\GoogleReviews\Models\Settings;
use PluginTestCase;

class FetchGoogleReviewsTest extends PluginTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function samplePayload(): array
    {
        return [
            'id' => 'PLACE_ID',
            'rating' => 4.9,
            'userRatingCount' => 88,
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

    public function testCommandFailsWhenNotConfigured(): void
    {
        $this->assertSame(1, Artisan::call('googlereviews:fetch'));
    }

    public function testCommandStoresReviewsAndAggregate(): void
    {
        Settings::set('api_key', 'api-key');
        Settings::set('place_id', 'PLACE_ID');
        Settings::set('min_rating', 4);

        Http::fake([
            'places.googleapis.com/*' => Http::response($this->samplePayload(), 200),
        ]);

        $this->assertSame(0, Artisan::call('googlereviews:fetch'));

        $this->assertSame(1, Review::query()->where('is_active', true)->count());
        $this->assertSame(4.9, (float) Settings::get('aggregate_rating'));
        $this->assertSame(88, (int) Settings::get('aggregate_count'));
    }

    public function testCommandFailsGracefullyOnApiError(): void
    {
        Settings::set('api_key', 'api-key');
        Settings::set('place_id', 'PLACE_ID');

        Http::fake([
            'places.googleapis.com/*' => Http::response(['error' => 'denied'], 403),
        ]);

        $this->assertSame(1, Artisan::call('googlereviews:fetch'));
    }
}
