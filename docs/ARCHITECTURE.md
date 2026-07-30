# File 25 Architecture

## Governing rule

**One Unified Public Experience — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current phase

This branch implements and reviews phases **25A — Governance and Contracts** and **25B — Plugin Foundation and Safety**. It does not represent staging or production completion.

## Ownership

- File 00: identity, roles, approval, age/guardian authority, public visibility, and professional eligibility.
- File 03: profile master data, photographs, and public-contact consent.
- File 20: global shell, navigation, widths, and base design tokens.
- File 21: Home/News publications and the production timeline provider.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, cache partitioning, and resilience.
- File 25: public profile templates, a rebuildable read projection of approved native contributions, public components, states, and visual acceptance.

## Public profile projection

File 25 creates no second profile authority. It reads the minimum presentation fields required from File 00 and File 03, then applies monotonic visibility rules:

- a setting or filter may make a surface more restrictive but may not elevate Founder/Doctor status, widen visibility, restore denied contact data, or fabricate a missing dependency;
- public Doctor presentation requires an approved adult File 00 Doctor identity, current professional evidence, and no File 03 rejection/suspension;
- ordinary member contact requires a public approved profile and explicit File 03 public-contact consent;
- minors, unknown-age accounts, private/member-only profiles, and rejected/suspended states fail closed;
- public responses contain no identity documents, registration numbers, patient data, internal WordPress user IDs, or private native identifiers.

## Routing and cache behavior

Canonical routes are:

- `/founder/`
- `/doctors/{slug}/`
- `/profile/{slug}/`

Only sections that can be rendered truthfully are exposed. Role-route mismatch and alias slugs redirect to the canonical destination. Unknown or unavailable sections return a non-cacheable 404 rather than an empty duplicate page.

Until File 24 supplies an audited public/private cache-partition contract, profile HTML and REST responses remain `no-store`.

## Timeline design

Providers expose public, approved, canonical objects through a read-only interface. File 25:

- bounds the provider registry and each provider result set;
- validates provider ID, version, maturity, item identity, public status, review state, author/profile binding, dates, media references, and same-site canonical destinations;
- asks every provider for a bounded newest-candidate pool beginning at provider page one;
- performs the final cross-provider merge, sort, canonical deduplication, and pagination itself;
- returns public-safe items without provider IDs, native object IDs, WordPress user IDs, metrics pointers, or diagnostic details;
- reports partial and truncated states honestly.

The WordPress posts provider is a compatibility baseline only. It is registered only while File 21 is absent. An active File 21 installation without a registered production provider yields an honest unavailable timeline instead of bypassing File 21 with raw WordPress queries.

File 21 registers its provider from an integration listener:

```php
add_action(
    'sabri_public_experience/register_timeline_providers',
    static function ($registry): void {
        $registry->register(new File_21_Timeline_Provider());
    }
);
```

The canonical provider ID must be `file-21`; production readiness requires maturity `production-accepted` after staging acceptance.

## Integration hooks

- `sabri_public_experience/dependency/*`
- `sabri_public_experience/profile_by_slug`
- `sabri_public_experience/public_profile_data`
- `sabri_public_experience/can_render_profile`
- `sabri_public_experience/can_show_contact`
- `sabri_public_experience/available_profile_sections`
- `sabri_public_experience/register_timeline_providers`
- `sabri_public_experience/provider_error`
- `sabri_public_experience/safe_mode_changed`

All extension hooks are followed by authoritative revalidation where they could affect public identity, contact, visibility, routing, or timeline admission.

## Safe failure

A missing required dependency prevents public overrides from booting. File 25 Safe Mode disables only File 25 public overrides, records bounded incident metadata, exposes an authenticated administrator retry, and returns control to the native theme and modules without deleting data. File 24 remains the canonical incident owner.

## Existing-module compatibility detected in phase 25A

The foundation consumes the currently shipped module contracts without copying their data:

- File 00: `SMC_VERSION`, `smc_get_profile()`, `smc_is_founder()`, `smc_user_status()`, and documented owned tables.
- File 03: `SPD_VERSION` and `SPD_Helpers` compatibility reads.
- File 20: `SABRI_SHELL_VERSION`, `sabri_shell_layout_mode`, and `--sabri-shell-*` design tokens.
- File 21: `SABRI_HNF_VERSION`; its production provider remains an explicit pending adapter.

See `REVIEW-AND-CORRECTION-2026-07-30.md` for the defect register and corrections.
