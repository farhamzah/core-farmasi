<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    <main class="mx-auto grid min-h-screen w-full max-w-5xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_420px] lg:px-8">
        <section class="hidden lg:block">
            <div class="inline-flex items-center gap-3 rounded-2xl border border-blue-100 bg-white/90 px-4 py-3 shadow-sm">
                <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-12 w-12 rounded-xl object-contain">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                    <p class="text-sm font-bold text-slate-700">Password baru untuk akun pusat</p>
                </div>
            </div>
            <h1 class="mt-6 max-w-3xl text-5xl font-black leading-tight tracking-normal text-slate-950">Buat password baru</h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                Setelah berhasil, password lama tidak bisa dipakai lagi dan password baru langsung aktif untuk layanan yang memverifikasi ke Core.
            </p>
        </section>

        <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.16)]">
            <div class="h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-14 w-14 rounded-2xl border border-blue-100 bg-white object-contain p-1 shadow-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                        <h2 class="mt-1 text-3xl font-black tracking-normal text-slate-950">Reset Password</h2>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-black">Reset password belum berhasil.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.password.reset.update') }}" class="mt-7 grid gap-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="text-sm font-bold text-slate-800">Email Akun</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="email"
                            required
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label for="password" class="text-sm font-bold text-slate-800">Password Baru</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                required
                                class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-0"
                            >
                            <button type="button" data-password-toggle data-target="password" class="shrink-0 border-l border-slate-200 px-4 text-sm font-bold text-blue-700 transition hover:bg-blue-50 focus:outline-none">Lihat</button>
                        </div>
                        <p class="mt-2 text-xs font-medium text-slate-500">Gunakan minimal 8 karakter.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="text-sm font-bold text-slate-800">Konfirmasi Password Baru</label>
                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm focus-within:border-blue-600 focus-within:ring-4 focus-within:ring-blue-100">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                class="min-w-0 flex-1 border-0 bg-white px-4 py-3 text-sm text-slate-900 focus:outline-none focus:ring-0"
                            >
                            <button type="button" data-password-toggle data-target="password_confirmation" class="shrink-0 border-l border-slate-200 px-4 text-sm font-bold text-blue-700 transition hover:bg-blue-50 focus:outline-none">Lihat</button>
                        </div>
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Simpan Password Baru
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-slate-500">
                    Link bermasalah? <a href="{{ route('profile.password.request') }}" class="font-bold text-blue-700 underline underline-offset-4">Kirim ulang link reset</a>
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
                    input.focus();
                });
            });
        });
    </script>
</body>
</html>
