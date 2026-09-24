# File 25 — Reviews 27–38: Twelve-Round Corrective Record

Exact starting head: `30574e6eff07c9cf73e62a21df539d680692c2aa`.

This record covers twelve separate fresh reviews. Each review produced a concrete correction and an executable regression test. Hostinger staging, production acceptance, merge, and live deployment remain false.

| Review | Defect class | Correction | Regression test |
|---:|---|---|---|
| 27 | Permissive public timeline date rendering | Strict canonical UTC parsing and UTC display | `review27-timeline-template-date.php` |
| 28 | Ungoverned timeline query filters | Exact allow-list and canonical query handling | `review28-timeline-filter-allowlist.php` |
| 29 | Silent contact transformation/truncation | Strict fail-closed contact canonicalization | `review29-contact-canonicalization.php` |
| 30 | Encoded path ambiguity and traversal | Reject direct/double-encoded separators and dot segments | `review30-public-url-path-hardening.php` |
| 31 | Meaningful card images without accessible text | Bounded title fallback and orphan-alt removal | `review31-content-card-image-alt.php` |
| 32 | Ungoverned public timeline actions | Explicit public action allow-list | `review32-timeline-action-allowlist.php` |
| 33 | Update date preceding publication | Chronology validation after UTC normalization | `review33-timeline-chronology.php` |
| 34 | Divergent section SEO titles | Shared page title for HTML, OpenGraph, and JSON-LD | `review34-section-aware-seo-title.php` |
| 35 | Slug aliasing through sanitization or filters | Exact canonical slug agreement | `review35-profile-slug-exactness.php` |
| 36 | Cache collisions from bounded projections | Disable caching for unrepresentable profile projections | `review36-section-cache-fail-closed.php` |
| 37 | ETag emitted but not honored | Deterministic associative ETags and safe 304 handling | `review37-rest-etag-revalidation.php` |
| 38 | Weak title/type/date section deduplication | Projection key, canonical URL, then complete normalized card hash | `review38-section-card-deduplication.php` |

## Acceptance boundary

These are source-level and automated-QA corrections. They do not imply Hostinger staging acceptance, Founder acceptance, production readiness, merge authorization, or live deployment.
