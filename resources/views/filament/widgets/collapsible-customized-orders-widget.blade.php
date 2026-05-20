<x-filament-widgets::widget class="fi-wi-table">
    <div
        x-data="{ open: false }"
        class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    >
        <button
            type="button"
            x-on:click="open = ! open"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
        >
            <span class="text-base font-semibold text-gray-950 dark:text-white">
                Personalização
            </span>
            <x-heroicon-m-chevron-down
                class="h-5 w-5 text-gray-500 transition-transform dark:text-gray-400"
                x-bind:class="{ 'rotate-180': open }"
            />
        </button>

        <div x-show="open" x-collapse>
            <div class="border-t border-gray-200 dark:border-white/10">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_START, scopes: static::class) }}

                {{ $this->table }}

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\Widgets\View\WidgetsRenderHook::TABLE_WIDGET_END, scopes: static::class) }}
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
