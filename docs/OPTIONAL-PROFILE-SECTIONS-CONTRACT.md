# File 25 Optional Profile Sections Contract

## Purpose

File 25 may present optional public profile sections only through accepted read-only providers registered by native module owners. The contract exists to prevent dead tabs, duplicate stores, private-data leakage, and arbitrary HTML injection.

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

## Mandatory boundaries

- Provider IDs are unique, bounded, and validated.
- Versions use a semantic-version form.
- Sections and maturity levels use closed allow lists.
- Providers must report `owns_native_content() === false`.
- File 25 never writes, deletes, moderates, reviews, transacts, or changes native records.
- Provider output consists only of bounded public card descriptors.
- File 25 renders every accepted descriptor through `Content_Cards`; raw provider HTML is never accepted.
- Cross-origin destinations, unsafe schemes, credentials, fragments where forbidden, and malformed URLs fail closed.
- Internal IDs, patient data, private notes, metrics pointers, comments, reactions, and diagnostics are not accepted card fields.

## Runtime limits

- Maximum registered providers: 32.
- Maximum candidates read from one provider for one section: 24.
- Maximum cards rendered in one section: 48.
- Duplicate card candidates are suppressed.
- Provider exceptions are isolated and counted without exposing provider IDs publicly.
- Truncation is reported honestly.

## Visibility rule

The public profile must already be authorized by File 00 and File 03 policy. A section provider cannot widen profile visibility, elevate a role, restore denied contacts, or create a route for a private account.

## Ownership

- Native modules own canonical content, permissions, moderation, workflow, metrics, and transactions.
- File 25 owns only visual projection, section navigation, bounded card rendering, responsive presentation, accessibility, and visual acceptance.
- File 20 remains the sole global application-shell owner.

## Acceptance

A registered provider is not production-accepted merely because source tests pass. Exact multi-plugin staging, real public records, privacy checks, responsive and accessibility evidence, rollback, and Founder acceptance remain mandatory.
