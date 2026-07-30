# File 25 — Complete Public UI, Profile Timeline and Visual Experience

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**.

## Current implementation status

Phases **25A — Governance and Contracts**, **25B — Plugin Foundation and Safety**, **25C — File 20 Design-System Integration**, the first coding segment of **25D — Complete Founder and Professional Profiles**, and the source-contract portion of **25F/25G — Timeline Provider Integration** are now implemented on the draft pull-request branch.

Implemented in `0.3.0`:

- early PHP guard, duplicate-copy protection, activation boundaries, and non-destructive Safe Mode;
- File 00-authoritative identity, age, role, visibility, and professional-eligibility checks;
- privacy-first Founder, verified-Doctor, and permitted-member profile projection;
- canonical `/founder/`, `/doctors/{slug}/`, and `/profile/{slug}/` routes with role/slug canonicalization;
- truthful section navigation: only currently renderable tabs are exposed;
- File 20 integration through its real `sabri_shell_layout_mode` filter and shell custom properties, without creating a second shell;
- canonical author-link replacement for approved public profiles and scoped shell/profile body classes;
- structured Founder Overview, Books and Research, About, and Clinic and Contact sections;
- structured verified-Doctor professional Overview, About, and Clinic sections;
- a strict public-data allow-list for Founder, professional, and clinic information;
- federated read-only timeline provider interface, bounded registry, public-safe normalized contract, same-site canonical validation, deduplication, and global pagination;
- a native read-only File 21 adapter over `Sabri\HomeNewsFeed\ProfileTimeline::query()`;
- bounded File 21 multi-page retrieval with a stable native page size, local normalization, identifier redaction, and no write authority;
- a read-only WordPress-post compatibility provider used only while File 21 is absent, never while File 21 is active but incompatible;
- responsive profile hero, initials fallback, accessible sharing, honest empty/error/partial/truncated states, and inherited File 20 design tokens;
- public profile and timeline REST endpoints with no-store responses and internal-identifier redaction;
- professional-only indexing defaults, privacy-safe ProfilePage metadata, and Person/Organization schema output;
- truthful Site Health diagnostics, timeline, File 21 adapter, and profile-data contract tests, package verification, and PHP 8.0/8.3 CI.

## Architecture boundary

- **File 00** remains authoritative for identity, membership approval, age, public visibility, and professional eligibility.
- **File 03** remains the profile-master and public-contact-consent owner.
- **File 20** remains the global application-shell and base-design-token owner.
- **File 21** remains the Home/News/publication owner. File 25 consumes File 21's visibility-safe public Timeline query only as a read-only adapter.
- **Files 22/23** own creation and private publishing operations.
- **File 24** remains the security, privacy, compliance, incident, and resilience owner.
- **File 25** owns public profile presentation, a rebuildable read projection of approved native contributions, reusable public components, and visual acceptance.

File 25 does not duplicate native content bodies, comments, reactions, identity evidence, patient information, messages, appointments, clinic records, or analytics ledgers.

## Review record

The source review and defect-correction record is documented in [`docs/REVIEW-AND-CORRECTION-2026-07-30.md`](docs/REVIEW-AND-CORRECTION-2026-07-30.md).

The 25C/25D coding record is documented in [`docs/PHASE-25C-25D-IMPLEMENTATION.md`](docs/PHASE-25C-25D-IMPLEMENTATION.md).

The File 21 adapter record is documented in [`docs/FILE21-TIMELINE-ADAPTER-IMPLEMENTATION.md`](docs/FILE21-TIMELINE-ADAPTER-IMPLEMENTATION.md).

Additional governing documents:

- [`docs/GOVERNING-SCOPE.md`](docs/GOVERNING-SCOPE.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DECISION-LOG.md`](docs/DECISION-LOG.md)

The Founder-approved Microsoft Word specification remains the full project-governance source for File 25.

## Quality commands

```bash
composer validate --strict --no-check-publish
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/run.php
php tests/profile-data.php
php tests/file21-provider.php
php tools/verify-structure.php
node --check assets/js/public.js
```

## Known production gaps

- The File 21 adapter is source-contract complete but remains `read-only`; exact Files 00/03/20/21/25 staging is required before any maturity promotion.
- File 24 currently has no runtime contract available to File 25.
- Knowledge, Media, Reviews, Research, Marketplace, and other optional profile-section providers remain later phases.
- Fresh install, upgrade, migration, rollback, Hostinger staging, real-user workflows, responsive/accessibility evidence, performance measurement, release packaging, deployment, and post-deployment monitoring remain mandatory.

## Release policy

Code presence, a ZIP, or a green CI run is not production completion. File 25 remains a draft implementation until all staged Definition-of-Done gates are evidenced and accepted.
