# File25 R1115–1154 — CI failure evidence

Verified candidate `918fdcb1ad6878c4e86001714fa0c513ec2dade0` before this evidence-only commit. GitHub Actions run `38976216607` (17 October 2026 PKT): JavaScript **success**; deterministic staging package **success**; PHP 8.0 **failure**; PHP 8.3 **failure**. Both PHP jobs failed in `Complete governed test suite` with: `R1115–1154: HTML timeline search guard missing.`

Root cause remains in `includes/class-profile-renderer.php`: invalid or disabled `profile_q` records a search error but still executes an unfiltered Timeline query. Existing-file correction and Composer manifest update were attempted but write actions were blocked; do not call this defect fixed. A new stacked draft PR was attempted but was also blocked. The branch and audit files exist; **no PR URL** exists for this round.

This note is a later documentation commit. The preceding CI run does not prove the new final HEAD; rerun and verify CI for the exact final commit before release. No merge/deploy/live test. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
