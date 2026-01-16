<?php

namespace App\Filament\Resources\AiProviders\Pages;

use App\Filament\Resources\AiProviders\AiProviderResource;
use App\Models\AiProvider;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProvider extends CreateRecord
{
    protected static string $resource = AiProviderResource::class;

    // Add this method to control the bottom "Create" button
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->disabled(fn (callable $get) => $get('is_duplicate')),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $name = $data['name'] ?? null;
        $slug = $data['slug'] ?? null;

        if ($name || $slug) {
            $exists = AiProvider::where('name', $name)
                ->orWhere('slug', $slug)
                ->exists();

            if ($exists) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate Provider')
                    ->body('An AI Provider with this Name or Slug already exists. Please choose a different name.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'AI Provider created successfully';
    }
}