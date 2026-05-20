<x-filament-panels::page>
    @php
        $products = $this->getTopProducts();
        $maxSold = max((int) $products->max('total_sold'), 1);
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#84cc16', '#ec4899'];
    @endphp

    <div class="space-y-6">
        @if($products->isEmpty())
            <div class="rounded-lg bg-white p-4 text-center text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
                Nenhuma venda paga encontrada para montar o ranking.
            </div>
        @else
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <div class="flex min-h-[22rem] min-w-[52rem] items-end gap-5 px-4 pb-8 pt-6" style="perspective: 900px;">
                        @foreach($products as $index => $product)
                            @php
                                $sold = (int) $product->total_sold;
                                $height = max(34, (int) round(($sold / $maxSold) * 240));
                                $color = $colors[$index % count($colors)];
                            @endphp

                            <div class="flex w-24 shrink-0 flex-col items-center gap-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $sold }}</div>
                                <div class="relative h-[260px] w-16">
                                    <div
                                        class="absolute bottom-0 left-3 w-12 rounded-t-md shadow-lg"
                                        style="
                                            height: {{ $height }}px;
                                            background: {{ $color }};
                                            transform: rotateX(55deg) rotateZ(-36deg);
                                            transform-origin: bottom center;
                                            transform-style: preserve-3d;
                                        "
                                    >
                                        <div
                                            class="absolute left-full top-1 h-full w-5 rounded-r-md opacity-80"
                                            style="background: color-mix(in srgb, {{ $color }} 70%, black); transform: skewY(45deg); transform-origin: left top;"
                                        ></div>
                                        <div
                                            class="absolute bottom-full left-0 h-5 w-full rounded-t-md opacity-90"
                                            style="background: color-mix(in srgb, {{ $color }} 78%, white); transform: skewX(45deg); transform-origin: left bottom;"
                                        ></div>
                                    </div>
                                </div>
                                <div class="h-16 text-center text-xs font-medium leading-4 text-gray-700 dark:text-gray-300">
                                    {{ $product->team_name }}
                                    <div class="font-normal text-gray-500 dark:text-gray-400">{{ $product->season }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ranking</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Produto</th>
                                <th class="px-4 py-3 text-center">Gênero</th>
                                <th class="px-4 py-3 text-center">Versão</th>
                                <th class="px-4 py-3 text-center">Vendidos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($products as $product)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $product->team_name }}
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $product->season }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $product->gender_label }}</td>
                                    <td class="px-4 py-3 text-center">{{ $product->version_label }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ (int) $product->total_sold }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
