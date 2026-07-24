# Yalıhan OS — Business Automation Index (BAI) Baseline
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. BAI Definitions and Formulas

The **Unweighted Step Automation Ratio (USAR)** measures the raw ratio of verified, production-automated steps to the total operational steps required to manage properties in Yalıhan OS.

$$\text{USAR} = \left( \frac{\text{Verified Production Automated Steps}}{\text{Total Operational Steps}} \right) \times 100$$

The **Weighted Business Automation Index (WBAI)** accounts for frequency, manual duration, error risk, and revenue weightings.

---

## 2. Step Classification Matrix

Across the 12 property lifecycle stages, 36 distinct operational steps have been mapped:

| Lifecycle Stage | Total Steps | Manual Steps | Stubbed Steps | Production Automated Steps |
|---|---|---|---|---|
| **1. Acquisition & Mandate** | 3 | 3 | 0 | 0 |
| **2. Property Onboarding** | 3 | 2 | 0 | 1 (Workspace verification) |
| **3. Drive Provisioning** | 3 | 0 | 0 | 3 (Folder scaffolding) |
| **4. Media Intelligence** | 3 | 3 | 0 | 0 |
| **5. Content Generation** | 3 | 2 | 0 | 1 (Description generator) |
| **6. Commercial Offering** | 3 | 3 | 0 | 0 |
| **7. Pricing Engine** | 3 | 2 | 1 (Cortex suggestions) | 0 |
| **8. Channel Publishing** | 3 | 2 | 1 (Calendar sync stub) | 0 |
| **9. Reservation Intake** | 3 | 2 | 1 (Reservation create price stub) | 0 |
| **10. Availability Locking** | 3 | 3 | 0 | 0 |
| **11. Turnover Operations** | 3 | 3 | 0 | 0 |
| **12. Finance & Payout** | 3 | 1 | 2 (Bank / invoice stubs) | 0 |
| **Total** | **36** | **26** | **5** | **5** |

---

## 3. Automation Metric Indicators

*   **Unweighted Step Automation Ratio:** **13.88%**
*   **Weighted Business Automation Index:** *Not yet measured* (Requires runtime telemetry log data to assign operational weights).
*   **Evidence Confidence Score:** **High (100% verified via static codebase analysis)**

### Ratio Details:
*   **Active Automation Steps:** 5 (13.88%)
*   **Stubbed/Partial Steps:** 5 (13.88%)
*   **Manual Steps:** 26 (72.24%)

---

## 4. Growth Targets

| Sprint Target | Target USAR | Required Production Automations |
|---|---|---|
| **Sprint 16 (Classification)** | **16.66%** | Automate YazlikDetail form mapping (+1 step). |
| **Sprint 17 (Property Core)** | **22.22%** | Implement canonical Property-to-Listing event bindings (+2 steps). |
| **Sprint 18 (Reservation Loop)** | **33.33%** | Automate Availability lock on manual reservations (+4 steps). |
