<?php

namespace App\Filament\Resources\TestAttempts\Pages;

use App\Filament\Resources\TestAttempts\TestAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestAttempt extends CreateRecord
{
    protected static string $resource = TestAttemptResource::class;
}
