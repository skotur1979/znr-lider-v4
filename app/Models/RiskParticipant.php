<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskParticipant extends Model
{
    protected $table = 'risk_participants';

    protected $fillable = [
        'risk_assessment_id',
        'ime_prezime',
        'uloga',
        'napomena',
    ];

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }
}