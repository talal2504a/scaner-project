# Requirements: Stock System (Diwan International)

**Defined:** 2026-09-22
**Core Value:** Barcode hierarchy accuracy — every Stock In/Out updates the correct product's piece count; consumed barcodes cannot be sold again.

## v1 Requirements

Requirements for the current system. Existing capabilities are validated; improvement requirements are the active roadmap.

### Product Master & Registration

- [x] **PROD-01**: User can register a product by item code with item name and packaging ratios
- [x] **PROD-02**: User can register products in bulk from an uploaded sheet
- [x] **PROD-03**: User can view the product master list
- [x] **PROD-04**: Registration is duplicate-safe (same item_code returns existing product)
- [x] **PROD-05**: User can delete a product (and its dependent records)

### Barcode Hierarchy

- [x] **BCDE-01**: User can register barcodes at CARTON, BOX, and PCS levels
- [x] **BCDE-02**: Each barcode knows its level, parent barcode, item_code, and piece quantity
- [x] **BCDE-03**: Piece quantity falls back by level (CARTON=pcs_per_ctn, BOX=pcs_per_box, PCS=1) when not explicit
- [x] **BCDE-04**: Duplicate barcodes are skipped (not overwritten)
- [x] **BCDE-05**: Barcode level is inferred from prefix (L=B CARTON, B=BOX, P=PCS)

### Sheet Upload & Parsing

- [x] **SHEE-01**: User can upload sheets in .xlsx, .xls, .csv, .docx, .pdf, .txt
- [x] **SHEE-02**: Uploaded sheet shows a grouped preview before committing
- [x] **SHEE-03**: Header columns are auto-detected (barcode/item code/item name/level)
- [x] **SHEE-04**: Positional fallback works when headers are absent
- [x] **SHEE-05**: Empty cells (CARTON parent) are preserved during parsing

### Stock In

- [x] **SIN-01**: User can stock in manually by barcode with auto piece quantity by level
- [x] **SIN-02**: User can stock in multiple barcodes from a sheet
- [x] **SIN-03**: Stock In increases the product's piece count
- [x] **SIN-04**: Stock In records a transaction row (source: manual/sheet)
- [x] **SIN-05**: After Stock In, the barcode and its children become available (un-consumed)

### Stock Out

- [x] **SOUT-01**: User can stock out manually by barcode with auto piece quantity by level
- [x] **SOUT-02**: User can stock out multiple barcodes from a sheet (already-consumed skipped)
- [x] **SOUT-03**: User can stock out via USB scanner (auto-process on scan)
- [x] **SOUT-04**: Consumed barcodes cannot be stocked out again
- [x] **SOUT-05**: Insufficient stock prevents stock out
- [x] **SOUT-06**: Stock Out records a transaction row
- [x] **SOUT-07**: User can undo the last stock out

### Dashboard & History

- [x] **DASH-01**: Dashboard shows stock statistics
- [x] **DASH-02**: User can view stock history
- [x] **DASH-03**: Queries return full datasets (no LIMIT per directive)

### Active Improvements (from codebase concerns)

- [ ] **SECU-01**: User must authenticate to access any admin/API route (no auth today)
- [ ] **SECU-02**: All POST endpoints reject unauthenticated requests
- [ ] **SECU-03**: All POST endpoints are protected against CSRF
- [ ] **TEST-01**: Core flow helpers (processStockIn/processStockOut) covered by automated tests

## v2 Requirements

Deferred. Tracked but not in current roadmap.

### Enhancements

- **SOUT-08**: Batch-optimize recursive consume/un-consume queries for very large sheets
- **SECU-04**: Role-based access (operator vs admin)
- **DASH-04**: Pagination control opt-in for huge datasets (user currently forbids LIMIT)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Front-end framework / build tooling | Deliberate vanilla JS/no-deps design |
| Photo upload per product | Legacy v1 concept dropped in v2 |
| Multi-branch deployment pipeline | Local XAMPP tool, single environment |
| Public internet exposure | Local internal tool by design |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| SECU-01 | Phase 1 | Pending |
| SECU-02 | Phase 1 | Pending |
| SECU-03 | Phase 1 | Pending |
| TEST-01 | Phase 2 | Pending |

(Validated requirements PROD-*/BCDE-*/SHEE-*/SIN-*/SOUT-*/DASH-* are already shipped in the existing codebase.)

**Coverage:**
- Active requirements: 4 total
- Mapped to phases: 4
- Unmapped: 0 ✓

---
*Requirements defined: 2026-09-22*
*Last updated: 2026-09-22 after initial definition*