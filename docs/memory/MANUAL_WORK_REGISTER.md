# Yalıhan OS — Manual Work Register
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. Catalog of Manual Human Steps

This register catalogs the operational steps that currently rely on manual human intervention in Yalıhan OS.

| Step ID | Operational Task | Manual Steps Required | Duration (est.) | Frequency | Error Risk | Automation Target |
|---|---|---|---|---|---|---|
| **M-01** | Legal Mandates | Draft kiralama/satış contracts, send to owner, print/scan signed copies, upload to Drive manually. | 60 mins | Low | Low | Document signature automation (DocuSign/E-imza) |
| **M-02** | Spec Registration | Search municipality/TKGM maps, type ADA/PARSEL coordinates, and fill specs manually. | 15 mins | Low | Medium | TKGM API Integrator / Geocoding automation |
| **M-03** | Media Sorting | Inspect photos, sort into rooms (bathroom, pool), filter low-res images, and rename manually. | 45 mins | High | High | AI Media Organizer (Room recognition) |
| **M-04** | Pricing Schedules | Look up competitor prices on portals, decide nightly rates, and fill pricing forms manually. | 20 mins | High | High | Valuation Engine & Dynamic Pricing Agent |
| **M-05** | Listing Creation | Create listing on Airbnb/Booking/Sahibinden manually by copy-pasting specs. | 90 mins | Medium | High | Channel Manager Publishing Adapter |
| **M-06** | Booking Intake | Answer client voice calls/emails, check calendar sheets, and type reservation info manually. | 10 mins | High | High | Omnichannel CRM inbox & AI lead matching |
| **M-07** | Calendar Locking | Open Airbnb, Booking, and VRBO portals and lock dates manually for every booking. | 15 mins | High | Critical | Availability Lock Sync Engine |
| **M-08** | Turnover Dispatch | Review check-out list, call cleaning staff, and create tasks manually in command center. | 15 mins | High | High | Event-driven Turnover Auto-Scheduler |
| **M-09** | Bank Mutabakat | Download bank/stripe statements, match against booking ledger, and transfer payouts manually. | 30 mins | High | Medium | Bank Statement Reconciler |
