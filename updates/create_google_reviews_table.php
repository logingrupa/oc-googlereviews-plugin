<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Updates;

use Illuminate\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

/**
 * Creates the reviews table that mirrors Google Places review payloads.
 */
class CreateGoogleReviewsTable extends Migration
{
    private const TABLE_NAME = 'logingrupa_googlereviews_reviews';

    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $obTable): void {
            $obTable->increments('id');
            // 191 caps the unique-index key to fit the 767-byte prefix limit
            // on older MySQL/MariaDB (utf8mb4 = 4 bytes/char; 191 * 4 = 764).
            $obTable->string('google_review_id', 191)->unique();
            $obTable->string('author_name');
            $obTable->string('author_photo_url')->nullable();
            $obTable->string('author_url')->nullable();
            $obTable->unsignedTinyInteger('rating')->default(0);
            $obTable->text('text_english')->nullable();
            $obTable->text('text_original')->nullable();
            $obTable->string('original_language', 16)->nullable();
            $obTable->string('relative_time')->nullable();
            $obTable->timestamp('published_at')->nullable();
            $obTable->boolean('is_active')->default(true);
            $obTable->unsignedInteger('sort_order')->default(0);
            $obTable->timestamps();

            $obTable->index(['is_active', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
}
