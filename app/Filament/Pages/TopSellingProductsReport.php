<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TopSellingProductsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Relatórios';

    protected static ?string $navigationLabel = 'Produtos Mais Vendidos';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Produtos Mais Vendidos';

    protected static string $view = 'filament.pages.top-selling-products-report';

    public function getTopProducts(): Collection
    {
        return Product::query()
            ->withSum(['salesOrderItems as total_sold' => function ($query) {
                $query->whereHas('salesOrder', function ($query) {
                    $query->where('financial_status', 'pago');
                });
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get()
            ->filter(fn (Product $product): bool => (int) $product->total_sold > 0)
            ->values();
    }
}
