<?php

namespace Database\Seeders;

use App\Models\Kpi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationKpiSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Broj Near Miss',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 6,
                'warning_offset' => 2,
                'direction' => 'higher_better',
                'calculation_type' => 'automatic',
                'source_key' => 'near_miss_count',
                'show_on_dashboard' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Broj Negativnih zapažanja',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 10,
                'warning_offset' => 2,
                'direction' => 'higher_better',
                'calculation_type' => 'automatic',
                'source_key' => 'negative_observation_count',
                'show_on_dashboard' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Interni nadzori',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 5,
                'warning_offset' => 1,
                'direction' => 'higher_better',
                'calculation_type' => 'automatic',
                'source_key' => 'inspection_count',
                'show_on_dashboard' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Ukupan broj odrađenih radnih sati',
                'category' => 'ZNR',
                'unit' => 'sati',
                'target_value' => null,
                'warning_offset' => null,
                'direction' => 'higher_better',
                'calculation_type' => 'manual',
                'source_key' => null,
                'show_on_dashboard' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Ukupan broj dana otvorenih korektivnih radnji',
                'category' => 'ZNR',
                'unit' => 'dani',
                'target_value' => 0,
                'warning_offset' => 3,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'corrective_actions_delay_days',
                'show_on_dashboard' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Broj otvorenih korektivnih radnji',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 5,
                'warning_offset' => 2,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'corrective_actions_open',
                'show_on_dashboard' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Broj zatvorenih korektivnih radnji',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 3,
                'warning_offset' => 1,
                'direction' => 'higher_better',
                'calculation_type' => 'automatic',
                'source_key' => 'corrective_actions_closed',
                'show_on_dashboard' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Broj korektivnih radnji u tijeku',
                'category' => 'ZNR',
                'unit' => 'broj',
                'target_value' => 2,
                'warning_offset' => 1,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'corrective_actions_in_progress',
                'show_on_dashboard' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'Neopasni otpad',
                'category' => 'Okoliš',
                'unit' => 'kg',
                'target_value' => null,
                'warning_offset' => null,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'non_hazardous_waste_kg',
                'show_on_dashboard' => true,
                'sort_order' => 13,
            ],
            [
                'name' => 'Opasni otpad',
                'category' => 'Okoliš',
                'unit' => 'kg',
                'target_value' => null,
                'warning_offset' => null,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'hazardous_waste_kg',
                'show_on_dashboard' => true,
                'sort_order' => 14,
            ],
            [
                'name' => 'Miješani komunalni otpad',
                'category' => 'Okoliš',
                'unit' => 'kg',
                'target_value' => 40,
                'warning_offset' => 10,
                'direction' => 'lower_better',
                'calculation_type' => 'automatic',
                'source_key' => 'municipal_waste_kg',
                'show_on_dashboard' => false,
                'sort_order' => 15,
            ],
        ];

        $owners = User::query()
            ->whereNull('parent_user_id')
            ->where(function ($query) {
                $query->where('is_admin', false)
                    ->orWhereNull('is_admin');
            })
            ->get();

        foreach ($owners as $owner) {
            foreach ($items as $item) {
                $slug = Str::slug($item['name']) . '-org-' . $owner->id;

                Kpi::updateOrCreate(
                    [
                        'user_id' => $owner->id,
                        'slug' => $slug,
                    ],
                    $item + [
                        'user_id' => $owner->id,
                        'slug' => $slug,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}