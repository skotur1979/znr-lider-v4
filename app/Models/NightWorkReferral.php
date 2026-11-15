<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\FormVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NightWorkReferral extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'NR-1 uputnice';

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

        'exam_type',
        'last_exam_date',
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

    protected static function booted(): void
    {
        /*
         * Ako verzija obrasca nije zadana kroz
         * UI/API, koristi trenutno važeću NR-1
         * verziju.
         */
        static::creating(
            function (self $record): void {
                if (blank($record->form_version)) {
                    $record->form_version =
                        FormVersionService::currentNr1();
                }
            }
        );

        /*
         * Integritet ručnog/povezanog NR-1 zapisa
         * mora vrijediti neovisno o Filament formi.
         */
        static::saving(
            function (self $record): void {
                /*
                 * Ručni unos ne smije ostati
                 * povezan s Employee zapisom.
                 */
                if ($record->manual_entry) {
                    $record->employee_id = null;

                    if (blank($record->full_name)) {
                        throw ValidationException::withMessages([
                            'full_name' =>
                                'Ime i prezime obavezni su kod ručnog unosa NR-1 uputnice.',
                        ]);
                    }

                    if (blank($record->oib)) {
                        throw ValidationException::withMessages([
                            'oib' =>
                                'OIB je obavezan kod ručnog unosa NR-1 uputnice.',
                        ]);
                    }

                    if (
                        preg_match(
                            '/^\d{11}$/',
                            (string) $record->oib
                        ) !== 1
                    ) {
                        throw ValidationException::withMessages([
                            'oib' =>
                                'OIB mora sadržavati točno 11 znamenki.',
                        ]);
                    }

                    return;
                }

                /*
                 * Ako nije ručni unos, zaposlenik
                 * mora biti povezan.
                 */
                if (blank($record->employee_id)) {
                    throw ValidationException::withMessages([
                        'employee_id' =>
                            'Zaposlenik je obavezan kada NR-1 nije ručni unos.',
                    ]);
                }

                /*
                 * Određujemo stvarnog ownera.
                 *
                 * Organizacijski korisnik uvijek
                 * radi unutar svoje organizacije.
                 *
                 * Kod superadmin uređivanja koristi
                 * se owner postojećeg zapisa.
                 */
                $ownerId =
                    (int) ($record->user_id ?? 0);

                $user = Auth::user();

                if (
                    $user
                    && ! $user->isSuperAdmin()
                ) {
                    $ownerId =
                        (int) ($user->ownerId() ?? 0);
                }

                if ($ownerId <= 0) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Organizacija NR-1 uputnice nije valjano određena.',
                    ]);
                }

                /*
                 * Employee mora postojati i
                 * pripadati istom owneru.
                 *
                 * withTrashed() omogućuje provjeru
                 * postojećih povijesnih veza.
                 */
                $employee = Employee::withTrashed()
                    ->find($record->employee_id);

                if (! $employee) {
                    throw ValidationException::withMessages([
                        'employee_id' =>
                            'Odabrani zaposlenik ne postoji.',
                    ]);
                }

                if (
                    (int) $employee->user_id
                    !== $ownerId
                ) {
                    throw ValidationException::withMessages([
                        'employee_id' =>
                            'Odabrani zaposlenik ne pripada ovoj organizaciji.',
                    ]);
                }

                /*
                 * Novi NR-1 zapis ne smije se
                 * povezati s deaktiviranim
                 * zaposlenikom.
                 *
                 * Isto vrijedi ako se employee_id
                 * naknadno mijenja.
                 */
                if (
                    $employee->trashed()
                    && (
                        ! $record->exists
                        || $record->isDirty(
                            'employee_id'
                        )
                    )
                ) {
                    throw ValidationException::withMessages([
                        'employee_id' =>
                            'NR-1 uputnicu nije moguće povezati s deaktiviranim zaposlenikom.',
                    ]);
                }
            }
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public static function formVersions(): array
    {
        return FormVersionService::nr1Versions();
    }

    public function getFormVersionLabelAttribute(): string
    {
        return FormVersionService::nr1Versions()[
            $this->form_version
        ]
            ?? $this->form_version
            ?? FormVersionService::currentNr1();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->employee?->name
            ?? ($this->full_name ?: 'NR-1 Uputnica');
    }

    public function getFilamentTitleAttribute(): string
    {
        return (
            $this->employee?->name
            ?? $this->full_name
            ?? 'NR-1'
        )
            . ' - '
            . ($this->referral_number ?: '-');
    }
}