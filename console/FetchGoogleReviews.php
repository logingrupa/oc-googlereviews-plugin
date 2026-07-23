<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Console;

use Illuminate\Console\Command;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesException;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewFetcher;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Pulls the latest Google reviews and reconciles them with the database.
 *
 * Orchestration only: reads settings and delegates the fetch/persist flow to
 * {@see ReviewFetcher}.
 */
class FetchGoogleReviews extends Command
{
    protected $signature = 'googlereviews:fetch';

    protected $description = 'Fetch Google Business reviews (translated to English) and cache them locally.';

    public function __construct(private readonly ReviewFetcher $obReviewFetcher)
    {
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
            $iActiveCount = $this->obReviewFetcher->fetch($sApiKey, $sPlaceId, $obSettings->getMinRating());
        } catch (GooglePlacesException $obException) {
            $this->error('Google Reviews fetch failed: ' . $obException->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Google Reviews: %d active review(s) synchronised.', $iActiveCount));

        return self::SUCCESS;
    }
}
