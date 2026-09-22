---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# ARCHITECTURE.md — System Architecture

**Analysis Date:** 2026-09-22

## High-Level Architecture

Server-side rendered PHP application with a REST-style JSON Ajax layer. Simple layered monolith — **no framework, no MVC library, no composer framework**. Three physical tiers:

```
Browser (vanilla JS)
      │  fetch() JSON
      ▼
ajax/*.php  (HTTP endpoints: validate + orchestrate + respond JSON)
      │
      ▼
config/db.php  (shared core: DB connection + business helpers)
      │
      ▼
MySQL (stock_system: products, barcodes, stock_in, stock_out)
```

## Business Domains

1. **Product Master** — `products` table; item codes with packaging ratios (pcs/box, boxes/ctn).
2. **Barcode Hierarchy** — `barcodes` table; 3-level hierarchy **CARTON → BOX → PCS** with parent links and per-barcode piece quantity (`pcs_qty`).
3. **Stock In / Stock Out** — `stock_in` / `stock_out` transactional tables; movement tracked **always in pieces**.
4. **Sheet Ingestion** — file uploads (Excel/PDF/docx/CSV/txt) parsed into hierarchy items; bulk registration or stock movement.

## Core Invariant: CARTON → BOX → PCS

- Every barcode belongs to exactly one level. Prefix convention: `L...`→CARTON, `B...`→BOX, `P...`→PCS (`levelFromBarcode()` in `config/db.php:124`).
- `barcodes.pcs_qty` is the **source of truth** for piece quantity.
- Parent linkage: CARTON has empty parent; BOX→its CARTON; PCS→its BOX.
- `consumeChildren()` / `consumeChildrenUnset()` recurse down the hierarchy to mark/unmark self + all children (`is_consumed`).
- Stock In: product stock `+=` pcs, children un-consumed. Stock Out: product stock `-=` pcs, self + children consumed.

## Entry Points

| Entry | File |
|---|---|
| App redirect | `index.php → root/index.php` |
| Dashboard | `root/index.php` |
| Products (list/register) | `root/products.php` |
| Stock In | `root/stock-in.php` |
| Stock Out (manual/sheet/scanner) | `root/stock-out.php` |
| Register page | `root/register.php` |
| History | `root/history.php` |
| Shared layout/sidebar | `root/_layout.php`, `root/sidebar.php` |
| API endpoints | `ajax/*.php` (~17 files) |

## Data Flow Example (Stock Out)

1. `root/stock-out.php` captures barcode (manual form / sheet selection / scanner keydown buffer)
2. `fetch('ajax/stock_out_form.php' | 'stock_out_sheet.php' | 'stock_out_scanner.php')`
3. Endpoint validates → calls `processStockOut($barcode, $source, $remark)` (`config/db.php:272`)
4. `processStockOut`: lookups barcode, checks consumed flag + sufficient stock, decrements `products.current_stock_pcs`, INSERTs `stock_out` record, calls `consumeChildren()` → recursion marks hierarchy consumed
5. Returns `success/message/data` JSON → JS renders message + UI

## Patterns

- **Thin endpoints, fat shared helpers:** most business logic lives in `config/db.php`; `ajax/*.php` files are thin adapters.
- **JSON responses via `jout()`** (`config/db.php:40`): always `Content-Type: application/json`.
- **Idempotent registration:** `ensureProduct` / `ensureBarcode` return existing rows instead of failing on duplicates.
- **Undo support:** `ajax/undo_stock_out.php` reverses the last stock-out (restores stock, deletes record).
- **No LIMIT in queries** (explicit user directive) — dashboard/history return full datasets.

## Abstractions & Modules

- No OOP design patterns (no repositories/services/DTOs). Plain procedural functions grouped by concern in `config/db.php` (product / barcode / consumed / sheet-parsing / column-detection helpers).
- Front-end has no module system; global helpers in `app.js`, per-page inline `<script>` blocks.
