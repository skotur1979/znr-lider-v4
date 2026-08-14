<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskAssessment extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Procjene rizika';

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
        return $this->hasMany(
            RiskParticipant::class,
            'risk_assessment_id'
        );
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(
            RiskRevision::class,
            'risk_assessment_id'
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            RiskAttachment::class,
            'risk_assessment_id'
        );
    }
}