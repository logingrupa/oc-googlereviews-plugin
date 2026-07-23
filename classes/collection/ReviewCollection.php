<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Collection;

use Logingrupa\GoogleReviews\Classes\Item\ReviewItem;
use Lovata\Toolbox\Classes\Collection\ElementCollection;

/**
 * Lazily-materialised, cached collection of {@see ReviewItem} objects.
 */
class ReviewCollection extends ElementCollection
{
    const ITEM_CLASS = ReviewItem::class;
}
