<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($record) {
            ActivityLogger::log(
                module: static::activityModule(),
                action: 'created',
                title: static::activityTitle('created', $record),
                record: $record,
            );
        });

        static::updated(function ($record) {
            ActivityLogger::log(
                module: static::activityModule(),
                action: 'updated',
                title: static::activityTitle('updated', $record),
                record: $record,
            );
        });

        static::deleted(function ($record) {
            ActivityLogger::log(
                module: static::activityModule(),
                action: 'deleted',
                title: static::activityTitle('deleted', $record),
                record: $record,
            );
        });
    }

    protected static function activityModule(): string
    {
        return property_exists(static::class, 'activityModule')
            ? static::$activityModule
            : class_basename(static::class);
    }

   protected static function activityTitle(string $action, $record): string
{
    $name = $record->name
        ?? $record->title
        ?? $record->display_name
        ?? $record->company_name
        ?? $record->permit_number
        ?? $record->question
        ?? $record->naziv
        ?? $record->naziv_troska
        ?? $record->material_type
        ?? $record->type_of_incident
        ?? $record->referral_number
        ?? $record->broj_procjene
        ?? $record->tvrtka
        ?? $record->ime_prezime
        ?? $record->radno_mjesto
        ?? $record->full_name
        ?? $record->user_last_name
        ?? $record->entry_no
        ?? $record->question?->question
        ?? $record->number
        ?? $record->location
        ?? $record->product_name
        ?? $record->equipment_name
        ?? $record->tekst
        ?? $record->question?->question
        ?? $record->inspection?->title
        ?? $record->kpi?->name
        ?? $record->question?->title
        ?? $record->employee?->name
        ?? $record->godina
        ?? $record->item
        ?? $record->place
        ?? $record->type
        ?? $record->serial_label_number
        ?? $record->location
        ?? $record->subject
        ?? ('ID ' . $record->getKey());

    if ($record instanceof \App\Models\Fire) {
        $parts = array_filter([
            $record->place,
            $record->type,
            $record->serial_label_number,
        ]);

        $name = ! empty($parts)
            ? implode(' - ', $parts)
            : ('ID ' . $record->getKey());
    }

    $actionLabel = match ($action) {
        'created' => 'Kreiran zapis',
        'updated' => 'Uređen zapis',
        'deleted' => 'Obrisan zapis',
        default => 'Aktivnost',
    };

    return $actionLabel . ': ' . $name;
}
}
