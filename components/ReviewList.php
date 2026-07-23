<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Log;
use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Item\ReviewItem;
use Logingrupa\GoogleReviews\Classes\Schema\ReviewSchemaBuilder;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Renders the cached Google reviews in a selectable layout (grid, slider or
 * list) plus optional Review/AggregateRating JSON-LD.
 *
 * Also exposed as a RainLab.Pages snippet so editors can drop it onto any
 * static page.
 */
class ReviewList extends ComponentBase
{
    public string $snippetName = 'Google Reviews';

    public string $snippetDescription = 'Display Google Business reviews (grid, slider or list) with JSON-LD.';

    /**
     * @return array<string, string>
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'logingrupa.googlereviews::lang.component.name',
            'description' => 'logingrupa.googlereviews::lang.component.description',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function defineProperties(): array
    {
        return [
            'style' => [
                'title' => 'logingrupa.googlereviews::lang.component.style',
                'type' => 'dropdown',
                'default' => 'grid',
                'options' => [
                    'grid' => 'logingrupa.googlereviews::lang.component.style_grid',
                    'slider' => 'logingrupa.googlereviews::lang.component.style_slider',
                    'list' => 'logingrupa.googlereviews::lang.component.style_list',
                ],
            ],
            'order' => [
                'title' => 'logingrupa.googlereviews::lang.component.order',
                'type' => 'dropdown',
                'default' => 'best',
                'options' => [
                    'best' => 'logingrupa.googlereviews::lang.component.order_best',
                    'newest' => 'logingrupa.googlereviews::lang.component.order_newest',
                    'relevance' => 'logingrupa.googlereviews::lang.component.order_relevance',
                ],
            ],
            'shuffle' => [
                'title' => 'logingrupa.googlereviews::lang.component.shuffle',
                'description' => 'logingrupa.googlereviews::lang.component.shuffle_comment',
                'type' => 'checkbox',
                'default' => false,
            ],
            'autoplay' => [
                'title' => 'logingrupa.googlereviews::lang.component.autoplay',
                'description' => 'logingrupa.googlereviews::lang.component.autoplay_comment',
                'type' => 'checkbox',
                'default' => false,
            ],
            'autoplayInterval' => [
                'title' => 'logingrupa.googlereviews::lang.component.autoplay_interval',
                'type' => 'string',
                'default' => '5000',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'logingrupa.googlereviews::lang.component.autoplay_interval_invalid',
            ],
            'maxItems' => [
                'title' => 'logingrupa.googlereviews::lang.component.max_items',
                'type' => 'string',
                'default' => '9',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'logingrupa.googlereviews::lang.component.max_items_invalid',
            ],
            'heading' => [
                'title' => 'logingrupa.googlereviews::lang.component.heading',
                'type' => 'string',
                'default' => '',
            ],
            'eyebrow' => [
                'title' => 'logingrupa.googlereviews::lang.component.eyebrow',
                'type' => 'string',
                'default' => '',
            ],
            'showAggregate' => [
                'title' => 'logingrupa.googlereviews::lang.component.show_aggregate',
                'type' => 'checkbox',
                'default' => true,
            ],
            'businessName' => [
                'title' => 'logingrupa.googlereviews::lang.component.business_name',
                'type' => 'string',
                'default' => '',
            ],
            'businessType' => [
                'title' => 'logingrupa.googlereviews::lang.component.business_type',
                'type' => 'string',
                'default' => 'LocalBusiness',
            ],
            'renderSchema' => [
                'title' => 'logingrupa.googlereviews::lang.component.render_schema',
                'type' => 'checkbox',
                'default' => true,
            ],
            'includeAssets' => [
                'title' => 'logingrupa.googlereviews::lang.component.include_assets',
                'description' => 'logingrupa.googlereviews::lang.component.include_assets_comment',
                'type' => 'checkbox',
                'default' => true,
            ],
        ];
    }

    public function onRun(): void
    {
        if (!(bool) $this->property('includeAssets')) {
            return;
        }

        $this->addCss('assets/css/reviews.css');

        if ($this->style() === 'slider') {
            $this->addJs('assets/js/reviews-slider.js', ['module' => true]);
        }
    }

    public function style(): string
    {
        $sStyle = (string) $this->property('style', 'grid');

        return in_array($sStyle, ['grid', 'slider', 'list'], true) ? $sStyle : 'grid';
    }

    public function reviewCollection(): ReviewCollection
    {
        $arReviewIdList = $this->orderedReviewIdList();

        $iMaxItems = $this->maxItems();
        if ($iMaxItems > 0) {
            $arReviewIdList = array_slice($arReviewIdList, 0, $iMaxItems);
        }

        return ReviewCollection::make($arReviewIdList);
    }

    /**
     * Data-attribute string for the slider container div.
     */
    public function sliderAttributes(): string
    {
        $sAttributes = 'data-gr-slider';

        if ((bool) $this->property('shuffle')) {
            $sAttributes .= ' data-gr-shuffle';
        }

        if ((bool) $this->property('autoplay')) {
            $iInterval = max(2000, (int) $this->property('autoplayInterval', '5000'));
            $sAttributes .= ' data-gr-autoplay data-gr-interval="' . $iInterval . '"';
        }

        return $sAttributes;
    }

