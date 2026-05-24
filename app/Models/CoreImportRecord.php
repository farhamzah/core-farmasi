<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreImportRecord extends Model
{
    protected $fillable = [
        'core_import_batch_id',
        'source_table',
        'source_id',
        'source_identifier',
        'target_table',
        'target_id',
        'target_type',
        'action',
        'payload_snapshot',
        'message',
        'validation_status',
        'suggested_action',
        'admin_decision',
        'decision_note',
        'decided_by',
        'decided_at',
        'normalized_data',
        'errors',
        'warnings',
        'conflicts',
        'execution_status',
        'executed_action',
        'executed_by',
        'executed_at',
        'previous_snapshot',
        'rollback_status',
        'rollback_note',
        'rolled_back_by',
        'rolled_back_at',
        'rollback_result',
        'created_user_id',
        'linked_user_id',
    ];

    protected $casts = [
        'payload_snapshot' => 'array',
        'normalized_data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'conflicts' => 'array',
        'decided_at' => 'datetime',
        'executed_at' => 'datetime',
        'previous_snapshot' => 'array',
        'rolled_back_at' => 'datetime',
        'rollback_result' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CoreImportBatch::class, 'core_import_batch_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function rollbackActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }
}
