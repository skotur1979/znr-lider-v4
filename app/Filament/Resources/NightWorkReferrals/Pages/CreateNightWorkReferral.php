<?php

namespace App\Filament\Resources\NightWorkReferrals\Pages;

use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use App\Services\FormVersionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNightWorkReferral extends CreateRecord
{
    protected static string $resource = NightWorkReferralResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()?->ownerId() ?? Auth::id();
        $data['form_version'] = $data['form_version'] ?? FormVersionService::currentNr1();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
            '2xl' => 1,
        ];
    }
}
