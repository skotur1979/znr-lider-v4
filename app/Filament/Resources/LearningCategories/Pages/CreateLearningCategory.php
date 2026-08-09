<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningCategory extends CreateRecord
{
    protected static string $resource =
        LearningCategoryResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            /*
             * Superadmin kreira globalnu kategoriju.
             */
            $data['user_id'] = null;
            $data['is_global'] = true;
        } else {
            /*
             * Glavni korisnik i podkorisnik kreiraju
             * kategoriju svoje organizacije.
             */
            $data['user_id'] = $user->ownerId();
            $data['is_global'] = false;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
