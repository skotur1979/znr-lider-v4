<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Edukacijski centar';

    protected $fillable = [
        'user_id',
        'learning_category_id',
        'title',
        'description',
        'type',
        'source_type',
        'url',
        'file_path',
        'links',
        'files',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'manual' => 'Upute za korištenje',
            'excel_template' => 'Excel predložak',
            'pdf_form' => 'PDF obrazac',
            'faq' => 'FAQ / pomoć',
            'example' => 'Primjer',
            'video' => 'Video link',
            'website' => 'Korisni link',
            'document' => 'Dokument',
            'instruction' => 'Uputa',
            'other' => 'Ostalo',
            default => 'Materijal',
        };
    }

    public function hasLinks(): bool
    {
        return ! empty($this->url)
            || collect($this->links ?? [])->contains(fn ($item) => ! blank($item['url'] ?? null));
    }

    public function hasFiles(): bool
    {
        return ! empty($this->file_path)
            || collect($this->files ?? [])->filter()->isNotEmpty();
    }

    public function getAllLinks(): array
    {
        $links = [];

        if (! empty($this->url)) {
            $links[] = [
                'label' => 'Glavni link',
                'url' => $this->url,
            ];
        }

        foreach ($this->links ?? [] as $link) {
            if (! blank($link['url'] ?? null)) {
                $links[] = [
                    'label' => $link['label'] ?? 'Link',
                    'url' => $link['url'],
                ];
            }
        }

        return $links;
    }

    public function getAllFiles(): array
    {
        $files = [];

        if (! empty($this->file_path)) {
            $files[] = $this->file_path;
        }

        foreach ($this->files ?? [] as $file) {
            if (! blank($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}