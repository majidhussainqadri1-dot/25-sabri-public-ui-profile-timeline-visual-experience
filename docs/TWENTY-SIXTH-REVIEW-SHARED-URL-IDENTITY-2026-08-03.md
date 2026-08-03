# File 25 — Review 26 / Correction 26

Date: 2026-08-03

## Review focus

Cross-component canonical URL identity for timeline items and optional profile-section cards.

## Confirmed defect

Timeline deduplication had received default-port and trailing-host normalization, but optional section cards still hashed the merely sanitized URL. Equivalent destinations such as an omitted HTTPS port and explicit `:443` could therefore be one timeline identity but two section-card identities. This created divergent deduplication semantics inside File 25.

## Corrections

- added one governed `Public_URL::canonical_same_site_identity()` contract;
- retained strict same-site validation before canonicalization;
- normalized host case, trailing host dots, HTTP port 80, HTTPS port 443, and trailing path slash;
- preserved path case, query semantics, and non-default ports;
- applied the shared identity to timeline duplicate suppression and deterministic tie-breaking;
- applied the same identity to optional section-card duplicate suppression;
- added executable checks for default ports, cross-origin rejection, mismatched ports, relative paths, and both consumers.

## Eight-round completion boundary

Reviews 19–26 now constitute eight separate review-and-correction commits. This source completion does not replace canonical Hostinger multi-plugin staging, RTL/accessibility/performance evidence, rollback/restore testing, independent human review, Founder acceptance, merge approval, or production deployment.
