<?php

namespace App\Filament\Resources\MedicalReferrals\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MedicalReferralInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('employee_id')
                    ->numeric(),
                TextEntry::make('employer_name'),
                TextEntry::make('job_title'),
                TextEntry::make('employment_date')
                    ->date(),
                TextEntry::make('place_of_birth'),
                TextEntry::make('oib'),
                TextEntry::make('education'),
                TextEntry::make('total_years'),
                IconEntry::make('exam_previous')
                    ->boolean(),
                IconEntry::make('exam_periodic')
                    ->boolean(),
                IconEntry::make('exam_extraordinary')
                    ->boolean(),
                TextEntry::make('last_exam_date'),
                TextEntry::make('last_exam_reference'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('full_name'),
                TextEntry::make('referral_number'),
                TextEntry::make('referral_date')
                    ->date(),
                TextEntry::make('employer_oib'),
                TextEntry::make('work_years_in_job'),
                TextEntry::make('name_of_parents'),
                TextEntry::make('law_reference1'),
                TextEntry::make('last_exam_reference1'),
                TextEntry::make('last_exam_reference2'),
                TextEntry::make('last_exam_reference3'),
                IconEntry::make('lifting_enabled')
                    ->boolean(),
                TextEntry::make('lifting_weight'),
                IconEntry::make('carrying_enabled')
                    ->boolean(),
                TextEntry::make('carrying_weight'),
                IconEntry::make('pushing_enabled')
                    ->boolean(),
                TextEntry::make('pushing_weight'),
                TextEntry::make('chemcial_substances'),
                TextEntry::make('biological_hazards'),
                TextEntry::make('employer_address'),
            ]);
    }
}
