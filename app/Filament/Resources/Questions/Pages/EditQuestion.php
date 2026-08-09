<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Tests\TestResource;
use App\Models\Test;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $test = Test::query()
            ->findOrFail($data['test_id']);

        abort_unless(
            TestResource::canManageTest($test),
            403
        );

        // Ownership pitanja uvijek prati Test.
        $data['user_id'] = $test->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}