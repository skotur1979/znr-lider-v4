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

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst liste.
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                ['isteklo', 'uskoro'],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

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
         */
        $ownerId =
            (int) $this->record->user_id;

        $categoryId =
            $data['category_id']
            ?? null;

        $validCategory =
            Category::query()
                ->whereKey(
                    $categoryId
                )
                ->where(
                    'user_id',
                    $ownerId
                )
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
        /*
         * Ako smo uređivanje otvorili iz liste
         * "Isteklo" ili "Uskoro", nakon spremanja
         * vraćamo se direktno na tu listu.
         */
        if (
            in_array(
                $this->pregled,
                ['isteklo', 'uskoro'],
                true
            )
        ) {
            return static::getResource()::getUrl(
                'index',
                [
                    'pregled' =>
                        $this->pregled,
                ]
            );
        }

        /*
         * Kod običnog uređivanja zadržavamo
         * standardno ponašanje.
         */
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
