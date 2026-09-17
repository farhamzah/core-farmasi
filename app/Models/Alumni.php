<?php

namespace App\Models;

use App\Services\CorePersonNameFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumni extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni';

    protected $fillable = [
        'user_id',
        'student_id',
        'study_program_id',
        'student_number',
        'name',
        'personal_email',
        'whatsapp',
        'program_name_snapshot',
        'entry_year',
        'graduation_year',
        'graduation_date',
        'status',
        'source',
        'active',
        'notes',
    ];

    protected $casts = [
        'graduation_date' => 'date',
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = app(CorePersonNameFormatter::class)
            ->normalizePersonName($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
