<?php

namespace App\Filament\Resources\Answers\Pages;

use App\Filament\Resources\Answers\AnswerResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Filament\Resources\Pages\EditRecord;

class EditAnswer extends EditRecord
{
    protected static string $resource = AnswerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $question = Question::query()
            ->with('test')
            ->findOrFail($data['question_id']);

        abort_unless(
            QuestionResource::canManageQuestion($question),
            403
        );

        // Answer ownership uvijek prati pripadajući Test.
        $data['user_id'] = $question->test?->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}