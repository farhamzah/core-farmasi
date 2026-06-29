<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permohonan Akun - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @php
        $selectedType = old('request_type', '');
        $typeCards = [
            \App\Models\AccountRequest::TYPE_STUDENT => [
                'label' => 'Mahasiswa',
                'description' => 'Untuk akun mahasiswa. Cukup isi NIM, nama, dan email.',
                'badge' => 'NIM',
                'role' => 'mahasiswa',
            ],
            \App\Models\AccountRequest::TYPE_LECTURER => [
                'label' => 'Dosen',
                'description' => 'Untuk dosen tetap/luar biasa. Isi nomor utama dosen dulu.',
                'badge' => 'NIDN/NIP',
                'role' => 'dosen',
            ],
            \App\Models\AccountRequest::TYPE_EMPLOYEE => [
                'label' => 'Tendik / Staf',
                'description' => 'Untuk tata usaha, staf, laboran, atau pegawai pendukung.',
                'badge' => 'No. Pegawai',
                'role' => 'tata-usaha',
            ],
            \App\Models\AccountRequest::TYPE_FIELD_SUPERVISOR => [
                'label' => 'Mitra Eksternal',
                'description' => 'Untuk pembimbing atau penguji dari industri, RS, apotek, klinik, atau kampus lain.',
                'badge' => 'Mitra / RS / Industri',
                'role' => 'pembimbing-lapangan',
            ],
        ];

        $institutionTypes = \App\Models\AccountRequest::externalInstitutionTypeOptions();
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
            <div class="border-t-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-emerald-50/50 p-5 sm:p-8">
                <div class="grid gap-5 lg:grid-cols-[1fr_360px] lg:items-center">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-sm sm:h-16 sm:w-16">
                            <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-10 w-10 object-contain sm:h-12 sm:w-12">
                        </div>
                        <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
                        <h1 class="mt-2 text-3xl font-bold leading-tight text-slate-950">Permohonan Akun</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Pilih jenis akun, isi data minimum, lalu permohonan masuk antrean verifikasi Admin Core. Detail lengkap bisa dilengkapi setelah akun disetujui.
                        </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-white px-4 py-3 text-sm leading-6 text-blue-900 shadow-sm">
                        Tidak ada password di form ini. Admin Core akan membuat akun dengan password awal sementara, lalu pemilik akun wajib menggantinya saat login pertama.
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">Periksa kembali data yang diisi.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('account-request.store') }}" class="grid gap-6" data-account-request-form>
                    @csrf

                    <section>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">Pilih Jenis Akun</h2>
                                <p class="mt-1 text-sm text-slate-600">Pilih satu kategori. Form akan menampilkan field yang relevan saja.</p>
                            </div>
                            <p class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Data minimum</p>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($typeCards as $value => $card)
                                <label class="group cursor-pointer rounded-xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-transparent transition hover:border-blue-200 hover:bg-blue-50/40 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-100 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:ring-blue-100">
                                    <input
                                        type="radio"
                                        name="request_type"
                                        value="{{ $value }}"
                                        class="sr-only"
                                        data-request-type-option
                                        data-default-role="{{ $card['role'] }}"
                                        @checked($selectedType === $value)
                                        required
                                    >
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">{{ $card['badge'] }}</span>
                                    <span class="mt-3 block text-base font-bold text-slate-950">{{ $card['label'] }}</span>
                                    <span class="mt-1 block text-sm leading-5 text-slate-600">{{ $card['description'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Data Wajib Awal</h2>
                        <p class="mt-1 text-sm text-slate-500">Isi yang diperlukan dulu. Data resmi tambahan tetap diverifikasi Admin Core setelah permohonan masuk.</p>
                    </section>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-slate-800">Nama Lengkap <span class="text-red-600">*</span></span>
                            <input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </label>

                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-slate-800">Email Aktif <span class="text-red-600">*</span></span>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </label>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-white p-4 shadow-sm" data-profile-panel="student" hidden>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-950">Data Mahasiswa</h3>
                                <p class="mt-1 text-sm text-slate-600">NIM wajib. Program studi boleh diisi sekarang agar approval lebih cepat.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Mahasiswa</span>
                        </div>
                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">NIM <span class="text-red-600">*</span></span>
                                <input name="student_number" value="{{ old('student_number') }}" maxlength="255" data-required-for="student" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Program Studi</span>
                                <select name="study_program_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Boleh dikosongkan dulu</option>
                                    @foreach ($studyPrograms as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('study_program_id') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-sky-100 bg-white p-4 shadow-sm" data-profile-panel="lecturer" hidden>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-950">Data Dosen</h3>
                                <p class="mt-1 text-sm text-slate-600">Isi satu nomor utama dulu. NIP/NIDN/NIDK/NUPTK detail bisa dilengkapi admin/profile nanti.</p>
                            </div>
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">Dosen</span>
                        </div>
                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Nomor Utama Dosen <span class="text-red-600">*</span></span>
                                <input name="lecturer_number" value="{{ old('lecturer_number') }}" maxlength="255" placeholder="NIDN, NIP, NIDK, atau NUPTK" data-required-for="lecturer" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Departemen / Unit</span>
                                <select name="department_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Boleh dikosongkan dulu</option>
                                    @foreach ($departments as $id => $name)
                                        <option value="{{ $id }}" @selected((string) old('department_id') === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm" data-profile-panel="employee" hidden>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-950">Data Tendik / Staf / Laboran</h3>
                                <p class="mt-1 text-sm text-slate-600">Nomor pegawai dan jenis staf cukup untuk masuk waiting list.</p>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">Tendik</span>
                        </div>
                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Nomor Pegawai <span class="text-red-600">*</span></span>
                                <input name="employee_number" value="{{ old('employee_number') }}" maxlength="255" data-required-for="employee" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Jenis Tendik / Staf <span class="text-red-600">*</span></span>
                                <select name="staff_type" data-required-for="employee" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Pilih jenis</option>
                                    <option value="tendik" @selected(old('staff_type') === 'tendik')>Tendik</option>
                                    <option value="staf_tu" @selected(old('staff_type') === 'staf_tu')>Staf TU</option>
                                    <option value="laboran" @selected(old('staff_type') === 'laboran')>Laboran</option>
                                    <option value="admin" @selected(old('staff_type') === 'admin')>Admin</option>
                                    <option value="other" @selected(old('staff_type') === 'other')>Lainnya</option>
                                </select>
                            </label>

                            <label class="grid gap-2 md:col-span-2">
                                <span class="text-sm font-semibold text-slate-800">Jabatan / Posisi</span>
                                <input name="position_title" value="{{ old('position_title') }}" maxlength="255" placeholder="Opsional, contoh: Laboran Farmakologi" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-white p-4 shadow-sm" data-profile-panel="field_supervisor" hidden>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-950">Data Mitra Eksternal</h3>
                                <p class="mt-1 text-sm text-slate-600">Untuk pembimbing/penguji luar KP atau TA. Isi data kontak dan instansi dulu, detail akses diverifikasi Admin Core.</p>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">Mitra Eksternal</span>
                        </div>
                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Nomor Telepon / WhatsApp <span class="text-red-600">*</span></span>
                                <input name="phone" value="{{ old('phone') }}" maxlength="50" autocomplete="tel" data-required-for="field_supervisor" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Instansi / Perusahaan <span class="text-red-600">*</span></span>
                                <input name="institution_name" value="{{ old('institution_name') }}" maxlength="255" placeholder="Contoh: RS Mitra Farmasi, Apotek Sehat, PT Industri Farma" data-required-for="field_supervisor" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Jenis Instansi</span>
                                <select name="institution_type" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <option value="">Boleh dikosongkan dulu</option>
                                    @foreach ($institutionTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('institution_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-slate-800">Profesi / Jabatan</span>
                                <input name="profession" value="{{ old('profession') }}" maxlength="255" placeholder="Opsional, contoh: Apoteker, Preseptor, HRD, Dosen Tamu" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            </label>
                        </div>
                    </div>

                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Tujuan Akses</h2>
                        <p class="mt-1 text-sm text-slate-500">Opsional. Isi jika sudah tahu aplikasi yang ingin dipakai. Admin tetap memverifikasi aksesnya.</p>
                    </section>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-slate-800">Aplikasi yang Dituju</span>
                            <select name="requested_app_code" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                <option value="">Boleh dikosongkan dulu</option>
                                @foreach ($applications as $appCode => $name)
                                    <option value="{{ $appCode }}" @selected(old('requested_app_code') === $appCode)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-slate-800">Catatan Singkat</span>
                            <input name="notes" value="{{ old('notes') }}" maxlength="5000" placeholder="Opsional, contoh: perlu akses KP semester ini" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </label>
                    </div>

                    <input type="hidden" name="requested_role" value="{{ old('requested_role') }}" data-requested-role>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                        Setelah disetujui, akun Core dibuat atau ditautkan. Data resmi tambahan, role, dan app access tetap diverifikasi di Core.
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:w-auto sm:py-2.5">
                            Kirim Permohonan
                        </button>
                        <p class="text-sm text-slate-500">Rata-rata butuh kurang dari 2 menit untuk mengisi.</p>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-account-request-form]');
            const panels = Array.from(document.querySelectorAll('[data-profile-panel]'));
            const roleInput = document.querySelector('[data-requested-role]');
            const options = Array.from(document.querySelectorAll('[data-request-type-option]'));

            const setType = (type) => {
                panels.forEach((panel) => {
                    const active = panel.dataset.profilePanel === type;
                    panel.hidden = ! active;

                    panel.querySelectorAll('input, select, textarea').forEach((field) => {
                        field.disabled = ! active;
                    });
                });

                document.querySelectorAll('[data-required-for]').forEach((field) => {
                    field.required = field.dataset.requiredFor === type;
                });

                const selected = options.find((option) => option.value === type);
                if (roleInput && selected && ! roleInput.value) {
                    roleInput.value = selected.dataset.defaultRole || '';
                }
            };

            options.forEach((option) => {
                option.addEventListener('change', () => setType(option.value));
            });

            const checked = options.find((option) => option.checked);
            setType(checked ? checked.value : '');

            form?.addEventListener('submit', () => {
                const checkedOption = options.find((option) => option.checked);
                if (roleInput && checkedOption && ! roleInput.value) {
                    roleInput.value = checkedOption.dataset.defaultRole || '';
                }
            });
        });
    </script>
</body>
</html>
