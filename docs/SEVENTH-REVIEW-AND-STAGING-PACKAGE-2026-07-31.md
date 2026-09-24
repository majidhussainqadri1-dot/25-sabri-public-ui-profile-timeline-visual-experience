# Seventh Review, Defect Correction, and Staging Package Foundation — 2026-07-31

## Scope

This review was performed after File 25 version 0.9.0 and before beginning the staging-release segment.

The objective was not to declare staging completion. The objective was to make the source candidate reproducibly packageable, cryptographically inspectable, and explicitly bound to the reviewed dependency matrix.

## Confirmed defects and corrections

### Artifact references were too permissive

The visual-acceptance validator rejected executable schemes but still allowed arbitrary absolute or external references. A manifest could therefore point to uncontrolled evidence locations.

Correction:

- evidence references must begin with `artifacts/`;
- absolute and external URLs are rejected;
- traversal segments, backslashes, colons, queries, fragments, whitespace, control characters, empty segments, and dot segments are rejected;
- references are bounded to 512 bytes.

### Evidence checksums lacked file-shape metadata

A SHA-256 value alone did not state the expected artifact size or media type.

Correction:

- every evidence record now requires `byte_size`;
- every evidence record now requires an allow-listed `media_type`;
- accepted sizes are bounded from 1 byte to 1 GiB;
- Founder sign-off evidence now requires its own SHA-256, byte size, and media type.

### No canonical reproducible staging package existed

The branch had source tests but no deterministic builder, manifest, detached checksum, or archive verification process.

Correction:

- added `tools/build-staging-package.php`;
- versions are reconciled from the plugin header, runtime constant, and WordPress stable tag;
- runtime files are selected through a closed payload policy;
- development-only directories and symlinks are excluded;
- copied files and ZIP entries use one supplied source timestamp;
- payload files are sorted before archive creation;
- the builder emits an embedded manifest, detached manifest, and detached SHA-256 checksum;
- the ZIP is reopened and fully verified before success.

### No machine-readable exact staging matrix existed

Narrative documentation listed remaining modules, but automation and reviewers had no bounded structured matrix.

Correction:

- added `config/staging-dependencies.json`;
- known reviewed package/source expectations are recorded without inventing unavailable contracts;
- File 24 remains explicitly pending and blocked until its runtime contract is reviewed;
- every module remains staging-pending until real integration evidence exists.

### CI did not prove package reproducibility

A package could theoretically differ across two builds from the same source.

Correction:

- CI now builds the package twice from the exact source candidate and commit timestamp;
- ZIP bytes, detached manifests, and detached checksum files must match exactly;
- the detached checksum is verified;
- only then is the staging candidate uploaded as a temporary workflow artifact.

## Runtime and contract changes

- File 25 runtime: `0.10.0`
- Design-system contract: `1.5.0`
- Visual-acceptance contract: `1.3.0`
- Staging-package contract: `1.0.0`

## New artifacts in source

- `tools/build-staging-package.php`
- `config/staging-dependencies.json`
- `tests/release-engineering.php`
- `docs/STAGING-PACKAGE-CONTRACT.md`
- `docs/SEVENTH-REVIEW-AND-STAGING-PACKAGE-2026-07-31.md`

## Security and ownership boundaries

The staging package contains File 25 only. It does not embed Files 00, 03, 06, 10, 11, 12, 18, 20, 21, or 24.

The dependency matrix records integration requirements but does not transfer native data, permissions, moderation, media, metrics, transactions, security ownership, or application-shell ownership to File 25.

## Remaining acceptance gates

- successful CI on PHP 8.0, PHP 8.3, JavaScript, and deterministic packaging;
- download and independent checksum verification of the workflow artifact;
- exact multi-plugin installation on Hostinger staging;
- real Founder, Doctor, Member, Knowledge, Media, Marketplace, timeline, and negative-state tests;
- File 24 runtime review and integration;
- captured responsive, RTL, accessibility, forced-colors, reduced-motion, and performance evidence;
- fresh install, upgrade, migration, rollback, and backup restoration;
- explicit Founder acceptance against the exact tested commit;
- merge and production deployment only after all gates pass.
