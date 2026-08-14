<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditLearningCategory extends EditRecord
{
    protected static string $resource =
        LearningCategoryResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership i globalni status postojećeg zapisa
         * ne mijenjaju se uređivanjem.
         */
        $data['user_id'] =
            $this->record->user_id;

        $data['is_global'] =
            $this->record->is_global;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}