<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Category;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditMiscellaneous extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        MiscellaneousResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership zapisa nikada se ne mijenja
         * kroz edit formu.
         */
        unset($data['user_id']);

        /*
         * Kategorija mora pripadati istom owneru
         * kao postojeći zapis.
         *
         * Ovo vrijedi i kada zapis administrira
         * superadmin.
         */
        $ownerId = (int) $this->record->user_id;

        $categoryId = $data['category_id'] ?? null;

        $validCategory = Category::query()
            ->whereKey($categoryId)
            ->where('user_id', $ownerId)
            ->exists();

        if (! $validCategory) {
            throw ValidationException::withMessages([
                'category_id' =>
                    'Odabrana kategorija ne pripada organizaciji ovog zapisa.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
