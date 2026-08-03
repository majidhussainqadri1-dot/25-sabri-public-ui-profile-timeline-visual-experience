# File 25 — Review 25 / Correction 25

Date: 2026-08-03

## Review focus

Canonical public identity and post-validation media filter boundaries.

## Confirmed defects

1. The general public-profile filter could rename a non-Founder account after File 00/File 03 identity resolution.
2. The same filter could replace a validated File 03 avatar or cover with another same-site URL that had not passed File 03 ownership and purpose checks.
3. Same-site URL validation alone was therefore insufficient after canonical media verification.

## Corrections

- made non-Founder display name derive only from the canonical WordPress/File 00 identity and retained the fixed Founder spelling;
- retained bounded extension control over headline and biography presentation only;
- changed avatar and cover filtering to revoke-only behavior;
- required the filtered media URL to be byte-identical to the already validated canonical URL;
- rejected additions, replacements, non-scalars, malformed URLs, and removed canonical media;
- added regression markers prohibiting identity and media substitution.

## Acceptance boundary

The source now prevents post-validation identity and media substitution. Real profile edits, media replacement, CDN rewriting, cache behavior, and Hostinger staging remain pending.
