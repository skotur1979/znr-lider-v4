<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
        ];
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
}



