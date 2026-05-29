<?php

namespace App\Filament\Resources\Answers\Pages;

use App\Filament\Resources\Answers\AnswerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnswers extends ListRecords
{
    protected static string $resource = AnswerResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'screen-2xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novi odgovor')
            ->icon('heroicon-o-plus'),
        ];
    }
}