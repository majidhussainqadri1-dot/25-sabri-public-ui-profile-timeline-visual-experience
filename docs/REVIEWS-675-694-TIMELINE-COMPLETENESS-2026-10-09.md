# File 25 — Reviews 675–694 Timeline/Plan/Cross-File Completeness Audit — 2026-10-09

## Evidence boundary

This audit is repository-source evidence only. The frozen baseline was main SHA `e35563b7f3d8ebf0acbbc80982b7bcf2e1b78c0a`, whose exact-main GitHub Actions run `1689` completed successfully. The corrections in this audit are a later candidate and require their own exact-head CI after the final commit.

Nothing here establishes Hostinger staging acceptance, deployed package parity, live database/schema version, live migration state, production deployment, Founder acceptance or operational monitoring.

## Governing basis

The audit rechecked the File 25 Final Harmonized Specification 2.0, especially §43–46 Timeline Sorting/Filters/Pagination/Provider Contract, §68 SEO Presentation, §72 REST/API and the central ownership boundaries. The plan explicitly requires default reverse-chronological sorting, governed pinning, primary/secondary timeline filters, accessible pagination, URL-preserved state and browser-Back behavior.

## Twenty review scopes

| Review | Scope | Result |
|---:|---|---|
| 675 | Repository baseline / exact-main CI | Clean |
| 676 | File 25 plan / central ownership | Clean |
| 677 | Provider secondary filter | Defect corrected |
| 678 | Year filter | Defect corrected |
| 679 | Language filter | Defect corrected |
| 680 | Topic filter | Defect corrected |
| 681 | Oldest sorting | Defect corrected |
| 682 | Corrections primary filter | Defect corrected |
| 683 | Search/filter state preservation | Defect corrected |
| 684 | Filtered empty-state truth | Defect corrected |
| 685 | Screen-reader page announcement | Defect corrected |
| 686 | REST/HTML filter parity | Defect corrected |
| 687 | Secondary-filter noindex | Defect corrected |
| 688 | Explicit default-query noindex | Defect corrected |
| 689 | Pin audit provenance | Defect corrected |
| 690 | File 09 current-head drift | Defect corrected |
| 691 | File 14 current-head drift | Defect corrected |
| 692 | Files 03/08/17 boundaries | Clean |
| 693 | Files 20/21/22/23/24/26 boundaries | Clean |
| 694 | Richer timeline content owner contracts | Dependency-limited; no local patch authorized |

## Corrections

### Timeline filters and sorting

File 25 now implements bounded profile-local provider, year, language and topic filters plus latest/oldest sorting. The default remains latest/reverse chronological and pin precedence is preserved.

The **Corrections** primary filter is implemented as a File 25 projection over already-authorized normalized items whose correction state is `corrected` or `retracted`. File 25 does not require native owners to invent a fake `corrections` content type.

### State preservation and accessibility

Search and secondary-filter forms explicitly preserve compatible filter state and owner preview mode while resetting pagination. Filtered empty results no longer claim that a profile has no publications. Timeline rendering now emits an `aria-live` page/result announcement.

### REST and SEO parity

The public timeline REST endpoints now validate and forward provider/year/language/topic/sort/search in addition to page/per_page/content_type. HTML and REST therefore consume the same Timeline_Service behavior.

All timeline refinement URLs are noindex, including explicit default variants such as `?sort=latest`, preventing indexable duplicate filter URLs.

### Governed pinning

A nonzero pin weight can affect ordering only when bounded internal audit provenance is present:

- audit reference;
- actor ID;
- reason;
- surface;
- start timestamp;
- end timestamp.

Legacy or malformed pin claims fail closed to pin weight `0` while the otherwise-valid public item remains readable. This preserves provider failure isolation without allowing unaudited ordering authority.

### Companion drift

File 09 current repository main advanced to `cfc5f781a766330314dc98c42abeca0eb7786eba`; runtime `1.3.0`, schema `6`, integration contract `1.1.0` remain compatible.

File 14 current repository main advanced to `080e2198d84dfb7491bb0b75946e14a5fe118b91`; runtime `1.4.8`, schema `10005` remain inside File 25's reviewed `>=1.4.3 <1.5.0` family.

## Deliberate owner-contract limitation

The File 25 plan names richer timeline types such as Knowledge, Lessons, Research, successful cases, Videos, Reels, PDFs and Clinic Updates. Current File 21 `ProfileTimeline::query` is an owner-authorized WordPress-post timeline. File 25 also has read-only section-card adapters for several other modules, but those section-card adapters are not authoritative timeline contracts.

Therefore File 25 does **not** promote section cards into timeline items merely to satisfy a label list. Richer timeline types require their canonical owners to publish authoritative timeline-provider contracts or owner-authorized timeline projections. This is a cross-file contract dependency, not permission to infer foreign publication approval.

## Result

All File 25-owned source defects discovered in Reviews 675–694 were corrected. The remaining richer-content limitation is explicitly cross-file/owner-contract dependent. External staging/live acceptance remains pending.
