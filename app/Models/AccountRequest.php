<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_STUDENT = 'student';
    public const TYPE_LECTURER = 'lecturer';
    public const TYPE_EMPLOYEE = 'employee';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'request_type',
        'name',
        'email',
        'phone',
        'identity_number',
        'student_number',
        'lecturer_number',
        'employee_number',
        'study_program_id',
        'department_id',
        'requested_role',
        'requested_app_code',
        'status',
        'notes',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_user_id',
        'submitted_ip',
        'submitted_user_agent',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_user_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_STUDENT => 'Mahasiswa',
            self::TYPE_LECTURER => 'Dosen',
            self::TYPE_EMPLOYEE => 'Tendik / Staf / Laboran',
            self::TYPE_OTHER => 'Lainnya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
