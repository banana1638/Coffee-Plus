@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'neutral' => 'border-[rgb(var(--cp-line))] bg-stone-100 text-stone-700',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
        'info' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md border px-2 py-1 text-xs font-semibold ' . ($variants[$variant] ?? $variants['neutral'])]) }}>
    {{ $slot }}
</span>
