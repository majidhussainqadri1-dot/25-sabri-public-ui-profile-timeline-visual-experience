# File 25 — Review 22 / Correction 22

Date: 2026-08-03

## Review focus

Canonical timeline identity, duplicate suppression, and deterministic ordering.

## Confirmed defects

1. Timeline same-site checks duplicated URL policy instead of using the governed `Public_URL` contract.
2. Canonical identity preserved explicit default ports, so `https://example.com/x` and `https://example.com:443/x` could be treated as different objects.
3. Host trailing-dot normalization was incomplete.
4. Sorting still used permissive `strtotime()` despite normalized items already carrying strict UTC timestamps.
5. Equal pin weight and equal publication time had no total deterministic tie-breaker.

## Corrections

- routed canonical acceptance through strict same-site `Public_URL` validation;
- normalized HTTP `80` and HTTPS `443` out of canonical identity;
- lowercased and trailing-dot-normalized hosts while preserving path case and query semantics;
- replaced `strtotime()` with lexical comparison of normalized UTC timestamps;
- added canonical URL, provider ID, native type, and native ID as a deterministic final identity order;
- retained monotonic filters that may revoke but cannot grant an invalid URL.

## Acceptance boundary

The correction establishes deterministic source behavior and regression coverage. Real multi-provider Hostinger staging, cache, pagination, and visual acceptance remain pending.
