<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Employee extends Model
{
    use SoftDeletes;

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

    public function certificates(): HasMany
{
    return $this->hasMany(\App\Models\EmployeeCertificate::class, 'employee_id');
}
public function getOibAttribute(): ?string
{
    return $this->attributes['OIB'] ?? null;
}
}

