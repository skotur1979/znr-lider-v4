<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Employees\Schemas\EmployeeInfolist;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmployeeResource extends BaseResource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Zaposlenici';
    protected static ?string $modelLabel = 'Zaposlenik';
    protected static ?string $pluralModelLabel = 'Zaposlenici';

    protected static string|\UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?int $navigationSort = 1;

    protected static bool $usesSoftDeletes = true;
    protected static bool $hasOwnership = true;

    protected static function getModuleKey(): ?string
    {
        return 'employees';
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}

