@php
    $toneClasses = [
        'blue' => 'border-blue-100 bg-blue-50/60 text-blue-700 ring-blue-100',
        'sky' => 'border-sky-100 bg-sky-50/60 text-sky-700 ring-sky-100',
        'indigo' => 'border-indigo-100 bg-indigo-50/60 text-indigo-700 ring-indigo-100',
        'emerald' => 'border-emerald-100 bg-emerald-50/60 text-emerald-700 ring-emerald-100',
        'amber' => 'border-amber-100 bg-amber-50/70 text-amber-800 ring-amber-100',
        'slate' => 'border-slate-100 bg-slate-50/70 text-slate-700 ring-slate-100',
    ];
@endphp

<x-filament-widgets::widget>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
            <div class="border-l-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-emerald-50/50 p-6">
                <div class="grid gap-6 xl:grid-cols-[1fr_360px] xl:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Core Farmasi UBP</p>
                        <h2 class="mt-2 text-3xl font-semibold text-gray-950">Pusat Identitas & Master Data</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
                            Pantau akun, profil akademik, akses aplikasi, dan kualitas data dari satu dashboard admin.
                            Core tetap menjadi sumber utama untuk KP, TU, TA, dan Lab.
                        </p>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Link registrasi calon user</p>
                                <p class="mt-2 break-all text-sm font-semibold text-gray-950">{{ $registrationUrl }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $publicRegistrationEnabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-100' }}">
                                {{ $publicRegistrationEnabled ? 'Aktif lokal' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-gray-500">
                            Calon user masuk waiting list. Akun, role, dan app access tetap perlu approval Admin Core.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($primaryMetrics as $metric)
                <a href="{{ $metric['url'] }}" class="group rounded-lg border bg-white p-5 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md {{ $toneClasses[$metric['tone']] ?? $toneClasses['blue'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $metric['label'] }}</p>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold shadow-sm ring-1 ring-black/5">
                            {{ $metric['status'] }}
                        </span>
                    </div>
                    <p class="mt-4 text-3xl font-semibold text-gray-950">{{ $metric['value'] }}</p>
                    <p class="mt-1 text-sm text-gray-600">{{ $metric['detail'] }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <section class="rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
                <div class="border-b border-blue-100 bg-blue-50/60 px-5 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-950">Peta Master Data</h3>
                            <p class="mt-1 text-sm text-gray-600">Ringkasan data utama yang sudah tersedia di Core.</p>
                        </div>
                        <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}" class="inline-flex w-fit rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100">
                            Kelola User
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($masterData as $item)
                        <a @if ($item['url']) href="{{ $item['url'] }}" @endif class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm ring-1 ring-gray-50 transition hover:border-blue-100 hover:bg-blue-50/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $item['label'] }}</p>
                            <p class="mt-3 text-2xl font-semibold text-gray-950">{{ $item['value'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
                <div class="border-b border-blue-100 bg-blue-50/60 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-950">Sinyal Kualitas Data</h3>
                    <p class="mt-1 text-sm text-gray-600">Angka ini tidak memperbaiki otomatis, hanya memberi prioritas review.</p>
                </div>

                <div class="space-y-3 p-5">
                    @foreach ($qualitySignals as $signal)
                        <div class="flex items-center justify-between gap-4 rounded-lg border p-4 ring-1 {{ $toneClasses[$signal['tone']] ?? $toneClasses['blue'] }}">
                            <span class="text-sm font-semibold text-gray-700">{{ $signal['label'] }}</span>
                            <span class="text-2xl font-semibold text-gray-950">{{ $signal['value'] }}</span>
                        </div>
                    @endforeach
                    <a href="{{ \App\Filament\Pages\CoreDataQualityDashboard::getUrl() }}" class="block rounded-md bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Buka Data Quality
                    </a>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <section class="rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
                <div class="border-b border-blue-100 bg-blue-50/60 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-950">Readiness Aplikasi</h3>
                    <p class="mt-1 text-sm text-gray-600">Status registry dan access count untuk aplikasi consumer utama.</p>
                </div>

                <div class="grid gap-3 p-5 md:grid-cols-2">
                    @foreach ($appReadiness as $app)
                        <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm ring-1 ring-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-950">{{ $app['name'] }}</p>
                                    <p class="mt-1 text-xs font-medium text-gray-500">{{ $app['code'] }}</p>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $app['registered'] && $app['active'] ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-100' }}">
                                    {{ $app['registered'] && $app['active'] ? 'Ready' : 'Review' }}
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-md bg-blue-50/60 p-3">
                                    <p class="text-xs font-semibold uppercase text-gray-500">Access</p>
                                    <p class="mt-1 text-xl font-semibold text-gray-950">{{ $app['access_count'] }}</p>
                                </div>
                                <div class="rounded-md bg-blue-50/60 p-3">
                                    <p class="text-xs font-semibold uppercase text-gray-500">Role</p>
                                    <p class="mt-1 text-xl font-semibold text-gray-950">{{ $app['role_count'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
                <div class="border-b border-blue-100 bg-blue-50/60 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-950">Aksi Cepat</h3>
                    <p class="mt-1 text-sm text-gray-600">Shortcut kerja harian Admin Core.</p>
                </div>

                <div class="space-y-3 p-5">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="block rounded-lg border border-gray-100 bg-white p-4 shadow-sm ring-1 ring-gray-50 transition hover:border-blue-100 hover:bg-blue-50/40">
                            <p class="text-sm font-semibold text-gray-950">{{ $action['label'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-gray-600">{{ $action['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50">
            <div class="border-b border-blue-100 bg-blue-50/60 px-5 py-4">
                <h3 class="text-lg font-semibold text-gray-950">Permohonan Terbaru</h3>
                <p class="mt-1 text-sm text-gray-600">Waiting list calon user dari form registrasi publik.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($latestRequests as $request)
                    <a href="{{ \App\Filament\Resources\AccountRequestResource::getUrl('edit', ['record' => $request]) }}" class="grid gap-2 px-5 py-4 transition hover:bg-blue-50/40 md:grid-cols-[1fr_220px_140px_120px] md:items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-950">{{ $request->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $request->email }}</p>
                        </div>
                        <p class="text-sm text-gray-600">{{ \App\Models\AccountRequest::typeOptions()[$request->request_type] ?? $request->request_type }}</p>
                        <span class="w-fit rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">{{ str($request->status)->headline() }}</span>
                        <p class="text-xs text-gray-500 md:text-right">{{ $request->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</p>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-sm font-semibold text-gray-950">Belum ada permohonan akun.</p>
                        <p class="mt-1 text-xs text-gray-500">Bagikan link registrasi ke calon user jika ingin mulai menerima waiting list.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-widgets::widget>
