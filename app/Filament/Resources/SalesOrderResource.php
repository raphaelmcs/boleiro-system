<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Filament\Resources\SalesOrderResource\RelationManagers;
use App\Models\SalesOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelLabel = 'Pedido de Venda';
    protected static ?string $pluralModelLabel = 'Pedidos de Venda (Outbound)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Cliente e Pedido')
                    ->schema([
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
                        Forms\Components\TextInput::make('tracking_code')
                            ->label('Código de Rastreio (Correios)')
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
                                Forms\Components\Select::make('product_variant_id')
                                    ->relationship('productVariant', 'id', fn ($query) => $query->join('products', 'products.id', '=', 'product_variants.product_id')->selectRaw('product_variants.id, CONCAT(products.team_name, " - ", products.season, " (", product_variants.size, ")") as variant_name')->orderBy('products.team_name'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product->team_name} - {$record->product->season} ({$record->size})")
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
                                    ->reactive()
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('custom_name')
                                    ->label('Nome nas Costas')
                                    ->hidden(fn (Forms\Get $get): bool => ! $get('is_customized'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('custom_number')
                                    ->label('Número')
                                    ->hidden(fn (Forms\Get $get): bool => ! $get('is_customized'))
                                    ->maxLength(255),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Camisa'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Venda')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('financial_status')
                    ->label('Pagamento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aguardando_pagamento' => 'warning',
                        'pago' => 'success',
                        'cancelado' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('dispatch_status')
                    ->label('Envio')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'preparando' => 'gray',
                        'enviado' => 'warning',
                        'entregue' => 'success',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Venda')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
}
