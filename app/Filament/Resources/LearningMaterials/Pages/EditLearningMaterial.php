<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\LearningCategory;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningMaterial extends EditRecord
{
    protected static string $resource =
        LearningMaterialResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership i globalni status
         * postojećeg materijala ostaju isti.
         */
        $data['user_id'] =
            $this->record->user_id;

        $data['is_global'] =
            $this->record->is_global;

        $data['type'] =
            $data['content_types'][0]
            ?? ($data['type'] ?? 'document');

        $data['source_type'] = 'mixed';

        $categoryId =
            (int) ($data['learning_category_id'] ?? 0);

        /*
         * Globalni materijal smije pripadati
         * samo globalnoj kategoriji.
         */
        if ((bool) $this->record->is_global) {
            $validCategory =
                LearningCategory::query()
                    ->whereKey($categoryId)
                    ->where('is_global', true)
                    ->where('is_active', true)
                    ->exists();

            abort_unless(
                $validCategory,
                403
            );

            return $data;
        }

        /*
         * Organizacijski materijal:
         * globalna ili kategorija iste organizacije.
         */
        $ownerId =
            (int) $this->record->user_id;

        $validCategory =
            LearningCategory::query()
                ->whereKey($categoryId)
                ->where('is_active', true)
                ->where(
                    function ($query) use ($ownerId): void {
                        $query
                            ->where(
                                function ($global): void {
                                    $global
                                        ->where('is_global', true)
                                        ->whereNull('user_id');
                                }
                            )
                            ->orWhere(
                                function ($organization) use ($ownerId): void {
                                    $organization
                                        ->where('is_global', false)
                                        ->where(
                                            'user_id',
                                            $ownerId
                                        );
                                }
                            );
                    }
                )
                ->exists();

        abort_unless(
            $validCategory,
            403
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši')
                ->requiresConfirmation()
                ->modalHeading(
                    'Obriši edukacijski materijal'
                )
                ->modalDescription(
                    'Jeste li sigurni da želite obrisati ovaj edukacijski materijal?'
                )
                ->modalSubmitActionLabel('Obriši')
                ->modalCancelActionLabel('Odustani'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}