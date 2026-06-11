@props([
    'padding' => 'default',
])

@php
    $paddingClass = $padding === 'compact' ? 'p-4' : 'p-4 sm:p-6';
@endphp

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:shadow-md ' . $paddingClass]) }}>
    {{ $slot }}
</section>
