<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditLearningCategory extends EditRecord
{
    protected static string $resource = LearningCategoryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }
}