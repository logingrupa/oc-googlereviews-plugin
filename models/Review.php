<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Logingrupa\GoogleReviews\Classes\Item\ReviewItem;
use Logingrupa\GoogleReviews\Classes\Store\ActiveReviewListStore;
use Model;

/**
 * Persisted mirror of a single Google Places review.
 *
 * @property int         $id
 * @property string      $google_review_id
 * @property string      $author_name
 * @property string|null $author_photo_url
 * @property string|null $author_url
 * @property int         $rating
 * @property string|null $text_english
 * @property string|null $text_original
 * @property string|null $original_language
 * @property string|null $relative_time
 * @property \Carbon\Carbon|null $published_at
 * @property bool        $is_active
 * @property int         $sort_order
 */
class Review extends Model
{
    public $table = 'logingrupa_googlereviews_reviews';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'google_review_id',
        'author_name',
        'author_photo_url',
        'author_url',
        'rating',
        'text_english',
        'text_original',
        'original_language',
        'relative_time',
        'published_at',
        'is_active',
        'sort_order',
    ];

    /**
     * @var array<int, string>
     */
    protected $dates = ['published_at'];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param Builder<Review> $obQuery
     * @return Builder<Review>
     */
    public function scopeActive(Builder $obQuery): Builder
    {
        return $obQuery->where('is_active', true);
    }

    public function afterSave(): void
    {
        $this->flushReadCache();
    }

    public function afterDelete(): void
    {
        $this->flushReadCache();
    }

    private function flushReadCache(): void
    {
        ReviewItem::clearCache($this->id);
        ActiveReviewListStore::instance()->clear();
    }
}
