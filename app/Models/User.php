<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    public const MAX_SUBUSERS_PER_ORGANIZATION = 5;

    /**
     * Moduli za koje glavni korisnik organizacije može
     * određivati dozvole svojim podkorisnicima.
     *
     * OVAJ POPIS ZA SADA NE ŠIRIMO.
     */
    public const CONTROLLED_MODULES = [
        'observations' => 'Zapažanja',
        'employees' => 'Zaposlenici',
        'machines' => 'Radna oprema',
        'waste_tracking_forms' => 'Prateći listovi',
        'miscellaneous' => 'Ostala ispitivanja',
        'categories' => 'Kategorije ispitivanja',
    ];

    /**
     * Dozvole koje koristimo u kontroliranim modulima.
     */
    public const MODULE_PERMISSION_ACTIONS = [
        'view' => 'Pregled',
        'create' => 'Dodavanje',
        'update' => 'Uređivanje',
        'delete' => 'Brisanje',
    ];

    protected $fillable = [
        'name',
        'organization_name',
        'email',
        'password',
        'is_admin',
        'role',
        'quick_actions',
        'module_permissions',
        'parent_user_id',
        'can_manage_subusers',
        'is_active',
        'daily_status_email_enabled',
        'weekly_status_email_enabled',
        'cookies_version',
        'dpa_version',
        'security_version',
        'retention_version',
        'cookies_accepted_at',
        'dpa_accepted_at',
        'security_accepted_at',
        'retention_accepted_at',
        'accepted_terms_at',
        'accepted_privacy_at',
        'terms_version',
        'privacy_version',
        'newsletter_opt_in',
        'legal_consent_withdrawn_at',
        'legal_consent_withdrawn_reason',
        'account_deletion_requested_at',
        'account_deletion_reason',
        'account_status',
        'gdpr_request_status',
        'gdpr_request_processed_at',
        'last_activity_at',
        'storage_quota_mb',
        'email_2fa_code_hash',
        'email_2fa_expires_at',
        'email_2fa_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_2fa_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'quick_actions' => 'array',
            'module_permissions' => 'array',
            'can_manage_subusers' => 'boolean',
            'is_active' => 'boolean',
            'daily_status_email_enabled' => 'boolean',
            'weekly_status_email_enabled' => 'boolean',
            'accepted_terms_at' => 'datetime',
            'accepted_privacy_at' => 'datetime',
            'newsletter_opt_in' => 'boolean',
            'cookies_accepted_at' => 'datetime',
            'dpa_accepted_at' => 'datetime',
            'security_accepted_at' => 'datetime',
            'retention_accepted_at' => 'datetime',
            'legal_consent_withdrawn_at' => 'datetime',
            'account_deletion_requested_at' => 'datetime',
            'deleted_at' => 'datetime',
            'storage_quota_mb' => 'integer',
            'gdpr_request_processed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'email_2fa_expires_at' => 'datetime',
            'email_2fa_verified_at' => 'datetime',
        ];
    }

    /**
     * Pristup Filament panelu.
     *
     * Deaktivirani korisnik ne smije pristupiti aplikaciji
     * bez obzira na svoju ulogu.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->isSuperAdmin()
            || $this->isOrgAdmin()
            || $this->isOrgUser();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false)
            || in_array(
                $this->role,
                [
                    'admin',
                    'super_admin',
                    'superadmin',
                ],
                true
            );
    }

    public function isOrgAdmin(): bool
    {
        return $this->role === 'org_admin';
    }

    public function isOrgUser(): bool
    {
        return $this->role === 'org_user';
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * CENTRALNA MULTI-TENANT LOGIKA.
     *
     * Glavni korisnik:
     * ownerId() = vlastiti ID
     *
     * Podkorisnik:
     * ownerId() = ID glavnog korisnika
     */
    public function ownerId(): int
    {
        return $this->parent_user_id ?: $this->id;
    }

    /**
     * Glavni korisnik organizacije.
     */
    public function owner(): self
    {
        if (
            $this->parent_user_id
            && $this->parentUser
        ) {
            return $this->parentUser;
        }

        return $this;
    }

    /**
     * Broj podkorisnika organizacije.
     */
    public function subusersCountForLimit(): int
    {
        return self::query()
            ->where(
                'parent_user_id',
                $this->ownerId()
            )
            ->where(
                'role',
                'org_user'
            )
            ->withoutTrashed()
            ->count();
    }

    public function canAddMoreSubusers(): bool
    {
        return $this->subusersCountForLimit()
            < self::MAX_SUBUSERS_PER_ORGANIZATION;
    }

    public function subusersLimitText(): string
    {
        return $this->subusersCountForLimit()
            . ' / '
            . self::MAX_SUBUSERS_PER_ORGANIZATION;
    }

    public function remainingSubusers(): int
    {
        return max(
            0,
            self::MAX_SUBUSERS_PER_ORGANIZATION
                - $this->subusersCountForLimit()
        );
    }

    /**
     * Moduli koje je superadmin omogućio cijeloj organizaciji.
     *
     * Podkorisnik koristi popis modula glavnog korisnika.
     */
    public function moduleAccess(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        $owner = $this->owner();

        return is_array($owner->quick_actions)
            ? $owner->quick_actions
            : [];
    }

    /**
     * Provjera je li organizaciji omogućen modul.
     */
    public function canAccessModule(
        string $moduleKey
    ): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array(
            $moduleKey,
            $this->moduleAccess(),
            true
        );
    }

    /**
     * Provjerava koristi li modul granularne
     * view/create/update/delete dozvole.
     */
    public static function isControlledModule(
        string $moduleKey
    ): bool {
        return array_key_exists(
            $moduleKey,
            self::CONTROLLED_MODULES
        );
    }

    /**
     * Sve dozvole za kontrolirani modul.
     */
    public static function fullModulePermissionSet(): array
    {
        return array_keys(
            self::MODULE_PERMISSION_ACTIONS
        );
    }

    /**
     * Zadane dozvole novog podkorisnika.
     *
     * Novi korisnik standardno dobiva sva prava,
     * a glavni korisnik ih može isključiti.
     */
    public static function defaultModulePermissions(): array
    {
        $permissions = [];

        foreach (
            array_keys(self::CONTROLLED_MODULES)
            as $moduleKey
        ) {
            $permissions[$moduleKey] =
                self::fullModulePermissionSet();
        }

        return $permissions;
    }

    /**
     * Dozvole spremljene za određeni modul.
     *
     * module_permissions = NULL:
     * stari korisnici zadržavaju puna prava
     * zbog kompatibilnosti prije uvođenja sustava.
     */
    public function permissionsForModule(
        string $moduleKey
    ): array {
        if (! self::isControlledModule($moduleKey)) {
            return self::fullModulePermissionSet();
        }

        if ($this->module_permissions === null) {
            return self::fullModulePermissionSet();
        }

        $permissions =
            $this->module_permissions[$moduleKey]
            ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(
            array_intersect(
                $permissions,
                array_keys(
                    self::MODULE_PERMISSION_ACTIONS
                )
            )
        );
    }

    /**
     * CENTRALNA provjera granularnih dozvola.
     *
     * Pravila:
     *
     * Superadmin:
     * - sva prava.
     *
     * Glavni korisnik:
     * - sva prava u modulima svoje organizacije.
     *
     * Podkorisnik:
     * - samo prava koja mu je dodijelio glavni korisnik.
     *
     * Nepoznata/neispravna uloga:
     * - nema prava (fail closed).
     */
    public function hasModulePermission(
        string $moduleKey,
        string $permission
    ): bool {
        /*
         * Nepoznata vrsta dozvole nikada nije dopuštena.
         */
        if (
            ! array_key_exists(
                $permission,
                self::MODULE_PERMISSION_ACTIONS
            )
        ) {
            return false;
        }

        /*
         * Superadmin.
         */
        if ($this->isSuperAdmin()) {
            return true;
        }

        /*
         * Organizacija prvo mora imati omogućen modul.
         */
        if (! $this->canAccessModule($moduleKey)) {
            return false;
        }

        /*
         * Granularne dozvole vrijede samo za
         * šest CONTROLLED_MODULES modula.
         */
        if (! self::isControlledModule($moduleKey)) {
            return $this->isOrgAdmin()
                || $this->isOrgUser();
        }

        /*
         * Glavni korisnik ima sva prava
         * u svojoj organizaciji.
         */
        if ($this->isOrgAdmin()) {
            return true;
        }

        /*
         * Podkorisnik koristi spremljene dozvole.
         */
        if ($this->isOrgUser()) {
            return in_array(
                $permission,
                $this->permissionsForModule(
                    $moduleKey
                ),
                true
            );
        }

        /*
         * Fail closed.
         *
         * Ako korisnik nema jednu od poznatih uloga,
         * ne dopuštamo akciju.
         */
        return false;
    }

    public function canViewModuleRecords(
        string $moduleKey
    ): bool {
        return $this->hasModulePermission(
            $moduleKey,
            'view'
        );
    }

    public function canCreateModuleRecords(
        string $moduleKey
    ): bool {
        return $this->hasModulePermission(
            $moduleKey,
            'create'
        );
    }

    public function canUpdateModuleRecords(
        string $moduleKey
    ): bool {
        return $this->hasModulePermission(
            $moduleKey,
            'update'
        );
    }

    public function canDeleteModuleRecords(
        string $moduleKey
    ): bool {
        return $this->hasModulePermission(
            $moduleKey,
            'delete'
        );
    }

    /**
     * Glavni korisnik može dodavati podkorisnike
     * samo ako mu je to omogućio superadmin.
     */
    public function canCreateSubusers(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isOrgAdmin()
            && $this->can_manage_subusers;
    }

    /**
     * Provjera prihvaćene pravne dokumentacije.
     */
    public function hasAcceptedCurrentLegalTerms(): bool
    {
        if ($this->legal_consent_withdrawn_at) {
            return false;
        }

        $required = [
            'terms' => [
                'accepted_at' => 'accepted_terms_at',
                'version_field' => 'terms_version',
                'config' => 'legal.terms_version',
            ],

            'privacy' => [
                'accepted_at' => 'accepted_privacy_at',
                'version_field' => 'privacy_version',
                'config' => 'legal.privacy_version',
            ],

            'cookies' => [
                'accepted_at' => 'cookies_accepted_at',
                'version_field' => 'cookies_version',
                'config' => 'legal.cookies_version',
            ],

            'dpa' => [
                'accepted_at' => 'dpa_accepted_at',
                'version_field' => 'dpa_version',
                'config' => 'legal.dpa_version',
            ],

            'security' => [
                'accepted_at' => 'security_accepted_at',
                'version_field' => 'security_version',
                'config' => 'legal.security_version',
            ],

            'retention' => [
                'accepted_at' => 'retention_accepted_at',
                'version_field' => 'retention_version',
                'config' => 'legal.retention_version',
            ],
        ];

        foreach ($required as $item) {
            if (! $this->{$item['accepted_at']}) {
                return false;
            }

            if (
                $this->{$item['version_field']}
                !== config($item['config'])
            ) {
                return false;
            }
        }

        return true;
    }

    public function hasRequestedAccountDeletion(): bool
    {
        return (bool)
            $this->account_deletion_requested_at;
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'parent_user_id'
        );
    }

    public function subusers(): HasMany
    {
        return $this->hasMany(
            User::class,
            'parent_user_id'
        );
    }

    public function operationalLogs(): HasMany
    {
        return $this->hasMany(
            OperationalLog::class
        );
    }

    public function legalAcceptances(): HasMany
    {
        return $this->hasMany(
            LegalAcceptance::class
        );
    }

    public function sendPasswordResetNotification(
        $token
    ): void {
        $this->notify(
            new ResetPasswordNotification(
                $token
            )
        );
    }
}