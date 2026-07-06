@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-lg font-semibold transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 active:translate-y-px';

    $variants = [
        'primary' => 'bg-[rgb(var(--cp-brand))] text-white shadow-sm hover:bg-[rgb(var(--cp-brand-strong))] focus:ring-[rgb(var(--cp-brand))]',
        'secondary' => 'border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] text-[rgb(var(--cp-ink))] shadow-sm hover:bg-white focus:ring-[rgb(var(--cp-brand))]',
        'subtle' => 'text-[rgb(var(--cp-muted))] hover:bg-emerald-50 hover:text-[rgb(var(--cp-brand-strong))] focus:ring-[rgb(var(--cp-brand))]',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus:ring-rose-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'min-h-12 px-5 py-3 text-sm',
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
