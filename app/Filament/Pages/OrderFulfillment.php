<?php

namespace App\Filament\Pages;

use App\Models\ProductVariant;
use App\Models\SalesOrder;
use Filament\Pages\Page;

class OrderFulfillment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Controle de Pedidos';

    protected static ?string $title = 'Controle de Pedidos (Estoque)';

    protected static string $view = 'filament.pages.order-fulfillment';

    public function getFulfillmentData(): array
    {
        $variants = ProductVariant::all()->keyBy('id')->map(function ($variant) {
            $variant->current_stock = $variant->stock_quantity;

            return $variant;
        });

        $orders = SalesOrder::with(['customer', 'items.productVariant.product'])
            ->where('financial_status', '!=', 'cancelado')
            ->where('dispatch_status', 'preparando')
            ->orderBy('order_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $fulfillmentOrders = [];

        foreach ($orders as $order) {
            $orderData = [
                'order' => $order,
                'items' => [],
                'is_fully_fulfillable' => true,
            ];

            foreach ($order->items as $item) {
                $variantId = $item->product_variant_id;
                $required = $item->quantity;
                $variant = $variants->get($variantId);

                if ($item->hasCustomizationStockDeducted()) {
                    $orderData['items'][] = [
                        'item' => $item,
                        'required' => $required,
                        'allocated' => $required,
                        'missing' => 0,
                        'ready_from_customization' => true,
                    ];

                    continue;
                }

                $available = $variant ? $variant->current_stock : 0;
                $allocated = min($required, $available);
                $missing = $required - $allocated;

                if ($variant) {
                    $variant->current_stock -= $allocated;
                }

                if ($missing > 0) {
                    $orderData['is_fully_fulfillable'] = false;
                }

                $orderData['items'][] = [
                    'item' => $item,
                    'required' => $required,
                    'allocated' => $allocated,
                    'missing' => $missing,
                    'ready_from_customization' => false,
                ];
            }

            $fulfillmentOrders[] = $orderData;
        }

        return $fulfillmentOrders;
    }
}
