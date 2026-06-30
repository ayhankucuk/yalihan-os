# API CONTRACT — Uniform Response Standard

> **Version**: 1.0.0
> **Hash**: 8f1bc0a4
> **Generated**: 2026-04-25T08:21:50.073Z

## Required Keys (Root)
- `success`
- `data`
- `meta`
- `error`

## Meta Schema
Required fields in `meta` object:
- `timestamp`

Optional fields:
- `version`
- `warnings`
- `pagination`

## Error Shape
Structure of the `error` object when `success: false`:
- `code`
- `message`

## Deterministic Rules
- **success true implies error null**: `true`
- **success false implies data null**: `true`

---
<!-- AUTO-GENERATED FROM API_CONTRACT.json -->
<!-- DO NOT EDIT MANUALLY -->
