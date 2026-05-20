<x-filament-panels::page>
    @php
        $report = $this->getReportData();
    @endphp

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total faltando</div>
                <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $report['total_missing'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Demanda pendente</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $report['total_pending'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Estoque nas faltas</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $report['total_stock'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Camisas com falta</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $report['unique_products_count'] }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">Variações com falta</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $report['variants_count'] }}</div>
            </div>
        </div>

        @if($report['total_missing'] <= 0)
            <div class="rounded-lg bg-white p-4 text-center text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
                Nenhuma falta encontrada para pedidos pendentes.
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-3">
                @include('filament.pages.partials.shortage-summary-table', [
                    'title' => 'Faltas por tamanho',
                    'rows' => $report['by_size'],
                ])

                @include('filament.pages.partials.shortage-summary-table', [
                    'title' => 'Faltas por gênero',
                    'rows' => $report['by_gender'],
                ])

                @include('filament.pages.partials.shortage-summary-table', [
                    'title' => 'Faltas por versão',
                    'rows' => $report['by_version'],
                ])

                @include('filament.pages.partials.shortage-summary-table', [
                    'title' => 'Faltas por temporada',
                    'rows' => $report['by_season'],
                ])

                @include('filament.pages.partials.shortage-summary-table', [
                    'title' => 'Faltas por time/seleção',
                    'rows' => $report['by_team'],
                ])
            </div>

            <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Faltas por camisa</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[54rem] table-fixed text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="w-[38%] px-4 py-3">Camisa</th>
                                <th class="w-[12%] px-4 py-3 text-center">Tam.</th>
                                <th class="w-[14%] px-4 py-3 text-center">Gênero</th>
                                <th class="w-[16%] px-4 py-3 text-center">Versão</th>
                                <th class="w-[10%] px-4 py-3 text-center">Pendente</th>
                                <th class="w-[10%] px-4 py-3 text-center">Falta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($report['by_product'] as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $row['product_name'] }}
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $row['season'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $row['size'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $row['gender_label'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $row['version_label'] }}</td>
                                    <td class="px-4 py-3 text-center">{{ $row['pending_quantity'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-red-600 dark:text-red-400">{{ $row['missing_quantity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
