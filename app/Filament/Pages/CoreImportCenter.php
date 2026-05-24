<?php

namespace App\Filament\Pages;

use App\Exports\CoreImportTemplateExport;
use App\Models\CoreImportBatch;
use App\Models\CoreImportRecord;
use App\Services\CoreImportExecutionService;
use App\Services\CoreImportPreviewService;
use App\Services\CoreImportRollbackService;
use App\Services\CoreImportTemplateService;
use App\Services\CoreImportValidationService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class CoreImportCenter extends Page
{
    use WithFileUploads;

    protected const EXECUTABLE_IMPORT_TYPES = ['students', 'lecturers', 'employees', 'users', 'user_role_assignments', 'user_app_accesses'];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|UnitEnum|null $navigationGroup = 'Import & Data Tools';

    protected static ?string $navigationLabel = 'Import Center';

    protected static ?string $title = 'Import Center';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.core-import-center';

    public ?string $importType = null;

    public ?TemporaryUploadedFile $importFile = null;

    public ?array $previewResult = null;

    public ?array $validationResult = null;

    public ?int $batchId = null;

    public array $decisionRows = [];

    public ?array $decisionSummary = null;

    public ?array $executionSummary = null;

    public ?array $rollbackSummary = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'import-center';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Upload dan validasi data master Core melalui template Excel.';
    }

    public function getImportTypes(): array
    {
        return app(CoreImportTemplateService::class)->enabledTypes();
    }

    public function getAcceptedExtensions(): string
    {
        return collect(config('core_import.upload.accepted_extensions', ['xlsx', 'xls', 'csv']))
            ->map(fn (string $extension): string => ".{$extension}")
            ->implode(',');
    }

    public function uploadAndPreview(CoreImportPreviewService $previewService, CoreImportValidationService $validationService): void
    {
        $enabledTypes = array_keys($this->getImportTypes());
        $maxSize = (int) config('core_import.upload.max_size_kb', 5120);

        $this->validate([
            'importType' => ['required', 'string', Rule::in($enabledTypes)],
            'importFile' => ['required', 'file', "max:{$maxSize}", 'mimes:xlsx,xls,csv'],
        ]);

        $disk = config('core_import.upload.disk', 'local');
        $directory = trim(config('core_import.upload.directory', 'core-imports/pending'), '/');
        $extension = strtolower($this->importFile->getClientOriginalExtension());
        $storedName = Str::uuid()->toString() . ".{$extension}";
        $storedPath = $this->importFile->storeAs($directory, $storedName, $disk);
        $fullPath = Storage::disk($disk)->path($storedPath);

        $this->previewResult = $previewService->preview(
            $this->importType,
            $fullPath,
            $this->importFile->getClientOriginalName(),
            $storedPath,
        );

        $this->validationResult = $this->previewResult['is_valid_for_preview']
            ? $validationService->validate($this->importType, $fullPath)
            : null;

        $batch = CoreImportBatch::create([
            'source' => $this->importType,
            'mode' => $this->validationResult ? 'validation' : 'preview',
            'status' => $this->validationResult
                ? ($this->validationResult['is_supported'] ? 'validated' : 'validation_not_available')
                : $this->previewResult['status'],
            'started_at' => now(),
            'operator_id' => Filament::auth()->id(),
            'options' => [
                'original_filename' => $this->previewResult['original_filename'],
                'stored_path' => $storedPath,
                'disk' => $disk,
            ],
            'summary' => [
                'headings' => $this->previewResult['headings'],
                'missing_required_columns' => $this->previewResult['missing_required_columns'],
                'unknown_columns' => $this->previewResult['unknown_columns'],
                'password_columns' => $this->previewResult['password_columns'],
                'row_count_estimate' => $this->previewResult['row_count_estimate'],
                'errors' => $this->previewResult['errors'],
                'warnings' => $this->previewResult['warnings'],
                'validation' => $this->safeValidationSummary($this->validationResult),
            ],
        ]);

        $this->batchId = $batch->id;

        if ($this->validationResult && ($this->validationResult['is_supported'] ?? false)) {
            $validationService->persistValidationResults($batch, $this->validationResult);
            $this->loadDecisionState($batch->fresh());
        } else {
            $this->decisionRows = [];
            $this->decisionSummary = null;
        }

        $this->importFile = null;

        $notification = Notification::make()
            ->title($this->previewResult['is_valid_for_preview'] ? 'Preview siap divalidasi.' : 'Heading perlu diperbaiki.');

        $this->previewResult['is_valid_for_preview']
            ? $notification->success()
            : $notification->warning();

        $notification->send();
    }

    public function saveImportDecisions(CoreImportValidationService $validationService): void
    {
        $batch = $this->batchId ? CoreImportBatch::query()->find($this->batchId) : null;

        if (! $batch) {
            Notification::make()
                ->danger()
                ->title('Batch import tidak ditemukan.')
                ->send();

            return;
        }

        foreach ($this->decisionRows as $recordId => $decision) {
            $record = CoreImportRecord::query()
                ->where('core_import_batch_id', $batch->id)
                ->find($recordId);

            if (! $record) {
                continue;
            }

            $adminDecision = $decision['admin_decision'] ?? null;
            $allowed = $validationService->allowedDecisionsForStatus((string) $record->validation_status, $batch->source);

            if (! in_array($adminDecision, $allowed, true)) {
                Notification::make()
                    ->danger()
                    ->title('Keputusan tidak valid.')
                    ->body("Row {$record->source_id} tidak dapat memakai decision {$adminDecision}.")
                    ->send();

                return;
            }

            $record->forceFill([
                'admin_decision' => $adminDecision,
                'decision_note' => $decision['decision_note'] ?? null,
                'decided_by' => Filament::auth()->id(),
                'decided_at' => now(),
            ])->save();
        }

        $this->decisionSummary = $validationService->summarizeDecisions($batch->fresh());
        $this->loadDecisionState($batch->fresh());

        Notification::make()
            ->success()
            ->title('Decision import disimpan.')
            ->body('Belum ada data master yang diimport. Execute import tersedia pada tahap berikutnya.')
            ->send();
    }

    public function executeImport(CoreImportExecutionService $executionService): void
    {
        $batch = $this->batchId ? CoreImportBatch::query()->find($this->batchId) : null;

        if (! $batch) {
            Notification::make()
                ->danger()
                ->title('Batch import tidak ditemukan.')
                ->send();

            return;
        }

        if (($this->decisionSummary['pending_decisions'] ?? 0) > 0) {
            Notification::make()
                ->warning()
                ->title('Masih ada decision pending.')
                ->body('Selesaikan keputusan admin sebelum execute import.')
                ->send();

            return;
        }

        if (($this->decisionSummary['executable_rows'] ?? 0) < 1) {
            Notification::make()
                ->warning()
                ->title('Tidak ada row executable.')
                ->send();

            return;
        }

        try {
            $this->executionSummary = $executionService->execute($batch, Filament::auth()->user());
            $this->loadDecisionState($batch->fresh());

            Notification::make()
                ->success()
                ->title('Import dieksekusi.')
                ->body('Cek summary dan execution status untuk hasil detail.')
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Execute import gagal.')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function rollbackImport(CoreImportRollbackService $rollbackService): void
    {
        $batch = $this->batchId ? CoreImportBatch::query()->find($this->batchId) : null;

        if (! $batch) {
            Notification::make()
                ->danger()
                ->title('Batch import tidak ditemukan.')
                ->send();

            return;
        }

        try {
            $this->rollbackSummary = $rollbackService->rollback($batch, Filament::auth()->user());
            $this->loadDecisionState($batch->fresh());

            Notification::make()
                ->success()
                ->title('Rollback import selesai diproses.')
                ->body('Cek rollback summary dan row status untuk detail.')
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Rollback import gagal.')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function canExecuteImport(): bool
    {
        return in_array($this->importType, self::EXECUTABLE_IMPORT_TYPES, true)
            && ($this->decisionSummary['pending_decisions'] ?? 1) === 0
            && ($this->decisionSummary['executable_rows'] ?? 0) > 0
            && ! in_array($this->executionSummary['status'] ?? null, ['executed', 'partially_failed', 'failed'], true);
    }

    public function canRollbackImport(): bool
    {
        return in_array($this->executionSummary ? 'executed' : null, ['executed'], true)
            || filled($this->executionSummary)
            || collect($this->decisionRows)->contains(fn (array $row): bool => ($row['execution_status'] ?? null) === 'executed');
    }

    public function setAllValidToCreateNew(): void
    {
        foreach ($this->decisionRows as $recordId => $decision) {
            if (($decision['validation_status'] ?? null) === 'valid') {
                $this->decisionRows[$recordId]['admin_decision'] = in_array($this->importType, ['user_role_assignments', 'user_app_accesses'], true)
                    ? 'assign'
                    : 'create_new';
            }
        }
    }

    public function setAllConflictToSkip(): void
    {
        foreach ($this->decisionRows as $recordId => $decision) {
            if (($decision['validation_status'] ?? null) === 'conflict') {
                $this->decisionRows[$recordId]['admin_decision'] = 'skip';
            }
        }
    }

    public function setAllInvalidToSkip(): void
    {
        foreach ($this->decisionRows as $recordId => $decision) {
            if (($decision['validation_status'] ?? null) === 'invalid') {
                $this->decisionRows[$recordId]['admin_decision'] = 'skip';
            }
        }
    }

    public function resetDecisions(): void
    {
        $batch = $this->batchId ? CoreImportBatch::query()->with('records')->find($this->batchId) : null;

        if (! $batch) {
            return;
        }

        foreach ($batch->records as $record) {
            $this->decisionRows[$record->id]['admin_decision'] = app(CoreImportValidationService::class)
                ->defaultDecision((string) $record->validation_status, $record->suggested_action, $batch->source);
            $this->decisionRows[$record->id]['decision_note'] = null;
        }
    }

    public function decisionOptions(string $validationStatus): array
    {
        if ($this->importType === 'user_role_assignments') {
            return match ($validationStatus) {
                'valid', 'conflict' => [
                    'assign' => 'Assign',
                    'skip' => 'Skip',
                ],
                'invalid' => [
                    'invalid' => 'Invalid',
                    'skip' => 'Skip',
                ],
                default => [
                    'skip' => 'Skip',
                ],
            };
        }

        if ($this->importType === 'user_app_accesses') {
            return match ($validationStatus) {
                'valid', 'conflict' => [
                    'assign' => 'Assign',
                    'deactivate' => 'Deactivate',
                    'skip' => 'Skip',
                ],
                'invalid' => [
                    'invalid' => 'Invalid',
                    'skip' => 'Skip',
                ],
                default => [
                    'skip' => 'Skip',
                ],
            };
        }

        return match ($validationStatus) {
            'valid' => [
                'create_new' => 'Create New',
                'skip' => 'Skip',
            ],
            'conflict' => [
                'needs_admin_decision' => 'Needs Decision',
                'update_existing' => 'Update Existing',
                'skip' => 'Skip',
                'create_new' => 'Create New',
            ],
            'invalid' => [
                'invalid' => 'Invalid',
                'skip' => 'Skip',
            ],
            default => [
                'skip' => 'Skip',
            ],
        };
    }

    protected function loadDecisionState(CoreImportBatch $batch): void
    {
        $this->decisionRows = $batch->records()
            ->orderByRaw('CAST(source_id AS INTEGER)')
            ->get()
            ->mapWithKeys(fn (CoreImportRecord $record): array => [
                $record->id => [
                    'id' => $record->id,
                    'row_number' => $record->source_id,
                    'identifier' => $record->source_identifier,
                    'validation_status' => $record->validation_status,
                    'suggested_action' => $record->suggested_action,
                    'admin_decision' => $record->admin_decision,
                    'decision_note' => $record->decision_note,
                    'errors' => $record->errors ?? [],
                    'warnings' => $record->warnings ?? [],
                    'conflicts' => $record->conflicts ?? [],
                    'execution_status' => $record->execution_status,
                    'rollback_status' => $record->rollback_status,
                ],
            ])
            ->all();

        $this->decisionSummary = $batch->summary['decision'] ?? null;
        $this->executionSummary = $batch->summary['execution'] ?? $this->executionSummary;
        $this->rollbackSummary = $batch->summary['rollback'] ?? $this->rollbackSummary;
    }

    protected function safeValidationSummary(?array $validation): ?array
    {
        if (! $validation) {
            return null;
        }

        return [
            'import_type' => $validation['import_type'],
            'is_supported' => $validation['is_supported'],
            'total_rows_checked' => $validation['total_rows_checked'],
            'valid_rows' => $validation['valid_rows'],
            'invalid_rows' => $validation['invalid_rows'],
            'conflict_rows' => $validation['conflict_rows'],
            'warnings_count' => $validation['warnings_count'],
            'errors_count' => $validation['errors_count'],
            'warnings' => $validation['warnings'],
            'errors' => $validation['errors'],
            'rows' => collect($validation['rows'])
                ->take(25)
                ->map(fn (array $row): array => [
                    'row_number' => $row['row_number'],
                    'identifier' => $row['identifier'],
                    'errors' => $row['errors'],
                    'warnings' => $row['warnings'],
                    'conflicts' => $row['conflicts'],
                    'suggested_action' => $row['suggested_action'],
                    'is_valid' => $row['is_valid'],
                    'can_import_later' => $row['can_import_later'],
                ])
                ->values()
                ->all(),
        ];
    }

    public function downloadTemplate(string $type): BinaryFileResponse
    {
        $templates = app(CoreImportTemplateService::class);

        if (! $templates->assertNoPasswordColumns($type)) {
            Notification::make()
                ->danger()
                ->title('Template tidak aman.')
                ->body('Template terdeteksi memiliki kolom password.')
                ->send();

            abort(422);
        }

        return Excel::download(
            new CoreImportTemplateExport($type, $templates),
            $templates->filename($type),
        );
    }
}
