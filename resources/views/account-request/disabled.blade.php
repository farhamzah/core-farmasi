<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Akun Dinonaktifkan - Core Farmasi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <section class="w-full max-w-2xl rounded-xl border border-blue-100 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-950">Registrasi akun tidak dibuka secara mandiri.</h1>
            <div class="mt-5 space-y-3 text-base leading-7 text-slate-600">
                <p>Akun dibuat oleh Admin Core.</p>
                <p>Silakan hubungi admin, program studi, atau tata usaha sesuai kebutuhan.</p>
            </div>
            <a href="/admin/login" class="mt-8 inline-flex rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                Masuk Core
            </a>
        </section>
    </main>
</body>
</html>
