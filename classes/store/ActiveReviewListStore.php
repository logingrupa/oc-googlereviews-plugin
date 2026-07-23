<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Store;

use Logingrupa\GoogleReviews\Models\Review;
use Lovata\Toolbox\Classes\Store\AbstractStoreWithoutParam;

/**
 * Cached list of active review ids, ordered for display (newest first).
 *
 * Holds only identifiers; {@see \Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection}
 * turns them into cached items on demand.
 *
 * @method static self instance()
 */
class ActiveReviewListStore extends AbstractStoreWithoutParam
{
    /**
     * @return array<int, int>
     */
    protected function getIDListFromDB(): array
    {
        $obIdList = Review::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->pluck('id')
            ->all();

        $arReviewIdList = [];
        foreach ($obIdList as $obId) {
            if (is_numeric($obId)) {
                $arReviewIdList[] = (int) $obId;
            }
        }

        return $arReviewIdList;
    }
}
