# 🛡️ SAB Certification Bundle: TS-01-F2

**Bundle Format Version:** 1.0  
**Created:** 2026-08-03T21:33:56+00:00  

This directory is an immutable SAB certification bundle packaging the full evidence lifecycle for **TS-01-F2**.

## 📄 Bundle Files
- `manifest.json`: Machine-readable empirical runtime evidence and cryptographic signature.
- `policy-result.json`: Quality gate policy engine rule evaluation results.
- `report.md`: Canonical 10-section human-readable engineering evidence document.
- `verification.json`: Archive-time verification proof snapshot.
- `bundle-metadata.json`: Archive bundle metadata, format version (1.0) and timestamp.

## 🔍 Integrity Verification
To dynamically re-compute and verify the cryptographic payload integrity:
```bash
./scripts/tools/sab-cert.sh verify TS-01-F2
```
