# File 25 — Review 21 / Correction 21

Date: 2026-08-03

## Review focus

File 03-owned profile and cover media projection.

## Confirmed defects

1. File 25 converted `_spd_profile_photo_id` and `_spd_cover_photo_id` directly into public URLs.
2. It did not independently require the File 03 media-owner marker.
3. It did not require the correct `profile` or `cover` purpose marker.
4. It did not require attachment type, genuine image state, allowed MIME, valid dimensions, or the File 03 forty-million-pixel ceiling before public projection.
5. A same-site URL alone was therefore insufficient proof that media belonged to the public profile.

## Corrections

- required File 03 media owner `_spd_media_owner_user_id` to match the profile user;
- required `_spd_media_purpose` to match `profile` or `cover`;
- required WordPress attachment type and genuine image state;
- allowed only JPEG, PNG, and WebP;
- required positive dimensions not exceeding forty million pixels;
- retained strict same-site delivery;
- failed closed on every ownership, purpose, type, MIME, dimension, metadata, or URL mismatch.

## Acceptance boundary

This is a source and regression correction. Real WordPress media replacement, erasure, cache, CDN, and Hostinger staging evidence remain mandatory.
