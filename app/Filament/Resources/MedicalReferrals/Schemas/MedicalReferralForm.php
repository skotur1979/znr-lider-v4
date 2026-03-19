<?php

namespace App\Filament\Resources\MedicalReferrals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MedicalReferralForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('employee_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('employer_name')
                    ->default(null),
                TextInput::make('job_title')
                    ->default(null),
                DatePicker::make('employment_date'),
                TextInput::make('place_of_birth')
                    ->default(null),
                TextInput::make('oib')
                    ->default(null),
                TextInput::make('education')
                    ->default(null),
                Textarea::make('health_jobs_description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('law_reference')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('special_conditions')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('retirement_conditions')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('total_years')
                    ->default(null),
                Toggle::make('exam_previous')
                    ->required(),
                Toggle::make('exam_periodic')
                    ->required(),
                Toggle::make('exam_extraordinary')
                    ->required(),
                TextInput::make('last_exam_date')
                    ->default(null),
                TextInput::make('last_exam_reference')
                    ->default(null),
                Textarea::make('short_description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('workplace_location')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('organization')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('body_position')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('loads')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('harmful_factors')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('full_name')
                    ->default(null),
                Textarea::make('job_tasks')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('exam_type')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('tools')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('hazards')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('referral_number')
                    ->default(null),
                DatePicker::make('referral_date'),
                TextInput::make('employer_oib')
                    ->default(null),
                TextInput::make('work_years_in_job')
                    ->default(null),
                TextInput::make('name_of_parents')
                    ->default(null),
                TextInput::make('law_reference1')
                    ->default(null),
                TextInput::make('last_exam_reference1')
                    ->default(null),
                TextInput::make('last_exam_reference2')
                    ->default(null),
                TextInput::make('last_exam_reference3')
                    ->default(null),
                Toggle::make('lifting_enabled'),
                TextInput::make('lifting_weight')
                    ->default(null),
                Toggle::make('carrying_enabled'),
                TextInput::make('carrying_weight')
                    ->default(null),
                Toggle::make('pushing_enabled'),
                TextInput::make('pushing_weight')
                    ->default(null),
                Textarea::make('job_characteristics')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('chemcial_substances')
                    ->default(null),
                TextInput::make('biological_hazards')
                    ->default(null),
                TextInput::make('employer_address')
                    ->default(null),
            ]);
    }
}
