# Core Centralized Profile Portal Plan

## A. Purpose
- Profil utama user dikelola terpusat di Core Farmasi UBP.
- Aplikasi lain seperti KP, TU, dan SAFA membaca profil dari Core.
- Edit profil utama tidak tersebar di aplikasi consumer.
- Core menjadi canonical source untuk identity, master profile, role, app access, dan leadership data.

## B. Profile Ownership
Core owns:
- nama resmi.
- username.
- identity type.
- identity number.
- email utama.
- phone dan address pada student, lecturer, dan employee profile.
- student profile.
- lecturer profile.
- employee profile.
- app access metadata.
- official leadership data.

Other apps own:
- data operasional aplikasi.
- transaksi.
- berkas KP/TU.
- penilaian.
- workflow.
- status aplikasi spesifik.
- field domain khusus yang tidak menjadi identitas/master profile resmi.

## C. Access Model
- Core Admin Panel hanya untuk `super-admin` dan `admin-core` aktif.
- Profile Portal untuk authenticated user non-admin agar bisa melihat profil sendiri.
- User hanya boleh melihat dan mengedit safe contact fields miliknya sendiri.
- User boleh mengganti password miliknya sendiri melalui Profile Portal.
- Other apps menampilkan profil secara read-only dan menyediakan link "Ubah Profil di Core".
- Profile Portal tidak membuka akses ke `/admin`.
- Pembuatan akun aktif dilakukan oleh Admin Core lewat CRUD/import. Registrasi publik/account request disabled by default dan tidak membuat user/app access otomatis.

## D. Editable by User
Mahasiswa/dosen/tendik boleh edit jika field tersedia:
- phone.
- address.
- alternate email jika field khusus dibuat di tahap berikutnya.
- optional contact fields yang aman.
- foto profil di tahap lanjutan jika storage policy sudah jelas.

Tidak boleh edit sendiri:
- NIM.
- NIDN/NIP/employee_number.
- identity_number.
- identity_type.
- nama resmi jika kebijakan kampus mengharuskan admin.
- prodi.
- department.
- status aktif/nonaktif.
- roles.
- app access.
- leadership/jabatan.
- password user lain.

## E. Profile Portal Pages
- `/profile` untuk view profile.
- `/profile/edit` untuk edit safe contact fields.
- `/profile/change-password` untuk ganti password Core sendiri.
- `/profil-saya` redirect ke `/profile`.
- `/profil-saya/ganti-password` redirect ke `/profile/change-password`.
- View profile menampilkan identitas akun, profil tertaut, dan security notice.
- Edit profile hanya menampilkan field kontak aman.
- Change password profile portal meminta current password, password baru, dan confirmation.
- User dengan `must_change_password=true` melihat warning dan tombol ganti password di `/profile`.
- Admin yang bisa masuk panel tetap dapat memakai `/admin/change-password`; Profile Portal tidak membuka akses `/admin`.
- App launcher tetap menjadi fitur admin/authorized app context, bukan SSO.

## F. Security Rules
- Authenticated only.
- User can only see/edit own profile.
- Non-admin cannot access `/admin`.
- CSRF protected.
- No sensitive data exposure.
- No password, password hash, token, or secret exposure.
- No role/app access editing.
- No official identity editing.
- Password change is self-only and audited without old/new password values.
- Audit profile changes with changed field names only, not sensitive old/new values.

## G. Integration Rule for Other Apps
- KP/TU should not duplicate edit profile forms.
- KP/TU show read-only profile from Core.
- KP/TU provide "Ubah Profil di Core" link.
- App-specific fields remain in each app.
- No write-back from consumer app to Core profile.
- No SSO, no auto-login, and no token URL.

## H. Roadmap
- CORE-PROFILE-1 skeleton: protected `/profile`, safe summary, and safe contact update where existing fields support it.
- CORE-PROFILE-2 editable safe contact fields and profile completion workflow: implemented for student, lecturer, and employee contact fields.
- CORE-PROFILE-3 profile completion/data quality indicators.
- CORE-PROFILE-4 self-service change password di Profile Portal untuk authenticated user tanpa membuka akses `/admin`: implemented.
- CORE-INTEGRATION update KP/TU read-only profile display and link to Core profile.
