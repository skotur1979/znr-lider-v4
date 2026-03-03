<?php

namespace App\Filament\Resources\TestAttempts\Pages;

use App\Filament\Resources\TestAttempts\TestAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListTestAttempts extends ListRecords
{
    protected static string $resource = TestAttemptResource::class;
}