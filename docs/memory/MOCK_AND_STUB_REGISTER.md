# Yalıhan OS — Mock & Stub Register
**Sponsor:** Strategic Architecture & Automation Board (SAAB)  
**Date:** 2026-07-24  
**Status:** PROPOSED (Awaiting SAAB Review)  

---

## 1. Catalog of Mocks & Stubs in Production Paths

This register catalogs the methods in active code paths that contain mock logic, stubs, or hardcoded success conditions instead of real external network integrations.

| Component Path | Method / Function | Stubbed Behavior | Risk / Impact | Code Evidence (FQCN) |
|---|---|---|---|---|
| **Calendar Sync** | `pushToAirbnb` | Returns `['success' => true]` directly without invoking the Airbnb API. | **High.** CLI sync commands report success false/true falsely. | [CalendarSyncService.php](../../app/Services/CalendarSyncService.php#L81-L90) |
| **Calendar Sync** | `pushToBookingCom` | Returns `['success' => true]` directly without invoking Booking.com API. | **High.** Mute failures; scheduled jobs report false success. | [CalendarSyncService.php](../../app/Services/CalendarSyncService.php#L92-L101) |
| **Calendar Sync** | `pushToGoogleCalendar` | Returns `['success' => true]` directly without Google Calendar API integration. | **Medium.** Google Calendar remains unsynced. | [CalendarSyncService.php](../../app/Services/CalendarSyncService.php#L103-L112) |
| **Pricing Engine** | `generatePriceSuggestion` | Returns a static mock price matrix instead of scanning regional competitor portals. | **Medium.** AI suggestions are hardcoded placeholders. | [YalihanCortex.php](../../app/Services/AI/YalihanCortex.php#L1005-L1097) |
| **Reservation Payout** | `createReservation` | Sets `toplam_tutar => 0` directly, bypassing actual nightly pricing and tax calculations. | **High.** Booking records lack accurate financial amounts. | [YazlikKiralamaService.php](../../app/Services/YazlikKiralamaService.php#L93-L114) |
| **Drive Integrator** | `WorkspaceSync` | Mocks folder synchronization for files other than directory generation. | **Low.** Directory skeleton is generated, but file sync is stubbed. | [DriveWorkspaceService.php](../../app/Services/Drive/DriveWorkspaceService.php) |
