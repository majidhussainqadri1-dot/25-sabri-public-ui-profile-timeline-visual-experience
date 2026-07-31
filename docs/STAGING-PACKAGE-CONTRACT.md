# File 25 Deterministic Staging Package Contract

## Purpose

This contract creates a reproducible File 25 staging candidate without implying staging or production acceptance.

## Canonical builder

```bash
php tools/build-staging-package.php \
  --output-dir=build/staging-package \
  --commit=<40-character-commit-sha> \
  --source-date-epoch=<commit-unix-timestamp>
```

The output directory must remain below `build/`. The builder rejects absolute paths, traversal attempts, malformed commit identifiers, unsupported timestamps, missing version sources, symlinks, and missing required runtime files.

## Version authority

The following values must match before a package can be created:

- WordPress plugin header version;
- `SABRI_PUBLIC_EXPERIENCE_VERSION`;
- WordPress `Stable tag`.

## Runtime payload

The staging payload may contain only approved runtime and public-governance files:

- plugin bootstrap and uninstall file;
- WordPress readme and bounded release/security/privacy records;
- `assets/`;
- `config/`;
- `includes/`;
- `languages/` when present;
- `templates/`.

The package excludes development-only paths including:

- `.git` and `.github`;
- `build/` and `coverage/`;
- `docs/`;
- `tests/` and `tools/`;
- `vendor/` and `node_modules/`.

## Determinism

The builder uses one supplied source timestamp for copied files, the embedded manifest, detached manifest, checksum file, and ZIP entries. Payload paths are sorted before archive insertion.

CI builds the package twice from the same source commit and source timestamp. The ZIP, detached manifest, and checksum file must be byte-for-byte identical.

## Integrity outputs

The package set contains:

```text
sabri-public-experience-<version>.zip
sabri-public-experience-<version>.sha256
sabri-public-experience-<version>-manifest.json
```

The ZIP contains:

```text
sabri-public-experience/STAGING-MANIFEST.json
```

The manifest records:

- schema version;
- canonical package name;
- File number;
- runtime version;
- exact source commit;
- deterministic source timestamp;
- each payload path;
- each payload SHA-256;
- each payload byte size.

## Archive verification

Before success, the builder reopens the ZIP and verifies:

- every archive path begins with the canonical package root;
- no entry contains traversal or backslash authority escapes;
- actual entries exactly equal expected manifest entries;
- every payload SHA-256 matches;
- every payload byte size matches;
- embedded and detached manifests are identical.

## Dependency matrix

`config/staging-dependencies.json` records the exact source/package expectations available for Files 00, 03, 06, 10, 11, 12, 18, 20, 21, 24, and 25.

The matrix may record a dependency as pending or blocked. It must never fabricate an unavailable package, runtime contract, staging result, or production acceptance.

## Workflow artifact

CI may upload the verified staging candidate as a temporary workflow artifact containing:

- ZIP;
- detached checksum;
- detached package manifest;
- staging dependency matrix.

A workflow artifact is not a release, merge, deployment, or Founder acceptance.

## Acceptance boundary

The following remain mandatory after package generation:

- checksum verification outside the build job;
- exact multi-plugin staging installation;
- File 24 security/privacy/cache integration;
- real-role and negative-path testing;
- responsive, RTL, accessibility, and visual-regression evidence;
- performance, upgrade, migration, rollback, and backup restoration;
- explicit Founder sign-off bound to the exact tested commit.
