<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Classes\Api;

/**
 * Immutable author attribution for a Google review.
 */
readonly class AuthorDto
{
    public function __construct(
        public string $sName,
        public ?string $sPhotoUrl,
        public ?string $sProfileUrl,
    ) {
    }
}
