<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Saya - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .core-profile-avatar {
            align-items: center;
            aspect-ratio: 1 / 1;
            display: inline-flex;
            flex: 0 0 auto;
            justify-content: center;
            max-height: 5rem;
            max-width: 5rem;
            min-height: 5rem;
            min-width: 5rem;
            overflow: hidden;
            width: 5rem;
            height: 5rem;
        }

        .core-profile-avatar.is-hero {
            max-height: 6.5rem;
            max-width: 6.5rem;
            min-height: 6.5rem;
            min-width: 6.5rem;
            width: 6.5rem;
            height: 6.5rem;
        }

        .core-profile-avatar.is-small {
            max-height: 3.5rem;
            max-width: 3.5rem;
            min-height: 3.5rem;
            min-width: 3.5rem;
            width: 3.5rem;
            height: 3.5rem;
        }

        .core-profile-avatar > img {
            display: block;
            height: 100%;
            max-height: 100%;
            max-width: 100%;
            object-fit: cover;
            object-position: center top;
            width: 100%;
        }

        @media (max-width: 640px) {
            .core-profile-avatar.is-hero {
                max-height: 5.25rem;
                max-width: 5.25rem;
                min-height: 5.25rem;
                min-width: 5.25rem;
                width: 5.25rem;
                height: 5.25rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    @php
        $completion = $profile['completion'];
        $contact = $profile['contact_values'];
        $linkedProfiles = collect($profile['profiles'] ?? []);
        $primaryProfile = $linkedProfiles->first();
        $roleLabel = $primaryProfile['label'] ?? ucfirst((string) ($profile['user']['identity_type'] ?? 'Akun Core'));
        $contactComplete = filled($contact['phone'] ?? null) && filled($contact['address'] ?? null);
        $profilePhotoUrl = $profile['user']['profile_photo_url'] ?? null;
        $initial = strtoupper(substr((string) ($profile['user']['name'] ?? 'U'), 0, 1));
    @endphp

    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.12)]">
            <div class="grid gap-0 lg:grid-cols-[1fr_360px]">
                <section class="relative p-6 sm:p-8">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
                    <div class="flex flex-col gap-7 xl:flex-row xl:items-start xl:justify-between">
                        <div class="grid min-w-0 gap-5 sm:grid-cols-[auto_1fr] sm:items-start">
                            <div class="core-profile-avatar is-hero rounded-[1.75rem] border border-blue-100 bg-blue-50 text-3xl font-black text-blue-700 shadow-[0_18px_40px_rgba(30,64,175,0.16)]">
                                <span class="{{ $profilePhotoUrl ? 'hidden' : '' }}" data-photo-fallback>{{ $initial }}</span>
                                @if ($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $profile['user']['name'] ?? 'user' }}" onerror="this.classList.add('hidden'); this.previousElementSibling?.classList.remove('hidden');">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Core Farmasi UBP</p>
                                <h1 class="mt-3 break-words text-3xl font-black tracking-normal text-slate-950 sm:text-4xl">Profil Saya</h1>
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $roleLabel }}</span>
                                    <span class="rounded-full {{ ($profile['user']['active'] ?? false) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-3 py-1 text-xs font-bold">
                                        {{ ($profile['user']['active'] ?? false) ? 'Akun aktif' : 'Akun tidak aktif' }}
                                    </span>
                                    <span class="rounded-full {{ $completion['is_complete'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold">
                                        {{ $completion['is_complete'] ? 'Profil lengkap' : 'Profil belum lengkap' }}
                                    </span>
                                </div>
                                <p class="mt-5 max-w-3xl text-sm leading-7 text-slate-600">
                                    Data resmi dikelola terpusat oleh Admin Core. Kontak pribadi bisa diperbarui mandiri, sementara identitas akademik/kepegawaian, role, app access, dan jabatan tetap terkunci.
                                </p>
                            </div>
                        </div>

                        <div class="grid w-full shrink-0 grid-cols-1 gap-2 sm:grid-cols-3 xl:w-40 xl:grid-cols-1">
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                                Edit Profil
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
                </section>

                <aside class="border-t border-blue-100 bg-gradient-to-br from-blue-700 to-sky-600 p-6 text-white lg:border-l lg:border-t-0 sm:p-8">
                    <p class="text-sm font-semibold text-blue-100">Kelengkapan Profil</p>
                    <div class="mt-3 flex items-end gap-3">
                        <span class="text-5xl font-black">{{ $completion['percentage'] }}%</span>
                        <span class="pb-2 text-sm font-semibold text-blue-100">{{ $completion['completed'] }}/{{ $completion['total'] }} item</span>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/20">
                        <div class="h-full rounded-full bg-white" style="width: {{ $completion['percentage'] }}%"></div>
                    </div>
                    <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <div class="rounded-2xl bg-white/12 p-4">
                            <dt class="text-blue-100">Kontak</dt>
                            <dd class="mt-1 font-bold">{{ $contactComplete ? 'Lengkap' : 'Belum lengkap' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-white/12 p-4">
                            <dt class="text-blue-100">Profil Resmi</dt>
                            <dd class="mt-1 font-bold">{{ $linkedProfiles->isNotEmpty() ? 'Tertaut' : 'Belum tertaut' }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </header>

        @if ($user->must_change_password)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
                Anda wajib mengganti password awal sebelum menggunakan layanan.
                <a href="{{ route('profile.password.edit') }}" class="ml-1 font-black text-amber-950 underline underline-offset-4">Ganti password sekarang</a>
            </div>
        @endif

        @if (! $completion['is_complete'])
            <div class="rounded-2xl border border-amber-200 bg-white px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
                Profil Anda belum lengkap. Lengkapi telepon dan alamat agar layanan Farmasi dapat memakai kontak terbaru.
                <a href="{{ route('profile.edit') }}" class="ml-1 font-black text-amber-950 underline underline-offset-4">Lengkapi profil</a>
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <article class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="core-profile-avatar is-small rounded-2xl bg-blue-50 text-lg font-black text-blue-700">
                            <span class="{{ $profilePhotoUrl ? 'hidden' : '' }}" data-photo-fallback>{{ $initial }}</span>
                            @if ($profilePhotoUrl)
                                <img src="{{ $profilePhotoUrl }}" alt="Foto profil" onerror="this.classList.add('hidden'); this.previousElementSibling?.classList.remove('hidden');">
                            @endif
                        </div>
                        <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Identitas Akun</p>
                        <h2 class="mt-2 text-xl font-black text-slate-950">{{ $profile['user']['name'] ?? '-' }}</h2>
                        </div>
                    </div>
                    <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $profile['user']['identity_type'] ?? 'internal' }}</span>
                </div>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Username</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $profile['user']['username'] ?? '-' }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Email Utama</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $profile['user']['email'] ?? '-' }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Identitas Login</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-950">{{ $profile['user']['identity_number_masked'] ?? '-' }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Status Akses</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-950">{{ ($profile['user']['active'] ?? false) ? 'Aktif' : 'Tidak aktif' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Kontak Aman</p>
                <h2 class="mt-2 text-xl font-black text-slate-950">Kontak Saya</h2>
                <dl class="mt-6 space-y-4">
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-blue-700">Telepon</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $contact['phone'] ?? '-' }}</dd>
                    </div>
                    <div class="rounded-2xl bg-blue-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-blue-700">Alamat</dt>
                        <dd class="mt-1 whitespace-pre-line break-words text-sm font-semibold text-slate-950">{{ $contact['address'] ?? '-' }}</dd>
                    </div>
                    @if (filled($contact['alternate_email'] ?? null))
                        <div class="rounded-2xl bg-blue-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-wide text-blue-700">Email Alternatif</dt>
                            <dd class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $contact['alternate_email'] }}</dd>
                        </div>
                    @endif
                </dl>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
            <div class="grid gap-6">
                @forelse ($linkedProfiles as $linkedProfile)
                    <article class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">{{ $linkedProfile['label'] }}</p>
                                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $linkedProfile['name'] }}</h2>
                                <p class="mt-2 text-sm text-slate-600">{{ $linkedProfile['unit'] ?? $linkedProfile['unit_secondary'] ?? 'Unit belum tersedia' }}</p>
                            </div>
                            <span class="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $linkedProfile['status'] ?? 'status tidak tersedia' }}</span>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($linkedProfile['official_identifiers'] ?? [] as $identifier)
                                @if (filled($identifier['value'] ?? null))
                                    <span class="rounded-full {{ ($identifier['sensitive'] ?? false) ? 'bg-slate-100 text-slate-700' : 'bg-blue-50 text-blue-700' }} px-3 py-1 text-xs font-bold">
                                        {{ $identifier['label'] }}: {{ $identifier['value'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-6 grid gap-4 lg:grid-cols-3">
                            @foreach ($linkedProfile['profile_sections'] ?? [] as $sectionTitle => $items)
                                <section class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                    <h3 class="text-sm font-black text-slate-950">{{ $sectionTitle }}</h3>
                                    <dl class="mt-4 space-y-3">
                                        @foreach ($items as $label => $value)
                                            <div>
                                                <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                                                <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ filled($value) ? $value : '-' }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </section>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <article class="rounded-3xl border border-dashed border-blue-200 bg-white p-8 text-center shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Profil Resmi</p>
                        <h2 class="mt-3 text-xl font-black text-slate-950">Profil resmi belum ditautkan</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">Akun ini belum terhubung ke data mahasiswa, dosen, atau tendik. Kontak tetap bisa disimpan di akun Core sambil menunggu Admin Core menautkan data resmi.</p>
                        <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                            Isi Kontak Aman
                        </a>
                    </article>
                @endforelse
            </div>

            <aside class="grid gap-6">
                <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Checklist</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Kelengkapan</h2>
                    <ul class="mt-5 space-y-3">
                        @foreach ($completion['items'] as $item)
                            <li class="flex gap-3 rounded-2xl bg-slate-50 p-3 text-sm text-slate-700">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $item['complete'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $item['complete'] ? 'OK' : '!' }}
                                </span>
                                <span class="pt-0.5 font-semibold">{{ $item['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Standar Profil</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Data Utama</h2>
                    <div class="mt-5 space-y-4 text-sm">
                        @foreach ($profile['profile_standards'] as $type => $items)
                            <div>
                                <h3 class="font-black text-slate-900">{{ $type }}</h3>
                                <p class="mt-1 leading-6 text-slate-600">{{ implode(', ', $items) }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
                    <h2 class="text-lg font-black text-slate-950">Keamanan Profil</h2>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                        <li>Password bisa diganti kapan saja dari halaman Ganti Password.</li>
                        <li>NIK/KTP dimasking di portal karena termasuk data sensitif.</li>
                        <li>Role, app access, status, dan jabatan hanya diubah oleh Admin Core.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
