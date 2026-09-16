<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        EmployeeResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard pregleda
         * prije parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                [
                    'medical_expired',
                    'medical_expiring',
                    'certificates_expired',
                    'certificates_expiring',
                ],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'view'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editEmployee')
                ->label('Uredi')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color('warning')
                ->action(function () {
                    if (
                        ! EmployeeResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    $parameters = [
                        'record' =>
                            $this->getRecord(),
                    ];

                    if (
                        in_array(
                            $this->pregled,
                            [
                                'medical_expired',
                                'medical_expiring',
                                'certificates_expired',
                                'certificates_expiring',
                            ],
                            true
                        )
                    ) {
                        $parameters['pregled'] =
                            $this->pregled;
                    }

                    return redirect(
                        EmployeeResource::getUrl(
                            'edit',
                            $parameters
                        )
                    );
                }),
        ];
    }
}