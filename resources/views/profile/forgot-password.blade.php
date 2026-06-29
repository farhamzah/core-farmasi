<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f8ff] text-slate-950">
    <main class="mx-auto grid min-h-screen w-full max-w-5xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_420px] lg:px-8">
        <section class="hidden lg:block">
            <div class="inline-flex items-center gap-3 rounded-2xl border border-blue-100 bg-white/90 px-4 py-3 shadow-sm">
                <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-12 w-12 rounded-xl object-contain">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                    <p class="text-sm font-bold text-slate-700">Reset password akun pusat</p>
                </div>
            </div>
            <h1 class="mt-6 max-w-3xl text-5xl font-black leading-tight tracking-normal text-slate-950">Pulihkan akses akun Core</h1>
            <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                Masukkan email, username, NIM, NIDN/NIP, NUPTK, atau nomor pegawai. Jika cocok dengan akun aktif, Core akan mengirim link reset ke email terdaftar.
            </p>
        </section>

        <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-[0_24px_70px_rgba(30,64,175,0.16)]">
            <div class="h-1 bg-gradient-to-r from-blue-700 via-cyan-500 to-emerald-400"></div>
            <div class="p-6 sm:p-8">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-14 w-14 rounded-2xl border border-blue-100 bg-white object-contain p-1 shadow-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Core Farmasi UBP</p>
                        <h2 class="mt-1 text-3xl font-black tracking-normal text-slate-950">Lupa Password</h2>
                    </div>
                </div>

                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Link reset hanya dikirim ke email yang tersimpan di Core. Password baru langsung berlaku untuk aplikasi Farmasi yang terhubung.
                </p>

                @if (session('status'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">
                                OK
                            </span>
                            <div>
                                <p class="font-black">Permintaan reset sudah diproses.</p>
                                <p class="mt-1 leading-6">{{ session('status') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-black">Periksa data Anda.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.password.email') }}" class="mt-7 grid gap-5">
                    @csrf

                    <div>
                        <label for="login" class="text-sm font-bold text-slate-800">Email / Username / Nomor Identitas</label>
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

                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        Kirim Link Reset
                    </button>
                </form>

                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-7 text-slate-700">
                    Demi keamanan, halaman ini tidak memberi tahu apakah akun ditemukan atau tidak.
                </div>

                <p class="mt-5 text-center text-sm text-slate-500">
                    Ingat password? <a href="{{ route('profile.login') }}" class="font-bold text-blue-700 underline underline-offset-4">Kembali ke login profil</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
