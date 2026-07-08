@props([
    'amount' => null,
    'cents' => null,
    'currency' => 'RM',
    'size' => 'base',
    'accent' => false,
    'showCurrency' => true,
])

@php
    $value = $cents !== null ? ((int) $cents) / 100 : (float) $amount;
    $sizeClass = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-lg',
        'xl' => 'text-3xl',
        '2xl' => 'text-4xl',
        default => 'text-base',
    };
    $toneClass = $accent ? 'text-[rgb(var(--cp-accent))]' : 'text-[rgb(var(--cp-ink))]';
@endphp

<span {{ $attributes->class(['cp-tabular inline-flex items-baseline gap-1 font-bold', $sizeClass, $toneClass]) }}>
    @if($showCurrency)
        <span class="text-[0.65em] font-semibold text-slate-500">{{ $currency }}</span>
    @endif
    <span>{{ number_format($value, 2) }}</span>
</span>
