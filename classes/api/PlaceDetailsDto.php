<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Api;

/**
 * Immutable place-level payload: aggregate rating plus the returned reviews.
 */
readonly class PlaceDetailsDto
{
    /**
     * @param array<int, ReviewDto> $arReviewList
     */
    public function __construct(
        public float $fAverageRating,
        public int $iReviewCount,
        public array $arReviewList,
    ) {
    }
}
