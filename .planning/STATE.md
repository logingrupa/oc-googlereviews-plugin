---
gsd_state_version: 1.0
milestone: v2.0
milestone_name: business-profile-all-reviews
status: planning
stopped_at: "Milestone v2.0 planning created (PROJECT.md, REQUIREMENTS.md, ROADMAP.md). Ready to plan Phase 1."
last_updated: "2026-07-23T00:00:00.000Z"
last_activity: 2026-07-23
progress:
  total_phases: 5
  completed_phases: 0
  total_plans: 0
  completed_plans: 0
  percent: 0
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-07-23)

**Core value:** Show a business's real Google reviews on its own site, fast and SEO-rich — v2.0 reads the FULL review history, not just the Places 5-cap.
**Current focus:** Phase 1 — source abstraction + selectable source

## Current Position

Phase: Not started (defining requirements complete)
Plan: —
Status: Defining requirements → roadmap approved; ready to plan Phase 1
Last activity: 2026-07-23 — Milestone v2.0 started

Progress: [░░░░░░░░░░] 0%

## Milestone Roadmap

| # | Phase | Requirements | Status |
|---|-------|--------------|--------|
| 1 | Source abstraction + selectable source | SRC-01..05 | Not started |
| 2 | OAuth lifecycle + encrypted token storage | OAUTH-01..06, DOC-02 | Not started |
| 3 | Account + location discovery and selection | LOC-01, LOC-02 | Not started |
| 4 | Full paginated review sync (Business Profile) | SYNC-01..06, QA-01 | Not started |
| 5 | Full-pool frontend verification, docs, release | FE-01, FE-02, DOC-01, QA-02 | Not started |

## Accumulated Context

### Decisions
- Business Profile API is a second, selectable source — Places stays the no-OAuth default (`review_source = places`).
- `ReviewSourceInterface` seam; `ReviewSynchronizer` stays source-agnostic; reuse, don't fork.
- OAuth refresh token + client secret encrypted at rest (Encryptable, explicit decrypt like `getApiKey()`).
- Sync scheduled only; reads served from Toolbox cache; low Business Profile quotas.
- Plan lives in plugin-local `.planning/` on prod (gitignored/ephemeral); canonical dev in the plugin's own git repo locally.

### Blockers
- **External / non-code:** Business Profile API access is Google-approval-gated (create OAuth credentials → apply for access → verify ownership). Until approved the reviews endpoint returns 403. End-to-end acceptance (SYNC/FE with real data) cannot be verified until approval lands — plan builds against `Http::fake()` in the meantime and degrades gracefully on 403.

### Todos
- (none yet)

## Notes

- Prod runtime PHP 8.4, but code must stay PHP 8.2+ compatible.
- Gates: PHPStan max, PHPMD, php8.2 + php8.4 lint, PHPUnit + PluginTestCase with `Http::fake()`.
- Run gates by syncing the plugin repo into the live install: `php8.4 ../../../vendor/bin/{phpstan,phpmd,phpunit}`.
