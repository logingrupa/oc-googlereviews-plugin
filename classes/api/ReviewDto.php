<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Api;

use Carbon\Carbon;

/**
 * Immutable, provider-neutral representation of one Google Places review.
 *
 * Populated by {@see GooglePlacesClient} from the Places API (New) payload and
 * consumed by the synchronizer. Carries both the English translation and the
 * untranslated original so consuming sites can pick per active language.
 */
readonly class ReviewDto
{
    public function __construct(
        public string $sGoogleReviewId,
        public AuthorDto $obAuthor,
        public int $iRating,
        public ?string $sTextEnglish,
        public ?string $sTextOriginal,
        public ?string $sOriginalLanguage,
        public ?string $sRelativeTime,
        public ?Carbon $dtPublishedAt,
    ) {
    }
}
