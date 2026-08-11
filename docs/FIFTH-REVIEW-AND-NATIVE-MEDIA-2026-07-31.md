# Fifth Review, Defect Correction, and Native Media Adapters — 2026-07-31

## Scope

This review was performed after File 25 version 0.7.0 and before continuing the next coding segment. The reviewed sources were the current File 25 branch and the supplied packages:

- File 10 — `10-video-wall-and-educational-broadcasting-foundation-0.1.0.zip`
- File 11 — `11-reels-and-short-video-discovery-foundation-0.1.0.zip`
- File 12 — `12-pdf-library-and-digital-reading-foundation-0.1.0.zip`

The adapters are read projections only. Files 10, 11, and 12 remain the canonical owners of their post objects, publishing rules, moderation, interactions, metrics, files, and workflows.

## Confirmed defects and corrections

### Provider metadata was not completely immutable

The registry froze provider ID, version, and section, but not maturity or native-ownership state. A mutable provider could therefore change those values after registration.

Correction:

- registration snapshots now include ID, version, section, maturity, and `owns_native_content`;
- query-time consistency checks compare every frozen field;
- self-promotion or an ownership claim fails closed.

### Provider consistency failures were not counted truthfully

Metadata exceptions and consistency failures were isolated, but the public section result could still report zero provider errors.

Correction:

- provider usability now has explicit `usable`, `skip`, and `error` outcomes;
- metadata mutation, invalid maturity, ownership claims, and exceptions increase the bounded error count;
- public output still exposes only a count, never provider identities or exception details.

### Canonical duplicates could survive

The previous fallback fingerprint included title and date even when a valid canonical URL existed. Two providers could therefore produce duplicate cards for the same destination with different display text.

Correction:

- a valid same-site canonical URL is now the primary deduplication identity;
- path case remains significant;
- title/type/date are used only when there is no canonical URL.

### Visual evidence accepted placeholders

The first Definition-of-Done validator accepted non-empty booleans for many evidence cells. That proved checklist completion, not evidence integrity.

Correction:

- every matrix cell now requires status, artifact reference, SHA-256, timestamp, and reviewer;
- staging evidence requires HTTPS site URL, exact commit SHA, WordPress/PHP versions, and timestamp;
- Founder sign-off requires accepted status, signer, commit SHA, evidence reference, and timestamp;
- the Media section is now a mandatory visual-acceptance surface.

## Reviewed File 10 contract

The supplied File 10 package defines:

- version constant `SVW_VERSION = 0.1.0`;
- canonical post type `SVW_Helpers::TYPE = svw_video`;
- category taxonomy `SVW_Helpers::TAX = svw_category`;
- public state `publish`;
- author ownership through `post_author`;
- Reel marker `_svw_is_reel`;
- public presentation helpers for category, duration, language, permalink, excerpt, and thumbnail.

File 25 adapter:

- canonical ID `file-10-video-media`;
- section `media`;
- maturity `read-only`;
- accepts reviewed versions `>=0.1.0 <0.2.0`;
- excludes Reel objects so File 11 can present them without duplication;
- exposes no views, likes, dislikes, saves, reports, internal review notes, attachment IDs, or native object IDs.

## Reviewed File 11 contract

The supplied File 11 package defines:

- version constant `SRL_VERSION = 0.1.0`;
- dependency on File 10 and `SVW_Helpers`;
- canonical objects stored as File 10 `svw_video` posts;
- Reel marker `_svw_is_reel = 1`;
- mandatory duration from 60 to 600 seconds;
- public state `publish` and author ownership through `post_author`.

File 25 adapter:

- canonical ID `file-11-reels-media`;
- section `media`;
- maturity `read-only`;
- requires reviewed File 10 and File 11 0.1.x contracts;
- independently revalidates the 60–600 second duration;
- exposes no history, progress, replay counts, reactions, saves, scores, review actions, or native IDs.

## Reviewed File 12 contract

The supplied File 12 package defines:

- version constant `SPL_VERSION = 0.1.0`;
- canonical post type `SPL_Helpers::TYPE = spl_document`;
- document taxonomy `SPL_Helpers::DOCTYPE = spl_document_type`;
- public state `publish`;
- author ownership through `post_author`;
- public title, excerpt, permalink, cover, document type, pages, language, and publication time;
- encrypted file storage and native reading/download workflows.

File 25 adapter:

- canonical ID `file-12-pdf-media`;
- section `media`;
- maturity `read-only`;
- accepts reviewed versions `>=0.1.0 <0.2.0`;
- exposes no storage names, original file names, encryption data, stream nonces, downloads, notes, bookmarks, progress, reactions, reports, views, reads, saves, or native IDs.

## Preserved ownership

- File 10 owns Video Wall objects and broadcasting workflows.
- File 11 owns Reel-specific creation, discovery, duration policy, history, and management.
- File 12 owns PDF files, encryption, reader/download authorization, documents, and reading workflows.
- File 25 owns only the normalized visual projection inside the public profile Media section.

## Automated evidence

The CI matrix now includes:

- PHP 8.0 and 8.3 syntax;
- exact provider identity, version, section, maturity, and ownership tests;
- public author/status/password/post-type checks;
- File 10 non-Reel separation;
- File 11 60–600 second duration enforcement;
- File 12 public PDF projection;
- no-write package scanning;
- strict visual evidence records;
- existing profile, timeline, privacy, Safe Mode, URL, card, design-system, and package tests.

## Remaining gates

Source integration is not staging acceptance. Remaining gates include exact multi-plugin staging, File 24 runtime integration, visual-regression artifacts, RTL/accessibility/performance evidence, Reviews/Research/Marketplace adapters, migration, rollback, release packaging, deployment, monitoring, and Founder sign-off.
