<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $fulfillmentData = $this->getFulfillmentData();
        @endphp

        @if(empty($fulfillmentData))
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow text-center text-gray-500 dark:text-gray-400">
                Nenhum pedido pendente de preparação no momento.
            </div>
        @else
            <div class="rounded-lg bg-white p-4 text-sm text-gray-600 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10">
                Pedidos pendentes em ordem de chegada, do mais antigo para o mais recente. O estoque disponível é alocado nessa ordem.
            </div>

            @foreach($fulfillmentData as $data)
                @php
                    $order = $data['order'];
                    $items = $data['items'];
                    $isFullyFulfillable = $data['is_fully_fulfillable'];
                @endphp
                <div class="p-4 bg-white dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                Pedido #{{ $order->id }} - {{ $order->customer->name }}
                            </h2>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Data do Pedido: {{ $order->order_date->format('d/m/Y') }} | 
                                Pagamento: 
                                @if($order->financial_status === 'pago')
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                        Pago
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                        {{ ucfirst(str_replace('_', ' ', $order->financial_status)) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div>
                            @if($isFullyFulfillable)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                    <x-heroicon-m-check-circle class="w-5 h-5" />
                                    Completo para Envio
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                    <x-heroicon-m-x-circle class="w-5 h-5" />
                                    Faltam Itens
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-800">
                        <table class="w-full table-fixed text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th scope="col" class="w-[52%] px-3 py-2">Produto</th>
                                    <th scope="col" class="w-[12%] px-3 py-2 text-center">Tam.</th>
                                    <th scope="col" class="w-[12%] px-3 py-2 text-center">Ped.</th>
                                    <th scope="col" class="w-[12%] px-3 py-2 text-center">Aloc.</th>
                                    <th scope="col" class="w-[12%] px-3 py-2 text-center">Falta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $itemData)
                                    @php
                                        $item = $itemData['item'];
                                        $productName = $item->productVariant->product->team_name . ' - ' . $item->productVariant->product->season . ' - ' . $item->productVariant->product->gender_label . ' (' . $item->productVariant->product->version_label . ')';
                                        if ($item->is_customized) {
                                            $productName .= ' [Personalizado: ' . $item->custom_name . ' - ' . $item->custom_number . ']';
                                        }
                                    @endphp
                                    <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 {{ $itemData['missing'] > 0 ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                        <td class="px-3 py-3 font-medium text-gray-900 break-words dark:text-white">
                                            {{ $productName }}
                                            @if($itemData['ready_from_customization'])
                                                <span class="ml-2 inline-flex rounded px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                                    Pronto
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            {{ $item->productVariant->size }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            {{ $itemData['required'] }}
                                        </td>
                                        <td class="px-3 py-3 text-center text-green-600 dark:text-green-400 font-bold">
                                            {{ $itemData['allocated'] }}
                                        </td>
                                        <td class="px-3 py-3 text-center {{ $itemData['missing'] > 0 ? 'text-red-600 dark:text-red-400 font-bold' : '' }}">
                                            {{ $itemData['missing'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
