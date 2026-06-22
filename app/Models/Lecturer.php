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
        'front_title',
        'back_title',
        'title_updated_at',
        'email',
        'birth_place',
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
        'title_updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getDisplayNameWithTitleAttribute(): string
    {
        return app(\App\Services\CorePersonNameFormatter::class)
            ->formatWithTitle($this->front_title, $this->name, $this->back_title);
    }

    public function getFormalNameAttribute(): string
    {
        return $this->display_name_with_title;
    }

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
