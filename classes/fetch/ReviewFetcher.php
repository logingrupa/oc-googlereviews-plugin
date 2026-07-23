<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Fetch;

use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesClient;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Orchestrates one end-to-end fetch: call Google, reconcile the reviews table,
 * and persist the place-level aggregate snapshot.
 *
 * Shared by the scheduled console command and the backend preview widget so the
 * "fetch and store" flow lives in exactly one place.
 */
class ReviewFetcher
{
    public function __construct(
        private readonly GooglePlacesClient $obPlacesClient,
        private readonly ReviewSynchronizer $obSynchronizer,
    ) {
    }

    /**
     * @return int Active review count after the fetch.
     */
    public function fetch(string $sApiKey, string $sPlaceId, int $iMinRating): int
    {
        $obPlaceDetails = $this->obPlacesClient->fetchPlaceDetails($sApiKey, $sPlaceId);
        $iActiveCount = $this->obSynchronizer->synchronize($obPlaceDetails->arReviewList, $iMinRating);

        $obSettings = Settings::instance();
        $obSettings->set('aggregate_rating', $obPlaceDetails->fAverageRating);
        $obSettings->set('aggregate_count', $obPlaceDetails->iReviewCount);

        return $iActiveCount;
    }
}
