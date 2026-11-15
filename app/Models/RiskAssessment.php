<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    protected array $attachmentPathsPendingDeletion = [];

    protected static function booted(): void
    {
        static::deleting(
            function (
                RiskAssessment $riskAssessment
            ): void {
                $riskAssessment
                    ->attachmentPathsPendingDeletion =
                    $riskAssessment
                        ->attachments()
                        ->whereNotNull('file_path')
                        ->pluck('file_path')
                        ->filter(
                            fn ($path) =>
                                is_string($path)
                                && filled($path)
                        )
                        ->map(
                            fn ($path) =>
                                ltrim($path, '/')
                        )
                        ->values()
                        ->all();
            }
        );

        static::deleted(
            function (
                RiskAssessment $riskAssessment
            ): void {
                $paths =
                    $riskAssessment
                        ->attachmentPathsPendingDeletion;

                if (empty($paths)) {
                    return;
                }

                DB::afterCommit(
                    function () use ($paths): void {
                        foreach ($paths as $path) {
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
                    }
                );
            }
        );
    }

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