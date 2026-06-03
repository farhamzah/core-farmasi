<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Kontak - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    @php
        $editableFields = collect($profile['editable_fields'])->flatten()->unique()->values();
        $hasLinkedProfile = collect($profile['profiles'] ?? [])->isNotEmpty();
        $contactTarget = $hasLinkedProfile ? 'profil resmi tertaut' : 'akun Core sementara';
        $completion = $profile['completion'];
        $contact = $profile['contact_values'];
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.12)]">
            <div class="relative p-6 sm:p-8">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Core Farmasi UBP</p>
                        <h1 class="mt-3 text-3xl font-black tracking-normal text-slate-950 sm:text-4xl">Edit Kontak Aman</h1>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">
                            Ubah kontak yang boleh dikelola mandiri. Data resmi seperti NIM, NIDN, NUPTK, NIP, NIK/KTP, prodi, departemen, status, role, app access, dan jabatan tetap admin-only.
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

        <form method="POST" action="{{ route('profile.update') }}" class="grid gap-6 lg:grid-cols-[1fr_320px]">
            @csrf
            @method('PUT')

            <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Form Kontak</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Kontak yang Bisa Diperbarui</h2>

                <div class="mt-6 grid gap-5">
                    @if ($editableFields->contains('phone'))
                        <div>
                            <label for="phone" class="text-sm font-bold text-slate-800">Telepon</label>
                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                value="{{ old('phone', $contact['phone'] ?? '') }}"
                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                autocomplete="tel"
                            >
                            <p class="mt-2 text-xs font-medium text-slate-500">Disimpan ke {{ $contactTarget }}.</p>
                        </div>
                    @endif

                    @if ($editableFields->contains('address'))
                        <div>
                            <label for="address" class="text-sm font-bold text-slate-800">Alamat</label>
                            <textarea
                                id="address"
                                name="address"
                                rows="5"
                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                autocomplete="street-address"
                            >{{ old('address', $contact['address'] ?? '') }}</textarea>
                            <p class="mt-2 text-xs font-medium text-slate-500">Disimpan ke {{ $contactTarget }}.</p>
                        </div>
                    @endif

                    @if ($editableFields->contains('alternate_email'))
                        <div>
                            <label for="alternate_email" class="text-sm font-bold text-slate-800">Email Alternatif</label>
                            <input
                                id="alternate_email"
                                name="alternate_email"
                                type="email"
                                value="{{ old('alternate_email', $contact['alternate_email'] ?? '') }}"
                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                                autocomplete="email"
                            >
                            <p class="mt-2 text-xs font-medium text-slate-500">Disimpan jika field email alternatif tersedia.</p>
                        </div>
                    @endif

                    @if ($editableFields->isEmpty())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-7 text-amber-900">
                            Belum ada field kontak aman yang tersedia. Anda tetap bisa melihat profil atau mengganti password.
                        </div>
                    @endif
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                        Simpan Kontak
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
                        Nomor akademik/pegawai, NIK/KTP, NIDN/NIDK, NIP, NUPTK, prodi, departemen, status, role, app access, dan jabatan hanya diperbarui oleh Admin Core.
                    </p>
                </section>
            </aside>
        </form>
    </main>
</body>
</html>
