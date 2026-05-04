<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

class ListLearningMaterials extends Page
{
    use WithPagination;

    protected static string $resource = LearningMaterialResource::class;

    protected string $view = 'filament.resources.learning-materials.pages.list-learning-materials';

    public ?string $search = null;
    public ?string $category = null;
    public ?string $type = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'type' => ['except' => ''],
    ];

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi materijal')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Edukacijski centar';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = null;
        $this->category = null;
        $this->type = null;

        $this->resetPage();
    }

    public function getMaterialsProperty(): LengthAwarePaginator
    {
        return LearningMaterial::query()
            ->with(['category', 'user'])
            ->where('is_active', true)
            ->when(! $this->isSuperAdmin(), function (Builder $query) {
                $ownerId = $this->ownerId();

                $query->where(function (Builder $q) use ($ownerId) {
                    $q->where('is_global', true)
                        ->orWhere('user_id', $ownerId);
                });
            })
            ->when($this->search, function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhereJsonContains('links', [['url' => $search]]);
                });
            })
            ->when($this->category, fn (Builder $query) => $query->where('learning_category_id', $this->category))
            ->when($this->type, fn (Builder $query) => $query->where('type', $this->type))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    public function getCategoriesProperty()
    {
        return LearningCategory::query()
            ->where('is_active', true)
            ->when(! $this->isSuperAdmin(), function (Builder $query) {
                $ownerId = $this->ownerId();

                $query->where(function (Builder $q) use ($ownerId) {
                    $q->where('is_global', true)
                        ->orWhere('user_id', $ownerId);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function materialLinks(LearningMaterial $record): array
    {
        $links = [];

        if (! blank($record->url)) {
            $links[] = [
                'label' => 'Glavni link',
                'url' => $record->url,
            ];
        }

        foreach (($record->links ?? []) as $link) {
            if (! blank($link['url'] ?? null)) {
                $links[] = [
                    'label' => $link['label'] ?? 'Link',
                    'url' => $link['url'],
                ];
            }
        }

        return $links;
    }

    public function materialFiles(LearningMaterial $record): array
    {
        $files = [];

        if (! blank($record->file_path)) {
            $files[] = [
                'label' => basename($record->file_path),
                'url' => Storage::disk('public')->url($record->file_path),
            ];
        }

        foreach (($record->files ?? []) as $file) {
            if (! blank($file)) {
                $files[] = [
                    'label' => basename($file),
                    'url' => Storage::disk('public')->url($file),
                ];
            }
        }

        return $files;
    }

    public function typeLabel(?string $type): string
    {
        return match ($type) {
            'document' => 'Dokument',
            'video' => 'Video',
            'website' => 'Stručna stranica',
            'instruction' => 'Uputa',
            'other' => 'Ostalo',
            default => 'Materijal',
        };
    }

    public function iconFor(?string $type): string
    {
        return match ($type) {
            'video' => '🎥',
            'website' => '🌐',
            'instruction' => '📘',
            'document' => '📄',
            default => '📚',
        };
    }

    public function canEditMaterial(LearningMaterial $record): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return ! $record->is_global && (int) $record->user_id === (int) $this->ownerId();
    }

    protected function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return (bool) (
            $user?->isSuperAdmin()
            || $user?->is_admin
            || $user?->role === 'admin'
        );
    }

    protected function ownerId(): ?int
    {
        $user = Auth::user();

        return $user?->parent_user_id ?: $user?->id;
    }
}