# File 25 — Review 20 / Correction 20

Date: 2026-08-03

## Review focus

Authoritative File 08 clinic projection, File 03 profile facade, File 09 professional snapshot, and File 18 marketplace owner API.

## Confirmed defects

1. File 25 still accepted File 08 `0.2.0`, even though the reviewed owner contract exists only in `0.2.1`.
2. File 08 availability accepted a legacy helper without contract introspection.
3. The clinic adapter did not verify contract owner, exact public fields, required exclusions, or `writes_data=false`.
4. File 03 availability did not verify its reviewed `0.2.x` facade and required public methods.
5. The File 09 snapshot could still become a fallback source for general identity, location, and biography fields.
6. Marketplace availability still admitted legacy native-class fallbacks instead of the owner public DTO API.

## Corrections

- required File 08 `>=0.2.1 <0.3.0` and exact contract `1.0.0`;
- required `SWC_PUBLIC_CLINIC_CONTRACT_VERSION`, `swc_get_public_clinic_projection()`, and `swc_public_clinic_projection_contract()`;
- required owner `file-08`, exact public fields, required excluded private fields, and `writes_data=false`;
- removed the legacy File 08 helper fallback;
- required File 03 `>=0.2.0 <0.3.0` and its reviewed public facade;
- restricted File 09 fallback data to approved professional fields only;
- required File 18's owner-executed public listing API.

## Acceptance boundary

These are source-contract corrections. Hostinger staging, real cross-plugin execution, production approval, merge, and live deployment remain pending.
