<?php

namespace App\Filament\Resources\NightWorkReferrals\Pages;

use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use App\Services\Nr1PdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNightWorkReferral extends ViewRecord
{
    protected static string $resource = NightWorkReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(
                    fn (): bool =>
                        auth()->user()?->isSuperAdmin() !== true
                ),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(function () {
                    $record = $this->getRecord();
                    $path = Nr1PdfGenerator::generate($record);

                    return response()
                        ->download(
                            $path,
                            Nr1PdfGenerator::buildFileName(
                                $record,
                                'd.m.Y.'
                            )
                        )
                        ->deleteFileAfterSend(true);
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