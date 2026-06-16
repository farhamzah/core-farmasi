<?php

namespace App\Filament\Pages;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Services\BulkUserAppAccessService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class BulkUserAppAccess extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?string $navigationLabel = 'Bulk App Access';

    protected static ?string $title = 'Bulk App Access';

    protected static ?int $navigationSort = 31;

    protected string $view = 'filament.pages.bulk-user-app-access';

    public ?string $appCode = null;

    public ?string $roleSlug = null;

    public string $targetScope = 'identity_type';

    public ?string $targetValue = null;

    public bool $includeInactive = false;

    public bool $reactivateExisting = true;

    public ?string $activatedAt = null;

    public ?string $deactivatedAt = null;

    public bool $confirmApply = false;

    public ?array $previewResult = null;

    public ?array $applyResult = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'bulk-user-app-access';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Berikan akses aplikasi secara kolektif berdasarkan jenis akun, role global, prefix NIM/NIDN, prodi, departemen, atau jenis tendik.';
    }

    public function updatedAppCode(): void
    {
        $this->roleSlug = null;
        $this->resetResult();
    }

    public function updatedTargetScope(): void
    {
        $this->targetValue = null;
        $this->resetResult();
    }

    public function updated($name): void
    {
        if (! in_array($name, ['previewResult', 'applyResult', 'confirmApply'], true)) {
            $this->applyResult = null;
        }
    }

    public function preview(BulkUserAppAccessService $service): void
    {
        $this->validateInput();

        $this->previewResult = $service->preview($this->filters());
        $this->applyResult = null;
        $this->confirmApply = false;

        if ($this->previewResult['blockers'] !== []) {
            Notification::make()
                ->danger()
                ->title('Preview belum aman.')
                ->body(implode(' ', $this->previewResult['blockers']))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Preview bulk access siap.')
            ->body(($this->previewResult['counts']['planned_insert'] ?? 0).' akses baru, '.($this->previewResult['counts']['planned_reactivate'] ?? 0).' reaktivasi.')
            ->send();
    }

    public function apply(BulkUserAppAccessService $service): void
    {
        $this->validateInput(requireConfirmation: true);

        $this->applyResult = $service->apply($this->filters(), Filament::auth()->user());
        $this->previewResult = $this->applyResult;
        $this->confirmApply = false;

        if (! ($this->applyResult['applied'] ?? false)) {
            Notification::make()
                ->warning()
                ->title('Bulk access tidak mengubah data.')
                ->body($this->applyResult['message'] ?? 'Tidak ada akses yang perlu dibuat.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Bulk access selesai.')
            ->body($this->applyResult['message'])
            ->send();
    }

    public function getApplicationOptionsProperty(): array
    {
        return CoreApplication::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'app_code')
            ->all();
    }

    public function getRoleOptionsProperty(): array
    {
        if (blank($this->appCode)) {
            return [];
        }

        return CoreApplicationRole::query()
            ->active()
            ->where('app_code', $this->appCode)
            ->orderBy('sort_order')
            ->orderBy('role_name')
            ->get()
            ->mapWithKeys(fn (CoreApplicationRole $role): array => [
                $role->role_slug => "{$role->role_name} ({$role->role_slug})",
            ])
            ->all();
    }

    public function getTargetScopeOptionsProperty(): array
    {
        return BulkUserAppAccessService::TARGET_SCOPES;
    }

    public function getTargetValueOptionsProperty(): array
    {
        return match ($this->targetScope) {
            'identity_type' => [
                'student' => 'Mahasiswa',
                'lecturer' => 'Dosen',
                'employee' => 'Tendik / Staf / Laboran',
            ],
            'global_role' => Role::query()
                ->where('active', true)
                ->orderBy('label')
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Role $role): array => [$role->name => $role->label ?: $role->name])
                ->all(),
            'student_study_program' => StudyProgram::query()
                ->where('active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'employee_staff_type' => Employee::query()
                ->whereNotNull('staff_type')
                ->where('staff_type', '!=', '')
                ->distinct()
                ->orderBy('staff_type')
                ->pluck('staff_type', 'staff_type')
                ->mapWithKeys(fn (string $staffType): array => [$staffType => str($staffType)->replace(['_', '-'], ' ')->title()->toString()])
                ->all(),
            'lecturer_department', 'employee_department' => Department::query()
                ->where('active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            default => [],
        };
    }

    public function targetValueIsFreeText(): bool
    {
        return in_array($this->targetScope, ['student_nim_prefix', 'lecturer_nidn_prefix'], true);
    }

    public function targetValuePlaceholder(): string
    {
        return match ($this->targetScope) {
            'lecturer_nidn_prefix' => 'Contoh: 04, 0403, 0430037804',
            'student_nim_prefix' => 'Contoh: 22, 23, 244162',
            default => 'Isi nilai target',
        };
    }

    public function targetValueHelpText(): string
    {
        return match ($this->targetScope) {
            'lecturer_nidn_prefix' => 'Untuk dosen, masukkan awalan NIDN. Contoh: 04 untuk semua NIDN yang dimulai dengan 04.',
            'student_nim_prefix' => 'Untuk mahasiswa, masukkan awalan NIM. Contoh: 24 untuk angkatan 2024 jika pola NIM kampus memakai prefix tersebut.',
            default => '',
        };
    }

    protected function validateInput(bool $requireConfirmation = false): void
    {
        $rules = [
            'appCode' => ['required', 'string'],
            'roleSlug' => ['required', 'string'],
            'targetScope' => ['required', 'string', 'in:'.implode(',', array_keys(BulkUserAppAccessService::TARGET_SCOPES))],
            'targetValue' => ['required', 'string', 'max:100'],
            'includeInactive' => ['boolean'],
            'reactivateExisting' => ['boolean'],
            'activatedAt' => ['nullable', 'date'],
            'deactivatedAt' => ['nullable', 'date', 'after_or_equal:activatedAt'],
        ];

        if ($requireConfirmation) {
            $rules['confirmApply'] = ['accepted'];
        }

        $this->validate($rules, [
            'confirmApply.accepted' => 'Centang konfirmasi sebelum menjalankan apply bulk access.',
            'targetValue.required' => 'Nilai target wajib diisi.',
        ]);
    }

    protected function filters(): array
    {
        return [
            'app_code' => $this->appCode,
            'role_slug' => $this->roleSlug,
            'target_scope' => $this->targetScope,
            'target_value' => $this->targetValue,
            'include_inactive' => $this->includeInactive,
            'reactivate_existing' => $this->reactivateExisting,
            'activated_at' => $this->activatedAt,
            'deactivated_at' => $this->deactivatedAt,
        ];
    }

    protected function resetResult(): void
    {
        $this->previewResult = null;
        $this->applyResult = null;
        $this->confirmApply = false;
    }
}
