# File 25 — Reviews 54–93 — Forty-Round Corrective Record

**Canonical module:** File 25 — Sabri Unified Global Visual Experience and Design System  
**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency  
**Runtime:** 0.14.0  
**Review date:** 2026-08-03  
**Acceptance boundary:** source correction and automated regression evidence only. Hostinger staging, Founder acceptance, merge, production, and live deployment remain separate gates.

The forty reviews below were performed sequentially. Each review ended with its own source correction and dedicated executable regression. Later rounds retained all earlier gates.

| Review | Defect found | Correction and regression |
|---:|---|---|
| 54 | Timeline provider IDs could be trimmed before registration. | Require exact canonical raw ID; `review54-corrective-regression.php`. |
| 55 | Timeline versions could be accepted after whitespace trimming. | Require exact bounded semantic version; Review 55 regression. |
| 56 | Timeline maturity aliases could be normalized. | Require exact enumerated maturity; Review 56 regression. |
| 57 | Timeline lookup could canonicalize an alias into a registered provider. | Exact lookup only; Review 57 regression. |
| 58 | Timeline unregister could remove a provider through an alias. | Non-canonical unregister is a no-op; Review 58 regression. |
| 59 | Expected provider identity was canonicalized during metadata validation. | Expected ID must already be exact; Review 59 regression. |
| 60 | Timeline page/per-page accepted floats, scientific notation, whitespace, or leading zeros. | Exact positive decimal integers only; Review 60 regression. |
| 61 | Timeline content/provider filters silently transformed aliases. | Exact optional safe keys only; Review 61 regression. |
| 62 | A string such as `false` from the canonical-URL filter became truthy. | Filter grant requires the boolean `true`; Review 62 regression. |
| 63 | A provider returning exactly the hard cap could conceal additional items without `truncated=true`. | Exact-cap responses are conservatively marked truncated; Review 63 regression. |
| 64 | Profile-section provider IDs were canonicalized before registration. | Require exact raw provider ID; Review 64 regression. |
| 65 | Section provider versions could be whitespace-normalized. | Require exact semantic version; Review 65 regression. |
| 66 | Section aliases could be normalized into approved sections. | Exact approved section required; Review 66 regression. |
| 67 | Maturity aliases could be normalized. | Exact approved maturity required; Review 67 regression. |
| 68 | Section registry lookup/selection could accept aliases. | Exact ID and section lookup only; Review 68 regression. |
| 69 | Approval helper methods normalized their arguments. | Exact approved keys only; Review 69 regression. |
| 70 | Mutable provider metadata was normalized during revalidation. | Re-read raw fields and compare exact registration snapshot; Review 70 regression. |
| 71 | A transformed section request could reach a canonical public section. | Exact request section or fail closed; Review 71 regression. |
| 72 | Associative provider responses could masquerade as lists. | Provider result must be a list; Review 72 regression. |
| 73 | Valid early cards could survive a malformed late card. | Validate each provider batch transactionally; Review 73 regression. |
| 74 | Projection keys were lowercased/trimmed before identity use. | Supplied projection key must be exact lowercase SHA-256; Review 74 regression. |
| 75 | Section request cache did not bind the provider registry snapshot. | Cache key includes deterministic registry metadata fingerprint; Review 75 regression. |
| 76 | Public health counted mature but unavailable providers as enabled. | Enabled count now requires real availability; Review 76 regression. |
| 77 | Profile route type was sanitized into a recognized type. | Preserve and validate exact route type; Review 77 regression. |
| 78 | Profile route section was sanitized into an approved section. | Preserve and validate exact section; Review 78 regression. |
| 79 | A truthy string could enable the File 20 right sidebar. | Third column requires exact boolean `true`; Review 79 regression. |
| 80 | REST pagination accepted ambiguous numeric representations. | Exact decimal integer sanitizer/validator; Review 80 regression. |
| 81 | REST content type could be transformed by sanitization. | Exact canonical key validation; Review 81 regression. |
| 82 | `If-None-Match` accepted unbounded headers and token counts. | Bound length, controls, and token count; Review 82 regression. |
| 83 | Recursive ETag canonicalization had no depth/entry budget. | Bound depth and entries; omit ETag if uncacheable; Review 83 regression. |
| 84 | Profile-visibility filters cast non-booleans. | Monotonic grant requires exact boolean `true`; Review 84 regression. |
| 85 | Contact-visibility filters cast non-booleans. | Monotonic contact grant requires exact boolean `true`; Review 85 regression. |
| 86 | Dependency availability filters cast non-booleans. | All dependency gates require exact boolean truth; Review 86 regression. |
| 87 | Founder extension filter cast non-booleans. | Founder extension requires exact boolean truth; Review 87 regression. |
| 88 | Doctor extension filter cast non-booleans. | Doctor extension requires exact boolean truth; Review 88 regression. |
| 89 | Public-contact revocation map cast arbitrary values. | A canonical field survives only when its filter value is exactly `true`; Review 89 regression. |
| 90 | Available-section filters sanitized aliases into existing tabs. | Only exact existing section values survive; Review 90 regression. |
| 91 | Query/fragment components lacked recursive decoding and Unicode format-control defenses. | Bounded recursive component validation and bidi/zero-width control rejection; Review 91 regression. |
| 92 | Timeline IDs, pin weight, list fields, and actions accepted ambiguous casts. | Exact bounded integers and exact list/action contracts; Review 92 regression. |
| 93 | A normalized item could exist with an external canonical URL before service filtering. | Constructor now requires an exact same-site HTTP(S) canonical URL; Review 93 regression. |

## Corrective QA law

- All forty dedicated regressions are registered in the governed Composer suite.
- Reviews 20–53 and all authority, privacy, integration, release, installed-package, upgrade, Safe Mode, and structure tests remain mandatory.
- A provider batch is never partially trusted after a contract failure.
- Extension filters may revoke authority but non-boolean values cannot grant it.
- Green source or CI evidence is not Hostinger staging, Founder, production, or live acceptance.
- Any new defect, dependency release, staging result, or user report reopens the cycle.
