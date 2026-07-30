# File 06 Knowledge Adapter Implementation

## Reviewed native package

- Package: `06-homeopathy-encyclopedia-foundation-0.1.0.zip`
- Native plugin: **Homeopathy Encyclopedia Foundation**
- Native version constant: `HE_VERSION = 0.1.0`
- Canonical post type: `HE_Content::TYPE = he_entry`
- Canonical taxonomy: `HE_Content::TAX = he_type`
- Native public state: WordPress `publish`
- Native author field: WordPress `post_author`
- Native canonical route: `/encyclopedia-entry/{post-slug}/`

## File 06 ownership preserved

File 06 remains the sole owner of:

- encyclopedia entries and full content bodies;
- knowledge types and relationships;
- editorial submission and review workflow;
- references, medical red flags, safety fields, corrections, reports, bookmarks, comments, views, and audit records;
- permissions and publication state.

File 25 performs no File 06 write, moderation, interaction, or record-management operation.

## File 25 provider

Canonical provider ID:

```text
file-06-knowledge
```

Section:

```text
knowledge
```

Maturity:

```text
read-only
```

Reviewed compatibility range:

```text
>= 0.1.0 and < 0.2.0
```

The adapter fails closed for malformed, older, or unknown-major versions; a future File 06-owned provider registered first under the canonical ID takes precedence.

## Admission rules

A descriptor is admitted only when:

- File 06 is active and its reviewed post type exists;
- the profile is already public under File 00/File 03 policy;
- the profile is not a patient profile;
- the native entry is `publish`;
- the native `post_author` equals the displayed profile user;
- the entry has no password;
- the native object remains an `he_entry`;
- the native title and permalink are present.

## Public descriptor

The adapter returns only bounded presentation fields:

- article type;
- native title;
- native permalink;
- native excerpt;
- “Homeopathy Encyclopedia” eyebrow;
- same-origin featured image reference;
- native public knowledge-type label;
- optional public body-system label;
- native publication date;
- “Read entry” action label.

It does not return post IDs, user IDs, view counts, bookmark state, feedback, comments, references, private metadata, audit records, or internal provider diagnostics.

## Query limits

- Maximum File 06 candidates per profile request: 24.
- Query is author-bound, publish-only, password-free, newest-first, and no-found-rows.
- The general File 25 section service performs final card validation, same-origin enforcement, deduplication, and visual rendering.

## Acceptance boundary

This adapter is source-contract complete against the supplied File 06 `0.1.0` package. It remains `read-only`; exact Files 00/03/06/20/25 staging, real entry data, responsive/accessibility evidence, privacy verification, rollback, and Founder acceptance are required before maturity promotion.
