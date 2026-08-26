# Domain Glossary

| Term | Meaning | Boundary |
|---|---|---|
| Property | Managed real-estate asset | Domain entity, not merely an ilan row |
| İlan / Listing | Public or draft presentation of a property | Publication workflow |
| Kategori | Listing/property classification | Drives schema and filtering |
| Yayın tipi | Publication/rental period type | Category-dependent selection |
| Feature / Özellik | Structured property attribute | Tenant-aware assignment and template rules |
| Template / Şablon | Reusable feature/form structure | Property Hub configuration |
| Property Hub | Feature, template, pack, dependency and governance management | Admin configuration boundary |
| Wizard | Stepwise listing creation flow | Category → features → media → address → preview |
| Hermes | Domain-event orchestration and coordination layer | Not an AI model or UI |
| Cortex | Intelligence/analysis layer | AI reasoning and recommendations |
| OpenClaw | Agent runtime/integration concept | Agent execution boundary |
| n8n | External automation/workflow engine | Adapter/automation boundary |
| Knowledge Core | Source-backed project/operational knowledge layer | Explainability and provenance |
| Tenant | Isolated organization/business context | Must constrain data, queries, indexes and authorization |
