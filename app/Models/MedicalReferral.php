<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\FormVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        /*
         * Verzija obrasca mora biti određena i kod
         * kreiranja izvan Filament forme / budućeg API-ja.
         */
        static::creating(function (self $record): void {
            if (blank($record->form_version)) {
                $record->form_version =
                    FormVersionService::currentRa1();
            }
        });

        /*
         * Model-level integritet RA-1 ↔ Employee.
         *
         * Ova zaštita vrijedi neovisno o Filamentu,
         * odnosno i za budući API, import, servis ili
         * direktni model poziv.
         */
        static::saving(function (self $record): void {
            /*
             * Ručni unos nema vezu na Employee zapis.
             */
            if ($record->manual_entry) {
                $record->employee_id = null;

                if (blank($record->full_name)) {
                    throw ValidationException::withMessages([
                        'full_name' =>
                            'Ime i prezime obavezni su kod ručnog unosa RA-1 uputnice.',
                    ]);
                }

                if (blank($record->oib)) {
                    throw ValidationException::withMessages([
                        'oib' =>
                            'OIB je obavezan kod ručnog unosa RA-1 uputnice.',
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
             * Ako nije ručni unos, RA-1 mora biti
             * povezan sa zaposlenikom.
             */
            if (blank($record->employee_id)) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'Zaposlenik je obavezan kada RA-1 nije ručni unos.',
                ]);
            }

            /*
             * Kod organizacijskog korisnika očekivani
             * owner uzimamo iz prijavljenog korisnika.
             *
             * Time tenant provjera radi čak i ako
             * creating observer naknadno postavlja user_id.
             */
            $ownerId = (int) ($record->user_id ?? 0);

            $user = Auth::user();

            if ($user && ! $user->isSuperAdmin()) {
                $ownerId = (int) ($user->ownerId() ?? 0);
            }

            if ($ownerId <= 0) {
                throw ValidationException::withMessages([
                    'user_id' =>
                        'Organizacija RA-1 uputnice nije valjano određena.',
                ]);
            }

            /*
             * withTrashed() omogućuje da povijesni RA-1
             * ostane valjan i ako je zaposlenik kasnije
             * soft-deaktiviran.
             */
            $employee = Employee::withTrashed()
                ->find($record->employee_id);

            if (! $employee) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'Odabrani zaposlenik ne postoji.',
                ]);
            }

            /*
             * Employee i RA-1 moraju pripadati
             * istoj organizaciji.
             */
            if ((int) $employee->user_id !== $ownerId) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'Odabrani zaposlenik ne pripada organizaciji RA-1 uputnice.',
                ]);
            }

            /*
             * Novi RA-1 ne smije se povezati s
             * deaktiviranim zaposlenikom.
             *
             * Postojeći povijesni RA-1 smije ostati
             * povezan ako employee_id nije promijenjen.
             */
            if (
                $employee->trashed()
                && (
                    ! $record->exists
                    || $record->isDirty('employee_id')
                )
            ) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'Nije moguće povezati RA-1 s deaktiviranim zaposlenikom.',
                ]);
            }
        });
    }

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
        return $this->employee?->name
            ?? ($this->full_name ?: 'RA-1 Uputnica');
    }

    public function getFilamentTitleAttribute(): string
    {
        return (
            $this->employee?->name
            ?? $this->full_name
            ?? 'RA-1'
        )
            . ' - '
            . ($this->referral_number ?: '-');
    }

    public static function formVersions(): array
    {
        return FormVersionService::ra1Versions();
    }

    public function getFormVersionLabelAttribute(): string
    {
        return FormVersionService::ra1Versions()[
            $this->form_version
        ]
            ?? $this->form_version
            ?? FormVersionService::currentRa1();
    }
}