# File 25 Optional Profile Sections Contract

## Purpose

File 25 may present optional public profile sections only through accepted read-only providers registered by native module owners. The contract exists to prevent dead tabs, duplicate stores, private-data leakage, arbitrary HTML injection, provider impersonation, and ownership drift.

Contract version: `1.1.0`.

## Approved sections

- `knowledge`
- `media`
- `reviews`
- `research`
- `marketplace`

A section appears in profile navigation only when at least one accepted provider returns a valid public card after File 25 normalization.

## Registration

Native modules register providers through:

```php
do_action('sabri_public_experience/register_section_providers', $registry);
```

Providers implement:

```php
Sabri\PublicExperience\Contracts\Profile_Section_Provider
```

## Immutable provider identity

At registration File 25 freezes:

- provider ID;
- semantic version;
- approved section;
- maturity level;
- native-ownership declaration;
- concrete provider object identity through `spl_object_id()`.

Before a query, `Section_Registry::validated_metadata()` reads every mutable provider field exactly once and compares the complete snapshot with registration. A provider cannot impersonate another registered ID, self-promote maturity, switch sections, change version, or claim native ownership after registration.

Metadata exceptions and inconsistencies fail closed. Public status exposes only a bounded error count, not provider identity or exception details.

## Mandatory boundaries

- Provider IDs are unique, bounded, and validated.
- Versions use a semantic-version form.
- Sections and maturity levels use closed allow lists.
- Providers must report `owns_native_content() === false`.
- File 25 never writes, deletes, moderates, reviews, transacts, or changes native records.
- Provider output consists only of bounded public card descriptors.
- File 25 renders every accepted descriptor through `Content_Cards`; raw provider HTML is never accepted.
- Cross-origin destinations, unsafe schemes, credentials, fragments where forbidden, and malformed URLs fail closed.
- Internal IDs, patient data, private notes, metrics pointers, comments, reactions, transactions, and diagnostics are not accepted card fields.

## Canonical URL and opaque projection identity

A valid same-site canonical item URL is the preferred deduplication identity. URL path case remains significant.

Some native systems expose one application URL rather than item permalinks. Such a provider may supply an internal field:

```text
projection_key = 64-character hexadecimal SHA-256
```

Rules:

- the key is consumed only by `Section_Service` before rendering;
- it is not part of the content-card public allow list;
- it never appears in HTML or REST output;
- it must identify the native object inside the provider namespace without transferring native ownership;
- it is a compatibility mechanism, not a public identifier or invented route.

## Runtime limits

- Maximum registered providers: 32.
- Maximum candidates read from one provider for one section: 24.
- Maximum cards rendered in one section: 48.
- Duplicate card candidates are suppressed.
- Provider exceptions are isolated and counted without exposing provider IDs publicly.
- Truncation is reported honestly.

## Visibility rule

The public profile must already be authorized by File 00 and File 03 policy. A section provider cannot widen profile visibility, elevate a role, restore denied contacts, or create a route for a private account.

## Current native adapters

- `file-06-knowledge` — Homeopathy Encyclopedia Knowledge cards.
- `file-10-video-media` — Video Wall non-Reel cards.
- `file-11-reels-media` — Reels cards with 60–600 second validation.
- `file-12-pdf-media` — PDF Library cards.
- `file-18-marketplace` — approved seller-owned public Marketplace listing cards.

All are read-only source-contract integrations until exact staging acceptance.

## Ownership

- Native modules own canonical content, permissions, moderation, workflow, files, metrics, and transactions.
- File 25 owns only visual projection, section navigation, bounded card rendering, responsive presentation, accessibility, and visual acceptance.
- File 20 remains the sole global application-shell owner.

## Acceptance

A registered provider is not production-accepted merely because source tests pass. Exact multi-plugin staging, real public records, privacy checks, responsive and accessibility evidence, rollback, and Founder acceptance against the exact target commit remain mandatory.
