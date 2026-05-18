<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\SalesOrder;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $receita = SalesOrder::where('financial_status', 'pago')->sum('total_amount');
        $custo = SalesOrder::where('financial_status', 'pago')->sum('total_cost');
        $lucro = $receita - $custo;

        return [
            Stat::make('Receita Total', 'R$ ' . number_format($receita, 2, ',', '.'))
                ->description('Todas as vendas pagas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Custo Total', 'R$ ' . number_format($custo, 2, ',', '.'))
                ->description('Custo dos produtos vendidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
            Stat::make('Lucro Bruto', 'R$ ' . number_format($lucro, 2, ',', '.'))
                ->description('Receita menos custo')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('success'),
        ];
    }
}
