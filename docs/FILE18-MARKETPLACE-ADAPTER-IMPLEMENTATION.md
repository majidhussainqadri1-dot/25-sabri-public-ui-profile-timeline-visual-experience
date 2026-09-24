# File 18 Marketplace Profile Adapter

## Purpose

The `file-18-marketplace` provider supplies a bounded read-only visual projection of a profile owner's approved public Marketplace listings. It does not create or replace Marketplace records.

## Reviewed source contract

The adapter was written against the supplied **Sabri Marketplace 1.1.0** package.

Accepted source range:

```text
SMP_VERSION >= 1.1.0 and < 1.2.0
```

Required native runtime symbols:

```text
SMP_DB::table()
SMP_Activator::marketplace_url()
SMP_Utils::decode_json()
```

Required WordPress database operations are read-only prepared queries.

## Canonical native ownership

File 18 remains authoritative for:

- sellers and seller verification;
- products/listings and moderation;
- images and documents;
- public Marketplace application behavior;
- contact permissions;
- direct chat and offers;
- listing reports;
- views, ratings, review counts, sales counts, and analytics;
- deal state and transaction/direct-deal workflows.

File 25 owns only the normalized public profile presentation.

## Provider identity

```text
Provider ID: file-18-marketplace
Section: marketplace
Maturity: read-only
Owns native content: false
Maximum candidates: 24
```

The adapter cannot promote its own maturity. File 25 freezes provider ID, version, section, maturity, native-ownership state, and concrete object identity at registration and revalidates them atomically before every query.

## Native admission policy

A row is eligible only when all of the following are true:

- native product ID is positive internally;
- joined seller user ID equals the public profile user ID;
- seller status is `approved`;
- product status is `published` or `approved`;
- deal status is `available`, `reserved`, or `sold`;
- title is non-empty;
- the provider and exact reviewed runtime contract are available.

The product ID is used only inside the provider and its opaque projection fingerprint. It is not returned as a public card field.

## Public projection allow list

The adapter may return these presentation descriptors:

```text
type = marketplace-item
title
url
excerpt
eyebrow
image_url
image_alt
badge
badge_tone
meta
published_at
action_label
projection_key (server-only)
```

The effective displayed price uses a positive lower sale price when present; otherwise it uses the regular public price. The card metadata may include product type, condition, formatted currency/price, and public deal-state label.

## Application URL and projection key

The reviewed File 18 package provides one Marketplace application URL, not stable public product permalinks. File 25 therefore links cards honestly to the native Marketplace application instead of inventing unsupported item routes.

Several listings can share that URL. To avoid collapsing them into one card, the provider supplies:

```text
projection_key = SHA-256(file/provider namespace + native product identity + slug)
```

Rules:

- exactly 64 hexadecimal characters;
- consumed only by `Section_Service` for deduplication;
- not included in the content-card allow list;
- never rendered into HTML;
- never exposed through the public profile API;
- does not transfer native identity or ownership to File 25.

When a future accepted File 18 version supplies stable item permalinks, the canonical URL should replace this compatibility projection strategy after a new source-contract review.

## Explicit exclusions

The adapter must not expose:

- product ID, seller ID, WordPress user ID, attachment ID, or report ID;
- seller email, phone, WhatsApp, address, city, country, identity-document references, or verification notes;
- moderation notes and review decisions;
- chat messages, offers, offer amounts, contact requests, or participant identities;
- views, ratings, review counts, sales counts, reports, or analytics;
- storage paths, original file names, document metadata, or private media fields;
- payment, checkout, commission, escrow, or transaction authority.

## Failure behavior

The adapter fails closed when:

- File 18 is absent;
- the version is outside the reviewed range;
- required native classes/methods are unavailable;
- the WordPress database read API is unavailable;
- seller/profile ownership does not match;
- seller or listing state is not public and approved;
- deal state is unsupported;
- the public title is missing.

One provider failure is isolated from other profile sections. Public status reveals only a bounded provider error count, never internal provider diagnostics.

## Acceptance boundary

`read-only` source compatibility is not staging or production acceptance. Promotion requires exact Files 00/03/18/20/25 integration, real approved listings, privacy review, responsive/RTL/accessibility evidence, File 24 cache/security integration, rollback evidence, and Founder sign-off against the exact target commit.
