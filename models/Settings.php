<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Models;

use Model;
use System\Behaviors\SettingsModel;

/**
 * Backend-managed configuration: API credentials, place id, aggregate snapshot.
 *
 * @method static self instance()
 * @method static mixed get(string $sKey, mixed $obDefault = null)
 * @method mixed set(array<string, mixed>|string $obKey, mixed $obValue = null)
 */
class Settings extends Model
{
    /**
     * @var array<int, class-string>
     */
    public $implement = [SettingsModel::class];

    public $settingsCode = 'logingrupa_googlereviews_settings';

    public $settingsFields = 'fields.yaml';

    public function getApiKey(): string
    {
        return trim((string) $this->get('api_key', ''));
    }

    public function getPlaceId(): string
    {
        return trim((string) $this->get('place_id', ''));
    }

    public function getMinRating(): int
    {
        $iMinRating = (int) $this->get('min_rating', 4);

        return max(1, min(5, $iMinRating));
    }

    public function getAggregateRating(): float
    {
        return (float) $this->get('aggregate_rating', 0);
    }

    public function getAggregateCount(): int
    {
        return (int) $this->get('aggregate_count', 0);
    }
}
