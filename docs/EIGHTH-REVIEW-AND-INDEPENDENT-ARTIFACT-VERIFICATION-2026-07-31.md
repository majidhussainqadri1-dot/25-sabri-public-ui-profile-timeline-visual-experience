# Eighth Review, Defect Correction, and Independent Artifact Verification — 2026-07-31

## Scope

This review was performed after the first deterministic File 25 `0.10.0` candidate was produced. The GitHub Actions artifact from run `#558` was downloaded independently and inspected outside the producing workflow.

The downloaded outer artifact had SHA-256:

```text
aa3fa3406c3bc06ccbe1c29c1e1356f1c63bc6fef13f3169206352355a7921ac
```

The inner plugin ZIP had SHA-256:

```text
f96a129172cbf06fa5dc17fde4ed411b0dbfacb01afe4a76f42ab7da16616c6d
```

The detached checksum matched, the embedded and detached manifests matched, all 54 payload hashes and byte sizes matched, the package root was singular, and the manifest commit was `17c0db174518ecbdf243b3f83d3d4cd91f8ab539`.

That verification proved the original candidate was internally consistent. It did not prove Hostinger staging or production acceptance.

## Confirmed defects

### No canonical independent verifier

The producing workflow validated its own output, but the repository did not contain a reusable verifier for a downloaded GitHub Actions artifact. A later reviewer had to reproduce the checks manually.

### Output-directory parent symlink risk

The builder restricted the output string to `build/...`, but it did not reject a symbolic `build` directory or a symbolic ancestor below it before recursive cleanup. A hostile or accidental symlink could redirect deletion or writes outside the repository build tree.

### Hard-coded versioned filenames in CI

The staging workflow named `sabri-public-experience-0.10.0.*` directly. A later version change would make the workflow fail or, if incompletely edited, compare or upload the wrong files.

### Candidate status wording was stale inside a completed artifact

The dependency matrix stored `candidate-package-pending-ci`. That text was included unchanged in the artifact even after CI had completed. A build-input matrix cannot truthfully know a later CI result, so staging and package states must remain non-acceptance states independent of workflow outcome.

### Downloaded-artifact bounds were not centrally enforced

There was no single verifier enforcing the exact outer file set, duplicate-name rejection, symbolic-link rejection, file-count limits, expanded-size limits, embedded dependency-matrix equality, and packaged runtime-version equality.

## Corrections

### Hardened deterministic builder

`tools/build-staging-package.php` now:

- resolves and validates the repository root;
- rejects a symbolic repository root, symbolic `build` directory, and symbolic output ancestors;
- refuses recursive removal outside the canonical repository `build/` tree;
- refuses to remove an output path that is a file or symbolic link;
- validates real payload paths remain below the repository root;
- limits payload file count, per-file size, and total expanded bytes;
- rejects duplicate ZIP entries and symbolic-link ZIP entries;
- retains deterministic timestamps, permissions, manifest hashes, and byte-for-byte reproducibility.

### Added independent artifact verifier

New tool:

```text
tools/verify-staging-artifact.php
```

It accepts either an assembled artifact directory or a downloaded GitHub Actions artifact ZIP. It verifies:

- optional expected outer artifact SHA-256;
- exact four-file artifact bundle;
- strict versioned filenames;
- detached inner-package checksum;
- detached manifest identity, commit, deterministic timestamp, hashes, and byte sizes;
- exact inner ZIP file set and single package root;
- duplicate, traversal, symbolic-link, file-count, per-file, and expanded-size protections;
- embedded and detached manifest equality;
- outer and embedded dependency-matrix equality;
- plugin header, runtime constant, WordPress stable tag, manifest version, and matrix version equality;
- Files 00/03/06/10/11/12/18/20/21/24/25 matrix presence;
- File 24 remains blocked pending exact contract review;
- artifact verification does not imply staging or production acceptance.

### Dynamic workflow versioning

The workflow now derives the version from the plugin header and uses it for all build, comparison, checksum, assembly, and upload paths. It no longer hard-codes one versioned filename.

The assembled directory is passed through the independent verifier before upload.

### Corrected dependency states

File 25 now records:

```text
staging_status = pending
package_status = build-input-not-acceptance
```

The matrix separately declares the verifier and explicitly states that artifact verification does not imply staging acceptance.

## Acceptance boundary

These corrections strengthen release engineering and downloaded-artifact integrity. They still do not perform or replace:

- exact multi-plugin Hostinger staging;
- File 24 runtime integration;
- real Founder, Doctor, Member, Knowledge, Media, Marketplace, and timeline workflows;
- RTL, accessibility, viewport, visual-regression, performance, migration, rollback, backup restoration, deployment, monitoring, or Founder sign-off.
