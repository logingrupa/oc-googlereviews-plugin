# Milestones: Logingrupa.GoogleReviews

Historical record of shipped milestones. v1.x predates the GSD pipeline — reconstructed from `updates/version.yaml`.

## v1.x — Places API foundation (shipped, pre-GSD)

**Goal:** Fetch and display Google reviews via Places API (New), cached, SEO-rich.

| Tag | Shipped |
|-----|---------|
| v1.0.0 | Places API (New) weekly fetch, English-translated reviews, Toolbox cached read layer, `reviewList` component with Review/AggregateRating JSON-LD |
| v1.0.1 | Move backend settings into the Frontend category |
| v1.0.2 | Settings under October's CMS/Frontend settings group; document Git-based install |
| v1.0.3 | Encrypt API key at rest (Encryptable) + masked field; backend live-preview widget with AJAX Fetch-now; extract shared `ReviewFetcher` |
| v1.0.4 | Fix API key decryption (`SettingsModel::get()` bypasses accessor → `getApiKey()` decrypts explicitly, legacy-plaintext fallback); encryption round-trip tests |
| v1.0.5 | Fix backend settings 500 — lowercase `formwidgets` dir for autoload; correct `FormField::NO_SAVE_DATA` |
| v1.0.6 | Frontend styling + layouts: restyleable CSS (`--gr-*`), grid/list/vanilla slider (arrows, dots, autoplay/shuffle, reduced-motion), order best/newest/relevance, overall-rating summary, RainLab.Pages snippet |

**Status:** Complete — current production version v1.0.6.

## v2.0 — Business Profile all-reviews (in planning)

**Goal:** Add a second, selectable review source (Google Business Profile API, OAuth) to fetch ALL reviews for a location, feeding the existing frontend. Places source remains the no-OAuth default.

**Status:** Requirements + roadmap defined 2026-07-23. Not yet started.
