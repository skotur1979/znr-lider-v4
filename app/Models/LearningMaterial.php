<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $fillable = [
        'user_id',
        'learning_category_id',
        'title',
        'description',
        'type',
        'url',          // stari single link (ostavljamo zbog kompatibilnosti)
        'file_path',    // stari single file (ostavljamo)
        'links',        // novi multiple linkovi (json)
        'files',        // novi multiple fileovi (json)
        'is_global',
        'is_active',
        'sort_order',
        'content_types',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'links' => 'array',
            'files' => 'array',
            'content_types' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'document' => 'Dokument',
            'video' => 'Video',
            'website' => 'Stručna stranica',
            'instruction' => 'Uputa',
            'other' => 'Ostalo',
            default => 'Materijal',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METODE (KLJUČNO 🔥)
    |--------------------------------------------------------------------------
    */

    public function hasLinks(): bool
    {
        return !empty($this->url)
            || collect($this->links ?? [])
                ->contains(fn ($item) => !blank($item['url'] ?? null));
    }

    public function hasFiles(): bool
    {
        return !empty($this->file_path)
            || collect($this->files ?? [])
                ->filter()
                ->isNotEmpty();
    }

    public function getAllLinks(): array
    {
        $links = [];

        if (!empty($this->url)) {
            $links[] = [
                'label' => 'Glavni link',
                'url' => $this->url,
            ];
        }

        foreach ($this->links ?? [] as $link) {
            if (!blank($link['url'] ?? null)) {
                $links[] = $link;
            }
        }

        return $links;
    }

    public function getAllFiles(): array
    {
        $files = [];

        if (!empty($this->file_path)) {
            $files[] = $this->file_path;
        }

        foreach ($this->files ?? [] as $file) {
            if (!blank($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}