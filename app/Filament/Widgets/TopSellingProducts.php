<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Product;

class TopSellingProducts extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Produtos Mais Vendidos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->withSum(['salesOrderItems as total_sold' => function ($query) {
                        $query->whereHas('salesOrder', function ($q) {
                            $q->where('financial_status', 'pago');
                        });
                    }], 'quantity')
                    ->orderByDesc('total_sold')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('team_name')
                    ->label('Time')
                    ->searchable(),
                Tables\Columns\TextColumn::make('season')
                    ->label('Temporada'),
                Tables\Columns\TextColumn::make('version')
                    ->label('Versão')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Qtd. Vendida (Unidades)')
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ]);
    }
}
