<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMiscellaneous extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        MiscellaneousResource::class;

    public function mount(): void
    {
        if (
            $this->redirectIfMissingModulePermission(
                'create'
            )
        ) {
            return;
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        $ownerId = (int) $user->ownerId();

        if ($ownerId <= 0) {
            abort(403);
        }

        /*
         * Sigurnosna provjera:
         * odabrana kategorija mora pripadati
         * istoj organizaciji.
         */
        $categoryId = $data['category_id'] ?? null;

        $validCategory = Category::query()
            ->whereKey($categoryId)
            ->where('user_id', $ownerId)
            ->exists();

        if (! $validCategory) {
            throw ValidationException::withMessages([
                'category_id' =>
                    'Odabrana kategorija ne pripada vašoj organizaciji.',
            ]);
        }

        return MiscellaneousResource::fillOwnershipData(
            $data
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'index'
        );
    }
}
