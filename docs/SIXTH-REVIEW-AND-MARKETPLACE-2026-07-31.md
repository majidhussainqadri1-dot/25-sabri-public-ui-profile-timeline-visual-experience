# Sixth Review, Defect Correction, and File 18 Marketplace Adapter — 2026-07-31

## Scope

This review was performed after File 25 version 0.8.0 and before continuing the next coding segment. The reviewed basis was the current File 25 branch and the supplied package `18 sabri-marketplace-1.1.0.zip`.

The File 18 integration is a read-only visual projection. File 18 remains the canonical owner of sellers, products, listing moderation, images/files, contacts, chat, offers, reports, metrics, and direct-deal workflows.

## Confirmed defects and corrections

### Section-provider metadata was not bound to the concrete object instance

A provider registration snapshot froze ID, version, section, maturity, and native-ownership state, but did not freeze the concrete provider object identity. A mutable provider could attempt to change its ID to another registered provider ID.

Correction:

- registration metadata now records `spl_object_id()`;
- query-time validation compares the concrete object identity with the registration snapshot;
- a provider object cannot impersonate another registered provider;
- all mutable metadata is read once through one atomic validation operation.

### Section metadata was read again after consistency validation

The section service validated provider metadata and then separately read maturity and native-ownership state again. An unstable provider could return different values across those reads.

Correction:

- `Section_Registry::validated_metadata()` returns one registration-bound snapshot;
- the section service consumes that snapshot rather than re-reading mutable metadata;
- mutation, exceptions, self-promotion, and native-ownership claims fail closed and increment only a bounded public error count.

### Timeline providers had the same mutable-identity weakness

The timeline registry validated provider ID, version, and maturity only at registration. The timeline service later trusted the registry key but re-read mutable version and maturity values. A provider could change metadata after registration and attempt to return items under the modified version.

Correction:

- timeline provider ID, version, maturity, and concrete object identity are now frozen;
- `Timeline_Registry::validated_metadata()` performs one atomic query-time comparison;
- the timeline service and Site Health consume the immutable snapshot rather than re-reading version or maturity;
- provider impersonation, version mutation, and maturity self-promotion fail closed and are reported against the registered provider key.

### Native objects sharing one application URL could collapse

Some canonical native systems expose one application URL rather than public item permalinks. File 18 currently opens listing details inside the Marketplace application, so several product cards would otherwise share the same URL and be deduplicated into one card.

Correction:

- providers may return an optional server-only `projection_key`;
- it must be a 64-character SHA-256 hexadecimal value;
- File 25 uses it only for deduplication before rendering;
- it is outside the content-card public allow list and never appears in HTML or REST output;
- canonical item URLs still take precedence where native modules provide them.

### Visual evidence could be mixed across commits

The evidence contract required checksums and timestamps, but individual evidence records were not bound to one target source commit.

Correction:

- every manifest now declares one `target_commit_sha`;
- every surface, viewport, direction, color, motion, zoom, and input evidence record must contain the same commit SHA;
- staging evidence and Founder sign-off must match the same target commit;
- evidence from multiple source revisions cannot be combined into one accepted manifest.

### Invalid timestamps could be normalized

Generic date parsing may normalize impossible dates or times.

Correction:

- evidence timestamps now use a strict ISO-8601 shape;
- calendar date, clock time, and UTC-offset limits are validated explicitly;
- parser warnings and errors are rejected;
- staging URLs must be canonical HTTPS URLs without credentials, query strings, fragments, control characters, or backslashes.

### Marketplace status translations were not statically extractable

The first File 18 adapter draft passed a variable status label into the WordPress translation function.

Correction:

- Available, Reserved, and Sold are now literal translation strings in the source;
- standard WordPress translation extraction can discover them.

## Reviewed File 18 contract

The supplied File 18 package defines:

- plugin version `SMP_VERSION = 1.1.0`;
- native seller and product tables through `SMP_DB::table()`;
- seller ownership through `sellers.user_id`;
- approved seller state `approved`;
- public product states `published` and `approved`;
- deal states including `available`, `reserved`, and `sold`;
- public listing presentation fields including title, slug, category, product type, condition, short description, price, sale price, currency, images, deal state, and publication time;
- one Marketplace application URL through `SMP_Activator::marketplace_url()`;
- native contact, chat, offer, report, moderation, media, metric, and transaction workflows.

## File 18 adapter

Canonical provider:

- ID: `file-18-marketplace`
- section: `marketplace`
- maturity: `read-only`
- reviewed compatibility: `>=1.1.0 <1.2.0`

Admission requires:

- the profile owner to match the native seller user ID;
- seller status `approved`;
- listing status `published` or `approved`;
- deal status `available`, `reserved`, or `sold`;
- a non-empty title;
- a bounded query limit.

The public card may contain title, short description, Marketplace application URL, first public image URL, category, product type, condition, effective public price/currency, public deal-state label, and publication date.

The projection excludes product/seller/user/attachment/report IDs, identity/contact evidence, moderation notes, chats/offers, metrics, storage details, and transaction authority.

## Visual acceptance extension

The Marketplace section is now a mandatory visual-acceptance surface. Its real staging evidence must be captured against the exact target commit before the section can contribute to production acceptance.

## Automated evidence added

The PHP 8.0/8.3 suite now covers:

- section and timeline provider concrete-object identity;
- one-read atomic metadata validation;
- version, maturity, ownership, and impersonation rejection;
- server-only projection-key behavior;
- File 18 version, seller, author, listing, deal-state, price, privacy, and no-ownership boundaries;
- commit-bound visual evidence;
- impossible timestamp rejection;
- canonical staging URL validation;
- package and no-write scanning.

## Remaining gates

Source-level integration is not staging acceptance. Exact multi-plugin staging, File 24 runtime integration, real Marketplace content, responsive/RTL/accessibility/performance evidence, migration, rollback, packaging, deployment, monitoring, and explicit Founder sign-off remain mandatory.
