<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Zaposlenici';

    protected $fillable = [
        'user_id',
        'name',
        'job_title',
        'education',
        'place_of_birth',
        'name_of_parents',
        'address',
        'gender',
        'OIB',
        'phone',
        'email',
        'workplace',
        'organization_unit',
        'contract_type',
        'employeed_at',
        'contract_ended_at',
        'medical_examination_valid_from',
        'medical_examination_valid_until',
        'article',
        'remark',
        'occupational_safety_valid_from',
        'fire_protection_valid_from',
        'fire_protection_statement_at',
        'evacuation_valid_from',
        'first_aid_valid_from',
        'first_aid_valid_until',
        'toxicology_valid_from',
        'toxicology_valid_until',
        'handling_flammable_materials_valid_from',
        'handling_flammable_materials_valid_until',
        'employers_authorization_valid_from',
        'employers_authorization_valid_until',
        'pdf',
    ];

    protected $casts = [
        'employeed_at' => 'date',
        'contract_ended_at' => 'date',
        'medical_examination_valid_from' => 'date',
        'medical_examination_valid_until' => 'date',
        'occupational_safety_valid_from' => 'date',
        'fire_protection_valid_from' => 'date',
        'fire_protection_statement_at' => 'date',
        'evacuation_valid_from' => 'date',
        'first_aid_valid_from' => 'date',
        'first_aid_valid_until' => 'date',
        'toxicology_valid_from' => 'date',
        'toxicology_valid_until' => 'date',
        'handling_flammable_materials_valid_from' => 'date',
        'handling_flammable_materials_valid_until' => 'date',
        'employers_authorization_valid_from' => 'date',
        'employers_authorization_valid_until' => 'date',
        'pdf' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(EmployeeCertificate::class, 'employee_id');
    }

    public function alcoholTests(): HasMany
    {
        return $this->hasMany(EmployeeAlcoholTest::class, 'employee_id');
    }

    public function latestAlcoholTest(): HasOne
    {
        return $this->hasOne(EmployeeAlcoholTest::class, 'employee_id')->latestOfMany('test_date');
    }

    public function nightWorkReferrals(): HasMany
    {
        return $this->hasMany(NightWorkReferral::class);
    }

    public function getOibAttribute(): ?string
    {
        return $this->attributes['OIB'] ?? null;
    }

    public function znrTrainingDueDate(): ?Carbon
    {
        if ($this->occupational_safety_valid_from) {
            return null;
        }

        if (! $this->employeed_at) {
            return null;
        }

        return Carbon::parse($this->employeed_at)->addDays(60)->startOfDay();
    }

    public function znrTrainingStatus(): string
    {
        $dueDate = $this->znrTrainingDueDate();

        if (! $dueDate) {
            return 'completed';
        }

        $today = Carbon::today();
        $soon = $today->copy()->addDays(30);

        if ($dueDate->lt($today)) {
            return 'expired';
        }

        if ($dueDate->lte($soon)) {
            return 'expiring';
        }

        return 'ok';
    }

    public function isZnrTrainingExpired(): bool
    {
        return $this->znrTrainingStatus() === 'expired';
    }

    public function isZnrTrainingExpiring(): bool
    {
        return $this->znrTrainingStatus() === 'expiring';
    }

    public function znrTrainingDueLabel(): ?string
    {
        return $this->znrTrainingDueDate()?->format('d.m.Y.');
    }

    public function znrTrainingTooltip(): string
    {
        if ($this->occupational_safety_valid_from) {
            return 'ZNR osposobljavanje je upisano.';
        }

        if (! $this->employeed_at) {
            return 'Nije upisan datum zaposlenja.';
        }

        return 'ZNR treba položiti najkasnije do ' . $this->znrTrainingDueDate()?->format('d.m.Y.') . ' (60 dana od zaposlenja).';
    }

    public function znrTrainingBadgeColor(): string
    {
        return match ($this->znrTrainingStatus()) {
            'expired' => 'danger',
            'expiring' => 'warning',
            'ok' => 'gray',
            default => 'success',
        };
    }

    public function znrTrainingBadgeIcon(): string
    {
        return match ($this->znrTrainingStatus()) {
            'expired' => 'heroicon-m-exclamation-triangle',
            'expiring' => 'heroicon-m-clock',
            'ok' => 'heroicon-m-information-circle',
            default => 'heroicon-m-check-circle',
        };
    }
}