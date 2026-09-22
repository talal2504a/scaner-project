---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# STACK.md — Technology Stack

**Analysis Date:** 2026-09-22

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

**No front-end framework.** jQuery, React, Tailwind, Bootstrap are NOT used. All DOM work is via `document.getElementById` helper `$()` in `root/assets/js/app.js`.

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
