<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPermit extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Dozvole za rad';

    protected $fillable = [
        'user_id',
        'permit_number',
        'issue_date',
        'valid_from',
        'valid_until',
        'work_types',
        'other_work_type',
        'request_or_regulation',
        'executor_types',
        'worker_1',
        'worker_2',
        'worker_3',
        'worker_4',
        'worker_5',
        'worker_6',
        'worker_7',
        'worker_8',
        'worker_9',
        'work_description',
        'contact_person',
        'phone',
        'required_measures',
        'additional_measures',
        'required_equipment',
        'work_hazards',
        'other_hazard',
        'required_ppe',
        'requester_name',
        'requester_signature',
        'approver_name',
        'approver_signature',
        'extension_valid_from',
        'extension_valid_until',
        'extension_approver_name',
        'extension_approver_signature',
        'works_finished',
        'unfinished_reason',
        'checked_after',
        'verification_name',
        'verification_signature',
        'verification_date',
        'verification_time',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'work_types' => 'array',
        'executor_types' => 'array',
        'required_measures' => 'array',
        'work_hazards' => 'array',
        'required_ppe' => 'array',
        'extension_valid_from' => 'datetime',
        'extension_valid_until' => 'datetime',
        'works_finished' => 'boolean',
        'verification_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (blank($record->user_id) && auth()->check()) {
                $record->user_id = auth()->id();
            }
        });
    }

    public static function workTypeOptions(): array
    {
        return [
            'hot_work' => 'Vrući radovi',
            'work_at_height' => 'Radovi na visini',
            'electrical_work' => 'Električarski radovi',
            'hazardous_chemicals' => 'Rad s opasnim kemikalijama',
            'other' => 'Ostalo',
        ];
    }

    public static function executorTypeOptions(): array
    {
        return [
            'company_employees' => 'Zaposlenici tvrtke',
            'external_contractors' => 'Vanjski izvođači',
        ];
    }

    public static function requiredMeasuresOptions(): array
    {
        return [
            'remove_flammable_material' => 'Ukloniti zapaljivi materijal u zoni rada od 10 m',
            'place_fire_extinguishers' => 'U zoni rada postaviti aparate za gašenje požara',
            'check_welding_bottles' => 'Provjeriti ispravnost boca za zavarivanje',
            'check_welding_hoses' => 'Provjeriti ispravnost crijeva opreme za zavarivanje',
            'cover_openings' => 'Pokriti sve otvore u krugu 10 m',
            'check_grounding_cable' => 'Provjeriti stanje napojnog kabela za uzemljenje',
            'fire_blankets' => 'Postaviti požarne pokrivače za zadržavanje iskri',
            'additional_lighting' => 'Osigurati dodatnu rasvjetu',
            'check_dangerous_gases' => 'Provjeriti prisutnost opasnih plinova',
            'mark_work_area' => 'Ograditi i označiti mjesto rada',
            'lototo' => 'Upotreba LOTOTO sustava rada',
            'additional_risk_assessment' => 'Dodatna procjena rizika',
            'mandatory_scaffold' => 'Obavezno korištenje radne skele',
            'safety_rope' => 'Sigurnosno uže',
            'additional_access_exit' => 'Dodatni pristup / izlaz',
            'five_rules_electrical' => '5 pravila za rad na el. postrojenjima i instalacijama',
        ];
    }

    public static function hazardOptions(): array
    {
        return [
            'fall_from_height' => 'Pad s visine',
            'sharp_objects' => 'Oštri predmeti',
            'mechanical_lifting' => 'Mehaničko podizanje',
            'stored_energy' => 'Pohranjena energija',
            'hot_cold_surfaces' => 'Vruće / hladne površine',
            'acids_alkalis' => 'Kiseline / lužine',
            'heavy_loads' => 'Teški tereti',
            'electrical_hazard' => 'Električna opasnost',
            'lack_of_oxygen' => 'Nedostatak kisika',
            'crushing' => 'Zgnječenja / prignječenja',
            'vehicle_impact' => 'Udar / nalet vozila',
            'bad_weather' => 'Loši vremenski uvjeti',
            'noise' => 'Buka',
            'explosive_flammable' => 'Eksplozivne / zapaljive tvari',
            'outdoor_work' => 'Rad na otvorenom',
            'poor_lighting' => 'Nedovoljna osvijetljenost',
            'confined_space' => 'Rad u skučenom prostoru',
            'repetitive_movements' => 'Ponavljajući pokreti',
            'walking_surfaces' => 'Površine za kretanje',
            'high_pressure' => 'Visoki tlak',
            'prolonged_work' => 'Produljeni rad',
            'eye_strain' => 'Napor vida',
            'dangerous_vapors_gases' => 'Opasne pare / plinovi',
            'other' => 'Ostalo',
        ];
    }

    public static function ppeOptions(): array
    {
        return [
            'safety_shoes' => 'Zaštitne cipele s kapicom',
            'safety_glasses' => 'Zaštitne naočale',
            'hearing_protection' => 'Antifoni ili čepići',
            'helmet' => 'Zaštitna kaciga',
            'work_clothes' => 'Radno odijelo',
            'protective_mask' => 'Zaštitna maska',
            'respirator' => 'Respirator',
            'welding_mask' => 'Maska za zavarivanje',
            'rubber_boots' => 'Gumene čizme',
            'half_mask' => 'Zaštitna polumaska',
            'reflective_vest' => 'Reflektirajući prsluk',
            'cap_with_protection' => 'Šilt kapa s zaštitom',
            'protective_gloves' => 'Zaštitne rukavice',
            'face_shield' => 'Zaštitni vizir',
            'fall_protection_belt' => 'Pojas za rad na visini',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}