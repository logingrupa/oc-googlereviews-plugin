# Google Reviews plugin for OctoberCMS

Fetch Google Business Profile reviews via the **Places API (New)**, translated to English, store them locally, and render them with `Review` / `AggregateRating` JSON-LD. Reads are cached through the [Lovata.Toolbox](https://github.com/lovata/oc-toolbox-plugin) Item / Collection / Store layer, so page requests never hit the Google API.

## Requirements

- OctoberCMS 4.2–4.3
- PHP 8.2+ (runs on 8.2 / 8.3 / 8.4)
- `Lovata.Toolbox` plugin (installed automatically as a dependency)
- A Google Places API (New) key with billing enabled, and your Business Profile Place ID

## Installation

This plugin is not on the OctoberCMS Marketplace or Packagist yet, so install it
straight from the Git repository with October's native installer.

**From Git (recommended):**

```bash
php artisan plugin:install Logingrupa.GoogleReviews \
    --from=git@github.com:logingrupa/oc-googlereviews-plugin.git --oc
```

- `--oc` is required because the package name carries the `oc-` prefix
  (`logingrupa/oc-googlereviews-plugin`).
- `plugin:install` adds the repository to `composer.json` and runs Composer under
  the hood, so the plugin is Composer-managed and survives deploys.

Pin a specific tag or branch with `--want`:

```bash
# a released tag
php artisan plugin:install Logingrupa.GoogleReviews \
    --from=git@github.com:logingrupa/oc-googlereviews-plugin.git --oc --want=v1.0.1

# a development branch
php artisan plugin:install Logingrupa.GoogleReviews \
    --from=git@github.com:logingrupa/oc-googlereviews-plugin.git --oc --want=dev-master
```

Then run migrations (the installer usually does this automatically):

```bash
php artisan october:migrate
```

**Via Composer** (once published to Packagist, or if you add the VCS repository
manually to the root `composer.json`):

```bash
composer require logingrupa/oc-googlereviews-plugin
php artisan october:migrate
```

## Configuration

Backend → **Settings → Google Reviews**:

| Setting | Description |
|---|---|
| Google Places API key | Places API (New) key with billing enabled |
| Google Place ID | The place identifier of your Business Profile |
| Max reviews to display | 1–5 (Google returns at most 5) |
| Minimum rating to store | Reviews below this star rating are skipped |

## Fetching reviews

The plugin registers a weekly scheduled fetch. Ensure the OctoberCMS scheduler runs (Forge: add a scheduled job `php artisan schedule:run` every minute).

Manual fetch:

```bash
php artisan googlereviews:fetch
```

## Displaying reviews

Drop the `reviewList` component on any CMS page or partial:

```twig
[reviewList]
maxItems = 5
businessName = "NAIS Cosmetics"
businessType = "LocalBusiness"
renderSchema = 1
==
{% component 'reviewList' %}
```

- `maxItems` — how many reviews to render (0 = all stored)
- `businessName` — required for JSON-LD schema output; leave empty to disable schema
- `businessType` — any schema.org business type (default `LocalBusiness`)
- `renderSchema` — emit `Review` / `AggregateRating` JSON-LD

Both the English translation (`text_english`) and the untranslated original (`text_original`) are stored, so multilingual sites can render either per active language.

## Translation

Requesting `languageCode=en` makes Google machine-translate each review; the untranslated text is preserved in `originalText`. Attribution to Google is required by the Places API Terms of Service when displaying reviews.

## Development

```bash
# from the plugin directory
phpunit                                   # tests (PHPUnit + PluginTestCase)
vendor/bin/phpstan analyse -c phpstan.neon   # static analysis (level max)
vendor/bin/phpmd classes,console,components,models text phpmd.xml
```

## License

MIT — see [LICENSE](LICENSE).
