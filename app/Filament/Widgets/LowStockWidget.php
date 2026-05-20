<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Alerta de Estoque Baixo ou Zerado';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->where('stock_quantity', '<=', 3)
                    ->with('product')
                    ->orderBy('stock_quantity', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.team_name')
                    ->label('Time/Seleção')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.season')
                    ->label('Temporada'),
                Tables\Columns\TextColumn::make('product.gender')
                    ->label('Gênero')
                    ->formatStateUsing(fn (?string $state): string => Product::genderLabel($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('product.version')
                    ->label('Versão')
                    ->formatStateUsing(fn (?string $state): string => Product::versionLabel($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Tamanho')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Qtd. Estoque')
                    ->badge()
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : 'warning')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_product')
                    ->label('Gerenciar Estoque')
                    ->url(fn (ProductVariant $record): string => route('filament.admin.resources.products.edit', $record->product_id))
                    ->icon('heroicon-m-pencil-square'),
            ]);
    }
}
