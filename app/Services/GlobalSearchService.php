<?php

namespace App\Services;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Fires\FireResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Chemical;
use App\Models\Employee;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
        $items = $this->scopeUser(
            Employee::query()->with('certificates')
        )
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('OIB', 'like', "%{$term}%")
                    ->orWhere('workplace', 'like', "%{$term}%")
                    ->orWhere('job_title', 'like', "%{$term}%")
                    ->orWhere('organization_unit', 'like', "%{$term}%")
                    ->orWhere('article', 'like', "%{$term}%")
                    ->orWhere('education', 'like', "%{$term}%")
                    ->orWhere('contract_type', 'like', "%{$term}%")

                    ->orWhereHas('certificates', function (Builder $certificateQuery) use ($term) {
                        $certificateQuery->where('title', 'like', "%{$term}%");
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['prva pomoć', 'prva pomoc', 'first aid'])) {
                            $q->whereNotNull('first_aid_valid_from')
                                ->orWhereNotNull('first_aid_valid_until');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['ovlaštenik', 'ovlastenik', 'ovlaštenik poslodavca', 'ovlastenik poslodavca'])) {
                            $q->whereNotNull('employers_authorization_valid_from')
                                ->orWhereNotNull('employers_authorization_valid_until');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['toksikologija', 'otrovi'])) {
                            $q->whereNotNull('toxicology_valid_from')
                                ->orWhereNotNull('toxicology_valid_until');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['zapaljive', 'zapaljive tvari', 'rukovanje zapaljivim tvarima'])) {
                            $q->whereNotNull('handling_flammable_materials_valid_from')
                                ->orWhereNotNull('handling_flammable_materials_valid_until');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['znr', 'zaštita na radu', 'zastita na radu'])) {
                            $q->whereNotNull('occupational_safety_valid_from');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['zop', 'požar', 'pozar', 'zaštita od požara', 'zastita od pozara'])) {
                            $q->whereNotNull('fire_protection_valid_from')
                                ->orWhereNotNull('fire_protection_statement_at');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['evakuacija', 'voditelj evakuacije'])) {
                            $q->whereNotNull('evacuation_valid_from');
                        }
                    })

                    ->orWhere(function (Builder $q) use ($term) {
                        if ($this->matchesAny($term, ['liječnički', 'lijecnicki', 'pregled', 'liječnički pregled', 'lijecnicki pregled'])) {
                            $q->whereNotNull('medical_examination_valid_until');
                        }
                    });
            })
            ->get()
            ->map(function (Employee $record) use ($term) {
                $matchedCertificate = $record->certificates
                    ->first(function ($certificate) use ($term) {
                        return str_contains(
                            mb_strtolower($certificate->title ?? ''),
                            mb_strtolower($term)
                        );
                    });

                $matchedCategories = $this->matchedEmployeeCategories($record, $term);

                return [
                    'title' => $record->name ?: 'Bez naziva',
                    'subtitle' => collect([
                        $record->workplace ? 'Radno mjesto: ' . $record->workplace : null,
                        $matchedCertificate ? 'Certifikat: ' . $matchedCertificate->title : null,
                        ! empty($matchedCategories) ? 'Kategorija: ' . implode(', ', $matchedCategories) : null,
                        $record->OIB ? 'OIB: ' . $record->OIB : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(EmployeeResource::class, $record),
                    'icon' => 'heroicon-o-users',
                    'score' => $this->rankRecord([
                        $record->name,
                        $record->OIB,
                        $record->workplace,
                        $record->job_title,
                        $record->organization_unit,
                        $record->article,
                        $record->education,
                        $record->contract_type,
                        $matchedCertificate?->title,
                        ...$matchedCategories,
                    ], $term),
                ];
            })
            ->toArray();

        return $this->sortResults($items);
    }

    protected function matchedEmployeeCategories(Employee $record, string $term): array
    {
        $categories = [];

        if ($this->matchesAny($term, ['prva pomoć', 'prva pomoc', 'first aid'])
            && ($record->first_aid_valid_from || $record->first_aid_valid_until)) {
            $categories[] = 'Prva pomoć';
        }

        if ($this->matchesAny($term, ['ovlaštenik', 'ovlastenik', 'ovlaštenik poslodavca', 'ovlastenik poslodavca'])
            && ($record->employers_authorization_valid_from || $record->employers_authorization_valid_until)) {
            $categories[] = 'Ovlaštenik poslodavca za ZNR';
        }

        if ($this->matchesAny($term, ['toksikologija', 'otrovi'])
            && ($record->toxicology_valid_from || $record->toxicology_valid_until)) {
            $categories[] = 'Toksikologija';
        }

        if ($this->matchesAny($term, ['zapaljive', 'zapaljive tvari', 'rukovanje zapaljivim tvarima'])
            && ($record->handling_flammable_materials_valid_from || $record->handling_flammable_materials_valid_until)) {
            $categories[] = 'Rukovanje zapaljivim tvarima';
        }

        if ($this->matchesAny($term, ['znr', 'zaštita na radu', 'zastita na radu'])
            && $record->occupational_safety_valid_from) {
            $categories[] = 'Zaštita na radu';
        }

        if ($this->matchesAny($term, ['zop', 'požar', 'pozar', 'zaštita od požara', 'zastita od pozara'])
            && ($record->fire_protection_valid_from || $record->fire_protection_statement_at)) {
            $categories[] = 'ZOP / Zaštita od požara';
        }

        if ($this->matchesAny($term, ['evakuacija', 'voditelj evakuacije'])
            && $record->evacuation_valid_from) {
            $categories[] = 'Evakuacija';
        }

        if ($this->matchesAny($term, ['liječnički', 'lijecnicki', 'pregled', 'liječnički pregled', 'lijecnicki pregled'])
            && $record->medical_examination_valid_until) {
            $categories[] = 'Liječnički pregled';
        }

        return $categories;
    }

    protected function matchesAny(string $term, array $needles): bool
    {
        $term = mb_strtolower(trim($term));

        foreach ($needles as $needle) {
            $needle = mb_strtolower($needle);

            if ($term === $needle || str_contains($needle, $term) || str_contains($term, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function searchMachines(string $term): array
    {
        $items = $this->scopeUser(Machine::query())
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('manufacturer', 'like', "%{$term}%")
                    ->orWhere('factory_number', 'like', "%{$term}%")
                    ->orWhere('inventory_number', 'like', "%{$term}%")
                    ->orWhere('report_number', 'like', "%{$term}%")
                    ->orWhere('examined_by', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('remark', 'like', "%{$term}%");
            })
            ->get()
            ->map(function (Machine $record) use ($term) {
                return [
                    'title' => $record->name ?: 'Bez naziva',
                    'subtitle' => collect([
                    $record->factory_number
                        ? 'Tv. broj: ' . $record->factory_number
                        : null,

                    $record->inventory_number
                        ? 'Inventarni broj: ' . $record->inventory_number
                        : null,

                    $record->report_number
                        ? 'Broj zapisnika: ' . $record->report_number
                        : null,

                    $record->manufacturer
                        ? 'Proizvođač: ' . $record->manufacturer
                        : null,

                    $record->examined_by
                        ? 'Ispitao: ' . $record->examined_by
                        : null,

                    $record->location
                        ? 'Lokacija: ' . $record->location
                        : null,
                ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(MachineResource::class, $record),
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'score' => $this->rankRecord([
                    $record->name,
                    $record->manufacturer,
                    $record->factory_number,
                    $record->inventory_number,
                    $record->report_number,
                    $record->examined_by,
                    $record->location,
                    $record->remark,
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
                    ->orWhere('service', 'like', "%{$term}%")
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
                        $record->service ? 'Servis: ' . $record->service : null,
                    ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(FireResource::class, $record),
                    'icon' => 'heroicon-o-fire',
                    'score' => $this->rankRecord([
                        $record->place,
                        $record->type,
                        $record->serial_label_number,
                        $record->factory_number_year_of_production,
                        $record->service,
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
                    ->orWhere('report_number', 'like', "%{$term}%")
                    ->orWhere('remark', 'like', "%{$term}%")
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
                    $record->category?->name
                        ? 'Kategorija: ' . $record->category->name
                        : null,

                    $record->examiner
                        ? 'Ispitao: ' . $record->examiner
                        : null,

                    $record->report_number
                        ? 'Broj zapisnika: ' . $record->report_number
                        : null,
                ])->filter()->implode(' · '),
                    'url' => $this->resourceRecordUrl(MiscellaneousResource::class, $record),
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'score' => $this->rankRecord([
                    $record->name,
                    $record->category?->name,
                    $record->examiner,
                    $record->report_number,
                    $record->remark,
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