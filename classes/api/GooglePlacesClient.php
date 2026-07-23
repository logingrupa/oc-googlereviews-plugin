<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Api;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Thin client for the Places API (New) place-details endpoint.
 *
 * Single responsibility: perform the authenticated request for one place and
 * map the JSON payload into immutable DTOs. It never touches the database,
 * cache, or settings storage — callers pass primitives in and receive a
 * {@see PlaceDetailsDto} out.
 */
class GooglePlacesClient
{
    private const ENDPOINT_TEMPLATE = 'https://places.googleapis.com/v1/places/%s';

    private const FIELD_MASK = 'id,rating,userRatingCount,reviews';

    private const REQUEST_LANGUAGE = 'en';

    private const TIMEOUT_SECONDS = 15;

    public function __construct(private readonly HttpFactory $obHttp)
    {
    }

    /**
     * Fetch place details, translating reviews into English.
     *
     * @throws GooglePlacesException on transport failure or a non-success status.
     */
    public function fetchPlaceDetails(string $sApiKey, string $sPlaceId): PlaceDetailsDto
    {
        if (trim($sApiKey) === '') {
            throw new InvalidArgumentException('Google Places API key must not be empty.');
        }

        if (trim($sPlaceId) === '') {
            throw new InvalidArgumentException('Google Places place id must not be empty.');
        }

        $obResponse = $this->requestPlaceDetails($sApiKey, $sPlaceId);

        if ($obResponse->failed()) {
            throw new GooglePlacesException(sprintf(
                'Places API returned HTTP %d: %s',
                $obResponse->status(),
                $obResponse->body(),
            ));
        }

        return $this->mapResponse($obResponse->json());
    }

    private function requestPlaceDetails(string $sApiKey, string $sPlaceId): Response
    {
        try {
            return $this->obHttp
                ->timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'X-Goog-Api-Key' => $sApiKey,
                    'X-Goog-FieldMask' => self::FIELD_MASK,
                ])
                ->get(sprintf(self::ENDPOINT_TEMPLATE, rawurlencode($sPlaceId)), [
                    'languageCode' => self::REQUEST_LANGUAGE,
                ]);
        } catch (Throwable $obThrowable) {
            throw new GooglePlacesException(
                'Places API request failed: ' . $obThrowable->getMessage(),
                (int) $obThrowable->getCode(),
                $obThrowable,
            );
        }
    }

    private function mapResponse(mixed $obPayload): PlaceDetailsDto
    {
        if (!is_array($obPayload)) {
            throw new GooglePlacesException('Places API returned an empty or malformed body.');
        }

        $fAverageRating = $this->castFloat($obPayload['rating'] ?? null);
        $iReviewCount = $this->castInt($obPayload['userRatingCount'] ?? null);
        $arReviewList = $this->mapReviewList($obPayload['reviews'] ?? []);

        return new PlaceDetailsDto($fAverageRating, $iReviewCount, $arReviewList);
    }

    /**
     * @return array<int, ReviewDto>
     */
    private function mapReviewList(mixed $obRawReviewList): array
    {
        if (!is_array($obRawReviewList)) {
            return [];
        }

        $arReviewList = [];
        foreach ($obRawReviewList as $obRawReview) {
            if (!is_array($obRawReview)) {
                continue;
            }

            $arReviewList[] = $this->mapReview($obRawReview);
        }

        return $arReviewList;
    }

    /**
     * @param array<array-key, mixed> $arRawReview
     */
    private function mapReview(array $arRawReview): ReviewDto
    {
        return new ReviewDto(
            sGoogleReviewId: $this->castString($arRawReview['name'] ?? null),
            obAuthor: $this->mapAuthor($arRawReview['authorAttribution'] ?? null),
            iRating: $this->castInt($arRawReview['rating'] ?? null),
            sTextEnglish: $this->nullableString($this->nestedValue($arRawReview, 'text', 'text')),
            sTextOriginal: $this->nullableString($this->nestedValue($arRawReview, 'originalText', 'text')),
            sOriginalLanguage: $this->nullableString($this->nestedValue($arRawReview, 'originalText', 'languageCode')),
            sRelativeTime: $this->nullableString($arRawReview['relativePublishTimeDescription'] ?? null),
            dtPublishedAt: $this->parsePublishTime($arRawReview['publishTime'] ?? null),
        );
    }

    private function mapAuthor(mixed $obAuthor): AuthorDto
    {
        $arAuthor = is_array($obAuthor) ? $obAuthor : [];

        return new AuthorDto(
            sName: $this->castString($arAuthor['displayName'] ?? null),
            sPhotoUrl: $this->nullableString($arAuthor['photoUri'] ?? null),
            sProfileUrl: $this->nullableString($arAuthor['uri'] ?? null),
        );
    }

    /**
     * @param array<array-key, mixed> $arSource
     */
    private function nestedValue(array $arSource, string $sOuterKey, string $sInnerKey): mixed
    {
        $obOuter = $arSource[$sOuterKey] ?? null;

        return is_array($obOuter) ? ($obOuter[$sInnerKey] ?? null) : null;
    }

    private function castFloat(mixed $obValue): float
    {
        return is_numeric($obValue) ? (float) $obValue : 0.0;
    }

    private function castInt(mixed $obValue): int
    {
        return is_numeric($obValue) ? (int) $obValue : 0;
    }

    private function castString(mixed $obValue): string
    {
        return is_string($obValue) ? $obValue : '';
    }

    private function nullableString(mixed $obValue): ?string
    {
        if (!is_string($obValue)) {
            return null;
        }

        $sValue = trim($obValue);

        return $sValue === '' ? null : $sValue;
    }

    private function parsePublishTime(mixed $obPublishTime): ?Carbon
    {
        $sPublishTime = $this->nullableString($obPublishTime);

        if ($sPublishTime === null) {
            return null;
        }

        try {
            return Carbon::parse($sPublishTime);
        } catch (InvalidFormatException $obInvalidFormat) {
            Log::warning('Google Reviews: unparseable review publishTime skipped.', [
                'publishTime' => $sPublishTime,
                'exception' => $obInvalidFormat->getMessage(),
            ]);

            return null;
        }
    }
}
