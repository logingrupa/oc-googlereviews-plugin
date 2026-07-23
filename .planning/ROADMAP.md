# Roadmap: Logingrupa.GoogleReviews — v2.0

## Overview

v2.0 adds a second, selectable review source — the **Google Business Profile API** (OAuth) — to fetch a location's FULL review history alongside the existing Places API (5-cap, no OAuth). Build order goes seam → auth → location → sync → verify/ship, so each phase leaves the plugin releasable and Places users unaffected. Milestone goal: with an approved+connected Google account and `review_source = business_profile`, the plugin pulls ALL reviews for the selected location into the existing table and frontend; `review_source = places` still behaves exactly as v1.x; all gates green.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked INSERTED)

- [ ] **Phase 1: Source abstraction + selectable source** — Introduce `ReviewSourceInterface`, wrap the existing Places client as `PlacesReviewSource`, make `ReviewFetcher` dispatch on a `review_source` Settings dropdown (default `places`), and show source-specific fields. Pure refactor: no behaviour change, full backward compatibility.
- [ ] **Phase 2: OAuth lifecycle + encrypted token storage** — `GoogleOAuthService` (auth URL, code exchange, refresh, revoke), backend callback route, connect/disconnect actions, additive migration for encrypted refresh-token/client-secret storage, graceful reconnect state.
- [ ] **Phase 3: Account + location discovery and selection** — List the authorized account's Business Profile accounts/locations and let the admin pick one; store `account_id` + `location_id`.
- [ ] **Phase 4: Full paginated review sync (Business Profile)** — `BusinessProfileClient` (pagination, enum→int, comment split, null-safe authors) + `BusinessProfileReviewSource` upserting through the existing `ReviewSynchronizer`; persist location aggregate; scheduled-only; 403 degrades gracefully. Full test coverage.
- [ ] **Phase 5: Full-pool frontend verification, docs, release** — Verify "best + random cycle N" over >5 reviews with the rest of the frontend unchanged; write the README prerequisites/OAuth section; run all gates and ship v2.0.0.

## Phase Details

### Phase 1: Source abstraction + selectable source
**Goal**: Establish the clean seam without changing any runtime behaviour. `ReviewSourceInterface` defines `fetch()` → DTOs + aggregate. `PlacesReviewSource` wraps `GooglePlacesClient` and reproduces v1.x exactly. `ReviewFetcher` reads `review_source` from Settings and dispatches; the Settings form gains a `review_source` dropdown (default `places`) that toggles which source's fields are visible. `ReviewSynchronizer` stays source-agnostic.

**Depends on**: Nothing (first phase)
**Requirements**: SRC-01, SRC-02, SRC-03, SRC-04, SRC-05
**Success Criteria** (what must be TRUE):
  1. `ReviewSourceInterface` exists with a `fetch()` method returning review DTOs plus an aggregate; `ReviewSynchronizer` has no source-specific branches.
  2. `PlacesReviewSource implements ReviewSourceInterface` and wraps the existing `GooglePlacesClient`; `googlereviews:fetch` with default settings produces byte-identical results to v1.0.6.
  3. Settings has a `review_source` dropdown defaulting to `places`; selecting a value shows only that source's fields.
  4. `ReviewFetcher` selects the source from Settings (no hard-coded Places dependency).
  5. Existing PHPUnit suite stays green; a new test proves `review_source` selection returns the correct source instance.

### Phase 2: OAuth lifecycle + encrypted token storage
**Goal**: A backend admin can connect and disconnect a Google account; the refresh token and client secret are stored encrypted; access tokens auto-refresh; expiry/revocation surfaces a reconnect state instead of a fatal. Additive migration only.

**Depends on**: Phase 1 (source seam + Settings fields per source)
**Requirements**: OAUTH-01, OAUTH-02, OAUTH-03, OAUTH-04, OAUTH-05, OAUTH-06, DOC-02
**Success Criteria** (what must be TRUE):
  1. A "Connect Google account" button starts the consent flow (scope `https://www.googleapis.com/auth/business.manage`); a backend callback route completes the code exchange.
  2. The refresh token and client secret are stored encrypted at rest (Encryptable) and decrypted explicitly, mirroring `getApiKey()`.
  3. `GoogleOAuthService` refreshes the access token from the refresh token; a unit test with `Http::fake()` proves refresh success.
  4. On invalid_grant / revocation the service logs and returns a reconnect state; a test proves no `\Throwable` escapes and the UI shows "reconnect".
  5. "Disconnect" clears all stored tokens and connection state.
  6. `updates/version.yaml` gains a `2.0.0:` key with an additive migration adding the token/location columns; existing Places installs load unchanged with `review_source = places`.

