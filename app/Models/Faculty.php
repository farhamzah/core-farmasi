<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    /**
     * @return array<string, string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        if ($this->departments()->exists()) {
            $blockers['departments'] = 'Fakultas masih memiliki departemen.';
        }

        if ($this->studyPrograms()->exists()) {
            $blockers['study_programs'] = 'Fakultas masih memiliki program studi.';
        }

        if (LeadershipAssignment::query()
            ->where('unit_type', 'faculty')
            ->where(function ($query) {
                $query->where('unit_id', $this->id)->orWhereNull('unit_id');
            })
            ->exists()) {
            $blockers['leadership_assignments'] = 'Fakultas masih dipakai pada data jabatan pimpinan.';
        }

        return $blockers;
    }

    public function canBeDeletedSafely(): bool
    {
        return $this->deletionBlockers() === [];
    }

    protected static function booted(): void
    {
        static::deleting(function (Faculty $faculty): void {
            if (! $faculty->canBeDeletedSafely()) {
                throw ValidationException::withMessages([
                    'faculty' => implode(' ', $faculty->deletionBlockers()).' Nonaktifkan data jika sudah tidak dipakai.',
                ]);
            }
        });
    }
}
