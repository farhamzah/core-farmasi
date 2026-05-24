# Core Profile Cutover Notes

## Current State
- Core owns canonical profile data.
- Core Profile Portal is available at `/profile`.
- KP and TU expose a safe browser link to Core Profile Portal when profile URL is configured.
- KP/TU remain read-only consumers for canonical profile data.
- Existing KP/TU local profile forms may still exist for legacy or operational fields.
- No SSO, no auto-login, no token URL, and no write-back are implemented.

## What Core Owns
- Official name and username.
- Identity type and identity number.
- NIM/NIDN/NIP/employee number.
- Student, lecturer, and employee profile.
- Phone/address safe contact fields.
- Study program and department association.
- Status/active flag.
- Roles and app access.
- Leadership and official position data.

## What KP/TU Can Keep Local
- Application workflow data.
- Requests, submissions, documents, validations, and approvals.
- KP-specific supervisor or external party fields.
- TU-specific service request fields.
- Local operational notes and status that are not canonical identity/master data.

## Future Cutover Steps
1. Inventory duplicate profile fields in KP and TU.
2. Classify each field as Core-owned, app-specific, or transitional.
3. Make Core-owned fields read-only in KP/TU views.
4. Keep app-specific fields editable locally.
5. Ensure KP/TU link to Core Profile Portal for Core-owned edits.
6. Run staging smoke test with real app client credentials.
7. Verify no token URL, no SSO, no write-back, and no secret exposure.
8. Communicate user guidance: profile utama diubah di Core, data operasional tetap di aplikasi.
9. Cut over only after owner approval and staging validation.

## Non-Goals For Current Stage
- No immediate cutover.
- No removal of legacy/local profile forms.
- No SSO.
- No cross-app session.
- No write-back from KP/TU to Core.
- No auth replacement.
- No database migration in KP/TU.

## Risks
- Users may need to login separately to Core because there is no SSO.
- Existing local profile forms can confuse users until field cutover is completed.
- Staging credential issuance and secret handling must be disciplined.
- Future cutover should be paired with user education and operational SOP.
