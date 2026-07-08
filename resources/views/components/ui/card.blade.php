@props([
    'padding' => 'default',
])

@php
    $paddingClass = $padding === 'compact' ? 'p-4' : 'p-4 sm:p-6';
@endphp

<section {{ $attributes->merge(['class' => 'rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm ' . $paddingClass]) }}>
    {{ $slot }}
</section>
