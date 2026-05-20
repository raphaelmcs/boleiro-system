<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Vendas e Clientes';

    protected static ?string $modelLabel = 'Venda para Cliente';

    protected static ?string $pluralModelLabel = 'Vendas para Clientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Cliente e Pedido')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('ID do Pedido (Nuvemshop)')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->hint('Insira o mesmo número gerado na Nuvemshop'),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->label('Cliente')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('order_date')
                            ->label('Data do Pedido')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('financial_status')
                            ->label('Status Financeiro')
                            ->options([
                                'aguardando_pagamento' => 'Aguardando Pagamento',
                                'pago' => 'Pago',
                                'cancelado' => 'Cancelado',
                            ])
                            ->required()
                            ->default('aguardando_pagamento'),
                        Forms\Components\Select::make('dispatch_status')
                            ->label('Status de Envio')
                            ->options([
                                'preparando' => 'Preparando',
                                'enviado' => 'Enviado',
                                'entregue' => 'Entregue',
                            ])
                            ->required()
                            ->default('preparando'),
                        Forms\Components\Select::make('shipping_method')
                            ->label('Forma de Envio')
                            ->options(SalesOrder::shippingMethodOptions())
                            ->required()
                            ->default('correios')
                            ->live(),
                        Forms\Components\TextInput::make('tracking_code')
                            ->label('Código de Rastreio (Correios)')
                            ->hidden(fn (Forms\Get $get): bool => $get('shipping_method') === 'uber')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total da Venda (R$)')
                            ->numeric()
                            ->required()
                            ->default(0.00),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Custo Total (R$)')
                            ->numeric()
                            ->required()
                            ->default(0.00),
                    ])->columns(2),

                Forms\Components\Section::make('Itens da Venda e Personalização')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Hidden::make('sent_to_customization_at'),
                                Forms\Components\Hidden::make('customization_stock_deducted_at'),
                                Forms\Components\Select::make('product_variant_id')
                                    ->options(fn (): array => ProductVariant::optionLabels())
                                    ->getSearchResultsUsing(fn (string $search): array => ProductVariant::optionLabels($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => ProductVariant::with('product')->find($value)?->displayName())
                                    ->label('Produto (Tamanho)')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qtd')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Preço Venda')
                                    ->numeric()
                                    ->required()
                                    ->prefix('R$')
                                    ->default(0.00),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Custo Base')
                                    ->numeric()
                                    ->required()
                                    ->prefix('R$')
                                    ->default(0.00),

                                Forms\Components\Toggle::make('is_customized')
                                    ->label('Tem Personalização?')
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('custom_name')
                                    ->label('Nome nas Costas')
                                    ->hidden(fn (Forms\Get $get): bool => ! $get('is_customized'))
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('custom_number')
                                    ->label('Número')
                                    ->hidden(fn (Forms\Get $get): bool => ! $get('is_customized'))
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Camisa')
                            ->extraItemActions([
                                Forms\Components\Actions\Action::make('send_to_customization')
                                    ->label('Enviar para personalização')
                                    ->icon('heroicon-m-paper-airplane')
                                    ->color('warning')
                                    ->tooltip('Enviar para personalização e baixar estoque')
                                    ->requiresConfirmation()
                                    ->modalHeading('Enviar para personalização')
                                    ->modalDescription('A camisa deste item será retirada do estoque e passará a aparecer no dashboard de personalização.')
                                    ->modalSubmitActionLabel('Enviar')
                                    ->visible(function (array $arguments, Forms\Components\Repeater $component): bool {
                                        $itemData = $component->getRawItemState($arguments['item']);

                                        return (bool) ($itemData['is_customized'] ?? false)
                                            && blank($itemData['sent_to_customization_at'] ?? null);
                                    })
                                    ->action(function (array $arguments, Forms\Components\Repeater $component): void {
                                        self::sendRepeaterItemToCustomization($arguments['item'], $component);
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pedido / Cliente')
                    ->description(fn (SalesOrder $record): string => "#{$record->id} - {$record->order_date->format('d/m/Y')}")
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('financial_status')
                    ->label('Pgto.')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aguardando_pagamento' => 'Aguard.',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancel.',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aguardando_pagamento' => 'warning',
                        'pago' => 'success',
                        'cancelado' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('dispatch_status')
                    ->label('Envio')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'preparando' => 'Prep.',
                        'enviado' => 'Env.',
                        'entregue' => 'Entr.',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'preparando' => 'gray',
                        'enviado' => 'warning',
                        'entregue' => 'success',
                    }),
                Tables\Columns\TextColumn::make('shipping_method')
                    ->label('Entrega')
                    ->formatStateUsing(fn (?string $state): string => SalesOrder::shippingMethodLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'uber' => 'warning',
                        'correios' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stock_fulfillment')
                    ->label('Atendimento')
                    ->getStateUsing(fn (SalesOrder $record): string => $record->stockFulfillmentStatus())
                    ->badge()
                    ->color(fn (SalesOrder $record): string => $record->stockFulfillmentColor())
                    ->tooltip(fn (SalesOrder $record): string => $record->stockFulfillmentTooltip()),
                Tables\Columns\TextColumn::make('customization_stock_status')
                    ->label('Personalização')
                    ->getStateUsing(fn (SalesOrder $record): string => $record->customizedItemsStockStatus())
                    ->badge()
                    ->color(fn (SalesOrder $record): string => $record->customizedItemsStockColor())
                    ->tooltip(fn (SalesOrder $record): string => $record->customizedItemsStockTooltip()),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customized')
                    ->label('Personalizado')
                    ->options([
                        'sim' => 'Sim',
                        'nao' => 'Não',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'sim' => $query->whereHas('items', fn (Builder $query): Builder => $query->where('is_customized', true)),
                            'nao' => $query->whereDoesntHave('items', fn (Builder $query): Builder => $query->where('is_customized', true)),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('kit_infantil')
                    ->label('Kit Infantil')
                    ->options([
                        'sim' => 'Sim',
                        'nao' => 'Não',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'sim' => $query->whereHas('items.productVariant.product', fn (Builder $query): Builder => $query->where('version', 'kit_infantil')),
                            'nao' => $query->whereDoesntHave('items.productVariant.product', fn (Builder $query): Builder => $query->where('version', 'kit_infantil')),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gênero')
                    ->options(Product::genderOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return filled($data['value'] ?? null)
                            ? $query->whereHas('items.productVariant.product', fn (Builder $query): Builder => $query->where('gender', $data['value']))
                            : $query;
                    }),
                Tables\Filters\Filter::make('missing_products')
                    ->label('Falta produto')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('financial_status', '!=', 'cancelado')
                        ->where('dispatch_status', '!=', 'entregue')
                        ->whereHas('items', function (Builder $query): Builder {
                            return $query
                                ->join('product_variants', 'product_variants.id', '=', 'sales_order_items.product_variant_id')
                                ->whereNull('sales_order_items.customization_stock_deducted_at')
                                ->whereColumn('sales_order_items.quantity', '>', 'product_variants.stock_quantity');
                        })),
            ])
            ->actions([
                Tables\Actions\Action::make('deduct_customized_stock')
                    ->label('Enviar personalização')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('warning')
                    ->iconButton()
                    ->tooltip('Enviar itens personalizados para personalização')
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
                Tables\Actions\Action::make('toggle_finished')
                    ->label(fn (SalesOrder $record): string => $record->isFinalized() ? 'Não finalizado' : 'Finalizado')
                    ->icon(fn (SalesOrder $record): string => $record->isFinalized() ? 'heroicon-m-arrow-uturn-left' : 'heroicon-m-check-circle')
                    ->color(fn (SalesOrder $record): string => $record->isFinalized() ? 'gray' : 'success')
                    ->iconButton()
                    ->tooltip(fn (SalesOrder $record): string => $record->isFinalized() ? 'Marcar como não finalizado' : 'Marcar como finalizado')
                    ->requiresConfirmation()
                    ->action(function (SalesOrder $record): void {
                        $previousQuantities = $record->stock_deducted_at
                            ? $record->stockDeductibleItemQuantities()
                            : null;

                        $record->update([
                            'dispatch_status' => $record->isFinalized() ? 'enviado' : 'entregue',
                        ]);

                        $record->syncStockDeductions($previousQuantities);
                    }),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'items.productVariant.product']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }

    private static function sendRepeaterItemToCustomization(string $itemKey, Forms\Components\Repeater $component): void
    {
        $state = $component->getState();
        $sentAt = now()->toDateTimeString();

        if (! Str::startsWith($itemKey, 'record-')) {
            $state[$itemKey]['sent_to_customization_at'] = $sentAt;
            $component->state($state);

            Notification::make()
                ->title('Será enviado ao salvar')
                ->body('Ao salvar a venda, o estoque desta camisa será baixado e o item aparecerá no dashboard de personalização.')
                ->info()
                ->send();

            return;
        }

        $itemData = $component->getItemState($itemKey);
        $itemId = (int) Str::after($itemKey, 'record-');
        $item = SalesOrderItem::find($itemId);

        if (! $item) {
            Notification::make()
                ->title('Item não encontrado')
                ->danger()
                ->send();

            return;
        }

        $item
            ->fill(Arr::only($itemData, [
                'product_variant_id',
                'quantity',
                'unit_price',
                'unit_cost',
                'is_customized',
                'custom_name',
                'custom_number',
            ]))
            ->save();

        $result = $item->salesOrder->sendCustomizedItemToCustomization($item->id);

        if ($result['errors'] !== []) {
            Notification::make()
                ->title('Não foi possível enviar para personalização')
                ->body(implode("\n", $result['errors']))
                ->danger()
                ->send();

            return;
        }

        $state[$itemKey]['sent_to_customization_at'] = $sentAt;
        $state[$itemKey]['customization_stock_deducted_at'] = $sentAt;
        $component->state($state);

        Notification::make()
            ->title('Enviado para personalização')
            ->body("Baixa realizada em {$result['deducted_quantity']} unidade(s).")
            ->success()
            ->send();
    }
}
