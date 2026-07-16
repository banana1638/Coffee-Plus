@props([
    'title',
    'description' => null,
    'eyebrow' => 'Operations console',
])

<header {{ $attributes->class(['flex flex-col gap-5 border-b border-[rgb(var(--cp-line))] pb-6 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <p class="text-[10px] font-semibold uppercase tracking-[0.17em] text-[rgb(var(--cp-brand))]">{{ $eyebrow }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-[-0.025em] text-[rgb(var(--cp-ink))] sm:text-4xl">{{ $title }}</h1>
        @if($description)
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[rgb(var(--cp-muted))]">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
