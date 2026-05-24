<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_number',
        'name',
        'email',
        'phone',
        'address',
        'birth_date',
        'study_program_id',
        'enrolled_at',
        'status',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'birth_date' => 'date',
        'enrolled_at' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
