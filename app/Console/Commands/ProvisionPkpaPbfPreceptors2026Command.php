<?php

namespace App\Console\Commands;

use App\Models\CoreApplication;
use App\Models\CoreApplicationRole;
use App\Models\ExternalPerson;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAppAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProvisionPkpaPbfPreceptors2026Command extends Command
{
    protected $signature = 'core:provision-pkpa-pbf-preceptors-2026
        {--apply : Terapkan setelah hasil preview diperiksa}
        {--initial-password=password1234 : Password awal khusus akun baru}';

    protected $description = 'Menyiapkan akun Core dan akses MY PKPA bagi Preseptor PBF periode Oktober-November 2026.';

    public function handle(): int
    {
        $application = CoreApplication::query()->where('app_code', 'kppspa-farmasi')->where('is_active', true)->first();
        $applicationRole = CoreApplicationRole::query()->where('app_code', 'kppspa-farmasi')->where('role_slug', 'pembimbing-lapangan')->where('is_active', true)->first();
        $role = Role::query()->where('name', 'pembimbing-lapangan')->where('active', true)->first();

        if (! $application || ! $applicationRole || ! $role) {
            $this->error('Aplikasi atau role Preseptor MY PKPA belum aktif di Core. Tidak ada perubahan yang dibuat.');

            return self::FAILURE;
        }

        $rows = collect($this->roster())->map(function (array $item): array {
            $userByEmail = User::withTrashed()->whereRaw('LOWER(TRIM(email)) = ?', [$item['email']])->first();
            $personByPhone = ExternalPerson::withTrashed()->where('phone', $item['phone'])->first();
            $conflict = $userByEmail && $personByPhone?->user_id && $userByEmail->id !== $personByPhone->user_id;
            $user = $userByEmail ?: $personByPhone?->user;

            return $item + [
                'action' => $conflict ? 'BLOCKER' : ($user ? 'Gunakan akun Core' : 'Buat akun baru'),
                'core_user_id' => $user?->id,
                'effective_email' => $user?->email ?: $item['email'],
                'conflict' => $conflict || (bool) $user?->trashed(),
            ];
        });

        $this->table(
            ['Nama', 'Tempat', 'Email/login', 'No. HP', 'Tindakan'],
            $rows->map(fn (array $row) => [$row['formal_name'], $row['institution'], $row['effective_email'], $row['phone'], $row['action']])->all()
        );

        if ($rows->contains('conflict', true)) {
            $this->error('Ditemukan akun terhapus atau identitas yang saling bertabrakan. Import dibatalkan.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->warn('Ini hanya preview. Tidak ada data yang diubah. Jalankan ulang dengan --apply jika seluruh data sudah benar.');

            return self::SUCCESS;
        }

        $created = 0;
        $reused = 0;
        DB::transaction(function () use ($rows, $role, &$created, &$reused): void {
            foreach ($rows as $item) {
                $user = filled($item['core_user_id']) ? User::query()->lockForUpdate()->findOrFail($item['core_user_id']) : null;
                if (! $user) {
                    $user = User::create([
                        'name' => $item['name'],
                        'email' => $item['email'],
                        'username' => $item['email'],
                        'phone' => $item['phone'],
                        'identity_type' => 'external',
                        'password' => Hash::make((string) $this->option('initial-password')),
                        'active' => true,
                        'must_change_password' => true,
                        'password_changed_at' => null,
                        'last_password_reset_at' => now(),
                    ]);
                    $created++;
                } else {
                    $reused++;
                }

                $person = ExternalPerson::withTrashed()->firstOrNew(['user_id' => $user->id]);
                if ($person->trashed()) {
                    $person->restore();
                }
                $person->fill([
                    'external_number' => $person->external_number ?: $item['external_number'],
                    'name' => $person->name ?: $item['name'],
                    'front_title' => $person->front_title ?: $item['front_title'],
                    'back_title' => $person->back_title ?: $item['back_title'],
                    'email' => $person->email ?: $user->email,
                    'phone' => $person->phone ?: $item['phone'],
                    'institution_name' => $person->institution_name ?: $item['institution'],
                    'institution_type' => $person->institution_type ?: 'Pedagang Besar Farmasi',
                    'position_title' => $person->position_title ?: 'Preseptor PKPA',
                    'profession' => $person->profession ?: 'Apoteker',
                    'status' => 'active',
                    'notes' => $person->notes ?: 'Preseptor PBF PKPA Farmasi UBP periode 05 Oktober-06 November 2026.',
                ])->save();

                $user->roles()->syncWithoutDetaching([$role->id]);
                UserAppAccess::query()->updateOrCreate([
                    'user_id' => $user->id,
                    'app_code' => 'kppspa-farmasi',
                    'role_slug' => 'pembimbing-lapangan',
                ], [
                    'is_active' => true,
                    'activated_at' => now(),
                    'deactivated_at' => null,
                ]);
            }
        });

        $this->info("Selesai. Akun baru: {$created}; akun Core digunakan kembali: {$reused}; total akses Preseptor: {$rows->count()}.");
        $this->warn('Akun baru wajib mengganti password awal melalui Profile Portal Core sebelum masuk MY PKPA.');

        return self::SUCCESS;
    }

    private function roster(): array
    {
        return [
            $this->person('001', 'Chindy Dwi Martinah', 'apt.', 'S.Farm.', 'chindy@preseptor.safaubp.com', '085710992245', 'PT. Bina San Prima Bekasi'),
            $this->person('002', 'Marthyana Ayuningtyas', 'apt.', 'S.Farm.', 'marthyana@preseptor.safaubp.com', '082126501214', 'PT. Penta Valent Cirebon'),
            $this->person('003', 'Siti Asniar Farisya', 'apt. Dra.', 'S.Si., M.Kes', 'sitiasniar@preseptor.safaubp.com', '081320471374', 'PT. Alida Bandung'),
            $this->person('004', 'Adela Adam Abdullah', 'apt.', 'S.Farm.', 'adela@preseptor.safaubp.com', '087852599790', 'PT. Bina San Prima Bogor'),
            $this->person('005', 'Nurul Istimala', 'apt.', 'S.Farm.', 'nurul@preseptor.safaubp.com', '085210249025', 'PT. Bina San Prima Pulogadung'),
            $this->person('006', 'Dara Cynthia Utami', 'apt.', 'S.Farm.', 'dara@preseptor.safaubp.com', '085695252673', 'PT. Bina San Prima Karawang'),
            $this->person('007', 'Fathimah Nurmajdina', 'apt.', 'S.Farm.', 'fathimah@preseptor.safaubp.com', '085773845313', 'PT. Bina San Prima Tangerang'),
            $this->person('008', 'Dona Waras Sakti', 'apt.', 'S.Farm.', 'dona@preseptor.safaubp.com', '087872700169', 'PT. Bina San Prima Depok'),
            $this->person('009', 'Ari Suwanda Johari', 'apt.', 'S.Farm.', 'arisuwanda@preseptor.safaubp.com', '085223449954', 'PT. Bina San Prima Tasikmalaya'),
            $this->person('010', 'Budiyawan', 'apt.', 'S.Farm.', 'budiyawan@preseptor.safaubp.com', '087728833322', 'PT. Bina San Prima Cirebon'),
        ];
    }

    private function person(string $number, string $name, string $frontTitle, string $backTitle, string $email, string $phone, string $institution): array
    {
        return [
            'external_number' => 'PKPA-PBF-2026-'.$number,
            'name' => $name,
            'front_title' => $frontTitle,
            'back_title' => $backTitle,
            'formal_name' => trim($frontTitle.' '.$name.', '.$backTitle),
            'email' => $email,
            'phone' => $phone,
            'institution' => $institution,
        ];
    }
}
