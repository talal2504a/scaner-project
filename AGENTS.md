<!-- GSD:project-start source:PROJECT.md -->

## Project

**Stock System — Diwan International Pvt Ltd**

A stock management web-app (PHP 8 + MySQL, no framework) that tracks products, barcodes and movements in a 3-level hierarchy (CARTON → BOX → PCS). Users register products/barcodes by uploading sheets (Excel/PDF/docx/CSV) or manually, then perform Stock In / Stock Out manually, via sheet, or with a USB barcode scanner. Inventory is always tracked in pieces.

**Core Value:** **Barcode hierarchy accuracy** — every Stock In/Out updates the correct product's piece count, and a barcode that is consumed cannot be sold again. Everything else can fail; this invariant must hold.

### Constraints

- **Tech stack**: PHP 8.0, MySQL InnoDB, vanilla JS, CSS variables — no new framework/library unless required
- **Data integrity**: hierarchy consumption must stay consistent (recursive consume/un-consume)
- **Performance**: no `LIMIT` in queries (user directive) — full datasets returned
- **Local-only**: DB creds are localhost/root; must not ship publicly without auth work

<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->

## Technology Stack

## Languages & Runtimes

- **PHP 8.0.30** (XAMPP / Apache) — back-end for all application logic
- **JavaScript** (vanilla, ES5/ES6 mixed, no framework) — front-end behavior
- **HTML5 + CSS3** (custom design, CSS variables) — UI layout and styling
- **SQL** (MySQL, InnoDB) — persistence via `mysqli` prepared statements

## Runtime Environment

- Local stack: XAMPP (Apache + PHP + MySQL) on Windows
- Entry: `C:\xampp\htdocs\scaner-prodect\project\index.php` redirects to `root/`
- Base URL served from XAMPP htdocs; all Ajax endpoints under `ajax/`

## Frameworks & Libraries

| Library | Version | Purpose | Loading |
|---|---|---|---|
| `phpoffice/phpspreadsheet` | ~3.6 | Legacy `.xls` binary sheet parsing | Composer autoload, on-demand |
| `smalot/pdfparser` | ~2.3 | `.pdf` sheet upload parsing | Composer autoload, on-demand |
| Composer (`vendor/autoload.php`) | — | Optional 3rd-party autoload | Included in `config/db.php` only if present |

## Configuration

- `composer.json` — dependency manifest (single line JSON)
- `config/db.php` — DB host/user/pass/name constants + shared helper functions. Config values: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=''`, `DB_NAME=stock_system`
- `database/schema.sql` — full database schema (products, barcodes, stock_in, stock_out)

## Key Files

- `config/db.php` — DB connection + all shared helper functions (jout, findProduct, ensureBarcode, processStockIn/Out, sheet parsing)
- `database/schema.sql` — table definitions and indexes
- `root/index.php` — Dashboard
- `root/assets/css/style.css` — all styling
- `root/assets/js/app.js` — shared front-end helpers

## Notes

- Dependencies are optional at runtime: code checks `file_exists('vendor/autoload.php')` and `class_exists()` before using them. Core sheet parsing for `.xlsx` uses a custom fast `XMLReader` streaming parser (NOT PhpSpreadsheet).
- `display_errors=0` in production; errors returned as JSON via `jout()`.

<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->

## Conventions

## Language & Comment Style

- **Comments are in Hinglish (Hindi + English mix)** throughout the codebase — e.g. `// Stock in ke baad sab available`, `Yeh file har backend (ajax/*.php) mein require hoti hai`. Keep this style when editing (do not rewrite to pure English).
- PHP docblocks use `/** ... */` with descriptive Hinglish summaries.
- Error messages shown in UI are English (production) but internal comments help future maintenance.

## PHP Conventions

