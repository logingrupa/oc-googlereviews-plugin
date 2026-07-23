<?php

declare(strict_types=1);

namespace Logingrupa\GoogleReviews;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Logingrupa\GoogleReviews\Components\ReviewList;
use Logingrupa\GoogleReviews\Console\FetchGoogleReviews;
use Logingrupa\GoogleReviews\Models\Settings;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;

/**
 * GoogleReviews plugin registration.
 *
 * Depends on Lovata.Toolbox for the cached Item/Collection/Store read layer.
 */
class Plugin extends PluginBase
{
    /**
     * @var array<int, string>
     */
    public $require = ['Lovata.Toolbox'];

    /**
     * @return array<string, string>
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'logingrupa.googlereviews::lang.plugin.name',
            'description' => 'logingrupa.googlereviews::lang.plugin.description',
            'author' => 'Logingrupa',
            'icon' => 'icon-star',
        ];
    }

    public function register(): void
    {
        $this->registerConsoleCommand('logingrupa.googlereviews.fetch', FetchGoogleReviews::class);
    }

    /**
     * @return array<class-string, string>
     */
    public function registerComponents(): array
    {
        return [
            ReviewList::class => 'reviewList',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label' => 'logingrupa.googlereviews::lang.settings.label',
                'description' => 'logingrupa.googlereviews::lang.settings.description',
                'category' => SettingsManager::CATEGORY_CMS,
                'icon' => 'icon-star',
                'class' => Settings::class,
                'order' => 500,
                'keywords' => 'google reviews places',
                'permissions' => ['logingrupa.googlereviews.access_settings'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function registerPermissions(): array
    {
        return [
            'logingrupa.googlereviews.access_settings' => [
                'tab' => 'logingrupa.googlereviews::lang.plugin.name',
                'label' => 'logingrupa.googlereviews::lang.settings.permission',
            ],
        ];
    }

    public function registerSchedule($schedule): void
    {
        assert($schedule instanceof Schedule);

        $schedule->command('googlereviews:fetch')
            ->weekly()
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onFailure(fn () => Log::error(
                'Google Reviews: scheduled fetch (googlereviews:fetch) failed.'
            ));
    }
}
