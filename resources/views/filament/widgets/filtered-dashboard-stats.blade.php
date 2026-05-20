@php
    $columns = $this->getColumns();
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview grid gap-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
            Financeiro
        </h3>

        <div class="flex flex-wrap gap-2">
            @foreach($this->getPeriodOptions() as $value => $label)
                <button
                    type="button"
                    wire:click="setPeriod('{{ $value }}')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium ring-1 transition',
                        'bg-primary-600 text-white ring-primary-600' => $period === $value,
                        'bg-white text-gray-700 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/5' => $period !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div
        @if ($pollingInterval = $this->getPollingInterval())
            wire:poll.{{ $pollingInterval }}
        @endif
        @class([
            'fi-wi-stats-overview-stats-ctn grid gap-6',
            'md:grid-cols-1' => $columns === 1,
            'md:grid-cols-2' => $columns === 2,
            'md:grid-cols-3' => $columns === 3,
            'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
        ])
    >
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>
</x-filament-widgets::widget>
