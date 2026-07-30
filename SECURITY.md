# Security Policy

## Ownership boundary

File 24 remains the canonical owner of platform security, privacy, compliance, incident management, cache governance, and resilience. File 25 consumes those policies and implements secure public rendering. Its local Safe Mode is a bounded non-destructive recovery mechanism, not a second incident center.

## Reviewed File 25 controls

- Early PHP compatibility guard before PHP 8-only classes load.
- Duplicate-plugin-copy collision protection.
- File 00-authoritative Founder, approval, age, role, visibility, and professional-eligibility checks.
- Monotonic filters: extensions may narrow public access but may not elevate identity, widen visibility, restore denied contact data, or fabricate native dependencies.
- Server-side public-field allowlists and post-filter revalidation.
- Forced privacy for minors, unknown-age accounts, private/member-only profiles, rejected/suspended states, and unavailable professional evidence.
- Approved-clinic-only projection and no public identity, registration, patient, or internal native identifiers.
- Strict custom-route and REST parameter validation.
- `no-store` public HTML and REST responses until File 24 provides an audited cache-partition contract.
- Bounded provider registry and result sets.
- Timeline validation for public/review status, author/profile binding, provider ID/version, canonical same-site URLs, dates, and media references.
- Public REST/timeline redaction of provider IDs, native object IDs, WordPress user IDs, metrics pointers, and diagnostic details.
- Context-aware escaping, safe redirects, nonces/capability checks for recovery actions, and local-only runtime assets.
- Safe Mode that disables File 25 overrides without deleting native data.

## Known limits

The current branch has not completed File 24 integration, Hostinger staging, penetration testing, migration/rollback proof, or production monitoring. A green source CI run is not a security certification or production acceptance.

## Reporting

Do not disclose vulnerabilities in public issues. Report them through the platform's authorized security channel. Never include identity documents, patient records, secrets, access tokens, production credentials, or exploitable private details in reports.
