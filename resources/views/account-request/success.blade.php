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
        <section class="w-full overflow-hidden rounded-2xl border border-blue-100 bg-white text-center shadow-sm">
            <div class="border-t-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-emerald-50/50 px-6 py-8 sm:px-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-100 bg-white shadow-sm">
                    <img src="{{ asset('images/logo-fakultas-farmasi-ubp.png') }}" alt="Logo Fakultas Farmasi UBP" class="h-12 w-12 object-contain">
                </div>
                <p class="mt-5 text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
                <h1 class="mt-2 text-3xl font-bold leading-tight text-slate-950">Permohonan Akun Diterima</h1>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-6 text-slate-600">
                    Permohonan Anda sudah tersimpan dan masuk antrean verifikasi Admin Core.
                </p>
            </div>

            <div class="px-6 py-6 sm:px-10">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                    Akun, akses aplikasi, token, atau sesi login tidak dibuat otomatis dari pengiriman form ini. Admin akan mengaktifkan akun setelah data diverifikasi.
                </div>

                <a href="{{ route('account-request.create') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-lg border border-blue-200 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 sm:w-auto sm:py-2.5">
                    Kirim Permohonan Lain
                </a>
            </div>
        </section>
    </main>
</body>
</html>
