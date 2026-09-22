---
last_mapped_commit: 462e2c2e7da8c1212db9fc73004860e973624786
last_mapped_at: 2026-09-22
---
# INTEGRATIONS.md — External Services & Data Connections

**Analysis Date:** 2026-09-22

## Database

- **Engine:** MySQL (via XAMPP), database name `stock_system`, charset `utf8mb4`
- **Connection:** single shared `mysqli` connection in `config/db.php` using constants `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` (`root`, empty password on local XAMPP)
- **Access pattern:** prepared statements (`$conn->prepare` + `bind_param`) for all parameterized queries; inline string `IN (...)` queries built with `real_escape_string` in `markRegisteredItems()` (`config/db.php:800`)
- **Foreign keys:** `barcodes.item_code → products.item_code` (`database/schema.sql:44`)

## Sheet/File Uploads (Primary External Input)

| Format | Parser | Location |
|---|---|---|
| `.xlsx` | Custom `XMLReader` streaming parser (fast, low memory) | `config/db.php` — `xlsxToRows()` |
| `.xls` (legacy binary) | PhpSpreadsheet `IOFactory` (only for this format) | `config/db.php` — `sheetFileToRows()` |
| `.csv` | `fgetcsv` | `config/db.php` — `sheetFileToRows()` |
| `.docx` | Custom `ZipArchive` + XML text extraction | `config/db.php` — `docxToText()` |
| `.pdf` | Smalot PdfParser (if installed) | `config/db.php` — `sheetFileToRows()` |
| `.txt` | `sheetRowsFromText()` custom delimiter splitter | `config/db.php` |

Files are uploaded via the `ajax/upload_sheet.php` endpoint; preview before commit via `ajax/sheet_preview.php`.

## USB Barcode Scanner

- **Integration:** keyboard-emulation (HID) input. Scanner characters are interpreted as keyboard keypresses; global `keydown` listener in `root/stock-out.php` buffers characters and fires 180ms after last keystroke or on Enter.
- **Safety:** lock/unlock toggle prevents accidental scans; only active while the scanner modal is open.

## Third-Party Packages (composer)

- `phpoffice/phpspreadsheet` ~3.6 — `.xls` legacy parsing only
- `smalot/pdfparser` ~2.3 — `.pdf` sheet parsing only

Both loaded on-demand via Composer autoload guarded by `class_exists()`.

## External APIs / Webhooks

- **None.** No REST APIs called, no auth providers, no webhooks, no email/SMS. Everything is local to the stock system.

## File Storage

- `uploads/photos/` — legacy v1 photos folder (unused in v2, kept for compatibility)
- `temp/` — temporary file storage (contains `.gitkeep` only)
