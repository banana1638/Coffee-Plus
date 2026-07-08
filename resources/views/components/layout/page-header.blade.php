@props([
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'flex flex-col gap-4 border-b border-[rgb(var(--cp-line))] pb-5 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-bold text-[rgb(var(--cp-ink))]">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 max-w-2xl text-sm leading-6 text-[rgb(var(--cp-muted))]">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
