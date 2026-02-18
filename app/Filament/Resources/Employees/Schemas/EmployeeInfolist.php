<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        // Za sada prazno (možemo kasnije složiti lijep “View” prikaz)
        return $schema->schema([]);
    }
}
