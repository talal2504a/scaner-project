---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# CONVENTIONS.md — Code Style & Conventions

**Analysis Date:** 2026-09-22

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

1. **`LIMIT` kabhi bhi queries mein use nahi hota** — dashboard/history return full data.
2. **Sidebar / CSS / logo bahar se change nahi karna** — only app-authorized edits allowed.
3. Stock hamesha **pieces** me track hota hai.

## HTML Conventions

- Pages are full standalone HTML documents that `include 'sidebar.php'` (and optionally `_layout.php`).
- Cache busting: `?v=<?php echo filemtime('assets/css/style.css'); ?>` on CSS/JS includes.
- Inline page-specific `<script>` blocks; shared helpers lifted into `app.js` when reused across 2+ pages.
