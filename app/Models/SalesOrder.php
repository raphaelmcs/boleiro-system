<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesOrder extends Model
{
    public const SHIPPING_METHOD_OPTIONS = [
        'correios' => 'Correios',
        'uber' => 'Uber',
    ];

    protected $fillable = [
        'id',
        'customer_id',
        'order_date',
        'financial_status',
        'dispatch_status',
        'shipping_method',
        'stock_deducted_at',
        'tracking_code',
        'total_amount',
        'total_cost',
    ];

    protected $casts = [
        'order_date' => 'date',
        'stock_deducted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function shouldDeductStock(): bool
    {
        return $this->isFinalized();
    }

    public function isFinalized(): bool
    {
        return $this->dispatch_status === 'entregue';
    }

    public static function shippingMethodOptions(): array
    {
        return self::SHIPPING_METHOD_OPTIONS;
    }

    public static function shippingMethodLabel(?string $shippingMethod): string
    {
        return self::SHIPPING_METHOD_OPTIONS[$shippingMethod] ?? 'Não informado';
    }

    public function getShippingMethodLabelAttribute(): string
    {
        return self::shippingMethodLabel($this->shipping_method);
    }

    public function hasCustomizedItems(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn (SalesOrderItem $item): bool => $item->is_customized);
        }

        return $this->items()->where('is_customized', true)->exists();
    }

    public function hasCustomizedItemsPendingCustomizationDispatch(): bool
    {
        if ($this->financial_status === 'cancelado' || $this->isFinalized() || $this->stock_deducted_at !== null) {
            return false;
        }

        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn (SalesOrderItem $item): bool => $item->is_customized && ! $item->hasBeenSentToCustomization());
        }

        return $this->items()
            ->where('is_customized', true)
            ->whereNull('sent_to_customization_at')
            ->exists();
    }

    public function hasCustomizedItemsPendingStockDeduction(): bool
    {
        return $this->hasCustomizedItemsPendingCustomizationDispatch();
    }

    public function customizedItemsStockStatus(): string
    {
        $items = $this->customizedItemsForStatus();

        if ($items->isEmpty()) {
            return 'Sem personalização';
        }

        $sentItems = $items->filter(fn (SalesOrderItem $item): bool => $item->hasBeenSentToCustomization())->count();

        if ($sentItems === $items->count()) {
            return 'Enviado';
        }

        return $sentItems > 0 ? 'Parcial' : 'Pendente';
    }

    public function customizedItemsStockColor(): string
    {
        return match ($this->customizedItemsStockStatus()) {
            'Enviado' => 'success',
            'Parcial' => 'warning',
            'Pendente' => 'danger',
            default => 'gray',
        };
    }

    public function customizedItemsStockTooltip(): string
    {
        $items = $this->customizedItemsForStatus();

        if ($items->isEmpty()) {
            return 'Pedido sem itens personalizados.';
        }

        return $items
            ->map(function (SalesOrderItem $item): string {
                $product = $item->productVariant?->displayName() ?? 'Produto Indisponível';
                $status = $item->hasBeenSentToCustomization() ? 'enviado para personalização' : 'pendente';

                return "{$product}: {$status}";
            })
            ->implode("\n");
    }

    public function deductCustomizedItemsFromStock(): array
    {
        return $this->sendPendingCustomizedItemsToCustomization();
    }

    public function sendPendingCustomizedItemsToCustomization(): array
    {
        if ($this->financial_status === 'cancelado') {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => ['Pedido cancelado não pode baixar estoque para personalização.'],
            ];
        }

        if ($this->isFinalized() || $this->stock_deducted_at !== null) {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => ['O estoque deste pedido já foi baixado na finalização.'],
            ];
        }

        return DB::transaction(function (): array {
            $items = $this->items()
                ->where('is_customized', true)
                ->whereNull('sent_to_customization_at')
                ->with('productVariant.product')
                ->lockForUpdate()
                ->get();

            return $this->sendCustomizationItems($items);
        });
    }

    public function sendCustomizedItemToCustomization(int $itemId): array
    {
        if ($this->financial_status === 'cancelado') {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => ['Pedido cancelado não pode enviar item para personalização.'],
            ];
        }

        if ($this->isFinalized() || $this->stock_deducted_at !== null) {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => ['O estoque deste pedido já foi baixado na finalização.'],
            ];
        }

        return DB::transaction(function () use ($itemId): array {
            $items = $this->items()
                ->whereKey($itemId)
                ->where('is_customized', true)
                ->whereNull('sent_to_customization_at')
                ->with('productVariant.product')
                ->lockForUpdate()
                ->get();

            return $this->sendCustomizationItems($items);
        });
    }

    public function sendMarkedCustomizedItemsToCustomization(): array
    {
        if ($this->financial_status === 'cancelado' || $this->isFinalized() || $this->stock_deducted_at !== null) {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => [],
            ];
        }

        return DB::transaction(function (): array {
            $items = $this->items()
                ->where('is_customized', true)
                ->whereNotNull('sent_to_customization_at')
                ->whereNull('customization_stock_deducted_at')
                ->with('productVariant.product')
                ->lockForUpdate()
                ->get();

            return $this->sendCustomizationItems($items, clearSentAtOnError: true);
        });
    }

    private function sendCustomizationItems($items, bool $clearSentAtOnError = false): array
    {
        if ($items->isEmpty()) {
            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => [],
            ];
        }

        $variantIds = $items->pluck('product_variant_id')->unique()->values();
        $variants = ProductVariant::query()
            ->whereKey($variantIds->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $availableByVariant = $variants
            ->map(fn (ProductVariant $variant): int => (int) $variant->stock_quantity)
            ->all();

        $errors = [];
        $quantityByVariant = [];

        foreach ($items as $item) {
            $variant = $variants->get($item->product_variant_id);
            $product = $item->productVariant?->displayName() ?? 'Produto Indisponível';
            $required = (int) $item->quantity;

            if (! $variant) {
                $errors[] = "{$product}: produto não encontrado no estoque.";

                continue;
            }

            $available = $availableByVariant[$variant->id] ?? 0;

            if ($available < $required) {
                $errors[] = "{$product}: disponível {$available}, precisa {$required}.";

                continue;
            }

            $availableByVariant[$variant->id] = $available - $required;
            $quantityByVariant[$variant->id] = ($quantityByVariant[$variant->id] ?? 0) + $required;
        }

        if ($errors !== []) {
            if ($clearSentAtOnError) {
                $items->each(fn (SalesOrderItem $item) => $item
                    ->forceFill(['sent_to_customization_at' => null])
                    ->saveQuietly());
            }

            return [
                'deducted_quantity' => 0,
                'deducted_items' => 0,
                'errors' => $errors,
            ];
        }

        foreach ($quantityByVariant as $variantId => $quantity) {
            ProductVariant::whereKey($variantId)->decrement('stock_quantity', $quantity);
        }

        $sentAt = now();

        foreach ($items as $item) {
            $item
                ->forceFill([
                    'sent_to_customization_at' => $item->sent_to_customization_at ?? $sentAt,
                    'customization_stock_deducted_at' => $sentAt,
                ])
                ->saveQuietly();
        }

        return [
            'deducted_quantity' => array_sum($quantityByVariant),
            'deducted_items' => $items->count(),
            'errors' => [],
        ];
    }

    public function customizationDetails(): string
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('productVariant.product')->get();

        $details = $items
            ->filter(fn (SalesOrderItem $item): bool => $item->is_customized)
            ->map(function (SalesOrderItem $item): string {
                $product = $item->productVariant?->displayName() ?? 'Produto Indisponível';
                $name = $item->custom_name ?: 'Sem nome';
                $number = $item->custom_number ?: 'Sem número';

                return "{$product}: {$name} - {$number}";
            });

        return $details->isEmpty()
            ? 'Sem personalização'
            : $details->implode("\n");
    }

    public function stockFulfillmentStatus(): string
    {
        if ($this->financial_status === 'cancelado') {
            return 'Cancelado';
        }

        if ($this->stock_deducted_at !== null) {
            return 'Já baixado';
        }

        if ($this->dispatch_status === 'enviado') {
            return 'Aguard. finalização';
        }

        $missing = array_sum(array_column($this->stockFulfillmentDetails(), 'missing'));

        return $missing > 0 ? "Faltam {$missing}" : 'Completo';
    }

    public function stockFulfillmentTooltip(): string
    {
        if ($this->financial_status === 'cancelado') {
            return 'Pedido cancelado não entra no levantamento.';
        }

        if ($this->stock_deducted_at !== null) {
            return 'Estoque já foi baixado porque o pedido saiu da preparação.';
        }

        if ($this->dispatch_status === 'enviado') {
            return 'Ao marcar a venda como finalizada, o estoque será baixado automaticamente.';
        }

        $details = collect($this->stockFulfillmentDetails());

        if ($details->isEmpty()) {
            return 'Sem itens cadastrados.';
        }

        if ($details->sum('missing') <= 0) {
            return 'Há estoque suficiente para todos os itens deste pedido.';
        }

        return $details
            ->filter(fn (array $detail): bool => $detail['missing'] > 0)
            ->map(fn (array $detail): string => "{$detail['product']}: faltam {$detail['missing']} de {$detail['required']}")
            ->implode("\n");
    }

    public function stockFulfillmentColor(): string
    {
        return match (true) {
            $this->financial_status === 'cancelado' => 'gray',
            $this->stock_deducted_at !== null => 'info',
            $this->dispatch_status === 'enviado' => 'warning',
            str_starts_with($this->stockFulfillmentStatus(), 'Faltam') => 'danger',
            default => 'success',
        };
    }

    public function stockFulfillmentDetails(): array
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('productVariant.product')->get();

        $availableByVariant = [];
        $details = [];

        foreach ($items as $item) {
            $variant = $item->productVariant;
            $variantId = $item->product_variant_id;

            if ($item->hasCustomizationStockDeducted()) {
                $required = (int) $item->quantity;

                $details[] = [
                    'product' => $variant?->displayName() ?? 'Produto Indisponível',
                    'required' => $required,
                    'available' => $required,
                    'allocated' => $required,
                    'missing' => 0,
                    'ready_from_customization' => true,
                ];

                continue;
            }

            if (! array_key_exists($variantId, $availableByVariant)) {
                $availableByVariant[$variantId] = $variant?->stock_quantity ?? 0;
            }

            $required = (int) $item->quantity;
            $available = $availableByVariant[$variantId];
            $allocated = min($required, $available);
            $missing = $required - $allocated;
            $availableByVariant[$variantId] -= $allocated;

            $details[] = [
                'product' => $variant?->displayName() ?? 'Produto Indisponível',
                'required' => $required,
                'available' => $available,
                'allocated' => $allocated,
                'missing' => $missing,
                'ready_from_customization' => false,
            ];
        }

        return $details;
    }

    public function itemQuantities(): array
    {
        return $this->items()
            ->selectRaw('product_variant_id, SUM(quantity) as quantity')
            ->groupBy('product_variant_id')
            ->pluck('quantity', 'product_variant_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
    }

    public function stockDeductibleItemQuantities(): array
    {
        return $this->items()
            ->whereNull('customization_stock_deducted_at')
            ->selectRaw('product_variant_id, SUM(quantity) as quantity')
            ->groupBy('product_variant_id')
            ->pluck('quantity', 'product_variant_id')
            ->map(fn ($quantity): int => (int) $quantity)
            ->all();
    }

    public function syncStockDeductions(?array $previousQuantities = null): void
    {
        DB::transaction(function () use ($previousQuantities) {
            $wasDeducted = $this->stock_deducted_at !== null;

            if (! $this->shouldDeductStock()) {
                if ($wasDeducted) {
                    $this->applyStockDeltas($this->invertQuantities($previousQuantities ?? $this->stockDeductibleItemQuantities()));
                    $this->forceFill(['stock_deducted_at' => null])->saveQuietly();
                }

                return;
            }

            $currentQuantities = $this->stockDeductibleItemQuantities();

            if (! $wasDeducted) {
                $this->applyStockDeltas($currentQuantities);
                $this->forceFill(['stock_deducted_at' => now()])->saveQuietly();

                return;
            }

            $this->applyStockDeltas($this->calculateQuantityDeltas($previousQuantities ?? [], $currentQuantities));
        });
    }

    private function customizedItemsForStatus()
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('productVariant.product')->get();

        return $items->filter(fn (SalesOrderItem $item): bool => $item->is_customized);
    }

    private function calculateQuantityDeltas(array $previousQuantities, array $currentQuantities): array
    {
        $variantIds = array_unique([
            ...array_keys($previousQuantities),
            ...array_keys($currentQuantities),
        ]);

        $deltas = [];

        foreach ($variantIds as $variantId) {
            $deltas[$variantId] = ($currentQuantities[$variantId] ?? 0) - ($previousQuantities[$variantId] ?? 0);
        }

        return $deltas;
    }

    private function invertQuantities(array $quantities): array
    {
        return array_map(fn (int $quantity): int => $quantity * -1, $quantities);
    }

    private function applyStockDeltas(array $deltas): void
    {
        foreach ($deltas as $variantId => $quantity) {
            if ($quantity > 0) {
                ProductVariant::whereKey($variantId)->decrement('stock_quantity', $quantity);
            }

            if ($quantity < 0) {
                ProductVariant::whereKey($variantId)->increment('stock_quantity', abs($quantity));
            }
        }
    }
}
