<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAttachment extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Prilozi procjene rizika';

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