<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Components;

use Cms\Classes\ComponentBase;
use Illuminate\Support\Facades\Log;
use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Schema\ReviewSchemaBuilder;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Logingrupa\GoogleReviews\Models\Settings;

/**
 * Renders the cached Google reviews plus optional Review/AggregateRating JSON-LD.
 */
class ReviewList extends ComponentBase
{
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
            'maxItems' => [
                'title' => 'logingrupa.googlereviews::lang.component.max_items',
                'type' => 'string',
                'default' => '5',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'logingrupa.googlereviews::lang.component.max_items_invalid',
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
        ];
    }

    public function reviewCollection(): ReviewCollection
    {
        $arReviewIdList = ActiveReviewListStore::instance()->get();

        $iMaxItems = $this->maxItems();
        if ($iMaxItems > 0) {
            $arReviewIdList = array_slice($arReviewIdList, 0, $iMaxItems);
        }

        return ReviewCollection::make($arReviewIdList);
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