    /**
     * Aggregate rating summary, or null when it should not be shown.
     *
     * @return array{rating: float, count: int}|null
     */
    public function aggregate(): ?array
    {
        if (!(bool) $this->property('showAggregate')) {
            return null;
        }

        $obSettings = Settings::instance();
        $iCount = $obSettings->getAggregateCount();
        if ($iCount <= 0) {
            return null;
        }

        return [
            'rating' => $obSettings->getAggregateRating(),
            'count' => $iCount,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function orderedReviewIdList(): array
    {
        $arActiveIdList = ActiveReviewListStore::instance()->get();

        $arReviewIdList = [];
        foreach ($arActiveIdList as $obId) {
            if (is_numeric($obId)) {
                $arReviewIdList[] = (int) $obId;
            }
        }

        $sOrder = (string) $this->property('order', 'best');
        if ($sOrder === 'relevance' || $arReviewIdList === []) {
            return $arReviewIdList;
        }

        return $this->sortIdListByItems($arReviewIdList, $sOrder);
    }

    /**
     * Sort the (already cached) review items in PHP; the set is at most five.
     *
     * @param array<int, int> $arReviewIdList
     * @return array<int, int>
     */
    private function sortIdListByItems(array $arReviewIdList, string $sOrder): array
    {
        /** @var array<int, ReviewItem> $arItemList */
        $arItemList = [];
        foreach (ReviewCollection::make($arReviewIdList) as $obReviewItem) {
            /** @var ReviewItem $obReviewItem */
            $arItemList[] = $obReviewItem;
        }

        usort($arItemList, function (ReviewItem $obLeft, ReviewItem $obRight) use ($sOrder): int {
            if ($sOrder === 'newest') {
                return strcmp((string) $obRight->published_at_iso, (string) $obLeft->published_at_iso);
            }

            return $obRight->rating <=> $obLeft->rating
                ?: strcmp((string) $obRight->published_at_iso, (string) $obLeft->published_at_iso);
        });

        return array_map(static fn (ReviewItem $obReviewItem): int => (int) $obReviewItem->id, $arItemList);
    }

    /**
     * JSON-LD string for the reviewed entity, or null when disabled/empty.
     */
    public function schemaJson(): ?string
    {
        if (!(bool) $this->property('renderSchema')) {
            return null;
        }

        $sBusinessName = trim((string) $this->property('businessName'));
        if ($sBusinessName === '') {
            Log::warning(
                'Google Reviews: renderSchema is enabled but the businessName property is blank; '
                . 'skipping JSON-LD schema. Set the component businessName to emit schema.'
            );

            return null;
        }

        $obSettings = Settings::instance();

        return (new ReviewSchemaBuilder())->build(
            $sBusinessName,
            (string) $this->property('businessType', 'LocalBusiness'),
            $obSettings->getAggregateRating(),
            $obSettings->getAggregateCount(),
            $this->reviewCollection(),
        );
    }

    private function maxItems(): int
    {
        return (int) $this->property('maxItems');
    }
}
