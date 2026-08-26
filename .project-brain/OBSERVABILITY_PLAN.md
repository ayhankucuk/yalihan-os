# Observability Agent Plan

## Health dimensions

- Availability: container health, health endpoint, HTTP status.
- Correctness: route response, API contract, database errors.
- Performance: response latency, slow queries, queue delay.
- Frontend: CSS/JS 404s, blocking console errors, asset version mismatch.
- Operations: failed jobs, retry counts, cache failures, external service errors.
- Security: 401/403 spikes, suspicious access patterns, secret exposure indicators.

## Minimum evidence record

- Timestamp and timezone
- Environment and deployed commit
- Route/service checked
- Status or metric
- Error class/message, redacted
- Impact scope
- Action and owner

## Rule

Healthy containers are only one signal. A feature is operationally healthy only when its relevant route, dependencies, logs, and user flow are also verified.
