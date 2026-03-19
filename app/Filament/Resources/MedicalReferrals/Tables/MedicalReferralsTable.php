<?php

namespace App\Filament\Resources\MedicalReferrals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MedicalReferralsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employee_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employer_name')
                    ->searchable(),
                TextColumn::make('job_title')
                    ->searchable(),
                TextColumn::make('employment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('place_of_birth')
                    ->searchable(),
                TextColumn::make('oib')
                    ->searchable(),
                TextColumn::make('education')
                    ->searchable(),
                TextColumn::make('total_years')
                    ->searchable(),
                IconColumn::make('exam_previous')
                    ->boolean(),
                IconColumn::make('exam_periodic')
                    ->boolean(),
                IconColumn::make('exam_extraordinary')
                    ->boolean(),
                TextColumn::make('last_exam_date')
                    ->searchable(),
                TextColumn::make('last_exam_reference')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('referral_number')
                    ->searchable(),
                TextColumn::make('referral_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('employer_oib')
                    ->searchable(),
                TextColumn::make('work_years_in_job')
                    ->searchable(),
                TextColumn::make('name_of_parents')
                    ->searchable(),
                TextColumn::make('law_reference1')
                    ->searchable(),
                TextColumn::make('last_exam_reference1')
                    ->searchable(),
                TextColumn::make('last_exam_reference2')
                    ->searchable(),
                TextColumn::make('last_exam_reference3')
                    ->searchable(),
                IconColumn::make('lifting_enabled')
                    ->boolean(),
                TextColumn::make('lifting_weight')
                    ->searchable(),
                IconColumn::make('carrying_enabled')
                    ->boolean(),
                TextColumn::make('carrying_weight')
                    ->searchable(),
                IconColumn::make('pushing_enabled')
                    ->boolean(),
                TextColumn::make('pushing_weight')
                    ->searchable(),
                TextColumn::make('chemcial_substances')
                    ->searchable(),
                TextColumn::make('biological_hazards')
                    ->searchable(),
                TextColumn::make('employer_address')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
