<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreImportBatch extends Model
{
    protected $fillable = [
        'source',
        'mode',
        'status',
        'started_at',
        'finished_at',
        'operator_id',
        'options',
        'summary',
        'decision_status',
        'decided_rows_count',
        'pending_decision_rows_count',
        'executable_rows_count',
        'rollback_status',
        'rolled_back_rows_count',
        'rollback_failed_rows_count',
        'rollback_skipped_rows_count',
        'rolled_back_by',
        'rolled_back_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'options' => 'array',
        'summary' => 'array',
        'decided_rows_count' => 'integer',
        'pending_decision_rows_count' => 'integer',
        'executable_rows_count' => 'integer',
        'rolled_back_rows_count' => 'integer',
        'rollback_failed_rows_count' => 'integer',
        'rollback_skipped_rows_count' => 'integer',
        'rolled_back_at' => 'datetime',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(CoreImportRecord::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
