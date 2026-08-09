<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\LearningCategory;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningMaterial extends CreateRecord
{
    protected static string $resource =
        LearningMaterialResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        /*
         * Održavamo postojeću kompatibilnost
         * type/content_types polja.
         */
        $data['type'] =
            $data['content_types'][0]
            ?? 'document';

        $data['source_type'] = 'mixed';

        if ($user->isSuperAdmin()) {
            /*
             * Superadmin kreira globalni
             * edukacijski sadržaj.
             */
            $data['user_id'] = null;
            $data['is_global'] = true;

            /*
             * Globalni materijal smije koristiti
             * samo globalnu kategoriju.
             */
            $categoryId =
                (int) ($data['learning_category_id'] ?? 0);

            $validCategory =
                LearningCategory::query()
                    ->whereKey($categoryId)
                    ->where('is_global', true)
                    ->where('is_active', true)
                    ->exists();

            abort_unless(
                $validCategory,
                403,
                'Globalni materijal mora pripadati globalnoj kategoriji.'
            );
        } else {
            $ownerId = $user->ownerId();

            if (! $ownerId) {
                abort(403);
            }

            $data['user_id'] = $ownerId;
            $data['is_global'] = false;

            /*
             * Organizacija smije koristiti:
             * - globalnu kategoriju
             * - svoju kategoriju
             */
            $categoryId =
                (int) ($data['learning_category_id'] ?? 0);

            $validCategory =
                LearningCategory::query()
                    ->whereKey($categoryId)
                    ->where('is_active', true)
                    ->where(
                        function ($query) use ($ownerId): void {
                            $query
                                ->where(
                                    'is_global',
                                    true
                                )
                                ->orWhere(
                                    'user_id',
                                    $ownerId
                                );
                        }
                    )
                    ->exists();

            abort_unless(
                $validCategory,
                403
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}