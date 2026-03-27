<?php

namespace Database\Seeders;

use App\Models\InspectionTemplateQuestion;
use Illuminate\Database\Seeder;

class FiveSTemplateQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['section' => 'sortiranje', 'sort_order' => 1, 'question' => 'Da li su prisutni samo potrebni predmeti i materijali?'],
            ['section' => 'sortiranje', 'sort_order' => 2, 'question' => 'Da li su nepotrebni predmeti uklonjeni iz zone?'],

            ['section' => 'slaganje', 'sort_order' => 1, 'question' => 'Da li svaki predmet ima svoje označeno mjesto?'],
            ['section' => 'slaganje', 'sort_order' => 2, 'question' => 'Da li su alati i pribor uredno složeni?'],

            ['section' => 'sjaj', 'sort_order' => 1, 'question' => 'Da li je radna površina čista?'],
            ['section' => 'sjaj', 'sort_order' => 2, 'question' => 'Da li se čišćenje provodi redovito?'],

            ['section' => 'standardiziranje', 'sort_order' => 1, 'question' => 'Da li postoje standardi i vizualne oznake?'],
            ['section' => 'standardiziranje', 'sort_order' => 2, 'question' => 'Da li se standardi dosljedno poštuju?'],

            ['section' => 'samoodrzavanje', 'sort_order' => 1, 'question' => 'Da li zaposlenici samostalno održavaju red i standarde?'],
            ['section' => 'samoodrzavanje', 'sort_order' => 2, 'question' => 'Da li se uočena odstupanja pravovremeno ispravljaju?'],
        ];

        foreach ($questions as $q) {
            InspectionTemplateQuestion::updateOrCreate(
                [
                    'section' => $q['section'],
                    'sort_order' => $q['sort_order'],
                    'question' => $q['question'],
                ],
                [
                    'code' => null,
                    'max_score' => 5,
                    'is_active' => true,
                ]
            );
        }
    }
}