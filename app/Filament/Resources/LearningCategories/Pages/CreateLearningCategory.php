<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningCategory extends CreateRecord
{
    protected static string $resource = LearningCategoryResource::class;
}
