<?php

namespace App\Models;

use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'code',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        if ($this->studyPrograms()->exists()) {
            $blockers['study_programs'] = 'Departemen masih dipakai oleh program studi.';
        }

        if ($this->lecturers()->exists()) {
            $blockers['lecturers'] = 'Departemen masih dipakai oleh data dosen.';
        }

        if ($this->employees()->exists()) {
            $blockers['employees'] = 'Departemen masih dipakai oleh data tendik/staf.';
        }

        if (LeadershipAssignment::query()
            ->where('unit_type', 'department')
            ->where('unit_id', $this->id)
            ->exists()) {
            $blockers['leadership_assignments'] = 'Departemen masih dipakai pada data jabatan pimpinan.';
        }

        return $blockers;
    }

    public function canBeDeletedSafely(): bool
    {
        return $this->deletionBlockers() === [];
    }

    protected static function booted(): void
    {
        static::deleting(function (Department $department): void {
            if (! $department->canBeDeletedSafely()) {
                throw ValidationException::withMessages([
                    'department' => implode(' ', $department->deletionBlockers()).' Nonaktifkan data jika sudah tidak dipakai.',
                ]);
            }
        });
    }
}
