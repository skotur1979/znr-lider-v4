<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Inspection extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'number',
        'inspection_type',
        'title',
        'location',
        'performed_at',
        'performed_by',
        'present_persons',
        'status',
        'overall_status',
        'five_s_score',
        'description',
        'conclusion',
        'attachments',
    ];

    protected $casts = [
        'performed_at' => 'date',
        'attachments' => 'array',
        'five_s_score' => 'integer',
    ];

    protected static function booted(): void
{
    static::creating(function (Inspection $inspection) {
        if (blank($inspection->number)) {
            $year = now()->format('Y');
            

            $countThisYear = static::query()
                ->whereYear('created_at', now()->year)
                ->count() + 1;

            $inspection->number = 'N-' . str_pad((string) $countThisYear, 2, '0', STR_PAD_LEFT) . '/' . $year;
        }

        if (blank($inspection->performed_by) && auth()->check()) {
            $inspection->performed_by = auth()->user()->name ?? null;
        }

        if (blank($inspection->inspection_type)) {
            $inspection->inspection_type = 'general';
        }
    });

    static::saved(function (Inspection $inspection) {
        $inspection->refreshFiveSScore();
    });
}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(InspectionFinding::class)->latest();
    }

    public function refreshFiveSScore(): void
    {
        $avg = $this->findings()
            ->whereNotNull('score_value')
            ->avg('score_value');

        $score = $avg !== null ? (int) round(($avg / 5) * 100) : null;

        if ($this->five_s_score !== $score) {
            $this->forceFill(['five_s_score' => $score])->saveQuietly();
        }
    }

    public function getFiveSScoreLabelAttribute(): string
    {
        return filled($this->five_s_score) ? $this->five_s_score . '%' : '-';
    }
    public function zones(): HasMany
{
    return $this->hasMany(InspectionZone::class)->orderBy('sort_order')->orderBy('id');
}

public function zoneAnswers(): HasMany
{
    return $this->hasMany(InspectionZoneAnswer::class);
}
}
