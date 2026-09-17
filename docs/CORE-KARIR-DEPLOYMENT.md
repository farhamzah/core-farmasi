# Deployment Core - Alumni Farmasi

## Target and scope

- Core repository: `/var/www/core-farmasi`.
- Core URL: `https://core.safaubp.com`.
- Alumni repository: `/var/www/alumni-farmasi`.
- Alumni URL: `https://alumni.safaubp.com`.
- Branch: `codex/core-karir-alumni`, starting from reviewed commit `3502906` plus the review fixes on that branch.
- Contract app code: `karir-farmasi`.
- Core stores minimum alumni identity, registration, grants, subjects and application access. CV, profiles, jobs and all professional application data stay in the separate Alumni database.

## Review and verification

The review fixes close three issues: cross-application clients reaching Karir endpoints, access decisions ignoring effective dates or disabled catalog roles, and rejection of a new registration revoking pre-existing candidate access.

Local verification used SQLite in memory, not production data:

- Final full Core regression, including the isolated Karir seeder and secure client issuance: 396 tests, 2,405 assertions.
- Final Alumni and client issuance tests: 26 tests, 128 assertions.
- Forward migrations and seeder idempotency are exercised by these tests. Production MySQL migrations have NOT been executed by the assistant.
- Production Core health responded with `{"status":"ok"}`. SSH authentication was refused, so backup, production IDs, deployment and client provisioning remain operator steps.

## 1. Back up Core before changing the checkout or schema

Run as root in the VPS shell. This reads the current Core connection privately; database credentials only go to a temporary file with restrictive permissions, never to terminal output. It does not back up or migrate the Alumni database.

```bash
bash <<'BASH'
set -euo pipefail
cd /var/www/core-farmasi
test -z "$(git status --porcelain)" || { echo 'STOP: Core checkout contains local changes.'; exit 1; }
umask 077
mkdir -p /var/backups/core-farmasi
chmod 700 /var/backups/core-farmasi
backup="/var/backups/core-farmasi/before-karir-$(date +%Y%m%d-%H%M%S).sql"
defaults=$(mktemp)
trap 'rm -f "$defaults"' EXIT
export CORE_BACKUP_DEFAULTS="$defaults"
database=$(php <<'PHP'
<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = Illuminate\Support\Facades\DB::connection();
$cfg = $connection->getConfig();
if (! in_array($cfg['driver'], ['mysql', 'mariadb'], true)) {
    fwrite(STDERR, "STOP: expected a MySQL/MariaDB Core connection.\n"); exit(1);
}
$quote = static fn ($v) => '"'.str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], (string) $v).'"';
$options = ['user' => $cfg['username'], 'password' => $cfg['password'] ?? ''];
if (! empty($cfg['unix_socket'])) {
    $options['socket'] = $cfg['unix_socket'];
} else {
    $options['host'] = $cfg['host'];
    $options['port'] = $cfg['port'] ?? 3306;
}
$contents = "[client]\n";
foreach ($options as $key => $value) {
    $contents .= $key.'='.$quote($value)."\n";
}
if (file_put_contents(getenv('CORE_BACKUP_DEFAULTS'), $contents) !== strlen($contents)) { exit(1); }
echo $connection->getDatabaseName();
PHP
)
mysqldump --defaults-extra-file="$defaults" --single-transaction --quick \
  --routines --triggers --events --no-tablespaces --databases "$database" > "$backup"
test -s "$backup"
sha256sum "$backup" > "$backup.sha256"
git rev-parse HEAD > "$backup.commit"
echo "Backup created: $backup"
BASH
```

Stop if backup fails. A nonempty successful dump and checksum are not a restore test; validate restoration on a separate disposable database under the site's backup procedure, never by overwriting Core. Do not paste the dump or credentials into chat. Connections requiring custom database TLS settings need the corresponding mysqldump options before running this block.

## 2. Deploy the reviewed branch and apply only its additive migrations

Proceed only after the backup step has succeeded. Do not discard local changes, force-push, run migrate:fresh, or roll back existing production migrations.

