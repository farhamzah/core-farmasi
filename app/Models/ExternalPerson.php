<?php

namespace App\Models;

use App\Services\CorePersonNameFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalPerson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'external_number',
        'name',
        'front_title',
        'back_title',
        'title_updated_at',
        'email',
        'phone',
        'institution_name',
        'institution_type',
        'position_title',
        'profession',
        'identity_number',
        'address',
        'status',
        'notes',
    ];

    protected $casts = [
        'title_updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = app(CorePersonNameFormatter::class)
            ->normalizePersonName($value);
    }

    public function getDisplayNameWithTitleAttribute(): string
    {
        return app(CorePersonNameFormatter::class)->formatWithTitle(
            $this->front_title,
            $this->name,
            $this->back_title,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
