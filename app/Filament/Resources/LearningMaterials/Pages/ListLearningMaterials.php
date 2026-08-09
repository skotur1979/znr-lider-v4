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

    protected static string $resource =
        LearningMaterialResource::class;

    protected string $view =
        'filament.resources.learning-materials.pages.list-learning-materials';

    public string $search = '';

    public string $category = '';

    public string $type = '';

    protected $queryString = [
        'search' => [
            'except' => '',
        ],

        'category' => [
            'except' => '',
        ],

        'type' => [
            'except' => '',
        ],
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
        $this->search = '';
        $this->category = '';
        $this->type = '';

        $this->resetPage();
    }

    public function getMaterialsProperty(): LengthAwarePaginator
    {
        $ownerId = $this->ownerId();

        $query = LearningMaterial::query()
            ->with([
                'category',
                'user',
            ])
            ->where(
                'is_active',
                true
            );

        /**
         * Organizacija vidi:
         *
         * - samo ispravne globalne materijale
         *   is_global = true + user_id = NULL
         *
         * - vlastite organizacijske materijale
         *   is_global = false + user_id = ownerId()
         */
        if (! $this->isSuperAdmin()) {
            if (! $ownerId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(
                    function (
                        Builder $query
                    ) use ($ownerId): void {
                        $query
                            ->where(
                                function (
                                    Builder $global
                                ): void {
                                    $global
                                        ->where(
                                            'is_global',
                                            true
                                        )
                                        ->whereNull(
                                            'user_id'
                                        );
                                }
                            )
                            ->orWhere(
                                function (
                                    Builder $organization
                                ) use ($ownerId): void {
                                    $organization
                                        ->where(
                                            'is_global',
                                            false
                                        )
                                        ->where(
                                            'user_id',
                                            $ownerId
                                        );
                                }
                            );
                    }
                );
            }
        }

        return $query
            ->when(
                $this->search,
                function (
                    Builder $query
                ): void {
                    $search =
                        trim(
                            $this->search
                        );

                    $query->where(
                        function (
                            Builder $q
                        ) use ($search): void {
                            $q
                                ->where(
                                    'title',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'url',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $this->category,
                fn (Builder $query) =>
                    $query->where(
                        'learning_category_id',
                        $this->category
                    )
            )
            ->when(
                $this->type,
                function (
                    Builder $query
                ): void {
                    $query->where(
                        function (
                            Builder $q
                        ): void {
                            $q
                                ->whereJsonContains(
                                    'content_types',
                                    $this->type
                                )
                                ->orWhere(
                                    'type',
                                    $this->type
                                );
                        }
                    );
                }
            )
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    public function getCategoriesProperty()
    {
        $ownerId = $this->ownerId();

        $query = LearningCategory::query()
            ->where(
                'is_active',
                true
            );

        /**
         * Superadmin vidi sve kategorije.
         *
         * Organizacija vidi:
         * - globalne kategorije
         * - svoje organizacijske kategorije.
         */
        if (! $this->isSuperAdmin()) {
            if (! $ownerId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(
                    function (
                        Builder $query
                    ) use ($ownerId): void {
                        $query
                            ->where(
                                function (
                                    Builder $global
                                ): void {
                                    $global
                                        ->where(
                                            'is_global',
                                            true
                                        )
                                        ->whereNull(
                                            'user_id'
                                        );
                                }
                            )
                            ->orWhere(
                                function (
                                    Builder $organization
                                ) use ($ownerId): void {
                                    $organization
                                        ->where(
                                            'is_global',
                                            false
                                        )
                                        ->where(
                                            'user_id',
                                            $ownerId
                                        );
                                }
                            );
                    }
                );
            }
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function materialLinks(
        LearningMaterial $record
    ): array {
        $links = [];

        if (! blank($record->url)) {
            $links[] = [
                'label' =>
                    'Glavni link',

                'url' =>
                    $record->url,
            ];
        }

        foreach (
            ($record->links ?? [])
            as $link
        ) {
            if (
                ! blank(
                    $link['url'] ?? null
                )
            ) {
                $links[] = [
                    'label' =>
                        $link['label']
                        ?? 'Link',

                    'url' =>
                        $link['url'],
                ];
            }
        }

        return $links;
    }

    public function materialFiles(
        LearningMaterial $record
    ): array {
        $files = [];

        if (! blank($record->file_path)) {
            $files[] = [
                'label' =>
                    basename(
                        $record->file_path
                    ),

                'url' =>
                    Storage::disk('public')
                        ->url(
                            $record->file_path
                        ),
            ];
        }

        foreach (
            ($record->files ?? [])
            as $file
        ) {
            if (! blank($file)) {
                $files[] = [
                    'label' =>
                        basename($file),

                    'url' =>
                        Storage::disk(
                            'public'
                        )->url($file),
                ];
            }
        }

        return $files;
    }

    public function typeLabel(
        ?string $type
    ): string {
        return LearningMaterialResource::contentTypeLabel(
            $type
        );
    }

    public function iconFor(
        ?string $type
    ): string {
        return match ($type) {
            'manual' =>
                '📘',

            'excel_template' =>
                '📊',

            'pdf_form' =>
                '📄',

            'faq' =>
                '❓',

            'example' =>
                '✅',

            'video' =>
                '🎥',

            'website' =>
                '🌐',

            'document' =>
                '📁',

            'instruction' =>
                '📘',

            default =>
                '📚',
        };
    }

    public function canEditMaterial(
        LearningMaterial $record
    ): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ((bool) $record->is_global) {
            return false;
        }

        return (int) $record->user_id
            === (int) $this->ownerId();
    }

    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin()
            === true;
    }

    /**
     * Koristimo isti User::ownerId()
     * kao u ostatku aplikacije.
     */
    protected function ownerId(): ?int
    {
        return Auth::user()?->ownerId();
    }
}