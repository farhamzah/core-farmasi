<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Profile Portal - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-10 sm:px-6">
        <section class="rounded-2xl border border-blue-100 bg-white p-7 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Portal Profil Core Farmasi</h1>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Gunakan username dari NIM, NIDN/NIP, atau nomor kepegawaian dan password awal dari Admin Core.
            </p>

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Login gagal.</p>
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
                    <label for="login" class="text-sm font-semibold text-slate-800">Username / Email / Nomor Identitas</label>
                    <input
                        id="login"
                        name="login"
                        type="text"
                        value="{{ old('login') }}"
                        autocomplete="username"
                        required
                        autofocus
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div>
                    <label for="password" class="text-sm font-semibold text-slate-800">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Masuk Profile Portal
                </button>
            </form>

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-slate-700">
                Password Core berlaku untuk aplikasi Farmasi lain yang memakai verifikasi Core. Halaman ini bukan SSO dan tidak membuat token login aplikasi lain.
            </div>

            <p class="mt-5 text-center text-sm text-slate-500">
                Admin Core? <a href="/admin/login" class="font-semibold text-blue-700 underline underline-offset-4">Masuk melalui /admin/login</a>
            </p>
        </section>
    </main>
</body>
</html>
