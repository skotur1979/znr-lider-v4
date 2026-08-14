<?php

namespace App\Services;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Fires\FireResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use App\Models\MedicalReferral;
use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\LearningMaterial;
use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\Kpis\KpiResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Filament\Resources\WorkTasks\WorkTaskResource;
use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Models\NightWorkReferral;
use App\Models\DocumentationItem;
use App\Models\Incident;
use App\Models\Kpi;
use App\Models\Observation;
use App\Models\WorkTask;
use App\Models\Expense;
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
                'medical_referrals' => [],
                'night_work_referrals' => [],
                'documentation_items' => [],
                'incidents' => [],
                'kpis' => [],
                'observations' => [],
                'work_tasks' => [],
                'expenses' => [],
                'learning_materials' => [],
            ];
        }

        return [
            'employees' => $this->searchEmployees($term),
            'machines' => $this->searchMachines($term),
            'fires' => $this->searchFires($term),
            'miscellaneous' => $this->searchMiscellaneous($term),
            'chemicals' => $this->searchChemicals($term),
            'medical_referrals' => $this->searchMedicalReferrals($term),
            'night_work_referrals' => $this->searchNightWorkReferrals($term),
            'documentation_items' => $this->searchDocumentationItems($term),
            'incidents' => $this->searchIncidents($term),
            'kpis' => $this->searchKpis($term),
            'observations' => $this->searchObservations($term),
            'work_tasks' => $this->searchWorkTasks($term),
            'expenses' => $this->searchExpenses($term),
            'learning_materials' => $this->searchLearningMaterials($term),
        ];
    }

    protected function scopeUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $ownerId);
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
    protected function searchWords(string $term): array
    {
        return collect(preg_split('/\s+/u', mb_strtolower(trim($term))))
            ->filter(fn ($word) => mb_strlen($word) >= 2)
            ->unique()
            ->values()
            ->all();
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
    protected function searchMedicalReferrals(string $term): array
{
    $items = $this->scopeUser(
        MedicalReferral::query()->with('employee')
    )
        ->whereNull('deleted_at')
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('referral_number', 'like', "%{$term}%")
                ->orWhere('form_version', 'like', "%{$term}%")
                ->orWhere('full_name', 'like', "%{$term}%")
                ->orWhere('oib', 'like', "%{$term}%")
                ->orWhere('job_title', 'like', "%{$term}%")
                ->orWhere('employer_name', 'like', "%{$term}%")
                ->orWhere('employer_oib', 'like', "%{$term}%")
                ->orWhere('health_jobs_description', 'like', "%{$term}%")
                ->orWhere('special_conditions', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('tools', 'like', "%{$term}%")
                ->orWhere('job_tasks', 'like', "%{$term}%")
                ->orWhere('chemcial_substances', 'like', "%{$term}%")
                ->orWhere('biological_hazards', 'like', "%{$term}%")
                ->orWhereHas('employee', function (Builder $employeeQuery) use ($term) {
                    $employeeQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('OIB', 'like', "%{$term}%")
                        ->orWhere('job_title', 'like', "%{$term}%")
                        ->orWhere('workplace', 'like', "%{$term}%");
                });
        })
        ->get()
        ->map(function (MedicalReferral $record) use ($term) {
            $employeeName = $record->employee?->name ?? $record->full_name;

            return [
                'title' => $employeeName ?: 'RA-1 Uputnica',
                'subtitle' => collect([
                    $record->referral_number ? 'Broj uputnice: ' . $record->referral_number : null,
                    $record->referral_date ? 'Datum: ' . $record->referral_date->format('d.m.Y.') : null,
                    $record->form_version_label ? 'Verzija: ' . $record->form_version_label : null,
                    $record->health_jobs_description ? 'Poslovi: ' . $record->health_jobs_description : null,
                    $record->oib ? 'OIB: ' . $record->oib : null,
                ])->filter()->implode(' · '),

                'url' => $this->resourceRecordUrl(MedicalReferralResource::class, $record),
                'icon' => 'heroicon-o-document-text',

                'score' => $this->rankRecord([
                    $employeeName,
                    $record->employee?->name,
                    $record->employee?->OIB,
                    $record->referral_number,
                    $record->form_version,
                    $record->form_version_label,
                    $record->full_name,
                    $record->oib,
                    $record->job_title,
                    $record->health_jobs_description,
                    $record->short_description,
                    $record->tools,
                    $record->job_tasks,
                    $record->chemcial_substances,
                    $record->biological_hazards,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}
    protected function searchNightWorkReferrals(string $term): array
{
    $items = $this->scopeUser(NightWorkReferral::query()->with('employee'))
        ->whereNull('deleted_at')
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('referral_number', 'like', "%{$term}%")
                ->orWhere('form_version', 'like', "%{$term}%")
                ->orWhere('full_name', 'like', "%{$term}%")
                ->orWhere('oib', 'like', "%{$term}%")
                ->orWhere('job_title', 'like', "%{$term}%")
                ->orWhere('employer_name', 'like', "%{$term}%")
                ->orWhere('employer_oib', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('tools', 'like', "%{$term}%")
                ->orWhere('job_tasks', 'like', "%{$term}%")
                ->orWhere('chemcial_substances', 'like', "%{$term}%")
                ->orWhere('biological_hazards', 'like', "%{$term}%")
                ->orWhereHas('employee', function (Builder $employeeQuery) use ($term) {
                    $employeeQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('OIB', 'like', "%{$term}%")
                        ->orWhere('job_title', 'like', "%{$term}%")
                        ->orWhere('workplace', 'like', "%{$term}%");
                });
        })
        ->get()
        ->map(function (NightWorkReferral $record) use ($term) {
            $employeeName = $record->employee?->name ?? $record->full_name;

            return [
                'title' => $employeeName ?: 'NR-1 Uputnica',
                'subtitle' => collect([
                    $record->referral_number ? 'Broj uputnice: ' . $record->referral_number : null,
                    $record->referral_date ? 'Datum: ' . $record->referral_date->format('d.m.Y.') : null,
                    $record->form_version_label ? 'Verzija: ' . $record->form_version_label : null,
                    $record->job_title ? 'Noćni rad: ' . $record->job_title : null,
                    $record->oib ? 'OIB: ' . $record->oib : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(NightWorkReferralResource::class, $record),
                'icon' => 'heroicon-o-document-text',
                'score' => $this->rankRecord([
                    $employeeName,
                    $record->employee?->name,
                    $record->employee?->OIB,
                    $record->referral_number,
                    $record->form_version,
                    $record->form_version_label,
                    $record->full_name,
                    $record->oib,
                    $record->job_title,
                    $record->short_description,
                    $record->tools,
                    $record->job_tasks,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchDocumentationItems(string $term): array
{
    $items = $this->scopeUser(DocumentationItem::query())
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('naziv', 'like', "%{$term}%")
                ->orWhere('tvrtka', 'like', "%{$term}%")
                ->orWhere('status_napomena', 'like', "%{$term}%");
        })
        ->get()
        ->map(function (DocumentationItem $record) use ($term) {
            return [
                'title' => $record->naziv ?: 'Dokumentacija',
                'subtitle' => collect([
                    $record->tvrtka ? 'Tvrtka: ' . $record->tvrtka : null,
                    $record->datum_izrade ? 'Datum izrade: ' . $record->datum_izrade->format('d.m.Y.') : null,
                    $record->status_napomena ? 'Status / napomena: ' . $record->status_napomena : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(DocumentationItemResource::class, $record),
                'icon' => 'heroicon-o-rectangle-stack',
                'score' => $this->rankRecord([
                    $record->naziv,
                    $record->tvrtka,
                    $record->status_napomena,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchIncidents(string $term): array
{
    $items = $this->scopeUser(Incident::query())
        ->whereNull('deleted_at')
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('location', 'like', "%{$term}%")
                ->orWhere('type_of_incident', 'like', "%{$term}%")
                ->orWhere('permanent_or_temporary', 'like', "%{$term}%")
                ->orWhere('causes_of_injury', 'like', "%{$term}%")
                ->orWhere('accident_injury_type', 'like', "%{$term}%")
                ->orWhere('injured_body_part', 'like', "%{$term}%")
                ->orWhere('other', 'like', "%{$term}%");
        })
        ->get()
        ->map(function (Incident $record) use ($term) {
            return [
                'title' => $record->location ?: 'Incident',
                'subtitle' => collect([
                    $record->type_of_incident ? 'Vrsta: ' . $record->type_of_incident : null,
                    $record->date_occurred ? 'Datum: ' . $record->date_occurred->format('d.m.Y.') : null,
                    $record->injured_body_part ? 'Dio tijela: ' . $record->injured_body_part : null,
                    $record->working_days_lost ? 'Izgubljeni dani: ' . $record->working_days_lost : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(IncidentResource::class, $record),
                'icon' => 'heroicon-o-eye',
                'score' => $this->rankRecord([
                    $record->location,
                    $record->type_of_incident,
                    $record->causes_of_injury,
                    $record->accident_injury_type,
                    $record->injured_body_part,
                    $record->other,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchKpis(string $term): array
{
    $query = Kpi::query()->whereNull('deleted_at');

    if (! Auth::user()?->isSuperAdmin()) {
        $ownerId = Auth::user()?->ownerId();

        $query->where(function (Builder $q) use ($ownerId) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $ownerId);
        });
    }

    $items = $query
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%")
                ->orWhere('unit', 'like', "%{$term}%")
                ->orWhere('calculation_type', 'like', "%{$term}%")
                ->orWhere('source_key', 'like', "%{$term}%")
                ->orWhere('formula_text', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        })
        ->get()
        ->map(function (Kpi $record) use ($term) {
            return [
                'title' => match ($record->source_key) {
                    'afr' => 'AFR – Stopa učestalosti ozljeda',
                    'asr' => 'ASR – Stopa težine ozljeda',
                    default => $record->name ?: 'KPI',
                },
                'subtitle' => collect([
                    $record->category ? 'Kategorija: ' . $record->category : null,
                    $record->unit ? 'Jedinica: ' . $record->unit : null,
                    $record->calculation_type ? 'Tip: ' . $record->calculation_type : null,
                    $record->target_value !== null ? 'Cilj: ' . $record->formatNumberOnly($record->target_value) : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(KpiResource::class, $record),
                'icon' => 'heroicon-o-chart-bar-square',
                'score' => $this->rankRecord([
                    $record->name,
                    $record->slug,
                    $record->category,
                    $record->unit,
                    $record->calculation_type,
                    $record->source_key,
                    $record->formula_text,
                    $record->description,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchObservations(string $term): array
{
    $items = $this->scopeUser(Observation::query())
        ->whereNull('deleted_at')
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('observation_type', 'like', "%{$term}%")
                ->orWhere('priority', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('item', 'like', "%{$term}%")
                ->orWhere('potential_incident_type', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('responsible', 'like', "%{$term}%")
                ->orWhere('status', 'like', "%{$term}%")
                ->orWhere('comments', 'like', "%{$term}%");
        })
        ->get()
        ->map(function (Observation $record) use ($term) {
            return [
                'title' => $record->item ?: ($record->location ?: 'Zapažanje'),
                'subtitle' => collect([
                    $record->incident_date ? 'Datum: ' . $record->incident_date->format('d.m.Y.') : null,
                    $record->observation_type ? 'Vrsta: ' . $record->observation_type : null,
                    $record->location ? 'Lokacija: ' . $record->location : null,
                    $record->responsible ? 'Odgovoran: ' . $record->responsible : null,
                    $record->status ? 'Status: ' . $record->status : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(ObservationResource::class, $record),
                'icon' => 'heroicon-o-exclamation-circle',
                'score' => $this->rankRecord([
                    $record->item,
                    $record->observation_type,
                    $record->priority,
                    $record->location,
                    $record->potential_incident_type,
                    $record->action,
                    $record->responsible,
                    $record->status,
                    $record->comments,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchWorkTasks(string $term): array
{
    $items = $this->scopeUser(WorkTask::query())
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        })
        ->get()
        ->map(function (WorkTask $record) use ($term) {
            return [
                'title' => $record->title ?: 'Radni zadatak',
                'subtitle' => collect([
                    $record->due_date ? 'Datum: ' . $record->due_date->format('d.m.Y.') : null,
                    $record->is_done ? 'Status: Riješeno' : 'Status: Otvoreno',
                    $record->description ? 'Opis: ' . $record->description : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(WorkTaskResource::class, $record),
                'icon' => 'heroicon-o-clipboard-document-check',
                'score' => $this->rankRecord([
                    $record->title,
                    $record->description,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}

    protected function searchExpenses(string $term): array
{
    $items = $this->scopeUser(
        Expense::query()->with(['budget', 'category'])
    )
        ->where(function (Builder $query) use ($term) {
            $query
                ->where('naziv_troska', 'like', "%{$term}%")
                ->orWhere('dobavljac', 'like', "%{$term}%")
                ->orWhere('mjesec', 'like', "%{$term}%")
                ->orWhereHas('category', fn (Builder $q) => $q->where('name', 'like', "%{$term}%"))
                ->orWhereHas('budget', fn (Builder $q) => $q->where('godina', 'like', "%{$term}%"));
        })
        ->get()
        ->map(function (Expense $record) use ($term) {
            return [
                'title' => $record->naziv_troska ?: 'Trošak',
                'subtitle' => collect([
                    $record->budget?->godina ? 'Godina: ' . $record->budget->godina : null,
                    $record->mjesec ? 'Mjesec: ' . $record->mjesec : null,
                    $record->category?->name ? 'Kategorija: ' . $record->category->name : null,
                    $record->dobavljac ? 'Dobavljač: ' . $record->dobavljac : null,
                    $record->iznos !== null ? 'Iznos: ' . number_format((float) $record->iznos, 2, ',', '.') . ' €' : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(ExpenseResource::class, $record),
                'icon' => 'heroicon-o-calculator',
                'score' => $this->rankRecord([
                    $record->naziv_troska,
                    $record->mjesec,
                    $record->dobavljac,
                    $record->category?->name,
                    $record->budget?->godina,
                ], $term),
            ];
        })
        ->toArray();

    return $this->sortResults($items);
}
    protected function searchLearningMaterials(string $term): array
{
    $query = LearningMaterial::query()->with(['category', 'user']);

    if (! Auth::user()?->isSuperAdmin()) {
        $ownerId = Auth::user()?->ownerId();

        $query->where(function (Builder $q) use ($ownerId) {
            $q->where('is_global', true)
                ->orWhere('user_id', $ownerId);
        });
    }

    $words = $this->searchWords($term);

    $items = $query
        ->where('is_active', true)
        ->get()
        ->filter(function (LearningMaterial $record) use ($words) {
            $filesText = collect($record->getAllFiles())
                ->map(fn ($file) => basename((string) $file))
                ->implode(' ');

            $linksText = collect($record->getAllLinks())
                ->map(fn ($link) => trim(($link['label'] ?? '') . ' ' . ($link['url'] ?? '')))
                ->implode(' ');

            $contentTypesText = collect($record->content_types ?? [])->implode(' ');

            $haystack = mb_strtolower(collect([
                $record->title,
                $record->description,
                $record->type,
                $record->type_label,
                $record->source_type,
                $record->url,
                $record->file_path,
                $filesText,
                $linksText,
                $contentTypesText,
                $record->category?->name,
            ])->filter()->implode(' '));

            return collect($words)->every(fn ($word) => str_contains($haystack, $word));
        })
        ->map(function (LearningMaterial $record) use ($term) {
            $filesText = collect($record->getAllFiles())
                ->map(fn ($file) => basename((string) $file))
                ->implode(' ');

            return [
                'title' => $record->title ?: 'Edukacijski materijal',
                'subtitle' => collect([
                    $record->category?->name ? 'Kategorija: ' . $record->category->name : null,
                    $record->type_label ? 'Vrsta: ' . $record->type_label : null,
                    $record->is_global ? 'Globalni materijal' : 'Organizacijski materijal',
                    $filesText ? 'Dokumenti: ' . $filesText : null,
                    $record->description ? 'Opis: ' . $record->description : null,
                ])->filter()->implode(' · '),
                'url' => $this->resourceRecordUrl(LearningMaterialResource::class, $record),
                'icon' => 'heroicon-o-academic-cap',
                'score' => $this->rankRecord([
                    $record->title,
                    $record->description,
                    $record->type_label,
                    $filesText,
                    $record->category?->name,
                ], $term),
            ];
        })
        ->values()
        ->toArray();

    return $this->sortResults($items);
}
}