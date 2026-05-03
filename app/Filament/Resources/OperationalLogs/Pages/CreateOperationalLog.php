<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\WorkTask;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateOperationalLog extends CreateRecord
{
    protected static string $resource = OperationalLogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()?->isSuperAdmin()
            ? ($data['user_id'] ?? Auth::id())
            : Auth::user()?->ownerId();

        $data['log_date'] = $data['log_date'] ?? now()->toDateString();
        $data['status'] = $data['type'] === 'task' ? 'converted' : 'recorded';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);

        if (($data['type'] ?? 'note') === 'task') {
            $task = WorkTask::create([
                'user_id' => $data['user_id'],
                'title' => str($data['note'])->limit(80)->toString(),
                'description' => $data['note'],
                'due_date' => $data['log_date'],
                'is_done' => false,
                'completed_at' => null,
            ]);

            $record->update([
                'status' => 'converted',
                'converted_type' => WorkTask::class,
                'converted_id' => $task->id,
            ]);

            Notification::make()
                ->title('Zapis je spremljen i kreiran je radni zadatak.')
                ->success()
                ->send();
        }

        if (($data['type'] ?? 'note') === 'note') {
            Notification::make()
                ->title('Zapis je spremljen u operativni dnevnik.')
                ->success()
                ->send();
        }

        return $record;
    }
}
