# ADR-010: Production Frontend Asset Ownership

**Status:** PROPOSED
**Date:** 2026-08-26
**Baseline:** ea0549c
**Deciders:** Pending explicit approval

## Context

The production Dockerfile builds frontend assets inside the image. Nginx then mounts the host `public` directory over `/app/public`. Because `public/build` is Git-ignored, the host mount can hide the generated CSS and JavaScript files. Live browser evidence showed the main admin stylesheet was referenced but not loaded.

## Decision

Pending. The deployment design must choose one owner for generated frontend assets: the immutable image, or an explicitly synchronized host asset directory. Both must not silently compete.

## Alternatives considered

- Image-owned `/app/public/build`: immutable and tied to the deployed commit.
- Explicit host synchronization: operationally visible but requires reliable deploy and rollback steps.
- Full host `/public` mount: rejected as unsafe until asset synchronization is guaranteed.

## Consequences

- The current mount strategy remains a release blocker for frontend certification.
- Any fix must be followed by CSS HTTP 200 and browser visual verification.

## Verification

- Repository evidence: `docker/Dockerfile.production`, `docker-compose.production.yml`, `.gitignore`.
- Automated tests: pending.
- Production/browser evidence: missing stylesheet reproduced on 2026-08-26.

## Revisit trigger

Revisit when the deployment strategy is changed or the build artifact ownership changes.
