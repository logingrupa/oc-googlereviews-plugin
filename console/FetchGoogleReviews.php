<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Console;

use Illuminate\Console\Command;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesClient;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesException;
use Logingrupa\GoogleReviews\Classes\Api\PlaceDetailsDto;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewSynchronizer;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Pulls the latest Google reviews and reconciles them with the database.
 *
 * Orchestration only: reads settings, delegates the HTTP call to
 * {@see GooglePlacesClient} and persistence to {@see ReviewSynchronizer}, then
 * records the place-level aggregate for the JSON-LD AggregateRating.
 */
class FetchGoogleReviews extends Command
{
    protected $signature = 'googlereviews:fetch';

    protected $description = 'Fetch Google Business reviews (translated to English) and cache them locally.';

    public function __construct(
        private readonly GooglePlacesClient $obPlacesClient,
        private readonly ReviewSynchronizer $obSynchronizer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $obSettings = Settings::instance();
        $sApiKey = $obSettings->getApiKey();
        $sPlaceId = $obSettings->getPlaceId();

        if ($sApiKey === '' || $sPlaceId === '') {
            $this->error('Google Reviews: API key and Place ID must be configured in Settings.');

            return self::FAILURE;
        }

        try {
            $obPlaceDetails = $this->obPlacesClient->fetchPlaceDetails($sApiKey, $sPlaceId);
        } catch (GooglePlacesException $obException) {
            $this->error('Google Reviews fetch failed: ' . $obException->getMessage());

            return self::FAILURE;
        }

        $iActiveCount = $this->obSynchronizer->synchronize(
            $obPlaceDetails->arReviewList,
            $obSettings->getMinRating(),
        );

        $this->persistAggregate($obSettings, $obPlaceDetails);

        $this->info(sprintf('Google Reviews: %d active review(s) synchronised.', $iActiveCount));

        return self::SUCCESS;
    }

    private function persistAggregate(Settings $obSettings, PlaceDetailsDto $obPlaceDetails): void
    {
        $obSettings->set('aggregate_rating', $obPlaceDetails->fAverageRating);
        $obSettings->set('aggregate_count', $obPlaceDetails->iReviewCount);
    }
}
