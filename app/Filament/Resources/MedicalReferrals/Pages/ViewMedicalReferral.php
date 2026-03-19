<?php

namespace App\Filament\Resources\MedicalReferrals\Pages;

use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use App\Services\Ra1PdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicalReferral extends ViewRecord
{
    protected static string $resource = MedicalReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function () {
                    $record = $this->getRecord();
                    $path = Ra1PdfGenerator::generate($record);

                    return response()->download(
                        $path,
                        Ra1PdfGenerator::buildFileName($record, 'd.m.Y.')
                    )->deleteFileAfterSend(true);
                }),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
            '2xl' => 1,
        ];
    }
}