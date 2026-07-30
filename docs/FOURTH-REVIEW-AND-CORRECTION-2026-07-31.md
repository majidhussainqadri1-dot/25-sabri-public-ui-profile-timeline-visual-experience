# Fourth Review and Correction — 2026-07-31

## Reviewed baseline

File 25 runtime `0.5.0`, including the global visual system, reusable states, content cards, public profiles, federated timeline, File 20 token inheritance, and the File 21 read-only adapter.

## Confirmed defects

1. The component contract did not enumerate all implemented form primitives and supplied no canonical notice renderer.
2. Optional Knowledge, Media, Reviews, Research, and Marketplace labels existed in profile planning, but there was no governed provider registry capable of proving content before exposing a tab.
3. A native module could otherwise rely on an unbounded or arbitrary integration hook and introduce dead sections or raw HTML.
4. Optional provider maturity and native-ownership claims needed validation both at registration and again at runtime because a stateful provider could change after registration.
5. Provider exceptions needed isolation without exposing provider identifiers or diagnostics to public output.
6. The package and CI matrix had no executable optional-section contract tests.
7. Site Health did not report whether optional profile-section providers were absent, healthy, or failing.

## Corrections

- Completed the reusable component class map and added the escaped same-origin `render_notice()` contract.
- Added `Profile_Section_Provider`, `Section_Registry`, and `Section_Service`.
- Restricted optional sections to the closed allow list: Knowledge, Media, Reviews, Research, and Marketplace.
- Required semantic provider versions, bounded unique IDs, approved maturity, availability, profile support, and denial of native ownership.
- Revalidated maturity and ownership at query time.
- Limited one provider to 24 candidates and one section to 48 rendered cards.
- Routed all provider descriptors through the canonical File 25 `Content_Cards` renderer; provider HTML is never accepted.
- Suppressed duplicates and hid sections without accepted public cards.
- Isolated provider failures, emitted bounded internal events, and exposed only a public error count.
- Added the provider-section template, honest partial/truncated states, Site Health diagnostics, documentation, package rules, Composer coverage, and PHP 8.0/8.3 CI coverage.

## Architecture preserved

- File 20 remains the only shell owner.
- Native modules remain canonical owners of content, permissions, moderation, reviews, media, Marketplace transactions, and records.
- File 25 remains a read-only visual projection and does not create a second data store or publication backend.
- Private profiles remain fail-closed before any optional provider is queried for rendering.

## Completion boundary

This source phase does not claim native Knowledge, Media, Reviews, Research, or Marketplace provider completion. Each native adapter still requires exact source-contract review, staging evidence, privacy validation, responsive/accessibility testing, rollback evidence, and Founder acceptance.
