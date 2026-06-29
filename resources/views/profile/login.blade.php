<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Profile Portal - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    <main class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_460px] lg:px-8">
        <section class="hidden lg:block">
            <div class="inline-flex items-center gap-3 rounded-2xl border border-blue-100 bg-white/90 px-4 py-3 shadow-sm">
                <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-12 w-12 rounded-xl object-contain">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                    <p class="text-sm font-bold text-slate-700">Identity & master data center</p>
                </div>
            </div>
            <h1 class="mt-6 max-w-3xl text-5xl font-black leading-tight tracking-normal text-slate-950">Portal Profil Core Farmasi</h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                Satu akun pusat untuk identitas resmi, kontak aman, dan password layanan Farmasi yang terhubung ke Core.
            </p>

            <div class="mt-8 grid max-w-2xl gap-4 sm:grid-cols-3">
                <div class="rounded-3xl border border-blue-100 bg-white p-5 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Login</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">NIM, NIDN/NIP, NUPTK, nomor pegawai, email, atau username.</p>
                </div>
                <div class="rounded-3xl border border-blue-100 bg-white p-5 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Kontak</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Telepon dan alamat dapat diperbarui mandiri.</p>
                </div>
                <div class="rounded-3xl border border-blue-100 bg-white p-5 shadow-[0_18px_48px_rgba(15,23,42,0.06)]">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Password</p>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">Password Core bisa diganti setelah masuk portal.</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.16)]">
            <div class="h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-14 w-14 rounded-2xl border border-blue-100 bg-white object-contain p-1 shadow-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                        <h2 class="mt-1 text-3xl font-black tracking-normal text-slate-950">Masuk Profil</h2>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Gunakan username dari NIM, NIDN/NIP, NUPTK, nomor kepegawaian, email, atau username yang diberikan Admin Core.
                </p>

                @if (session('status'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-black">Login gagal.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.login.store') }}" class="mt-7 grid gap-5">
                    @csrf

                    <div>
                        <label for="login" class="text-sm font-bold text-slate-800">Username / Email / Nomor Identitas</label>
                        <input
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            autocomplete="username"
                            required
                            autofocus
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label for="password" class="text-sm font-bold text-slate-800">Password</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-0"
                            >
                            <button
                                type="button"
                                data-password-toggle
                                data-target="password"
                                class="shrink-0 border-l border-slate-200 px-4 text-sm font-black text-blue-700 transition hover:bg-blue-50 focus:outline-none"
                                aria-controls="password"
                                aria-label="Lihat password"
                            >Lihat</button>
                        </div>
                        <div class="mt-2 flex justify-end">
                            <a href="{{ route('profile.password.request') }}" class="text-xs font-black text-blue-700 underline underline-offset-4">Lupa Password?</a>
                        </div>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Masuk Portal Profil
                    </button>
                </form>

                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-7 text-slate-700">
                    Password Core berlaku untuk aplikasi Farmasi lain yang memakai verifikasi Core. Portal ini bukan SSO dan tidak membuat token login aplikasi lain.
                </div>

                <p class="mt-5 text-center text-sm text-slate-500">
                    Admin Core? <a href="/admin/login" class="font-bold text-blue-700 underline underline-offset-4">Masuk melalui /admin/login</a>
                </p>
            </div>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.target);

                    if (! input) {
                        return;
                    }

                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    button.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
                    button.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Lihat password');
                    input.focus();
                });
            });
        });
    </script>
</body>
</html>
