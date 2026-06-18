<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

public const MAX_SUBUSERS_PER_ORGANIZATION = 5;

public function subusersCountForLimit(): int
{
    return self::query()
        ->where('parent_user_id', $this->ownerId())
        ->where('role', 'org_user')
        ->withoutTrashed()
        ->count();
}

public function canAddMoreSubusers(): bool
{
    return $this->subusersCountForLimit() < self::MAX_SUBUSERS_PER_ORGANIZATION;
}

public function subusersLimitText(): string
{
    return $this->subusersCountForLimit() . ' / ' . self::MAX_SUBUSERS_PER_ORGANIZATION;
}

public function remainingSubusers(): int
{
    return max(
        0,
        self::MAX_SUBUSERS_PER_ORGANIZATION - $this->subusersCountForLimit()
    );
}

    protected $fillable = [
        'name',
        'organization_name',
        'email',
        'password',
        'is_admin',
        'role',
        'quick_actions',
        'parent_user_id',
        'can_manage_subusers',
        'is_active',
        'daily_status_email_enabled',
        'weekly_status_email_enabled',

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
            'can_manage_subusers' => 'boolean',
            'is_active' => 'boolean',
            'daily_status_email_enabled' => 'boolean',
            'weekly_status_email_enabled' => 'boolean',
            'accepted_terms_at' => 'datetime',
            'accepted_privacy_at' => 'datetime',
            'newsletter_opt_in' => 'boolean',
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

    public function hasAcceptedCurrentLegalTerms(): bool
{
    if ($this->legal_consent_withdrawn_at) {
        return false;
    }

    if (! $this->accepted_terms_at || ! $this->accepted_privacy_at) {
        return false;
    }

    if ($this->terms_version !== config('legal.terms_version')) {
        return false;
    }

    if ($this->privacy_version !== config('legal.privacy_version')) {
        return false;
    }

    return true;
}

    public function hasRequestedAccountDeletion(): bool
    {
        return (bool) $this->account_deletion_requested_at;
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function subusers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false)
            || in_array($this->role, ['admin', 'super_admin'], true);
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

    public function ownerId(): int
    {
        return $this->parent_user_id ?: $this->id;
    }

    public function owner(): self
    {
        if ($this->parent_user_id && $this->parentUser) {
            return $this->parentUser;
        }

        return $this;
    }

    public function moduleAccess(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        $owner = $this->owner();

        return is_array($owner->quick_actions) ? $owner->quick_actions : [];
    }

    public function canAccessModule(string $moduleKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($moduleKey, $this->moduleAccess(), true);
    }

    public function canCreateSubusers(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isOrgAdmin() && $this->can_manage_subusers;
    }

    public function operationalLogs(): HasMany
    {
        return $this->hasMany(\App\Models\OperationalLog::class);
    }

    public function legalAcceptances(): HasMany
    {
        return $this->hasMany(\App\Models\LegalAcceptance::class);
    }
}