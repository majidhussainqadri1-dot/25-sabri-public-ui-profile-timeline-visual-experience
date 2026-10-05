# File 25 Migration Guide

## Governing rule

File 25 migrations may change only File 25-owned preferences, rebuildable projections, route aliases and schema state. They must never mutate File 00/03/07/08/09/14/18/20/21/22/23/24 native truth directly.

## Required sequence

1. Freeze the exact source/package candidate and dependency matrix.
2. Take a verified staging backup.
3. Run the File 25 migration dry-run and inspect collisions/invalid mappings.
4. Require explicit high-risk authorization; capability alone is insufficient.
5. Execute only the approved redirect/projection migration.
6. Reconcile routes and rebuild File 25 derivative indexes where needed.
7. Verify canonical profile routes, no redirect loops, privacy boundaries and owner contracts.
8. Preserve rollback data until acceptance is signed.

The dry-run checksum and approved map must match before execution. A changed map requires a new dry-run. Migration success in source tests is not live schema evidence.

**Current status:** migration source path prepared; exact deployed DB/schema and live migration state unverified.
