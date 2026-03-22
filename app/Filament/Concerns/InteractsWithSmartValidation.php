<?php

namespace App\Filament\Concerns;

use App\Services\SmartValidation\SmartValidationService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait InteractsWithSmartValidation
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     */
    protected function runSmartValidation(array $data, array $rules, ?Model $record = null): void
    {
        $result = app(SmartValidationService::class)->validate($data, $rules, $record);

        // Ako postoje blokirajuće greške, nemoj slati warning notification
        if (! empty($result['blocking'])) {
            throw ValidationException::withMessages($result['blocking']);
        }

        if (! empty($result['warnings'])) {
            Notification::make()
                ->warning()
                ->title('Upozorenje')
                ->body(implode(' | ', $result['warnings']))
                ->duration(6000)
                ->send();
        }
    }
}