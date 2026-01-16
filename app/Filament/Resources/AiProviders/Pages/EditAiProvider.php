<?php

namespace App\Filament\Resources\AiProviders\Pages;

use App\Filament\Resources\AiProviders\AiProviderResource;
use App\Models\AiProvider;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAiProvider extends EditRecord
{
    protected static string $resource = AiProviderResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->disabled(fn (callable $get) => $get('is_duplicate')),
            ...(static::canDelete($this->getRecord()) ? [
                $this->getDeleteFormAction(),
            ] : []),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $name = $data['name'] ?? null;
        $slug = $data['slug'] ?? null;

        if ($name || $slug) {
            $exists = AiProvider::where('name', $name)
                ->orWhere('slug', $slug)
                ->where('id', '!=', $this->record->id)
                ->exists();

            if ($exists) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate Provider')
                    ->body('Another AI Provider with this Name or Slug already exists.')
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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'AI Provider updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}