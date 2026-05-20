@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm transition-colors duration-200 px-4 py-3 bg-gray-50/50 focus:bg-white']) }}>