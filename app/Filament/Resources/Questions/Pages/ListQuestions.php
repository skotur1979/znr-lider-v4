<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'screen-2xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo pitanje')
            ->icon('heroicon-o-plus'),
        ];
    }
}