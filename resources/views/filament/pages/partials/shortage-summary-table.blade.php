<div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[24rem] text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Grupo</th>
                    <th class="px-4 py-3 text-center">Camisas</th>
                    <th class="px-4 py-3 text-center">Variações</th>
                    <th class="px-4 py-3 text-center">Falta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-center">{{ $row['products_count'] }}</td>
                        <td class="px-4 py-3 text-center">{{ $row['variants_count'] }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-red-600 dark:text-red-400">{{ $row['missing_quantity'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400" colspan="4">Nenhuma falta encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
