<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Pages;

use App\Filament\Resources\SubscriptionPlanModelAccesses\SubscriptionPlanModelAccessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPlanModelAccess extends CreateRecord
{
    protected static string $resource = SubscriptionPlanModelAccessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Subscription Plan Model Access created successfully';
    }
}
