<?php

declare(strict_types=1);

return [
    'plugin' => [
        'name' => 'Google Reviews',
        'description' => 'Fetch and display Google Business Profile reviews, translated to English.',
    ],
    'settings' => [
        'label' => 'Google Reviews',
        'description' => 'Configure the Google Places credentials and fetch options.',
        'permission' => 'Manage Google Reviews settings',
        'api_key' => 'Google Places API key',
        'api_key_comment' => 'A Places API (New) key with billing enabled.',
        'place_id' => 'Google Place ID',
        'place_id_comment' => 'The place identifier of your Business Profile.',
        'min_rating' => 'Minimum rating to store',
        'min_rating_comment' => 'Reviews below this star rating are skipped.',
    ],
    'component' => [
        'name' => 'Review List',
        'description' => 'Displays the cached Google reviews with optional JSON-LD schema.',
        'max_items' => 'Maximum reviews to render',
        'max_items_invalid' => 'Maximum reviews must be a whole number.',
        'business_name' => 'Business name (for JSON-LD schema)',
        'business_type' => 'Schema.org business type',
        'render_schema' => 'Render Review/AggregateRating JSON-LD',
        'aria_label' => 'Customer reviews from Google',
        'attribution' => 'Reviews from Google',
        'author_profile_label' => 'View :author on Google (opens in a new tab)',
    ],
];
