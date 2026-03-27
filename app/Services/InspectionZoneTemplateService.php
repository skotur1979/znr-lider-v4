<?php

namespace App\Services;

use App\Models\InspectionZone;

class InspectionZoneTemplateService
{
    public function syncQuestionsAndAnswers(InspectionZone $zone): void
    {
        $template = config('inspection_zone_questions', []);

        if (empty($template)) {
            return;
        }

        foreach ($template as $section => $questions) {
            foreach ($questions as $questionText) {
                $question = $zone->questions()->firstOrCreate([
                    'section' => $section,
                    'question' => trim($questionText),
                ]);

                $zone->answers()->firstOrCreate(
                    [
                        'inspection_question_id' => $question->id,
                    ],
                    [
                        'score' => 0,
                    ]
                );
            }
        }
    }
}