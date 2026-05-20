<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    private ?array $previousItemQuantities = null;

    protected function beforeSave(): void
    {
        $this->previousItemQuantities = $this->record->stock_deducted_at
            ? $this->record->stockDeductibleItemQuantities()
            : null;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->notifyCustomizationDispatchResult($this->record->sendMarkedCustomizedItemsToCustomization());
        $this->record->refresh();
        $this->record->syncStockDeductions($this->previousItemQuantities);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    private function notifyCustomizationDispatchResult(array $result): void
    {
        if ($result['errors'] !== []) {
            Notification::make()
                ->title('Alguns itens não foram enviados para personalização')
                ->body(implode("\n", $result['errors']))
                ->danger()
                ->send();

            return;
        }

        if ($result['deducted_quantity'] <= 0) {
            return;
        }

        Notification::make()
            ->title('Itens enviados para personalização')
            ->body("Baixa realizada em {$result['deducted_quantity']} unidade(s).")
            ->success()
            ->send();
    }
}
