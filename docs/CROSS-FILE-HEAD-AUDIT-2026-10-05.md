# File 25 Current Cross-File Repository-Head Audit — 2026-10-05 (revalidated 2026-10-06)

Baseline File 25 main SHA: `59927df876dc92c7461351420c7b7c95c65c6a93`.

This audit records **repository source truth only**. It does not prove which packages are installed on staging or production.

| File | Current observed repository HEAD | Current observed contract/version relevant to File 25 | Result |
|---|---|---|---|
| 00 | `2fa7c022ee9cd1b65432e900579512f304532442` | runtime 1.2.44, DB 1.4.5, membership contract 1.2.3 | File 25 drift found and corrected |
| 03 | `636e3ef965423887f810718abec3cd1c11c3659d` | 1.2.0-rc18, contract 1.4.0; WP 7.0/PHP 8.1 | compatible; integrated minima refreshed |
| 07 | `67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844` | directory/discovery owner | boundary preserved |
| 08 | `70541974ce0ffb16aebef557c3016eb7447662f4` | 1.2.15, schema 3.4.0, public clinic projection 1.1.0 | File 25 drift found and corrected |
| 09 | `9639f75ba046ac1a36e39d5e9aae56c7bae3279b` | 1.3.0, schema 6, integration contract 1.1.0 | compatible |
| 14 | `db60c4bc5c37a5c88126b78c31b34c75236f33d7` | 1.4.4, base schema 10005 | boundary preserved |
| 20 | `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` | 1.4.17, central-plan contract 1.0.0 | compatible |
| 21 | `f2eb7e95ddea327af36ea725ffb923b029f885e6` | package 1.0.5, runtime 1.0.3, schema 1.0.0 | compatible |
| 22 | `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a` | 1.0.0-rc.3 | destination boundary preserved |
| 23 | `dcae138e6073f4d0ff596623deb05b9940b8271b` | 1.2.0; Future Publishing Intelligence merged to main at source level | destination boundary preserved; staging/live unverified |
| 24 | `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` | 0.99.0 | compatible |
| 26 | `bbea3aad466792a4a6a62b53532bbd45c7c592de` | search/discovery owner | File 25 does not acquire global search/ranking truth |

Other observed current repository heads are recorded machine-readably in `config/staging-dependencies.json`.

## Corrections opened by this audit

Reviews 595–614 identified six defect rounds: 596, 599, 610, 611, 613 and 614. The code corrections address current File 00/File 08 contract drift, restore fail-closed high-risk authorization, add a data-free recoverable Safe Mode public profile surface, restore release-documentation contracts, and distinguish historical reviewed dependency pins from current repository-head observations.

Exact deployed code, deployed DB version, live migration state and live verification remain unverified.

## Post-review parity note — 2026-10-06

A fresh repository check found that File 09 and File 23 advanced after the original dated observation, and that File 25 profile action slots still lacked owner-native route resolution. Those source defects were corrected in the post-614 reconciliation; no staging/live/deployed truth is claimed.
