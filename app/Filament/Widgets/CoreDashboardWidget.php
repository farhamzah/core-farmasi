<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CoreDataQualityDashboard;
use App\Filament\Pages\CoreImportCenter;
use App\Filament\Resources\AccountRequestResource;
use App\Filament\Resources\CoreApiClientResource;
use App\Filament\Resources\CoreApplicationResource;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\LecturerResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserAppAccessResource;
use App\Filament\Resources\UserResource;
use App\Models\AccountRequest;
use App\Models\CoreApiClient;
use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\Employee;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\UserAppAccess;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class CoreDashboardWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.core-dashboard-widget';

    protected function getViewData(): array
    {
        $activeUsers = User::query()->where('active', true)->count();
        $totalUsers = User::query()->count();
        $pendingRequests = AccountRequest::query()
            ->whereIn('status', [AccountRequest::STATUS_PENDING, AccountRequest::STATUS_IN_REVIEW])
            ->count();

        $appCodes = ['kp-farmasi', 'tu-farmasi', 'ta-farmasi', 'lab-farmasi'];
        $applications = CoreApplication::query()
            ->whereIn('app_code', $appCodes)
            ->get()
            ->keyBy('app_code');

        return [
            'user' => Filament::auth()->user(),
            'registrationUrl' => route('account-request.create'),
            'publicRegistrationEnabled' => (bool) config('core_account.public_account_request_enabled', false),
            'primaryMetrics' => [
                [
                    'label' => 'User aktif',
                    'value' => $activeUsers,
                    'detail' => "{$totalUsers} total akun Core",
                    'status' => $activeUsers > 0 ? 'OK' : 'Review',
                    'tone' => 'blue',
                    'url' => UserResource::getUrl('index'),
                ],
                [
                    'label' => 'Permohonan akun',
                    'value' => $pendingRequests,
                    'detail' => 'Pending atau in review',
                    'status' => $pendingRequests > 0 ? 'Review' : 'OK',
                    'tone' => $pendingRequests > 0 ? 'amber' : 'emerald',
                    'url' => AccountRequestResource::getUrl('index'),
                ],
                [
                    'label' => 'Akses aplikasi aktif',
                    'value' => UserAppAccess::query()->where('is_active', true)->count(),
                    'detail' => 'Hak akses lintas aplikasi',
                    'status' => 'OK',
                    'tone' => 'sky',
                    'url' => UserAppAccessResource::getUrl('index'),
                ],
                [
                    'label' => 'API client aktif',
                    'value' => CoreApiClient::query()->active()->count(),
                    'detail' => 'Readiness integrasi staging',
                    'status' => 'OK',
                    'tone' => 'indigo',
                    'url' => CoreApiClientResource::getUrl('index'),
                ],
            ],
            'masterData' => [
                ['label' => 'Mahasiswa', 'value' => Student::query()->count(), 'url' => StudentResource::getUrl('index')],
                ['label' => 'Dosen', 'value' => Lecturer::query()->count(), 'url' => LecturerResource::getUrl('index')],
                ['label' => 'Tendik / Staff', 'value' => Employee::query()->count(), 'url' => EmployeeResource::getUrl('index')],
                ['label' => 'Program Studi', 'value' => StudyProgram::query()->count(), 'url' => null],
                ['label' => 'Fakultas', 'value' => Faculty::query()->count(), 'url' => null],
                ['label' => 'Role Global', 'value' => Role::query()->where('active', true)->count(), 'url' => null],
            ],
            'qualitySignals' => [
                [
                    'label' => 'User tanpa role',
                    'value' => User::query()->whereDoesntHave('roles')->count(),
                    'tone' => 'amber',
                ],
                [
                    'label' => 'User tanpa app access',
                    'value' => User::query()->whereDoesntHave('appAccesses')->count(),
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Wajib ganti password',
                    'value' => User::query()->where('must_change_password', true)->count(),
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Akun nonaktif',
                    'value' => User::query()->where('active', false)->count(),
                    'tone' => 'slate',
                ],
            ],
            'appReadiness' => collect($appCodes)->map(function (string $appCode) use ($applications): array {
                $app = $applications->get($appCode);
                $activeAccessCount = UserAppAccess::query()
                    ->where('app_code', $appCode)
                    ->where('is_active', true)
                    ->count();
                $roleCount = CoreApplicationRole::query()
                    ->where('app_code', $appCode)
                    ->where('is_active', true)
                    ->count();

                return [
                    'code' => $appCode,
                    'name' => $app?->name ?? str($appCode)->replace('-', ' ')->title()->toString(),
                    'registered' => (bool) $app,
                    'active' => (bool) ($app?->is_active),
                    'access_count' => $activeAccessCount,
                    'role_count' => $roleCount,
                ];
            })->all(),
            'quickActions' => [
                [
                    'label' => 'Review permohonan akun',
                    'description' => 'Approve calon mahasiswa, dosen, dan tendik setelah validasi identitas.',
                    'url' => AccountRequestResource::getUrl('index'),
                ],
                [
                    'label' => 'Import data master',
                    'description' => 'Upload template Excel untuk user, profil, role, dan akses aplikasi.',
                    'url' => CoreImportCenter::getUrl(),
                ],
                [
                    'label' => 'Audit kualitas data',
                    'description' => 'Cek user tanpa role, akses kosong, data duplikat, dan referensi bermasalah.',
                    'url' => CoreDataQualityDashboard::getUrl(),
                ],
                [
                    'label' => 'Kelola aplikasi',
                    'description' => 'Pastikan KP, TU, TA, dan Lab terdaftar serta aktif di registry Core.',
                    'url' => CoreApplicationResource::getUrl('index'),
                ],
            ],
            'latestRequests' => AccountRequest::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'request_type', 'name', 'email', 'status', 'created_at']),
        ];
    }
}
