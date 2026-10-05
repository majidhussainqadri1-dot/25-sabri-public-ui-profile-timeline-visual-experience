# File 25 Rollback Guide

## Rollback boundary

Rollback restores File 25-owned route aliases, preferences, rebuildable projection state and the prior approved plugin package. It must not roll back or rewrite another module's canonical data.

## Minimum rollback proof

Before production approval, staging must prove: verified backup/restore, reversible File 25 route migration, previous package reactivation, rewrite-rule recovery, Safe Mode recovery surface, preservation of File 00/03/08/09/21/24 owner truth, and post-rollback smoke tests.

High-risk rollback requires explicit authorization and must fail closed when approval is absent. Safe Mode may keep canonical profile routes recoverable through the data-free native-theme 503 surface while optional providers and File 25 writes remain disabled.

**Current status:** rollback source controls prepared; staging rehearsal and live rollback evidence pending.
