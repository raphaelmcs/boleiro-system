<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;

class TopSellingProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Produtos Mais Vendidos';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $products = Product::query()
            ->withSum(['salesOrderItems as total_sold' => function ($query) {
                $query->whereHas('salesOrder', function ($query) {
                    $query->where('financial_status', 'pago');
                });
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->filter(fn (Product $product): bool => (int) $product->total_sold > 0)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Unidades vendidas',
                    'data' => $products
                        ->map(fn (Product $product): int => (int) $product->total_sold)
                        ->all(),
                    'backgroundColor' => [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                    ],
                ],
            ],
            'labels' => $products
                ->map(fn (Product $product): string => "{$product->team_name} {$product->season}")
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
