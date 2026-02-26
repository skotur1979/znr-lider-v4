<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class RiskAssessment extends Model
{
    protected $table = 'risk_assessments';

    protected $fillable = [
        'user_id',
        'tvrtka',
        'oib_tvrtke',
        'adresa_tvrtke',
        'broj_procjene',
        'datum_izrade',
        'vrsta_procjene',
    ];

    protected $casts = [
        'datum_izrade' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(RiskParticipant::class, 'risk_assessment_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(RiskRevision::class, 'risk_assessment_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RiskAttachment::class, 'risk_assessment_id');
    }
    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (blank($record->user_id)) {
                $record->user_id = Auth::id();
            }
        });
    }
}
