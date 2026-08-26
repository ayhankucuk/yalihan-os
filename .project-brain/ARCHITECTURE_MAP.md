# YALIHAN OS — Architecture Map

## System layers

Product modules sit above shared domain and intelligence layers:

- Property/listing domain: properties, listings, categories, publication types, feature assignments, reservations.
- Operations: channel synchronization, property command center, action/task center.
- Intelligence: AI orchestrator, Knowledge Core, explainable recommendations.
- Integration/automation: Hermes event broker/orchestration, OpenClaw agent runtime, n8n automation, external channels.
- Delivery: Laravel application, PHP-FPM, nginx, queue worker, MySQL/Redis-backed production stack.

## Important runtime chain

User/browser → route → controller → service → model/query → database → view/API response.

For the listing wizard:

Category + publication type → EffectiveWizardSchemaResolver / FeatureTemplateResolver → tenant-aware feature assignments → wizard fields → listing draft.

For public summer listings:

`/yazliklar` → VillaController → VillaService → Yazlık category and published listing/reservation data → `villas/index` view.

## Hermes

Hermes is the event/orchestration layer, not an AI model. It coordinates domain events, routes work to services/agents, preserves operational flow, and allows one result to become the next event/task. It should remain separate from the model provider and from n8n's automation concerns.

## Boundaries to protect

- Tenant isolation must exist in schema, queries, uniqueness, seeders, and authorization.
- Authentication/session behavior must be validated across the production subdomains.
- External integrations must be treated as adapters, not as domain truth.
- AI outputs require provenance and a human-verifiable source path.
