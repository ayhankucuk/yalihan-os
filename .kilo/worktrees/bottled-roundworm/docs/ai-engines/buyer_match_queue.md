# Buyer Match Queue

- **Purpose**: Prioritizes and ranks potential buyers for a specific listing based on past behavioral data and matching criteria.
- **Data Source**: Search history, listing views, and saved preferences.
- **Algorithm / Logic**: Multi-factor scoring (activity frequency, budget match, intent signaling).
- **Service**: `BuyerMatchQueueService`
- **Controller**: `BuyerMatchQueueController`
- **Routes**: `GET /advisor/buyer-match`
- **UI Surface**: `resources/views/advisor/buyer-match-queue.blade.php`
- **Tests**: `BuyerMatchQueueTest.php`
- **Guard**: Standard SAB Guard.
- **SSOT Notes**: Documented in Core README.
- **Integration Points**: Central router for demand-side matches.
