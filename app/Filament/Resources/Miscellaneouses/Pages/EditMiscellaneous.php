<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Resources\Pages\EditRecord;

class EditMiscellaneous extends EditRecord
{
    protected static string $resource = MiscellaneousResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // korisnik ne smije “ukrasti” zapis promjenom user_id
        if (! auth()->user()?->isAdmin()) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}
