<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\SalesOrderItem;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class InventoryShortageReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Relatórios';

    protected static ?string $navigationLabel = 'Faltas no Estoque';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Relatório de Faltas no Estoque';

    protected static string $view = 'filament.pages.inventory-shortage-report';

    public function getReportData(): array
    {
        $rows = $this->getShortageRows();

        return [
            'total_missing' => $rows->sum('missing_quantity'),
            'total_pending' => $rows->sum('pending_quantity'),
            'total_stock' => $rows->sum('stock_quantity'),
            'unique_products_count' => $rows->pluck('product_id')->unique()->count(),
            'variants_count' => $rows->count(),
            'by_size' => $this->groupRows($rows, 'size'),
            'by_gender' => $this->groupRows($rows, 'gender_label'),
            'by_version' => $this->groupRows($rows, 'version_label'),
            'by_season' => $this->groupRows($rows, 'season'),
            'by_team' => $this->groupRows($rows, 'product_name'),
            'by_product' => $rows
                ->sortByDesc('missing_quantity')
                ->values()
                ->all(),
        ];
    }

    private function getShortageRows(): Collection
    {
        return SalesOrderItem::query()
            ->selectRaw('product_variant_id, SUM(quantity) as pending_quantity')
            ->whereNull('customization_stock_deducted_at')
            ->whereHas('salesOrder', function ($query) {
                $query
                    ->where('financial_status', '!=', 'cancelado')
                    ->where('dispatch_status', '!=', 'entregue');
            })
            ->with('productVariant.product')
            ->groupBy('product_variant_id')
            ->get()
            ->map(function (SalesOrderItem $item): ?array {
                $variant = $item->productVariant;
                $product = $variant?->product;

                if (! $variant || ! $product) {
                    return null;
                }

                $pendingQuantity = (int) $item->pending_quantity;
                $stockQuantity = (int) $variant->stock_quantity;
                $missingQuantity = max(0, $pendingQuantity - $stockQuantity);

                if ($missingQuantity <= 0) {
                    return null;
                }

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->team_name,
                    'season' => $product->season,
                    'gender' => $product->gender,
                    'gender_label' => Product::genderLabel($product->gender),
                    'version' => $product->version,
                    'version_label' => Product::versionLabel($product->version),
                    'size' => $variant->size,
                    'stock_quantity' => $stockQuantity,
                    'pending_quantity' => $pendingQuantity,
                    'missing_quantity' => $missingQuantity,
                ];
            })
            ->filter()
            ->values();
    }

    private function groupRows(Collection $rows, string $key): array
    {
        return $rows
            ->groupBy(fn (array $row): string => filled($row[$key] ?? null) ? (string) $row[$key] : 'Não informado')
            ->map(fn (Collection $items, string $label): array => [
                'label' => $label,
                'missing_quantity' => $items->sum('missing_quantity'),
                'pending_quantity' => $items->sum('pending_quantity'),
                'stock_quantity' => $items->sum('stock_quantity'),
                'products_count' => $items->pluck('product_id')->unique()->count(),
                'variants_count' => $items->count(),
            ])
            ->sortByDesc('missing_quantity')
            ->values()
            ->all();
    }
}
