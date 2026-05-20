<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardStats extends BaseWidget
{
    protected static string $view = 'filament.widgets.filtered-dashboard-stats';

    protected static ?int $sort = 2;

    public string $period = 'geral';

    public function updatedPeriod(): void
    {
        $this->cachedStats = null;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->updatedPeriod();
    }

    protected function getStats(): array
    {
        $receita = $this->applyPeriod(
            SalesOrder::where('financial_status', 'pago'),
            'order_date',
        )->sum('total_amount');

        $custoVendido = $this->applyPeriod(
            SalesOrder::where('financial_status', 'pago'),
            'order_date',
        )->sum('total_cost');

        $comprasEstoque = $this->applyPeriod(
            PurchaseOrder::query(),
            'order_date',
        )->sum('total_cost');

        $despesas = $this->applyPeriod(
            Expense::query(),
            'expense_date',
        )->sum('amount');

        $lucroBruto = $receita - $custoVendido;
        $lucroLiquido = $lucroBruto - $comprasEstoque - $despesas;

        return [
            Stat::make('Receita Total', 'R$ '.number_format($receita, 2, ',', '.'))
                ->description('Todas as vendas pagas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Lucro Bruto', 'R$ '.number_format($lucroBruto, 2, ',', '.'))
                ->description('Receita menos custo vendido')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($lucroBruto >= 0 ? 'success' : 'danger'),
            Stat::make('Compras para Estoque', 'R$ '.number_format($comprasEstoque, 2, ',', '.'))
                ->description('Total cadastrado em compras')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('warning'),
            Stat::make('Despesas da Loja', 'R$ '.number_format($despesas, 2, ',', '.'))
                ->description('Taxas, fretes, empréstimos e materiais')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('danger'),
            Stat::make('Lucro Líquido Aprox.', 'R$ '.number_format($lucroLiquido, 2, ',', '.'))
                ->description('Bruto menos compras e despesas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($lucroLiquido >= 0 ? 'success' : 'danger'),
            Stat::make('Custo Vendido', 'R$ '.number_format($custoVendido, 2, ',', '.'))
                ->description('Custo dos produtos vendidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),
        ];
    }

    public function getPeriodOptions(): array
    {
        return [
            'geral' => 'Geral',
            'mes' => 'Mês',
            'quinzenal' => 'Quinzenal',
            'semanal' => 'Semanal',
            'diario' => 'Diário',
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    private function applyPeriod(Builder $query, string $dateColumn): Builder
    {
        $range = $this->periodRange();

        if ($range === null) {
            return $query;
        }

        return $query->whereBetween($dateColumn, [
            $range[0]->toDateString(),
            $range[1]->toDateString(),
        ]);
    }

    private function periodRange(): ?array
    {
        $today = CarbonImmutable::today();

        return match ($this->period) {
            'mes' => [$today->startOfMonth(), $today->endOfMonth()],
            'quinzenal' => [$today->subDays(14), $today],
            'semanal' => [$today->subDays(6), $today],
            'diario' => [$today, $today],
            default => null,
        };
    }
}