### Phase 3: Account + location discovery and selection
**Goal**: After connecting, the admin sees the account's Business Profile locations and picks the one to sync; the choice persists as `account_id` + `location_id`.

**Depends on**: Phase 2 (a valid access token)
**Requirements**: LOC-01, LOC-02
**Success Criteria** (what must be TRUE):
  1. The plugin calls the accounts endpoint (`mybusinessaccountmanagement.googleapis.com/v1/accounts`) and the locations endpoint (`mybusinessbusinessinformation.googleapis.com/v1/{parent}/locations`) and lists results in the backend.
  2. The admin can select a location; `account_id` + `location_id` are stored in Settings.
  3. A test with `Http::fake()` proves the account/location lists parse correctly and the selection persists.
  4. With no location selected, the Business Profile sync is a no-op with a clear "pick a location" message (never a fatal).

### Phase 4: Full paginated review sync (Business Profile)
**Goal**: Pull ALL reviews for the selected location through the existing synchronizer. `BusinessProfileClient` paginates (`pageSize` + `nextPageToken`), maps `starRating` enum→int, splits the "(Translated by Google)" comment into English/original, handles null author fields, and maps `createTime`/`updateTime` to `published_at`. `BusinessProfileReviewSource` implements the interface and persists the location aggregate. Sync is scheduled-only; 403 degrades gracefully.

**Depends on**: Phase 3 (selected `account_id` + `location_id`)
**Requirements**: SYNC-01, SYNC-02, SYNC-03, SYNC-04, SYNC-05, SYNC-06, QA-01
**Success Criteria** (what must be TRUE):
  1. `BusinessProfileClient` walks every page via `nextPageToken`; a multi-page `Http::fake()` test proves all pages are consumed and no review is dropped.
  2. `starRating` `ONE`..`FIVE` maps to `1`..`5`; a unit test covers every enum value.
  3. `comment` splits into `text_english` / `text_original` on the "(Translated by Google)" marker; null `author_url`/`photoUrl` map to null without error.
  4. Reviews upsert through the unchanged `ReviewSynchronizer` (dedupe by `google_review_id`); `createTime`/`updateTime` populate `published_at`.
  5. `BusinessProfileReviewSource implements ReviewSourceInterface` and persists the location aggregate (`averageRating`, `totalReviewCount`, or computed).
  6. A 403 from the reviews endpoint logs and surfaces actionable guidance (access-not-approved) without a fatal; a test proves the graceful path. Sync runs only via the scheduled command.

### Phase 5: Full-pool frontend verification, docs, release
**Goal**: Prove the frontend works over the full review pool, document the Google prerequisites, pass all gates, and ship v2.0.0.

**Depends on**: Phase 4 (full review pool present)
**Requirements**: FE-01, FE-02, DOC-01, QA-02
**Success Criteria** (what must be TRUE):
  1. With >5 reviews stored, "order by best + random cycle N" draws from the full pool; a test/manual check confirms N distinct cards cycle from more than 5 candidates.
  2. All other frontend behaviour (grid/list/slider, order, autoplay, JSON-LD) is unchanged versus v1.0.6 for the >5-review case.
  3. README documents the Business Profile prerequisites (create OAuth credentials, apply for + be granted API access, verify ownership) and the connect flow; the plugin's 403 message points the user to these steps.
  4. All gates green: PHPStan max, PHPMD, php8.2 + php8.4 lint, full PHPUnit suite.
  5. v2.0.0 is tagged and released per the project workflow (commit → tag → push → `composer update` → migrate → cache clear → FPM reload); `plugin:list` shows 2.0.0 and prod returns HTTP 200 with Settings rendering.

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Source abstraction + selectable source | 0/? | Not started | — |
| 2. OAuth lifecycle + encrypted token storage | 0/? | Not started | — |
| 3. Account + location discovery and selection | 0/? | Not started | — |
| 4. Full paginated review sync (Business Profile) | 0/? | Not started | — |
| 5. Full-pool frontend verification, docs, release | 0/? | Not started | — |

---
*Roadmap created: 2026-07-23 — milestone v2.0 (5 phases, 25 requirements, all covered)*
