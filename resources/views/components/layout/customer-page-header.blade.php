@props([
    'title',
    'description' => null,
    'eyebrow' => 'Coffee Plus',
])

<header {{ $attributes->class(['border-b border-[rgb(var(--cp-line))] pb-7 sm:pb-8']) }}>
    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.17em] text-[rgb(var(--cp-brand))]">
                <span>{{ $eyebrow }}</span>
                <span class="h-px w-10 bg-[rgb(var(--cp-caramel))]" aria-hidden="true"></span>
            </div>
            <h1 class="mt-3 font-display text-4xl font-medium leading-none tracking-[-0.035em] text-[rgb(var(--cp-ink))] sm:text-5xl">
                {{ $title }}
            </h1>
            @if($description)
                <p class="mt-4 max-w-2xl text-sm leading-6 text-[rgb(var(--cp-muted))] sm:text-base">
                    {{ $description }}
                </p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
