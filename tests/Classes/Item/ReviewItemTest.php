<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Classes\Item;

use Carbon\Carbon;
use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Item\ReviewItem;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Logingrupa\GoogleReviews\Models\Review;
use PluginTestCase;

class ReviewItemTest extends PluginTestCase
{
    private function seedReview(): Review
    {
        return Review::create([
            'google_review_id' => 'rev-1',
            'author_name' => 'Anna B',
            'rating' => 5,
            'text_english' => 'Great nails!',
            'text_original' => 'Lieliski!',
            'original_language' => 'lv',
            'published_at' => Carbon::parse('2026-05-01T10:00:00Z'),
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    public function testItemExposesNormalisedFields(): void
    {
        $obReview = $this->seedReview();

        $obItem = ReviewItem::make($obReview->id);

        $this->assertSame('Anna B', $obItem->author_name);
        $this->assertSame(5, $obItem->rating);
        $this->assertSame('Great nails!', $obItem->text_english);
        $this->assertSame('Lieliski!', $obItem->text_original);
        $this->assertNotNull($obItem->published_at_iso);
    }

    public function testActiveStoreAndCollectionReturnActiveReviews(): void
    {
        $this->seedReview();
        Review::create([
            'google_review_id' => 'rev-2',
            'author_name' => 'Hidden',
            'rating' => 5,
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $arIdList = ActiveReviewListStore::instance()->getNoCache();
        $obCollection = ReviewCollection::make($arIdList);

        $this->assertSame(1, $obCollection->count());
        $this->assertSame('Anna B', $obCollection->first()->author_name);
    }
}
