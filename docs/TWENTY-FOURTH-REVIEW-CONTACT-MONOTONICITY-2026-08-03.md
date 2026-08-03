# File 25 — Review 24 / Correction 24

Date: 2026-08-03

## Review focus

Public phone and WhatsApp value provenance, File 03 consent, and extension-filter monotonicity.

## Confirmed defects

1. The public-contact filter could replace a consented File 03 phone or WhatsApp value with a different arbitrary number.
2. A filter could add a contact field that was absent from the canonical consented projection.
3. The contact assembly still mentioned File 08 clinic values even though the File 08 public contract expressly excludes phone and WhatsApp.

## Corrections

- made File 03 the only owner of ordinary public contact values and consent;
- retained the File 03 Founder projection for Founder contact;
- removed File 08 clinic contact fallback;
- changed the extension filter to revoke-only behavior;
- allowed publication only when the field already exists canonically and the filter keeps it truthy;
- preserved the exact canonical value instead of accepting a replacement;
- added regression markers prohibiting the old substitution path.

## Acceptance boundary

The source now prevents contact substitution and contact-owner drift. Real consent changes, cache invalidation, private profiles, minors, and Hostinger staging remain mandatory acceptance cases.
