<x-filament-panels::page>
    @php
        $apps = $this->apps;
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50 dark:border-blue-500/20 dark:bg-gray-950 dark:ring-blue-500/10">
            <div class="border-l-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-cyan-50 p-5 dark:from-blue-950/40 dark:via-gray-950 dark:to-cyan-950/20">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Internal navigation</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Launcher Internal</h2>
                        <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Aplikasi yang tampil di sini berasal dari akses aktif akun Anda. Link hanya navigasi; aplikasi tujuan tetap wajib login sendiri.
                        </p>
                    </div>
                    <span class="inline-flex w-fit rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">
                        Tanpa SSO
                    </span>
                </div>
            </div>
        </section>

        <x-filament::section>
            <x-slot name="heading">
                Aplikasi Saya
            </x-slot>

            <x-slot name="description">
                Tidak ada token, auto-login, atau bypass autentikasi aplikasi tujuan.
            </x-slot>

            @if (empty($apps))
                <div class="rounded-lg border border-dashed border-blue-200 bg-blue-50/60 p-6 text-sm text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                    <p class="font-semibold">Belum ada akses aplikasi aktif untuk akun ini.</p>
                    <p class="mt-1 text-blue-700 dark:text-blue-300">Hubungi admin Core jika Anda membutuhkan akses aplikasi internal.</p>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($apps as $app)
                        <div class="group flex min-h-64 flex-col overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50 transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-950 dark:ring-blue-500/5">
                            <div class="h-1.5 bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400"></div>

                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-base font-semibold text-gray-950 dark:text-white">
                                            {{ $app['name'] }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">
                                            {{ $app['app_code'] }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($app['requires_login'])
                                            <span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">
                                                Wajib login
                                            </span>
                                        @endif

                                        @if ($app['is_sensitive'])
                                            <span class="rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-warning-100 dark:bg-warning-500/10 dark:text-warning-200 dark:ring-warning-500/20">
                                                Internal
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <p class="mt-4 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ $app['description'] ?: 'Tidak ada deskripsi aplikasi.' }}
                                </p>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    @forelse ($app['roles'] as $role)
                                        <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-800 ring-1 ring-sky-100 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/20">
                                            {{ $role['name'] }}
                                        </span>
                                    @empty
                                        <span class="rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-100 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-800">
                                            Akses aktif
                                        </span>
                                    @endforelse
                                </div>

                                <div class="mt-auto pt-6">
                                    @if ($app['is_disabled'])
                                        <button type="button" disabled class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                            {{ $app['disabled_reason'] }}
                                        </button>
                                    @else
                                        <a href="{{ $app['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
                                            Buka Aplikasi
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
