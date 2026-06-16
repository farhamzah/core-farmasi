<x-filament-panels::page>
    @php
        $counts = $previewResult['counts'] ?? null;
        $samples = collect($previewResult['samples'] ?? []);
        $blockers = $previewResult['blockers'] ?? [];
        $warnings = $previewResult['warnings'] ?? [];
        $canApply = (bool) ($previewResult['can_apply'] ?? false);
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50 dark:border-blue-500/20 dark:bg-gray-950 dark:ring-blue-500/10">
            <div class="border-l-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 dark:from-blue-950/40 dark:via-gray-950 dark:to-sky-950/20">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Bulk access guardrail</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Pemberian Akses Kolektif</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Gunakan untuk memberi akses aplikasi ke banyak user sekaligus. Sistem akan preview dulu, skip akses yang sudah ada, dan tidak membuat duplikasi.
                        </p>
                    </div>
                    <span class="inline-flex w-fit rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">
                        Preview dulu, apply belakangan
                    </span>
                </div>
            </div>
        </section>

        <form wire:submit.prevent="preview" class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">1. Aplikasi dan Role</x-slot>
                <x-slot name="description">Pilih aplikasi tujuan dan role aplikasi yang akan diberikan. Role ini bukan role global Core.</x-slot>

                <div class="grid gap-5 lg:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Aplikasi</span>
                        <select wire:model.live="appCode" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Pilih aplikasi</option>
                            @foreach ($this->applicationOptions as $code => $name)
                                <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                            @endforeach
                        </select>
                        @error('appCode') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Role Aplikasi</span>
                        <select wire:model="roleSlug" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Pilih role aplikasi</option>
                            @foreach ($this->roleOptions as $slug => $label)
                                <option value="{{ $slug }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pilihan role otomatis mengikuti aplikasi.</p>
                        @error('roleSlug') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </label>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">2. Target Kolektif</x-slot>
                <x-slot name="description">Target bisa berdasarkan jenis akun, role global, prefix NIM/NIDN, program studi, departemen, atau jenis tendik.</x-slot>

                <div class="grid gap-5 lg:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Filter Target</span>
                        <select wire:model.live="targetScope" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach ($this->targetScopeOptions as $scope => $label)
                                <option value="{{ $scope }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('targetScope') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Nilai Target</span>
                        @if ($this->targetValueIsFreeText())
                            <input
                                wire:model="targetValue"
                                type="text"
                                maxlength="20"
                                placeholder="{{ $this->targetValuePlaceholder() }}"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->targetValueHelpText() }}</p>
                        @else
                            <select wire:model="targetValue" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="">Pilih nilai target</option>
                                @foreach ($this->targetValueOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('targetValue') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </label>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">3. Opsi Aman</x-slot>
                <x-slot name="description">Default aman: user tidak aktif tidak diberi akses, akses lama yang nonaktif boleh diaktifkan ulang jika dicentang.</x-slot>

                <div class="grid gap-5 lg:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <input wire:model="reactivateExisting" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-950 dark:text-white">Aktifkan ulang akses lama nonaktif</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Jika akses user untuk aplikasi dan role ini pernah ada tetapi nonaktif, sistem akan mengaktifkannya lagi.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <input wire:model="includeInactive" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span>
                            <span class="block text-sm font-semibold text-gray-950 dark:text-white">Tampilkan user tidak aktif di preview</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">User tidak aktif tetap hanya ditampilkan sebagai skip, tidak diberi akses.</span>
                        </span>
                    </label>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Mulai Aktif</span>
                        <input wire:model="activatedAt" type="datetime-local" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kosongkan untuk memakai waktu apply.</p>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Nonaktif Pada</span>
                        <input wire:model="deactivatedAt" type="datetime-local" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Opsional untuk akses sementara.</p>
                        @error('deactivatedAt') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                    </label>
                </div>
            </x-filament::section>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-filament::button type="submit" icon="heroicon-o-eye">
                    Preview Target
                </x-filament::button>
            </div>
        </form>

        @if ($previewResult)
            <x-filament::section>
                <x-slot name="heading">Preview Hasil</x-slot>
                <x-slot name="description">Belum ada data yang ditulis sampai tombol Apply Bulk Access dijalankan.</x-slot>

                @if ($blockers)
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-200">
                        <p class="font-semibold">Belum aman dijalankan:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($blockers as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($warnings)
                    <div class="mt-4 rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-900 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-200">
                        <p class="font-semibold">Catatan:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($warnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($counts)
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            'matched_users' => 'User cocok',
                            'planned_insert' => 'Akses baru',
                            'planned_reactivate' => 'Reaktivasi',
                            'skipped_existing' => 'Sudah ada',
                            'skipped_inactive_user' => 'User nonaktif skip',
                            'skipped_inactive_access' => 'Akses nonaktif skip',
                            'users_with_other_roles_same_app' => 'Punya role app lain',
                        ] as $key => $label)
                            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $counts[$key] ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($samples->isNotEmpty())
                    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">User</th>
                                        <th class="px-4 py-3">Identitas</th>
                                        <th class="px-4 py-3">Action</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                                    @foreach ($samples as $row)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-950 dark:text-white">{{ $row['name'] }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['email'] }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                {{ $row['student_number'] ?? $row['lecturer_number'] ?? $row['employee_number'] ?? $row['identity_number'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">{{ $row['action'] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['status'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                    <label class="flex items-start gap-3">
                        <input wire:model="confirmApply" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" @disabled(! $canApply)>
                        <span>
                            <span class="block text-sm font-semibold text-gray-950 dark:text-white">Saya sudah memeriksa preview dan ingin menjalankan apply.</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Aksi ini hanya membuat atau mengaktifkan ulang user app access yang tertera pada preview. Core tidak membuat role global baru.</span>
                        </span>
                    </label>
                    @error('confirmApply') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
                    <div class="mt-4">
                        <x-filament::button wire:click="apply" icon="heroicon-o-check-circle" color="success" :disabled="! $canApply">
                            Apply Bulk Access
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
