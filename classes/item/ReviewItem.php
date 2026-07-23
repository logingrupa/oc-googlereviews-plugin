<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Item;

use Logingrupa\GoogleReviews\Models\Review;
use Lovata\Toolbox\Classes\Item\ElementItem;

/**
 * Cached, read-only projection of a {@see Review} row.
 *
 * Field values are served from the Lovata.Toolbox cache via __get, so they are
 * declared as @property hints rather than real typed properties (a real typed
 * property would shadow the magic accessor).
 *
 * @property int         $id
 * @property string      $google_review_id
 * @property string      $author_name
 * @property string|null $author_photo_url
 * @property string|null $author_url
 * @property int         $rating
 * @property string|null $text_english
 * @property string|null $text_original
 * @property string|null $original_language
 * @property string|null $relative_time
 * @property string|null $published_at_iso
 * @property string|null $published_at_display
 */
class ReviewItem extends ElementItem
{
    const MODEL_CLASS = Review::class;

    /**
     * Normalised scalar shape cached for each review.
     *
     * @return array<string, mixed>
     */
    protected function getElementData(): array
    {
        /** @var Review $obReview */
        $obReview = $this->obElement;

        return [
            'id' => (int) $obReview->id,
            'google_review_id' => (string) $obReview->google_review_id,
            'author_name' => (string) $obReview->author_name,
            'author_photo_url' => $obReview->author_photo_url,
            'author_url' => $obReview->author_url,
            'rating' => (int) $obReview->rating,
            'text_english' => $obReview->text_english,
            'text_original' => $obReview->text_original,
            'original_language' => $obReview->original_language,
            'relative_time' => $obReview->relative_time,
            'published_at_iso' => $obReview->published_at?->toIso8601String(),
            'published_at_display' => $obReview->published_at?->translatedFormat('F Y'),
        ];
    }
}
