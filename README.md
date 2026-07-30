# File 25 — Complete Public UI, Profile Timeline and Visual Experience

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**.

## Current implementation status

Coding has begun on phases **25A (Governance and Contracts)** and **25B (Plugin Foundation and Safety)**.

Implemented in `0.1.0`:

- activation-safe WordPress bootstrap;
- graded dependency and fail-closed runtime checks;
- privacy-first Founder, verified-doctor, and permitted-member profile projection;
- canonical `/founder/`, `/doctors/{slug}/`, and `/profile/{slug}/` routes;
- federated read-only timeline provider interface, registry, service, and normalized object contract;
- read-only WordPress posts compatibility provider;
- responsive profile hero, tabs, timeline cards, public empty/error states, and visible keyboard focus;
- public profile and timeline REST endpoints;
- Site Health diagnostics, contract tests, package verification, and CI.

## Architecture boundary

- **File 20** remains the global application-shell owner.
- **File 21** remains the Home/News publication owner and will provide the production timeline adapter.
- **Files 22/23** own creation and private publishing operations.
- **File 24** remains the security, privacy, compliance, and resilience owner.
- **File 25** owns public profile presentation, timeline projection, reusable public components, and visual acceptance.

File 25 does not duplicate native content bodies, comments, reactions, identity evidence, patient information, messages, appointments, or analytics ledgers.

## Governing documentation

See [`docs/GOVERNING-SCOPE.md`](docs/GOVERNING-SCOPE.md), [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), and [`docs/DECISION-LOG.md`](docs/DECISION-LOG.md). The Founder-approved Microsoft Word specification remains the full project-governance source for File 25.

## Quality commands

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/run.php
php tools/verify-structure.php
node --check assets/js/public.js
```

## Release policy

Code presence, a ZIP, or a green CI run is not production completion. Fresh install, upgrade, migration, rollback, staging acceptance, responsive/accessibility evidence, real-user workflows, deployment, and post-deployment monitoring remain mandatory.
