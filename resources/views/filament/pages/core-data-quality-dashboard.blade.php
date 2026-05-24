<x-filament-panels::page>
    @php
        $summary = $this->dataQualitySummary;
        $sections = [
            'Identity & Users' => [
                'description' => 'Akun, role global, identity, dan akses aplikasi user.',
                'data' => $summary['identity'],
                'accent' => 'blue',
            ],
            'Master Profiles' => [
                'description' => 'Kelengkapan link profil mahasiswa, dosen, dan tendik/staff.',
                'data' => $summary['profiles'],
                'accent' => 'sky',
            ],
            'App Access' => [
                'description' => 'Konsistensi app registry, app role catalog, dan user app access.',
                'data' => $summary['app_access'],
                'accent' => 'indigo',
            ],
            'Leadership' => [
                'description' => 'Kondisi jabatan resmi seperti Dekan dan Kaprodi.',
                'data' => $summary['leadership'],
                'accent' => 'cyan',
            ],
            'Imports' => [
                'description' => 'Status batch import, failed import, dan rollback/manual review.',
                'data' => $summary['imports'],
                'accent' => 'blue',
            ],
        ];

        $isProblemMetric = function (string $label): bool {
            return str_contains($label, 'without')
                || str_contains($label, 'missing')
                || str_contains($label, 'duplicate')
                || str_contains($label, 'failed')
                || str_contains($label, 'manual_review')
                || str_contains($label, 'warning')
                || str_contains($label, 'unknown')
                || str_contains($label, 'inactive_users_with')
                || str_contains($label, 'expired_but_active')
                || str_contains($label, 'multiple_current');
        };
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50 dark:border-blue-500/20 dark:bg-gray-950 dark:ring-blue-500/10">
            <div class="border-l-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 dark:from-blue-950/40 dark:via-gray-950 dark:to-sky-950/20">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Read-only quality monitor</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Ringkasan Kualitas Data</h2>
                        <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Dashboard ini membantu audit data master, identity, akses aplikasi, jabatan struktural, dan batch import tanpa membuat perubahan otomatis.
                        </p>
                    </div>
                    <span class="inline-flex w-fit rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">
                        Tidak ada auto-fix
                    </span>
                </div>
            </div>
        </section>

        @foreach ($sections as $sectionTitle => $section)
            @php
                $problemCount = collect($section['data']['metrics'])
                    ->filter(fn ($value, $label) => $isProblemMetric((string) $label) && (int) $value > 0)
                    ->count();
            @endphp

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                        <span>{{ $sectionTitle }}</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    {{ $section['description'] }}
                </x-slot>

                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">
                        {{ count($section['data']['metrics']) }} metrics
                    </span>
                    <span class="rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $problemCount > 0 ? 'bg-warning-50 text-warning-700 ring-warning-100 dark:bg-warning-500/10 dark:text-warning-200 dark:ring-warning-500/20' : 'bg-success-50 text-success-700 ring-success-100 dark:bg-success-500/10 dark:text-success-200 dark:ring-success-500/20' }}">
                        {{ $problemCount > 0 ? $problemCount . ' issue groups' : 'Good state' }}
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($section['data']['metrics'] as $label => $value)
                        @php
                            $isProblem = $isProblemMetric((string) $label);
                            $hasProblem = $isProblem && (int) $value > 0;
                        @endphp

                        <div class="rounded-lg border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-950 {{ $hasProblem ? 'border-warning-200 ring-1 ring-warning-100 dark:border-warning-500/30 dark:ring-warning-500/10' : 'border-blue-100 ring-1 ring-blue-50 dark:border-gray-800 dark:ring-blue-500/5' }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                    {{ str($label)->replace('_', ' ')->headline() }}
                                </p>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $hasProblem ? 'bg-warning-100 text-warning-800 dark:bg-warning-500/15 dark:text-warning-200' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200' }}">
                                    {{ $hasProblem ? 'Review' : 'OK' }}
                                </span>
                            </div>
                            <p class="mt-3 text-3xl font-semibold {{ $hasProblem ? 'text-warning-700 dark:text-warning-300' : 'text-gray-950 dark:text-white' }}">
                                {{ $value }}
                            </p>
                        </div>
                    @endforeach
                </div>

                @if (! empty($section['data']['examples']))
                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        @foreach ($section['data']['examples'] as $exampleLabel => $items)
                            <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ str($exampleLabel)->replace('_', ' ')->headline() }}
                                    </p>
                                    <span class="rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-100 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-800">
                                        Max sample
                                    </span>
                                </div>

                                @if (empty($items))
                                    <p class="mt-3 rounded-md bg-success-50 p-3 text-sm text-success-700 ring-1 ring-success-100 dark:bg-success-500/10 dark:text-success-200 dark:ring-success-500/20">
                                        Tidak ada contoh.
                                    </p>
                                @else
                                    <div class="mt-3 space-y-2">
                                        @foreach ($items as $item)
                                            <div class="rounded-md bg-blue-50/50 p-3 text-xs text-gray-700 ring-1 ring-blue-100 dark:bg-blue-500/5 dark:text-gray-300 dark:ring-blue-500/10">
                                                @foreach ($item as $key => $value)
                                                    <span class="mr-3 inline-block">
                                                        <span class="font-semibold">{{ str($key)->replace('_', ' ')->headline() }}:</span>
                                                        {{ is_bool($value) ? ($value ? 'true' : 'false') : ($value ?? '-') }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
