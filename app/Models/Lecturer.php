<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lecturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'lecturer_number',
        'national_id_number',
        'nip',
        'nidn',
        'nidk',
        'nuptk',
        'name',
        'email',
        'birth_date',
        'department_id',
        'study_program_id',
        'phone',
        'address',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'birth_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
