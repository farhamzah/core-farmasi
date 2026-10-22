<?php

namespace App\Console\Commands;

use App\Models\ExternalPerson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportPkpaPuskesmasPreceptors2026Command extends Command
{
    protected $signature = 'core:import-pkpa-puskesmas-preceptors-2026 {--apply : Simpan profil setelah preview diperiksa}';

    protected $description = 'Mencatat 29 mitra eksternal Preseptor Puskesmas November-Desember 2026 tanpa membuat akun atau akses aplikasi.';

    public function handle(): int
    {
        $roster = json_decode(file_get_contents(database_path('data/pkpa_puskesmas_preceptors_2026.json')), true, 512, JSON_THROW_ON_ERROR);
        $prepare = function () use ($roster) {
            $people = ExternalPerson::withTrashed()->get();

            return collect($roster['sites'])->map(function ($site) use ($people) {
                $key = fn ($value) => strtolower(preg_replace('/[^a-z0-9]/i', '', Str::ascii((string) $value)));
                $phone = fn ($value) => preg_replace('/\D/', '', preg_replace('/^\+?62/', '0', (string) $value));
                $matches = $people->filter(fn ($p) => $p->external_number === $site['external_number']
                    || $phone($p->phone) === $phone($site['phone'])
                    || ($key($p->name) === $key($site['preceptor']) && $key($p->institution_name) === $key($site['name'])));
                $person = $matches->count() === 1 ? $matches->first() : null;
                $conflict = $matches->count() > 1 || ($person && (
                    $key($person->name) !== $key($site['preceptor'])
                    || (filled($person->phone) && $phone($person->phone) !== $phone($site['phone']))
                    || (filled($person->institution_name) && $key($person->institution_name) !== $key($site['name']))
                ));

                return $site + ['person' => $person, 'conflict' => $conflict];
            });
        };
        $rows = $prepare();
        $this->table(['Mitra eksternal', 'Puskesmas', 'Kontak', 'Tindakan'], $rows->map(fn ($r) => [
            $r['preceptor'], $r['name'], $r['phone'], $r['conflict'] ? 'BLOCKER identitas' : ($r['person'] ? 'Gunakan profil existing' : 'Buat profil tanpa akun'),
        ])->all());
        if ($rows->count() !== 29 || $rows->contains('conflict', true)) {
            $this->error('Identitas tidak aman atau jumlah bukan 29. Tidak ada perubahan yang dibuat.');

            return self::FAILURE;
        }
        if (! $this->option('apply')) {
            $this->warn('Ini hanya preview. Tidak ada akun, profil, atau akses yang diubah. Gunakan --apply setelah diperiksa.');

            return self::SUCCESS;
        }
        try {
            DB::transaction(function () use ($prepare, $roster) {
                // Serialisasi import yang sama; tidak menyentuh akun/role/app access.
                DB::table('external_people')->whereIn('external_number', array_column($roster['sites'], 'external_number'))
                    ->orWhereIn('phone', array_column($roster['sites'], 'phone'))->lockForUpdate()->get();
                $rows = $prepare();
                if ($rows->contains('conflict', true)) {
                    throw ValidationException::withMessages(['identity' => 'Data mitra berubah/ambigu. Jalankan ulang preview.']);
                }
                foreach ($rows as $row) {
                    $person = $row['person'] ?: new ExternalPerson;
                    if ($person->exists && $person->trashed()) {
                        $person->restore();
                    }
                    $person->fill([
                        'external_number' => $person->external_number ?: $row['external_number'],
                        'name' => $person->name ?: $row['preceptor'],
                        'front_title' => $person->front_title ?: $row['front_title'],
                        'back_title' => $person->back_title ?: $row['back_title'],
                        'phone' => $person->phone ?: $row['phone'],
                        'institution_name' => $person->institution_name ?: $row['name'],
                        'institution_type' => $person->institution_type ?: 'Puskesmas',
                        'position_title' => $person->position_title ?: 'Preseptor PKPA',
                        'profession' => $person->profession ?: 'Apoteker',
                        'status' => 'active',
                        'notes' => $person->notes ?: 'Mitra Preseptor PKPA Puskesmas 10 November-07 Desember 2026. Dicatat tanpa membuat akun/login.',
                    ])->save();
                }
            });
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }
        $this->info('29 profil mitra eksternal siap. Tidak ada akun, password, role, atau app access yang dibuat/diubah.');

        return self::SUCCESS;
    }
}
