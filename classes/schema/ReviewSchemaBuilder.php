<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Schema;

use Logingrupa\GoogleReviews\Classes\Collection\ReviewCollection;
use Logingrupa\GoogleReviews\Classes\Item\ReviewItem;

/**
 * Pure builder for the Review / AggregateRating JSON-LD block.
 *
 * Holds no CMS or storage dependencies: callers pass primitives plus a cached
 * review collection and receive the encoded JSON string, or null when there is
 * nothing worth emitting. That keeps the schema rules unit-testable without a
 * CMS context.
 */
class ReviewSchemaBuilder
{
    private const BEST_RATING = 5;

    public function build(
        string $sBusinessName,
        string $sBusinessType,
        float $fAggregateRating,
        int $iAggregateCount,
        ReviewCollection $obCollection
    ): ?string {
        if (trim($sBusinessName) === '') {
            return null;
        }

        if ($obCollection->isEmpty()) {
            return null;
        }

        $arSchema = [
            '@context' => 'https://schema.org',
            '@type' => $sBusinessType,
            'name' => $sBusinessName,
        ];

        // Omit the aggregateRating node entirely for a zero/empty count: a 0/0
        // AggregateRating is invalid schema and can suppress the rich result.
        if ($iAggregateCount > 0) {
            $arSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $fAggregateRating,
                'reviewCount' => $iAggregateCount,
            ];
        }

        $arSchema['review'] = $this->buildReviewNodes($obCollection);

        return (string) json_encode($arSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildReviewNodes(ReviewCollection $obCollection): array
    {
        $arReviewNodeList = [];

        /** @var ReviewItem $obReviewItem */
        foreach ($obCollection as $obReviewItem) {
            $arReviewNodeList[] = $this->buildReviewNode($obReviewItem);
        }

        return $arReviewNodeList;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewNode(ReviewItem $obReviewItem): array
    {
        $arReviewNode = [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => $obReviewItem->author_name,
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $obReviewItem->rating,
                'bestRating' => self::BEST_RATING,
            ],
        ];

        // Omit null schema fields rather than emitting explicit null values.
        if ($obReviewItem->text_english !== null) {
            $arReviewNode['reviewBody'] = $obReviewItem->text_english;
        }

        if ($obReviewItem->published_at_iso !== null) {
            $arReviewNode['datePublished'] = $obReviewItem->published_at_iso;
        }

        return $arReviewNode;
    }
}
