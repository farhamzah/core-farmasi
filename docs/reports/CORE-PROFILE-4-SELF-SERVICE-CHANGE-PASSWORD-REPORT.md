# CORE-PROFILE-4 Self-Service Change Password Report

Tanggal: 2026-06-01

## Scope

Self-service change password untuk Core Profile Portal di `apps/core-farmasi`.

Tahap ini tidak mengubah aplikasi consumer, tidak membuat SSO, tidak membuat token URL, dan tidak membuka `/admin` untuk non-admin.

## Previous Context

CORE-ACCOUNT-2B menetapkan registrasi publik disabled by default dan akun aktif dibuat oleh Admin Core lewat CRUD/import. User baru memakai password awal sementara, password langsung hashed, dan `must_change_password=true`.

Gap yang ditutup CORE-PROFILE-4:

- user non-admin perlu bisa mengganti password Core tanpa akses `/admin`.
- user dengan password awal sementara perlu jalur aman untuk membersihkan `must_change_password`.

## Routes

- `GET /profile/change-password`
- `PUT /profile/change-password`
- `GET /profil-saya/ganti-password` redirect ke `/profile/change-password`

Semua route berada di middleware `auth`, bukan public.

## Implementation

- `ProfilePortalController::changePassword()` menampilkan form ganti password.
- `ProfilePortalController::updatePassword()` memproses update password milik user yang sedang login.
- Validasi:
  - `current_password` required.
  - `password` required, confirmed, minimal sesuai policy Laravel existing, dan minimal 8 karakter.
  - current password diverifikasi dengan `Hash::check`.
  - password baru tidak boleh sama dengan password saat ini.
- Update sukses:
  - password baru disimpan hashed.
  - `must_change_password=false`.
  - `password_changed_at=now()`.
  - session diregenerasi.
  - audit `profile.password_changed` dibuat tanpa nilai password.

## UI

- View baru `resources/views/profile/change-password.blade.php`.
- Halaman `/profile` menampilkan tombol `Ganti Password`.
- User dengan `must_change_password=true` melihat warning dan link ganti password.
- UI memakai light theme putih + biru farmasi.
- Security note menjelaskan password berlaku untuk aplikasi Farmasi yang memakai verifikasi Core dan tidak boleh dibagikan.

## Security Confirmation

- Authenticated only: OK.
- Self-only: OK.
- Current password required: OK.
- Password baru hashed: OK.
- Password/hash/token/secret tidak ditampilkan: OK.
- Audit tidak menyimpan password lama/baru: OK.
- Non-admin tetap tidak bisa akses `/admin`: OK.
- Tidak ada SSO/token URL/auto-login: OK.

## Commands Run

- `php artisan test --filter=CoreProfilePortalTest`: OK, 17 passed / 96 assertions.
- `php artisan optimize:clear`: OK.
- `npm.cmd run build`: OK.
- `php artisan route:list`: OK, 98 routes listed.
- `php artisan test`: OK, 247 passed / 1384 assertions.

## Test Result

All tests passed.

## Guardrails Confirmation

- Tidak menjalankan migration.
- Tidak menjalankan `migrate:fresh/reset/rollback`.
- Tidak drop database.
- Tidak menghapus data.
- Tidak membuka `/admin` untuk non-admin.
- Tidak melonggarkan role access.
- Tidak membuat SSO/bypass login.
- Tidak membuat auto-login.
- Tidak membuat token URL.
- Tidak expose password/hash/token/secret.
- Tidak log password.
- Tidak mengubah password user lain.
- Tidak mengubah KP/TU/TA/Lab/SAFA.
- Tidak commit/push.

## Recommended Next Step

- CORE-ACCOUNT-3 Admin Approval Creates User Safely.
- CORE-GIT-3 Commit & Push Account/Profile Updates.
