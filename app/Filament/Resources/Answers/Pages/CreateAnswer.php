<?php

namespace App\Filament\Resources\Answers\Pages;

use App\Filament\Resources\Answers\AnswerResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use Filament\Resources\Pages\CreateRecord;

class CreateAnswer extends CreateRecord
{
    protected static string $resource = AnswerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $question = Question::query()
            ->with('test')
            ->findOrFail($data['question_id']);

        abort_unless(
            QuestionResource::canManageQuestion($question),
            403
        );

        // Answer ownership prati Test preko Questiona.
        $data['user_id'] = $question->test?->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}