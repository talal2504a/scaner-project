# Roadmap: Stock System (Diwan International)

**Defined:** 2026-09-22
**Granularity:** Standard

## Phase 1: Security Hardening

**Goal:** Add authentication and CSRF protection so the stock system is safe to operate beyond localhost trust.
**Mode:** standard

**Success Criteria:**
1. User can log in with credentials; all pages and endpoints redirect/require valid session
2. Unauthenticated API requests receive a JSON `success:false` response
3. Every POST endpoint verifies a CSRF token; forged requests are rejected
4. `CONCERNS.md` security items 1-3 resolved without breaking existing flows

**Requirements:** SECU-01, SECU-02, SECU-03

## Phase 2: Test Coverage Foundation

**Goal:** Add an automated test suite for core business helpers so future changes are safe.
**Mode:** standard

**Success Criteria:**
1. `processStockIn` and `processStockOut` covered for CARTON/BOX/PCS, consumed, and insufficient-stock cases
2. Test database fixture loads from `database/schema.sql`
3. Tests runnable via a single command; core helper regressions are caught

**Requirements:** TEST-01

---
*Last updated: 2026-09-22 after creation*