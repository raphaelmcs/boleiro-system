<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function afterCreate(): void
    {
        $this->notifyCustomizationDispatchResult($this->record->sendMarkedCustomizedItemsToCustomization());
        $this->record->syncStockDeductions();
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
