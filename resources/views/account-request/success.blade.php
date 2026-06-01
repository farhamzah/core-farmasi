<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permohonan Diterima - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-8 sm:px-6 lg:px-8">
        <section class="w-full rounded-2xl border border-blue-100 bg-white p-6 text-center shadow-sm sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Permohonan Akun Diterima</h1>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Permohonan Anda sudah tersimpan dengan status pending. Admin Core akan melakukan verifikasi sebelum akun atau akses aplikasi diaktifkan.
            </p>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Tidak ada akun aktif, app access, token, atau sesi login yang dibuat dari pengiriman form ini.
            </p>
            <a href="{{ route('account-request.create') }}" class="mt-6 inline-flex items-center justify-center rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">
                Kirim Permohonan Lain
            </a>
        </section>
    </main>
</body>
</html>
