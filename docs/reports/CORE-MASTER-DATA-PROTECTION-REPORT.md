# CORE Master Data Protection Report

Tanggal: 2026-06-01

## Latar Belakang

Master akademik Core sebelumnya masih memiliki beberapa relasi lama dengan `cascadeOnDelete()`.
Jika parent master seperti departemen atau program studi dihapus, data turunan dapat ikut hilang atau
menjadi tidak lengkap. Untuk Core sebagai sumber utama data, pola ini tidak aman.

## Best Practice yang Dipakai

1. Master data resmi tidak dihapus dari UI harian.
2. Data yang tidak berlaku dinonaktifkan dengan kolom `active`, bukan dihapus.
3. Hard delete hanya boleh untuk data kosong yang belum dipakai.
4. Relasi database untuk master akademik harus `RESTRICT` ketika ada data turunan.
5. Model tetap menolak delete walaupun penghapusan dilakukan dari jalur selain UI.
6. User-facing message harus memberi arahan: nonaktifkan data jika sudah tidak dipakai.

## Guardrail yang Ditambahkan

### UI Admin

Bulk delete dihapus dari resource akademik berikut:

- Fakultas
- Departemen
- Program Studi

Admin tetap bisa membuat dan mengedit data, termasuk mengubah `active` menjadi nonaktif.

### Model Guard

Model berikut sekarang memiliki validasi sebelum delete:

- `Faculty`
- `Department`
- `StudyProgram`

Delete ditolak jika masih ada relasi ke:

- Fakultas: departemen, program studi, jabatan pimpinan fakultas.
- Departemen: program studi pembina, dosen, tendik/staf, jabatan kepala departemen.
- Program Studi: mahasiswa, dosen, tendik/staf, jabatan kaprodi.

### Database Guard

Foreign key akademik utama diganti ke `RESTRICT` agar database ikut mencegah penghapusan parent
yang masih dipakai.

Relasi yang diproteksi:

- `departments.faculty_id -> faculties.id`
- `study_programs.faculty_id -> faculties.id`
- `study_programs.department_id -> departments.id`
- `students.study_program_id -> study_programs.id`
- `lecturers.department_id -> departments.id`
- `lecturers.study_program_id -> study_programs.id`
- `employees.department_id -> departments.id`
- `employees.study_program_id -> study_programs.id`

## Dampak Operasional

- Jika Fakultas, Departemen, atau Program Studi masih dipakai, admin tidak dapat menghapusnya.
- Jika data sudah tidak berlaku, gunakan `active = false`.
- Jika benar-benar perlu hard delete, semua relasi harus dipindahkan/dikosongkan dengan sadar lebih dulu.
- Tidak ada perubahan pada user, password, role, import, API auth, atau aplikasi lain.

## Validasi

- Migration protect FK berhasil dijalankan dengan `php artisan migrate`.
- Test khusus proteksi master akademik berhasil.
- Full test Core berhasil.

## Rekomendasi Lanjutan

1. Terapkan pola yang sama ke master kritikal lain seperti role, app registry, dan user app access.
2. Tambahkan halaman Data Quality untuk mendeteksi master nonaktif yang masih dipakai.
3. Untuk data production, hard delete sebaiknya hanya melalui command maintenance khusus dengan backup.
4. Admin panel sebaiknya memakai istilah "Nonaktifkan" untuk master resmi, bukan "Hapus".
