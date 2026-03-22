<?php

namespace App\Services;

use App\Models\Chemical;
use App\Models\Employee;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Fires\FireResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GlobalSearchService
{
    public function search(?string $term): array
    {
        $term = trim((string) $term);

        if (mb_strlen($term) < 2) {
            return [
                'employees' => [],
                'machines' => [],
                'fires' => [],
                'miscellaneous' => [],
                'chemicals' => [],
            ];
        }

        return [
            'employees' => $this->searchEmployees($term),
            'machines' => $this->searchMachines($term),
            'fires' => $this->searchFires($term),
            'miscellaneous' => $this->searchMiscellaneous($term),
            'chemicals' => $this->searchChemicals($term),
        ];
    }

    protected function scopeUser(Builder $query): Builder
    {
        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    protected function searchEmployees(string $term): array
    {
        return $this->scopeUser(Employee::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('OIB', 'like', "%{$term}%")
                    ->orWhere('workplace', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Employee $record) => [
                'title' => $record->name ?: 'Bez naziva',
                'subtitle' => collect([
                    $record->OIB ? 'OIB: ' . $record->OIB : null,
                    $record->workplace ? 'Radno mjesto: ' . $record->workplace : null,
                ])->filter()->implode(' · '),
                'url' => EmployeeResource::getUrl('view', ['record' => $record]),
                'icon' => 'heroicon-o-users',
            ])
            ->toArray();
    }

    protected function searchMachines(string $term): array
    {
        return $this->scopeUser(Machine::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('factory_number', 'like', "%{$term}%")
                    ->orWhere('manufacturer', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Machine $record) => [
                'title' => $record->name ?: 'Bez naziva',
                'subtitle' => collect([
                    $record->factory_number ? 'Tv. broj: ' . $record->factory_number : null,
                    $record->manufacturer ? 'Proizvođač: ' . $record->manufacturer : null,
                    $record->location ? 'Lokacija: ' . $record->location : null,
                ])->filter()->implode(' · '),
                'url' => MachineResource::getUrl('view', ['record' => $record]),
                'icon' => 'heroicon-o-cog-6-tooth',
            ])
            ->toArray();
    }

    protected function searchFires(string $term): array
    {
        return $this->scopeUser(Fire::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('place', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhere('serial_label_number', 'like', "%{$term}%")
                    ->orWhereRaw("`factory_number/year_of_production` LIKE ?", ["%{$term}%"]);
            })
            ->orderBy('place')
            ->limit(8)
            ->get()
            ->map(fn (Fire $record) => [
                'title' => $record->place ?: 'Bez naziva',
                'subtitle' => collect([
                    $record->type ? 'Tip: ' . $record->type : null,
                    $record->serial_label_number ? 'Serijski broj: ' . $record->serial_label_number : null,
                    $record->factory_number_year_of_production
                        ? 'Tv. broj / god.: ' . $record->factory_number_year_of_production
                        : null,
                ])->filter()->implode(' · '),
                'url' => FireResource::getUrl('view', ['record' => $record]),
                'icon' => 'heroicon-o-fire',
            ])
            ->toArray();
    }

    protected function searchMiscellaneous(string $term): array
    {
        return $this->scopeUser(Miscellaneous::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('examiner', 'like', "%{$term}%")
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($term) {
                        $categoryQuery->where('name', 'like', "%{$term}%");
                    });
            })
            ->with('category')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Miscellaneous $record) => [
                'title' => $record->name ?: 'Bez naziva',
                'subtitle' => collect([
                    $record->category?->name ? 'Kategorija: ' . $record->category->name : null,
                    $record->examiner ? 'Ispitao: ' . $record->examiner : null,
                ])->filter()->implode(' · '),
                'url' => MiscellaneousResource::getUrl('view', ['record' => $record]),
                'icon' => 'heroicon-o-wrench-screwdriver',
            ])
            ->toArray();
    }

    protected function searchChemicals(string $term): array
    {
        return $this->scopeUser(Chemical::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('product_name', 'like', "%{$term}%")
                    ->orWhere('ufi_number', 'like', "%{$term}%")
                    ->orWhere('cas_number', 'like', "%{$term}%")
                    ->orWhere('usage_location', 'like', "%{$term}%");
            })
            ->orderBy('product_name')
            ->limit(8)
            ->get()
            ->map(fn (Chemical $record) => [
                'title' => $record->product_name ?: 'Bez naziva',
                'subtitle' => collect([
                    $record->ufi_number ? 'UFI: ' . $record->ufi_number : null,
                    $record->cas_number ? 'CAS: ' . $record->cas_number : null,
                    $record->usage_location ? 'Mjesto upotrebe: ' . $record->usage_location : null,
                ])->filter()->implode(' · '),
                'url' => ChemicalResource::getUrl('edit', ['record' => $record]),
                'icon' => 'heroicon-o-beaker',
            ])
            ->toArray();
    }
}