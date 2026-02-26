<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskRevision extends Model
{
    protected $table = 'risk_revisions';

    protected $fillable = [
        'risk_assessment_id',
        'revizija_broj',
        'datum_izrade',
    ];

    protected $casts = [
        'datum_izrade' => 'date',
    ];

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }
}
