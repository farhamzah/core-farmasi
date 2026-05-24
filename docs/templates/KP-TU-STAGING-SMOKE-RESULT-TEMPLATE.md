# KP/TU Staging Smoke Result Template

## Date / Time
- Date:
- Start time:
- End time:
- Timezone:

## Environment
- Core environment:
- KP environment:
- TU environment:
- Core base URL:
- KP base URL:
- TU base URL:

Do not include client secret, token, password, or full sensitive logs.

## Tester
- Name:
- Role:
- Approval context:

## Core Credential Metadata
- KP app_code:
- KP client_id:
- KP abilities:
- TU app_code:
- TU client_id:
- TU abilities:

Secret values must not be recorded.

## KP Result
- `php artisan kp:core-smoke-test` result:
- Users/students/lecturers/study programs:
- App access:
- Leadership:
- KP logs secret check:
- KP DB unchanged:
- Notes:

## TU Result
- `php artisan tu:core-smoke-test` result:
- Users/students/lecturers/employees/departments/study programs:
- Person search:
- App access:
- Leadership:
- TU logs secret check:
- TU DB unchanged:
- Notes:

## Profile Link Result
- KP profile link visible when configured:
- KP profile link target:
- TU profile link visible when configured:
- TU profile link target:
- Link contains no token/secret/user session data:
- Core login/profile behavior:

## Negative Test Result
- Invalid secret expected 401:
- Missing ability expected 403:
- Revoked/disabled client behavior:
- Rate limit behavior:
- Core unavailable fail-safe:

## API Log Check
- Core API request logs show KP requests:
- Core API request logs show TU requests:
- No request body/full headers/secret logged:
- `last_used_at` updated where expected:

## Secret Leak Check
- Core logs checked:
- KP logs checked:
- TU logs checked:
- Command output checked:
- Screenshots/evidence checked:

## Go / No-Go
- Decision:
- Blockers:
- Approver:
- Follow-up actions:

## Notes
-
