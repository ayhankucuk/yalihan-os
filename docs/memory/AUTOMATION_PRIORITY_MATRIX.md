# Yalıhan OS — Automation Priority Matrix
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. Prioritization Methodology

We rank manual tasks using the SAAB Prioritization Formula:

$$\text{Priority Score} = \frac{\text{Manual Time} \times \text{Frequency} \times \text{Error Risk} \times \text{Revenue Impact}}{\text{Implementation Risk}}$$

---

## 2. Priority Ranking Matrix

Here, Capability A (Reservation Availability Lock) and Capability B (Turnover Dispatch) are analyzed independently.

| Task Code | Operational Step / Capability | Time | Freq | Risk | Rev | Imp. Risk | Priority Score | Ranking |
|---|---|---|---|---|---|---|---|---|
| **Cap-B** | **Turnover Dispatch** (Operations Scheduling) | 2 | 5 | 4 | 4 | 1 (Low) | **160.0** | **#1** |
| **Cap-A** | **Reservation Availability Lock** (Date Conflict Check) | 2 | 5 | 5 | 4 | 2 (Medium) | **100.0** | **#2** |
| **M-09** | Bank Mutabakat (Payout Matching) | 4 | 4 | 3 | 4 | 3 (Medium) | **64.0** | **#3** |
| **M-04** | Pricing Schedules (Rates updates) | 3 | 4 | 4 | 4 | 4 (High) | **48.0** | **#4** |
| **M-05** | Channel Publishing (OTA Listings) | 5 | 2 | 4 | 5 | 5 (Critical) | **40.0** | **#5** |
| **M-06** | Booking Intake (Lead Entry) | 1 | 5 | 3 | 5 | 2 (Medium) | **37.5** | **#6** |
| **M-03** | Media Sorting (Photo categorization) | 4 | 2 | 3 | 3 | 4 (High) | **18.0** | **#7** |
| **M-01** | Legal Mandates (Contracts upload) | 5 | 1 | 2 | 3 | 3 (Medium) | **10.0** | **#8** |
| **M-02** | Spec Registration (Manual specs entry) | 2 | 1 | 3 | 2 | 2 (Medium) | **6.0** | **#9** |

---

## 3. Architectural Precedence & Final Recommendation

Even though **Turnover Dispatch (Cap-B)** has a higher raw priority score (160.0) due to lower implementation risk, the SAAB rules that it is architecturally dependent on the existence of reliable booking and calendar blocking data. 

Therefore, the board selects **Reservation-to-Availability Core (Capability A)** as the next vertical slice for implementation.

### Vertical Slice Scope:
*   **Target Bounded Contexts:** Canonical Reservation, Availability, Conflict Detection, Domain Event, Execution Record, and Timeline.
*   **Acceptance Criteria:** Excludes external Airbnb/Booking.com API integrations; works via direct/manual booking inputs to validate the internal availability lock transaction first.
