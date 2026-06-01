<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        <header class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Ganti Password</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Password ini berlaku untuk aplikasi Farmasi yang menggunakan verifikasi Core. Jangan bagikan password kepada siapa pun.
            </p>
        </header>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Periksa kembali password Anda.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($user->must_change_password)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                Anda wajib mengganti password awal sebelum menggunakan layanan.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password.update') }}" class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div class="grid gap-5">
                <div>
                    <label for="current_password" class="text-sm font-semibold text-slate-800">Password Saat Ini</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div>
                    <label for="password" class="text-sm font-semibold text-slate-800">Password Baru</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="mt-2 text-xs text-slate-500">Gunakan minimal 8 karakter dan hindari password yang mudah ditebak.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="text-sm font-semibold text-slate-800">Konfirmasi Password Baru</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                Core hanya menyimpan password dalam bentuk hash. Password tidak ditampilkan di profil, log, report, atau API.
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Kembali ke Profil
                </a>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Simpan Password
                </button>
            </div>
        </form>
    </main>
</body>
</html>
