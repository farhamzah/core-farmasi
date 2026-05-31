# Core Central Data Contract

Tanggal audit: 2026-06-01

## Tujuan

Dokumen ini menetapkan kontrak data pusat Core Farmasi UBP. Core menjadi sumber utama untuk identitas, master akademik, role global, akses aplikasi, jabatan resmi, import master data, audit, dan API internal.

Kontrak ini dipakai oleh aplikasi consumer seperti KP, TU, TA, Lab, SAFA, dan aplikasi baru berikutnya. Consumer boleh membaca data Core melalui integrasi yang disetujui, tetapi tidak boleh menulis langsung ke master data Core.

## Prinsip Utama

- Core adalah source of truth untuk master identity dan master akademik.
- Consumer app tetap memiliki data operasional domain masing-masing.
- Consumer app tidak membuat SSO, auto-login, token URL, atau cross-app session tanpa tahap desain terpisah.
- Consumer app tidak menyimpan secret di repo, report, URL, atau log.
- Perubahan master data dilakukan melalui admin Core, Excel Import Center Core, atau Profile Portal Core untuk field self-service yang aman.
- Field transaksi consumer tetap memakai ID legacy/domain masing-masing sampai ada cutover eksplisit.

## Core-Owned Data

| Area | Tabel / Modul | Keterangan |
| --- | --- | --- |
| Identity | `users` | Akun canonical, email, username, identity type/number, active status, password hash, password policy. |
| Global roles | `roles`, `user_roles` | Role global Core seperti `super-admin`, `admin-core`, dan role lintas aplikasi yang disetujui. |
| App registry | `core_applications` | Registry aplikasi internal dan metadata launch/readiness. |
| App role catalog | `core_application_roles` | Katalog role per aplikasi, dynamic dan tidak perlu hardcode di banyak tempat. |
| App access | `user_app_accesses` | Assignment akses user ke aplikasi tertentu dengan `app_code`, `role_slug`, permissions, dan status aktif. |
| Students | `students` | Master mahasiswa, NIM/student number, prodi, kontak aman, status. |
| Lecturers | `lecturers` | Master dosen, lecturer number/NIDN/NIP, prodi, departemen, kontak aman, status. |
| Employees | `employees` | Tendik, admin, staf TU, laboran, dan pegawai non-dosen lain. |
| Academic units | `departments`, `study_programs` | Fakultas/departemen/unit dan program studi. |
| Leadership | `leadership_assignments`, `study_programs.head_lecturer_id` | Jabatan resmi aktif seperti Dekan, Kaprodi, Kepala Lab, Koordinator KP. |
| Import audit | `core_import_batches`, `core_import_records` | Batch, row decision, snapshot, execute, dan rollback metadata import. |
| API clients | `core_api_clients`, `core_api_request_logs` | App-client credential hash, ability, audit request, rate-limit support. |
| User audit | `user_activity_logs` | Log aktivitas user, read-only di admin. |

## Consumer-Owned Data

| App | Data tetap milik app |
| --- | --- |
| KP Farmasi | Registrasi KP, penempatan, penugasan, nilai, field supervisor profile, dokumen dan workflow KP. |
| TU Farmasi | Surat, template, nomor surat, arsip, media, layanan TU, workflow dokumen. |
| TA Farmasi | Proposal/TA, bimbingan, seminar/sidang, penilaian, dokumen TA. |
| Lab Farmasi | Jadwal/praktikum/lab, transaksi pemakaian lab, domain lab. |
| SAFA UBP | Data operasional SAFA yang bukan master identity Core. |

Consumer app boleh menyimpan snapshot untuk kebutuhan historis, audit, atau dokumen, tetapi snapshot tidak menjadi sumber utama master identity.

## Manual Mutation Rules

