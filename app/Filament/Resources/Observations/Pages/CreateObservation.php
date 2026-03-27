<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Models\InspectionFinding;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateObservation extends CreateRecord
{
    protected static string $resource = ObservationResource::class;

    protected ?string $returnInspectionEditUrl = null;

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'lg' => 1,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public function mount(): void
    {
        parent::mount();

        $findingId = request()->query('inspection_finding_id');

        if ($findingId) {
            $finding = InspectionFinding::with('inspection')->find($findingId);

            if ($finding?->inspection) {
                $this->returnInspectionEditUrl = InspectionResource::getUrl('edit', [
                    'record' => $finding->inspection,
                ]);
            }
        }

        $this->form->fill([
            'user_id' => request()->query('user_id'),
            'incident_date' => request()->query('incident_date'),
            'observation_type' => request()->query('observation_type'),
            'location' => request()->query('location'),
            'item' => request()->query('item'),
            'potential_incident_type' => request()->query('potential_incident_type'),
            'picture_path' => request()->query('picture_path'),
            'action' => request()->query('action'),
            'responsible' => request()->query('responsible'),
            'target_date' => request()->query('target_date'),
            'status' => request()->query('status'),
            'comments' => request()->query('comments'),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $findingId = request()->query('inspection_finding_id');

        if (! $findingId) {
            return;
        }

        $finding = InspectionFinding::find($findingId);

        if (! $finding) {
            return;
        }

        $finding->update([
            'observation_id' => $this->record?->id,
            'workflow_status' => 'converted_to_observation',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        if (filled($this->returnInspectionEditUrl)) {
            return $this->returnInspectionEditUrl;
        }

        return $this->getResource()::getUrl('index');
    }
}