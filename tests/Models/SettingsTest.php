<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews\Tests\Models;

use Db;
use Illuminate\Support\Facades\Cache;
use Logingrupa\GoogleReviews\Models\Settings;
use PluginTestCase;
use System\Behaviors\SettingsModel;

class SettingsTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        SettingsModel::clearInternalCache();
    }

    public function testApiKeyIsEncryptedAtRestButDecryptedOnRead(): void
    {
        Settings::set('api_key', 'SECRET-xyz-123');

        $this->assertSame('SECRET-xyz-123', Settings::instance()->getApiKey());

        $sRawStored = (string) Db::table('system_settings')
            ->where('item', 'logingrupa_googlereviews_settings')
            ->value('value');
        $this->assertStringNotContainsString('SECRET-xyz-123', $sRawStored);
    }

    public function testEmptyApiKeyReturnsEmptyString(): void
    {
        $this->assertSame('', Settings::instance()->getApiKey());
    }

    public function testMinRatingClampsToOneThroughFive(): void
    {
        Settings::set('min_rating', 9);
        $this->assertSame(5, Settings::instance()->getMinRating());

        Settings::set('min_rating', 0);
        $this->assertSame(1, Settings::instance()->getMinRating());
    }
}
