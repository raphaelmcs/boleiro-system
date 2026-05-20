<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex justify-center w-full items-center px-6 py-3 bg-emerald-600 border border-transparent rounded-xl font-bold text-sm text-white uppercase tracking-wider hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md shadow-emerald-600/20 hover:shadow-lg hover:shadow-emerald-600/30']) }}>
    {{ $slot }}
</button>
