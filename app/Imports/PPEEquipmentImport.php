<?php

namespace App\Imports;

use App\Models\PPEEquipment;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class PPEEquipmentImport implements ToCollection
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public int $unchanged = 0;

    public function collection(Collection $rows): void
    {
        $user = auth()->user();

        $isSuperAdmin =
            (bool) ($user?->is_admin ?? false)
            || in_array($user?->role, ['super_admin', 'admin'], true)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());

        $ownerId = $isSuperAdmin
            ? null
            : ($user && method_exists($user, 'ownerId') ? $user->ownerId() : $user?->id);

        $header = $rows->first();

        if (! $header) {
            $this->skipped++;

            ActivityLogger::import(
                module: 'Registar OZO',
                created: $this->created,
                updated: $this->updated,
                unchanged: $this->unchanged,
                skipped: $this->skipped,
            );

            return;
        }

        $map = [];

        foreach ($header as $index => $column) {
            $map[$this->normalize($column)] = $index;
        }

        foreach ($rows->skip(1) as $row) {
            $name = trim((string) $this->value($row, $map, [
                'naziv_ozo',
                'naziv',
                'name',
            ]));

            if ($name === '') {
                $this->skipped++;
                continue;
            }

            $standard = trim((string) $this->value($row, $map, [
                'hrn_en_norma',
                'hrn_en',
                'norma',
                'standard',
            ]));

            $duration = $this->value($row, $map, [
                'rok_uporabe',
                'rok_uporabe_mjeseci',
                'rok_mjeseci',
                'duration_months',
            ]);

            $record = PPEEquipment::query()
                ->where('user_id', $ownerId)
                ->where('name', $name)
                ->first();

            $data = [
                'standard' => $standard !== '' ? $standard : null,
                'duration_months' => is_numeric($duration) ? (int) $duration : null,
                'is_active' => true,
            ];

            if (! $record) {
                PPEEquipment::create([
                    'user_id' => $ownerId,
                    'name' => $name,
                    ...$data,
                ]);

                $this->created++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if ((string) ($record->{$field} ?? '') !== (string) ($value ?? '')) {
                    $changed[$field] = $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;
                continue;
            }

            $record->update($changed);
            $this->updated++;
        }

        ActivityLogger::import(
            module: 'Registar OZO',
            created: $this->created,
            updated: $this->updated,
            unchanged: $this->unchanged,
            skipped: $this->skipped,
        );
    }

    protected function value($row, array $map, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $map)) {
                return $row[$map[$key]] ?? null;
            }
        }

        return null;
    }

    protected function normalize($value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replace(['.', ',', '/', '\\', '-', '(', ')'], ' ')
            ->replaceMatches('/\s+/', '_')
            ->trim('_')
            ->toString();
    }
}