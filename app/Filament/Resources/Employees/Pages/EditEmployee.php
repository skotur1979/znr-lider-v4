<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        EmployeeResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard pregleda.
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

        /*
         * Filament zatim pretvara ID iz URL-a
         * u stvarni Employee model.
         */
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Prikaži')
                ->color('gray')
                ->url(function (): string {
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

                    return EmployeeResource::getUrl(
                        'view',
                        $parameters
                    );
                }),

            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->before(
                    EmployeeResource::beforeModulePermission(
                        'delete'
                    )
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->before(
                    EmployeeResource::beforeModulePermission(
                        'delete'
                    )
                ),

            ForceDeleteAction::make()
                ->label('Trajno obriši')
                ->requiresConfirmation()
                ->before(
                    EmployeeResource::beforeModulePermission(
                        'delete'
                    )
                ),
        ];
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership zaposlenika nikada se
         * ne mijenja kroz edit formu,
         * uključujući superadmina.
         */
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        /*
         * Ako smo zaposlenika otvorili iz
         * dashboard pregleda, vraćamo se
         * na isti pregled.
         */
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
            return static::getResource()::getUrl(
                'index',
                [
                    'pregled' =>
                        $this->pregled,
                ]
            );
        }

        /*
         * Kod normalnog ulaska u modul
         * zadržavamo postojeće ponašanje.
         */
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}