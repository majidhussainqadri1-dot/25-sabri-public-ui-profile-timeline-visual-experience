# Privacy Contract

File 25 is a public presentation layer and follows data minimization. File 00 and File 03 remain the authoritative owners of identity, approval, age, role, profile, and contact-consent data.

## File 25 does not own or expose

- identity documents, registration/licence numbers, reviewer notes, or encrypted evidence;
- full dates of birth, gender, guardian identity, or consent records;
- patient health histories, appointments, prescriptions, symptoms, searches, or treating-Doctor relationships;
- private messages, calls, saves, reading history, learning progress, reports, blocks, or private analytics;
- internal WordPress user IDs, provider IDs, native object IDs, metrics pointers, or exception details in public APIs;
- unapproved clinic records or raw contact information without authoritative visibility consent.

## Public projection rules

- Founder identity must be confirmed by File 00.
- A public verified-Doctor profile requires approved adult membership and current professional evidence.
- Ordinary members require an approved adult account and explicit public visibility.
- Ordinary-member Phone/WhatsApp display additionally requires File 03 public-contact consent.
- Minor, unknown-age, private/member-only, rejected, suspended, and unavailable-professional states fail closed.
- Filters may make a surface more restrictive but may not widen visibility or restore denied fields.
- Only approved clinic contact data may enter the public projection.

## Timeline privacy

The timeline contains only bounded, rebuildable pointers and lightweight public display metadata. It rejects private, draft, pending-review, wrong-author, provider-spoofed, external-canonical, or malformed items. Public output omits internal identifiers and diagnostics. Native modules remain the source of truth.

## Cache policy

Profile HTML and REST responses use `no-store` until File 24 provides an audited public/private cache-partition and invalidation contract. This prevents stale contact or visibility data from remaining public after consent or account-state changes.

## Erasure boundary

File 25 currently owns no permanent publication body or clinical record. Any future rebuildable index must support deletion/reconciliation when the authoritative native owner erases or withdraws a public object.
