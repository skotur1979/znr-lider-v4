<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalAcceptance extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'organization_name',

        'terms_version',
        'privacy_version',
        'cookies_version',
        'dpa_version',
        'security_version',
        'retention_version',
        'accepted_documents',

        'newsletter_opt_in',
        'accepted_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'newsletter_opt_in' => 'boolean',
            'accepted_at' => 'datetime',
            'accepted_documents' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}