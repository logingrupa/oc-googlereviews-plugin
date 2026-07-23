<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Classes\Api;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesClient;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesException;
use PluginTestCase;

class GooglePlacesClientTest extends PluginTestCase
{
    private function makeClient(): GooglePlacesClient
    {
        return new GooglePlacesClient(app(HttpFactory::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePayload(): array
    {
        return [
            'id' => 'PLACE_ID',
            'rating' => 4.8,
            'userRatingCount' => 127,
            'reviews' => [
                [
                    'name' => 'places/PLACE_ID/reviews/REVIEW_1',
                    'rating' => 5,
                    'text' => ['text' => 'Great nails!', 'languageCode' => 'en'],
                    'originalText' => ['text' => 'Lieliski nagi!', 'languageCode' => 'lv'],
                    'authorAttribution' => [
                        'displayName' => 'Anna B',
                        'uri' => 'https://maps.google.com/anna',
                        'photoUri' => 'https://lh3.googleusercontent.com/anna',
                    ],
                    'publishTime' => '2026-05-01T10:00:00Z',
                    'relativePublishTimeDescription' => '2 months ago',
                ],
            ],
        ];
    }

    public function testFetchMapsPayloadIntoDtos(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response($this->samplePayload(), 200),
        ]);

        $obPlaceDetails = $this->makeClient()->fetchPlaceDetails('api-key', 'PLACE_ID');

        $this->assertSame(4.8, $obPlaceDetails->fAverageRating);
        $this->assertSame(127, $obPlaceDetails->iReviewCount);
        $this->assertCount(1, $obPlaceDetails->arReviewList);

        $obReview = $obPlaceDetails->arReviewList[0];
        $this->assertSame('places/PLACE_ID/reviews/REVIEW_1', $obReview->sGoogleReviewId);
        $this->assertSame('Anna B', $obReview->obAuthor->sName);
        $this->assertSame('https://lh3.googleusercontent.com/anna', $obReview->obAuthor->sPhotoUrl);
        $this->assertSame('Great nails!', $obReview->sTextEnglish);
        $this->assertSame('Lieliski nagi!', $obReview->sTextOriginal);
        $this->assertSame('lv', $obReview->sOriginalLanguage);
        $this->assertSame(5, $obReview->iRating);
        $this->assertNotNull($obReview->dtPublishedAt);
    }

    public function testFetchRequestsEnglishTranslation(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response($this->samplePayload(), 200),
        ]);

        $this->makeClient()->fetchPlaceDetails('api-key', 'PLACE_ID');

        Http::assertSent(function ($obRequest): bool {
            return str_contains($obRequest->url(), 'languageCode=en')
                && $obRequest->hasHeader('X-Goog-Api-Key', 'api-key');
        });
    }

    public function testFetchThrowsOnErrorStatus(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response(['error' => 'denied'], 403),
        ]);

        $this->expectException(GooglePlacesException::class);

        $this->makeClient()->fetchPlaceDetails('api-key', 'PLACE_ID');
    }

    public function testFetchRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeClient()->fetchPlaceDetails('  ', 'PLACE_ID');
    }

    public function testFetchRejectsEmptyPlaceId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeClient()->fetchPlaceDetails('api-key', '');
    }
}
