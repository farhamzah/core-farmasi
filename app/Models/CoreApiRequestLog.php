<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'core_api_client_id',
        'app_code',
        'client_id',
        'method',
        'path',
        'route_name',
        'status_code',
        'ability',
        'ip_address',
        'user_agent',
        'request_id',
        'duration_ms',
        'is_success',
        'error_code',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'duration_ms' => 'integer',
        'is_success' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CoreApiClient::class, 'core_api_client_id');
    }
}
