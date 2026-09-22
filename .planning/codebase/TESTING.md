---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# TESTING.md — Testing Structure & Practices

**Analysis Date:** 2026-09-22

## Testing Framework

- **No automated test framework.** No PHPUnit, no Jest, no Cypress, no test runner configured.
- No `tests/` directory in the project.

## Current Testing Approach

- **Manual testing** via XAMPP local server (`http://localhost/scaner-prodect/project/`).
- **Browser refresh rule:** `Ctrl+F5` (hard refresh) required to clear JS/CSS cache (documented in `STRUCTURE.md`).

## Verification Patterns Used During Development

- Smoke-test each Ajax endpoint in browser devtools / direct URL: verify JSON shape `{success, message, ...}`.
- Real barcode flows tested through the UI (manual scan, sheet upload with preview, USB scanner modal).
- Sheet upload verified by uploading sample Excel/PDF files and checking preview table grouping by level.

## What to Test When Changing Code (practical checklist)

- **DB-level changes** (`database/schema.sql`): reimport schema in fresh DB, confirm FKs/indexes.
- **Core helpers** (`config/db.php`): exercise `processStockIn`/`processStockOut` across all three levels (CARTON/BOX/PCS), consumed + insufficient-stock edge cases.
- **Ajax endpoints**: check success path and each failure return (`jout` shape).
- **Pages**: after changes press `Ctrl+F5`, verify messages, tabs, scanner lock toggle.

## Gaps / Technical Debt (Testing)

- No regression suite — a refactor of `config/db.php` would have no safety net.
- No CI. No test fixtures/seed data for the DB.
- Scanner (hardware) behavior cannot be automated; relies on keyboard capture simulation.

## Recommended (future) approach

- If adding automation later: PHPUnit for core helpers (`config/db.php` functions) with an in-memory or test-database fixture echoing `database/schema.sql` is the smallest high-value first step.
