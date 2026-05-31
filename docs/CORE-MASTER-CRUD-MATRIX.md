# Core Master CRUD Matrix

Tanggal audit: 2026-06-01

## Ringkasan

Manual CRUD master data Core tersedia melalui Filament Admin. Excel Import Center tetap tersedia untuk bulk import yang aman. Profile Portal tersedia untuk update kontak diri sendiri tanpa membuka akses admin.

Status umum: tidak ditemukan blocker CRUD untuk Core sebagai pusat master data.

## Matrix

| Data / Modul | Owner | Admin UI | Manual CRUD | Excel Import | Profile Portal | Consumer Access | Catatan |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Users | Core | `UserResource` | Index, create, edit, bulk delete action tersedia | Ya, `users` | View profile sendiri; safe contact via linked profile | API safe user, directory, app access check | Password tidak diekspor/ditampilkan; reset awal via action admin. |
| Global roles | Core | `RoleResource` | Index, create, edit, bulk delete action tersedia | Ya, `roles` | Tidak | Role read via access/API sesuai endpoint | Role global bukan app role. |
| User role assignments | Core | Via `UserResource` multi-select dan Import Center | Assign/edit via user form | Ya, `user_role_assignments` | Tidak | Consumer hanya membaca hasil role/access | Tidak ada standalone `UserRoleResource`, ini diterima karena pivot dikelola lewat user/import. |
| Departments | Core | `DepartmentResource` | Index, create, edit, bulk delete action tersedia | Ya, `departments` | Tidak | Directory/study relation | Code harus stabil. |
| Study programs | Core | `StudyProgramResource` | Index, create, edit, bulk delete action tersedia | Ya, `study_programs` | Tidak | Directory/study program endpoints | Memiliki relation department dan optional head lecturer. |
| Students | Core | `StudentResource` | Index, create, edit, bulk delete action tersedia | Ya, `students` | Safe contact self-update bila user terkait | Directory/student endpoints | Consumer FK transaksi tetap legacy sampai cutover. |
| Lecturers | Core | `LecturerResource` | Index, create, edit, bulk delete action tersedia | Ya, `lecturers` | Safe contact self-update bila user terkait | Directory/lecturer endpoints | Lecturer number/NIDN/NIP menjadi identifier utama. |
| Employees | Core | `EmployeeResource` | Index, create, edit, bulk delete action tersedia | Ya, `employees` | Safe contact self-update bila user terkait | Directory/employee endpoints | Untuk tendik, admin, staf TU, laboran, pegawai non-dosen. |
| Core applications | Core | `CoreApplicationResource` | Index, create, edit, bulk delete action tersedia | Tidak via Import Center saat ini | Tidak | App launcher/readiness/API | Registry aplikasi internal. |
| Core application roles | Core | `CoreApplicationRoleResource` | Index, create, edit, bulk delete action tersedia | Tidak via Import Center saat ini | Tidak | App role catalog | Role app dynamic per `app_code`. |
| User app accesses | Core | `UserAppAccessResource` | Index, create, edit, bulk delete action tersedia | Ya, `user_app_accesses` | Tidak | App access check endpoint | `user_id + app_code + role_slug`. |
| Leadership assignments | Core | `LeadershipAssignmentResource` | Index, create, edit, bulk delete action tersedia | Tidak via Import Center saat ini | Tidak | Current leadership endpoint | Jabatan resmi, bukan role login. |
| API clients | Core | `CoreApiClientResource` | Index, create, edit | Tidak | Tidak | App-client auth | Secret hashed, shown once on create/rotate by tooling. |
| API request logs | Core | `CoreApiRequestLogResource` | Read-only | Tidak | Tidak | Tidak untuk consumer umum | No create/edit/delete; pruning via command. |
| User activity logs | Core | `UserActivityLogResource` | Read-only | Tidak | Tidak | Tidak untuk consumer umum | No create/edit/delete. |
| Import batches/records | Core | Import Center page | Managed by import workflow | Ya, as workflow metadata | Tidak | Tidak | Tidak ada standalone CRUD resource; intentionally managed through Import Center decisions/execute/rollback. |
| Data quality | Core | Data Quality page | Read-only dashboard | Tidak | Tidak | Tidak | Tidak melakukan auto-fix. |

## Admin Route Evidence

`php artisan route:list --path=admin --method=GET` menampilkan route admin untuk:

- users
- roles
- departments
- study-programs
- students
- lecturers
- employees
- leadership-assignments
- core-applications
- core-application-roles
- user-app-accesses
- core-api-clients
- core-api-request-logs
- user-activity-logs
- import-center
- data-quality

## Import Evidence

`config/core_import.php` mengaktifkan template:

- `users`
- `students`
- `lecturers`
- `employees`
- `departments`
- `study_programs`
- `roles`
- `user_role_assignments`
- `user_app_accesses`

Test `CoreImportCenterTest` mencakup template download, upload preview, validation, decision, execute, dan rollback safety.

## Profile Portal Evidence

Route web:

- `GET /profile`
- `GET /profile/edit`
- `PUT /profile`
- `GET /profil-saya`

Test `CoreProfilePortalTest` mencakup:

- guest redirect.
- non-admin bisa akses profile tetapi tidak admin panel.
- summary student/lecturer/employee.
- update safe contact fields.
- proteksi agar user tidak mengubah profil user lain.

## Keputusan Audit

- Tidak perlu membuat resource CRUD baru pada tahap ini.
- `UserActivityLogResource` dan `CoreApiRequestLogResource` tetap read-only by design.
- Import batch/record tetap dikelola melalui Import Center, bukan standalone CRUD manual.
- Bulk delete action pada beberapa resource master tersedia, tetapi pemakaiannya harus mengikuti SOP admin dan backup karena Core adalah source of truth.
