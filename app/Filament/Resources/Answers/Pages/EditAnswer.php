<?php

namespace App\Filament\Resources\Answers\Pages;

use App\Filament\Resources\Answers\AnswerResource;
use Filament\Resources\Pages\EditRecord;

class EditAnswer extends EditRecord
{
    protected static string $resource = AnswerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }
}