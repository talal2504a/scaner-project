---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# CONCERNS.md — Technical Debt, Risks & Known Issues

**Analysis Date:** 2026-09-22

## Technical Debt

1. **`config/db.php` is a monolith (819 lines)** — single shared file holds DB connection, business logic, AND sheet parsing. Large but deliberate (fast iteration). Risk: touching one function risks others; no unit tests.
2. **No test suite / no CI** — all verification manual. Highest-impact improvement would be PHPUnit for core helpers.
3. **HTML pages duplicate layout** — each page includes `sidebar.php` but largely re-implements `<head>`, wiring inline. `_layout.php` exists but isn't consistently used across all pages.
4. **Front-end mix of ES5/ES6** — `var` + `let`, no modules; global namespace pollution risk grows with pages.
5. **Structure docs duplicated** — `STRUCTURE.md` at repo root is maintained by hand; slight drift risk from actual code (e.g. `ajax/dashboard_items.php`, `root/scan.php`, `delete_barcode.php` are present but not in the root doc's tree).
6. **`levelFromBarcode` default is PCS** — unknown-prefix barcodes silently treated as PCS (`config/db.php:129`); could mask bad data.

## Security

1. **No authentication/authorization** — the entire app (admin paths + ajax endpoints) has NO login. Anyone with URL access can register/manipulate stock/delete products. **If exposed beyond LAN/localhost this is a serious risk.**
2. **CSRF protection absent** — no tokens on POST endpoints; a malicious site could trigger `stock_out_scanner.php` etc. if user is on same host. Mitigated for local-only use, but should be addressed before public deployment.
3. **SQL injection largely mitigated** — prepared statements + `real_escape_string` for `IN()` lists. Good.
4. **XSS posture mixed** — `esc()` used for dynamic rendering in most pages (good); raw `innerHTML` interpolation must always go through `esc()` (audit remaining interpolations).
5. **File upload** — uploads parsed server-side; temp files in `temp/`; no direct execution risk seen, but upload validation (size/type whitelist) should be confirmed before exposure.
6. **DB credentials committed** — `config/db.php` holds `root`/empty password; only acceptable for local XAMPP; must not ship to production unchanged.

## Performance

1. **No `LIMIT` anywhere (user directive)** — dashboard/history queries return FULL datasets. With large data volumes this will degrade. Deliberate product decision, but worth monitoring.
2. **`markRegisteredItems()` builds escaped SQL dynamically** — for very large sheets, the `IN (...)` list grows large (still fine for realistic sheet sizes).
3. **Recursive `consumeChildren` / `consumeChildrenUnset`** — recursion depth is bounded by hierarchy depth (3 levels), so safe. Each level = separate SELECT per parent; for huge sheets there are many queries — could batch for speed.

## Maintainability / Fragile Areas

1. **Sheet column assumptions** — hierarchy parsing depends on specific column order/count as fallback; auto-detection (`detectSheetColumns`) exists but positional fallback remains fragile to column shifts.
2. **Legacy folders** (`uploads/photos/`) kept but unused — confusion risk.
3. **`vendor/` requires `composer install`** — on fresh checkout, `.xls` and `.pdf` uploads silently degrade to `[]` if deps missing (guarded by `class_exists`, but silently empty).
4. **Scanner relies on keyboard capture heuristics** — pause timer 180ms; fast/slow scanners or IME could drop characters. Locked toggle mitigates accidents but not missed scans.

## Known Bugs (reported/observed)

- **None formally tracked.** `QUICK-WINS-CONFIRMED-BUGS.md` class of issues does not exist here; codebase carries no issue tracker. Bugs surface ad hoc during manual testing.
- Candidate to watch: silent `ensureProduct`/`ensureBarcode` null returns when DB insert fails — no error surfaced up to endpoint (caller must check).

## Recommendations (priority order)

1. Add authentication if the system will ever be reachable off-localhost.
2. Add a minimal automated test suite for `config/db.php` core flows.
3. Add CSRF protection on POST endpoints.
4. Centralize page layout into `_layout.php` consistently.
5. Audit all `innerHTML` facilities to route through `esc()`.
6. Monitor DB query growth given no-LIMIT directive.
