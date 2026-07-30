# File 25 — Complete Public UI, Profile Timeline and Visual Experience

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**.

## Current implementation status

Phases **25A — Governance and Contracts**, **25B — Plugin Foundation and Safety**, **25C — File 20 Design-System Integration**, and the first coding segment of **25D — Complete Founder and Professional Profiles** are now implemented on the draft pull-request branch.

Implemented in `0.2.0`:

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
- a read-only WordPress-post compatibility provider used only while File 21 is absent;
- responsive profile hero, initials fallback, accessible sharing, honest empty/error/partial/truncated states, and inherited File 20 design tokens;
- public profile and timeline REST endpoints with no-store responses and internal-identifier redaction;
- professional-only indexing defaults, privacy-safe ProfilePage metadata, and Person/Organization schema output;
- truthful Site Health diagnostics, timeline and profile-data contract tests, package verification, and PHP 8.0/8.3 CI.

## Architecture boundary

- **File 00** remains authoritative for identity, membership approval, age, public visibility, and professional eligibility.
- **File 03** remains the profile-master and public-contact-consent owner.
- **File 20** remains the global application-shell and base-design-token owner.
- **File 21** remains the Home/News/publication owner and must provide the production `file-21` timeline provider.
- **Files 22/23** own creation and private publishing operations.
- **File 24** remains the security, privacy, compliance, incident, and resilience owner.
- **File 25** owns public profile presentation, a rebuildable read projection of approved native contributions, reusable public components, and visual acceptance.

File 25 does not duplicate native content bodies, comments, reactions, identity evidence, patient information, messages, appointments, clinic records, or analytics ledgers.

## Review record

The source review and defect-correction record is documented in [`docs/REVIEW-AND-CORRECTION-2026-07-30.md`](docs/REVIEW-AND-CORRECTION-2026-07-30.md).

The 25C/25D coding record is documented in [`docs/PHASE-25C-25D-IMPLEMENTATION.md`](docs/PHASE-25C-25D-IMPLEMENTATION.md).

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
php tools/verify-structure.php
node --check assets/js/public.js
```

## Known production gaps

- File 21 has not registered a production-accepted `file-21` timeline provider.
- File 24 currently has no runtime contract available to File 25.
- Knowledge, Media, Reviews, Research, Marketplace, and other optional profile-section providers remain later phases.
- Fresh install, upgrade, migration, rollback, Hostinger staging, real-user workflows, responsive/accessibility evidence, performance measurement, release packaging, deployment, and post-deployment monitoring remain mandatory.

## Release policy

Code presence, a ZIP, or a green CI run is not production completion. File 25 remains a draft implementation until all staged Definition-of-Done gates are evidenced and accepted.
