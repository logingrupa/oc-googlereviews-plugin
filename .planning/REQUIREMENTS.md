# Requirements: Logingrupa.GoogleReviews — v2.0

**Defined:** 2026-07-23
**Core Value:** Show a business's real Google reviews on its own site, fast and SEO-rich — v2.0 reads the FULL review history, not just the Places 5-cap.

## v2.0 Requirements

Requirements for the v2.0.0 release. Each maps to exactly one roadmap phase.

### Source selection (SRC)

- [ ] **SRC-01**: `ReviewSourceInterface` defines a `fetch()` contract returning review DTOs plus an aggregate; `ReviewSynchronizer` stays source-agnostic
- [ ] **SRC-02**: `PlacesReviewSource` wraps the existing `GooglePlacesClient` and reproduces v1.x behaviour exactly
- [ ] **SRC-03**: Admin can select the source in Settings via a `review_source` dropdown (`places` default | `business_profile`)
- [ ] **SRC-04**: Settings shows only the fields relevant to the selected source (Places fields hidden under Business Profile and vice-versa)
- [ ] **SRC-05**: `ReviewFetcher` resolves and dispatches to the source chosen in Settings

### OAuth lifecycle (OAUTH)

- [ ] **OAUTH-01**: Admin can start the Google consent flow from a "Connect Google account" button in Settings
- [ ] **OAUTH-02**: A backend controller route serves the OAuth redirect callback and completes the authorization-code exchange
- [ ] **OAUTH-03**: The refresh token (and client secret) are stored encrypted at rest via October `Encryptable`, decrypted explicitly (same pattern as `getApiKey()`)
- [ ] **OAUTH-04**: The access token is auto-refreshed from the stored refresh token when expired
- [ ] **OAUTH-05**: Token expiry or revocation is handled gracefully — logged and surfaced as a "reconnect" state, never fatal
- [ ] **OAUTH-06**: Admin can disconnect, clearing all stored tokens and connection state

### Location selection (LOC)

- [ ] **LOC-01**: After connecting, the plugin lists the authorized account's Business Profile accounts and locations
- [ ] **LOC-02**: Admin can pick which location's reviews to sync; the plugin stores `account_id` + `location_id`

### Full sync (SYNC)

- [ ] **SYNC-01**: `BusinessProfileClient` paginates through ALL reviews for the selected location (`pageSize` + `nextPageToken`) and maps them to the existing `ReviewDto`
- [ ] **SYNC-02**: `starRating` enum (`ONE`..`FIVE`) is converted to an integer rating
- [ ] **SYNC-03**: `comment` is parsed into English + original text, splitting on the "(Translated by Google)" marker; nullable `author_url` / `photoUrl` are handled without error
- [ ] **SYNC-04**: `createTime` / `updateTime` map to `published_at`; all reviews upsert through the existing `ReviewSynchronizer` (dedupe by `google_review_id`)
- [ ] **SYNC-05**: `BusinessProfileReviewSource` implements `ReviewSourceInterface` and persists the location aggregate (`averageRating`, `totalReviewCount`, or computed from the full set)
- [ ] **SYNC-06**: Sync runs only on the scheduled command (never on page load); a 403 (access not approved) degrades gracefully with actionable guidance

### Frontend on full pool (FE)

- [ ] **FE-01**: "Order by best + random cycle N" draws from the full review pool and is verified working with more than 5 reviews
- [ ] **FE-02**: All other existing frontend behaviour (grid/list/slider, order, autoplay, JSON-LD) is unchanged when the source has >5 reviews

### Docs & compatibility (DOC)

- [ ] **DOC-01**: README documents the Business Profile prerequisites — create OAuth credentials, apply for and be granted API access, verify business ownership — and the OAuth connect flow
- [ ] **DOC-02**: Additive migration (`updates/version.yaml` `2.0.0:` key + migration file) adds token/location storage; existing Places installs keep working unchanged on upgrade (default `review_source = places`)

### Quality gates (QA)

- [ ] **QA-01**: PHPUnit + `PluginTestCase` with `Http::fake()` cover: enum→int rating mapping, multi-page pagination (`nextPageToken`), token refresh success + expiry/revocation, source selection, and empty/403 handling; Settings-touching tests flush cache + `SettingsModel::clearInternalCache()` in `setUp()`
- [ ] **QA-02**: All gates green — PHPStan level max, PHPMD, php8.2 + php8.4 lint clean — and v2.0.0 released per the project workflow (tag, `composer update`, migrate, cache clear, FPM reload, prod verify)

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Replying to / writing reviews via the API | v2.0 is read-only; reply management is a separate concern |
| Multi-location aggregation in one component | One selected location per install for v2.0 (YAGNI) |
| Marketplace / Packagist publishing | Install stays Git-based via `plugin:install --from` |
| On-page-load Business Profile fetch | Quotas too low; sync is scheduled only, reads served from Toolbox cache |
| Forking `ReviewSynchronizer` per source | Reuse the source-agnostic upsert layer; extend only if strictly needed |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| SRC-01 | Phase 1 | Pending |
| SRC-02 | Phase 1 | Pending |
| SRC-03 | Phase 1 | Pending |
| SRC-04 | Phase 1 | Pending |
| SRC-05 | Phase 1 | Pending |
| OAUTH-01 | Phase 2 | Pending |
| OAUTH-02 | Phase 2 | Pending |
| OAUTH-03 | Phase 2 | Pending |
| OAUTH-04 | Phase 2 | Pending |
| OAUTH-05 | Phase 2 | Pending |
| OAUTH-06 | Phase 2 | Pending |
| DOC-02 | Phase 2 | Pending |
| LOC-01 | Phase 3 | Pending |
| LOC-02 | Phase 3 | Pending |
| SYNC-01 | Phase 4 | Pending |
| SYNC-02 | Phase 4 | Pending |
| SYNC-03 | Phase 4 | Pending |
| SYNC-04 | Phase 4 | Pending |
| SYNC-05 | Phase 4 | Pending |
| SYNC-06 | Phase 4 | Pending |
| FE-01 | Phase 5 | Pending |
| FE-02 | Phase 5 | Pending |
| DOC-01 | Phase 5 | Pending |
| QA-01 | Phase 4 | Pending |
| QA-02 | Phase 5 | Pending |

**Coverage:**
- v2.0 requirements: 25 total
- Mapped to phases: 25
- Unmapped: 0 ✓

Note: QA-01 (test coverage) is anchored to Phase 4 (the sync/enum/pagination surface it most exercises) but is a standing expectation across every phase — each phase ships its own tests. QA-02 (all gates green + release) anchors to Phase 5 as the final gate before tagging v2.0.0.

---
*Requirements defined: 2026-07-23*
*Last updated: 2026-07-23 after initial v2.0 definition*
