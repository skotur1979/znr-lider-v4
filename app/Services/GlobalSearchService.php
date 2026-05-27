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

        $ownerId = Auth::user()?->ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('user_id', $ownerId);
    }

    return $query;
}

    protected function resourceRecordUrl(string $resourceClass, $record): string
    {
        $pages = $resourceClass::getPages();

        if (array_key_exists('view', $pages)) {
            return $resourceClass::getUrl('view', ['record' => $record]);
        }

        return $resourceClass::getUrl('edit', ['record' => $record]);
    }

    protected function rankRecord(array $values, string $term): int
    {
        $termLower = mb_strtolower(trim($term));

        $bestScore = 999;

        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $valueLower = mb_strtolower($value);

            if ($valueLower === $termLower) {
                $bestScore = min($bestScore, 1);
                continue;
            }

            if (str_starts_with($valueLower, $termLower)) {
                $bestScore = min($bestScore, 2);
                continue;
            }

            if (str_contains($valueLower, $termLower)) {
                $bestScore = min($bestScore, 3);
                continue;
            }
        }

        return $bestScore;
    }

    protected function sortResults(array $items): array
    {
        usort($items, function ($a, $b) {
            if (($a['score'] ?? 999) === ($b['score'] ?? 999)) {
                return strcmp(mb_strtolower($a['title'] ?? ''), mb_strtolower($b['title'] ?? ''));
            }

            return ($a['score'] ?? 999) <=> ($b['score'] ?? 999);
        });

        return array_slice($items, 0, 8);
    }

    protected function searchEmployees(string $term): array
    {
        $items = $this->scopeUser(Employee::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('OIB', 'like', "%{$term}%")
                    ->orWhere('workplace', 'like', "%{$term}%");
            })
            ->get()
            ->map(function (Employee $record) use ($term) {
                return [
                    'title' => $record->name ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->OIB ? 'OIB: ' . $record->OIB : null,
                        $record->workplace ? 'Radno mjesto: ' . $record->workplace : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(EmployeeResource::class, $record),
                    'icon' => 'heroicon-o-users',
                    'score' => $this->rankRecord([
                        $record->name,
                        $record->OIB,
                        $record->workplace,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }

    protected function searchMachines(string $term): array
    {
        $items = $this->scopeUser(Machine::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('factory_number', 'like', "%{$term}%")
                    ->orWhere('manufacturer', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%");
            })
            ->get()
            ->map(function (Machine $record) use ($term) {
                return [
                    'title' => $record->name ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->factory_number ? 'Tv. broj: ' . $record->factory_number : null,
                        $record->manufacturer ? 'Proizvođač: ' . $record->manufacturer : null,
                        $record->location ? 'Lokacija: ' . $record->location : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(MachineResource::class, $record),
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'score' => $this->rankRecord([
                        $record->name,
                        $record->factory_number,
                        $record->manufacturer,
                        $record->location,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }

    protected function searchFires(string $term): array
    {
        $items = $this->scopeUser(Fire::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('place', 'like', "%{$term}%")
                    ->orWhere('type', 'like', "%{$term}%")
                    ->orWhere('serial_label_number', 'like', "%{$term}%")
                    ->orWhereRaw("`factory_number/year_of_production` LIKE ?", ["%{$term}%"]);
            })
            ->get()
            ->map(function (Fire $record) use ($term) {
                return [
                    'title' => $record->place ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->type ? 'Tip: ' . $record->type : null,
                        $record->serial_label_number ? 'Serijski broj: ' . $record->serial_label_number : null,
                        $record->factory_number_year_of_production ? 'Tv. broj / god.: ' . $record->factory_number_year_of_production : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(FireResource::class, $record),
                    'icon' => 'heroicon-o-fire',
                    'score' => $this->rankRecord([
                        $record->place,
                        $record->type,
                        $record->serial_label_number,
                        $record->factory_number_year_of_production,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }

    protected function searchMiscellaneous(string $term): array
    {
        $items = $this->scopeUser(Miscellaneous::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('examiner', 'like', "%{$term}%")
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($term) {
                        $categoryQuery->where('name', 'like', "%{$term}%");
                    });
            })
            ->with('category')
            ->get()
            ->map(function (Miscellaneous $record) use ($term) {
                return [
                    'title' => $record->name ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->category?->name ? 'Kategorija: ' . $record->category->name : null,
                        $record->examiner ? 'Ispitao: ' . $record->examiner : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(MiscellaneousResource::class, $record),
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'score' => $this->rankRecord([
                        $record->name,
                        $record->category?->name,
                        $record->examiner,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }

    protected function searchChemicals(string $term): array
    {
        $items = $this->scopeUser(Chemical::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('product_name', 'like', "%{$term}%")
                    ->orWhere('ufi_number', 'like', "%{$term}%")
                    ->orWhere('cas_number', 'like', "%{$term}%")
                    ->orWhere('usage_location', 'like', "%{$term}%");
            })
            ->get()
            ->map(function (Chemical $record) use ($term) {
                return [
                    'title' => $record->product_name ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->ufi_number ? 'UFI: ' . $record->ufi_number : null,
                        $record->cas_number ? 'CAS: ' . $record->cas_number : null,
                        $record->usage_location ? 'Mjesto upotrebe: ' . $record->usage_location : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(ChemicalResource::class, $record),
                    'icon' => 'heroicon-o-beaker',
                    'score' => $this->rankRecord([
                        $record->product_name,
                        $record->ufi_number,
                        $record->cas_number,
                        $record->usage_location,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }
}