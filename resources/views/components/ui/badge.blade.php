@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'border-slate-200 bg-slate-100 text-slate-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
        'info' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ' . ($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
