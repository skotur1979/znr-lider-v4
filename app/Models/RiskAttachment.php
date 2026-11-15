<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    protected static function booted(): void
    {
        static::deleted(
            function (
                RiskAttachment $attachment
            ): void {
                $path = ltrim(
                    (string) $attachment->file_path,
                    '/'
                );

                if (blank($path)) {
                    return;
                }

                DB::afterCommit(
                    function () use ($path): void {
                        foreach (
                            ['local', 'public']
                            as $disk
                        ) {
                            if (
                                Storage::disk($disk)
                                    ->exists($path)
                            ) {
                                Storage::disk($disk)
                                    ->delete($path);
                            }
                        }
                    }
                );
            }
        );
    }
}