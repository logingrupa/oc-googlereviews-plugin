<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\FormWidgets;

use Backend\Classes\FormWidgetBase;
use Backend\Widgets\Form;
use Illuminate\Http\Client\Factory as HttpFactory;
use InvalidArgumentException;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesClient;
use Logingrupa\GoogleReviews\Classes\Api\GooglePlacesException;
use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewFetcher;
use Logingrupa\GoogleReviews\Classes\Fetch\ReviewSynchronizer;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Backend settings widget: renders the cached reviews and a manual "Fetch now"
 * button that pulls fresh data from Google over AJAX and re-renders the preview.
 *
 * Presentation glue only; the fetch flow is delegated to {@see ReviewFetcher}.
 */
class ReviewPreview extends FormWidgetBase
{
    protected $defaultAlias = 'logingrupa_googlereviews_preview';

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('reviewpreview');
    }

    public function prepareVars(): void
    {
        $obSettings = Settings::instance();
        $this->vars['fAggregateRating'] = $obSettings->getAggregateRating();
        $this->vars['iAggregateCount'] = $obSettings->getAggregateCount();
        $this->vars['obReviewList'] = $this->loadReviewCollection();

        if (!array_key_exists('sErrorMessage', $this->vars)) {
            $this->vars['sErrorMessage'] = null;
        }
    }

    /**
     * @return array<string, string>
     */
    public function onRefreshReviews(): array
    {
        try {
            $this->runFetch();
        } catch (GooglePlacesException | InvalidArgumentException $obException) {
            $this->vars['sErrorMessage'] = $obException->getMessage();
        }

        $this->prepareVars();

        return ['#' . $this->getId('preview') => $this->makePartial('preview')];
    }

    public function loadAssets(): void
    {
        $this->addCss('css/preview.css');
    }

    public function getSaveValue($obValue)
    {
        return Form::NO_SAVE_DATA;
    }

    private function runFetch(): void
    {
        $obSettings = Settings::instance();
        $sApiKey = $this->resolveApiKey($obSettings);
        $sPlaceId = $this->resolvePlaceId($obSettings);

        $obFetcher = new ReviewFetcher(
            new GooglePlacesClient(app(HttpFactory::class)),
            new ReviewSynchronizer(),
        );

        $obFetcher->fetch($sApiKey, $sPlaceId, $obSettings->getMinRating());
    }

    private function resolveApiKey(Settings $obSettings): string
    {
        $sPostedApiKey = trim((string) post('Settings.api_key'));

        if ($sPostedApiKey === '' || $sPostedApiKey === '__hidden__') {
            return $obSettings->getApiKey();
        }

        return $sPostedApiKey;
    }

    private function resolvePlaceId(Settings $obSettings): string
    {
        $sPostedPlaceId = trim((string) post('Settings.place_id'));

        return $sPostedPlaceId !== '' ? $sPostedPlaceId : $obSettings->getPlaceId();
    }

    private function loadReviewCollection(): ReviewCollection
    {
        $arReviewIdList = ActiveReviewListStore::instance()->get();

        return ReviewCollection::make($arReviewIdList);
    }
}
