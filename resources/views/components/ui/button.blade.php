@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.98]';

    $variants = [
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 focus:ring-indigo-500',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus:ring-slate-400',
        'subtle' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 focus:ring-slate-400',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus:ring-rose-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-3 text-sm',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
