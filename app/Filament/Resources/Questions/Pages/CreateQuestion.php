<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Tests\TestResource;
use App\Models\Test;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $test = Test::query()
            ->findOrFail($data['test_id']);

        // Provjera da korisnik stvarno smije mijenjati taj test.
        abort_unless(
            TestResource::canManageTest($test),
            403
        );

        // Pitanje uvijek nasljeđuje ownership testa.
        $data['user_id'] = $test->user_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}