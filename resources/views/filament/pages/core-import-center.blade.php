<x-filament-panels::page>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm ring-1 ring-blue-50 dark:border-blue-500/20 dark:bg-gray-950 dark:ring-blue-500/10">
            <div class="border-l-4 border-blue-600 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 dark:from-blue-950/40 dark:via-gray-950 dark:to-sky-950/20">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Controlled import lifecycle</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Import Center</h2>
                        <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Kelola template, upload, preview, admin decision, execute, dan rollback dengan jejak status yang jelas.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">Private upload</span>
                        <span class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20">No password column</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                '1' => ['Download template', 'Gunakan format resmi'],
                '2' => ['Upload file', 'Private storage'],
                '3' => ['Preview', 'Cek heading & row'],
                '4' => ['Decision', 'Pilih aksi aman'],
                '5' => ['Execute', 'Manual + konfirmasi'],
                '6' => ['Rollback', 'Undo terkontrol'],
            ] as $step => [$title, $caption])
                <div class="rounded-lg border border-blue-100 bg-white p-3 shadow-sm ring-1 ring-blue-50 dark:border-gray-800 dark:bg-gray-950 dark:ring-blue-500/5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white">{{ $step }}</span>
                        <div>
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $caption }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Template Download
            </x-slot>

            <x-slot name="description">
                Gunakan template resmi agar heading konsisten. Template tidak berisi kolom password.
            </x-slot>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->getImportTypes() as $type => $definition)
                    <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm ring-1 ring-blue-50 transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-950 dark:ring-blue-500/5">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $definition['label'] }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $definition['description'] }}
                                </p>
                            </div>

                            <div class="space-y-1 rounded-md bg-blue-50/70 p-3 text-xs text-gray-600 ring-1 ring-blue-100 dark:bg-blue-500/5 dark:text-gray-300 dark:ring-blue-500/10">
                                <p class="font-semibold text-blue-800 dark:text-blue-200">Required columns</p>
                                <p>{{ implode(', ', $definition['required_columns']) }}</p>
                            </div>

                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-arrow-down-tray"
                                size="sm"
                                wire:click="downloadTemplate('{{ $type }}')"
                            >
                                Download Template
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Upload & Preview
            </x-slot>

            <x-slot name="description">
                Upload file ke private storage, validasi heading, dan tampilkan preview terbatas sebelum decision/execute manual.
            </x-slot>

            <form wire:submit="uploadAndPreview" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Jenis Import</span>
                        <select
                            wire:model="importType"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="">Pilih jenis import</option>
                            @foreach ($this->getImportTypes() as $type => $definition)
                                <option value="{{ $type }}">{{ $definition['label'] }}</option>
                            @endforeach
                        </select>
                        @error('importType')
                            <span class="text-sm text-danger-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="space-y-1">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">File Import</span>
                        <input
                            type="file"
                            wire:model="importFile"
                            accept="{{ $this->getAcceptedExtensions() }}"
                            class="block w-full rounded-lg border border-gray-300 text-sm shadow-sm file:mr-4 file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:file:bg-gray-800 dark:file:text-gray-200"
                        />
                        <span class="text-xs text-gray-500 dark:text-gray-400">xlsx, xls, csv. Maksimum 5 MB.</span>
                        @error('importFile')
                            <span class="block text-sm text-danger-600">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-eye"
                    wire:loading.attr="disabled"
                    wire:target="uploadAndPreview,importFile"
                >
                    Upload & Preview
                </x-filament::button>
            </form>

            <div class="mt-5 rounded-lg border border-dashed border-blue-300 bg-blue-50/60 p-4 text-sm text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                Preview dan validation tidak mengeksekusi import. Execute dan rollback selalu manual serta membutuhkan konfirmasi.
            </div>

            @if ($previewResult)
                <div class="mt-6 space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $previewResult['is_valid_for_preview'] ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' }}">
                            {{ $previewResult['status'] }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Batch #{{ $batchId }} - {{ $previewResult['original_filename'] }} - {{ $previewResult['row_count_estimate'] }} data rows
                        </span>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">Headings ditemukan</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ implode(', ', $previewResult['headings']) ?: '-' }}</p>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">Required columns</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ empty($previewResult['missing_required_columns']) ? 'Lengkap' : implode(', ', $previewResult['missing_required_columns']) }}
                            </p>
                        </div>
                    </div>

                    @if (! empty($previewResult['unknown_columns']))
                        <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                            Unknown columns: {{ implode(', ', $previewResult['unknown_columns']) }}
                        </div>
                    @endif

                    @if (! empty($previewResult['errors']))
                        <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-200">
                            {{ implode(' ', $previewResult['errors']) }}
                        </div>
                    @endif

                    @if (! empty($previewResult['warnings']))
                        <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                            {{ implode(' ', $previewResult['warnings']) }}
                        </div>
                    @endif

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    @foreach (($previewResult['preview_rows'][0] ?? []) as $heading => $value)
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($previewResult['preview_rows'] as $row)
                                    <tr>
                                        @foreach ($row as $value)
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Tidak ada preview row.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($validationResult)
                <div class="mt-6 space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                        @foreach ([
                            'Checked' => $validationResult['total_rows_checked'],
                            'Valid' => $validationResult['valid_rows'],
                            'Invalid' => $validationResult['invalid_rows'],
                            'Conflict' => $validationResult['conflict_rows'],
                            'Warnings' => $validationResult['warnings_count'],
                            'Errors' => $validationResult['errors_count'],
                        ] as $label => $value)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="text-lg font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (! $validationResult['is_supported'])
                        <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                            Row validation belum tersedia untuk import type ini. Data belum diimport.
                        </div>
                    @else
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">Hasil Validasi Row</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Belum ada data yang diimport. Tahap ini hanya validasi dan deteksi konflik.
                            </p>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Row</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Identifier</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Suggested Action</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Errors</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Warnings</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Conflicts</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse (array_slice($validationResult['rows'], 0, 25) as $row)
                                        @php
                                            $status = ! empty($row['errors']) ? 'invalid' : (! empty($row['conflicts']) ? 'conflict' : 'valid');
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['row_number'] }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['identifier'] ?: '-' }}</td>
                                            <td class="px-3 py-2">
                                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $status === 'valid' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : ($status === 'conflict' ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' : 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-300') }}">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['suggested_action'] }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ implode(' ', $row['errors']) ?: '-' }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ implode(' ', $row['warnings']) ?: '-' }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ implode(' ', $row['conflicts']) ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-2 text-gray-600 dark:text-gray-300">Tidak ada row yang divalidasi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (! empty($decisionRows))
                            <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-950 dark:text-white">Admin Decision</p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Simpan keputusan per row untuk tahap execute berikutnya. Belum ada data yang diimport.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <x-filament::button size="sm" color="gray" wire:click="setAllValidToCreateNew">
                                            Valid: Default Action
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" wire:click="setAllConflictToSkip">
                                            Conflict: Skip
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" wire:click="setAllInvalidToSkip">
                                            Invalid: Skip
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" wire:click="resetDecisions">
                                            Reset
                                        </x-filament::button>
                                    </div>
                                </div>

                                @if ($decisionSummary)
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                                        @foreach ([
                                            'Pending' => $decisionSummary['pending_decisions'] ?? 0,
                                            'Executable' => $decisionSummary['executable_rows'] ?? 0,
                                            'Skipped' => $decisionSummary['skipped_rows'] ?? 0,
                                            'Invalid' => $decisionSummary['invalid_decisions'] ?? 0,
                                            'Decided' => $decisionSummary['decided_rows'] ?? 0,
                                            'Total' => $decisionSummary['total_rows'] ?? 0,
                                        ] as $label => $value)
                                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                                <p class="text-lg font-semibold text-gray-950 dark:text-white">{{ $value }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Row</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Identifier</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Status</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Suggested</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Admin Decision</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Execution</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Rollback</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Note</th>
                                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach (array_slice($decisionRows, 0, 50, true) as $recordId => $row)
                                                <tr>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['row_number'] }}</td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['identifier'] ?: '-' }}</td>
                                                    <td class="px-3 py-2">
                                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $row['validation_status'] === 'valid' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : ($row['validation_status'] === 'conflict' ? 'bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300' : 'bg-danger-100 text-danger-700 dark:bg-danger-500/20 dark:text-danger-300') }}">
                                                            {{ $row['validation_status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['suggested_action'] }}</td>
                                                    <td class="px-3 py-2">
                                                        <select
                                                            wire:model="decisionRows.{{ $recordId }}.admin_decision"
                                                            @if (($row['execution_status'] ?? null) === 'executed') disabled @endif
                                                            class="block min-w-40 rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                        >
                                                            @foreach ($this->decisionOptions($row['validation_status']) as $value => $label)
                                                                <option value="{{ $value }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['execution_status'] ?: 'not_executed' }}</td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['rollback_status'] ?: '-' }}</td>
                                                    <td class="px-3 py-2">
                                                        <input
                                                            type="text"
                                                            wire:model="decisionRows.{{ $recordId }}.decision_note"
                                                            @if (($row['execution_status'] ?? null) === 'executed') disabled @endif
                                                            class="block min-w-52 rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                                            placeholder="Opsional"
                                                        />
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                                        <div class="max-w-md space-y-1">
                                                            @if (! empty($row['errors']))
                                                                <p><span class="font-semibold text-danger-600">Errors:</span> {{ implode(' ', $row['errors']) }}</p>
                                                            @endif
                                                            @if (! empty($row['warnings']))
                                                                <p><span class="font-semibold text-warning-600">Warnings:</span> {{ implode(' ', $row['warnings']) }}</p>
                                                            @endif
                                                            @if (! empty($row['conflicts']))
                                                                <p><span class="font-semibold text-warning-600">Conflicts:</span> {{ implode(' ', $row['conflicts']) }}</p>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <x-filament::button
                                        color="primary"
                                        icon="heroicon-o-check"
                                        wire:click="saveImportDecisions"
                                    >
                                        Save Decisions
                                    </x-filament::button>

                                    <x-filament::button
                                        color="gray"
                                        icon="heroicon-o-play"
                                        wire:click="executeImport"
                                        wire:confirm="Execute import akan menulis data master untuk row approved. Users dapat dibuat/diupdate, role global dapat di-assign, dan app access dapat di-assign/deactivate sesuai decision. Invalid/skip tidak dieksekusi. Password user baru dibuat dari birth_date jika tersedia dan tidak akan ditampilkan. Lanjutkan?"
                                        :disabled="! $this->canExecuteImport()"
                                    >
                                        Execute Import
                                    </x-filament::button>

                                    <x-filament::button
                                        color="danger"
                                        icon="heroicon-o-arrow-uturn-left"
                                        wire:click="rollbackImport"
                                        wire:confirm="Rollback akan mencoba membatalkan perubahan batch ini. Update existing hanya direstore jika previous snapshot tersedia. User yang sudah dipakai data lain tidak akan dihapus otomatis. Lanjutkan?"
                                        :disabled="! $this->canRollbackImport()"
                                    >
                                        Rollback Import Batch
                                    </x-filament::button>
                                </div>

                                @if ($executionSummary)
                                    <div class="rounded-lg border border-success-200 bg-success-50 p-4 text-sm text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-200">
                                        <p class="font-semibold">Execution Summary</p>
                                        <p class="mt-1">
                                            Executed: {{ $executionSummary['executed_count'] ?? 0 }},
                                            Created: {{ $executionSummary['created_count'] ?? 0 }},
                                            Updated: {{ $executionSummary['updated_count'] ?? 0 }},
                                            Skipped: {{ $executionSummary['skipped_count'] ?? 0 }},
                                            Ignored invalid: {{ $executionSummary['ignored_invalid_count'] ?? 0 }},
                                            Failed: {{ $executionSummary['failed_count'] ?? 0 }},
                                            Users created: {{ $executionSummary['users_created_count'] ?? 0 }}
                                        </p>
                                    </div>
                                @endif

                                @if ($rollbackSummary)
                                    <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                                        <p class="font-semibold">Rollback Summary</p>
                                        <p class="mt-1">
                                            Rolled back: {{ $rollbackSummary['rolled_back_count'] ?? 0 }},
                                            Skipped: {{ $rollbackSummary['skipped_count'] ?? 0 }},
                                            Manual review: {{ $rollbackSummary['manual_review_count'] ?? 0 }},
                                            Failed: {{ $rollbackSummary['failed_count'] ?? 0 }},
                                            Already rolled back: {{ $rollbackSummary['already_rolled_back_count'] ?? 0 }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
