<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\FormVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalReferral extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'RA-1 uputnice';

    protected $fillable = [
        'user_id',
        'employee_id',
        'manual_entry',
        'form_version',

        'referral_number',
        'referral_date',

        'employer_name',
        'employer_address',
        'employer_oib',

        'full_name',
        'name_of_parents',
        'place_of_birth',
        'oib',
        'job_title',
        'education',

        'health_jobs_description',
        'law_reference',
        'law_reference1',
        'special_conditions',

        'total_years',
        'work_years_in_job',

        'exam_type',
        'last_exam_date',
        'last_exam_reference',
        'last_exam_reference1',
        'last_exam_reference2',
        'last_exam_reference3',

        'short_description',
        'tools',
        'job_tasks',

        'workplace_location',
        'organization',
        'body_position',

        'lifting_enabled',
        'lifting_weight',
        'carrying_enabled',
        'carrying_weight',
        'pushing_enabled',
        'pushing_weight',

        'job_characteristics',
        'hazards',

        'chemcial_substances',
        'biological_hazards',
    ];

    protected $casts = [
        'manual_entry' => 'boolean',

        'referral_date' => 'date',
        'last_exam_date' => 'date',

        'exam_type' => 'array',
        'workplace_location' => 'array',
        'organization' => 'array',
        'body_position' => 'array',
        'job_characteristics' => 'array',
        'hazards' => 'array',

        'lifting_enabled' => 'boolean',
        'carrying_enabled' => 'boolean',
        'pushing_enabled' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->employee->name ?? ($this->full_name ?: 'RA-1 Uputnica');
    }

    public function getFilamentTitleAttribute(): string
    {
        return ($this->employee->name ?? $this->full_name ?? 'RA-1')
            . ' - ' . ($this->referral_number ?: '-');
    }
    public static function formVersions(): array
    {
        return FormVersionService::ra1Versions();
    }

    public function getFormVersionLabelAttribute(): string
    {
        return FormVersionService::ra1Versions()[$this->form_version]
            ?? $this->form_version
            ?? FormVersionService::currentRa1();
    }
}