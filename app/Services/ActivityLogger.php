<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $module,
        string $action,
        string $title,
        ?string $description = null,
        ?Model $record = null,
        ?string $url = null,
    ): void {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'owner_id' => $user?->ownerId(),
                'module' => $module,
                'action' => $action,
                'record_type' => $record ? get_class($record) : null,
                'record_id' => $record?->getKey(),
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function import(
        string $module,
        int $created = 0,
        int $updated = 0,
        int $unchanged = 0,
        int $skipped = 0,
        ?string $fileName = null,
    ): void {
        $total = $created + $updated + $unchanged + $skipped;

        $status = $skipped > 0
            ? 'djelomično uspješno'
            : 'uspješno';

        $title = "Import Excel - {$module}";

        $description = trim(implode(' ', array_filter([
            $fileName ? "Datoteka: {$fileName}." : null,
            "Status: {$status}.",
            "Ukupno obrađeno: {$total}.",
            "Dodano: {$created}.",
            "Ažurirano: {$updated}.",
            "Bez promjene: {$unchanged}.",
            "Preskočeno: {$skipped}.",
        ])));

        self::log(
            module: $module,
            action: 'import',
            title: $title,
            description: $description,
        );
    }

    public static function export(
        string $module,
        string $type = 'Excel',
        ?string $description = null,
    ): void {
        self::log(
            module: $module,
            action: 'export',
            title: "Izvoz {$type} - {$module}",
            description: $description,
        );
    }

    public static function status(
        string $module,
        string $title,
        ?string $description = null,
        ?Model $record = null,
    ): void {
        self::log(
            module: $module,
            action: 'status',
            title: $title,
            description: $description,
            record: $record,
        );
    }
}
