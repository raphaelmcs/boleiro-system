<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Filament\Imports\SalesOrderImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('stock_fulfillment')
                ->label('Levantamento de Estoque')
                ->url(route('filament.admin.pages.order-fulfillment'))
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info'),
            Actions\ImportAction::make()
                ->importer(SalesOrderImporter::class)
                ->label('Importar Nuvemshop (CSV)')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray'),
            Actions\CreateAction::make(),
        ];
    }
}
