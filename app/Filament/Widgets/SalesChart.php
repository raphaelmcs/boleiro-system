<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Vendas por Mês';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $sum = SalesOrder::where('financial_status', 'pago')
                ->whereMonth('order_date', $month->month)
                ->whereYear('order_date', $month->year)
                ->sum('total_amount');

            $data[] = $sum;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Vendas (R$)',
                    'data' => $data,
                    'borderColor' => '#10b981', // Tailwind success color
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
