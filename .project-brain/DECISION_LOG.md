# Decision Log

## D001 — Evidence-first project brain

The brain separates repository facts, documented claims, live production observations, inferences, and unknowns. This prevents old chat output from becoming false current state.

## D002 — Current roadmap authority

For active planning, use `docs/ERA_V/PHASE2-ROADMAP.md`. Treat older sprint backlogs as historical/supporting material unless explicitly reconciled.

## D003 — Production safety

Diagnosis is read-only by default. Database drops, migrations, deploys, seeders, and draft creation require an explicit operational request and a before/after record.

## D004 — User command format

Give VPS commands as plain text without shell prompts, Markdown fences, or copied output mixed into the command.

## D005 — Durable architecture decisions

Material architecture choices are recorded as individual ADRs under `docs/adrs/` and indexed in `.project-brain/ADR_INDEX.md`. A proposed ADR does not authorize implementation or production deployment.

## D006 — Golden Thread certification and scope freeze

Date: 2026-08-26

Prioritize certification of the eight-step Golden Thread before speculative feature expansion: listing creation, Cortex enrichment, photo/location capture, draft save, management approval, publication, CRM matching, and advisor task generation. Freeze new feature scope during certification; prune duplicate paths only after impact analysis. Production deployment still requires explicit user authorization and evidence gates.