- PHP 8.0 compatible syntax (no PHP 8.1+ only features used).
- `error_reporting(E_ALL)` + `ini_set('display_errors', '0')` in `config/db.php` — errors not shown to users.
- **All DB access uses prepared statements** (`$conn->prepare()` + `bind_param('ssssi', ...)` + `execute()`). Inline SQL only for dynamic `IN (...)` lists, always escaped via `real_escape_string`.
- `global $conn;` used inside functions to access the shared mysqli connection.
- Associative array returns with `['success' => bool, 'message' => string, ...]` pattern for helpers.
- `jout($arr)` for JSON responses — call `exit` via function.
- No strict typing declarations (`declare(strict_types=1)` not used); int-ve casts applied where needed (`(int)$value`).

## JavaScript Conventions

- Vanilla JS, `var`/`let`/`function` used (ES5-style mostly, some `let`/arrow-free).
- Helper `$('id')` = `document.getElementById`; `esc()` for HTML escaping BEFORE injecting user data into innerHTML.
- All server calls via `fetch()` + `.then(r => r.json())`; shared wrappers `postForm(url, formData, done)` and `ajax(url, done)` in `app.js`.
- Messages rendered through `showMsg(msg, type)` (auto-hide 4s) or scan-local `scanMsg`.
- Scanner input handled via global `keydown` listener buffering chars (see `root/stock-out.php`).

## CSS Conventions

- Custom design, CSS variables in `:root` (`--blue`, `--red`, `--panel`, `--muted`, etc.).
- Reusable utility classes: `.btn` (+ `.red/.blue/.green/.amber/.ghost`), `.tag` (+ `.in/.out/.low/.ok`), `.panel`, `.field`, `.msg-box`, `.nav-tabs`, `.modal-*`, `.scan-box`.
- Color tag system: `.tag.in`=blue (PCS/positive), `.tag.out`=red (BOX/outgoing), `.tag.low`=red (CARTON/low/warning), `.tag.ok`=green (success/new).

## Error Handling

- Backend: return `['success' => false, 'message' => '...']` JSON; UI shows red message. No exceptions — return values only.
- Barcode validation happens in core helpers (`processStockOut` checks registered/consumed/insufficient).
- Belt-and-suspenders guards in sheet parsing (skip header rows, skip column-shift garbage, guard regexes like `preg_match('/\s/', $item_code)`).

## Naming

- Functions: camelCase (`findProduct`, `processStockIn`, `sheetRowsFromItems`).
- Files: snake_case for ajax/php, kebab-case for root pages.
- DB columns: snake_case lowercase (`current_stock_pcs`, `parent_barcode`, `is_consumed`).
- DB tables: plural lowercase (`products`, `barcodes`, `stock_in`, `stock_out`).

## User Directives (hard rules — do not break)

## HTML Conventions

- Pages are full standalone HTML documents that `include 'sidebar.php'` (and optionally `_layout.php`).
- Cache busting: `?v=<?php echo filemtime('assets/css/style.css'); ?>` on CSS/JS includes.
- Inline page-specific `<script>` blocks; shared helpers lifted into `app.js` when reused across 2+ pages.

<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->

## Architecture

## High-Level Architecture

```

```

## Business Domains

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

## Patterns

- **Thin endpoints, fat shared helpers:** most business logic lives in `config/db.php`; `ajax/*.php` files are thin adapters.
- **JSON responses via `jout()`** (`config/db.php:40`): always `Content-Type: application/json`.
- **Idempotent registration:** `ensureProduct` / `ensureBarcode` return existing rows instead of failing on duplicates.
- **Undo support:** `ajax/undo_stock_out.php` reverses the last stock-out (restores stock, deletes record).
- **No LIMIT in queries** (explicit user directive) — dashboard/history return full datasets.

## Abstractions & Modules

- No OOP design patterns (no repositories/services/DTOs). Plain procedural functions grouped by concern in `config/db.php` (product / barcode / consumed / sheet-parsing / column-detection helpers).
- Front-end has no module system; global helpers in `app.js`, per-page inline `<script>` blocks.

<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->

## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, `.github/skills/`, or `.codex/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->

## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:

- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->

## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
