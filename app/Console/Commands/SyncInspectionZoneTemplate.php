<?php

namespace App\Console\Commands;

use App\Models\InspectionZone;
use App\Services\InspectionZoneTemplateService;
use Illuminate\Console\Command;

class SyncInspectionZoneTemplate extends Command
{
    protected $signature = 'inspection-zones:sync-template';

    protected $description = 'Ubaci ista 5S pitanja i odgovore u sve postojeće zone';

    public function handle(InspectionZoneTemplateService $service): int
    {
        $zones = InspectionZone::query()->get();

        foreach ($zones as $zone) {
            $service->syncQuestionsAndAnswers($zone);
            $this->info("Zona obrađena: {$zone->name}");
        }

        $this->info('Sve zone su sinkronizirane.');
        return self::SUCCESS;
    }
}