<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CustomizedOrdersWidget extends BaseWidget
{
    protected static string $view = 'filament.widgets.collapsible-customized-orders-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pedidos com Personalização (Ordem de Chegada)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesOrder::query()
                    ->where('dispatch_status', '!=', 'entregue')
                    ->whereHas('items', function (Builder $query) {
                        $query
                            ->where('is_customized', true)
                            ->whereNotNull('sent_to_customization_at');
                    })
                    ->with(['items' => function ($query) {
                        $query
                            ->where('is_customized', true)
                            ->whereNotNull('sent_to_customization_at')
                            ->with('productVariant.product');
                    }, 'customer'])
                    ->orderBy('order_date', 'asc')
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customizations')
                    ->label('Detalhes da Personalização')
                    ->getStateUsing(function (SalesOrder $record): string {
                        $details = [];
                        foreach ($record->items as $item) {
                            if ($item->is_customized) {
                                $productName = $item->productVariant?->product?->team_name ?? 'Produto';
                                $name = $item->custom_name ?: 'Sem nome';
                                $number = $item->custom_number ?: 'Sem número';
                                $details[] = "{$productName}: Nome: {$name}, Nº: {$number}";
                            }
                        }

                        return implode(' | ', $details);
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('customization_stock_status')
                    ->label('Status')
                    ->getStateUsing(fn (SalesOrder $record): string => $record->customizedItemsStockStatus())
                    ->badge()
                    ->color(fn (SalesOrder $record): string => $record->customizedItemsStockColor())
                    ->tooltip(fn (SalesOrder $record): string => $record->customizedItemsStockTooltip()),
                Tables\Columns\TextColumn::make('financial_status')
                    ->label('Pagamento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aguardando_pagamento' => 'warning',
                        'pago' => 'success',
                        'cancelado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('deduct_customized_stock')
                    ->label('Enviar estoque')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('warning')
                    ->visible(fn (SalesOrder $record): bool => $record->hasCustomizedItemsPendingCustomizationDispatch())
                    ->requiresConfirmation()
                    ->modalHeading('Enviar para personalização')
                    ->modalDescription('Os itens personalizados deste pedido serão retirados do estoque agora e marcados como prontos.')
                    ->modalSubmitActionLabel('Enviar')
                    ->action(function (SalesOrder $record): void {
                        $result = $record->sendPendingCustomizedItemsToCustomization();

                        if ($result['errors'] !== []) {
                            Notification::make()
                                ->title('Não foi possível enviar para personalização')
                                ->body(implode("\n", $result['errors']))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Enviado para personalização')
                            ->body("Baixa realizada em {$result['deducted_quantity']} unidade(s).")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('view')
                    ->label('Ver Pedido')
                    ->url(fn (SalesOrder $record): string => route('filament.admin.resources.sales-orders.edit', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
