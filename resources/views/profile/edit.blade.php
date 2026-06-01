<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Kontak - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    @php
        $editableFields = collect($profile['editable_fields'])->flatten()->unique()->values();
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-4xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950">Edit Kontak Aman</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Hanya field kontak yang aman dan tersedia di profil tertaut yang dapat diperbarui. Data resmi tetap admin-only.
                    </p>
                </div>
                <form method="POST" action="{{ route('profile.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Keluar
                    </button>
                </form>
            </div>
        </header>

        @if (! ($profile['completion']['is_complete'] ?? false))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                Profil Anda belum lengkap. Isi field kontak yang tersedia, terutama telepon dan alamat.
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Periksa kembali input profil.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <section class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600">Data resmi read-only</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $profile['user']['name'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Username</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $profile['user']['username'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email utama</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $profile['user']['email'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identitas</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $profile['user']['identity_type'] ?? '-' }} {{ $profile['user']['identity_number_masked'] ? '('.$profile['user']['identity_number_masked'].')' : '' }}</dd>
                    </div>
                </dl>
            </section>

            <div class="grid gap-5">
                @if ($editableFields->contains('phone'))
                    <div>
                    <label for="phone" class="text-sm font-semibold text-slate-800">Telepon</label>
                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="{{ old('phone', $profile['contact_values']['phone'] ?? '') }}"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <p class="mt-2 text-xs text-slate-500">Disimpan ke profil tertaut milik Anda yang memiliki kolom telepon.</p>
                    </div>
                @endif

                @if ($editableFields->contains('address'))
                    <div>
                    <label for="address" class="text-sm font-semibold text-slate-800">Alamat</label>
                    <textarea
                        id="address"
                        name="address"
                        rows="4"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >{{ old('address', $profile['contact_values']['address'] ?? '') }}</textarea>
                        <p class="mt-2 text-xs text-slate-500">Disimpan ke profil tertaut milik Anda yang memiliki kolom alamat.</p>
                    </div>
                @endif

                @if ($editableFields->contains('alternate_email'))
                    <div>
                        <label for="alternate_email" class="text-sm font-semibold text-slate-800">Email Alternatif</label>
                        <input
                            id="alternate_email"
                            name="alternate_email"
                            type="email"
                            value="{{ old('alternate_email', $profile['contact_values']['alternate_email'] ?? '') }}"
                            class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                        <p class="mt-2 text-xs text-slate-500">Disimpan hanya jika field email alternatif tersedia di profil resmi.</p>
                    </div>
                @endif

                @if ($editableFields->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Belum ada field kontak aman yang tersedia untuk profil tertaut Anda.
                    </div>
                @endif
            </div>

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                Tidak dapat diedit di sini: nama resmi, username, identity type/number, NIM/NIDN/NIP/nomor pegawai, prodi, departemen, status, role, app access, jabatan, dan password.
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Simpan Kontak
                </button>
            </div>
        </form>
    </main>
</body>
</html>
