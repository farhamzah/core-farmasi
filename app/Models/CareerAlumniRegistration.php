<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerAlumniRegistration extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_MANUAL_REVIEW = 'manual_review';

    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'reference', 'student_number', 'full_name', 'claimed_program', 'graduation_year',
        'personal_email', 'whatsapp', 'password_hash', 'status', 'conflict_code', 'account_resolution', 'review_note',
        'matched_user_id', 'reviewed_by', 'reviewed_at',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }
}
