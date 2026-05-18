<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $modelLabel = 'Pedido de Compra';
    protected static ?string $pluralModelLabel = 'Pedidos de Compra (Inbound)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Pedido')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Fornecedor')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('order_date')
                            ->label('Data do Pedido')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status')
                            ->label('Status de Rastreio')
                            ->options([
                                'aguardando_envio' => 'Aguardando Envio',
                                'em_transito' => 'Em Trânsito',
                                'alfandega' => 'Na Alfândega',
                                'recebido' => 'Recebido no Estoque',
                            ])
                            ->required()
                            ->default('aguardando_envio'),
                        Forms\Components\TextInput::make('tracking_code')
                            ->label('Código de Rastreio')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Custo Total')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.00),
                    ])->columns(2),

                Forms\Components\Section::make('Itens do Pedido')
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
                                    ->preload(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Custo Unitário')
                                    ->numeric()
                                    ->required()
                                    ->prefix('R$')
                                    ->default(0.00),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Item'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aguardando_envio' => 'gray',
                        'em_transito' => 'warning',
                        'alfandega' => 'danger',
                        'recebido' => 'success',
                    }),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total')
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
