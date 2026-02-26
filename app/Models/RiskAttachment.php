<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAttachment extends Model
{
    protected $table = 'risk_attachments';

    protected $fillable = [
        'risk_assessment_id',
        'naziv',
        'file_path',
    ];

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'risk_assessment_id');
    }
}
