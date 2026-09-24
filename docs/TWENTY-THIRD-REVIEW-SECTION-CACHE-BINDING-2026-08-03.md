# File 25 — Review 23 / Correction 23

Date: 2026-08-03

## Review focus

Request-local optional-section cache identity and profile-context isolation.

## Confirmed defect

The section cache key used only user ID, section, and profile class. Within one request, two materially different public projections for the same user and class could therefore share cached provider output. Changes to public contact, clinic, professional, visibility, or presentation values were not represented in the cache identity.

## Corrections

- bound the cache identity to a SHA-256 digest of the complete bounded public profile projection;
- deterministically sorted associative keys so equivalent projections produce one identity;
- preserved list ordering where order is semantically meaningful;
- bounded recursion depth, entry count, key length, and scalar length;
- represented truncated and unsupported structures deterministically;
- retained request-local storage only—no raw profile projection is written to persistent cache or database;
- added regressions for equivalent key order, material profile changes, and adversarial deep structures.

## Acceptance boundary

This correction prevents request-local cross-context reuse. Hostinger object-cache behavior, real provider integration, concurrency, and production acceptance remain pending.
