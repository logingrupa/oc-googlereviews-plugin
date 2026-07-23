<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Classes\Schema;

use Carbon\Carbon;
use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Schema\ReviewSchemaBuilder;
use Logingrupa\GoogleReviews\Models\Review;
use PluginTestCase;

class ReviewSchemaBuilderTest extends PluginTestCase
{
    private ReviewSchemaBuilder $obBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $this->obBuilder = new ReviewSchemaBuilder();
    }

    /**
     * @param array<string, mixed> $arOverrides
     */
    private function seedReview(array $arOverrides = []): Review
    {
        return Review::create(array_merge([
            'google_review_id' => 'rev-' . uniqid(),
            'author_name' => 'Anna B',
            'rating' => 5,
            'text_english' => 'Great nails!',
            'published_at' => Carbon::parse('2026-05-01T10:00:00Z'),
            'is_active' => true,
            'sort_order' => 0,
        ], $arOverrides));
    }

    private function collectionFrom(Review $obReview): ReviewCollection
    {
        return ReviewCollection::make([$obReview->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $sJson): array
    {
        return (array) json_decode($sJson, true);
    }

    public function testOmitsAggregateRatingWhenCountIsZero(): void
    {
        $obCollection = $this->collectionFrom($this->seedReview());

        $sJson = $this->obBuilder->build('Nais Cosmetics', 'LocalBusiness', 4.8, 0, $obCollection);

        $this->assertNotNull($sJson);
        $this->assertArrayNotHasKey('aggregateRating', $this->decode($sJson));
    }

    public function testIncludesAggregateRatingWhenCountIsPositive(): void
    {
        $obCollection = $this->collectionFrom($this->seedReview());

        $sJson = $this->obBuilder->build('Nais Cosmetics', 'LocalBusiness', 4.8, 127, $obCollection);

        $this->assertNotNull($sJson);
        $arSchema = $this->decode($sJson);
        $this->assertArrayHasKey('aggregateRating', $arSchema);
        $this->assertSame(127, $arSchema['aggregateRating']['reviewCount']);
        $this->assertSame(4.8, $arSchema['aggregateRating']['ratingValue']);
    }

    public function testOmitsNullReviewBodyAndDatePublished(): void
    {
        $obReview = $this->seedReview(['text_english' => null, 'published_at' => null]);
        $obCollection = $this->collectionFrom($obReview);

        $sJson = $this->obBuilder->build('Nais Cosmetics', 'LocalBusiness', 4.8, 5, $obCollection);

        $this->assertNotNull($sJson);
        $arReviewNode = $this->decode($sJson)['review'][0];
        $this->assertArrayNotHasKey('reviewBody', $arReviewNode);
        $this->assertArrayNotHasKey('datePublished', $arReviewNode);
    }

    public function testIncludesAuthorNameInReviewNodes(): void
    {
        $obReview = $this->seedReview(['author_name' => 'Reviewer X']);
        $obCollection = $this->collectionFrom($obReview);

        $sJson = $this->obBuilder->build('Nais Cosmetics', 'LocalBusiness', 4.8, 5, $obCollection);

        $this->assertNotNull($sJson);
        $arReviewNode = $this->decode($sJson)['review'][0];
        $this->assertSame('Reviewer X', $arReviewNode['author']['name']);
    }

    public function testReturnsNullForBlankBusinessName(): void
    {
        $obCollection = $this->collectionFrom($this->seedReview());

        $this->assertNull($this->obBuilder->build('   ', 'LocalBusiness', 4.8, 5, $obCollection));
    }
}
