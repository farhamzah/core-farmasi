<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class StudyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'department_id',
        'head_lecturer_id',
        'code',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function headLecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'head_lecturer_id');
    }

    /**
     * @return array<string, string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        if ($this->students()->exists()) {
            $blockers['students'] = 'Program studi masih dipakai oleh data mahasiswa.';
        }

        if ($this->lecturers()->exists()) {
            $blockers['lecturers'] = 'Program studi masih dipakai oleh data dosen.';
        }

        if ($this->employees()->exists()) {
            $blockers['employees'] = 'Program studi masih dipakai oleh data tendik/staf.';
        }

        if (LeadershipAssignment::query()
            ->where('unit_type', 'study_program')
            ->where('unit_id', $this->id)
            ->exists()) {
            $blockers['leadership_assignments'] = 'Program studi masih dipakai pada data jabatan pimpinan.';
        }

        return $blockers;
    }

    public function canBeDeletedSafely(): bool
    {
        return $this->deletionBlockers() === [];
    }

    protected static function booted(): void
    {
        static::deleting(function (StudyProgram $studyProgram): void {
            if (! $studyProgram->canBeDeletedSafely()) {
                throw ValidationException::withMessages([
                    'study_program' => implode(' ', $studyProgram->deletionBlockers()).' Nonaktifkan data jika sudah tidak dipakai.',
                ]);
            }
        });
    }
}
