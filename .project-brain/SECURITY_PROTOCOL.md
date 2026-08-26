# Security and Secret Hygiene

## Never persist

- Passwords, SSH private keys, API keys, access tokens, cookies, session values
- iCal URLs containing credentials
- Full private logs containing personal or financial data
- Unredacted `.env` contents

## Inspect safely

- Use variable names, presence/absence, hostnames, and redacted status.
- Show only the minimum log lines needed for diagnosis.
- Redact tokens before saving evidence.
- Do not copy browser storage, cookies, or authentication headers.

## Security gates

- Validate tenant scoping in reads and writes.
- Check authorization middleware before admin actions.
- Treat external integrations as untrusted boundaries.
- Require explicit confirmation before transmitting sensitive data or changing production state.
- Review new dependencies and scripts before execution.

## Incident response

Contain → preserve minimal evidence → identify scope → reproduce safely → patch → test → deploy with authorization → verify → record lesson.
