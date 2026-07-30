# File 25 Architecture

## Governing rule

**One Unified Public Experience — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current phase

This branch implements phases **25A — Governance and Contracts** and **25B — Plugin Foundation and Safety**.

## Ownership

- File 00: identity, roles, age/guardian authority, contact consent.
- File 03: profile master data.
- File 20: global shell, navigation, widths, and base design tokens.
- File 21: Home/News publications and the production timeline provider.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, and resilience.
- File 25: public profile templates, timeline projection, cards, states, and public visual experience.

## Timeline design

Providers expose public, approved, canonical objects through a read-only interface. The registry deduplicates objects by provider, native type, and native ID. The normalized object rejects private visibility and excludes full bodies or private metadata.

The WordPress posts provider is a compatibility baseline only. File 21 must register the production provider through:

```php
do_action('sabri_public_experience/register_timeline_providers', $registry);
```

## Integration hooks

- `sabri_public_experience/dependency/*`
- `sabri_public_experience/profile_by_slug`
- `sabri_public_experience/public_profile_data`
- `sabri_public_experience/can_render_profile`
- `sabri_public_experience/can_show_contact`
- `sabri_public_experience/register_timeline_providers`
- `sabri_public_experience/provider_error`

## Safe failure

A missing required dependency prevents public overrides from booting. Safe Mode also returns control to the native theme and modules without deleting data.

## Existing-module compatibility detected in phase 25A

The foundation consumes the currently shipped module contracts without copying their data:

- File 00: `SMC_VERSION`, `smc_get_profile()`, `smc_is_founder()`, `smc_user_status()`.
- File 03: `SPD_VERSION` and `SPD_Helpers` compatibility reads.
- File 20: `SABRI_SHELL_VERSION` and `--sabri-shell-*` design tokens.
- File 21: `SABRI_HNF_VERSION`; its production provider remains a later explicit adapter.

The fallback WordPress-post provider is read-only and exists only so the foundation can be tested before the File 21 adapter is implemented.
