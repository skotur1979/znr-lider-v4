<?php

namespace App\Services;

use App\Models\Chemical;
use App\Models\DocumentationItem;
use App\Models\Employee;
use App\Models\Fire;
use App\Models\Incident;
use App\Models\LearningMaterial;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\PPEEquipment;
use App\Models\RiskAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StorageQuotaService
{
    public function quotaMbForOwner(int $ownerId): int
    {
        $owner = User::find($ownerId);

        return (int) ($owner?->storage_quota_mb ?? 20480);
    }

    public function usedMbForOwner(int $ownerId): float
    {
        $bytes = 0;

        $ownerUserIds = User::query()
            ->where('id', $ownerId)
            ->orWhere('parent_user_id', $ownerId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $tracked = [
            Machine::class => ['pdf'],
            Employee::class => ['pdf'],
            Fire::class => ['pdf'],
            Miscellaneous::class => ['pdf'],
            RiskAttachment::class => ['file_path'],
            DocumentationItem::class => ['prilozi'],
            Chemical::class => ['attachments'],
            Observation::class => ['picture_path'],
            Incident::class => ['image_path', 'investigation_report'],
            LearningMaterial::class => ['files'],
            PPEEquipment::class => ['attachments'],
        ];

        foreach ($tracked as $modelClass => $fields) {
            if (! class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass();
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = $modelClass::query();

            if (in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($modelClass),
                true
            )) {
                $query->withTrashed();
            }

            if ($modelClass === RiskAttachment::class) {
                $query->whereHas('riskAssessment', function ($q) use ($ownerUserIds) {
                    $q->whereIn('user_id', $ownerUserIds);
                });
            } elseif (Schema::hasColumn($table, 'user_id')) {
                $query->whereIn('user_id', $ownerUserIds);
            } else {
                continue;
            }

            $records = $query->get($fields);

            foreach ($records as $record) {
                foreach ($fields as $field) {
                    $files = $record->{$field};

                    if (blank($files)) {
                        continue;
                    }

                    if (is_string($files)) {
                        $decoded = json_decode($files, true);
                        $files = json_last_error() === JSON_ERROR_NONE ? $decoded : [$files];
                    }

                    if (! is_array($files)) {
                        $files = [$files];
                    }

                    foreach ($files as $file) {
                        if (! is_string($file) || blank($file)) {
                            continue;
                        }

                        $path = ltrim($file, '/');

                        if (Storage::disk('public')->exists($path)) {
                            $bytes += Storage::disk('public')->size($path);
                        }
                    }
                }
            }
        }

        return round($bytes / 1024 / 1024, 2);
    }

    public function incomingMbFromState(mixed $state): float
    {
        $bytes = 0;

        if ($state instanceof TemporaryUploadedFile) {
            return round($state->getSize() / 1024 / 1024, 2);
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                if ($item instanceof TemporaryUploadedFile) {
                    $bytes += $item->getSize();
                }
            }
        }

        return round($bytes / 1024 / 1024, 2);
    }

    public function canUpload(mixed $state, int $ownerId): bool
    {
        $quotaMb = $this->quotaMbForOwner($ownerId);

        if ($quotaMb <= 0) {
            return true;
        }

        return ($this->usedMbForOwner($ownerId) + $this->incomingMbFromState($state)) <= $quotaMb;
    }

    public function usagePercent(int $ownerId): float
    {
        $quotaMb = $this->quotaMbForOwner($ownerId);

        if ($quotaMb <= 0) {
            return 0;
        }

        return min(100, round(($this->usedMbForOwner($ownerId) / $quotaMb) * 100, 1));
    }

    public function usageText(int $ownerId): string
    {
        $usedMb = $this->usedMbForOwner($ownerId);
        $quotaMb = $this->quotaMbForOwner($ownerId);

        if ($usedMb < 1024) {
            return round($usedMb, 2) . ' MB / ' . round($quotaMb / 1024, 2) . ' GB';
        }

        return round($usedMb / 1024, 2) . ' GB / ' . round($quotaMb / 1024, 2) . ' GB';
    }

    public function remainingText(int $ownerId): string
    {
        $remainingMb = max(0, $this->quotaMbForOwner($ownerId) - $this->usedMbForOwner($ownerId));

        return round($remainingMb / 1024, 2) . ' GB';
    }
}