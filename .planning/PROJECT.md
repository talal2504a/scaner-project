# Stock System — Diwan International Pvt Ltd

## What This Is

A stock management web-app (PHP 8 + MySQL, no framework) that tracks products, barcodes and movements in a 3-level hierarchy (CARTON → BOX → PCS). Users register products/barcodes by uploading sheets (Excel/PDF/docx/CSV) or manually, then perform Stock In / Stock Out manually, via sheet, or with a USB barcode scanner. Inventory is always tracked in pieces.

## Core Value

**Barcode hierarchy accuracy** — every Stock In/Out updates the correct product's piece count, and a barcode that is consumed cannot be sold again. Everything else can fail; this invariant must hold.

## Business Context

- **Customer**: Diwan International Pvt Ltd — internal warehouse/stock team.
- **Revenue model**: Internal tool (no direct revenue).
- **Success metric**: Zero accidental double-sales; accurate live stock counts (`products.current_stock_pcs`).

## Requirements

### Validated

- ✓ Product master register/list (manual + sheet bulk) — existing
- ✓ 3-level barcode hierarchy register (CARTON → BOX → PCS with parents) — existing
- ✓ Sheet upload & preview across formats (.xlsx/.xls/.csv/.docx/.pdf/.txt) — existing
- ✓ Stock In via manual form + sheet with auto piece qty by level — existing
- ✓ Stock Out via manual form + sheet + USB scanner with auto qty — existing
- ✓ Consumed tracking (`is_consumed`) + recursive consume/un-consume of hierarchy — existing
- ✓ Undo last stock out — existing
- ✓ Dashboard stats + full history (no LIMIT by directive) — existing
- ✓ Duplicate-safe registration (`ensureProduct`/`ensureBarcode`) — existing

### Active

- [ ] Authentication + authorization for all admin routes (no login today) — from CONCERNS.md
- [ ] CSRF protection on all POST endpoints — from CONCERNS.md
- [ ] Minimal automated test suite for `config/db.php` core flows — from CONCERNS.md

### Out of Scope

- Front-end framework / build tooling — app is deliberately vanilla (fast, zero deps)
- Multi-user roles/permisssions matrix — single internal team
- LIMIT-based pagination — user explicitly forbade LIMIT in queries
- Photos per product — legacy v1 concept, dropped in v2

## Context

- Local stack: XAMPP (Apache + PHP 8.0.30 + MySQL), DB `stock_system` on localhost/root.
- No framework; shared core in `config/db.php` (prepared statements, Hinglish comments).
- `.xlsx` parsed by a custom fast `XMLReader` streaming parser; PhpSpreadsheet only for legacy `.xls`; PdfParser for `.pdf`.
- Business rules to respect: stock always in pieces; `barcodes.pcs_qty` is source of truth; `levelFromBarcode` prefix convention (L/B/P); no CSS/sidebar/logo edits outside app flow.
- Hard refresh `Ctrl+F5` required after JS/CSS cache-busting changes.

## Constraints

- **Tech stack**: PHP 8.0, MySQL InnoDB, vanilla JS, CSS variables — no new framework/library unless required
- **Data integrity**: hierarchy consumption must stay consistent (recursive consume/un-consume)
- **Performance**: no `LIMIT` in queries (user directive) — full datasets returned
- **Local-only**: DB creds are localhost/root; must not ship publicly without auth work

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Pieces-first tracking | Avoids unit conversion errors; single number per product | ✓ Good |
| `barcodes.pcs_qty` = source of truth | Barcode can override inherited qty | ✓ Good |
| Custom XMLReader xlsx parser | Faster/lower-memory than PhpSpreadsheet common path | ✓ Good |
| Optional composer deps guarded by `class_exists` | App works even without vendor/ | ✓ Good |

---

*Last updated: 2026-09-22 after initialization*

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