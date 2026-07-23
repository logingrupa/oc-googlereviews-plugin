<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Fetch;

use Logingrupa\GoogleReviews\Classes\Api\ReviewDto;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Logingrupa\GoogleReviews\Models\Review;

/**
 * Reconciles a batch of fetched review DTOs with the reviews table.
 *
 * Single responsibility: given the DTOs returned for the current fetch, upsert
 * the qualifying ones and deactivate any stored review Google no longer serves.
 * The Places API returns at most five, rotating, "most relevant" reviews, so
 * absent rows are hidden rather than deleted to keep history.
 */
class ReviewSynchronizer
{
    /**
     * @param array<int, ReviewDto> $arReviewList
     * @return int Count of active reviews after synchronization.
     */
    public function synchronize(array $arReviewList, int $iMinRating): int
    {
        $arKeptGoogleIdList = [];
        $iSortOrder = 0;

        foreach ($arReviewList as $obReviewDto) {
            if (!$this->isStorable($obReviewDto, $iMinRating)) {
                continue;
            }

            $this->upsertReview($obReviewDto, $iSortOrder);
            $arKeptGoogleIdList[] = $obReviewDto->sGoogleReviewId;
            $iSortOrder++;
        }

        // Empty-batch floor: when nothing qualified (no data / all below min
        // rating), treat it as "keep existing" rather than wiping the widget.
        // Nothing changed, so skip deactivation and the store clear, and report
        // the truthful current active count.
        if ($arKeptGoogleIdList === []) {
            return $this->countActiveReviews();
        }

        $this->deactivateMissing($arKeptGoogleIdList);
        ActiveReviewListStore::instance()->clear();

        return count($arKeptGoogleIdList);
    }

    private function countActiveReviews(): int
    {
        return Review::query()->where('is_active', true)->count();
    }

    private function isStorable(ReviewDto $obReviewDto, int $iMinRating): bool
    {
        if (trim($obReviewDto->sGoogleReviewId) === '') {
            return false;
        }

        return $obReviewDto->iRating >= $iMinRating;
    }

    private function upsertReview(ReviewDto $obReviewDto, int $iSortOrder): void
    {
        Review::updateOrCreate(
            ['google_review_id' => $obReviewDto->sGoogleReviewId],
            [
                'author_name' => $obReviewDto->obAuthor->sName,
                'author_photo_url' => $obReviewDto->obAuthor->sPhotoUrl,
                'author_url' => $obReviewDto->obAuthor->sProfileUrl,
                'rating' => $obReviewDto->iRating,
                'text_english' => $obReviewDto->sTextEnglish,
                'text_original' => $obReviewDto->sTextOriginal,
                'original_language' => $obReviewDto->sOriginalLanguage,
                'relative_time' => $obReviewDto->sRelativeTime,
                'published_at' => $obReviewDto->dtPublishedAt,
                'is_active' => true,
                'sort_order' => $iSortOrder,
            ],
        );
    }

    /**
     * @param array<int, string> $arKeptGoogleIdList
     */
    private function deactivateMissing(array $arKeptGoogleIdList): void
    {
        $obQuery = Review::query()->where('is_active', true);

        if ($arKeptGoogleIdList !== []) {
            $obQuery->whereNotIn('google_review_id', $arKeptGoogleIdList);
        }

        // Mass update: intentionally bypasses Eloquent model events (no
        // afterSave), so the read cache is NOT flushed here. Read-cache
        // correctness relies on the explicit ActiveReviewListStore::clear()
        // the caller performs after this batch reconciliation.
        $obQuery->update(['is_active' => false]);
    }
}
