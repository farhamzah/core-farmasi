<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Permohonan Akun - Core Farmasi UBP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Permohonan Akun</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Form ini hanya membuat permohonan akun. Admin Core akan memverifikasi data sebelum akun aktif. Permohonan ini tidak otomatis membuat akses aplikasi, tidak membuat login instan, dan tidak meminta password.
            </p>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Periksa kembali data yang diisi.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('account-request.store') }}" class="mt-8 grid gap-5">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Jenis Pemohon</span>
                        <select name="request_type" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Pilih jenis pemohon</option>
                            @foreach ($requestTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('request_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Nama Lengkap</span>
                        <input name="name" value="{{ old('name') }}" required maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Nomor Telepon</span>
                        <input name="phone" value="{{ old('phone') }}" maxlength="50" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Nomor Identitas</span>
                        <input name="identity_number" value="{{ old('identity_number') }}" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">NIM</span>
                        <input name="student_number" value="{{ old('student_number') }}" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">NIDN/NIP Dosen</span>
                        <input name="lecturer_number" value="{{ old('lecturer_number') }}" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Nomor Pegawai</span>
                        <input name="employee_number" value="{{ old('employee_number') }}" maxlength="255" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Program Studi</span>
                        <select name="study_program_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Pilih jika relevan</option>
                            @foreach ($studyPrograms as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('study_program_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Departemen / Unit</span>
                        <select name="department_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Pilih jika relevan</option>
                            @foreach ($departments as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('department_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Aplikasi yang Dituju</span>
                        <select name="requested_app_code" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Opsional</option>
                            @foreach ($applications as $appCode => $name)
                                <option value="{{ $appCode }}" @selected(old('requested_app_code') === $appCode)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-slate-800">Role yang Diminta</span>
                        <input name="requested_role" value="{{ old('requested_role') }}" maxlength="255" placeholder="Opsional, contoh: mahasiswa" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </label>
                </div>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-slate-800">Catatan</span>
                    <textarea name="notes" rows="4" maxlength="5000" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes') }}</textarea>
                </label>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">
                    Permohonan akun akan diverifikasi Admin Core. Jangan mengirim password melalui form ini. Akses aplikasi hanya diberikan setelah admin menyetujui dan mengatur app access secara terpisah.
                </div>

                <div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Kirim Permohonan
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
