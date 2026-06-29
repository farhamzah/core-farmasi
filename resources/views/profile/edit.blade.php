<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .core-profile-photo-preview {
            align-items: center;
            aspect-ratio: 1 / 1;
            display: inline-flex;
            flex: 0 0 auto;
            height: 6rem;
            justify-content: center;
            max-height: 6rem;
            max-width: 6rem;
            min-height: 6rem;
            min-width: 6rem;
            overflow: hidden;
            width: 6rem;
        }

        .core-profile-photo-preview > img {
            display: block;
            height: 100%;
            max-height: 100%;
            max-width: 100%;
            object-fit: cover;
            object-position: center top;
            width: 100%;
        }

        @media (max-width: 640px) {
            .core-profile-photo-preview {
                height: 5.25rem;
                max-height: 5.25rem;
                max-width: 5.25rem;
                min-height: 5.25rem;
                min-width: 5.25rem;
                width: 5.25rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    @php
        $editableFields = collect($profile['editable_fields'])->flatten()->unique()->values();
        $editableGroups = collect($profile['editable_fields'])
            ->filter(fn ($fields) => collect($fields)->isNotEmpty());
        $hasLinkedProfile = collect($profile['profiles'] ?? [])->isNotEmpty();
        $contactTarget = $hasLinkedProfile ? 'profil resmi tertaut' : 'akun Core sementara';
        $completion = $profile['completion'];
        $contact = $profile['contact_values'];
        $editValues = $profile['edit_values'] ?? $contact;
        $profilePhotoUrl = $profile['user']['profile_photo_url'] ?? null;
        $initial = strtoupper(substr((string) ($profile['user']['name'] ?? 'U'), 0, 1));
        $groupLabels = [
            'student' => ['title' => 'Profil Mahasiswa', 'subtitle' => 'Data pendukung mahasiswa. NIM, nama resmi, program studi, dan status tetap diverifikasi Admin Core.'],
            'lecturer' => ['title' => 'Profil Dosen', 'subtitle' => 'Data pendukung dosen. Nama dasar, nomor utama, NIDN, dan NIDK tetap dikunci; gelar depan/belakang boleh dilengkapi mandiri.'],
            'employee' => ['title' => 'Profil Tendik / Staf / Laboran', 'subtitle' => 'Data pendukung kepegawaian. Nomor pegawai dan nama resmi tetap diverifikasi Admin Core.'],
            'externalPerson' => ['title' => 'Profil Mitra Eksternal', 'subtitle' => 'Data umum mitra luar Fakultas Farmasi. Akses KP/TA tetap diberikan oleh Admin Core sesuai kebutuhan aplikasi.'],
            'user' => ['title' => 'Akun Core Sementara', 'subtitle' => 'Kontak dasar disimpan di akun Core sampai profil resmi ditautkan oleh Admin Core.'],
        ];
        $fieldLabels = [
            'email' => 'Email Profil',
            'phone' => 'Telepon',
            'address' => 'Alamat',
            'alternate_email' => 'Email Alternatif',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir',
            'enrolled_at' => 'Tanggal Masuk',
            'front_title' => 'Gelar Depan',
            'back_title' => 'Gelar Belakang',
            'national_id_number' => 'NIK / No. KTP',
            'nip' => 'NIP',
            'nuptk' => 'NUPTK',
            'gender' => 'Jenis Kelamin',
            'staff_type' => 'Jenis Tendik / Staf',
            'position_title' => 'Jabatan / Posisi',
            'institution_name' => 'Instansi / Perusahaan',
            'institution_type' => 'Jenis Instansi',
            'profession' => 'Profesi',
            'notes' => 'Catatan Profil',
        ];
        $dateFields = ['birth_date', 'enrolled_at'];
        $textareaFields = ['address', 'notes'];
        $selectOptions = [
            'gender' => ['' => 'Pilih jika ingin diisi', 'male' => 'Laki-laki', 'female' => 'Perempuan'],
            'staff_type' => ['' => 'Pilih jika ingin diisi', 'tendik' => 'Tendik', 'staf_tu' => 'Staf TU', 'laboran' => 'Laboran', 'admin' => 'Admin', 'other' => 'Lainnya'],
            'institution_type' => ['' => 'Pilih jika ingin diisi', 'industry' => 'Industri', 'hospital' => 'Rumah Sakit', 'pharmacy' => 'Apotek', 'university' => 'Universitas / Kampus Lain', 'clinic' => 'Klinik', 'government' => 'Instansi Pemerintah', 'other' => 'Lainnya'],
        ];
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.12)]">
            <div class="relative p-6 sm:p-8">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Core Farmasi UBP</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal text-slate-950 sm:text-4xl">Edit Profil Aman</h1>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">
                            Ubah biodata pendukung dan kontak yang boleh dikelola mandiri. Nama, NIM, NIDN, dan NIDK tetap dikunci agar identitas utama tidak berubah tanpa verifikasi Admin Core.
                        </p>
                    </div>
                    <div class="grid shrink-0 grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-1">
                        <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Lihat Profil
                        </a>
                        <a href="{{ route('profile.password.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm font-bold text-blue-700 shadow-sm transition hover:bg-blue-50">
                            Ganti Password
                        </a>
                        <form method="POST" action="{{ route('profile.logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
                <p class="font-black">Periksa kembali input profil.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</p>
                <p class="mt-2 text-lg font-black {{ $completion['is_complete'] ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $completion['is_complete'] ? 'Profil lengkap' : 'Perlu dilengkapi' }}
                </p>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kelengkapan</p>
                <div class="mt-2 flex items-end gap-2">
                    <span class="text-3xl font-black text-blue-700">{{ $completion['percentage'] }}%</span>
                    <span class="pb-1 text-xs font-bold text-slate-500">{{ $completion['completed'] }}/{{ $completion['total'] }}</span>
                </div>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kontak disimpan ke</p>
                <p class="mt-2 text-lg font-black text-slate-900">{{ $contactTarget }}</p>
            </div>
        </section>

        @if (! $hasLinkedProfile)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm leading-7 text-blue-950">
                Akun ini belum tertaut ke profil mahasiswa, dosen, atau tendik. Telepon dan alamat tetap bisa disimpan di akun Core agar portal tidak berhenti di tengah jalan. Password dapat diganti kapan saja dari halaman Ganti Password.
            </div>
        @elseif (! $completion['is_complete'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-7 text-amber-950">
                Lengkapi telepon dan alamat bila tersedia. Password bisa diganti kapan saja dari halaman Ganti Password.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_320px]">
            @csrf
            @method('PUT')

            <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Form Profil</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Profil yang Bisa Diperbarui</h2>

                <div class="mt-6 grid gap-6">
                    <section class="rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-emerald-50/50 p-4 shadow-sm sm:p-5">
                        <div class="grid gap-5 sm:grid-cols-[auto_1fr] sm:items-center">
                            <div class="core-profile-photo-preview rounded-[1.75rem] border border-blue-100 bg-white text-3xl font-black text-blue-700 shadow-[0_16px_34px_rgba(30,64,175,0.14)]" data-profile-photo-preview-frame>
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil saat ini" data-profile-photo-preview>
                                @else
                                    <span data-profile-photo-initial>{{ $initial }}</span>
                                    <img src="" alt="Preview foto profil" class="hidden" data-profile-photo-preview>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-950">Foto Profil</h3>
                                        <p class="mt-1 text-xs leading-6 text-slate-600">Dipakai sebagai foto identitas umum untuk aplikasi Farmasi yang membaca Core.</p>
                                    </div>
                                    <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">JPG / PNG / WebP</span>
                                </div>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <label for="profile_photo" class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                                        Pilih Foto Baru
                                    </label>
                                    <span class="text-xs font-semibold text-slate-500">Maksimal 2MB, wajah sebaiknya tampak jelas.</span>
                                </div>
                                <input
                                    id="profile_photo"
                                    name="profile_photo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    data-profile-photo-input
                                >
                                <p class="mt-3 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 ring-1 ring-blue-100" data-profile-photo-status>
                                    {{ $profilePhotoUrl ? 'Foto saat ini akan tetap dipakai jika tidak memilih file baru.' : 'Belum ada file foto dipilih.' }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-slate-500">Preview akan tampil langsung. Klik Simpan Profil untuk menyimpan foto ke Core.</p>
                            </div>
                        </div>
                    </section>

                    @foreach ($editableGroups as $groupKey => $fields)
                        @php
                            $group = $groupLabels[$groupKey] ?? ['title' => str($groupKey)->replace('_', ' ')->title(), 'subtitle' => 'Field profil yang aman diperbarui mandiri.'];
                        @endphp

                        <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                            <div class="mb-5">
                                <h3 class="text-base font-black text-slate-950">{{ $group['title'] }}</h3>
                                <p class="mt-1 text-xs leading-6 text-slate-600">{{ $group['subtitle'] }}</p>
                            </div>

                            <div class="grid gap-5">
                                @foreach ($fields as $field)
                                    @php($inputId = $groupKey.'-'.$field)
                                    <div>
                                        <label for="{{ $inputId }}" class="text-sm font-bold text-slate-800">{{ $fieldLabels[$field] ?? str($field)->replace('_', ' ')->title() }}</label>

                                        @if (array_key_exists($field, $selectOptions))
                                            <select
                                                id="{{ $inputId }}"
                                                name="{{ $field }}"
                                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                            >
                                                @foreach ($selectOptions[$field] as $value => $label)
                                                    <option value="{{ $value }}" @selected(old($field, $editValues[$field] ?? '') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif (in_array($field, $textareaFields, true))
                                            <textarea
                                                id="{{ $inputId }}"
                                                name="{{ $field }}"
                                                rows="{{ $field === 'notes' ? 4 : 5 }}"
                                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                                @if ($field === 'address') autocomplete="street-address" @endif
                                            >{{ old($field, $editValues[$field] ?? '') }}</textarea>
                                        @else
                                            <input
                                                id="{{ $inputId }}"
                                                name="{{ $field }}"
                                                type="{{ in_array($field, $dateFields, true) ? 'date' : ($field === 'email' || $field === 'alternate_email' ? 'email' : 'text') }}"
                                                value="{{ old($field, $editValues[$field] ?? '') }}"
                                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                                @if ($field === 'phone') autocomplete="tel" @endif
                                                @if ($field === 'email' || $field === 'alternate_email') autocomplete="email" @endif
                                            >
                                        @endif

                                        <p class="mt-2 text-xs font-medium text-slate-500">
                                            @if (in_array($field, ['front_title', 'back_title'], true))
                                                Isi sesuai format resmi, contoh: Dr., apt., M.Farm., S.Si. Nama dasar tetap terkunci.
                                            @else
                                                Disimpan ke {{ $group['title'] }}.
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    @if ($editableGroups->isEmpty())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-7 text-amber-900">
                            Belum ada field profil yang tersedia untuk diedit. Anda tetap bisa melihat profil atau mengganti password.
                        </div>
                    @endif
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                        Simpan Profil
                    </button>
                </div>
            </section>

            <aside class="grid gap-6">
                <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Read-only</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Data Resmi</h2>
                    <dl class="mt-5 space-y-4">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nama</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $profile['user']['name'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Username</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $profile['user']['username'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Email Utama</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $profile['user']['email'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Identitas</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-950">{{ $profile['user']['identity_type'] ?? '-' }} {{ $profile['user']['identity_number_masked'] ? '('.$profile['user']['identity_number_masked'].')' : '' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
                    <h2 class="text-lg font-black text-slate-950">Batas Edit Mandiri</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-700">
                        Nama, NIM, NIDN, NIDK, status aktif, role, app access, dan jabatan struktural hanya diperbarui oleh Admin Core.
                    </p>
                </section>
            </aside>
        </form>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.querySelector('[data-profile-photo-input]');
            const preview = document.querySelector('[data-profile-photo-preview]');
            const initial = document.querySelector('[data-profile-photo-initial]');
            const status = document.querySelector('[data-profile-photo-status]');

            input?.addEventListener('change', () => {
                const file = input.files?.[0];

                if (! file) {
                    if (status) {
                        status.textContent = 'Belum ada file foto dipilih.';
                    }

                    return;
                }

                if (! file.type.startsWith('image/')) {
                    if (status) {
                        status.textContent = 'File harus berupa gambar JPG, PNG, atau WebP.';
                    }
                    input.value = '';

                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    if (status) {
                        status.textContent = 'Ukuran foto terlalu besar. Maksimal 2MB.';
                    }
                    input.value = '';

                    return;
                }

                if (status) {
                    const sizeKb = Math.round(file.size / 1024);
                    status.textContent = `${file.name} dipilih (${sizeKb} KB). Klik Simpan Profil untuk mengunggah.`;
                }

                if (preview && file.type.startsWith('image/')) {
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                    initial?.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
