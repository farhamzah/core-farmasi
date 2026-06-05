<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.12)]">
            <div class="relative p-6 sm:p-8">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex gap-4">
                        <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-16 w-16 shrink-0 rounded-2xl border border-blue-100 bg-white object-contain p-1.5 shadow-sm">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-700">Core Farmasi UBP</p>
                            <h1 class="mt-3 text-3xl font-black tracking-normal text-slate-950 sm:text-4xl">Ganti Password</h1>
                            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">
                                Password ini berlaku untuk aplikasi Farmasi yang menggunakan verifikasi Core. Gunakan password yang kuat dan jangan membagikannya kepada siapa pun.
                            </p>
                        </div>
                    </div>
                    <div class="grid shrink-0 grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                        <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm font-bold text-blue-700 shadow-sm transition hover:bg-blue-50">
                            Lihat Profil
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
                <p class="font-black">Periksa kembali password Anda.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($user->must_change_password)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-900 shadow-sm">
                Anda wajib mengganti password awal sebelum menggunakan layanan.
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[1fr_330px]">
            <form method="POST" action="{{ route('profile.password.update') }}" class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)] sm:p-7">
                @csrf
                @method('PUT')

                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Keamanan Akun</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">Perbarui Password Core</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Masukkan password saat ini untuk memastikan perubahan dilakukan oleh pemilik akun. Password baru langsung berlaku untuk portal dan aplikasi yang terhubung ke Core.
                </p>

                <div class="mt-6 grid gap-5">
                    <div>
                        <label for="current_password" class="text-sm font-bold text-slate-800">Password Saat Ini</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label for="password" class="text-sm font-bold text-slate-800">Password Baru</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        >
                        <p class="mt-2 text-xs font-medium text-slate-500">Gunakan minimal 8 karakter. Hindari nama, tanggal lahir, atau pola yang mudah ditebak.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-bold text-slate-800">Konfirmasi Password Baru</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                </div>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Kembali ke Profil
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Simpan Password
                    </button>
                </div>
            </form>

            <aside class="grid gap-6">
                <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Proteksi</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">Prinsip Aman</h2>
                    <ul class="mt-5 space-y-3 text-sm leading-6 text-slate-700">
                        <li class="rounded-2xl bg-blue-50 p-4">Core hanya menyimpan password dalam bentuk hash.</li>
                        <li class="rounded-2xl bg-blue-50 p-4">Password tidak ditampilkan di profil, log, report, atau API.</li>
                        <li class="rounded-2xl bg-blue-50 p-4">Setelah berhasil, password lama tidak bisa dipakai lagi.</li>
                    </ul>
                </section>

                <section class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
                    <h2 class="text-lg font-black text-slate-950">Butuh Bantuan?</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-700">
                        Jika lupa password atau akun terkunci, hubungi Admin Core. Jangan minta orang lain mengganti password tanpa verifikasi identitas.
                    </p>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
