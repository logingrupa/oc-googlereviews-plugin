# Logingrupa.GoogleReviews (OctoberCMS plugin)

## What This Is

An OctoberCMS plugin that fetches a business's Google reviews, stores them locally, and renders them on the public site with `Review` / `AggregateRating` JSON-LD. Reads are cached through the Lovata.Toolbox Item/Collection/Store layer so page requests never hit the Google API. Shipped as a public MIT Composer package (`logingrupa/oc-googlereviews-plugin`, repo `github.com/logingrupa/oc-googlereviews-plugin`) installable on any OctoberCMS 4.2–4.3 site.

## Core Value

Show a business's real Google reviews on its own site, fast and SEO-rich (JSON-LD), without hitting Google on every page load. **v2.0 raises the ceiling: read the FULL review history, not just the 5 the Places API caps at.**

## Requirements

### Validated

<!-- Shipped in v1.x (see MILESTONES.md). Locked. -->

- ✓ Places API (New) client → `ReviewDto` / `AuthorDto` / `PlaceDetailsDto` — v1.0.0
- ✓ `ReviewSynchronizer` upserts DTOs into `logingrupa_googlereviews_reviews` (dedupe by `google_review_id`, min-rating filter, empty-batch floor guard) — v1.0.0
- ✓ `ReviewFetcher` orchestrates fetch → sync → persist aggregate — v1.0.3
- ✓ `googlereviews:fetch` console command, scheduled weekly — v1.0.0
- ✓ Settings model with `api_key` encrypted at rest (Encryptable; explicit decrypt in `getApiKey()`) — v1.0.3/1.0.4
- ✓ Backend live-preview form widget with AJAX "Fetch now" — v1.0.3
- ✓ `reviewList` frontend component: grid / list / vanilla scroll-snap slider; order best/newest/relevance; shuffle; autoplay; JSON-LD; registered as RainLab.Pages snippet — v1.0.6
- ✓ Toolbox cached read layer: `ActiveReviewListStore` / `ReviewItem` / `ReviewCollection` — v1.0.0

### Active

<!-- v2.0 scope. See REQUIREMENTS.md for full REQ-IDs. -->

- [ ] Second review source: **Business Profile API** (OAuth) fetching ALL reviews for a location
- [ ] Selectable source in Settings (`review_source` = `places` default | `business_profile`)
- [ ] OAuth 2.0 lifecycle in backend: connect / refresh / revoke, encrypted refresh token
- [ ] Account + location discovery and selection
- [ ] Full paginated sync via existing `ReviewSynchronizer`; `starRating` enum→int; nullable author fields
- [ ] Location aggregate (`averageRating`, `totalReviewCount`)
- [ ] Frontend "best + random cycle N" now draws from the full pool (>5 reviews)
- [ ] README documents Google's access-approval + OAuth prerequisites; plugin degrades gracefully on 403

### Out of Scope

- Writing/replying to reviews via the API — v2.0 is read-only; reply management is a separate concern
- Multi-location aggregation in one component — one selected location per install for v2.0 (YAGNI)
- Marketplace/Packagist publishing — install stays Git-based via `plugin:install --from`
- Real-time / on-page-load fetch from Business Profile — quotas are low; sync is scheduled only

## Context

- **Stack:** OctoberCMS 4.2–4.3 on Laravel 12. Runtime PHP 8.4 on the reference host, but code MUST stay **PHP 8.2+ compatible** (installs on 8.2/8.3 client sites). Depends on `lovata/toolbox-plugin ^2.3`.
- **Current version:** v1.0.6. v2.0.0 is the next tag.
- **Why Business Profile API:** Google **Places API (New)** returns at most **5 reviews** ("most relevant", no pagination) — a hard cap. Reading the full history requires the **Google Business Profile APIs** (formerly Google My Business): OAuth 2.0 as the business owner + Google-approved API access. Only legitimate route to all reviews.
- **Access gate:** Business Profile API is access-restricted. The site owner must (a) create OAuth credentials, (b) apply for and be granted Business Profile API access, (c) verify business ownership. Until approved the reviews endpoint returns **403** — the plugin must degrade gracefully and tell the user exactly what to do.
- **Key existing classes to reuse (not fork):** `classes/api/GooglePlacesClient.php`, `classes/fetch/ReviewSynchronizer.php`, `classes/fetch/ReviewFetcher.php`, `models/Settings.php`, `formwidgets/ReviewPreview.php`, `components/ReviewList.php`, `classes/store/ActiveReviewListStore.php`, `classes/item/ReviewItem.php`, `classes/collection/ReviewCollection.php`.
- **Reviews table columns:** `google_review_id` (unique, 191), `author_name`, `author_photo_url`, `author_url`, `rating`, `text_english`, `text_original`, `original_language`, `relative_time`, `published_at`, `is_active`, `sort_order`, timestamps.
- **Planning location note:** this `.planning/` lives inside the composer-installed plugin dir on prod (gitignored, ephemeral). Canonical dev happens in the plugin's own git repo locally — copy `.planning/` there; then GSD tooling (`/gsd-plan-phase`, gsd-tools) works with `.planning/` at repo root.

## Constraints

- **Tech stack**: PHP 8.2+ compatible — no 8.4-only syntax. `declare(strict_types=1)` everywhere.
- **Coding rules**: Hungarian notation on every variable/param/property (`s`/`i`/`f`/`b`/`ar`/`ob`/`fn`/`dt`); no abbreviations; max control-flow nesting depth 2 (guard clauses, extract methods); DRY, SRP, fail-fast (validate public params, throw on violation; never swallow `\Throwable` — catch specific, log, rethrow/convert; no defensive `?? null` that hides missing values); YAGNI.
- **Security**: OAuth refresh token + client secret encrypted at rest (October `Encryptable`, same explicit-decrypt pattern as `getApiKey()`).
- **Architecture seam**: `ReviewSourceInterface` (`fetch()` → DTOs + aggregate). `PlacesReviewSource` wraps existing client; `BusinessProfileReviewSource` is new. `ReviewFetcher` selects source from Settings. Keep `ReviewSynchronizer` source-agnostic.
- **Migrations**: additive only. October `updates/version.yaml` unprefixed `2.0.0:` key + migration file in `updates/`.
- **Backward compat**: existing Places users keep working unchanged after upgrade (default `review_source = places`).
- **Gates (must stay green)**: PHPStan level **max** (`phpstan.neon`, scope `classes,console,components` + `scanDirectories: models`); PHPMD (`phpmd.xml`, StaticAccess excluded); php8.2 + php8.4 lint clean; PHPUnit + `PluginTestCase` with `Http::fake()`.
- **Quotas**: Business Profile APIs have low default quotas — sync on schedule (daily/weekly), never per page load; cache via Toolbox.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Add Business Profile API as a second, selectable source (not replace Places) | Places needs no OAuth and stays the simple default; Business Profile unlocks all reviews for owners who complete Google's approval | — Pending |
| `ReviewSourceInterface` seam; `ReviewSynchronizer` stays source-agnostic | Reuse the dedupe/upsert layer for both sources; isolate the new API behind one contract (SRP/DRY) | — Pending |
| Default `review_source = places` | Zero-touch backward compatibility for existing installs on upgrade | — Pending |
| Sync scheduled only, never on page load | Business Profile quotas are low; Toolbox cache serves reads | — Pending |
| Plan lives in plugin-local `.planning/`, dev continues in the plugin's own repo | Prod install is gitignored/ephemeral; the GitHub repo is the canonical dev home | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-07-23 — milestone v2.0 (business-profile all-reviews) started*
