<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-6xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-950">Profil Saya</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Profil utama dikelola terpusat di Core. Data resmi seperti identitas, nomor akademik/pegawai, status, role, akses aplikasi, dan jabatan hanya dapat diubah oleh admin Core.
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('profile.password.edit') }}" class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                        Ganti Password
                    </a>
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Edit Kontak Aman
                    </a>
                </div>
            </div>
        </header>

        @if ($user->must_change_password)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                Anda wajib mengganti password awal sebelum menggunakan layanan.
                <a href="{{ route('profile.password.edit') }}" class="ml-1 font-bold text-amber-950 underline underline-offset-4">Ganti password sekarang</a>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
            <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Identitas Akun</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $profile['user']['name'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Username</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $profile['user']['username'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email Utama</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $profile['user']['email'] ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identitas</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $profile['user']['identity_type'] ?? '-' }}
                            @if ($profile['user']['identity_number_masked'])
                                <span class="text-slate-500">({{ $profile['user']['identity_number_masked'] }})</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </article>

            <aside class="rounded-2xl border border-blue-100 bg-blue-50 p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Kelengkapan Profil</h2>
                <div class="mt-4 flex items-end gap-2">
                    <span class="text-4xl font-bold text-blue-700">{{ $profile['completion']['percentage'] }}%</span>
                    <span class="pb-1 text-sm font-semibold text-slate-600">{{ $profile['completion']['completed'] }}/{{ $profile['completion']['total'] }} item</span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $profile['completion']['percentage'] }}%"></div>
                </div>
                <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-700">
                    @foreach ($profile['completion']['items'] as $item)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $item['complete'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $item['complete'] ? 'OK' : '!' }}
                            </span>
                            <span>
                                {{ $item['label'] }}
                                @if ($item['sensitive'])
                                    <span class="text-slate-500">(status saja, nilai tidak ditampilkan)</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </section>

        <section class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Keamanan Profil</h2>
            <ul class="mt-4 grid gap-3 text-sm leading-6 text-slate-700 md:grid-cols-3">
                <li class="rounded-xl bg-blue-50 p-4">Halaman ini hanya untuk profil milik akun yang sedang login.</li>
                <li class="rounded-xl bg-blue-50 p-4">Role, app access, status aktif, jabatan, dan data resmi tidak bisa diedit mandiri.</li>
                <li class="rounded-xl bg-blue-50 p-4">Password dikelola melalui halaman Ganti Password Core dan berlaku untuk aplikasi yang memverifikasi ke Core.</li>
            </ul>
        </section>

        <section class="grid gap-6">
            @forelse ($profile['profiles'] as $linkedProfile)
                <article class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-blue-700">{{ $linkedProfile['label'] }}</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $linkedProfile['name'] }}</h2>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $linkedProfile['status'] ?? 'status tidak tersedia' }}
                        </span>
                    </div>

                    <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $linkedProfile['identifier_label'] }}</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $linkedProfile['identifier'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email Profil</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $linkedProfile['email'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $linkedProfile['unit'] ?? $linkedProfile['unit_secondary'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telepon</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $linkedProfile['phone'] ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $linkedProfile['address'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Lahir</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ ($linkedProfile['birth_date_recorded'] ?? false) ? 'Tercatat' : 'Belum tercatat' }}
                            </dd>
                        </div>
                    </dl>
                </article>
            @empty
                <article class="rounded-2xl border border-dashed border-blue-200 bg-white p-8 text-center shadow-sm">
                    <h2 class="text-lg font-bold text-slate-950">Belum ada profil tertaut</h2>
                    <p class="mt-2 text-sm text-slate-600">Akun Anda belum terhubung ke profil mahasiswa, dosen, atau pegawai. Hubungi admin Core jika ini tidak sesuai.</p>
                </article>
            @endforelse
        </section>
    </main>
</body>
</html>