```bash
bash <<'BASH'
set -euo pipefail
cd /var/www/core-farmasi
test -z "$(git status --porcelain)" || { echo 'STOP: checkout contains local changes.'; exit 1; }
git fetch origin
git switch codex/core-karir-alumni
git merge --ff-only origin/codex/core-karir-alumni
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
runuser -u www-data -- php artisan optimize:clear
runuser -u www-data -- php artisan migrate --force \
  --path=database/migrations/2026_09_11_000001_create_karir_identity_tables.php \
  --path=database/migrations/2026_09_11_000002_add_operational_review_fields_to_career_alumni_registrations.php \
  --path=database/migrations/2026_09_17_062706_create_alumnis_table.php
runuser -u www-data -- php artisan db:seed --class=KarirApplicationSeeder --force
runuser -u www-data -- php artisan optimize:clear
php artisan route:list --path=api/v1/internal/apps/karir-farmasi
php artisan tinker --execute='dump(App\Models\StudyProgram::query()->orderBy("id")->get(["id", "code", "name", "active"])->toArray());'
BASH
```

MySQL DDL is not fully transactional. If any migration fails, stop and inspect migration status; do not retry with table deletion. The production deployment uses the dedicated Karir seeder so existing application catalog rows are not reapplied.

## 3. Match the actual production program IDs and codes

The last command prints actual production program records. Select active S1 Farmasi and PSPPA / Profesi Apoteker. Do not guess IDs from a local database.

Set in Core `.env` (the program codes must match the query output):

```dotenv
CORE_KARIR_ISSUER=https://core.safaubp.com
CORE_KARIR_PHARMACY_PROGRAM_CODES=ACTUAL_S1_CODE,ACTUAL_PSPPA_CODE
KARIR_FARMASI_BASE_URL=https://alumni.safaubp.com
KARIR_FARMASI_ADMIN_URL=https://alumni.safaubp.com
```

Set these nonsecret values in `/var/www/alumni-farmasi/.env`, replacing the ID placeholders. Its `DB_DATABASE` must already refer to its own application database, never the Core database.

```dotenv
CORE_IDENTITY_DRIVER=http
CORE_IDENTITY_HTTP_ENABLED=true
CORE_IDENTITY_VERIFY_URL=https://core.safaubp.com/api/v1/internal/apps/karir-farmasi/identity/verify
CORE_DIRECTORY_BASE_URL=https://core.safaubp.com/api/v1/internal/apps/karir-farmasi/directory
CORE_ALUMNI_BASE_URL=https://core.safaubp.com/api/v1/internal/apps/karir-farmasi
CORE_PASSWORD_RECOVERY_URL=https://core.safaubp.com/profile/forgot-password
CORE_IDENTITY_ALLOWED_PROGRAM_IDS=ACTUAL_S1_ID,ACTUAL_PSPPA_ID
```

## 4. Issue and deliver the client secret without displaying it

```bash
cd /var/www/core-farmasi
runuser -u www-data -- php artisan optimize:clear
php artisan core:issue-karir-api-client --env-path=/var/www/alumni-farmasi/.env
php artisan core:issue-karir-api-client --env-path=/var/www/alumni-farmasi/.env --apply
cd /var/www/alumni-farmasi
runuser -u www-data -- php artisan config:clear
```

The first issuance creates a dedicated client with exactly these abilities and writes its ID and secret directly to the existing Alumni `.env`, preserving other settings and file ownership. Do not print that file, use config:show on credential configuration, or paste secrets into chat.

- `verify:karir-identity`
- `create:karir-alumni-registration`
- `read:karir-alumni-registrations`
- `read:karir-alumni-registration-status`
- `approve:karir-alumni-registration`
- `reject:karir-alumni-registration`
- `read:karir-person`
- `read:karir-study-program`

Re-running with matching credentials is a no-op. Existing mismatched, revoked, missing or broader credentials cause refusal; there is no automatic rotation or duplicate issuance. Keep the deployment window exclusive while the command writes `.env`.

## 5. Production verification

Check health, the dedicated client, four catalog roles, and minimum directory access. Example safe Core queries contain no secret:

```bash
cd /var/www/core-farmasi
curl -fsS --connect-timeout 5 --max-time 15 https://core.safaubp.com/api/v1/health
php artisan tinker --execute='dump(App\Models\CoreApiClient::query()->where("app_code", "karir-farmasi")->get(["app_code", "client_id", "abilities", "is_active"])->toArray()); dump(App\Models\CoreApplicationRole::query()->where("app_code", "karir-farmasi")->where("is_active", true)->pluck("role_slug")->all());'
```

Test Alumni login using an authorized user, including access restrictions for unauthorized users. A client alone does not grant user access; administrator assignment and Farmasi eligibility remain required. Do not create an alumni grant merely to bypass eligibility. Use the Alumni application's own migration/deployment procedure only against its separate database. This Core runbook does not deploy that database or establish that the whole Alumni product is production-ready.
