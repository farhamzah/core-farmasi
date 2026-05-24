# Core - Lab Access Assignment Dry Run Report

## Ringkasan

Core menambahkan command dry run untuk assignment app access Lab Farmasi. Command ini eksplisit untuk satu user dan satu role, default tidak menulis data, dan mendukung apply/revoke hanya dengan flag.

## Command

```bash
php artisan core:lab-access-dry-run {core_user_id} {role}
php artisan core:lab-access-dry-run {core_user_id} {role} --apply
php artisan core:lab-access-dry-run {core_user_id} {role} --revoke
```

## Guardrails

- Satu user dan satu role per command.
- Default dry run.
- Tidak membuat user.
- Tidak mengubah password.
- Tidak mass grant.
- Tidak SSO.
- Tidak token URL.
- Tidak menampilkan secret.

## Rollback

Gunakan `--revoke` untuk menonaktifkan satu akses Lab tanpa menghapus user atau data.
