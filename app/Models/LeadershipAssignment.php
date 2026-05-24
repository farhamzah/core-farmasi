<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class LeadershipAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'position_type',
        'position_title',
        'unit_type',
        'unit_id',
        'person_type',
        'person_id',
        'title_prefix',
        'title_suffix',
        'official_name_snapshot',
        'decree_number',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'person_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'person_id');
    }

    public function getPersonAttribute(): Lecturer|Employee|null
    {
        return match ($this->person_type) {
            'lecturer' => $this->lecturer,
            'employee' => $this->employee,
            default => null,
        };
    }

    protected function personDisplayName(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (filled($this->official_name_snapshot)) {
                return $this->official_name_snapshot;
            }

            return $this->person?->name;
        });
    }

    protected function unitLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->unit_type === 'study_program' && filled($this->unit_id)) {
                return StudyProgram::query()->find($this->unit_id)?->name
                    ?? "Program Studi #{$this->unit_id}";
            }

            if (in_array($this->unit_type, ['faculty', 'department'], true) && filled($this->unit_id)) {
                return Department::query()->find($this->unit_id)?->name
                    ?? "{$this->unitTypeLabel()} #{$this->unit_id}";
            }

            return $this->unitTypeLabel();
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query, mixed $date = null): Builder
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return $query
            ->whereDate('start_date', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }

    public function scopeForPosition(Builder $query, string $positionType): Builder
    {
        return $query->where('position_type', $positionType);
    }

    public function scopeForUnit(Builder $query, string $unitType, int|string|null $unitId = null): Builder
    {
        $query->where('unit_type', $unitType);

        if ($unitId !== null) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    private function unitTypeLabel(): string
    {
        return config("core_leadership.unit_types.{$this->unit_type}", (string) $this->unit_type);
    }
}