| Mutasi | Jalur resmi | Catatan |
| --- | --- | --- |
| Create/edit user | Filament `UserResource` | Password tidak pernah ditampilkan; reset password awal memakai birth date bila tersedia. |
| Assign global role | `UserResource` / `RoleResource` | Role global berbeda dari app role. |
| Assign app access | `UserAppAccessResource` | App access memakai `app_code` dan `role_slug`. |
| Create/edit student | `StudentResource` atau Import Center | NIM/student number adalah identifier utama mahasiswa. |
| Create/edit lecturer | `LecturerResource` atau Import Center | Lecturer number/NIDN/NIP adalah identifier utama dosen. |
| Create/edit employee | `EmployeeResource` atau Import Center | Employee number/national ID dipakai sesuai data tersedia. |
| Create/edit department | `DepartmentResource` atau Import Center | Code harus unik dan stabil. |
| Create/edit study program | `StudyProgramResource` atau Import Center | Terhubung ke department dan optional head lecturer. |
| Update profil sendiri | Profile Portal `/profile` | Hanya safe contact fields; bukan role/status/identity utama. |
| API client credential | `CoreApiClientResource` / command resmi | Secret ditampilkan sekali dan disimpan hashed. |
| Logs | Read-only resources | Tidak diedit manual. |

## Excel Import Contract

Import Center Core menyediakan template dan flow aman untuk:

- `users`
- `students`
- `lecturers`
- `employees`
- `departments`
- `study_programs`
- `roles`
- `user_role_assignments`
- `user_app_accesses`

Aturan import:

- File upload disimpan private/local.
- Password column ditolak/diabaikan sesuai tipe import.
- Import profile tidak otomatis membuat app access.
- App role dan global role tidak dibuat otomatis dari profile import.
- Row invalid/skip tidak dieksekusi.
- Execute memakai per-row transaction.
- Import batch dan import record menyimpan snapshot/decision untuk audit dan rollback readiness.
- Rollback menghindari hard delete yang tidak aman dan memberi status manual review bila metadata tidak cukup.

## Profile Portal Contract

Route:

- `/profile`
- `/profile/edit`
- `PUT /profile`
- `/profil-saya` redirect ke `/profile`

Aturan:

- Hanya authenticated user.
- Self-only.
- Non-admin boleh membuka Profile Portal tetapi tetap tidak bisa membuka `/admin`.
- Editable safe fields: phone/address pada profil terkait yang tersedia.
- Field resmi tetap admin-only: nama resmi, email utama, username, identity number, NIM, NIDN/NIP, employee number, prodi, department, role, app access, status, leadership.

## Integration Contract

Consumer app dapat membaca Core melalui:

- API internal app-client dengan ability scoped.
- Directory endpoints aman untuk users/students/lecturers/employees/study programs/departments.
- App access check endpoint.
- Current leadership endpoint.
- Profile Portal link tanpa token, secret, user id, atau session lintas aplikasi.
- Read-only bridge khusus bila sudah didesain pada app consumer.

Consumer app tidak boleh:

- Menulis langsung ke Core DB.
- Mengubah Core password atau legacy password.
- Menyimpan client secret di repo.
- Mengirim secret lewat query string.
- Membuat URL login otomatis.
- Mengubah FK transaksi menjadi Core ID tanpa tahap cutover eksplisit.

## Matching Keys

| Entity | Primary matching key | Fallback / catatan |
| --- | --- | --- |
| User | normalized email | username atau identity number bila desain consumer menyebutkan. |
| Student | `student_number` / NIM | linked user email untuk validasi tambahan. |
| Lecturer | `lecturer_number` / NIDN/NIP | employee number atau linked user email bila data legacy tidak lengkap. |
| Employee | `employee_number` | national ID atau email bila tersedia. |
| Role | `name` / slug | KP role `admin` tetap map ke `admin-kp`, bukan `admin-core`. |
| App access | `user_id + app_code + role_slug` | Hanya active access yang memberi akses aplikasi. |
| Study program | code/name map | Mapping legacy harus terdokumentasi. |
| Department | code/name map | Mapping legacy harus terdokumentasi. |

## Current Validation Summary

- Core runtime: Laravel `12.60.2`, Filament `v5.6.5`, MySQL, timezone `Asia/Jakarta`.
- Admin resource routes tersedia untuk master data utama.
- API internal dan public baseline tersedia.
- Import Center tersedia dan dites.
- Profile Portal tersedia dan dites.
- Test suite terakhir: `220 passed, 1130 assertions`.

## Open Guardrails

- Production cutover consumer app tetap harus melalui preflight dan approval terpisah.
- Consumer app baru harus mulai dari read-only integration.
- Secret/staging credentials harus dibuat di luar repo.
- SSO/cross-app session masih non-goal sampai ada desain eksplisit.
