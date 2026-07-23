<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Classes\Fetch;

use Carbon\Carbon;
use Logingrupa\GoogleReviews\Classes\Api\AuthorDto;
use Logingrupa\GoogleReviews\Classes\Api\ReviewDto;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewSynchronizer;
use Logingrupa\GoogleReviews\Models\Review;
use PluginTestCase;

class ReviewSynchronizerTest extends PluginTestCase
{
    private ReviewSynchronizer $obSynchronizer;

    public function setUp(): void
    {
        parent::setUp();
        $this->obSynchronizer = new ReviewSynchronizer();
    }

    private function makeDto(string $sGoogleReviewId, int $iRating, string $sText = 'Nice'): ReviewDto
    {
        return new ReviewDto(
            sGoogleReviewId: $sGoogleReviewId,
            obAuthor: new AuthorDto('Author ' . $sGoogleReviewId, null, null),
            iRating: $iRating,
            sTextEnglish: $sText,
            sTextOriginal: $sText,
            sOriginalLanguage: 'lv',
            sRelativeTime: 'a month ago',
            dtPublishedAt: Carbon::parse('2026-06-01T00:00:00Z'),
        );
    }

    public function testUpsertCreatesActiveRows(): void
    {
        $iActiveCount = $this->obSynchronizer->synchronize([
            $this->makeDto('rev-1', 5),
            $this->makeDto('rev-2', 4),
        ], 4);

        $this->assertSame(2, $iActiveCount);
        $this->assertSame(2, Review::query()->where('is_active', true)->count());
    }

    public function testDedupesByGoogleReviewId(): void
    {
        $this->obSynchronizer->synchronize([$this->makeDto('rev-1', 5, 'First')], 4);
        $this->obSynchronizer->synchronize([$this->makeDto('rev-1', 5, 'Updated')], 4);

        $this->assertSame(1, Review::query()->count());
        $this->assertSame('Updated', Review::query()->firstOrFail()->text_english);
    }

    public function testSkipsReviewsBelowMinimumRating(): void
    {
        $iActiveCount = $this->obSynchronizer->synchronize([
            $this->makeDto('rev-1', 5),
            $this->makeDto('rev-2', 3),
        ], 4);

        $this->assertSame(1, $iActiveCount);
        $this->assertNull(Review::query()->where('google_review_id', 'rev-2')->first());
    }

    public function testSkipsReviewsWithEmptyGoogleId(): void
    {
        $iActiveCount = $this->obSynchronizer->synchronize([$this->makeDto('', 5)], 4);

        $this->assertSame(0, $iActiveCount);
        $this->assertSame(0, Review::query()->count());
    }

    public function testDeactivatesReviewsMissingFromBatch(): void
    {
        $this->obSynchronizer->synchronize([$this->makeDto('rev-old', 5)], 4);
        $this->obSynchronizer->synchronize([$this->makeDto('rev-new', 5)], 4);

        $this->assertFalse((bool) Review::query()->where('google_review_id', 'rev-old')->firstOrFail()->is_active);
        $this->assertTrue((bool) Review::query()->where('google_review_id', 'rev-new')->firstOrFail()->is_active);
    }

    public function testEmptyBatchFloorKeepsExistingActiveRows(): void
    {
        $this->obSynchronizer->synchronize([
            $this->makeDto('rev-1', 5),
            $this->makeDto('rev-2', 4),
        ], 4);

        // A subsequent fetch that yields nothing storable (all below the min
        // rating, then an empty batch) must keep the existing active rows
        // rather than wiping the widget, and report the current active count.
        $iActiveAfterBelowMin = $this->obSynchronizer->synchronize([$this->makeDto('rev-3', 2)], 4);
        $iActiveAfterEmpty = $this->obSynchronizer->synchronize([], 4);

        $this->assertSame(2, $iActiveAfterBelowMin);
        $this->assertSame(2, $iActiveAfterEmpty);
        $this->assertSame(2, Review::query()->where('is_active', true)->count());
        $this->assertTrue((bool) Review::query()->where('google_review_id', 'rev-1')->firstOrFail()->is_active);
    }
}
