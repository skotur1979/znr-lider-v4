<?php

namespace Database\Seeders;

use App\Models\Kpi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $items = [

            /*
            |--------------------------------------------------------------------------
            | ZNR
            |--------------------------------------------------------------------------
            */

            [
                'name' =>
                    'Broj ozljeda LTA',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    0,

                'warning_offset' =>
                    0,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'lta_count',

                'formula_text' =>
                    'Automatski broj LTA ozljeda iz modula Incidenti.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    1,
            ],

            [
                'name' =>
                    'Dani izgubljeni zbog ozljede LTA',

                'category' =>
                    'ZNR',

                'unit' =>
                    'dani',

                'target_value' =>
                    0,

                'warning_offset' =>
                    0,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'lta_lost_days',

                'formula_text' =>
                    'Automatski zbroj izgubljenih radnih dana za LTA incidente.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    2,
            ],

            [
                'name' =>
                    'Broj Near Miss',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    6,

                'warning_offset' =>
                    2,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'near_miss_count',

                'formula_text' =>
                    'Automatski broj Near Miss zapažanja.',

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    3,
            ],

            [
                'name' =>
                    'Broj Negativnih zapažanja',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    10,

                'warning_offset' =>
                    2,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'negative_observation_count',

                'formula_text' =>
                    'Automatski broj negativnih zapažanja.',

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    4,
            ],

            [
                'name' =>
                    'Interni nadzori',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    5,

                'warning_offset' =>
                    1,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'inspection_count',

                'formula_text' =>
                    'Automatski broj provedenih nadzora.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    5,
            ],

            /*
             * Ovaj KPI ostaje RUČNI.
             *
             * AFR i ASR ovise upravo o ovoj vrijednosti.
             */
            [
                'name' =>
                    'Ukupan broj odrađenih radnih sati',

                'category' =>
                    'ZNR',

                'unit' =>
                    'sati',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'manual',

                'source_key' =>
                    null,

                'formula_text' =>
                    'Ručni unos ukupnog broja odrađenih radnih sati za odabrani mjesec.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    6,
            ],

            [
                'name' =>
                    'AFR',

                'category' =>
                    'ZNR',

                'unit' =>
                    'index',

                'target_value' =>
                    0.1,

                'warning_offset' =>
                    0.2,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'formula',

                'source_key' =>
                    'afr',

                'formula_text' =>
                    '(Broj ozljeda LTA × 1.000.000) ÷ Ukupan broj odrađenih radnih sati',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    7,
            ],

            [
                'name' =>
                    'ASR',

                'category' =>
                    'ZNR',

                'unit' =>
                    'index',

                'target_value' =>
                    2,

                'warning_offset' =>
                    1,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'formula',

                'source_key' =>
                    'asr',

                'formula_text' =>
                    '(Dani izgubljeni zbog ozljede LTA × 1.000.000) ÷ Ukupan broj odrađenih radnih sati',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    8,
            ],

            [
                'name' =>
                    'Broj otvorenih korektivnih radnji',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    5,

                'warning_offset' =>
                    2,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'corrective_actions_open',

                'formula_text' =>
                    'Broj korektivnih radnji koje su otvorene na kraju odabranog mjeseca.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    9,
            ],

            [
                'name' =>
                    'Broj zatvorenih korektivnih radnji',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    3,

                'warning_offset' =>
                    1,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'corrective_actions_closed',

                'formula_text' =>
                    'Broj zatvorenih korektivnih radnji.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    10,
            ],

            [
                'name' =>
                    'Broj korektivnih radnji u tijeku',

                'category' =>
                    'ZNR',

                'unit' =>
                    'broj',

                'target_value' =>
                    2,

                'warning_offset' =>
                    1,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'corrective_actions_in_progress',

                'formula_text' =>
                    'Broj korektivnih radnji sa statusom U tijeku.',

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    11,
            ],

            [
                'name' =>
                    'Broj dana kašnjenja zatvaranja korektivnih radnji',

                'category' =>
                    'ZNR',

                'unit' =>
                    'dani',

                'target_value' =>
                    0,

                'warning_offset' =>
                    3,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'corrective_actions_delay_days',

                'formula_text' =>
                    'Ukupan broj dana kašnjenja otvorenih i nezavršenih korektivnih radnji.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    12,
            ],

            /*
            |--------------------------------------------------------------------------
            | OKOLIŠ
            |--------------------------------------------------------------------------
            */

            [
                'name' =>
                    'Neopasni otpad',

                'category' =>
                    'Okoliš',

                'unit' =>
                    'kg',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'non_hazardous_waste_kg',

                'formula_text' =>
                    'Automatski izračun iz ONTO evidencije.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    13,
            ],

            [
                'name' =>
                    'Opasni otpad',

                'category' =>
                    'Okoliš',

                'unit' =>
                    'kg',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'hazardous_waste_kg',

                'formula_text' =>
                    'Automatski izračun iz ONTO evidencije.',

                'show_on_dashboard' =>
                    true,

                'sort_order' =>
                    14,
            ],

            [
                'name' =>
                    'Miješani komunalni otpad',

                'category' =>
                    'Okoliš',

                'unit' =>
                    'kg',

                'target_value' =>
                    40,

                'warning_offset' =>
                    10,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'automatic',

                'source_key' =>
                    'municipal_waste_kg',

                'formula_text' =>
                    'Automatski izračun iz ONTO evidencije.',

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    15,
            ],

            /*
            |--------------------------------------------------------------------------
            | ENERGIJA
            |--------------------------------------------------------------------------
            */

            [
                'name' =>
                    'Potrošnja el. energije',

                'category' =>
                    'Energija',

                'unit' =>
                    'kWh',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'manual',

                'source_key' =>
                    null,

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    16,
            ],

            [
                'name' =>
                    'Potrošnja vode',

                'category' =>
                    'Energija',

                /*
                 * Voda nije kWh.
                 */
                'unit' =>
                    'm³',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'manual',

                'source_key' =>
                    null,

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    17,
            ],

            [
                'name' =>
                    'Potrošnja lož ulja',

                'category' =>
                    'Energija',

                'unit' =>
                    'l',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'lower_better',

                'calculation_type' =>
                    'manual',

                'source_key' =>
                    null,

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    18,
            ],

            [
                'name' =>
                    'Proizvodnja el. energije solari',

                'category' =>
                    'Energija',

                'unit' =>
                    'kWh',

                'target_value' =>
                    null,

                'warning_offset' =>
                    null,

                'direction' =>
                    'higher_better',

                'calculation_type' =>
                    'manual',

                'source_key' =>
                    null,

                'show_on_dashboard' =>
                    false,

                'sort_order' =>
                    19,
            ],
        ];

        foreach ($items as $item) {
            $slug = Str::slug(
                $item['name']
            );

            Kpi::updateOrCreate(
                [
                    'user_id' =>
                        null,

                    'slug' =>
                        $slug,
                ],
                $item + [
                    'user_id' =>
                        null,

                    'slug' =>
                        $slug,

                    'is_active' =>
                        true,
                ]
            );
        }
    }
}