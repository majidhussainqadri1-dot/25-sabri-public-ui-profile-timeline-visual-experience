# File 25 — File 21 Timeline Adapter Implementation Record

Date: 2026-07-30

## Native contract reviewed

File 21 version 1.0.3 exposes the public read contract:

```php
Sabri\HomeNewsFeed\ProfileTimeline::query($author_id, $args)
```

The File 21 query remains authoritative for:

- WordPress publication status;
- author scoping;
- public visibility metadata;
- review-state eligibility;
- object-level viewer authorization;
- password and private-content exclusion;
- File 21 Safe Mode and public-feature gates;
- canonical post URL, title, public excerpt, and publication time.

File 25 does not reproduce those rules and does not query unreviewed File 21 tables or private metadata.

## File 25 adapter

The adapter is registered with the canonical provider ID:

```text
file-21
```

It performs only these operations:

1. detects the real File 21 class, query method, and runtime version;
2. requests a bounded candidate pool for one author;
3. keeps File 21's native page size fixed at twenty items so page offsets remain stable;
4. converts File 21's already-public serializer into `Normalized_Timeline_Item` pointers;
5. supplies File 25's global same-site canonical validation, sorting, deduplication, and pagination;
6. redacts native post IDs, author IDs, provider identifiers, and internal references from public output.

## Ownership and privacy boundaries

The adapter contains no operation that can:

- create, update, schedule, approve, publish, reject, or delete a post;
- read a draft or private post body;
- write File 21 metadata;
- copy comments, reactions, saves, reports, views, polls, or audit records;
- expose patient data, credentials, moderation notes, or raw analytics;
- bypass an active but incompatible File 21 installation through raw WordPress queries.

A native provider registered by File 21 itself under `file-21` takes precedence. File 25's WordPress fallback is used only when File 21 is absent.

## Maturity

The adapter reports `read-only` and cannot promote itself to `staging-accepted` or `production-accepted` through an ordinary filter. Source-contract success is not staging acceptance.

Promotion requires controlled staging with the exact accepted Files 00, 03, 20, 21, and 25 packages and evidence for:

- Founder, verified Doctor, general Member, Patient, Student, minor, suspended, rejected, and logged-out states;
- public/private/review-state and cross-user authorization;
- pagination beyond the first File 21 page;
- canonical URLs and duplicate suppression;
- Safe Mode and incompatible-version failure;
- browser, mobile, keyboard, screen-reader, zoom, reduced-motion, forced-colors, Urdu, and RTL behavior;
- cache invalidation, rollback, and backup restoration.

## Automated source evidence

The PHP 8.0 and PHP 8.3 contract test verifies:

- canonical provider identity;
- native version detection;
- availability and read-only maturity;
- three-page retrieval for a forty-five-item candidate pool;
- fixed twenty-item native page size across all File 21 requests;
- safe title/excerpt normalization;
- internal-ID redaction;
- rejection of unsupported content types;
- disabled-state fail-closed behavior;
- explicit denial of native content ownership.

Package verification additionally rejects write primitives inside the adapter source.
